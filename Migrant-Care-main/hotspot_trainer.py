import pandas as pd
import mysql.connector
import numpy as np
from sklearn.cluster import DBSCAN
from datetime import datetime, timedelta

# --- 1. CONFIGURATION: UPDATE THESE ---
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'migrantcare_db'
}
GEODATA_FILE = 'pincodes.csv'
MIN_CASES_FOR_HOTSPOT = 5
HOTSPOT_RADIUS_KM = 2
DISEASES_TO_TRACK = ['Dengue', 'Malaria', 'Typhoid', 'Cholera', 'Common Cold', 'Fever']
DAYS_TO_LOOK_BACK = 30

# --- 2. HELPER FUNCTIONS ---

def get_db_connection():
    """Establishes a connection to the MySQL database."""
    try:
        # --- FINAL FIX: Force autocommit=True ---
        DB_CONFIG['autocommit'] = True
        # --- END OF FIX ---
        
        conn = mysql.connector.connect(**DB_CONFIG)
        print("Successfully connected to the database (autocommit=True).")
        return conn
    except mysql.connector.Error as e:
        print(f"Error connecting to database: {e}")
        return None

def load_geodata(csv_file):
    """Loads the pincode to (lat, lon) mapping file."""
    print(f"Loading pincode geography from {csv_file}...")
    try:
        df_geo = pd.read_csv(csv_file)
        
        # --- DATA CLEANING for Geo-file ---
        # 1. Rename columns
        df_geo = df_geo.rename(columns={
            'Pincode': 'pincode',
            'Latitude': 'latitude',
            'Longitude': 'longitude'
        })
        
        # --- THIS IS THE FIX ---
        # 2. Convert pincode to numeric, dropping invalid rows
        df_geo['pincode'] = pd.to_numeric(df_geo['pincode'], errors='coerce')
        df_geo = df_geo.dropna(subset=['pincode'])
        
        # 3. Convert to integer (now that it's a clean numeric)
        df_geo['pincode'] = df_geo['pincode'].astype(int)
        # --- END OF FIX ---

        # 4. Keep only the columns we need
        df_geo = df_geo[['pincode', 'latitude', 'longitude']]
        
        # 5. Drop duplicates
        df_geo = df_geo.drop_duplicates(subset=['pincode'])
        
        # 6. Convert coordinates to numeric
        df_geo['latitude'] = pd.to_numeric(df_geo['latitude'], errors='coerce')
        df_geo['longitude'] = pd.to_numeric(df_geo['longitude'], errors='coerce')
        
        # 7. Drop rows with invalid coordinates
        df_geo = df_geo.dropna(subset=['latitude', 'longitude'])
        
        print("Successfully loaded, renamed, and converted pincode data.")
        return df_geo
        
    except FileNotFoundError:
        print(f"  FATAL ERROR: Geodata file not found at {csv_file}")
        return None
    except Exception as e:
        print(f"  FATAL ERROR loading geodata: {e}")
        return None
def save_daily_log(conn, log_date, disease, case_count):
    """Saves the daily case count, updating if it already exists."""
    sql = """
    INSERT INTO daily_case_log (log_date, disease, case_count)
    VALUES (%s, %s, %s)
    ON DUPLICATE KEY UPDATE case_count = VALUES(case_count)
    """
    try:
        with conn.cursor() as cursor:
            cursor.execute(sql, (log_date, disease, case_count))
            print(f"  Logged/Updated daily count for {disease} on {log_date}: {case_count} cases.")
    except mysql.connector.Error as e:
        print(f"  ERROR saving daily log for {disease}: {e}")

def fetch_recent_cases(conn, disease, days_back):
    """Fetches recent cases for a specific disease, joining with worker data for pincode."""
    print(f"Fetching data from healthRecords and workers tables for '{disease}'...")
    
    # Calculate the cutoff date
    cutoff_date = (datetime.now() - timedelta(days=days_back)).strftime('%Y-%m-%d')
    
    # --- THIS IS THE CORRECTED QUERY ---
    # We are joining hr.workerId (from healthRecords) with w.id (the primary key of workers)
    query = """
    SELECT 
        hr.workerId, 
        hr.diagnosis,
        hr.reportDate, 
        w.pincode
    FROM 
        healthRecords hr
    JOIN 
        workers w ON hr.workerId = w.id  -- THIS LINE IS NOW FIXED
    WHERE 
        hr.diagnosis = %s 
        AND hr.reportDate >= %s
    """
    
    try:
        df_cases = pd.read_sql(query, conn, params=(disease, cutoff_date))
        
        print(f"  Found {len(df_cases)} cases of '{disease}' in the last {days_back} days.")
        
        if df_cases.empty:
            return df_cases

        # --- DATA CLEANING ---
        df_cases['pincode'] = pd.to_numeric(df_cases['pincode'], errors='coerce')
        original_count = len(df_cases)
        df_cases = df_cases.dropna(subset=['pincode'])
        dropped_count = original_count - len(df_cases)
        if dropped_count > 0:
            print(f"  Dropped {dropped_count} cases due to missing/invalid pincodes.")
            
        if not df_cases.empty:
            df_cases['pincode'] = df_cases['pincode'].astype(int)

        return df_cases
        
    except Exception as e:
        print(f"  ERROR fetching cases: {e}")
        return pd.DataFrame() # Return an empty DataFrame on error

def find_clusters(df_cases, df_geo, min_cases, radius_km):
    """Merges case data with geodata and performs DBSCAN clustering."""
    
    # 1. Merge case data with geography data
    df_merged = df_cases.merge(df_geo, on='pincode', how='left')

    # 2. Convert coordinates to radians for distance calculation
    df_merged['lat_rad'] = np.radians(df_merged['latitude'])
    df_merged['lon_rad'] = np.radians(df_merged['longitude'])

    # 3. Clean the merged data
    df_merged.dropna(subset=['latitude', 'longitude', 'reportDate'], inplace=True)

    if len(df_merged) < min_cases:
        print(f"  Not enough cases with valid coordinates ({len(df_merged)} found) to find clusters.")
        return []

    # 4. Prepare data for clustering
    coords = df_merged[['lat_rad', 'lon_rad']].values
    
    earth_radius_km = 6371
    epsilon = radius_km / earth_radius_km

    # 5. Run DBSCAN
    print(f"  Running DBSCAN clustering on {len(coords)} cases...")
    db = DBSCAN(eps=epsilon, min_samples=min_cases, algorithm='ball_tree', metric='haversine').fit(coords)
    
    df_merged['cluster_label'] = db.labels_
    
    # 6. Process the clusters
    hotspots = []
    unique_clusters = set(db.labels_) - {-1}

    print(f"  Found {len(unique_clusters)} potential hotspots.")

    for cluster_id in unique_clusters:
        cluster_cases = df_merged[df_merged['cluster_label'] == cluster_id]
        
        # Calculate cluster properties
        case_count = len(cluster_cases)
        center_lat = np.degrees(np.mean(cluster_cases['lat_rad']))
        center_lon = np.degrees(np.mean(cluster_cases['lon_rad']))
        pincode = cluster_cases['pincode'].mode()[0]
        # Get the diagnosis (e.g., 'Fever')
        diagnosis = cluster_cases['diagnosis'].mode()[0]
        last_case_date = cluster_cases['reportDate'].max()

        hotspots.append({
            'pincode': int(pincode),
            # --- THIS IS THE FIX ---
            'diagnosis': diagnosis,  # Changed from 'disease'
            # --- END OF FIX ---
            'case_count': case_count,
            'latitude': center_lat,
            'longitude': center_lon,
            'last_reported': last_case_date
        })

    return hotspots

def save_hotspots(conn, hotspots, disease):
    """Saves a list of hotspots to the database for a specific disease."""
    
    # Note: The 'disease' parameter is now just for logging.
    print(f"  Attempting to save {len(hotspots)} new hotspots for '{disease}'...")
    
    # --- THIS IS FIX 2a ---
    sql = """
    INSERT INTO hotspots 
        (pincode, diagnosis, case_count, latitude, longitude, last_reported)
    VALUES 
        (%s, %s, %s, %s, %s, %s)
    """
    # --- END OF FIX ---
    
    saved_count = 0
    try:
        with conn.cursor() as cursor:
            for hotspot in hotspots:
                try:
                    # --- THIS IS FIX 2b ---
                    data_tuple = (
                        hotspot['pincode'],
                        hotspot['diagnosis'], # Changed from 'disease'
                        hotspot['case_count'],
                        hotspot['latitude'],
                        hotspot['longitude'],
                        hotspot['last_reported']
                    )
                    # --- END OF FIX ---
                    
                    cursor.execute(sql, data_tuple)
                    saved_count += 1
                
                except mysql.connector.Error as e:
                    print(f"    DATABASE ERROR during INSERT for hotspot data {hotspot}: {e}")
                except KeyError as e:
                    print(f"    SCRIPT ERROR: Key {e} not found in hotspot data {hotspot}")

        if saved_count > 0:
            print(f"  Successfully saved {saved_count} new hotspots for '{disease}'.")
        else:
            print(f"  WARNING: No hotspots were successfully saved for '{disease}' despite attempts.")
            
    except Exception as e:
        print(f"  FATAL ERROR during save_hotspots: {e}")
        
# --- 3. MAIN EXECUTION ---

def main():
    """Main function to run the hotspot detection and logging process."""
    
    # --- 1. Connect to Database ---
    conn = get_db_connection()
    if conn is None:
        print("Script terminated: Could not connect to the database.")
        return

    # --- 2. Load Geodata ---
    df_geo = load_geodata(GEODATA_FILE)
    if df_geo is None:
        conn.close()
        print("Script terminated: Geodata loading error.")
        return

    # --- 3. Clear Old Hotspots ---
    print("Clearing ALL old hotspots from the 'hotspots' table...")
    try:
        with conn.cursor() as cursor:
            cursor.execute("DELETE FROM hotspots")
        print("  Successfully cleared hotspots table.")
    except Exception as e:
        print(f"  FATAL ERROR: Could not clear hotspots table: {e}")
        conn.close()
        return

    # --- 4. Process Each Disease ---
    today_str = datetime.now().strftime('%Y-%m-%d') 

    for disease in DISEASES_TO_TRACK:
        print(f"\n--- Processing Hotspot Detection for '{disease}' ---")
        
        df_cases = fetch_recent_cases(conn, disease, DAYS_TO_LOOK_BACK)
        
        # --- 4a. LOG DAILY CASE COUNT ---
        today_cases_count = 0
        if not df_cases.empty:
            today_cases_count = df_cases[df_cases['reportDate'].astype(str) == today_str].shape[0]
        
        save_daily_log(conn, today_str, disease, today_cases_count)
            
        # --- 4b. RUN HOTSPOT ANALYSIS ---
        if df_cases.empty or len(df_cases) < MIN_CASES_FOR_HOTSPOT:
            print(f"  Not enough valid cases for '{disease}' ({len(df_cases)} found). Skipping hotspot detection.")
            continue
            
        hotspots = find_clusters(df_cases, df_geo, MIN_CASES_FOR_HOTSPOT, HOTSPOT_RADIUS_KM)
        
        if hotspots:
            # --- THIS IS THE FIX ---
            # We must pass the 'disease' from the loop to the save function.
            save_hotspots(conn, hotspots, disease)
            # --- END OF FIX ---
            
    # --- 5. Finish ---
    conn.close()
    print("\n--- Hotspot and Daily Log processing complete. ---")

if __name__ == "__main__":
    main()
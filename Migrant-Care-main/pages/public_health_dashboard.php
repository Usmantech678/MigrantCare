<?php
// We will need the database connection
require_once 'db.php';

// --- 1. Load Pincode Area Names from CSV ---
$pincodeAreaMap = [];
$csvFilePath = 'pincodes.csv'; 

if (($handle = fopen($csvFilePath, "r")) !== FALSE) {
    $headers = fgetcsv($handle, 1000, ",");
    $pincodeCol = array_search('Pincode', $headers);
    $areaNameCol = array_search('OfficeName', $headers);

    if ($pincodeCol !== false && $areaNameCol !== false) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (isset($data[$pincodeCol]) && isset($data[$areaNameCol])) {
                $pincodeAreaMap[$data[$pincodeCol]] = $data[$areaNameCol];
            }
        }
    }
    fclose($handle);
} else {
    echo "Error: Could not open pincodes.csv file.";
}


// --- 2. Fetch Hotspot Data from Database ---
$hotspots = [];
// Select 'diagnosis' from the hotspots table
$sql_hotspots = "SELECT pincode, diagnosis, case_count, latitude, longitude FROM hotspots ORDER BY case_count DESC";

$result_hotspots = $conn->query($sql_hotspots);

if ($result_hotspots && $result_hotspots->num_rows > 0) {
    while($row = $result_hotspots->fetch_assoc()) {
        $areaName = $pincodeAreaMap[$row['pincode']] ?? 'Unknown Area';
        $hotspots[] = [
            'lat' => (float)$row['latitude'],
            'lon' => (float)$row['longitude'],
            'pincode' => $row['pincode'],
            'areaName' => $areaName,
            'cases' => (int)$row['case_count'],
            // Read from 'diagnosis' but save as 'disease' for the JS
            'disease' => $row['diagnosis'] 
        ];
    }
}

// --- 3. Fetch Trend Data for Chart ---
$trend_labels = [];
$trend_datasets_raw = [];
$all_diseases = [];
$all_dates = [];

$sql_trend = "SELECT log_date, disease, case_count 
              FROM daily_case_log 
              WHERE log_date >= CURDATE() - INTERVAL 30 DAY 
              ORDER BY log_date ASC";

$result_trend = $conn->query($sql_trend);

if ($result_trend && $result_trend->num_rows > 0) {
    while($row = $result_trend->fetch_assoc()) {
        $date = $row['log_date'];
        $disease = $row['disease'];
        $count = (int)$row['case_count'];

        if (!in_array($date, $all_dates)) $all_dates[] = $date;
        if (!in_array($disease, $all_diseases)) $all_diseases[] = $disease;
        
        $trend_datasets_raw[$disease][$date] = $count;
    }
}
sort($all_dates); 

$disease_colors = [
    'Dengue' => 'rgba(255, 99, 132, 0.8)',
    'Malaria' => 'rgba(54, 162, 235, 0.8)',
    'Typhoid' => 'rgba(255, 206, 86, 0.8)',
    'Cholera' => 'rgba(75, 192, 192, 0.8)',
    'Common Cold' => 'rgba(153, 102, 255, 0.8)',
    'Fever' => 'rgba(255, 159, 64, 0.8)'
];
$default_color = 'rgba(201, 203, 207, 0.8)';

$chart_js_datasets = [];
foreach($all_diseases as $disease) {
    $data_points = [];
    foreach($all_dates as $date) {
        $data_points[] = $trend_datasets_raw[$disease][$date] ?? 0;
    }

    $chart_js_datasets[] = [
        'label' => $disease,
        'data' => $data_points,
        'borderColor' => $disease_colors[$disease] ?? $default_color,
        'backgroundColor' => $disease_colors[$disease] ?? $default_color,
        'fill' => false,
        'tension' => 0.1
    ];
}

$chart_data_json = json_encode([
    'labels' => $all_dates,
    'datasets' => $chart_js_datasets
]);

?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    #map { height: 600px; width: 100%; border-radius: 8px; }
    #trendChart { max-height: 400px; }
    .leaflet-popup-content h3 { margin: 0 0 10px; font-size: 1.2rem; border-bottom: 1px solid #eee; padding-bottom: 8px; }
    .leaflet-popup-content p { margin: 5px 0; }
</style>

<div class="p-6 bg-gray-100 min-h-screen">

    <a href="index.php?page=<?php echo htmlspecialchars($_SESSION['role']); ?>_dashboard" 
       class="inline-flex items-center px-4 py-2 mb-4 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
        &larr; <?php echo t('backToDashboard'); ?>
    </a>
    <h1 class="text-3xl font-bold mb-6 text-gray-800"><?php echo t('publicHealthDashboard'); ?></h1>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-xl font-semibold mb-4 text-gray-700">Disease Hotspot Map</h2>
            <div id="map"></div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-xl font-semibold mb-4 text-gray-700">Case Trends (Last 30 Days)</h2>
            <canvas id="trendChart"></canvas>
        </div>

    </div>
</div>

<script>
    // --- 4a. Initialize the Map ---
    const map = L.map('map').setView([20.5937, 78.9629], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    const hotspotsData = <?php echo json_encode($hotspots); ?>;

    if (hotspotsData.length > 0) {
        hotspotsData.forEach(hotspot => {
            const marker = L.marker([hotspot.lat, hotspot.lon]).addTo(map);
            const popupContent = `
                <h3>${hotspot.areaName}</h3>
                <p><strong>Pincode:</strong> ${hotspot.pincode}</p>
                <p><strong>Disease:</strong> ${hotspot.disease}</p>
                <p><strong>No. of Cases:</strong> ${hotspot.cases}</p>
            `;
            marker.bindPopup(popupContent);
        });
        const bounds = L.latLngBounds(hotspotsData.map(h => [h.lat, h.lon]));
        map.fitBounds(bounds, { padding: [50, 50] });
    } else {
        console.log("No hotspot data to display.");
    }

    // --- 4b. Initialize the Trend Chart ---
    const chartData = <?php echo $chart_data_json; ?>;
    const ctx = document.getElementById('trendChart').getContext('2d');
    
    if (chartData.labels.length > 0) {
        const trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: chartData.datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Number of New Cases' } },
                    x: { title: { display: true, text: 'Date' } }
                },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { mode: 'index', intersect: false }
                }
            }
        });
    } else {
        ctx.font = "16px 'Inter', sans-serif";
        ctx.fillStyle = "#6b7280";
        ctx.textAlign = "center";
        ctx.fillText("No case trend data available yet.", ctx.canvas.width / 2, 50);
    }
</script>
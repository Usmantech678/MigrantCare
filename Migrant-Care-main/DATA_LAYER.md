## MigrantCare — Data Layer Documentation

This document extracts and codifies the data-layer for the MigrantCare app. It provides an inferred schema (based on the application's PHP code), example CREATE TABLE statements, recommended indexes and constraints, sample queries, backup/restore commands, and seeding examples.

Location: repository root (next to `PRESENTATION_LAYER.md` and `BUSINESS_LAYER.md`).

---

### Important note on source of truth

I attempted to read `database.sql` but it was empty. The schema below is therefore inferred from the application's code (`index.php`, `pages/*.php`, and `functions.php`). Before applying any CREATE TABLE statements to a production DB, please review and adapt field types, lengths, and constraints to your real data and foreign-key relationships.

---

## Table inventory (inferred)
- `users` — authentication and top-level user record (mobile login, role, profileComplete)
- `workers` — migrant worker profiles
- `doctors` — doctor profiles
- `healthRecords` — consultation records created by doctors
- `labReports` — uploaded lab report metadata and file path
- `referrals` — specialist referral records
- `hotspots` — geo-located outbreak summary used by public health dashboard
- `daily_case_log` — time-series entries used for trends/charts

---

## Recommended CREATE TABLE examples

Note: these are example SQL statements you can use as a starting point. Adjust VARCHAR lengths, engine, charset, and NULLability as needed.

```sql
-- users
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mobileNumber VARCHAR(20) NOT NULL UNIQUE,
  pin VARCHAR(50) NOT NULL,
  role ENUM('worker','doctor') DEFAULT NULL,
  profileComplete TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- workers
CREATE TABLE workers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  userId INT NOT NULL,
  fullName VARCHAR(255) DEFAULT NULL,
  age VARCHAR(10) DEFAULT NULL,
  homeState VARCHAR(100) DEFAULT NULL,
  phoneNumber VARCHAR(30) DEFAULT NULL,
  emergencyContact VARCHAR(30) DEFAULT NULL,
  currentLocation VARCHAR(255) DEFAULT NULL,
  pincode VARCHAR(20) DEFAULT NULL,
  esiNumber VARCHAR(100) DEFAULT NULL,
  kycVerified TINYINT(1) DEFAULT 0,
  qrCode VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_workers_user FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY ux_workers_esi (esiNumber)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- doctors
CREATE TABLE doctors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  userId INT NOT NULL,
  fullName VARCHAR(255) NOT NULL,
  licenseId VARCHAR(100) DEFAULT NULL,
  hospital VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_doctors_user FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- healthRecords
CREATE TABLE healthRecords (
  id INT AUTO_INCREMENT PRIMARY KEY,
  workerId INT NOT NULL,
  doctorName VARCHAR(255) NOT NULL,
  symptoms TEXT,
  diagnosis TEXT,
  reportDate DATE,
  recordDate DATE,
  prescription TEXT,
  notes TEXT,
  followUpDate DATE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_health_worker FOREIGN KEY (workerId) REFERENCES workers(id) ON DELETE CASCADE,
  INDEX idx_health_worker_recordDate (workerId, recordDate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- labReports
CREATE TABLE labReports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  workerId INT NOT NULL,
  reportName VARCHAR(255) DEFAULT NULL,
  testDate DATE DEFAULT NULL,
  labName VARCHAR(255) DEFAULT NULL,
  fileURL VARCHAR(1024) NOT NULL,
  doctorNotes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_lab_worker FOREIGN KEY (workerId) REFERENCES workers(id) ON DELETE CASCADE,
  INDEX idx_lab_worker_testDate (workerId, testDate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- referrals
CREATE TABLE referrals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  workerId INT NOT NULL,
  specialist VARCHAR(255) DEFAULT NULL,
  notes TEXT,
  referringDoctor VARCHAR(255) DEFAULT NULL,
  referralDate DATE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_referral_worker FOREIGN KEY (workerId) REFERENCES workers(id) ON DELETE CASCADE,
  INDEX idx_referral_worker_date (workerId, referralDate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- hotspots (geo summary for dashboard)
CREATE TABLE hotspots (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pincode VARCHAR(20) NOT NULL,
  diagnosis VARCHAR(255) DEFAULT NULL,
  case_count INT DEFAULT 0,
  latitude DECIMAL(10,7) DEFAULT NULL,
  longitude DECIMAL(10,7) DEFAULT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_hotspots_pincode (pincode),
  INDEX idx_hotspots_cases (case_count)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- daily_case_log (time-series for trends)
CREATE TABLE daily_case_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pincode VARCHAR(20) DEFAULT NULL,
  disease VARCHAR(255) DEFAULT NULL,
  case_date DATE NOT NULL,
  case_count INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_daily_case_pincode_date (pincode, case_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Indexing & performance recommendations
- Add indexes on any column used frequently in WHERE clauses (e.g., `workers.userId`, `healthRecords.workerId`, `hotspots.pincode`, `daily_case_log.case_date`).
- Use composite indexes when queries filter by multiple columns (e.g., `workerId, recordDate`).
- For large text searches (symptoms/notes), consider a full-text index on the `diagnosis` or `symptoms` columns using InnoDB fulltext (MySQL 5.6+) or an external search engine for complex queries.
- Keep `uploads/lab_reports/` on a filesystem or object storage (S3) and store only URLs in the DB to prevent large BLOBs in MySQL.

---

## Referential integrity and transactions
- Where operations must succeed together (for example KYC: update `workers` then `users`), wrap statements in a transaction to avoid partial updates.

Example (pseudo-PHP using mysqli):

```php
$conn->begin_transaction();
try {
  // update workers
  // update users
  $conn->commit();
} catch (Exception $e) {
  $conn->rollback();
  throw $e;
}
```

---

## Sample queries (quick examples)

```sql
-- Get latest records for a worker
SELECT * FROM healthRecords WHERE workerId = ? ORDER BY recordDate DESC LIMIT 20;

-- Get hotspots for map (top N)
SELECT pincode, diagnosis, case_count, latitude, longitude FROM hotspots ORDER BY case_count DESC LIMIT 50;

-- Trend data for a pincode
SELECT case_date, SUM(case_count) AS total_cases FROM daily_case_log WHERE pincode = ? GROUP BY case_date ORDER BY case_date;
```

---

## Backup and restore

For local XAMPP / MySQL, use `mysqldump` to export. Example commands for PowerShell:

```powershell
# dump the database
mysqldump -u root -p migratcare_db > C:\backups\migrantcare-db-$(Get-Date -Format yyyyMMddHHmmss).sql

# restore
mysql -u root -p migratcare_db < C:\backups\migrantcare-db-latest.sql
```

Replace `migratcare_db` with your actual DB name. Consider automated nightly dumps and storing them off-machine (S3 or a secure backup server).

---

## Seeding example (small sample inserts)

```sql
INSERT INTO users (mobileNumber, pin, role, profileComplete) VALUES ('9999999999','1234','worker',0);
INSERT INTO users (mobileNumber, pin, role, profileComplete) VALUES ('8888888888','1234','doctor',1);

-- Create a worker record for the first user
INSERT INTO workers (userId, fullName, age, homeState, phoneNumber, emergencyContact, currentLocation, pincode)
VALUES (1, 'Test Worker', '30', 'State A', '9999999999', '9876543210', 'City X', '110001');

-- Create a doctor record for the second user
INSERT INTO doctors (userId, fullName, licenseId, hospital) VALUES (2, 'Test Doctor', 'LIC-12345', 'City Hospital');
```

---

## Migrations and versioning
- Keep SQL migrations in a `migrations/` folder and name them with an increasing numeric prefix (e.g., `001_create_users.sql`, `002_create_workers.sql`).
- Consider using a lightweight migration tool (Phinx, Flyway, or Doctrine Migrations) if the project grows.

---

## Security & maintenance notes
- Enforce the use of prepared statements (the app already uses them in many handlers).
- Rotate DB credentials and keep them outside the repo (use environment variables or a secrets manager).
- Regularly check files in `uploads/` for size and malware; consider virus scanning for uploaded lab reports.

---

## Next steps & optional improvements
- If you want, I can generate a `migrations/` folder with versioned SQL files based on these CREATE statements.
- I can also create a small ER diagram (SVG/PNG) from the schema and add it to the repo.
- If you prefer to use `PDO` with transactions and connection pooling recommendations, I can provide a `db.php` migration to PDO.

---

If you want the file split into smaller per-module docs (for example `DATA_USERS.md`, `DATA_HEALTH.md`) or added to the project README, tell me how you want it organized and I will split it accordingly.

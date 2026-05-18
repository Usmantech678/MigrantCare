## MigrantCare — Business Layer Documentation

This document extracts the business-layer code from the MigrantCare app: database connection, domain helpers, server-side action handlers, file upload handling, AI wrappers, and server-side data assembly for dashboards. The snippets are copy/paste-ready with a short use-case for each.

Location: repository root (next to `PRESENTATION_LAYER.md`).

---

### File inventory (business-focused)
- `db.php` — MySQL connection and charset handling
- `functions.php` — domain helpers, auth guards, AI wrappers, translations (shared)
- `index.php` — central request router and POST action handlers (login/register, record CRUD, KYC, etc.)
- `pages/public_health_dashboard.php` — server-side data assembly for hotspots and charts
- `pages/patient_record.php` — server-side queries for patient-related resources (records, referrals, lab reports)
- `uploads/` — storage location for lab report files (file-upload target)

---

## Snippets (copy/paste ready)

1) Database connection (`db.php`)

Use case: Central MySQLi connection with graceful error handling and utf8mb4 charset.

```php
// db.php
mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "migrantcare_db";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("Connection failed: " . $e->getMessage() . 
        "<br><br><strong>Common Fix:</strong> Please ensure the 'MySQL' service is running in your XAMPP Control Panel.");
}
```

2) Auth & domain helpers (`functions.php`)

Use case: Small domain functions used throughout the app: login checks and model helpers.

```php
// functions.php (auth & model helpers)
function isLoggedIn() { return isset($_SESSION['user_id']); }

function requireLogin() {
    if (!isLoggedIn()) { header('Location: index.php?page=login'); exit(); }
}

function getWorker($conn, $userId) {
    $stmt = $conn->prepare("SELECT * FROM workers WHERE userId = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getDoctor($conn, $userId) {
    $stmt = $conn->prepare("SELECT * FROM doctors WHERE userId = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
```

3) AI / External service wrappers (`functions.php`)

Use case: Wrap calls to the external AI (Generative Language API) and return parsed results for the presentation layer.

```php
// functions.php (AI wrapper excerpts)
function getSymptomsFromAI($userInput) {
    $apiKey = "AIzaSy..."; // move to environment/config for production
    $apiUrl = "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-pro:generateContent?key=" . $apiKey;
    $prompt = "You are a helpful medical symptom identifier... User's Text: \"{$userInput}\"";
    $data = ['contents'=>[['parts'=>[['text'=>$prompt]]]], 'safetySettings'=>[]];
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode !== 200) { error_log("AI API Error HTTP {$httpCode} Response: {$response}"); return null; }
    $result = json_decode($response, true);
    $rawText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '[]';
    $cleaned = preg_replace('/```json\s*|\s*```/', '', $rawText);
    $symptoms = json_decode(trim($cleaned), true);
    return is_array($symptoms) ? $symptoms : [];
}

function getAIChatResponse($userInput) {
    $apiKey = "AIzaSy..."; // store securely in env
    $apiUrl = "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-pro:generateContent?key=" . $apiKey;
    $systemPrompt = "You are a friendly and empathetic health assistant...";
    $fullPrompt = $systemPrompt . "\n\nUSER'S MESSAGE:\n\"" . $userInput . "\"\n\nAI ASSISTANT'S RESPONSE:";
    $data = ['contents'=>[['parts'=>[['text'=>$fullPrompt]]]], 'safetySettings'=>[]];
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($httpCode !== 200) { error_log("AI Chat Error HTTP {$httpCode} cURL Error: {$error} Response: {$response}"); return "I'm sorry, I'm having trouble connecting right now. Please try again later."; }
    $result = json_decode($response, true);
    if (empty($result['candidates'])) { return "I'm sorry, I couldn't get a valid response. Please try again."; }
    $aiText = $result['candidates'][0]['content']['parts'][0]['text'] ?? "I'm sorry, I couldn't process that.";
    return $aiText;
}
```

4) Login & Registration handlers (from `index.php` POST actions)

Use case: Authenticate users and create accounts; set session and redirect.

```php
// index.php (login)
case 'login':
    $mobile = $conn->real_escape_string($_POST['mobileNumber']);
    $pin = $conn->real_escape_string($_POST['pin']);
    $stmt = $conn->prepare("SELECT * FROM users WHERE mobileNumber = ?");
    $stmt->bind_param("s", $mobile);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user && $pin === $user['pin']) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        if (empty($user['role'])) { header('Location: index.php?page=role_selection'); }
        elseif (!$user['profileComplete']) { header('Location: index.php?page=' . $user['role'] . '_setup'); }
        else { header('Location: index.php?page=' . $user['role'] . '_dashboard'); }
        exit();
    }
    $_SESSION['error'] = 'Invalid mobile number or PIN.';
    header('Location: index.php?page=login');
    exit();

// index.php (register)
case 'register':
    $mobile = $_POST['mobile'];
    $pin = $_POST['pin'];
    $check = $conn->prepare("SELECT id FROM users WHERE mobileNumber = ?");
    $check->bind_param("s", $mobile);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
         $_SESSION['error'] = 'This mobile number is already registered.';
         header('Location: index.php?page=register'); exit();
    }
    $stmt = $conn->prepare("INSERT INTO users (mobileNumber, pin) VALUES (?, ?)");
    $stmt->bind_param("ss", $mobile, $pin);
    if ($stmt->execute()) {
        $_SESSION['user_id'] = $stmt->insert_id;
        header('Location: index.php?page=role_selection');
    } else {
        $_SESSION['error'] = 'An error occurred during registration.';
        header('Location: index.php?page=register');
    }
    exit();
```

5) Role selection & profile setup (index.php)

Use case: Assign role and insert/update profile records (worker/doctor).

```php
// index.php (select role)
case 'select_role':
    if (isLoggedIn()) {
        $role = $_POST['role']; $userId = $_SESSION['user_id'];
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $role, $userId); $stmt->execute();
        $_SESSION['role'] = $role;
        header('Location: index.php?page=' . $role . '_setup'); exit();
    }
    break;

// index.php (worker_setup)
case 'worker_setup':
    if (isLoggedIn()) {
        $userId = $_SESSION['user_id'];
        $workerCheck = getWorker($conn, $userId);
        if ($workerCheck) {
            $stmt = $conn->prepare("UPDATE workers SET fullName=?, age=?, homeState=?, phoneNumber=?, emergencyContact=?, currentLocation=?, pincode=? WHERE userId=?");
            $stmt->bind_param("sssssssi", $_POST['fullName'], $_POST['age'], $_POST['homeState'], $_POST['phoneNumber'], $_POST['emergencyContact'], $_POST['currentLocation'], $_POST['pincode'], $userId);
        } else {
            $stmt = $conn->prepare("INSERT INTO workers (userId, fullName, age, homeState, phoneNumber, emergencyContact, currentLocation, pincode) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssss", $userId, $_POST['fullName'], $_POST['age'], $_POST['homeState'], $_POST['phoneNumber'], $_POST['emergencyContact'], $_POST['currentLocation'], $_POST['pincode']);
        }
        $stmt->execute();
        header('Location: index.php?page=kyc_selection'); exit();
    }
    break;

// index.php (doctor_setup)
case 'doctor_setup':
    if (isLoggedIn()) {
        $userId = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO doctors (userId, fullName, licenseId, hospital) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $userId, $_POST['fullName'], $_POST['licenseId'], $_POST['hospital']);
        $stmt->execute();
        $updateUser = $conn->prepare("UPDATE users SET profileComplete = 1 WHERE id = ?");
        $updateUser->bind_param("i", $userId); $updateUser->execute();
        header('Location: index.php?page=doctor_dashboard'); exit();
    }
    break;
```

6) KYC verification (index.php)

Use case: Mark worker KYC verified and set user `profileComplete`.

```php
// index.php (verify_kyc)
case 'verify_kyc':
    if (isLoggedIn()) {
        $worker = getWorker($conn, $_SESSION['user_id']);
        if ($worker) {
             $workerId = $worker['id'];
             $qrCode = "WORKER-".$workerId;
             $stmt = $conn->prepare("UPDATE workers SET kycVerified = 1, qrCode = ? WHERE id = ?");
             $stmt->bind_param("si", $qrCode, $workerId); $stmt->execute();
             $updateUser = $conn->prepare("UPDATE users SET profileComplete = 1 WHERE id = ?");
             $updateUser->bind_param("i", $_SESSION['user_id']); $updateUser->execute();
        }
        header('Location: index.php?page=worker_dashboard'); exit();
    }
    break;
```

7) Patient record CRUD & lab reports (index.php + `pages/patient_record.php`)

Use case: Doctors add/update records and upload lab reports; server validates files and stores DB records.

```php
// index.php (add_record)
case 'add_record':
    if (isLoggedIn() && $_SESSION['role'] === 'doctor') {
        $workerId = $_POST['workerId'];
        $doctor = getDoctor($conn, $_SESSION['user_id']);
        $doctorName = "Dr. " . $doctor['fullName'];
        $followUpDate = empty($_POST['followUpDate']) ? NULL : $_POST['followUpDate'];

        $stmt = $conn->prepare("INSERT INTO healthRecords (workerId, doctorName, symptoms, diagnosis, reportDate, recordDate, prescription, notes, followUpDate) VALUES (?, ?, ?, ?, CURDATE(), CURDATE(), ?, ?, ?)");
        $stmt->bind_param("issssss", $workerId, $doctorName, $_POST['symptoms'], $_POST['diagnosis'], $_POST['prescription'], $_POST['notes'], $followUpDate);
        if ($stmt->execute()) { $_SESSION['success'] = "Health record added successfully."; }
        header('Location: index.php?page=patient_record&id=' . $workerId); exit();
    }
    break;

// index.php (add_lab_report)
case 'add_lab_report':
    if (isLoggedIn() && $_SESSION['role'] === 'doctor') {
        $workerId = $_POST['workerId'];
        if (!isset($_FILES['reportFile']) || $_FILES['reportFile']['error'] != 0) { $_SESSION['error'] = "File upload error."; header('Location: index.php?page=patient_record&id=' . $workerId); exit(); }
        if ($_FILES['reportFile']['size'] > 10000000) { $_SESSION['error'] = "File is too large."; header('Location: index.php?page=patient_record&id=' . $workerId); exit(); }
        $uploadDir = 'uploads/lab_reports/';
        $fileExtension = strtolower(pathinfo($_FILES['reportFile']['name'], PATHINFO_EXTENSION));
        $allowedTypes = ['pdf','jpg','jpeg','png'];
        if (!in_array($fileExtension, $allowedTypes)) { $_SESSION['error'] = "Invalid file type."; header('Location: index.php?page=patient_record&id=' . $workerId); exit(); }
        $uniqueFilename = uniqid('report_', true) . '.' . $fileExtension;
        $uploadFilePath = $uploadDir . $uniqueFilename;
        if (move_uploaded_file($_FILES['reportFile']['tmp_name'], $uploadFilePath)) {
            $stmt = $conn->prepare("INSERT INTO labReports (workerId, reportName, testDate, labName, fileURL, doctorNotes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $workerId, $_POST['reportName'], $_POST['testDate'], $_POST['labName'], $uploadFilePath, $_POST['doctorNotes']);
            if ($stmt->execute()) { $_SESSION['success'] = "Lab report uploaded successfully."; }
            else { $_SESSION['error'] = "Database error: Failed to save report details."; }
        } else { $_SESSION['error'] = "Server error: Could not move uploaded file."; }
        header('Location: index.php?page=patient_record&id=' . $workerId); exit();
    }
    break;
```

8) Update / refer / edit record

Use case: Modify existing consultation records and create referrals.

```php
// index.php (update_record)
case 'update_record':
    if (isLoggedIn() && $_SESSION['role'] === 'doctor') {
        $recordId = $_POST['recordId']; $workerId = $_POST['workerId'];
        $followUpDate = empty($_POST['followUpDate']) ? NULL : $_POST['followUpDate'];
        $stmt = $conn->prepare("UPDATE healthRecords SET symptoms=?, diagnosis=?, prescription=?, notes=?, followUpDate=? WHERE id=?");
        $stmt->bind_param("sssssi", $_POST['symptoms'], $_POST['diagnosis'], $_POST['prescription'], $_POST['notes'], $followUpDate, $recordId);
        if ($stmt->execute()) { $_SESSION['success'] = "Health record updated successfully."; }
        header('Location: index.php?page=patient_record&id=' . $workerId); exit();
    }
    break;

// index.php (refer_patient)
case 'refer_patient':
    if (isLoggedIn() && $_SESSION['role'] === 'doctor') {
        $workerId = $_POST['workerId'];
        $doctor = getDoctor($conn, $_SESSION['user_id']);
        $referringDoctor = "Dr. " . $doctor['fullName'];
        $stmt = $conn->prepare("INSERT INTO referrals (workerId, specialist, notes, referringDoctor, referralDate) VALUES (?, ?, ?, ?, CURDATE())");
        $stmt->bind_param("isss", $workerId, $_POST['specialist'], $_POST['notes'], $referringDoctor);
        if ($stmt->execute()) { $_SESSION['success'] = "Patient referred successfully."; }
        header('Location: index.php?page=patient_record&id=' . $workerId); exit();
    }
    break;
```

9) Link ESI card (unique constraint check)

Use case: Link an ESI number to a worker, ensuring uniqueness across workers.

```php
// index.php (link_esi)
case 'link_esi':
    if (isLoggedIn() && $_SESSION['role'] === 'worker') {
        $esiNumber = trim($_POST['esiNumber']); $userId = $_SESSION['user_id'];
        $checkStmt = $conn->prepare("SELECT id FROM workers WHERE esiNumber = ? AND userId != ?");
        $checkStmt->bind_param("si", $esiNumber, $userId); $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) { $_SESSION['error'] = 'This ESI number is already linked to another account.'; header('Location: index.php?page=link_esi'); exit(); }
        $updateStmt = $conn->prepare("UPDATE workers SET esiNumber = ? WHERE userId = ?"); $updateStmt->bind_param("si", $esiNumber, $userId);
        if ($updateStmt->execute()) $_SESSION['success'] = 'ESI Card linked successfully!'; else $_SESSION['error'] = 'Failed to link ESI card. Please try again.';
        header('Location: index.php?page=worker_dashboard'); exit();
    }
    break;
```

10) Public health data assembly (`pages/public_health_dashboard.php`)

Use case: Server-side assembly of hotspots and trend data for frontend visualization (reads CSV, queries DB, prepares JSON payloads).

```php
// public_health_dashboard.php (server-side excerpt)
$pincodeAreaMap = [];
$csvFilePath = 'pincodes.csv';
if (($handle = fopen($csvFilePath, "r")) !== FALSE) {
    $headers = fgetcsv($handle, 1000, ",");
    $pincodeCol = array_search('Pincode', $headers);
    $areaNameCol = array_search('OfficeName', $headers);
    if ($pincodeCol !== false && $areaNameCol !== false) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (isset($data[$pincodeCol]) && isset($data[$areaNameCol])) { $pincodeAreaMap[$data[$pincodeCol]] = $data[$areaNameCol]; }
        }
    }
    fclose($handle);
}

$hotspots = [];
$sql_hotspots = "SELECT pincode, diagnosis, case_count, latitude, longitude FROM hotspots ORDER BY case_count DESC";
$result_hotspots = $conn->query($sql_hotspots);
if ($result_hotspots && $result_hotspots->num_rows > 0) {
    while($row = $result_hotspots->fetch_assoc()) {
        $areaName = $pincodeAreaMap[$row['pincode']] ?? 'Unknown Area';
        $hotspots[] = ['lat'=>(float)$row['latitude'],'lon'=>(float)$row['longitude'],'pincode'=>$row['pincode'],'areaName'=>$areaName,'cases'=>(int)$row['case_count'],'disease'=>$row['diagnosis']];
    }
}

// Trend data assembly omitted for brevity (see repo for full example)
$chart_data_json = json_encode(['labels'=>[], 'datasets'=>[]]);
```

11) Patient record queries (`pages/patient_record.php`)

Use case: Load patient profile, healthRecords, referrals and labReports for doctor view.

```php
// patient_record.php (server queries)
$worker_id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM workers WHERE id = ?"); $stmt->bind_param("i", $worker_id); $stmt->execute(); $patient = $stmt->get_result()->fetch_assoc();

$stmt_records = $conn->prepare("SELECT * FROM healthRecords WHERE workerId = ? ORDER BY recordDate DESC"); $stmt_records->bind_param("i", $worker_id); $stmt_records->execute(); $records = $stmt_records->get_result();

$stmt_referrals = $conn->prepare("SELECT * FROM referrals WHERE workerId = ? ORDER BY referralDate DESC"); $stmt_referrals->bind_param("i", $worker_id); $stmt_referrals->execute(); $referrals = $stmt_referrals->get_result();

$stmt_lab_reports = $conn->prepare("SELECT * FROM labReports WHERE workerId = ? ORDER BY testDate DESC"); $stmt_lab_reports->bind_param("i", $worker_id); $stmt_lab_reports->execute(); $lab_reports = $stmt_lab_reports->get_result();
```

12) Defensive redirect for missing worker (business guard)

Use case: Redirect worker users to setup if their profile is missing.

```php
// worker_dashboard.php (guard)
$worker = getWorker($conn, $_SESSION['user_id']);
if ($worker === null) { header('Location: index.php?page=worker_setup'); exit(); }
```

---

Notes & recommendations
- Consider splitting `index.php` action handlers into separate controller functions for maintainability and testing.
- Move secrets (API keys) out of `functions.php` into environment variables or a secure config file.
- Add transactions where multiple DB updates must succeed together (KYC flow: update workers + users).
- Add stricter server-side validation for user input and uploaded file MIME types.

If you want this file split into smaller per-module docs or added to the repo README, tell me where to place it or how to format it.

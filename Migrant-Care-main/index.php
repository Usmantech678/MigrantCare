<?php
// /migrantcare/index.php

session_start();
require_once 'db.php';
require_once 'functions.php';

// --- HANDLE POST REQUESTS (FORM SUBMISSIONS) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    switch ($action) {
        // ## THIS IS THE CORRECTED BLOCK ##
        case 'set_language':
            $_SESSION['language'] = $_POST['language'] ?? 'en';
            
            // If a redirect URL is provided (from the footer switcher), use it.
            // Otherwise, it's the first-time selection, so always go to the login page.
            if (isset($_POST['redirect_url']) && !empty($_POST['redirect_url'])) {
                $redirect_url = $_POST['redirect_url'];
            } else {
                $redirect_url = 'index.php?page=login';
            }
            
            header('Location: ' . $redirect_url);
            exit();

        // ... all other cases remain the same
        
        case 'login':
            $mobile = $conn->real_escape_string($_POST['mobileNumber']);
            $pin = $conn->real_escape_string($_POST['pin']);
            $stmt = $conn->prepare("SELECT * FROM users WHERE mobileNumber = ?");
            $stmt->bind_param("s", $mobile);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if ($pin === $user['pin']) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];
                    if (empty($user['role'])) {
                         header('Location: index.php?page=role_selection');
                    } elseif (!$user['profileComplete']) {
                        header('Location: index.php?page=' . $user['role'] . '_setup');
                    } else {
                         header('Location: index.php?page=' . $user['role'] . '_dashboard');
                    }
                    exit();
                }
            }
            $_SESSION['error'] = 'Invalid mobile number or PIN.';
            header('Location: index.php?page=login');
            exit();

        case 'register':
            $mobile = $_POST['mobile'];
            $pin = $_POST['pin'];
            $check = $conn->prepare("SELECT id FROM users WHERE mobileNumber = ?");
            $check->bind_param("s", $mobile);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                 $_SESSION['error'] = 'This mobile number is already registered.';
                 header('Location: index.php?page=register');
                 exit();
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

        case 'select_role':
            if (isLoggedIn()) {
                $role = $_POST['role'];
                $userId = $_SESSION['user_id'];
                $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->bind_param("si", $role, $userId);
                $stmt->execute();
                $_SESSION['role'] = $role;
                header('Location: index.php?page=' . $role . '_setup');
                exit();
            }
            break;

        case 'worker_setup':
            if (isLoggedIn()) {
                $userId = $_SESSION['user_id'];
                // Check if worker profile already exists
                $workerCheck = getWorker($conn, $userId);
                
                if ($workerCheck) {
                    // Profile exists, so UPDATE it
                    $stmt = $conn->prepare("UPDATE workers SET fullName=?, age=?, homeState=?, phoneNumber=?, emergencyContact=?, currentLocation=?, pincode=? WHERE userId=?");
                    $stmt->bind_param("sssssssi", $_POST['fullName'], $_POST['age'], $_POST['homeState'], $_POST['phoneNumber'], $_POST['emergencyContact'], $_POST['currentLocation'], $_POST['pincode'], $userId);
                } else {
                    // Profile does not exist, so INSERT it
                    $stmt = $conn->prepare("INSERT INTO workers (userId, fullName, age, homeState, phoneNumber, emergencyContact, currentLocation, pincode) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("isssssss", $userId, $_POST['fullName'], $_POST['age'], $_POST['homeState'], $_POST['phoneNumber'], $_POST['emergencyContact'], $_POST['currentLocation'], $_POST['pincode']);
                }
                
                $stmt->execute();
                
                // We no longer set profileComplete=1 here.
                // We just send to the next step.
                header('Location: index.php?page=kyc_selection');
                exit();
            }
            break;

        case 'doctor_setup':
            if (isLoggedIn()) {
                $userId = $_SESSION['user_id'];
                $stmt = $conn->prepare("INSERT INTO doctors (userId, fullName, licenseId, hospital) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $userId, $_POST['fullName'], $_POST['licenseId'], $_POST['hospital']);
                $stmt->execute();
                $updateUser = $conn->prepare("UPDATE users SET profileComplete = 1 WHERE id = ?");
                $updateUser->bind_param("i", $userId);
                $updateUser->execute();
                header('Location: index.php?page=doctor_dashboard');
                exit();
            }
            break;

        case 'verify_kyc':
            if (isLoggedIn()) {
                $worker = getWorker($conn, $_SESSION['user_id']);
                if ($worker) {
                     $workerId = $worker['id'];
                     $qrCode = "WORKER-".$workerId;
                     $stmt = $conn->prepare("UPDATE workers SET kycVerified = 1, qrCode = ? WHERE id = ?");
                     $stmt->bind_param("si", $qrCode, $workerId); 
                     $stmt->execute();
                     
                     // --- THIS IS THE FIX ---
                     // NOW that KYC is done, we mark the profile as complete
                     $updateUser = $conn->prepare("UPDATE users SET profileComplete = 1 WHERE id = ?");
                     $updateUser->bind_param("i", $_SESSION['user_id']);
                     $updateUser->execute();
                }
                
                // Send to the dashboard
                header('Location: index.php?page=worker_dashboard');
                exit();
            }
            break;

        
        case 'view_patient':
            $workerQrId = $_POST['workerId'];
            $id = (int) preg_replace('/[^0-9]/', '', $workerQrId);
            if ($id > 0) {
                 header('Location: index.php?page=patient_record&id=' . $id);
            } else {
                 $_SESSION['error'] = 'Invalid Worker ID format.';
                 header('Location: index.php?page=doctor_dashboard');
            }
            exit();

        case 'link_esi':
            if (isLoggedIn() && $_SESSION['role'] === 'worker') {
                $esiNumber = trim($_POST['esiNumber']);
                $userId = $_SESSION['user_id'];
                $checkStmt = $conn->prepare("SELECT id FROM workers WHERE esiNumber = ? AND userId != ?");
                $checkStmt->bind_param("si", $esiNumber, $userId);
                $checkStmt->execute();
                $result = $checkStmt->get_result();
                if ($result->num_rows > 0) {
                    $_SESSION['error'] = 'This ESI number is already linked to another account.';
                    header('Location: index.php?page=link_esi');
                    exit();
                }
                $updateStmt = $conn->prepare("UPDATE workers SET esiNumber = ? WHERE userId = ?");
                $updateStmt->bind_param("si", $esiNumber, $userId);
                if ($updateStmt->execute()) {
                    $_SESSION['success'] = 'ESI Card linked successfully!';
                } else {
                    $_SESSION['error'] = 'Failed to link ESI card. Please try again.';
                }
                header('Location: index.php?page=worker_dashboard');
                exit();
            }
            break;
            
        case 'add_record':
            if (isLoggedIn() && $_SESSION['role'] === 'doctor') {
                $workerId = $_POST['workerId'];
                $doctor = getDoctor($conn, $_SESSION['user_id']);
                $doctorName = "Dr. " . $doctor['fullName'];
                $followUpDate = empty($_POST['followUpDate']) ? NULL : $_POST['followUpDate'];

                $stmt = $conn->prepare("INSERT INTO healthRecords (workerId, doctorName, symptoms, diagnosis, reportDate, recordDate, prescription, notes, followUpDate) VALUES (?, ?, ?, ?, CURDATE(), CURDATE(), ?, ?, ?)");
                $stmt->bind_param("issssss", $workerId, $doctorName, $_POST['symptoms'], $_POST['diagnosis'], $_POST['prescription'], $_POST['notes'], $followUpDate);
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Health record added successfully.";
                }
                header('Location: index.php?page=patient_record&id=' . $workerId);
                exit();
            }
            break;

        case 'update_record':
            if (isLoggedIn() && $_SESSION['role'] === 'doctor') {
                $recordId = $_POST['recordId'];
                $workerId = $_POST['workerId'];
                $followUpDate = empty($_POST['followUpDate']) ? NULL : $_POST['followUpDate'];

                $stmt = $conn->prepare("UPDATE healthRecords SET symptoms=?, diagnosis=?, prescription=?, notes=?, followUpDate=? WHERE id=?");
                $stmt->bind_param("sssssi", $_POST['symptoms'], $_POST['diagnosis'], $_POST['prescription'], $_POST['notes'], $followUpDate, $recordId);
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Health record updated successfully.";
                }
                header('Location: index.php?page=patient_record&id=' . $workerId);
                exit();
            }
            break;

        case 'refer_patient':
             if (isLoggedIn() && $_SESSION['role'] === 'doctor') {
                $workerId = $_POST['workerId'];
                $doctor = getDoctor($conn, $_SESSION['user_id']);
                $referringDoctor = "Dr. " . $doctor['fullName'];
                
                $stmt = $conn->prepare("INSERT INTO referrals (workerId, specialist, notes, referringDoctor, referralDate) VALUES (?, ?, ?, ?, CURDATE())");
                $stmt->bind_param("isss", $workerId, $_POST['specialist'], $_POST['notes'], $referringDoctor);
                 if ($stmt->execute()) {
                    $_SESSION['success'] = "Patient referred successfully.";
                }
                header('Location: index.php?page=patient_record&id=' . $workerId);
                exit();
            }
            break;

        case 'add_lab_report':
            if (isLoggedIn() && $_SESSION['role'] === 'doctor') {
                $workerId = $_POST['workerId'];
                if (!isset($_FILES['reportFile']) || $_FILES['reportFile']['error'] != 0) {
                    $errorCode = $_FILES['reportFile']['error'] ?? 'Unknown';
                    $_SESSION['error'] = "File upload error. Code: " . $errorCode . ". Please try again.";
                    header('Location: index.php?page=patient_record&id=' . $workerId);
                    exit();
                }
                if ($_FILES['reportFile']['size'] > 10000000) { 
                    $_SESSION['error'] = "File is too large. Maximum size is 10MB.";
                    header('Location: index.php?page=patient_record&id=' . $workerId);
                    exit();
                }
                $uploadDir = 'uploads/lab_reports/';
                $fileExtension = strtolower(pathinfo($_FILES['reportFile']['name'], PATHINFO_EXTENSION));
                $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png'];
                if (!in_array($fileExtension, $allowedTypes)) {
                    $_SESSION['error'] = "Invalid file type. Only PDF, JPG, and PNG are allowed.";
                    header('Location: index.php?page=patient_record&id=' . $workerId);
                    exit();
                }
                $uniqueFilename = uniqid('report_', true) . '.' . $fileExtension;
                $uploadFilePath = $uploadDir . $uniqueFilename;
                if (move_uploaded_file($_FILES['reportFile']['tmp_name'], $uploadFilePath)) {
                    $stmt = $conn->prepare("INSERT INTO labReports (workerId, reportName, testDate, labName, fileURL, doctorNotes) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("isssss", $workerId, $_POST['reportName'], $_POST['testDate'], $_POST['labName'], $uploadFilePath, $_POST['doctorNotes']);
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Lab report uploaded successfully.";
                    } else {
                        $_SESSION['error'] = "Database error: Failed to save report details.";
                    }
                } else {
                    $_SESSION['error'] = "Server error: Could not move uploaded file. Check folder permissions.";
                }
                header('Location: index.php?page=patient_record&id=' . $workerId);
                exit();
            }
            break;
    }
}

// --- PAGE ROUTING ---
$page = $_GET['page'] ?? (isLoggedIn() ? ($_SESSION['role'] . '_dashboard') : 'language_selection');
$allowed_pages = [
    'language_selection', 'login', 'register', 'role_selection', 'worker_setup', 'doctor_setup',
    'kyc_selection', 'aadhaar_verification', 'other_id_verification', 'health_profile_setup',
    'worker_dashboard', 'doctor_dashboard', 'patient_record', 'worker_health_history', 'edit_record',
    'hospital_recommendation', 'precaution_alerts', 'public_health_dashboard', 'ai_symptom_checker',
    'first_aid', 'financial_aid', 'link_esi', 'consent_forms', 'mental_health_screening', 'rewards', 'logout'
];
if (!in_array($page, $allowed_pages)) { $page = 'login'; }
if ($page === 'logout') { session_destroy(); header('Location: index.php?page=login'); exit(); }

// --- RENDER VIEW ---
include 'pages/shared/header.php';
if (file_exists("pages/{$page}.php")) { include "pages/{$page}.php"; }
else { echo "<div class='container card'><h1 class='title'>Page Not Found</h1></div>"; }
include 'pages/shared/footer.php';
?>
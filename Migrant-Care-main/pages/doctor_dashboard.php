<?php
// /migrantcare/pages/doctor_dashboard.php
requireLogin();
if ($_SESSION['role'] !== 'doctor') { header('Location: index.php'); exit(); }
$doctor = getDoctor($conn, $_SESSION['user_id']);

// Count treated patients
$stmt = $conn->prepare("SELECT count(id) as count FROM healthrecords WHERE doctorName = ?");
$doctorName = "Dr. " . $doctor['fullName'];
$stmt->bind_param("s", $doctorName);
$stmt->execute();
$treatedCount = $stmt->get_result()->fetch_assoc()['count'];
?>
<div class="dashboard-container">
     <div class="header">
        <h2 class="header-title"><?php echo t('welcomeMessage'); ?> Dr. <?php echo htmlspecialchars($doctor['fullName']); ?></h2>
        <a href="index.php?page=logout" class="link-button"><?php echo t('logout'); ?></a>
    </div>
    <div class="grid">
        <div class="card">
            <h3 class="subtitle"><?php echo t('patientLookup'); ?></h3>
            <form action="index.php" method="POST" class="form">
                <input type="hidden" name="action" value="view_patient">
                <input class="input" name="workerId" placeholder="<?php echo t('enterWorkerID'); ?>" required>
                <button type="submit" class="button"><?php echo t('viewPatientRecord'); ?></button>
            </form>
        </div>
        <div class="card">
            <h3 class="subtitle"><?php echo t('publicHealthTitle'); ?></h3>
            <p><?php echo t('publicHealthDescription'); ?></p>
            <a href="index.php?page=public_health_dashboard" class="button"><?php echo t('viewDashboard'); ?></a>
        </div>
        <div class="card">
            <h3 class="subtitle"><?php echo t('treatedPatients'); ?></h3>
            <p style="font-size: 48px; text-align: center; font-weight: bold; color: var(--primary-color);"><?php echo $treatedCount; ?></p>
        </div>
    </div>
</div>
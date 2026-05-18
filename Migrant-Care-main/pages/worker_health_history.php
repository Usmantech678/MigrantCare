<?php
// /migrantcare/pages/worker_health_history.php
requireLogin();
if ($_SESSION['role'] !== 'worker') { header('Location: index.php'); exit(); }
$worker = getWorker($conn, $_SESSION['user_id']);

// Fetch health records (consultations)
$stmt_records = $conn->prepare("SELECT * FROM healthRecords WHERE workerId = ? ORDER BY recordDate DESC");
$stmt_records->bind_param("i", $worker['id']);
$stmt_records->execute();
$records = $stmt_records->get_result();

// ## NEW: Fetch lab reports for the user ##
$stmt_reports = $conn->prepare("SELECT * FROM labReports WHERE workerId = ? ORDER BY testDate DESC");
$stmt_reports->bind_param("i", $worker['id']);
$stmt_reports->execute();
$lab_reports = $stmt_reports->get_result();

$active_tab = $_GET['tab'] ?? 'overview';
?>

<div class="dashboard-container">
    <div class="header">
        <a href="index.php?page=worker_dashboard" class="link-button">&lt; <?php echo t('backToDashboard'); ?></a>
    </div>
    <h1 class="title"><?php echo t('viewMyHistory'); ?></h1>

    <div class="tabs-container">
        <a href="index.php?page=worker_health_history&tab=overview" class="<?php echo $active_tab === 'overview' ? 'tab-button-active' : 'tab-button'; ?>"><?php echo t('overviewTab'); ?></a>
        <a href="index.php?page=worker_health_history&tab=consultations" class="<?php echo $active_tab === 'consultations' ? 'tab-button-active' : 'tab-button'; ?>"><?php echo t('consultationsTab'); ?></a>
        <a href="index.php?page=worker_health_history&tab=reports" class="<?php echo $active_tab === 'reports' ? 'tab-button-active' : 'tab-button'; ?>"><?php echo t('labReports'); ?></a>
    </div>
    
    <div class="tab-content">
        <?php if ($active_tab === 'overview'): ?>
            <div class="card">
                <h3 class="subtitle"><?php echo t('myHealthProfile'); ?></h3>
                <div class="profile-grid">
                    <div><span class="bold">Height:</span> <?php echo htmlspecialchars($worker['height'] ?: 'N/A'); ?> cm</div>
                    <div><span class="bold">Weight:</span> <?php echo htmlspecialchars($worker['weight'] ?: 'N/A'); ?> kg</div>
                    <div><span class="bold">Blood Group:</span> <?php echo htmlspecialchars($worker['bloodGroup'] ?: 'N/A'); ?></div>
                    <div><span class="bold">Smoking:</span> <?php echo htmlspecialchars($worker['smoking'] ?: 'N/A'); ?></div>
                    <div style="grid-column: 1 / -1;"><span class="bold">Allergies:</span> <?php echo htmlspecialchars($worker['allergies'] ?: 'None'); ?></div>
                    <div style="grid-column: 1 / -1;"><span class="bold">Chronic Conditions:</span> <?php echo htmlspecialchars($worker['chronicDiseases'] ?: 'None'); ?></div>
                </div>
            </div>
        <?php elseif ($active_tab === 'consultations'): ?>
            <div class="card">
                <?php if ($records->num_rows > 0): ?>
                    <?php while($record = $records->fetch_assoc()): ?>
                        <div class="record-item">
                            <p class="record-date"><?php echo htmlspecialchars($record['recordDate']); ?> - <span class="bold"><?php echo htmlspecialchars($record['diagnosis']); ?></span></p>
                            <p><span class="bold">Symptoms:</span> <?php echo htmlspecialchars($record['symptoms']); ?></p>
                            <p><span class="bold">Prescription:</span> <?php echo htmlspecialchars($record['prescription'] ?: 'N/A'); ?></p>
                            <p><span class="bold">Doctor's Notes:</span> <?php echo htmlspecialchars($record['notes'] ?: 'N/A'); ?></p>
                            <p><span class="bold">Doctor:</span> <?php echo htmlspecialchars($record['doctorName']); ?></p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>You have no consultation records yet.</p>
                <?php endif; ?>
            </div>
        
        <?php elseif ($active_tab === 'reports'): ?>
             <div class="card">
                <?php if ($lab_reports->num_rows > 0): ?>
                    <?php while($report = $lab_reports->fetch_assoc()): ?>
                        <div class="record-item">
                            <p><span class="bold"><?php echo htmlspecialchars($report['reportName']); ?></span></p>
                            <p class="record-date">
                                <?php echo t('testDate'); ?> <?php echo htmlspecialchars($report['testDate']); ?> <?php echo t('at'); ?> <?php echo htmlspecialchars($report['labName']); ?>
                            </p>
                            <p><span class="bold"><?php echo t('doctorsNotes'); ?></span> <?php echo htmlspecialchars($report['doctorNotes'] ?: 'N/A'); ?></p>
                            <a href="<?php echo htmlspecialchars($report['fileURL']); ?>" class="link-button" target="_blank" rel="noopener noreferrer" style="margin-top: 5px; padding-left: 0;">
                                <?php echo t('viewReport'); ?>
                            </a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>You have no lab reports yet.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
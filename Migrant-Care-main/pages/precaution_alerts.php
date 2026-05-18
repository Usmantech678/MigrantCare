<?php
// /migrantcare/pages/precaution_alerts.php
requireLogin();

// Mock data from source
$alerts = [
    ['id' => 1, 'title' => 'dengueFever', 'details' => 'dengueDetails', 'precautions' => 'denguePrecautions', 'prescription' => 'denguePrescription', 'hotspot' => true],
    ['id' => 2, 'title' => 'malaria', 'details' => 'malariaDetails', 'precautions' => 'malariaPrecautions', 'prescription' => 'malariaPrescription', 'hotspot' => false],
    ['id' => 3, 'title' => 'typhoid', 'details' => 'typhoidDetails', 'precautions' => 'typhoidPrecautions', 'prescription' => 'typhoidPrescription', 'hotspot' => false],
];
?>
<div class="dashboard-container">
    <div class="header">
        <a href="index.php?page=worker_dashboard" class="link-button">&lt; <?php echo t('backToDashboard'); ?></a>
    </div>
    <h1 class="title"><?php echo t('healthAlerts'); ?></h1>

    <?php foreach($alerts as $alert): ?>
        <div class="card" style="<?php echo $alert['hotspot'] ? 'border-left: 5px solid var(--danger);' : ''; ?>">
            <h2 class="header-title"><?php echo t($alert['title']); ?></h2>
            <?php if ($alert['hotspot']): ?>
                <p class="error-text" style="text-align: left; font-weight: bold;"><?php echo t('hotspotWarning'); ?></p>
            <?php endif; ?>
            <p><?php echo t($alert['details']); ?></p>
            <div class="record-item">
                <p><span class="bold">Recommended Precautions:</span></p>
                <p><?php echo t($alert['precautions']); ?></p>
            </div>
            <div>
                <p><span class="bold">Sample Prescription (for information only):</span></p>
                <p><?php echo t($alert['prescription']); ?></p>
            </div>
        </div>
    <?php endforeach; ?>
</div>
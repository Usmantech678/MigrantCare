<?php
// /migrantcare/pages/worker_dashboard.php
requireLogin();
if ($_SESSION['role'] !== 'worker') { header('Location: index.php'); exit(); }

$worker = getWorker($conn, $_SESSION['user_id']);

// --- THIS IS THE FIX ---
// If worker data is not found, their profile is incomplete. Redirect to setup.
if ($worker === null) {
    header('Location: index.php?page=worker_setup');
    exit();
}
// --- END OF FIX ---
?>
<div class="dashboard-container">
    <div class="header">
        <h2 class="header-title"><?php echo t('welcomeMessage'); ?> <?php echo htmlspecialchars($worker['fullName']); ?></h2>
        <a href="index.php?page=logout" class="link-button"><?php echo t('logout'); ?></a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <div class="card">
        <h3 class="subtitle" style="text-align: left;"><?php echo t('yourHealthID'); ?></h3>
        <?php if ($worker && $worker['kycVerified'] && $worker['qrCode']): ?>
            <div class="qr-code-container">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode($worker['qrCode']); ?>" alt="QR Code">
                <p class="qr-code-text"><?php echo htmlspecialchars($worker['qrCode']); ?></p>
            </div>
            <p class="subtitle"><?php echo t('showQRCode'); ?></p>
        <?php else: ?>
            <p class="subtitle">Complete identity verification to get your Health ID.</p>
            <a href="index.php?page=kyc_selection" class="button">Verify My Identity</a>
        <?php endif; ?>
    </div>

    <div class="grid">
        <a href="index.php?page=worker_health_history" class="grid-button"><span class="grid-icon">📄</span> <?php echo t('viewMyHistory'); ?></a>
        <a href="index.php?page=hospital_recommendation" class="grid-button"><span class="grid-icon">🏥</span> <?php echo t('findHospital'); ?></a>
        <a href="index.php?page=precaution_alerts" class="grid-button"><span class="grid-icon">🔔</span> <?php echo t('healthAlerts'); ?></a>
        <a href="index.php?page=ai_symptom_checker" class="grid-button"><span class="grid-icon">🤖</span> <?php echo t('symptomChecker'); ?></a>
        <a href="index.php?page=mental_health_screening" class="grid-button"><span class="grid-icon">🧠</span> <?php echo t('mentalHealthScreening'); ?></a>
        <a href="index.php?page=first_aid" class="grid-button"><span class="grid-icon">🩹</span> <?php echo t('firstAidGuide'); ?></a>
        <a href="index.php?page=financial_aid" class="grid-button"><span class="grid-icon">💰</span> <?php echo t('financialAid'); ?></a>
        <a href="index.php?page=rewards" class="grid-button"><span class="grid-icon">⭐</span> <?php echo t('rewardsTitle'); ?></a>
        
        <?php if ($worker && !empty($worker['esiNumber'])): ?>
            <div class="grid-button grid-button-success">
                <span class="grid-icon">✅</span>
                <?php echo t('esiLinked'); ?><br>
                <small><?php echo htmlspecialchars($worker['esiNumber']); ?></small>
            </div>
        <?php else: ?>
            <a href="index.php?page=link_esi" class="grid-button">
                <span class="grid-icon">💳</span> <?php echo t('linkESICard'); ?>
            </a>
        <?php endif; ?>
    </div>
</div>
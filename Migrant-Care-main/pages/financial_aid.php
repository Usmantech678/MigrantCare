<?php
// /migrantcare/pages/financial_aid.php
requireLogin();

$schemes = [
    ['title' => 'Pradhan Mantri Shram Yogi Maan-dhan (PM-SYM)', 'description' => 'A voluntary and contributory pension scheme for unorganized workers.'],
    ['title' => 'Ayushman Bharat Pradhan Mantri Jan Arogya Yojana (AB-PMJAY)', 'description' => 'A national public health insurance fund of India that aims to provide free access to health insurance coverage for low income earners.'],
    ['title' => 'Aam Admi Bima Yojana (AABY)', 'description' => 'A social security scheme for rural landless households.'],
];
?>
<div class="dashboard-container">
    <div class="header">
        <a href="index.php?page=worker_dashboard" class="link-button">&lt; <?php echo t('backToDashboard'); ?></a>
    </div>
    <h1 class="title"><?php echo t('financialAid'); ?></h1>
    <?php foreach($schemes as $scheme): ?>
    <div class="card">
        <h2 class="header-title"><?php echo htmlspecialchars($scheme['title']); ?></h2>
        <p><?php echo htmlspecialchars($scheme['description']); ?></p>
    </div>
    <?php endforeach; ?>
</div>
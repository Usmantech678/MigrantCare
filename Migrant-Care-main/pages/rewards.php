<?php
// /migrantcare/pages/rewards.php
requireLogin();
$worker = getWorker($conn, $_SESSION['user_id']);
?>
<div class="dashboard-container">
    <div class="header">
        <a href="index.php?page=worker_dashboard" class="link-button">&lt; <?php echo t('backToDashboard'); ?></a>
    </div>
    <h1 class="title"><?php echo t('rewardsTitle'); ?></h1>

    <div class="card">
        <h2 class="subtitle"><?php echo t('yourPoints'); ?></h2>
        <p style="font-size: 48px; text-align: center; font-weight: bold; color: var(--warning);">
            <?php echo htmlspecialchars($worker['points'] ?: 0); ?> ⭐
        </p>
    </div>

    <div class="card">
        <h2 class="subtitle"><?php echo t('howToEarn'); ?></h2>
        <ul class="rewards-list">
            <li><?php echo t('earnCheckup'); ?></li>
            <li><?php echo t('earnReferral'); ?></li>
        </ul>
        <p><?php echo t('redeemPoints'); ?></p>
        <button class="button"><?php echo t('referFriend'); ?></button>
    </div>
</div>
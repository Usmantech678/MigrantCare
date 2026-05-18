<?php
// /migrantcare/pages/role_selection.php
requireLogin();
?>
<div class="container bg-image-default">
    <h1 class="title"><?php echo t('selectRoleTitle'); ?></h1>
    <p class="subtitle"><?php echo t('roleSelectionSubtitle'); ?></p>
    <div class="role-container">
        <form action="index.php" method="POST">
            <input type="hidden" name="action" value="select_role">
            <input type="hidden" name="role" value="worker">
            <button type="submit" class="role-card">
                <span class="role-icon">👷</span>
                <h2 class="role-title"><?php echo t('workerRole'); ?></h2>
                <p class="role-description"><?php echo t('workerDescription'); ?></p>
            </button>
        </form>
        <form action="index.php" method="POST">
            <input type="hidden" name="action" value="select_role">
            <input type="hidden" name="role" value="doctor">
            <button type="submit" class="role-card">
                <span class="role-icon">🧑‍⚕️</span>
                <h2 class="role-title"><?php echo t('doctorRole'); ?></h2>
                <p class="role-description"><?php echo t('doctorDescription'); ?></p>
            </button>
        </form>
    </div>
</div>
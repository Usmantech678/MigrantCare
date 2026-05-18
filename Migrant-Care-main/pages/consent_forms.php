<?php
// /migrantcare/pages/consent_forms.php
requireLogin();
?>
<div class="dashboard-container">
    <div class="header">
        <a href="index.php?page=worker_dashboard" class="link-button">&lt; <?php echo t('backToDashboard'); ?></a>
    </div>
    <h1 class="title"><?php echo t('consentForms'); ?></h1>
    <div class="card">
        <h2 class="header-title">Pending Request</h2>
        <p><?php echo t('consentInfo'); ?></p>
        <form action="index.php" method="POST">
            <input type="hidden" name="action" value="give_consent">
            <button class="button" type="submit"><?php echo t('approveConsent'); ?></button>
        </form>
    </div>
</div>
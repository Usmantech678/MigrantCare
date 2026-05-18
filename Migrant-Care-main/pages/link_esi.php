<?php
// /migrantcare/pages/link_esi.php
requireLogin();
?>
<div class="container">
    <div class="auth-card">
        <h1 class="title"><?php echo t('linkESICard'); ?></h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <p class="error-text"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
        <?php endif; ?>
        
        <form action="index.php" method="POST" class="form">
            <input type="hidden" name="action" value="link_esi">
            <label class="label"><?php echo t('esiNumber'); ?></label>
            <input class="input" name="esiNumber" placeholder="Enter your ESI Number" required>
            <button class="button" type="submit"><?php echo t('linkCard'); ?></button>
        </form>
        <a href="index.php?page=worker_dashboard" class="link-button">Back</a>
    </div>
</div>
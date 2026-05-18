<?php
?>
<div class="auth-container bg-image-login">
    <div class="auth-card">
        <h1 class="title"><?php echo t('loginTitle'); ?></h1>
        <?php if (isset($_SESSION['error'])): ?>
            <p class="error-text"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
        <?php endif; ?>
        <form action="index.php" method="POST" class="form">
            <input type="hidden" name="action" value="login">
            <div class="input-group">
                <input class="input" type="tel" name="mobileNumber" placeholder="<?php echo t('mobilePlaceholder'); ?>" required>
            </div>
            <div class="input-group">
                <input class="input" type="password" name="pin" placeholder="<?php echo t('pinPlaceholder'); ?>" required>
            </div>
            <button class="button" type="submit"><?php echo t('loginButton'); ?></button>
        </form>
        <a href="index.php?page=register" class="link-button"><?php echo t('registerPrompt'); ?></a>
    </div>
</div>
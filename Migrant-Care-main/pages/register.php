<?php
// /migrantcare/pages/register.php
?>
<div class="auth-container bg-image-login">
    <div class="auth-card">
        <form action="index.php" method="POST" class="form" id="registerForm">
            <input type="hidden" name="action" value="register">

            <div class="form-step">
                <h1 class="title"><?php echo t('createAccountTitle'); ?></h1>
                <label class="label"><?php echo t('enterMobileLabel'); ?></label>
                <div class="input-group">
                    <input class="input" type="tel" name="mobile" placeholder="<?php echo t('mobilePlaceholder'); ?>" required>
                </div>
                <p class="info-text"><?php echo t('otpHint'); ?></p>
                <button type="button" class="button" data-next><?php echo t('sendOTPButton'); ?></button>
            </div>

            <div class="form-step" style="display: none;">
                <h1 class="title"><?php echo t('setPinTitle'); ?></h1>
                <label class="label"><?php echo t('createPinLabel'); ?></label>
                <div class="input-group">
                     <input class="input" type="password" name="pin" placeholder="<?php echo t('pinPlaceholder'); ?>" required>
                </div>
                <label class="label"><?php echo t('confirmPinLabel'); ?></label>
                 <div class="input-group">
                     <input class="input" type="password" name="confirm_pin" placeholder="<?php echo t('pinPlaceholder'); ?>" required>
                </div>
                <button type="submit" class="button"><?php echo t('createAccountButton'); ?></button>
            </div>
        </form>
        <a href="index.php?page=login" class="link-button"><?php echo t('alreadyHaveAccount'); ?></a>
    </div>
</div>
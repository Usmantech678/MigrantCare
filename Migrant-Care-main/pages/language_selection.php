<?php
// /migrantcare/pages/language_selection.php
?>
<div class="auth-container bg-image-login">
    <div class="auth-card">
        <h1 class="title"><?php echo t('selectLanguage'); ?></h1>
        <form action="index.php" method="POST" class="form">
            <input type="hidden" name="action" value="set_language">
            <button class="language-button" type="submit" name="language" value="en"><?php echo t('language'); ?></button>
            <button class="language-button" type="submit" name="language" value="ta">தமிழ் (Tamil)</button>
        </form>
    </div>
</div>
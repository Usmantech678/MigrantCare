<?php
// /migrantcare/pages/shared/footer.php
$show_footer_switcher = ($_GET['page'] ?? '') !== 'language_selection';
?>
    
    <?php if ($show_footer_switcher): ?>
    <footer class="site-footer">
        <form action="index.php" method="POST" class="language-switcher-form">
            <input type="hidden" name="action" value="set_language">
            <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
            <label for="language_select">🌐 <?php echo t('changeLanguage'); ?>:</label>
            <select name="language" id="language_select" class="input" onchange="this.form.submit()">
                <?php 
                global $translations;
                $current_lang = $_SESSION['language'] ?? 'en';
                foreach ($translations as $lang_code => $lang_data): 
                ?>
                    <option value="<?php echo $lang_code; ?>" <?php echo ($current_lang === $lang_code) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($lang_data['language']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </footer>
    <?php endif; ?>

    <script src="assets/script.js"></script>
</body>
</html>
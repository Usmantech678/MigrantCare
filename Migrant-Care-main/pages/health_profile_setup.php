<?php
// /migrantcare/pages/health_profile_setup.php
requireLogin();
?>
<div class="container">
    <div class="auth-card" style="max-width: 600px;">
        <h1 class="title"><?php echo t('healthProfileSetupTitle'); ?></h1>
        <p class="subtitle"><?php echo t('healthProfileSetupSubtitle'); ?></p>
        <form action="index.php" method="POST" class="form">
            <input type="hidden" name="action" value="health_profile_setup">
            <div class="profile-grid">
                <div>
                    <label class="label"><?php echo t('heightLabel'); ?></label>
                    <input class="input" type="number" name="height" placeholder="e.g., 170">
                </div>
                <div>
                    <label class="label"><?php echo t('weightLabel'); ?></label>
                    <input class="input" type="number" name="weight" placeholder="e.g., 65">
                </div>
            </div>
            <label class="label"><?php echo t('bloodGroupLabel'); ?></label>
            <select name="bloodGroup" class="input">
                <option value="">Select...</option>
                <option>A+</option><option>A-</option><option>B+</option><option>B-</option>
                <option>AB+</option><option>AB-</option><option>O+</option><option>O-</option>
            </select>
            <label class="label"><?php echo t('allergiesLabel'); ?></label>
            <input class="input" name="allergies" placeholder="e.g., Peanuts, Dust">
            <label class="label"><?php echo t('smokingLabel'); ?></label>
            <select name="smoking" class="input">
                <option value=""><?php echo t('preferNotToSayOption'); ?></option>
                <option value="Yes"><?php echo t('yesOption'); ?></option>
                <option value="No"><?php echo t('noOption'); ?></option>
            </select>
            <label class="label"><?php echo t('chronicDiseasesLabel'); ?></label>
            <textarea class="textarea" name="chronicDiseases" placeholder="e.g., Diabetes, Asthma"></textarea>
            <button type="submit" class="button"><?php echo t('saveProfileButton'); ?></button>
            <a href="index.php?page=worker_dashboard" class="link-button"><?php echo t('skipButton'); ?></a>
        </form>
    </div>
</div>
<?php
// /migrantcare/pages/worker_setup.php
requireLogin();
if ($_SESSION['role'] !== 'worker') { header('Location: index.php'); exit(); }
?>

<div class="form-container">
    <div class="card">
        <h1 class="title"><?php echo t('workerSetupTitle'); ?></h1>
        <p class="subtitle"><?php echo t('workerSetupSubtitle'); ?></p>

        <form action="index.php" method="POST">
            <input type="hidden" name="action" value="worker_setup">

            <div class="input-group">
                <label for="fullName" class="label"><?php echo t('fullName'); ?></label>
                <input type="text" id="fullName" name="fullName" class="input" placeholder="<?php echo t('enterFullName'); ?>" required>
            </div>

            <div class="input-group">
                <label for="age" class="label"><?php echo t('age'); ?></label>
                <input type="number" id="age" name="age" class="input" placeholder="<?php echo t('enterAge'); ?>" required>
            </div>

            <div class="input-group">
                <label for="homeState" class="label"><?php echo t('homeState'); ?></label>
                <input type="text" id="homeState" name="homeState" class="input" placeholder="<?php echo t('enterHomeState'); ?>" required>
            </div>

            <div class="input-group">
                <label for="phoneNumber" class="label"><?php echo t('phoneNumber'); ?></label>
                <input type="tel" id="phoneNumber" name="phoneNumber" class="input" placeholder="<?php echo t('enterPhoneNumber'); ?>" required>
            </div>

            <div class="input-group">
                <label for="emergencyContact" class="label"><?php echo t('emergencyContact'); ?></label>
                <input type="tel" id="emergencyContact" name="emergencyContact" class="input" placeholder="<?php echo t('enterEmergencyContact'); ?>" required>
            </div>

            <div class="input-group">
                <label for="currentLocation" class="label"><?php echo t('currentLocation'); ?></label>
                <input type="text" id="currentLocation" name="currentLocation" class="input" placeholder="<?php echo t('enterCurrentLocation'); ?>" required>
            </div>
            
            <div class="input-group">
                <label for="pincode" class="label"><?php echo t('pincode'); ?></label>
                <input type="text" id="pincode" name="pincode" class="input" placeholder="<?php echo t('enterPincode'); ?>" required pattern="\d{6}" title="Pincode must be 6 digits">
            </div>
            <button type="submit" class="button-primary w-full"><?php echo t('saveAndContinue'); ?></button>
        </form>
    </div>
</div>
<?php
// /migrantcare/pages/doctor_setup.php
requireLogin();
?>
<div class="container">
    <div class="auth-card">
        <h1 class="title">Doctor Profile Setup</h1>
        <form action="index.php" method="POST" class="form">
            <input type="hidden" name="action" value="doctor_setup">
            
            <label class="label">Full Name</label>
            <input class="input" name="fullName" placeholder="e.g., Dr. Priya Sharma" required>

            <label class="label">Medical License ID</label>
            <input class="input" name="licenseId" required>

            <label class="label">Hospital/Clinic Name</label>
            <input class="input" name="hospital" required>

            <button type="submit" class="button">Complete Profile</button>
        </form>
    </div>
</div>
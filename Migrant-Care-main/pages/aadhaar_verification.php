<?php
// /migrantcare/pages/aadhaar_verification.php
requireLogin();
?>
<div class="container">
    <div class="auth-card">
        <h1 class="title">Aadhaar Verification</h1>
        <p class="subtitle">This is a simulation. Enter any details to proceed.</p>
        <form action="index.php" method="POST" class="form">
            <input type="hidden" name="action" value="verify_kyc">
            <label class="label">Aadhaar Number</label>
            <input class="input" placeholder="Enter 12-digit number">
            <label class="label">OTP</label>
            <input class="input" placeholder="Enter 6-digit OTP (e.g., 123456)">
            <button type="submit" class="button">Verify & Continue</button>
        </form>
    </div>
</div>
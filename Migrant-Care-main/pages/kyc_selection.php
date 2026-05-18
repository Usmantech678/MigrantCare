<?php
// /migrantcare/pages/kyc_selection.php
requireLogin();
?>
<div class="container">
    <div class="auth-card">
        <h1 class="title">Identity Verification</h1>
        <p class="subtitle">Please verify your identity to generate your secure QR code.</p>
        <a href="index.php?page=aadhaar_verification" class="button">Verify with Aadhaar</a>
        <a href="index.php?page=other_id_verification" class="button" style="background-color: var(--text-light);">Verify with Other ID</a>
    </div>
</div>
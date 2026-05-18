<?php
// /migrantcare/pages/other_id_verification.php
requireLogin();
?>
<div class="container">
    <div class="auth-card">
        <h1 class="title">Other ID Verification</h1>
        <p class="subtitle">This is a simulation. Enter any details to proceed.</p>
        <form action="index.php" method="POST" class="form">
            <input type="hidden" name="action" value="verify_kyc">
            <label class="label">Select ID Type</label>
            <select class="input">
                <option>Voter ID</option>
                <option>Driving License</option>
            </select>
            <label class="label">ID Number</label>
            <input class="input" placeholder="Enter ID number">
            <button type="submit" class="button">Verify & Continue</button>
        </form>
    </div>
</div>
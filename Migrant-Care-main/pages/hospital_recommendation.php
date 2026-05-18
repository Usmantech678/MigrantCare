<?php
// /migrantcare/pages/hospital_recommendation.php
requireLogin();

// Mock data, as it's not in the DB schema
$hospitals = [
    ['id' => 1, 'name' => 'Manipal Hospital, Salem', 'distance' => '3.5 km', 'specialty' => 'Multi-Specialty, Emergency'],
    ['id' => 2, 'name' => 'Shanmuga Hospital, Salem', 'distance' => '6.1 km', 'specialty' => 'Cardiac, Orthopedics'],
    ['id' => 3, 'name' => 'Aravind Eye Hospital, Salem', 'distance' => '4.8 km', 'specialty' => 'Ophthalmology'],
];
?>
<div class="dashboard-container">
    <div class="header">
        <a href="index.php?page=worker_dashboard" class="link-button">&lt; <?php echo t('backToDashboard'); ?></a>
    </div>
    <h1 class="title"><?php echo t('findHospital'); ?></h1>
    <div class="input-group">
        <input class="input" placeholder="Search by hospital name or specialty...">
    </div>

    <?php foreach($hospitals as $hospital): ?>
        <div class="card">
            <h2 class="header-title"><?php echo htmlspecialchars($hospital['name']); ?></h2>
            <p><span class="bold">Specialty:</span> <?php echo htmlspecialchars($hospital['specialty']); ?></p>
            <p style="color: var(--text-light);"><?php echo htmlspecialchars($hospital['distance']); ?></p>
        </div>
    <?php endforeach; ?>
</div>
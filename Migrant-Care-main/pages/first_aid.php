<?php
// /migrantcare/pages/first_aid.php
requireLogin();

$guides = [
    ['title' => 'Cuts and Scrapes', 'icon' => '🩹', 'steps' => ['Wash hands with soap', 'Apply pressure with a clean cloth', 'Clean wound with water', 'Apply antibiotic ointment', 'Cover with a bandage']],
    ['title' => 'Minor Burns', 'icon' => '🔥', 'steps' => ['Cool the burn with running water', 'Remove tight items gently', "Don't break blisters", 'Apply aloe vera lotion', 'Cover with sterile gauze']],
    ['title' => 'Sprains (e.g., Ankle)', 'icon' => '🤕', 'steps' => ['Rest the injured joint', 'Apply ice pack (wrapped)', 'Compress with elastic bandage', 'Elevate the limb']],
];
?>
<div class="dashboard-container">
    <div class="header">
        <a href="index.php?page=worker_dashboard" class="link-button">&lt; <?php echo t('backToDashboard'); ?></a>
    </div>
    <h1 class="title"><?php echo t('firstAidGuide'); ?></h1>

    <?php foreach($guides as $guide): ?>
        <div class="card">
            <h2 class="header-title"><span class="grid-icon"><?php echo $guide['icon']; ?></span> <?php echo $guide['title']; ?></h2>
            <ol style="padding-left: 20px;">
                <?php foreach($guide['steps'] as $step): ?>
                    <li><?php echo $step; ?></li>
                <?php endforeach; ?>
            </ol>
        </div>
    <?php endforeach; ?>
</div>
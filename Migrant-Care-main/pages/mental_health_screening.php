<?php
// /migrantcare/pages/mental_health_screening.php
requireLogin();

$questions = [
    'Over the last 2 weeks, how often have you been bothered by having little interest or pleasure in doing things?',
    'Over the last 2 weeks, how often have you been bothered by feeling down, depressed, or hopeless?',
    'Over the last 2 weeks, how often have you been bothered by trouble falling or staying asleep, or sleeping too much?',
];
$options = ['Not at all', 'Several days', 'More than half the days', 'Nearly every day'];
?>
<div class="dashboard-container">
     <div class="header">
        <a href="index.php?page=worker_dashboard" class="link-button">&lt; <?php echo t('backToDashboard'); ?></a>
    </div>
    <h1 class="title"><?php echo t('mentalHealthTitle'); ?></h1>
    <form class="card" action="index.php" method="POST">
        <input type="hidden" name="action" value="submit_mental_health">
        <p class="subtitle"><?php echo t('mentalHealthSubtitle'); ?></p>
        <?php foreach ($questions as $index => $q): ?>
            <div class="form-group" style="margin-bottom: 20px;">
                <label class="label"><?php echo htmlspecialchars($q); ?></label>
                <?php foreach ($options as $o_index => $option): ?>
                    <div class="radio-group">
                        <input type="radio" name="q<?php echo $index; ?>" value="<?php echo $o_index; ?>" id="q<?php echo $index; ?>o<?php echo $o_index; ?>" required>
                        <label for="q<?php echo $index; ?>o<?php echo $o_index; ?>"><?php echo htmlspecialchars($option); ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <button type="submit" class="button"><?php echo t('submitAnswers'); ?></button>
    </form>
</div>
## MigrantCare — Presentation Layer Documentation

This document extracts the presentation-layer artifacts (views, templates, CSS, JS, and small client-side behaviors) from the MigrantCare app. The snippets below are copy/paste-ready and include a short use-case for each.

Location: repository root (useful for README cross-reference).

---

### File inventory (presentation-focused)
- `index.php` — central router and view loader
- `pages/shared/header.php` — shared head / opening markup
- `pages/shared/footer.php` — shared footer + language switcher + script include
- `pages/` — view templates (login, register, dashboards, patient_record, public_health_dashboard, etc.)
- `assets/style.css` — main stylesheet
- `assets/script.js` — front-end JS (chat, template autofill, form steps)
- `ajax.php` — AJAX endpoint used by the AI chat
- `functions.php` — translation helper `t()` (UI strings), and AI helper functions used by the UI

---

## Snippets (copy/paste ready)

1) index.php — central router + include pattern

Use case: Shows how pages are chosen and how shared header/footer wrap views.

```php
// index.php (routing + view inclusion)
$page = $_GET['page'] ?? (isLoggedIn() ? ($_SESSION['role'] . '_dashboard') : 'language_selection');

include 'pages/shared/header.php';
if (file_exists("pages/{$page}.php")) {
    include "pages/{$page}.php";
} else {
    echo "<div class='container card'><h1 class='title'>Page Not Found</h1></div>";
}
include 'pages/shared/footer.php';
```

2) Shared header (`pages/shared/header.php`)

Use case: Page head and global CSS link that every view inherits.

```php
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language'] ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MigrantCare</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
```

3) Shared footer (`pages/shared/footer.php`) — language switcher + script include

Use case: Footer area that provides the site language switcher and loads the main script.

```php
<footer class="site-footer">
  <form action="index.php" method="POST" class="language-switcher-form">
    <input type="hidden" name="action" value="set_language">
    <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
    <label>🌐 Change language:</label>
    <select name="language" onchange="this.form.submit()">
      <?php foreach ($translations as $code => $props): ?>
        <option value="<?php echo $code; ?>" <?php echo ($_SESSION['language'] ?? 'en') === $code ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($props['language']); ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>
</footer>

<script src="assets/script.js"></script>
</body>
</html>
```

4) Translation helper (`functions.php`) — t() function

Use case: Centralized UI strings; used in templates as `echo t('key')`.

```php
// functions.php (translations excerpt)
$translations = [
  'en' => ['language' => 'English', 'loginTitle' => 'MigrantCare Login', /* ... */],
  'ta' => ['language' => 'தமிழ்', /* ... */],
  // other languages...
];

function t($key) {
    global $translations;
    $lang = $_SESSION['language'] ?? 'en';
    return $translations[$lang][$key] ?? ($translations['en'][$key] ?? $key);
}
```

5) AJAX handler for AI chat (`ajax.php`)

Use case: Endpoint the chat UI calls; returns JSON `{ reply }`.

```php
// ajax.php?action=ai_chat
require_once 'db.php';
require_once 'functions.php';
requireLogin();

$input = json_decode(file_get_contents('php://input'), true);
$message = $input['message'] ?? '';

if (empty($message)) {
    $reply = "I'm sorry, I didn't receive a message.";
} else {
    // getAIChatResponse is implemented in functions.php (server-side AI)
    $reply = getAIChatResponse($message);
}

header('Content-Type: application/json');
echo json_encode(['reply' => $reply]);
exit();
```

6) Client fetch to AJAX endpoint (`assets/script.js`) — chat exchange

Use case: Send user message to server and display AI reply.

```javascript
// assets/script.js (chat request)
async function getAIResponse(prompt) {
  showTypingIndicator(); // UI helper
  try {
    const response = await fetch('ajax.php?action=ai_chat', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message: prompt }),
    });
    const data = await response.json();
    removeTypingIndicator();
    if (data.reply) addMessage('ai', data.reply);
    else addMessage('ai', "Sorry, no reply.");
  } catch (err) {
    removeTypingIndicator();
    addMessage('ai', "Connection error. Please try again.");
  }
}
```

7) AI Symptom Checker page (`pages/ai_symptom_checker.php`) — core markup

Use case: Chat UI structure (messages container + form) — minimal skeleton.

```html
<!-- pages/ai_symptom_checker.php (simplified) -->
<div class="chat-container">
  <header>
    <a href="index.php?page=worker_dashboard">&larr; Back</a>
    <h1><?php echo t('symptomChecker'); ?></h1>
  </header>

  <main id="chat-messages" class="chat-messages"></main>

  <footer>
    <form id="chat-form" class="chat-input-container">
      <input type="text" id="user-input" placeholder="<?php echo t('typeSymptomsHere'); ?>">
      <button type="submit">Send</button>
    </form>
  </footer>
</div>
```

8) Clinical templates injection (`pages/patient_record.php`)

Use case: Server-defined templates injected to JS to auto-fill clinical form fields.

```php
<?php
$disease_templates = [
  'Dengue Fever' => [
    'symptoms' => 'Fever, Headache, Body Ache, Rash',
    'diagnosis' => 'Suspected Dengue Fever',
    'prescription' => 'Paracetamol 500mg. Avoid NSAIDs.',
    'notes' => 'Advise platelet monitoring and fluids.'
  ],
  /* ... */
];
?>
<script>
  const diseaseTemplates = <?php echo json_encode($disease_templates); ?>;
</script>

<!-- form elements -->
<select id="templateSelector" class="input">
  <option value="">Select a condition...</option>
  <?php foreach (array_keys($disease_templates) as $name): ?>
    <option value="<?php echo htmlspecialchars($name); ?>"><?php echo htmlspecialchars($name); ?></option>
  <?php endforeach; ?>
</select>

<textarea id="symptomsInput" name="symptoms"></textarea>
<input id="diagnosisInput" name="diagnosis">
<textarea id="prescriptionInput" name="prescription"></textarea>
<textarea id="notesInput" name="notes"></textarea>
```

9) Template auto-fill JS (`assets/script.js`) — listener to apply template values

Use case: Populate the form fields after user selects a clinical template.

```javascript
// assets/script.js (template auto-fill)
const templateSelector = document.getElementById('templateSelector');
if (templateSelector) {
  const symptomsInput = document.getElementById('symptomsInput');
  const diagnosisInput = document.getElementById('diagnosisInput');
  const prescriptionInput = document.getElementById('prescriptionInput');
  const notesInput = document.getElementById('notesInput');

  templateSelector.addEventListener('change', function() {
    const template = diseaseTemplates[this.value];
    if (template) {
      symptomsInput.value = template.symptoms;
      diagnosisInput.value = template.diagnosis;
      prescriptionInput.value = template.prescription;
      notesInput.value = template.notes;
    }
  });
}
```

10) Lab report upload form (`pages/patient_record.php`)

Use case: File upload UI (multipart) handled by `index.php` action `add_lab_report`.

```html
<form action="index.php" method="POST" enctype="multipart/form-data" class="form">
  <input type="hidden" name="action" value="add_lab_report">
  <input type="hidden" name="workerId" value="<?php echo $patient['id']; ?>">

  <label>Report Name</label>
  <input name="reportName" class="input" required>

  <label>Date of Test</label>
  <input type="date" name="testDate" class="input" required>

  <label>Upload File (PDF, JPG, PNG)</label>
  <input type="file" name="reportFile" class="input" required>

  <button type="submit" class="button">Upload Report</button>
</form>
```

11) Public health dashboard — map & chart init (`pages/public_health_dashboard.php`)

Use case: Server provides JSON (hotspots and chart data) and front-end renders Leaflet map + Chart.js.

```html
<!-- server-side -->
<script>
  const hotspotsData = <?php echo json_encode($hotspots); ?>;
  const chartData = <?php echo $chart_data_json; ?>;
</script>

<!-- client-side init -->
<script>
  // Leaflet map
  const map = L.map('map').setView([20.5937, 78.9629], 5);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
  hotspotsData.forEach(h => L.marker([h.lat, h.lon]).addTo(map).bindPopup(`<b>${h.areaName}</b><p>${h.disease} — ${h.cases} cases</p>`));

  // Chart.js trends
  const ctx = document.getElementById('trendChart').getContext('2d');
  new Chart(ctx, { type: 'line', data: chartData, options: { responsive: true } });
</script>
```

12) CSS: basic card/button (`assets/style.css`)

Use case: Core visual building blocks reused across pages.

```css
.card {
  background-color: #fff;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 8px 25px rgba(0,0,0,0.07);
  border: 1px solid #e5e7eb;
}
.button {
  background-image: linear-gradient(145deg, #007bff, #0056b3);
  color: #fff;
  border-radius: 8px;
  padding: 12px 18px;
  border: none;
  cursor: pointer;
}
```

---

If you want this file organized differently (per-page sections, smaller snippets, or extra notes about where server handlers live), tell me which pages to expand and I will update `PRESENTATION_LAYER.md` accordingly.

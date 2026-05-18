<?php
// /migrantcare/pages/patient_record.php
requireLogin();
if ($_SESSION['role'] !== 'doctor') { header('Location: index.php'); exit(); }
$worker_id = (int)($_GET['id'] ?? 0);
if (!$worker_id) { die("Invalid patient ID."); }

// Fetch patient data
$stmt = $conn->prepare("SELECT * FROM workers WHERE id = ?");
$stmt->bind_param("i", $worker_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
if (!$patient) { die("Patient not found."); }

// Fetch related data
$stmt_records = $conn->prepare("SELECT * FROM healthRecords WHERE workerId = ? ORDER BY recordDate DESC");
$stmt_records->bind_param("i", $worker_id);
$stmt_records->execute();
$records = $stmt_records->get_result();
$stmt_referrals = $conn->prepare("SELECT * FROM referrals WHERE workerId = ? ORDER BY referralDate DESC");
$stmt_referrals->bind_param("i", $worker_id);
$stmt_referrals->execute();
$referrals = $stmt_referrals->get_result();
$stmt_lab_reports = $conn->prepare("SELECT * FROM labReports WHERE workerId = ? ORDER BY testDate DESC");
$stmt_lab_reports->bind_param("i", $worker_id);
$stmt_lab_reports->execute();
$lab_reports = $stmt_lab_reports->get_result();

// Clinical Templates
$disease_templates = [
    'Dengue Fever' => ['symptoms' => 'Fever, Headache, Body Ache, Rash', 'diagnosis' => 'Suspected Dengue Fever', 'prescription' => 'Paracetamol 500mg. AVOID NSAIDs like Ibuprofen.', 'notes' => 'Advise platelet count monitoring, rest, and plenty of fluids (ORS).'],
    'Common Cold' => ['symptoms' => 'Cough, Sore Throat, Runny Nose', 'diagnosis' => 'Common Cold / Upper Respiratory Infection', 'prescription' => 'Symptomatic relief. Antihistamines if needed. Warm saline gargles.', 'notes' => 'Advise rest and hydration. No antibiotics needed.'],
    'Viral Fever' => ['symptoms' => 'Fever, Body Ache, Chills', 'diagnosis' => 'Viral Fever', 'prescription' => 'Paracetamol 500mg for fever.', 'notes' => 'Advise rest and hydration. Monitor temperature.'],
    'Typhoid' => ['symptoms' => 'High Fever, Stomach Pain, Headache, Weakness', 'diagnosis' => 'Typhoid Fever (Enteric Fever)', 'prescription' => 'Prescribe appropriate antibiotics (e.g., Azithromycin or Ciprofloxacin).', 'notes' => 'Advise patient to complete the full course of antibiotics. Emphasize food and water hygiene.']
];
?>
<div class="dashboard-container">
    <div class="header">
        <a href="index.php?page=doctor_dashboard" class="link-button">&lt; <?php echo t('backToDashboard'); ?></a>
    </div>
    <h1 class="title">Patient Record: <?php echo htmlspecialchars($patient['fullName']); ?></h1>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-text" style="background-color: #f8d7da; padding: 15px; border-radius: 8px;"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="card patient-info-card">
        <div class="patient-info-grid">
            <div><span class="bold">Age:</span> <?php echo htmlspecialchars($patient['age']); ?></div>
            <div><span class="bold">Home State:</span> <?php echo htmlspecialchars($patient['homeState']); ?></div>
            <div><span class="bold">Blood Group:</span> <?php echo htmlspecialchars($patient['bloodGroup'] ?: 'N/A'); ?></div>
            <div>
                <span class="bold"><?php echo t('esiStatus'); ?>:</span>
                <?php if (!empty($patient['esiNumber'])): ?>
                    <span class="tag-success"><?php echo t('esiLinkedTag'); ?></span>
                <?php else: ?>
                    <span class="tag-danger"><?php echo t('esiNotLinkedTag'); ?></span>
                <?php endif; ?>
            </div>
            <div style="grid-column: 1 / -1;"><span class="bold">Chronic Diseases:</span> <?php echo htmlspecialchars($patient['chronicDiseases'] ?: 'None'); ?></div>
        </div>
    </div>
    
    <button class="button" onclick="document.getElementById('referralModal').style.display='flex'" style="width: auto; padding: 0 25px; margin-bottom: 20px;"><?php echo t('referPatient'); ?></button>
    
    <div class="card">
        <h3 class="subtitle">Add New Consultation Record</h3>
        <div class="form-group">
            <label class="label">Apply Clinical Template</label>
            <select id="templateSelector" class="input">
                <option value="">Select a common condition...</option>
                <?php foreach (array_keys($disease_templates) as $template_name): ?>
                    <option value="<?php echo htmlspecialchars($template_name); ?>"><?php echo htmlspecialchars($template_name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <form action="index.php" method="POST" class="form">
            <input type="hidden" name="action" value="add_record">
            <input type="hidden" name="workerId" value="<?php echo $patient['id']; ?>">
            <label class="label">Symptoms</label>
            <textarea id="symptomsInput" name="symptoms" class="textarea" placeholder="e.g., Fever, Headache" required></textarea>
            <label class="label">Diagnosis</label>
            <input id="diagnosisInput" name="diagnosis" class="input" placeholder="e.g., Viral Fever" required>
            <label class="label">Prescription</label>
            <textarea id="prescriptionInput" name="prescription" class="textarea" placeholder="e.g., Paracetamol 500mg"></textarea>
            <label class="label">Doctor's Notes</label>
            <textarea id="notesInput" name="notes" class="textarea" placeholder="e.g., Advised rest"></textarea>
            <label class="label">Follow-up Date</label>
            <input type="date" name="followUpDate" class="input">
            <button type="submit" class="button">Save New Record</button>
        </form>
    </div>

    <div class="card">
        <h3 class="subtitle">Lab Reports</h3>
        <?php if ($lab_reports->num_rows > 0): ?>
            <?php while($report = $lab_reports->fetch_assoc()): ?>
                <div class="record-item">
                    <p class="record-date"><?php echo t('testDate'); ?> <?php echo htmlspecialchars($report['testDate']); ?></p>
                    <p><span class="bold"><?php echo htmlspecialchars($report['reportName']); ?></span> (<?php echo htmlspecialchars($report['labName']); ?>)</p>
                    <p><span class="bold"><?php echo t('doctorsNotes'); ?></span> <?php echo htmlspecialchars($report['doctorNotes'] ?: 'N/A'); ?></p>
                    <a href="<?php echo htmlspecialchars($report['fileURL']); ?>" class="link-button" target="_blank" rel="noopener noreferrer" style="margin-top: 5px; padding-left: 0;"><?php echo t('viewReport'); ?></a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No lab reports found for this patient.</p>
        <?php endif; ?>
        <hr style="margin: 30px 0;">
        <h4 class="subtitle" style="text-align: left;">Upload New Lab Report</h4>
        <form action="index.php" method="POST" class="form" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_lab_report">
            <input type="hidden" name="workerId" value="<?php echo $patient['id']; ?>">
            <label class="label">Report Name</label>
            <input name="reportName" class="input" placeholder="e.g., Complete Blood Count" required>
            <label class="label">Date of Test</label>
            <input type="date" name="testDate" class="input" required>
            <label class="label">Lab / Hospital Name</label>
            <input name="labName" class="input" placeholder="e.g., Manipal Hospital Lab" required>
            <label class="label">Doctor's Notes (Optional)</label>
            <textarea name="doctorNotes" class="textarea" placeholder="e.g., Platelet count is low"></textarea>
            <label class="label">Upload File (PDF, JPG, PNG)</label>
            <input type="file" name="reportFile" class="input" style="padding: 10px;" required>
            <button type="submit" class="button">Upload Report</button>
        </form>
    </div>

    <div class="card">
        <h3 class="subtitle">Health History</h3>
        <?php if ($records->num_rows > 0): ?>
            <?php while($record = $records->fetch_assoc()): ?>
                <div class="record-item">
                    <p class="record-date"><?php echo htmlspecialchars($record['recordDate']); ?> - <span class="bold"><?php echo htmlspecialchars($record['diagnosis']); ?></span></p>
                    <p><span class="bold">Symptoms:</span> <?php echo htmlspecialchars($record['symptoms']); ?></p>
                    <a href="index.php?page=edit_record&id=<?php echo $record['id']; ?>" class="link-button" style="margin-top: 5px; padding-left: 0;">Edit Record</a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No health records found.</p>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <h3 class="subtitle">Referrals</h3>
        <?php if ($referrals->num_rows > 0): ?>
            <?php while($ref = $referrals->fetch_assoc()): ?>
                <div class="record-item">
                    <p class="record-date"><?php echo htmlspecialchars($ref['referralDate']); ?></p>
                    <p><span class="bold">Referred to:</span> <?php echo htmlspecialchars($ref['specialist']); ?></p>
                    <p><span class="bold">Notes:</span> <?php echo htmlspecialchars($ref['notes']); ?></p>
                    <p><span class="bold">Referring Doctor:</span> <?php echo htmlspecialchars($ref['referringDoctor']); ?></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No referrals found.</p>
        <?php endif; ?>
    </div>
</div>

<div class="modal-backdrop" id="referralModal">
    <div class="modal-content">
        <h2 class="title"><?php echo t('referralTitle'); ?></h2>
        <form action="index.php" method="POST" class="form">
            <input type="hidden" name="action" value="refer_patient">
            <input type="hidden" name="workerId" value="<?php echo $patient['id']; ?>">
            <label class="label"><?php echo t('selectSpecialist'); ?></label>
            <select name="specialist" class="input" required>
                <option value="">Select...</option>
                <option>Cardiologist</option>
                <option>Dermatologist</option>
                <option>Orthopedic</option>
                <option>General Physician</option>
            </select>
            <label class="label"><?php echo t('referralNotes'); ?></label>
            <textarea name="notes" class="textarea" required></textarea>
            <div style="display: flex; gap: 10px; width: 100%;">
                <button type="button" class="button" style="background: var(--text-light);" onclick="document.getElementById('referralModal').style.display='none'">Cancel</button>
                <button type="submit" class="button"><?php echo t('submitReferral'); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
    const diseaseTemplates = <?php echo json_encode($disease_templates); ?>;
</script>
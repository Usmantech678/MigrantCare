<?php
// /migrantcare/pages/edit_record.php
requireLogin();
if ($_SESSION['role'] !== 'doctor') { header('Location: index.php'); exit(); }
$record_id = (int)($_GET['id'] ?? 0);
if (!$record_id) { die("Invalid record ID."); }

// Fetch the specific health record to edit
$stmt = $conn->prepare("SELECT * FROM healthRecords WHERE id = ?");
$stmt->bind_param("i", $record_id);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();
if (!$record) { die("Record not found."); }
?>
<div class="dashboard-container">
    <a href="index.php?page=patient_record&id=<?php echo $record['workerId']; ?>" class="link-button">&lt; Back to Patient Record</a>
    <h1 class="title">Edit Health Record</h1>

    <div class="card">
        <form action="index.php" method="POST" class="form">
            <input type="hidden" name="action" value="update_record">
            <input type="hidden" name="recordId" value="<?php echo $record['id']; ?>">
            <input type="hidden" name="workerId" value="<?php echo $record['workerId']; ?>">

            <label class="label">Symptoms</label>
            <textarea name="symptoms" class="textarea" required><?php echo htmlspecialchars($record['symptoms']); ?></textarea>
            
            <label class="label">Diagnosis</label>
            <input name="diagnosis" class="input" value="<?php echo htmlspecialchars($record['diagnosis']); ?>" required>

            <label class="label">Prescription</label>
            <textarea name="prescription" class="textarea"><?php echo htmlspecialchars($record['prescription']); ?></textarea>

            <label class="label">Doctor's Notes</label>
            <textarea name="notes" class="textarea"><?php echo htmlspecialchars($record['notes']); ?></textarea>

            <label class="label">Follow-up Date</label>
            <input type="date" name="followUpDate" class="input" value="<?php echo htmlspecialchars($record['followUpDate']); ?>">

            <button type="submit" class="button">Update Record</button>
        </form>
    </div>
</div>
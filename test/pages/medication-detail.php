<?php
// Start session
session_start();

// Include database configuration
include '../db_config.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'patient') {
  // Redirect to login page
  header("Location: ../login.php");
  exit();
}

// Get patient ID from session
$patient_id = $_SESSION['linked_id'];

// Check if medication ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
  header("Location: medications.php");
  exit();
}

$medication_id = intval($_GET['id']);

// Query to get medication details
$query = "SELECT m.MedicationID, m.MedicationName, m.Dosage, m.StartDate, m.EndDate, m.Instructions,
          CONCAT(d.FirstName, ' ', d.LastName) AS DoctorName,
          CASE 
              WHEN CURDATE() < m.StartDate THEN 'Not Started'
              WHEN CURDATE() > m.EndDate THEN 'Completed'
              ELSE 'Active'
          END AS Status
          FROM MEDICATION m
          JOIN DOCTOR d ON m.PrescribedByID = d.DoctorID
          WHERE m.MedicationID = ? AND m.PatientID = ?";

// Prepare and execute the statement
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $medication_id, $patient_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if medication exists and belongs to the patient
if ($result->num_rows === 0) {
  header("Location: medications.php");
  exit();
}

$medication = $result->fetch_assoc();

// Query to find related medical records
$record_query = "SELECT r.RecordID 
                FROM MEDICAL_RECORD r 
                WHERE r.PatientID = ? 
                AND EXISTS (
                  SELECT 1 FROM MEDICATION m 
                  WHERE m.MedicationID = ? 
                  AND m.PatientID = r.PatientID
                )
                ORDER BY r.RecordID DESC 
                LIMIT 1";

$record_stmt = $conn->prepare($record_query);
$record_stmt->bind_param("ii", $patient_id, $medication_id);
$record_stmt->execute();
$record_result = $record_stmt->get_result();
$related_record_id = ($record_result->num_rows > 0) ? $record_result->fetch_assoc()['RecordID'] : null;
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Medication Detail - Patient Portal</title>
  <link rel="stylesheet" href="../css/main.css">
  <script src="../js/main.js" defer></script>
</head>

<body>
  <header class="page-header">
    <div class="logo">
      <img src="../assets/icons/healthcare.png" alt="Hospital Logo" class="logo-image">
      <span class="logo-text">Hospital's Name</span>
    </div>
  </header>
  <div class="page-header">Medications(View detail) - <?php echo htmlspecialchars($_SESSION['username']); ?></div>
  <div class="container">
    <!-- Sidebar Navigation -->
    <div class="sidebar">
      <ul class="sidebar-menu">
        <li><a href="appointment.php">Appointment</a></li>
        <li><a href="records.php">Records</a></li>
        <li class="active"><a href="medications.php">Medications</a></li>
        <li><a href="feedback.php">Feedback</a></li>
        <li><a href="profile.php">Profile</a></li>
        <li><a href="settings.php">Setting</a></li>
        <li><a href="../logout.php">Logout</a></li>
      </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
      <div class="header">
        <h1>Prescription detail</h1>
        <div class="header-actions">
          <a href="medications.php" class="button secondary">Back to Medications</a>
        </div>
      </div>

      <!-- Medication Details -->
      <div class="detail-container">
        <div class="detail-section">
          <div class="detail-label">Prescription Name</div>
          <div class="detail-value"><?php echo htmlspecialchars($medication['MedicationName']); ?></div>
        </div>

        <div class="detail-section">
          <div class="detail-label">Prescription Date</div>
          <div class="detail-value">
            From: <?php echo date('M d, Y', strtotime($medication['StartDate'])); ?>
            To: <?php echo date('M d, Y', strtotime($medication['EndDate'])); ?>
          </div>
        </div>

        <div class="detail-section">
          <div class="detail-label">Dosage</div>
          <div class="detail-value"><?php echo htmlspecialchars($medication['Dosage']); ?></div>
        </div>

        <div class="detail-section">
          <div class="detail-label">Status</div>
          <div class="detail-value">
            <span class="status-badge status-<?php echo strtolower(str_replace(' ', '', $medication['Status'])); ?>">
              <?php echo $medication['Status']; ?>
            </span>
          </div>
        </div>

        <div class="detail-section">
          <div class="detail-label">Prescribed By</div>
          <div class="detail-value"><?php echo htmlspecialchars($medication['DoctorName']); ?></div>
        </div>

        <div class="detail-section">
          <div class="detail-label">Instructions</div>
          <div class="detail-value"><?php echo nl2br(htmlspecialchars($medication['Instructions'])); ?></div>
        </div>

        <?php if ($related_record_id): ?>
          <div class="form-action">
            <a href="record-detail.php?id=<?php echo $related_record_id; ?>" class="button">View related record</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>

</html>

<?php
// Close the database connections
$stmt->close();
$record_stmt->close();
$conn->close();
?>
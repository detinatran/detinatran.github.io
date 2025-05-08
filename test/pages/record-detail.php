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

// Check if record ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
  header("Location: records.php");
  exit();
}

$record_id = intval($_GET['id']);

// Query to get medical record details
$query = "SELECT 
            a.AppointmentDate AS Date,
            d.DepartmentName AS Department,
            CONCAT(doct.FirstName, ' ', doct.LastName) AS Doctor,
            mr.Diagnosis,
            mr.Classification AS Category,
            al.Action
          FROM 
            APPOINTMENT a
          JOIN 
            DOCTOR doct ON a.DoctorID = doct.DoctorID
          JOIN 
            DEPARTMENT d ON doct.Department = d.DepartmentName
          JOIN 
            MEDICAL_RECORD mr ON a.PatientID = mr.PatientID
          JOIN 
            AUDIT_LOG al ON al.RecordID = mr.RecordID
          WHERE 
            a.PatientID = ? AND mr.RecordID = ?
          ORDER BY 
            a.AppointmentDate DESC
          LIMIT 1";

// Prepare and execute the statement
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $patient_id, $record_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if record exists and belongs to the patient
if ($result->num_rows === 0) {
  header("Location: records.php");
  exit();
}

$record = $result->fetch_assoc();

// Query to get any associated medications
$med_query = "SELECT m.MedicationID, m.MedicationName, m.Dosage, m.StartDate, m.EndDate
              FROM MEDICATION m 
              WHERE m.PatientID = ? 
              AND EXISTS (
                SELECT 1 FROM INTERACTION i 
                WHERE i.PatientID = m.PatientID 
                AND i.InteractionType = 'Medical Examination'
                AND DATE(i.InteractionDate) BETWEEN DATE_SUB(m.StartDate, INTERVAL 7 DAY) AND DATE_ADD(m.StartDate, INTERVAL 7 DAY)
              )
              ORDER BY m.StartDate DESC
              LIMIT 5";

$med_stmt = $conn->prepare($med_query);
$med_stmt->bind_param("i", $patient_id);
$med_stmt->execute();
$med_result = $med_stmt->get_result();

// Function to extract specific parts from the Description field
function extractDetail($fullDescription, $section)
{
  // Using regex to extract details between section headers
  $pattern = "/$section:(.*?)(?=\b(?:Symptoms|Test Results|Treatment Plan|Conclusion):|$)/s";
  if (preg_match($pattern, $fullDescription, $matches)) {
    return trim($matches[1]);
  }
  return "Not available";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Record Detail - Patient Portal</title>
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
  <div class="page-header">Records(View Detail) - <?php echo htmlspecialchars($_SESSION['username']); ?></div>
  <div class="container">
    <!-- Sidebar Navigation -->
    <div class="sidebar">
      <ul class="sidebar-menu">
        <li><a href="appointment.php">Appointment</a></li>
        <li class="active"><a href="records.php">Records</a></li>
        <li><a href="medications.php">Medications</a></li>
        <li><a href="feedback.php">Feedback</a></li>
        <li><a href="profile.php">Profile</a></li>
        <li><a href="settings.php">Setting</a></li>
        <li><a href="../logout.php">Logout</a></li>
      </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
      <div class="header">
        <h1>Medical Record Detail</h1>
        <div class="header-actions">
          <a href="records.php" class="button secondary">Back to Records</a>
        </div>
      </div>

      <!-- Record Details -->
      <div class="detail-container">
        <div class="detail-section">
          <div class="detail-label">Date:</div>
          <div class="detail-value">
            <?php echo isset($record['Date']) ? date('M d, Y', strtotime($record['Date'])) : 'Not available'; ?>
          </div>
        </div>

        <div class="detail-section">
          <div class="detail-label">Doctor Name:</div>
          <div class="detail-value">
            <?php echo isset($record['Doctor']) ? htmlspecialchars($record['Doctor']) : 'Not available'; ?>
          </div>
        </div>

        <div class="detail-section">
          <div class="detail-label">Department:</div>
          <div class="detail-value">
            <?php echo isset($record['Department']) ? htmlspecialchars($record['Department']) : 'Not available'; ?>
          </div>
        </div>

        <div class="detail-section">
          <div class="detail-label">Diagnosis:</div>
          <div class="detail-value">
            <?php echo htmlspecialchars($record['Diagnosis']); ?>
          </div>
        </div>

        <div class="detail-section">
          <div class="detail-label">Category:</div>
          <div class="detail-value">
            <?php echo htmlspecialchars($record['Category']); ?>
          </div>
        </div>

        <div class="detail-section">
          <div class="detail-label">Action:</div>
          <div class="detail-value">
            <?php echo htmlspecialchars($record['Action']); ?>
          </div>
        </div>

        <?php if ($med_result->num_rows > 0): ?>
          <div class="detail-section">
            <div class="detail-label">Related Medications</div>
            <div class="detail-value">
              <ul class="medications-list">
                <?php while ($medication = $med_result->fetch_assoc()): ?>
                  <li>
                    <a href="medication-detail.php?id=<?php echo $medication['MedicationID']; ?>">
                      <?php echo htmlspecialchars($medication['MedicationName']); ?>
                      (<?php echo htmlspecialchars($medication['Dosage']); ?>) -
                      Started: <?php echo date('M d, Y', strtotime($medication['StartDate'])); ?>
                    </a>
                  </li>
                <?php endwhile; ?>
              </ul>
            </div>
          </div>
        <?php else: ?>
          <div class="form-action">
            <a href="records.php" class="button">Back to Records</a>
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
$med_stmt->close();
$conn->close();
?>
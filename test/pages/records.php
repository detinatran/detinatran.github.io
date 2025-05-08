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

// Default sort and filter options
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';

// Build the query based on sort and filter options
$query = "SELECT mr.RecordID, mr.Diagnosis, 
          CONCAT(d.FirstName, ' ', d.LastName) AS DoctorName,
          d.Department, i.InteractionDate AS Date
          FROM MEDICAL_RECORD mr
          JOIN INTERACTION i ON mr.PatientID = i.PatientID
          JOIN DOCTOR d ON i.StaffType = 'doctor'AND d.DoctorID = i.StaffID
          WHERE mr.PatientID = ?";

// Add filter condition if date filter is applied
if (!empty($filter_date)) {
  $query .= " AND DATE(i.InteractionDate) = ?";
}

// Add sorting
switch ($sort_by) {
  case 'date_asc':
    $query .= " ORDER BY i.InteractionDate ASC";
    break;
  case 'date_desc':
  default:
    $query .= " ORDER BY i.InteractionDate DESC";
    break;
}

// Prepare and execute the statement
$stmt = $conn->prepare($query);

if (!empty($filter_date)) {
  $stmt->bind_param("is", $patient_id, $filter_date);
} else {
  $stmt->bind_param("i", $patient_id);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Medical Records - Patient Portal</title>
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
  <div class="page-header">Records - <?php echo htmlspecialchars($_SESSION['username']); ?></div>
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
        <h1>Medical Records</h1>
        <div class="header-actions">
          <form action="" method="GET" id="filter-form">
            <input type="date" name="filter_date" value="<?php echo $filter_date; ?>" onchange="document.getElementById('filter-form').submit();">
            <select name="sort" onchange="document.getElementById('filter-form').submit();">
              <option value="date_desc" <?php if ($sort_by == 'date_desc') echo 'selected'; ?>>Sort by Date ↓</option>
              <option value="date_asc" <?php if ($sort_by == 'date_asc') echo 'selected'; ?>>Sort by Date ↑</option>
            </select>
          </form>
        </div>
      </div>

      <!-- Records Table -->
      <table>
        <thead>
          <tr>
            <th>Diagnosis</th>
            <th>Doctor Name</th>
            <th>Department</th>
            <th>Date</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?php echo htmlspecialchars($row['Diagnosis']); ?></td>
                <td><?php echo htmlspecialchars($row['DoctorName']); ?></td>
                <td><?php echo htmlspecialchars($row['Department']); ?></td>
                <td><?php echo date('M d, Y', strtotime($row['Date'])); ?></td>
                <td>
                  <a href="record-detail.php?id=<?php echo $row['RecordID']; ?>" class="view-detail">View detail</a>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" style="text-align: center;">No records found</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>

</html>

<?php
// Close the database connection
$stmt->close();
$conn->close();
?>
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
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';

// Build the query based on sort and filter options
$query = "SELECT m.MedicationID, m.MedicationName, m.Dosage, m.StartDate, m.EndDate, m.Instructions,
          CONCAT(d.FirstName, ' ', d.LastName) AS DoctorName,
          CASE 
              WHEN CURDATE() < m.StartDate THEN 'Not Started'
              WHEN CURDATE() > m.EndDate THEN 'Completed'
              ELSE 'Active'
          END AS Status
          FROM MEDICATION m
          JOIN DOCTOR d ON m.PrescribedByID = d.DoctorID
          WHERE m.PatientID = ?";

// Add filter conditions
if (!empty($filter_date)) {
  $query .= " AND (DATE(m.StartDate) = ? OR DATE(m.EndDate) = ?)";
}

if (!empty($filter_status)) {
  if ($filter_status == 'active') {
    $query .= " AND CURDATE() BETWEEN m.StartDate AND m.EndDate";
  } elseif ($filter_status == 'completed') {
    $query .= " AND CURDATE() > m.EndDate";
  } elseif ($filter_status == 'notstarted') {
    $query .= " AND CURDATE() < m.StartDate";
  }
}

// Add sorting
switch ($sort_by) {
  case 'name_asc':
    $query .= " ORDER BY m.MedicationName ASC";
    break;
  case 'name_desc':
    $query .= " ORDER BY m.MedicationName DESC";
    break;
  case 'date_asc':
    $query .= " ORDER BY m.StartDate ASC";
    break;
  case 'date_desc':
    $query .= " ORDER BY m.StartDate DESC";
    break;
  case 'status':
    $query .= " ORDER BY Status";
    break;
  default:
    $query .= " ORDER BY m.StartDate DESC";
    break;
}

// Prepare and execute the statement
$stmt = $conn->prepare($query);

if (!empty($filter_date)) {
  $stmt->bind_param("iss", $patient_id, $filter_date, $filter_date);
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
  <title>Medications - Patient Portal</title>
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
  <div class="page-header">Medications - <?php echo htmlspecialchars($_SESSION['username']); ?></div>
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
        <h1>Medications</h1>
        <div class="header-actions">
          <form action="" method="GET" id="filter-form">
            <input type="date" name="filter_date" value="<?php echo $filter_date; ?>" onchange="document.getElementById('filter-form').submit();">
            <select name="filter_status" onchange="document.getElementById('filter-form').submit();">
              <option value="" <?php if ($filter_status == '') echo 'selected'; ?>>All Status</option>
              <option value="active" <?php if ($filter_status == 'active') echo 'selected'; ?>>Active</option>
              <option value="completed" <?php if ($filter_status == 'completed') echo 'selected'; ?>>Completed</option>
              <option value="notstarted" <?php if ($filter_status == 'notstarted') echo 'selected'; ?>>Not Started</option>
            </select>
            <select name="sort" onchange="document.getElementById('filter-form').submit();">
              <option value="date_desc" <?php if ($sort_by == 'date_desc') echo 'selected'; ?>>Sort by Date ↓</option>
              <option value="date_asc" <?php if ($sort_by == 'date_asc') echo 'selected'; ?>>Sort by Date ↑</option>
              <option value="name_asc" <?php if ($sort_by == 'name_asc') echo 'selected'; ?>>Sort by Name A-Z</option>
              <option value="name_desc" <?php if ($sort_by == 'name_desc') echo 'selected'; ?>>Sort by Name Z-A</option>
              <option value="status" <?php if ($sort_by == 'status') echo 'selected'; ?>>Sort by Status</option>
            </select>
          </form>
        </div>
      </div>

      <!-- Medications Table -->
      <table>
        <thead>
          <tr>
            <th>Prescription Name</th>
            <th>Dosage</th>
            <th>Start Date</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?php echo htmlspecialchars($row['MedicationName']); ?></td>
                <td><?php echo htmlspecialchars($row['Dosage']); ?></td>
                <td><?php echo date('M d, Y', strtotime($row['StartDate'])); ?></td>
                <td>
                  <span class="status-badge status-<?php echo strtolower(str_replace(' ', '', $row['Status'])); ?>">
                    <?php echo $row['Status']; ?>
                  </span>
                </td>
                <td>
                  <a href="medication-detail.php?id=<?php echo $row['MedicationID']; ?>" class="view-detail">View detail</a>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" style="text-align: center;">No medications found</td>
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
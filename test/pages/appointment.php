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

// Handle appointment cancellation
if (isset($_POST['cancel_appointment']) && isset($_POST['appointment_id'])) {
  $appointment_id = (int)$_POST['appointment_id'];

  // Verify the appointment belongs to this patient
  $check_query = "SELECT AppointmentID FROM APPOINTMENT WHERE AppointmentID = ? AND PatientID = ?";
  $check_stmt = $conn->prepare($check_query);
  $check_stmt->bind_param("ii", $appointment_id, $patient_id);
  $check_stmt->execute();
  $check_result = $check_stmt->get_result();

  if ($check_result->num_rows > 0) {
    // Update appointment status to 'Canceled'
    $update_query = "UPDATE APPOINTMENT SET Status = 'Canceled', ModifyAt = NOW() WHERE AppointmentID = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("i", $appointment_id);

    if ($update_stmt->execute()) {
      $success_message = "Appointment successfully canceled.";

      // Log the change in audit log
      $user_id = $_SESSION['user_id'];
      $ip_address = $_SERVER['REMOTE_ADDR'];
      $audit_query = "INSERT INTO AUDIT_LOG (UserID, Timestamp, Action, TableAffected, RecordID, IPAddress) 
                           VALUES (?, NOW(), 'Cancel Appointment', 'APPOINTMENT', ?, ?)";
      $audit_stmt = $conn->prepare($audit_query);
      $audit_stmt->bind_param("iis", $user_id, $appointment_id, $ip_address);
      $audit_stmt->execute();
      $audit_stmt->close();
    } else {
      $error_message = "Failed to cancel appointment. Please try again.";
    }

    $update_stmt->close();
  } else {
    $error_message = "Invalid appointment selection.";
  }

  $check_stmt->close();
}

// Default sort and filter options
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'date_asc';
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';

// Build the query based on sort and filter options
$query = "SELECT a.AppointmentID, a.AppointmentDate, a.AppointmentTime, a.Reason, a.Status,
          CONCAT(d.FirstName, ' ', d.LastName) AS DoctorName,
          d.Department
          FROM APPOINTMENT a
          JOIN DOCTOR d ON a.DoctorID = d.DoctorID
          WHERE a.PatientID = ?";

// Add filter conditions
if (!empty($filter_date)) {
  $query .= " AND DATE(a.AppointmentDate) = ?";
}

if (!empty($filter_status)) {
  $query .= " AND a.Status = ?";
}

// Add sorting
switch ($sort_by) {
  case 'date_desc':
    $query .= " ORDER BY a.AppointmentDate DESC, a.AppointmentTime DESC";
    break;
  case 'date_asc':
  default:
    $query .= " ORDER BY a.AppointmentDate ASC, a.AppointmentTime ASC";
    break;
  case 'status':
    $query .= " ORDER BY a.Status, a.AppointmentDate ASC, a.AppointmentTime ASC";
    break;
}

// Prepare and execute the statement
$stmt = $conn->prepare($query);

if (!empty($filter_date) && !empty($filter_status)) {
  $stmt->bind_param("iss", $patient_id, $filter_date, $filter_status);
} elseif (!empty($filter_date)) {
  $stmt->bind_param("is", $patient_id, $filter_date);
} elseif (!empty($filter_status)) {
  $stmt->bind_param("is", $patient_id, $filter_status);
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
  <title>Appointments - Patient Portal</title>
  <link rel="stylesheet" href="../css/main.css">
  <script src="../js/main.js" defer></script>
  <style>
    .success-message {
      background-color: #d4edda;
      color: #155724;
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 4px;
    }

    .error-message {
      background-color: #f8d7da;
      color: #721c24;
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 4px;
    }

    .status-badge {
      padding: 5px 10px;
      border-radius: 12px;
      font-size: 0.85em;
      font-weight: 500;
    }

    .status-scheduled {
      background-color: #e3f2fd;
      color: #0d47a1;
    }

    .status-completed {
      background-color: #e8f5e9;
      color: #1b5e20;
    }

    .status-canceled {
      background-color: #ffebee;
      color: #b71c1c;
    }

    .status-missed {
      background-color: #fafafa;
      color: #616161;
    }
  </style>
</head>

<body>
  <header class="page-header">
    <div class="logo">
      <img src="../assets/icons/healthcare.png" alt="Hospital Logo" class="logo-image">
      <span class="logo-text">Hospital's Name</span>
    </div>
  </header>
  <div class="page-header">Appointment - <?php echo htmlspecialchars($_SESSION['username']); ?></div>
  <div class="container">
    <!-- Sidebar Navigation -->
    <div class="sidebar">
      <ul class="sidebar-menu">
        <li class="active"><a href="appointment.php">Appointment</a></li>
        <li><a href="records.php">Records</a></li>
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
        <h1>My Appointments</h1>
        <div class="header-actions">
          <form action="" method="GET" id="filter-form">
            <input type="date" name="filter_date" value="<?php echo $filter_date; ?>" onchange="document.getElementById('filter-form').submit();">
            <select name="filter_status" onchange="document.getElementById('filter-form').submit();">
              <option value="" <?php if ($filter_status == '') echo 'selected'; ?>>All Status</option>
              <option value="Scheduled" <?php if ($filter_status == 'Scheduled') echo 'selected'; ?>>Scheduled</option>
              <option value="Completed" <?php if ($filter_status == 'Completed') echo 'selected'; ?>>Completed</option>
              <option value="Canceled" <?php if ($filter_status == 'Canceled') echo 'selected'; ?>>Canceled</option>
              <option value="Missed" <?php if ($filter_status == 'Missed') echo 'selected'; ?>>Missed</option>
            </select>
            <select name="sort" onchange="document.getElementById('filter-form').submit();">
              <option value="date_asc" <?php if ($sort_by == 'date_asc') echo 'selected'; ?>>Sort by Date ↑</option>
              <option value="date_desc" <?php if ($sort_by == 'date_desc') echo 'selected'; ?>>Sort by Date ↓</option>
              <option value="status" <?php if ($sort_by == 'status') echo 'selected'; ?>>Sort by Status</option>
            </select>
          </form>
        </div>
      </div>

      <?php if (isset($success_message)): ?>
        <div class="success-message"><?php echo $success_message; ?></div>
      <?php endif; ?>

      <?php if (isset($error_message)): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
      <?php endif; ?>

      <!-- Appointments Table -->
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Time</th>
            <th>Doctor</th>
            <th>Reason</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?php echo date('M d, Y', strtotime($row['AppointmentDate'])); ?></td>
                <td><?php echo date('h:i A', strtotime($row['AppointmentTime'])); ?></td>
                <td><?php echo htmlspecialchars($row['DoctorName']); ?><br>
                  <small><?php echo htmlspecialchars($row['Department']); ?></small>
                </td>
                <td><?php echo htmlspecialchars($row['Reason']); ?></td>
                <td>
                  <span class="status-badge status-<?php echo strtolower($row['Status']); ?>">
                    <?php echo $row['Status']; ?>
                  </span>
                </td>
                <td>
                  <?php if ($row['Status'] == 'Scheduled' && strtotime($row['AppointmentDate']) > time()): ?>
                    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to cancel this appointment?');">
                      <input type="hidden" name="appointment_id" value="<?php echo $row['AppointmentID']; ?>">
                      <button type="submit" name="cancel_appointment" class="cancel-btn">Cancel</button>
                    </form>
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" style="text-align: center;">No appointments found</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>

      <div class="form-action">
        <a href="new-appointment.php" class="button">Schedule New Appointment</a>
      </div>
    </div>
  </div>
</body>

</html>

<?php
// Close the database connection
$stmt->close();
$conn->close();
?>
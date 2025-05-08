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

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Get form data and sanitize inputs
  $reason = htmlspecialchars(trim($_POST['reason']));
  $doctor_id = (int)$_POST['doctor'];
  $appointment_date = $_POST['date'];
  $appointment_time = $_POST['time'];

  // Validate data
  $errors = [];

  if (empty($reason)) {
    $errors[] = "Reason is required";
  }

  if (empty($doctor_id)) {
    $errors[] = "Doctor selection is required";
  }

  if (empty($appointment_date)) {
    $errors[] = "Date is required";
  } else {
    // Check if the date is in the future
    $current_date = date('Y-m-d');
    if ($appointment_date < $current_date) {
      $errors[] = "Appointment date must be in the future";
    }
  }

  if (empty($appointment_time)) {
    $errors[] = "Time is required";
  }

  // If no errors, insert the appointment
  if (empty($errors)) {
    // Check doctor availability
    $check_query = "SELECT COUNT(*) as count FROM APPOINTMENT 
                    WHERE DoctorID = ? AND AppointmentDate = ? AND AppointmentTime = ? 
                    AND Status = 'Scheduled'";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("iss", $doctor_id, $appointment_date, $appointment_time);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $row = $check_result->fetch_assoc();

    if ($row['count'] > 0) {
      $errors[] = "This time slot is already booked. Please select another time.";
    } else {
      // Insert appointment
      $insert_query = "INSERT INTO APPOINTMENT (PatientID, DoctorID, AppointmentDate, AppointmentTime, Reason, Status, CreateAt, ModifyAt) 
                      VALUES (?, ?, ?, ?, ?, 'Scheduled', NOW(), NOW())";
      $insert_stmt = $conn->prepare($insert_query);
      $insert_stmt->bind_param("iisss", $patient_id, $doctor_id, $appointment_date, $appointment_time, $reason);

      if ($insert_stmt->execute()) {
        $appointment_id = $insert_stmt->insert_id;
        $success_message = "Appointment scheduled successfully!";

        // Log the action in audit log
        $user_id = $_SESSION['user_id'];
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $audit_query = "INSERT INTO AUDIT_LOG (UserID, Timestamp, Action, TableAffected, RecordID, IPAddress) 
                       VALUES (?, NOW(), 'Create Appointment', 'APPOINTMENT', ?, ?)";
        $audit_stmt = $conn->prepare($audit_query);
        $audit_stmt->bind_param("iis", $user_id, $appointment_id, $ip_address);
        $audit_stmt->execute();

        // Redirect to appointments page after successful booking
        header("Location: appointment.php?success=booked");
        exit();
      } else {
        $errors[] = "Error booking appointment: " . $conn->error;
      }
    }
  }
}

// Get the list of doctors
$doctor_query = "SELECT DoctorID, FirstName, LastName, Department FROM DOCTOR ORDER BY Department, LastName";
$doctor_result = $conn->query($doctor_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>New Appointment - Patient Portal</title>
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
  <div class="page-header">Appointment(New Appointment) - <?php echo htmlspecialchars($_SESSION['username']); ?></div>
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
        <h1>Schedule New Appointment</h1>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="error-message">
          <ul>
            <?php foreach ($errors as $error): ?>
              <li><?php echo $error; ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <!-- Appointment Form -->
      <form id="appointment-form" method="POST" action="">
        <div class="form-group">
          <label for="reason">Reason:</label>
          <input type="text" id="reason" name="reason" class="form-control" placeholder="Write down" value="<?php echo isset($_POST['reason']) ? htmlspecialchars($_POST['reason']) : ''; ?>" required>
        </div>

        <div class="form-group">
          <label for="doctor">Doctor:</label>
          <select id="doctor" name="doctor" class="form-control" required>
            <option value="" selected disabled>Select a doctor</option>
            <?php
            if ($doctor_result->num_rows > 0) {
              $current_department = "";
              while ($doctor = $doctor_result->fetch_assoc()) {
                // Add department header if it's a new department
                if ($current_department != $doctor['Department']) {
                  if ($current_department != "") {
                    echo "</optgroup>";
                  }
                  $current_department = $doctor['Department'];
                  echo "<optgroup label='" . htmlspecialchars($current_department) . "'>";
                }
                $selected = (isset($_POST['doctor']) && $_POST['doctor'] == $doctor['DoctorID']) ? 'selected' : '';
                echo "<option value='" . $doctor['DoctorID'] . "' $selected>" .
                  "Dr. " . htmlspecialchars($doctor['FirstName'] . " " . $doctor['LastName']) .
                  "</option>";
              }
              echo "</optgroup>";
            }
            ?>
          </select>
        </div>

        <div class="form-group">
          <label for="date">Date:</label>
          <input type="date" id="date" name="date" class="form-control"
            min="<?php echo date('Y-m-d'); ?>"
            value="<?php echo isset($_POST['date']) ? $_POST['date'] : ''; ?>" required>
        </div>

        <div class="form-group">
          <label for="time">Time:</label>
          <select id="time" name="time" class="form-control" required>
            <option value="" selected disabled>Select time</option>
            <?php
            // Generate time slots from 8:00 AM to 5:00 PM with 30-minute intervals
            $start = strtotime('08:00');
            $end = strtotime('17:00');
            $interval = 30 * 60; // 30 minutes in seconds

            for ($i = $start; $i <= $end; $i += $interval) {
              $time_value = date('H:i:s', $i);
              $time_display = date('h:i A', $i);
              $selected = (isset($_POST['time']) && $_POST['time'] == $time_value) ? 'selected' : '';
              echo "<option value='$time_value' $selected>$time_display</option>";
            }
            ?>
          </select>
        </div>

        <div class="form-action">
          <button type="submit" class="submit-button" data-form-type="appointment">Confirm appointment</button>
          <a href="appointment.php" class="button button-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</body>

</html>

<?php
// Close the database connection
$conn->close();
?>
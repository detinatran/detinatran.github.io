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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Validate form inputs
  $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
  $doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
  $visit_date = isset($_POST['visit_date']) ? $_POST['visit_date'] : '';
  $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

  // Basic validation
  $errors = [];

  if ($rating < 1 || $rating > 5) {
    $errors[] = "Please select a rating between 1 and 5 stars.";
  }

  if ($doctor_id <= 0) {
    $errors[] = "Please select a doctor.";
  }

  if (empty($visit_date)) {
    $errors[] = "Please select a visit date.";
  }

  if (empty($comment)) {
    $errors[] = "Please provide feedback comments.";
  }

  // If no errors, process the feedback submission
  if (empty($errors)) {
    // Begin transaction
    $conn->begin_transaction();

    try {
      // First insert into FEEDBACK table
      $feedback_query = "INSERT INTO FEEDBACK (PatientID, NurseID, FeedbackDate, Rating, Comments, IsAddressed) 
                        VALUES (?, NULL, NOW(), ?, ?, FALSE)";

      $feedback_stmt = $conn->prepare($feedback_query);
      $feedback_stmt->bind_param("iis", $patient_id, $rating, $comment);
      $feedback_stmt->execute();

      $feedback_id = $conn->insert_id;

      // Then create an INTERACTION record
      $interaction_query = "INSERT INTO INTERACTION (PatientID, StaffType, InteractionDate, InteractionType, Description, RecordedAt) 
                          VALUES (?, 'Doctor', ?, 'Doctor Feedback', CONCAT('Feedback for doctor ID: ', ?), NOW())";

      $interaction_stmt = $conn->prepare($interaction_query);
      $interaction_stmt->bind_param("isi", $patient_id, $visit_date, $doctor_id);
      $interaction_stmt->execute();

      // Create audit log entry
      $audit_query = "INSERT INTO AUDIT_LOG (UserID, Timestamp, Action, TableAffected, RecordID, IPAddress) 
                    VALUES (?, NOW(), 'Created Feedback', 'FEEDBACK', ?, ?)";

      $user_id = $_SESSION['user_id'];
      $ip_address = $_SERVER['REMOTE_ADDR'];

      $audit_stmt = $conn->prepare($audit_query);
      $audit_stmt->bind_param("iis", $user_id, $feedback_id, $ip_address);
      $audit_stmt->execute();

      // Commit transaction
      $conn->commit();

      // Redirect to feedback page with success message
      $_SESSION['success_message'] = "Thank you! Your feedback has been submitted successfully.";
      header("Location: feedback.php");
      exit();
    } catch (Exception $e) {
      // Rollback transaction on error
      $conn->rollback();
      $errors[] = "Error submitting feedback: " . $e->getMessage();
    }
  }
}

// Query to get all doctors for the dropdown
$doctors_query = "SELECT DoctorID, CONCAT(FirstName, ' ', LastName) AS DoctorName, Specialization 
                FROM DOCTOR 
                ORDER BY LastName, FirstName";

$doctors_result = $conn->query($doctors_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Submit Feedback - Patient Portal</title>
  <link rel="stylesheet" href="../css/main.css">
  <script src="../js/main.js" defer></script>
  <style>
    .stars {
      display: inline-block;
      unicode-bidi: bidi-override;
      direction: rtl;
    }

    .stars input {
      display: none;
    }

    .stars label {
      display: inline-block;
      font-size: 30px;
      color: #ccc;
      cursor: pointer;
    }

    .stars label:hover,
    .stars label:hover~label,
    .stars input:checked~label {
      color: #FFD700;
    }

    .error-message {
      color: red;
      margin-bottom: 15px;
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
  <div class="page-header">Submit Feedback - <?php echo htmlspecialchars($_SESSION['username']); ?></div>
  <div class="container">
    <!-- Sidebar Navigation -->
    <div class="sidebar">
      <ul class="sidebar-menu">
        <li><a href="appointment.php">Appointment</a></li>
        <li><a href="records.php">Records</a></li>
        <li><a href="medications.php">Medications</a></li>
        <li class="active"><a href="feedback.php">Feedback</a></li>
        <li><a href="profile.php">Profile</a></li>
        <li><a href="settings.php">Setting</a></li>
        <li><a href="../logout.php">Logout</a></li>
      </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
      <div class="header">
        <h1>Submit New Feedback</h1>
        <div class="header-actions">
          <a href="feedback.php" class="button secondary">Back to Feedback History</a>
        </div>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="error-message">
          <ul>
            <?php foreach ($errors as $error): ?>
              <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <!-- Feedback Form -->
      <form id="feedback-form" method="POST" action="submit-feedback.php">
        <div class="form-group">
          <label>Rating</label>
          <div class="stars">
            <input type="radio" id="star5" name="rating" value="5" <?php if (isset($_POST['rating']) && $_POST['rating'] == 5) echo 'checked'; ?> />
            <label for="star5">★</label>
            <input type="radio" id="star4" name="rating" value="4" <?php if (isset($_POST['rating']) && $_POST['rating'] == 4) echo 'checked'; ?> />
            <label for="star4">★</label>
            <input type="radio" id="star3" name="rating" value="3" <?php if (isset($_POST['rating']) && $_POST['rating'] == 3) echo 'checked'; ?> />
            <label for="star3">★</label>
            <input type="radio" id="star2" name="rating" value="2" <?php if (isset($_POST['rating']) && $_POST['rating'] == 2) echo 'checked'; ?> />
            <label for="star2">★</label>
            <input type="radio" id="star1" name="rating" value="1" <?php if (isset($_POST['rating']) && $_POST['rating'] == 1) echo 'checked'; ?> />
            <label for="star1">★</label>
          </div>
        </div>

        <div class="form-group">
          <label for="doctor_id">Doctor's Name</label>
          <select id="doctor_id" name="doctor_id" class="form-control" required>
            <option value="" selected disabled>Select doctor</option>
            <?php while ($doctor = $doctors_result->fetch_assoc()): ?>
              <option value="<?php echo $doctor['DoctorID']; ?>"
                <?php if (isset($_POST['doctor_id']) && $_POST['doctor_id'] == $doctor['DoctorID']) echo 'selected'; ?>>
                <?php echo htmlspecialchars($doctor['DoctorName']); ?> (<?php echo htmlspecialchars($doctor['Specialization']); ?>)
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="visit_date">Date of visit</label>
          <input type="date" id="visit_date" name="visit_date" class="form-control"
            value="<?php echo isset($_POST['visit_date']) ? htmlspecialchars($_POST['visit_date']) : ''; ?>"
            max="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <div class="form-group">
          <label for="comment">Comment</label>
          <textarea id="comment" name="comment" class="form-control" rows="5" required><?php echo isset($_POST['comment']) ? htmlspecialchars($_POST['comment']) : ''; ?></textarea>
        </div>

        <div class="form-action">
          <button type="submit" class="submit-button">Submit feedback</button>
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
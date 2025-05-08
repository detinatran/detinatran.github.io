<?php
// Start session
session_start();

// Include database configuration
include '../db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  // Redirect to login page if not logged in
  header("Location: login.php");
  exit();
}

// Get user information from the database
$user_id = $_SESSION['user_id'];
$user_query = "SELECT u.*, p.* FROM USER u 
              JOIN PATIENT p ON u.LinkedID = p.PatientID 
              WHERE u.UserID = ? AND u.Role = 'patient'";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

// Check if user exists and is a patient
if ($user_result->num_rows === 0) {
  // Redirect to login if user not found or not a patient
  header("Location: login.php");
  exit();
}

$user = $user_result->fetch_assoc();

// Process alerts/messages if any
$message = '';
if (isset($_SESSION['message'])) {
  $message = $_SESSION['message'];
  unset($_SESSION['message']); // Clear the message after displaying
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings - Patient Portal</title>
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

  <div class="page-header">Settings - Patient</div>

  <div class="container">
    <!-- Sidebar Navigation -->
    <div class="sidebar">
      <ul class="sidebar-menu">
        <li><a href="appointment.php">Appointment</a></li>
        <li><a href="records.php">Records</a></li>
        <li><a href="medications.php">Medications</a></li>
        <li><a href="feedback.php">Feedback</a></li>
        <li><a href="profile.php">Profile</a></li>
        <li><a href="settings.php" class="active">Settings</a></li>
        <li><a href="logout.php">Logout</a></li>
      </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">


      <?php if (!empty($message)): ?>
        <div class="alert <?php echo strpos($message, 'success') !== false ? 'alert-success' : 'alert-error'; ?>">
          <?php echo $message; ?>
        </div>
      <?php endif; ?>

      <!-- Update Personal Information -->
      <div class="card">
        <div class="card-header">
          <h3>Update Personal Information</h3>
        </div>
        <form action="update_info.php" method="POST">
          <div class="info-grid">
            <!-- Account Information -->
            <div class="section-title">Account Information</div>

            <div class="info-label">Username</div>
            <input type="text" name="username" value="<?php echo htmlspecialchars($user['Username']); ?>" required>


            <!-- Personal Information -->
            <div class="section-title">Personal Information</div>

            <div class="info-label">First Name</div>
            <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['FirstName']); ?>" required>

            <div class="info-label">Last Name</div>
            <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['LastName']); ?>" required>

            <div class="info-label">Date of Birth</div>
            <input type="date" name="date_of_birth" value="<?php echo htmlspecialchars($user['DateOfBirth']); ?>" required>

            <div class="info-label">Gender</div>
            <select name="gender" required>
              <option value="Male" <?php echo ($user['Gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
              <option value="Female" <?php echo ($user['Gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
              <option value="Other" <?php echo ($user['Gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
            </select>

            <!-- Contact Information -->
            <div class="section-title">Contact Information</div>

            <div class="info-label">Phone Number</div>
            <input type="text" name="phone_number" value="<?php echo htmlspecialchars($user['PhoneNumber']); ?>" required>

            <div class="info-label">Address</div>
            <textarea name="address" required><?php echo htmlspecialchars($user['Address']); ?></textarea>

            <div class="form-actions">
              <button type="submit" class="btn btn-primary">Update Information</button>
            </div>
          </div>
        </form>
      </div>

      <!-- Change Password -->
      <div class="card">
        <div class="card-header">
          <h3>Change Password</h3>
        </div>
        <form action="change_password.php" method="POST">
          <div class="info-grid">
            <div class="info-label">Current Password</div>
            <input type="password" name="current_password" required>

            <div class="info-label">New Password</div>
            <input type="password" name="new_password" id="new_password" required>

            <div class="info-label">Confirm New Password</div>
            <input type="password" name="confirm_new_password" id="confirm_new_password" required>

            <div class="password-requirements">
              Password must be at least 8 characters and include uppercase, lowercase, numbers, and special characters.
            </div>

            <div class="form-actions">
              <button type="submit" class="btn btn-primary">Change Password</button>
            </div>
          </div>
        </form>
      </div>

    </div>

    <script>
      // Password validation
      document.addEventListener('DOMContentLoaded', function() {
        const passwordForm = document.querySelector('form[action="change_password.php"]');
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_new_password');

        passwordForm.addEventListener('submit', function(e) {
          // Password strength check
          const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
          if (!passwordRegex.test(newPassword.value)) {
            e.preventDefault();
            alert('Password must be at least 8 characters and include uppercase, lowercase, numbers, and special characters.');
            return;
          }

          // Password match check
          if (newPassword.value !== confirmPassword.value) {
            e.preventDefault();
            alert('New password and confirmation password do not match!');
          }
        });
      });
    </script>
</body>

</html>

<?php
// Close database connection
$conn->close();
?>
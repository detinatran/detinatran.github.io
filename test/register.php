<?php
// Start session
session_start();

// Include database configuration
include 'db_config.php';

// Initialize variables
$error_message = "";
$success_message = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Get form data
  $username = $conn->real_escape_string($_POST['username']);
  $password = $_POST['password'];
  $confirm_password = $_POST['confirm_password'];
  $role = $_POST['role'];

  // Additional role-specific fields
  $admin_code = isset($_POST['admin_auth_code']) ? $conn->real_escape_string($_POST['admin_auth_code']) : "";
  $doctor_license = isset($_POST['doctor_license']) ? $conn->real_escape_string($_POST['doctor_license']) : "";
  $nurse_id = isset($_POST['nurse_id_number']) ? $conn->real_escape_string($_POST['nurse_id_number']) : "";

  // Validate input based on role
  $valid = true;

  if (empty($username) || empty($password) || empty($confirm_password) || empty($role)) {
    $error_message = "Please enter all required fields";
    $valid = false;
  } elseif ($password !== $confirm_password) {
    $error_message = "Passwords do not match";
    $valid = false;
  } elseif ($role == "admin" && empty($admin_code)) {
    $error_message = "Admin authentication code is required";
    $valid = false;
  } elseif ($role == "doctor" && empty($doctor_license)) {
    $error_message = "Medical license ID is required";
    $valid = false;
  } elseif ($role == "nurse" && empty($nurse_id)) {
    $error_message = "Nursing ID is required";
    $valid = false;
  }

  // Process valid form
  if ($valid) {
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Query to check if username already exists
    $sql = "SELECT * FROM USER WHERE Username = '{$username}'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
      $error_message = "Username already taken. Please choose a different one.";
    } else {
      // Insert new user into the database
      $insert_sql = "INSERT INTO USER (Username, PasswordHash, Role) VALUES ('{$username}', '{$hashedPassword}', '{$role}')";
      if ($conn->query($insert_sql)) {
        // Insert role-specific data if necessary
        $user_id = $conn->insert_id; // Get the ID of the inserted user

        switch ($role) {
          case 'admin':
            // Check for admin auth code
            if ($admin_code === "ADMIN123") {
              // Insert admin data
              $admin_sql = "INSERT INTO ADMIN (UserID, AuthCode) VALUES ({$user_id}, '{$admin_code}')";
              $conn->query($admin_sql);
            } else {
              $error_message = "Invalid admin authentication code.";
              break;
            }
            break;
          case 'doctor':
            // Insert doctor license data
            $doctor_sql = "INSERT INTO DOCTOR (UserID, LicenseID) VALUES ({$user_id}, '{$doctor_license}')";
            $conn->query($doctor_sql);
            break;
          case 'nurse':
            // Insert nurse ID data
            $nurse_sql = "INSERT INTO NURSE (UserID, NurseID) VALUES ({$user_id}, '{$nurse_id}')";
            $conn->query($nurse_sql);
            break;
        }

        $success_message = "Account successfully created! You can now log in.";
      } else {
        $error_message = "Error creating account. Please try again.";
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HealthcarePortal - Sign Up</title>
  <!-- Include your styles and other necessary meta tags -->
</head>

<body>
  <!-- Sign-up Form -->
  <h1>Create an Account</h1>

  <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
    <div class="form-group">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" placeholder="Enter your username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
    </div>

    <div class="form-group">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" placeholder="••••••••" required>
    </div>

    <div class="form-group">
      <label for="confirm_password">Confirm Password</label>
      <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required>
    </div>

    <div class="role-selector">
      <label>Select your role</label>
      <div class="role-options">
        <div class="role-option" data-role="patient" onclick="selectRole('patient')">
          <div class="role-name">Patient</div>
        </div>
        <div class="role-option" data-role="nurse" onclick="selectRole('nurse')">
          <div class="role-name">Nurse</div>
        </div>
        <div class="role-option" data-role="doctor" onclick="selectRole('doctor')">
          <div class="role-name">Doctor</div>
        </div>
        <div class="role-option" data-role="admin" onclick="selectRole('admin')">
          <div class="role-name">Admin</div>
        </div>
      </div>
      <input type="hidden" name="role" id="selected-role" value="<?php echo isset($_POST['role']) ? $_POST['role'] : 'patient'; ?>">
    </div>

    <div id="admin-code" class="form-group" style="display: none;">
      <label for="admin-auth-code">Admin Authentication Code</label>
      <input type="text" id="admin-auth-code" name="admin_auth_code" placeholder="Enter admin code">
    </div>

    <div id="doctor-id" class="form-group" style="display: none;">
      <label for="doctor-license">Medical License ID</label>
      <input type="text" id="doctor-license" name="doctor_license" placeholder="Enter license number">
    </div>

    <div id="nurse-id" class="form-group" style="display: none;">
      <label for="nurse-id-number">Nursing ID</label>
      <input type="text" id="nurse-id-number" name="nurse_id_number" placeholder="Enter nursing ID">
    </div>

    <div class="error-message"><?php echo $error_message; ?></div>
    <div class="success-message"><?php echo $success_message; ?></div>

    <button type="submit" class="btn">Sign up</button>
  </form>

  <script>
    function selectRole(role) {
      document.getElementById('selected-role').value = role;

      // Hide all role-specific fields
      document.getElementById('admin-code').style.display = 'none';
      document.getElementById('doctor-id').style.display = 'none';
      document.getElementById('nurse-id').style.display = 'none';

      // Show role-specific field if needed
      if (role === 'admin') {
        document.getElementById('admin-code').style.display = 'block';
      } else if (role === 'doctor') {
        document.getElementById('doctor-id').style.display = 'block';
      } else if (role === 'nurse') {
        document.getElementById('nurse-id').style.display = 'block';
      }
    }

    // Pre-select role from post or default to patient
    <?php
    if (isset($_POST['role'])) {
      echo "selectRole('" . $_POST['role'] . "');";
    } else {
      echo "selectRole('patient');";
    }
    ?>
  </script>
</body>

</html>

<?php
// Close the database connection
$conn->close();
?>
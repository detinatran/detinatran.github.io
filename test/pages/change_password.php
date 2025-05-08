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

// Process the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Get user ID from session
  $user_id = $_SESSION['user_id'];

  // Get form data
  $current_password = $_POST['current_password'];
  $new_password = $_POST['new_password'];
  $confirm_new_password = $_POST['confirm_new_password'];

  // Password validation server-side
  $password_regex = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/";

  if (!preg_match($password_regex, $new_password)) {
    $_SESSION['message'] = "Password must be at least 8 characters and include uppercase, lowercase, numbers, and special characters.";
    header("Location: settings.php");
    exit();
  }

  // Check if passwords match
  if ($new_password !== $confirm_new_password) {
    $_SESSION['message'] = "New password and confirmation password do not match!";
    header("Location: settings.php");
    exit();
  }

  // Get the current hashed password from the database
  $password_query = "SELECT PasswordHash FROM USER WHERE UserID = ?";
  $password_stmt = $conn->prepare($password_query);
  $password_stmt->bind_param("i", $user_id);
  $password_stmt->execute();
  $password_result = $password_stmt->get_result();

  if ($password_result->num_rows === 0) {
    $_SESSION['message'] = "User not found.";
    header("Location: settings.php");
    exit();
  }

  $user_data = $password_result->fetch_assoc();
  $hashed_password = $user_data['PasswordHash'];

  // Verify current password (check both hashed and plain text)
  if (!password_verify($current_password, $hashed_password) && $current_password !== $hashed_password) {
    $_SESSION['message'] = "Current password is incorrect.";
    header("Location: settings.php");
    exit();
  }

  // Hash the new password
  $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

  // Update the password in the database
  $update_query = "UPDATE USER SET PasswordHash = ? WHERE UserID = ?";
  $update_stmt = $conn->prepare($update_query);
  $update_stmt->bind_param("si", $new_password_hash, $user_id);

  if ($update_stmt->execute()) {
    // Log the password change in the audit log
    $action = "Password changed";
    $table_affected = "USER";
    $ip_address = $_SERVER['REMOTE_ADDR'];

    $log_query = "INSERT INTO AUDIT_LOG (UserID, Timestamp, Action, TableAffected, RecordID, IPAddress) 
                  VALUES (?, NOW(), ?, ?, ?, ?)";
    $log_stmt = $conn->prepare($log_query);
    $log_stmt->bind_param("issis", $user_id, $action, $table_affected, $user_id, $ip_address);
    $log_stmt->execute();

    $_SESSION['message'] = "Password changed successfully!";
  } else {
    $_SESSION['message'] = "Error updating password: " . $conn->error;
  }

  // Close statements
  $password_stmt->close();
  $update_stmt->close();
  if (isset($log_stmt)) $log_stmt->close();

  // Redirect back to settings page
  header("Location: settings.php");
  exit();
} else {
  // If not a POST request, redirect to settings page
  header("Location: settings.php");
  exit();
}

// Close database connection
$conn->close();

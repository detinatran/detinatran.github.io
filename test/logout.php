<?php
// Start the session
session_start();

// Include database configuration
include 'db_config.php';

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
  // Get user information before destroying session
  $user_id = $_SESSION['user_id'];
  $username = $_SESSION['username'];

  // Check if the logout is confirmed
  if (isset($_GET['logout_confirm']) && $_GET['logout_confirm'] == 'yes') {
    // Record logout in audit log
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $audit_sql = "INSERT INTO AUDIT_LOG (UserID, Timestamp, Action, TableAffected, IPAddress) 
                        VALUES (?, NOW(), 'Logout', 'USER', ?)";

    $stmt = $conn->prepare($audit_sql);
    $stmt->bind_param("is", $user_id, $ip_address);
    $stmt->execute();
    $stmt->close();

    // Close database connection
    $conn->close();

    // Unset all session variables
    $_SESSION = array();

    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
      setcookie(session_name(), '', time() - 3600, '/');
    }

    // Destroy the session
    session_destroy();

    // Redirect to login page with success message
    header("Location: login.php?logout=success");
    exit();
  } else {
    // If not confirmed, show confirmation prompt
    echo '<script>
                if (confirm("Are you sure you want to log out?")) {
                    window.location.href = "logout.php?logout_confirm=yes";
                } else {
                    window.location.href = "index.php"; // redirect to the main page or previous page
                }
              </script>';
  }
}

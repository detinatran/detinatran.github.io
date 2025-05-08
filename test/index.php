<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  // Redirect to login page if not logged in
  header("Location: login.php");
  exit();
} else {
  // Redirect to records page if logged in
  header("Location: pages/records.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Patient Portal</title>
  <link rel="stylesheet" href="css/main.css">
  <script src="js/main.js" defer></script>
</head>

<body>
  <header class="page-header">
    <div class="logo">
      <img src="assets/icons/healthcare.png" alt="Hospital Logo" class="logo-image">
      <span class="logo-text">Hospital's Name</span>
    </div>
  </header>
  <div class="container">
    <!-- Redirecting to the dashboard -->
    <p>Redirecting to the dashboard...</p>
  </div>
</body>

</html>
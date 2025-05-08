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

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Get user ID from session
  $user_id = $_SESSION['user_id'];

  // Get user's patient ID from database
  $user_query = "SELECT LinkedID FROM USER WHERE UserID = ? AND Role = 'patient'";
  $user_stmt = $conn->prepare($user_query);
  $user_stmt->bind_param("i", $user_id);
  $user_stmt->execute();
  $user_result = $user_stmt->get_result();

  if ($user_result->num_rows === 0) {
    $_SESSION['message'] = 'User not found!';
    header("Location: settings.php");
    exit();
  }

  $user_data = $user_result->fetch_assoc();
  $patient_id = $user_data['LinkedID'];

  // Get form data
  $username = $_POST['username'];
  $email = $_POST['email'];
  $first_name = $_POST['first_name'];
  $last_name = $_POST['last_name'];
  $date_of_birth = $_POST['date_of_birth'];
  $gender = $_POST['gender'];
  $phone_number = $_POST['phone_number'];
  $address = $_POST['address'];

  // Begin transaction
  $conn->begin_transaction();

  try {
    // Update USER table
    $update_user_query = "UPDATE USER SET Username = ? WHERE UserID = ?";
    $update_user_stmt = $conn->prepare($update_user_query);
    $update_user_stmt->bind_param("si", $username, $user_id);
    $update_user_stmt->execute();

    // Update PATIENT table
    $update_patient_query = "UPDATE PATIENT SET 
                            FirstName = ?, 
                            LastName = ?, 
                            DateOfBirth = ?, 
                            Gender = ?, 
                            Address = ?, 
                            PhoneNumber = ? 
                            WHERE PatientID = ?";
    $update_patient_stmt = $conn->prepare($update_patient_query);
    $update_patient_stmt->bind_param(
      "ssssssi",
      $first_name,
      $last_name,
      $date_of_birth,
      $gender,
      $address,
      $phone_number,
      $patient_id
    );
    $update_patient_stmt->execute();

    // Log the update in AUDIT_LOG
    $action = "Profile updated";
    $table_affected = "USER,PATIENT";
    $ip_address = $_SERVER['REMOTE_ADDR'];

    $log_query = "INSERT INTO AUDIT_LOG (UserID, Timestamp, Action, TableAffected, RecordID, IPAddress) 
                  VALUES (?, NOW(), ?, ?, ?, ?)";
    $log_stmt = $conn->prepare($log_query);
    $log_stmt->bind_param("issss", $user_id, $action, $table_affected, $user_id, $ip_address);
    $log_stmt->execute();

    // Commit transaction
    $conn->commit();

    $_SESSION['message'] = 'Personal information updated successfully!';
  } catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $_SESSION['message'] = 'Error updating personal information: ' . $e->getMessage();
  }

  // Redirect back to settings page
  header("Location: settings.php");
  exit();
}

// If not POST request, redirect to settings page
header("Location: settings.php");
exit();

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

// Get patient information
$patient_query = "SELECT p.PatientID, p.FirstName, p.LastName, p.DateOfBirth, p.Gender, 
                 p.Address, p.PhoneNumber, p.RegistrationDate
                 FROM PATIENT p
                 WHERE p.PatientID = ?";
$patient_stmt = $conn->prepare($patient_query);
$patient_stmt->bind_param("i", $patient_id);
$patient_stmt->execute();
$patient_result = $patient_stmt->get_result();

if ($patient_result->num_rows === 0) {
  $error_message = "Patient information not found.";
} else {
  $patient = $patient_result->fetch_assoc();
}

// Get the most recent vitals data
$vitals_query = "SELECT mr.RecordID, mr.Diagnosis, mr.Treatment, 
                 mr.Classification, i.InteractionDate, i.Description
                 FROM MEDICAL_RECORD mr
                 JOIN INTERACTION i ON mr.PatientID = i.PatientID
                 WHERE mr.PatientID = ? AND i.InteractionType = 'Vitals Check'
                 ORDER BY i.InteractionDate DESC LIMIT 1";
$vitals_stmt = $conn->prepare($vitals_query);
$vitals_stmt->bind_param("i", $patient_id);
$vitals_stmt->execute();
$vitals_result = $vitals_stmt->get_result();
$vitals = $vitals_result->fetch_assoc();

// Get upcoming appointments
$appointments_query = "SELECT a.AppointmentID, a.AppointmentDate, a.AppointmentTime, 
                      CONCAT(d.FirstName, ' ', d.LastName) AS DoctorName
                      FROM APPOINTMENT a
                      JOIN DOCTOR d ON a.DoctorID = d.DoctorID
                      WHERE a.PatientID = ? AND a.Status = 'Scheduled' 
                      AND a.AppointmentDate >= CURDATE()
                      ORDER BY a.AppointmentDate, a.AppointmentTime
                      LIMIT 4";
$appointments_stmt = $conn->prepare($appointments_query);
$appointments_stmt->bind_param("i", $patient_id);
$appointments_stmt->execute();
$appointments_result = $appointments_stmt->get_result();

// Parse vitals data if it exists
$temperature = "98.6"; // Default values
$blood_pressure = "120/83";
$respiratory_rate = "16.00";
$heart_rate = "74.67";

if ($vitals && !empty($vitals['Description'])) {
  // Parse the description to extract the vitals
  $vitals_data = json_decode($vitals['Description'], true);
  if ($vitals_data) {
    $temperature = $vitals_data['temperature'] ?? $temperature;
    $blood_pressure = $vitals_data['blood_pressure'] ?? $blood_pressure;
    $respiratory_rate = $vitals_data['respiratory_rate'] ?? $respiratory_rate;
    $heart_rate = $vitals_data['heart_rate'] ?? $heart_rate;
  }
}

// Calculate age from date of birth
$age = 0;
if (!empty($patient['DateOfBirth'])) {
  $dob = new DateTime($patient['DateOfBirth']);
  $now = new DateTime();
  $interval = $now->diff($dob);
  $age = $interval->y;
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile - Patient Portal</title>
  <link rel="stylesheet" href="../css/main.css">
  <script src="../js/main.js" defer></script>
  <style>
    * {

      box-sizing: border-box;
      font-family: "Poppins", Arial, sans-serif;
    }

    .dashboard {
      flex: 1;
      background-color: white;
      padding: 20px;
      border-radius: 8px;
      margin: 20px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
    }





    .personal-info {
      display: flex;

      flex-wrap: wrap;
      /* Allow items to wrap for better spacing */
    }

    .info-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      /* Divide into two columns */
      gap: 10px;
      margin-top: 10px;
      max-width: 100%;
    }

    .profile-image {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      margin-right: 20px;
      /* Add some space to the right */
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
  <div class="page-header">Profile - <?php echo htmlspecialchars($_SESSION['username']); ?></div>
  <div class="container">
    <!-- Sidebar Navigation -->
    <div class="sidebar">
      <ul class="sidebar-menu">
        <li><a href="appointment.php">Appointment</a></li>
        <li><a href="records.php">Records</a></li>
        <li><a href="medications.php">Medications</a></li>
        <li><a href="feedback.php">Feedback</a></li>
        <li class="active"><a href="profile.php">Profile</a></li>
        <li><a href="settings.php">Setting</a></li>
        <li><a href="../logout.php">Logout</a></li>
      </ul>
    </div>

    <!-- Main Content -->
    <div class="dashboard">
      <!-- Personal Information Card -->
      <div class="card">
        <div class="card-header">
          <h2>Personal Information</h2>
          <span class="more"></span>
        </div>
        <div class="personal-info">
          <img src="../GitHub.png" alt="Profile" class="profile-image">
          <div>
            <div class="patient-name">
              <?php echo htmlspecialchars($patient['FirstName'] . ' ' . $patient['LastName']); ?>
              <span class="heart-icon">♡</span>
            </div>
            <div class="patient-type">Patient</div>
            <div class="info-grid">

              <div class="info-label">Sex</div>
              <div class="info-value"><?php echo htmlspecialchars($patient['Gender']); ?></div>

              <div class="info-label">Phone</div>
              <div class="info-value"><?php echo htmlspecialchars($patient['PhoneNumber']); ?></div>

              <div class="info-label">Address</div>
              <div class="info-value"><?php echo htmlspecialchars($patient['Address']); ?></div>

              <div class="info-label">Born</div>
              <div class="info-value"><?php echo date('M d, Y', strtotime($patient['DateOfBirth'])); ?></div>

              <div class="info-label">Age</div>
              <div class="info-value"><?php echo $age; ?></div>


            </div>
          </div>
        </div>
      </div>
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

// Default sort options
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';

// Build query to get patient feedback
$query = "SELECT f.FeedbackID, f.FeedbackDate, f.Rating, f.Comments, 
          CONCAT(n.FirstName, ' ', n.LastName) AS NurseName,
          CONCAT(d.FirstName, ' ', d.LastName) AS DoctorName
          FROM FEEDBACK f
          LEFT JOIN NURSE n ON f.NurseID = n.NurseID
          LEFT JOIN INTERACTION i ON f.FeedbackID = i.InteractionID AND i.InteractionType = 'Doctor Feedback'
          LEFT JOIN DOCTOR d ON (i.StaffType = 'Doctor' AND i.Description LIKE CONCAT('%', d.DoctorID, '%'))
          WHERE f.PatientID = ?";

// Add sorting
switch ($sort_by) {
  case 'date_asc':
    $query .= " ORDER BY f.FeedbackDate ASC";
    break;
  case 'rating_desc':
    $query .= " ORDER BY f.Rating DESC";
    break;
  case 'rating_asc':
    $query .= " ORDER BY f.Rating ASC";
    break;
  case 'date_desc':
  default:
    $query .= " ORDER BY f.FeedbackDate DESC";
    break;
}

// Prepare and execute the statement
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

// Function to display star rating
function displayRating($rating)
{
  $stars = '';
  for ($i = 1; $i <= 5; $i++) {
    if ($i <= $rating) {
      $stars .= '★';
    } else {
      $stars .= '☆';
    }
  }
  return $stars;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Feedback - Patient Portal</title>
  <link rel="stylesheet" href="../css/main.css">
  <script src="../js/main.js" defer></script>
  <style>
    .stars {
      color: #FFD700;
      font-size: 18px;
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
  <div class="page-header">Feedback - <?php echo htmlspecialchars($_SESSION['username']); ?></div>
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
        <h1>Feedback History</h1>
        <div class="header-actions">
          <form action="" method="GET" id="filter-form">
            <select name="sort" onchange="document.getElementById('filter-form').submit();">
              <option value="date_desc" <?php if ($sort_by == 'date_desc') echo 'selected'; ?>>Newest First</option>
              <option value="date_asc" <?php if ($sort_by == 'date_asc') echo 'selected'; ?>>Oldest First</option>
              <option value="rating_desc" <?php if ($sort_by == 'rating_desc') echo 'selected'; ?>>Highest Rating</option>
              <option value="rating_asc" <?php if ($sort_by == 'rating_asc') echo 'selected'; ?>>Lowest Rating</option>
            </select>
          </form>
        </div>
      </div>

      <!-- Feedback Table -->
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Staff</th>
            <th>Rating</th>
            <th>Comment</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?php echo date('M d, Y', strtotime($row['FeedbackDate'])); ?></td>
                <td>
                  <?php
                  echo !empty($row['DoctorName']) ? htmlspecialchars($row['DoctorName']) : (!empty($row['NurseName']) ? htmlspecialchars($row['NurseName']) : 'Hospital Staff');
                  ?>
                </td>
                <td>
                  <div class="stars"><?php echo displayRating($row['Rating']); ?></div>
                </td>
                <td><?php echo htmlspecialchars($row['Comments']); ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="4" style="text-align: center;">No feedback history found</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>

      <div class="form-action">
        <a href="submit-feedback.php" class="button">Submit new feedback</a>
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
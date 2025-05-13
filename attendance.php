<?php
session_start();

// Allow access for logged-in users (user, staff, admin)
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

include 'connect.php';

// Set timezone appropriately
date_default_timezone_set('Your/Timezone'); // Replace 'Your/Timezone' with the correct timezone identifier

$message = "";
$today = date("Y-m-d");

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_SESSION['user'];
    $userrole = $_SESSION['userrole'];
    $currentTime = date("H:i:s");

    // Check In Process
    if (isset($_POST['checkin'])) {
        // Check if a record already exists for today
        $query = "SELECT * FROM attendance WHERE username = ? AND date = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $username, $today);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $record = $result->fetch_assoc();
            if (!empty($record['checkin'])) {
                $message = "Error: You have already checked in today at " . htmlspecialchars($record['checkin']) . ".";
            } else {
                // If record exists but checkin is empty, update the checkin time
                $updateQuery = "UPDATE attendance SET checkin = ? WHERE username = ? AND date = ?";
                $stmtUpdate = $conn->prepare($updateQuery);
                $stmtUpdate->bind_param("sss", $currentTime, $username, $today);
                if ($stmtUpdate->execute()) {
                    $message = "Check in time updated successfully to $currentTime.";
                } else {
                    $message = "Error updating check in time. Please try again.";
                }
            }
        } else {
            // Insert a new record with checkin time
            $insertQuery = "INSERT INTO attendance (username, userrole, date, checkin) VALUES (?, ?, ?, ?)";
            $stmtInsert = $conn->prepare($insertQuery);
            $stmtInsert->bind_param("ssss", $username, $userrole, $today, $currentTime);
            if ($stmtInsert->execute()) {
                $message = "Checked in successfully at $currentTime.";
            } else {
                $message = "Error during check in. Please try again.";
            }
        }
    }

    // Check Out Process
    if (isset($_POST['checkout'])) {
        // Check if a record exists for today
        $query = "SELECT * FROM attendance WHERE username = ? AND date = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $username, $today);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $record = $result->fetch_assoc();
            // Validate that the user has checked in
            if (empty($record['checkin'])) {
                $message = "Error: You must check in before checking out.";
            } elseif (!empty($record['checkout'])) {
                $message = "Error: You have already checked out today at " . htmlspecialchars($record['checkout']) . ".";
            } elseif ($currentTime < $record['checkin']) {
                $message = "Error: Checkout time ($currentTime) cannot be before checkin time (" . htmlspecialchars($record['checkin']) . ").";
            } else {
                // Update the record with the checkout time
                $updateQuery = "UPDATE attendance SET checkout = ? WHERE username = ? AND date = ?";
                $stmtUpdate = $conn->prepare($updateQuery);
                $stmtUpdate->bind_param("sss", $currentTime, $username, $today);
                if ($stmtUpdate->execute()) {
                    $message = "Checked out successfully at $currentTime.";
                } else {
                    $message = "Error during check out. Please try again.";
                }
            }
        } else {
            $message = "Error: No check in record found for today. Please check in first.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <!-- Prevent caching via meta tags as well -->
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Expires" content="0" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Attendance - Dashboard</title>
  <link rel="stylesheet" href="attendance.css">
</head>
<body>
  <!-- Professional Fixed Header -->
  <header class="main-header">
    <div class="header-inner">
      <div class="logo">
        <img src="pl1.jfif" alt="Logo">
        <h1>PSP Dashboard</h1>
      </div>
      <nav>
      <ul class="quick-links">
        <li><a href="home.php">Dashboard</a></li>
        <li><a href="profile.php">Profile</a></li>   
        <li><a href="help.php">Help</a></li>
      </ul>
    </nav>
      <div class="user-info">
        <span>Welcome, <?php echo htmlspecialchars($_SESSION['user']); ?></span>
        <a href="login.php?logout=true" class="logout">Logout</a>
      </div>
    </div>
  </header>

  <!-- Main Attendance Content -->
  <main class="main-content">
    <section class="dashboard-section">
      <h2>Attendance Form</h2>
      <?php if ($message): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
      <?php endif; ?>

      <?php
      // Retrieve today's attendance record for the logged-in user
      $username = $_SESSION['user'];
      $query = "SELECT * FROM attendance WHERE username = ? AND date = ?";
      $stmt = $conn->prepare($query);
      $stmt->bind_param("ss", $username, $today);
      $stmt->execute();
      $attendanceResult = $stmt->get_result();
      $attendanceData = $attendanceResult->fetch_assoc();
      ?>

      <p>Date: <?php echo $today; ?></p>
      <p>Check In Time: <?php echo isset($attendanceData['checkin']) ? htmlspecialchars($attendanceData['checkin']) : 'Not checked in'; ?></p>
      <p>Check Out Time: <?php echo isset($attendanceData['checkout']) ? htmlspecialchars($attendanceData['checkout']) : 'Not checked out'; ?></p>

      <form method="post" action="">
        <button type="submit" name="checkin" class="checkin-btn">Check In</button>
        <button type="submit" name="checkout" class="checkout-btn">Check Out</button>
      </form>
    </section>
  </main>
</body>
</html>

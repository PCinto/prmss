<?php
session_start();

// Validate that the logged-in user is a "user"
if (!isset($_SESSION['userrole']) || strtolower($_SESSION['userrole']) !== 'user') {
    header("Location: login.php");
    exit();
}

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include 'connect.php';

// Process logout: unset and destroy session immediately
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// ——— New: Prepare dates for today and yesterday ———
$today     = date("Y-m-d");                                    // e.g. "2025-04-11" :contentReference[oaicite:0]{index=0}
$yesterday = date("Y-m-d", strtotime("yesterday"));            // e.g. "2025-04-10" :contentReference[oaicite:1]{index=1}
$user      = $conn->real_escape_string($_SESSION['user']);

// Fetch today's attendance for this user
$sqlToday  = "SELECT checkin, checkout 
              FROM attendance 
              WHERE username = '$user' AND date = '$today'
              LIMIT 1";
$resToday  = $conn->query($sqlToday);
$rowToday  = ($resToday && $resToday->num_rows) ? $resToday->fetch_assoc() : [];

// Fetch yesterday's attendance for this user
$sqlYest   = "SELECT checkin, checkout 
              FROM attendance 
              WHERE username = '$user' AND date = '$yesterday'
              LIMIT 1";
$resYest   = $conn->query($sqlYest);
$rowYest   = ($resYest && $resYest->num_rows) ? $resYest->fetch_assoc() : [];
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
  <title>Home - User Dashboard</title>
  <link rel="stylesheet" href="admindash.css">
  <style>
    /* Attendance Summary Cards */
    .attendance-summary {
      display: flex;
      gap: 20px;
      margin-bottom: 20px;
    }
    .summary-card {
      flex: 1;
      background: linear-gradient(135deg, #836481, #8496a7);
      color: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .summary-card h3 {
      margin-bottom: 10px;
    }
    .summary-card p {
      font-size: 16px;
      margin: 5px 0;
    }
  </style>
</head>
<body>
  <!-- Professional Fixed Header for Logged-in Users -->
  <header class="main-header">
    <div class="header-inner">
      <div class="logo">
        <img src="logo.png" alt="Logo">
        <h1>Zain</h1>
      </div>
      <nav>
        <ul class="quick-links">
          <li><a href="attendance.php">Attendance</a></li>
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

  <!-- Main Dashboard Content -->
  <main class="main-content">
    
    
    <section class="dashboard-section">
      <h2>Dashboard</h2>
      <p>You are logged in as <strong><?php echo htmlspecialchars($_SESSION['user']); ?></strong></p>
      <p>This is your user dashboard where you can access your account features and monitor your activities.</p>
    </section>

    <!-- New Attendance Summary Section -->
    <section class="attendance-summary">
      <div class="summary-card">
        <h3>Today's Attendance (<?php echo $today; ?>)</h3>
        <p><strong>Check In:</strong>
          <?php echo !empty($rowToday['checkin']) 
            ? htmlspecialchars($rowToday['checkin']) 
            : '-'; ?>
        </p>
        <p><strong>Check Out:</strong>
          <?php echo !empty($rowToday['checkout']) 
            ? htmlspecialchars($rowToday['checkout']) 
            : '-'; ?>
        </p>
      </div>
      <div class="summary-card">
        <h3>Yesterday's Attendance (<?php echo $yesterday; ?>)</h3>
        <p><strong>Check In:</strong>
          <?php echo !empty($rowYest['checkin']) 
            ? htmlspecialchars($rowYest['checkin']) 
            : '-'; ?>
        </p>
        <p><strong>Check Out:</strong>
          <?php echo !empty($rowYest['checkout']) 
            ? htmlspecialchars($rowYest['checkout']) 
            : '-'; ?>
        </p>
      </div>
    </section>

    <section class="interactive-section">
      <div id="greeting" class="greeting"></div>
      <div id="clock" class="clock"></div>
    </section>

    <section class="quick-links-section">
      <h3>Quick Links</h3>
      <ul class="quick-links">
        <li><a href="profile.php">Profile</a></li>      
        <li><a href="attendance.php">Attendance</a></li>
        <li><a href="help.php">Help</a></li>
      </ul>
    </section>

    <section class="activities-section">
      <h3>Recent Activities</h3>
      <ul id="activity-list" class="activity-list">
        <li>No recent activities.</li>
      </ul>
      <button id="loadMore" class="load-more">Load More</button>
    </section>
  </main>

  <script>
    // Dynamic Clock and Greeting
    function updateClock() {
      const now = new Date();
      document.getElementById('clock').textContent = now.toLocaleTimeString();
      const hours = now.getHours();
      let greeting = hours < 12 ? 'Good Morning' 
                    : hours < 18 ? 'Good Afternoon' 
                    : 'Good Evening';
      document.getElementById('greeting').textContent = greeting + ', <?php echo htmlspecialchars($_SESSION['user']); ?>!';
    }
    updateClock();
    setInterval(updateClock, 1000);

    // Simulate loading more activities
    document.getElementById('loadMore')?.addEventListener('click', function() {
      const activityList = document.getElementById('activity-list');
      const newActivity = document.createElement('li');
      newActivity.textContent = 'You have a new notification at ' + new Date().toLocaleTimeString();
      activityList.appendChild(newActivity);
    });
  </script>
</body>
</html>

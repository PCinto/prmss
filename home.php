<?php
session_start();

// Validate that the logged-in user is an officer
if (!isset($_SESSION['userrole']) || strtolower($_SESSION['userrole']) !== 'officer') {
    header("Location: login.php");
    exit();
}

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include 'connect.php';

// Process logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Dates
$today     = date("Y-m-d");
$yesterday = date("Y-m-d", strtotime("yesterday"));
$user      = $conn->real_escape_string($_SESSION['user']);

// Total users count
$resultCount = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
$rowCount    = mysqli_fetch_assoc($resultCount);
$totalUsers  = $rowCount['total'];


// Fetch approved & rejected counts
$approvedCountQ = mysqli_query($conn, "SELECT COUNT(*) AS total FROM approved_cases");
$approvedCount  = mysqli_fetch_assoc($approvedCountQ)['total'];

$rejectedCountQ = mysqli_query($conn, "SELECT COUNT(*) AS total FROM rejected_cases");
$rejectedCount  = mysqli_fetch_assoc($rejectedCountQ)['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <!-- Prevent caching via meta tags -->
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Expires" content="0" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Home - Admin Dashboard</title>
  <link rel="stylesheet" href="home.css">
  <style>
    
  </style>
</head>
<body>
  <!-- Header -->
  <header class="main-header">
    <div class="header-inner">
      <div class="logo">
        <img src="pl1.jfif" alt="Logo">
        <h1>PSP Dashboard</h1>        
      </div>
   <style>
  
</style>

<div class="main-nav">
  <button>Management ▾</button>
  <ul>
    <li><a href="manageuser.php">Manage Users</a></li>
    <li><a href="create_id_card.php">ID Card Management</a></li>
    <li><a href="view_all_cases.php">Management Complaints</a></li>
  </ul>
</div>

<div class="main-nav">
  <button>Cases Management ▾</button>
  <ul>
    <li><a href="cases.php">Add Case</a></li>
    <li><a href="view_all_cases.php">All Cases</a></li>   
  </ul>
</div>

      <div class="user-info">
        <span>Welcome, <?php echo htmlspecialchars($_SESSION['user']); ?></span>
        <a href="?logout=true" class="logout">Logout</a>
      </div>
    </div>
   
    <nav>
      <ul class="quick-links">
         <!-- 
        <li><a href="home.php">Dashboard</a></li>
        <li><a href="profile.php">Profile</a></li>
        <li><a href="settings.php">Settings</a></li>
        <li><a href="manageuser.php">Manage Users</a></li>
        <li><a href="attendance.php">Manage Attendance</a></li>
        <li><a href="manageuser.php">ID Card Management</a></li>
        <li><a href="help.php">Help</a></li>
      </ul>
    </nav>
    -->
  </header>

  <!-- Left Sidebar -->
  <aside class="sidebar">
    <h3></h3>
    <ul class="sidebar-menu">
      <li><a href="home.php">Dashboard</a></li>
      <li><a href="#">Profile</a></li>
      <li><a href="#">Settings</a></li>
      <li><a href="manageuser.php">Manage Users</a></li>
      <li><a href="manageuser.php">ID Card Management</a></li>
    </ul>
  </aside>

  <!-- Right Sidebar -->
   <!-- Right Sidebar -->
  <aside class="sidebar-right">
    <h3>System Stats</h3>
    <div class="stat">
      <h4>Total Users</h4>
      <p><?php echo htmlspecialchars($totalUsers); ?></p>
    </div>
    <!--<div class="stat">
      <h4>Active IDs</h4>
      <p>0</p>
    </div>
-->
    <div class="stat">
      <h4>Pending Cases</h4>
      <p>
        <?php
          // pending = all in main cases table with status='pending'
          $pendingQ = mysqli_query($conn, "SELECT COUNT(*) AS total FROM cases WHERE status='pending'");
          echo mysqli_fetch_assoc($pendingQ)['total'];
        ?>
      </p>
    </div>
    <div class="stat">
      <h4>Approved Cases</h4>
      <p><?php echo htmlspecialchars($approvedCount); ?></p>
    </div>
    <div class="stat">
      <h4>Rejected Cases</h4>
      <p><?php echo htmlspecialchars($rejectedCount); ?></p>
    </div>
  </aside>


  <!-- Main Dashboard Content -->
  <main class="main-content">
    <div class="dashboard-wrapper">
      <div class="main-dashboard-content">
        <div class="dashboard-header">
          <section class="dashboard-section">
            <h2 style="color:#121212">Dashboard</h2>
            <p>You are logged in as <strong><?php echo htmlspecialchars($_SESSION['user']); ?></strong></p>
            <p>This is your admin dashboard where you can access account features and monitor your activities.</p>
          </section>
          <section class="interactive-section">
            <div id="greeting" class="greeting"></div>
            <div id="clock" class="clock"></div>
          </section>
        </div>

        <section class="attendance-summary">
          <div class="summary-card">
            <h3 style="color:#121212">Today's Attendance (<?php echo $today; ?>)</h3>
            <p><strong>Check In:</strong> -</p>
            <p><strong>Check Out:</strong> -</p>
          </div>
          <div class="summary-card">
            <h3 style="color:#121212">Yesterday's Attendance (<?php echo $yesterday; ?>)</h3>
            <p><strong>Check In:</strong> -</p>
            <p><strong>Check Out:</strong> -</p>
          </div>
        </section>

        <section class="activities-section">
          <h3>Recent Activities</h3>
          <ul id="activity-list" class="activity-list">
            <li>No recent activities.</li>
          </ul>
          <button id="loadMore" class="load-more">Load More</button>
        </section>
      </div>
    </div>
  </main>

  <script>
    // Clock & Greeting
    function updateClock() {
      const now = new Date();
      document.getElementById('clock').textContent = now.toLocaleTimeString();
      let hours = now.getHours(), greet = hours<12?'Good Morning':hours<18?'Good Afternoon':'Good Evening';
      document.getElementById('greeting').textContent = greet + ', <?php echo htmlspecialchars($_SESSION['user']); ?>!';
    }
    updateClock(); setInterval(updateClock,1000);

    document.getElementById('loadMore').addEventListener('click', ()=>{
      let li = document.createElement('li');
      li.textContent = 'New notification at ' + new Date().toLocaleTimeString();
      document.getElementById('activity-list').appendChild(li);
    });
  </script>
</body>
</html>

<?php
session_start();

// Validate that the logged-in user is an admin
if (!isset($_SESSION['userrole']) || strtolower($_SESSION['userrole']) !== 'officer') {
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

// Get status message if available
$msg = "";
if (isset($_GET['msg'])) {
    $msg = htmlspecialchars($_GET['msg']);
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
  <title>Settings - Admin Dashboard</title>
  <!-- Inline CSS using the provided styles -->
  <style>
    /* Global Reset */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body, html {
      font-family: Arial, sans-serif;
      background: linear-gradient(135deg, #918190, #adc4d9);
      color: #333;
    }
    
    /* Main Header (Professional Fixed Header) */
    .main-header {
      width: 100%;
      /* background: linear-gradient(135deg, #7d3e79, #38536d); */
      background: linear-gradient(135deg, #121212, #38536d);
      color: #fff;
      padding: 20px 5%;
      position: fixed;
      top: 0;
      left: 0;
      z-index: 1000;
    }
    .header-inner {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .logo {
      display: flex;
      align-items: center;
    }
    .logo img {
      height: 60px;
      margin-right: 10px;
      transition: transform 0.3s ease;
    }
    .logo img:hover {
      transform: scale(1.05);
    }
    .user-info {
      font-size: 16px;
    }
    .user-info .logout {
      color: #fff;
      margin-left: 15px;
      text-decoration: none;
      border: 1px solid #fff;
      padding: 5px 10px;
      border-radius: 4px;
      transition: background 0.3s ease;
    }
    .user-info .logout:hover {
      background: #fff;
      color: #343a40;
    }
    
    /* Quick Links Navigation */
    nav {
      margin-top: 15px;
      text-align: center;
    }
    .quick-links {
      list-style: none;
      display: inline-flex;
      justify-content: center;
    }
    .quick-links li {
      margin: 0 15px;
    }
    .quick-links li a {
      text-decoration: none;
      color:rgb(173, 184, 177);
      font-weight: bold;
      padding: 8px 12px;
      border-radius: 4px;
      transition: background 0.3s ease;
    }
    .quick-links li a:hover {
      background: #dfdddf;
    }
    
    /* Main Content Layout */
    .main-content {
      margin-top: 140px; /* Space for fixed header & quick links */
      width: 90%;
      max-width: 1200px;
      margin-left: auto;
      margin-right: auto;
      padding: 20px;
    }
    
    /* Dashboard Sections */
    .dashboard-section,
    .interactive-section,
    .activities-section {
      background: linear-gradient(135deg, #836481, #8496a7);
      padding: 20px;
      margin-bottom: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .dashboard-section h2 {
      font-size: 28px;
      margin-bottom: 15px;
    }
    .interactive-section .greeting,
    .interactive-section .clock {
      font-size: 25px;
      margin-bottom: 15px;
    }
    
    /* Form Styles */
    form label {
      font-weight: bold;
    }
    form select {
      padding: 8px;
      margin-top: 5px;
    }
    form button {
      padding: 10px 20px;
      background: #28a745;
      color: #fff;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      transition: background 0.3s ease;
      margin-top: 10px;
    }
    form button:hover {
      background: #218838;
    }
    
    /* Quick Toggle Buttons */
    .toggle-buttons a {
      text-decoration: none;
      display: inline-block;
      margin: 10px 15px 10px 0;
      padding: 8px 12px;
      border-radius: 4px;
      color: #fff;
      background: #007bff;
      transition: background 0.3s ease;
    }
    .toggle-buttons a:hover {
      background: #0056b3;
    }
    
    /* Activities Section */
    .activities-section h3 {
      margin-bottom: 10px;
    }
    .activity-list {
      list-style: none;
      padding-left: 0;
    }
    .activity-list li {
      background: #e9ecef;
      padding: 10px;
      margin-bottom: 10px;
      border-radius: 4px;
    }
    .load-more {
      padding: 10px 15px;
      background: #28a745;
      color: #fff;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      transition: background 0.3s ease;
    }
    .load-more:hover {
      background: #218838;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
      .header-inner {
        flex-direction: column;
        text-align: center;
      }
      .user-info {
        margin-top: 10px;
      }
      .quick-links {
        flex-direction: column;
      }
      .quick-links li {
        margin-bottom: 10px;
      }
    }
  </style>
</head>
<body>
  <!-- Professional Fixed Header for Logged-in Users -->
  <header class="main-header">
    <div class="header-inner">
      <div class="logo">
        <img src="pl1.jfif" alt="Logo">
        <h1>PS Police Dashboard</h1>
      </div>
      <div class="user-info">
        <span>Welcome, <?php echo htmlspecialchars($_SESSION['user']); ?></span>
        <a href="settings.php?logout=true" class="logout">Logout</a>
      </div>
    </div>
    <!-- Quick Links Navigation -->
    <nav>
      <ul class="quick-links">
        <li><a href="home.php">Dashboard</a></li>
        <li><a href="profile.php">Profile</a></li>
        <li><a href="settings.php">Settings</a></li>
        <li><a href="manageuser.php">Manage Users</a></li>
        <li><a href="help.php">Help</a></li>
      </ul>
    </nav>
  </header>

  <!-- Main Content Layout -->
  <main class="main-content">
    <section class="dashboard-section">
      <h2>Settings</h2>
      <p>This is the settings page where you can update your account and system preferences.</p>
      <!-- Display status message if available -->
      <?php if (!empty($msg)) { echo "<p style='color: green; font-weight: bold;'>$msg</p>"; } ?>
      
      <!-- Settings Form -->
      <form action="updatesettings.php" method="post">
        <label for="email_notifications">Email Notifications:</label>
        <select name="email_notifications" id="email_notifications">
          <option value="enabled">Enabled</option>
          <option value="disabled">Disabled</option>
        </select>
        <br><br>
        <button type="submit">Save Settings</button>
      </form>
      
      <!-- Quick Toggle Links -->
      <div class="toggle-buttons">
        <a href="enabled.php">Enable Notifications</a>
        <a href="disabled.php">Disable Notifications</a>
      </div>
    </section>

    <section class="interactive-section">
      <div id="greeting" class="greeting"></div>
      <div id="clock" class="clock"></div>
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
      
      // Dynamic greeting based on time of day
      const hours = now.getHours();
      let greetingText = '';
      if (hours < 12) {
        greetingText = 'Good Morning';
      } else if (hours < 18) {
        greetingText = 'Good Afternoon';
      } else {
        greetingText = 'Good Evening';
      }
      const userName = "<?php echo isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']) : ''; ?>";
      document.getElementById('greeting').textContent = greetingText + (userName ? (', ' + userName + '!') : '!');
    }
    updateClock();
    setInterval(updateClock, 1000);

    // Simulate loading more activities
    document.getElementById('loadMore')?.addEventListener('click', function() {
      const activityList = document.getElementById('activity-list');
      const newActivity = document.createElement('li');
      newActivity.textContent = 'New activity at ' + new Date().toLocaleTimeString();
      activityList.appendChild(newActivity);
    });
  </script>
</body>
</html>

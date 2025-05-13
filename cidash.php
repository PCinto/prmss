<?php
session_start();

// Validate that the logged-in user is an officer
if (!isset($_SESSION['userrole']) || strtolower($_SESSION['userrole']) !== 'cid') {
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
  <title>Home - Officer Dashboard</title>
  <link rel="stylesheet" href="admindash.css">
  <style>
    /* Global Reset */
    * { margin:0; padding:0; box-sizing:border-box; }
    body, html {
      font-family: Arial, sans-serif;
      background: linear-gradient(135deg, #918190, #adc4d9);
      color: #333;
    }
    /* Header */
    .main-header {
      width:100%; background: linear-gradient(135deg, #121212, #38536d);
      color:#fff; padding:15px 5%; position:fixed; top:0; left:0; z-index:1000;
    }
    .header-inner { display:flex; justify-content:space-between; align-items:center; }
    .logo { display:flex; align-items:center; }
    .logo img { height:60px; margin-right:10px; transition:transform 0.3s ease; }
    .logo img:hover { transform:scale(1.05); }
    .user-info { font-size:16px; }
    .user-info .logout {
      color:#fff; margin-left:15px; text-decoration:none;
      border:1px solid #fff; padding:5px 10px; border-radius:4px;
      transition:background 0.3s ease;
    }
    .user-info .logout:hover { background:#fff; color:#343a40; }

    /* Left Sidebar */
    .sidebar {
      width:190px; position:fixed; top:0; left:0; bottom:0;
      background:#2C3E50; overflow-y:auto; padding-top:100px;
      box-shadow:2px 0 5px rgba(0,0,0,0.1);
    }
    .sidebar h3 {
      color:#ECF0F1; text-align:center; margin-bottom:15px; font-size:1.1em;
    }
    .sidebar-menu { list-style:none; padding:0; }
    .sidebar-menu li { margin:0; }
    .sidebar-menu li a {
      display:flex; align-items:center;
      padding:12px 16px; color:#ECF0F1; text-decoration:none;
      font-size:0.95em; border-left:4px solid transparent;
      transition:background 0.2s ease, border-left-color 0.2s ease;
    }
    .sidebar-menu li a:hover {
      background:#34495E; border-left-color:#1ABC9C; color:#fff;
    }
    .sidebar-menu li a.active {
      background:#1ABC9C; border-left-color:#16A085; color:#fff;
    }

    /* Right Sidebar */
    .sidebar-right {
      width:190px; position:fixed; top:100px; right:0; bottom:0;
      background:#2C3E50; color:#ECF0F1; padding:20px;
      box-shadow:-2px 0 5px rgba(0,0,0,0.1); overflow-y:auto;
    }
    .sidebar-right h3 {
      text-align:center; margin-bottom:15px; font-size:1.1em;
    }
    .sidebar-right .stat {
      background:#34495E; padding:15px; border-radius:6px;
      margin-bottom:15px; text-align:center;
    }
    .sidebar-right .stat h4 {
      margin:0 0 5px; font-size:1em; color:#1ABC9C;
    }
    .sidebar-right .stat p {
      margin:0; font-size:1.5em; font-weight:bold;
    }

    /* Main Content */
    /* Replace your existing .main-content rules with: */
.main-content {
  /* push down below header */
  margin-top: 100px;
  /* no more left/right margins */
  margin-left: 0;
  margin-right: 0;
  /* inset content by sidebar widths */
  padding: 20px 200px 20px 200px; 
  /* allow full width behind those paddings */
  width: 100%;
  box-sizing: border-box;
}

/* Remove any width or margin adjustments on .dashboard-wrapper */
.dashboard-wrapper {
  display: flex;
  gap: 20px;
  /* ensure it fills the padded area */
  width: 100%;
}

/* Ensure main-dashboard-content grows to fill the space */
.main-dashboard-content {
  flex: 1;
}


    /* Dashboard Layout */
    .dashboard-wrapper { display:flex; gap:20px; }
    .main-dashboard-content { flex:1; }

    /* Dashboard Header */
    .dashboard-header { display:flex; gap:20px; margin-bottom:20px; }
    .dashboard-header > section { flex:1; color:white; }

    /* Attendance Summary */
    .attendance-summary { display:flex; gap:20px; margin-bottom:20px; }
    .summary-card {
      flex:1; background:linear-gradient(135deg, #836481, #8496a7);
      color:#fff; padding:20px; border-radius:8px;
      box-shadow:0 2px 8px rgba(0,0,0,0.1);
    }
    .summary-card h3 { margin-bottom:10px; }
    .summary-card p  { font-size:16px; margin:5px 0; }
    
    /* Navigation Styling */
.main-nav ul {
    list-style: none;
    display: flex;
    gap: 20px;
}

.main-nav ul li a {
    color: #fff;
    text-decoration: none;
    font-size: 16px;
    padding: 10px;
    transition: color 0.3s ease, text-decoration 0.3s ease;
}

.main-nav ul li a:hover {
    text-decoration: underline;
}
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
  /* Container */
  .main-nav {
    position: relative;
    display: inline-block;
  }

  /* Toggle button styling */
  .main-nav > button {
    background: #2f4f4f;
    color: #fff;
    padding: 8px 12px;
    border: none;
    cursor: pointer;
    font-size: 1em;
    border-radius: 4px;
  }

  /* Dropdown menu */
  .main-nav ul {
    list-style: none;
    margin: 0;
    padding: 0;
    position: absolute;
    top: 100%;
    left: 0;
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 4px;
    display: none;            /* hidden by default */
    min-width: 180px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    z-index: 100;
  }

  /* Show on hover */
  .main-nav:hover ul {
    display: block;
  }

  .main-nav ul li {
    border-bottom: 1px solid #eee;
  }
  .main-nav ul li:last-child {
    border-bottom: none;
  }

  .main-nav ul li a {
    display: block;
    padding: 8px 12px;
    color: #333;
    text-decoration: none;
  }
  .main-nav ul li a:hover {
    background: #f0f0f0;
  }
</style>

<!--

<div class="main-nav">
  <button>Management ▾</button>
  <ul>
    <li><a href="manageuser.php">Manage Users</a></li>
    <li><a href="create_id_card.php">ID Card Management</a></li>
    <li><a href="complaints.php">Management Complaints</a></li>
  </ul>
</div>

<div class="main-nav">
  <button>Cases Management ▾</button>
  <ul>
    <li><a href="cases.php">Add Case</a></li>
    <li><a href="file_case.php">View Approved Cases</a></li>   
  </ul>
</div>
-->
      <div class="user-info">
        <span>Welcome, <?php echo htmlspecialchars($_SESSION['user']); ?></span>
        <a href="?logout=true" class="logout">Logout</a>
      </div>
    </div>
   
    <nav>
      <ul class="quick-links">         
         <!-- 
      <li><a href="file_case.php">View Approved Cases</a></li> 
        <li><a href="home.php">Dashboard</a></li>
        <li><a href="profile.php">Profile</a></li>
        <li><a href="settings.php">Settings</a></li>
        <li><a href="manageuser.php">Manage Users</a></li>
        <li><a href="attendance.php">Manage Attendance</a></li>
        <li><a href="manageuser.php">ID Card Management</a></li>
        <li><a href="help.php">Help</a></li>
         -->
      </ul>
    </nav>
   
  </header>

  <!-- Left Sidebar -->
  <aside class="sidebar">
    <h3></h3>
    <ul class="sidebar-menu">
      <li><a href="cidash.php">Dashboard</a></li>
      <li><a href="profile.php">Profile</a></li>
      <li><a href="settings.php">Settings</a></li>   
       <li><a href="file_case.php">File a Case</a></li>  
    </ul>
  </aside>

  <!-- Right Sidebar -->
  <aside class="sidebar-right">
    <h3>System Stats</h3>
    <div class="stat">
      <h4>Total Users</h4>
      <p><?php echo htmlspecialchars($totalUsers); ?></p>
    </div>
    <div class="stat">
      <h4>Active IDs</h4>
      <p>0</p>
    </div>
      <div class="stat">
      <h4>Pending Complaints</h4>
      <p>17</p>
    </div>
      <div class="stat">
      <h4>Pending Cases</h4>
      <p>27</p>
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

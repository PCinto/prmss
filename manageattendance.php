<?php
session_start();
// (Assume admin‑only access is already enforced)
include 'connect.php';

// Fetch all attendance records (including the primary key `id`)
$query = "SELECT id, username, userrole, date, checkin, checkout 
          FROM attendance 
          ORDER BY date DESC, username";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <!-- Prevent caching -->
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Expires" content="0" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Attendance - Dashboard</title>
  <link rel="stylesheet" href="attendance.css">
  <style>
    /* Additional styles for the search bar */
    .search-container {
      text-align: right;
      margin-bottom: 10px;
    }
    .search-container input[type="text"] {
      border: 1px solid #ccc;
      border-radius: 20px;
      padding: 8px 15px;
      width: 300px;
      outline: none;
    }
    /* Table styling */
    table.attendance-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    table.attendance-table th,
    table.attendance-table td {
      border: 1px solid #ccc;
      padding: 12px;
      text-align: left;
    }
    table.attendance-table th {
      background: linear-gradient(135deg, #7d3e79, #38536d);
      color: #fff;
      cursor: pointer; /* indicate clickable for sorting */
    }
    table.attendance-table tr:nth-child(even) {
      background: rgba(255, 255, 255, 0.1);
    }
    /* Delete button */
    .btn-delete {
      background: #c0392b;
      color: #fff;
      border: none;
      padding: 6px 12px;
      border-radius: 4px;
      cursor: pointer;
    }
    /* Link next to heading */
    .heading-link {
      font-size: 0.9em;
      margin-left: 15px;
      text-decoration: none;
      color: #38536d;
      border: 1px solid #38536d;
      padding: 4px 8px;
      border-radius: 4px;
      transition: background 0.2s, color 0.2s;
    }
    .heading-link:hover {
      background: #38536d;
      color: #fff;
    }
  </style>
</head>
<body>
  <!-- Fixed Header -->
  <header class="main-header">
    <div class="header-inner">
      <div class="logo">
        <img src="logo.png" alt="Logo">
        <h1>Zain</h1>
      </div>
      <nav>
        <ul class="quick-links">
          <li><a href="home.php">Dashboard</a></li>
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

  <!-- Main Content -->
  <main class="main-content">
    <section class="dashboard-section">
      <h2>
        Manage Attendance Logs
        <a href="viewdeletedattendance.php" class="heading-link">View Deleted Attendance</a>
      </h2>
      
      <!-- Search bar container (positioned at the top right) -->
      <div class="search-container">
        <input type="text" id="searchInput" placeholder="Search by name, role, date, time..." onkeyup="filterTable()">
      </div>
      
      <!-- Attendance records table -->
      <table class="attendance-table" id="attendanceTable">
        <thead>
          <tr>
            <th onclick="sortTable(0)">Username</th>
            <th onclick="sortTable(1)">Role</th>
            <th onclick="sortTable(2)">Date</th>
            <th onclick="sortTable(3)">Check In</th>
            <th onclick="sortTable(4)">Check Out</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?php echo htmlspecialchars($row['username']); ?></td>
                <td><?php echo htmlspecialchars($row['userrole']); ?></td>
                <td><?php echo htmlspecialchars($row['date']); ?></td>
                <td><?php echo $row['checkin'] ? htmlspecialchars($row['checkin']) : '-'; ?></td>
                <td><?php echo $row['checkout'] ? htmlspecialchars($row['checkout']) : '-'; ?></td>
                <td>
                  <form method="POST" action="deleteattendance.php"
                        onsubmit="return confirm('Are you sure you want to delete this record?');">
                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                    <button type="submit" class="btn-delete">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="6">No attendance records found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </section>
  </main>
  
  <!-- JavaScript for search filtering and table sorting -->
  <script>
    // Filter table rows based on search query
    function filterTable() {
      var input = document.getElementById("searchInput");
      var filter = input.value.toLowerCase();
      var table = document.getElementById("attendanceTable");
      var tr = table.getElementsByTagName("tr");
      for (var i = 1; i < tr.length; i++) {
        var rowText = "";
        var td = tr[i].getElementsByTagName("td");
        for (var j = 0; j < td.length; j++) {
          rowText += td[j].textContent.toLowerCase() + " ";
        }
        tr[i].style.display = rowText.indexOf(filter) > -1 ? "" : "none";
      }
    }

    // Sort table rows when clicking on a header column
    function sortTable(n) {
      var table = document.getElementById("attendanceTable"),
          rows = table.rows,
          switching = true,
          dir = "asc",
          switchcount = 0;
      while (switching) {
        switching = false;
        for (var i = 1; i < rows.length - 1; i++) {
          var shouldSwitch = false,
              x = rows[i].getElementsByTagName("td")[n],
              y = rows[i+1].getElementsByTagName("td")[n];
          if ((dir === "asc" && x.textContent.toLowerCase() > y.textContent.toLowerCase()) ||
              (dir === "desc" && x.textContent.toLowerCase() < y.textContent.toLowerCase())) {
            shouldSwitch = true;
            break;
          }
        }
        if (shouldSwitch) {
          rows[i].parentNode.insertBefore(rows[i+1], rows[i]);
          switching = true;
          switchcount++;
        } else if (switchcount === 0 && dir === "asc") {
          dir = "desc";
          switching = true;
        }
      }
    }
  </script>
</body>
</html>

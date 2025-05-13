<?php
session_start();
// (Assume admin‑only access is already enforced)
include 'connect.php';

// Fetch all deleted attendance records, most recent deletions first
$query = "
  SELECT 
    deletion_id, attendance_id, username, userrole, date, checkin, checkout, deleted_at, deleted_by
  FROM deletedattendance
  ORDER BY deleted_at DESC, username
";
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
  <title>Deleted Attendance Log</title>
  <link rel="stylesheet" href="attendance.css">
  <style>
    /* Search bar styling */
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

    /* Table styling (same as active attendance) */
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
      cursor: pointer;
    }
    table.attendance-table tr:nth-child(even) {
      background: rgba(255, 255, 255, 0.1);
    }

    /* Back link styling */
    .back-link {
      display: inline-block;
      margin-bottom: 15px;
      color: #38536d;
      text-decoration: none;
      font-weight: bold;
    }
    .back-link:hover {
      text-decoration: underline;
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
          <li><a href="manageattendance.php">Back to Manage</a></li>
          <li><a href="home.php">Dashboard</a></li>
        </ul>
      </nav>
      <div class="user-info">
        <span>Welcome, <?= htmlspecialchars($_SESSION['user']); ?></span>
        <a href="login.php?logout=true" class="logout">Logout</a>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <main class="main-content">
    <section class="dashboard-section">
      <h2>Deleted Attendance Log</h2>
      <a href="manageattendance.php" class="back-link">&larr; Back to Active Records</a>

      <!-- Search bar -->
      <div class="search-container">
        <input type="text" id="searchInput" placeholder="Search deleted records..." onkeyup="filterTable()">
      </div>

      <!-- Deleted attendance table -->
      <table class="attendance-table" id="deletedTable">
        <thead>
          <tr>
            <th onclick="sortTable(0)">Username</th>
            <th onclick="sortTable(1)">Role</th>
            <th onclick="sortTable(2)">Date</th>
            <th onclick="sortTable(3)">Check In</th>
            <th onclick="sortTable(4)">Check Out</th>
            <th onclick="sortTable(5)">Deleted At</th>
            <th onclick="sortTable(6)">Deleted By</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($row['username']); ?></td>
                <td><?= htmlspecialchars($row['userrole']); ?></td>
                <td><?= htmlspecialchars($row['date']); ?></td>
                <td><?= $row['checkin'] ? htmlspecialchars($row['checkin']) : '-'; ?></td>
                <td><?= $row['checkout'] ? htmlspecialchars($row['checkout']) : '-'; ?></td>
                <td><?= htmlspecialchars($row['deleted_at']); ?></td>
                <td><?= htmlspecialchars($row['deleted_by']); ?> - <?= htmlspecialchars($row['userrole']); ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="7">No deleted attendance records found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </section>
  </main>

  <!-- JS for filtering and sorting -->
  <script>
    function filterTable() {
      var input = document.getElementById("searchInput"),
          filter = input.value.toLowerCase(),
          table = document.getElementById("deletedTable"),
          tr = table.getElementsByTagName("tr");
      for (var i = 1; i < tr.length; i++) {
        var rowText = "",
            td = tr[i].getElementsByTagName("td");
        for (var j = 0; j < td.length; j++) {
          rowText += td[j].textContent.toLowerCase() + " ";
        }
        tr[i].style.display = rowText.indexOf(filter) > -1 ? "" : "none";
      }
    }

    function sortTable(n) {
      var table = document.getElementById("deletedTable"),
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
          if ((dir==="asc" && x.textContent.toLowerCase()>y.textContent.toLowerCase()) ||
              (dir==="desc"&& x.textContent.toLowerCase()<y.textContent.toLowerCase())) {
            shouldSwitch = true;
            break;
          }
        }
        if (shouldSwitch) {
          rows[i].parentNode.insertBefore(rows[i+1], rows[i]);
          switching = true;
          switchcount++;
        } else if (switchcount===0 && dir==="asc") {
          dir = "desc";
          switching = true;
        }
      }
    }
  </script>
</body>
</html>

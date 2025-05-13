<?php
session_start();
include 'connect.php';

// Only officers can access
if (!isset($_SESSION['userrole']) || strtolower($_SESSION['userrole']) !== 'officer') {
    header("Location: login.php");
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Ensure we have a user_id in session (fallback to lookup by username)
if (empty($_SESSION['user_id']) && !empty($_SESSION['user'])) {
    $safeUser = mysqli_real_escape_string($conn, $_SESSION['user']);
    $res = mysqli_query($conn, "SELECT user_id FROM users WHERE username = '$safeUser' LIMIT 1");
    if ($res && mysqli_num_rows($res) === 1) {
        $_SESSION['user_id'] = mysqli_fetch_assoc($res)['user_id'];
    }
}

// Handle new case submission
$case_message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file_case'])) {
    $title       = mysqli_real_escape_string($conn, $_POST['title']);
    $victim           = mysqli_real_escape_string($conn, $_POST['victim']);
    $victim_age       = mysqli_real_escape_string($conn, $_POST['victim_age']);
    $perpetrator      = mysqli_real_escape_string($conn, $_POST['perpetrator']);
    $perpetrator_age  = mysqli_real_escape_string($conn, $_POST['perpetrator_age']);
    $location  = mysqli_real_escape_string($conn, $_POST['location']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $user_id     = intval($_SESSION['user_id']); // now guaranteed

    $ins = "
      INSERT INTO cases (filed_by_user_id, title, description, victim, victim_age, perpetrator,
        perpetrator_age,
        location,)
      VALUES ($user_id, '$title', '$victim',
        '$victim_age',
        '$perpetrator',
        '$perpetrator_age',
        '$location', '$description')
    ";
    if (mysqli_query($conn, $ins)) {
        $case_message = "Case filed successfully.";
    } else {
        $case_message = "Error filing case: " . mysqli_error($conn);
    }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['case_id'])) {
    $cid      = intval($_POST['case_id']);
    $status   = mysqli_real_escape_string($conn, $_POST['status']);
    $feedback = mysqli_real_escape_string($conn, $_POST['feedback']);
    $upd = "
      UPDATE cases
      SET status='$status', feedback='$feedback', updated_at=NOW()
      WHERE case_id=$cid
    ";
    mysqli_query($conn, $upd);
}

// Fetch all cases
$sql = "
  SELECT c.*, u.username
  FROM cases c
  JOIN users u ON c.filed_by_user_id = u.user_id
  ORDER BY c.created_at DESC
";
$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Error fetching cases: " . mysqli_error($conn));
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Cases Management</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="admindash.css">
  <style>
    /* (same CSS you use for dashboard, sidebars, etc.) */
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
      width:120px; position:fixed; top:0; left:0; bottom:0;
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
      width:10px; position:fixed; top:90px; right:0; bottom:0;
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

    /* Case form & table */
    .case-container {
      background: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      margin-bottom: 30px;
    }
    .case-container h2 {
      margin-bottom: 15px;
    }
    .case-container label {
      display:block; margin-top:10px; font-weight:bold;
    }
    .case-container input, .case-container textarea {
      width:100%; padding:10px; margin-top:5px;
      border:1px solid #ccc; border-radius:4px;
    }
    .case-container button {
      margin-top:15px; padding:10px 20px;
      background:#1ABC9C; color:#fff; border:none; border-radius:4px;
      cursor:pointer;
    }
    .cases-table {
      width:100%; border-collapse:collapse;
      background:#fff; border-radius:8px; overflow:hidden;
      box-shadow:0 4px 12px rgba(0,0,0,0.1);
    }
    .cases-table th, .cases-table td {
      padding:12px; border-bottom:1px solid #e2e8f0;
    }
    .cases-table th {
      background:#2F4F4F; color:#FFF; text-transform:uppercase;
    }
    .cases-table tr:hover td {
      background:#f7fafc;
    }
    .cases-table .action-links a {
      margin-right:8px; padding:6px 10px;
      color:#fff; text-decoration:none; border-radius:4px;
      font-size:0.85em;
    }
    .cases-table .edit-link   { background:#28a745; }
    .cases-table .delete-link { background:#dc3545; }
    .cases-table .view-link   { background:#007bff; }

    /* Tighter, fixed-layout cases table */
.cases-table {
  table-layout: fixed;          /* Force fixed column widths */
  width: 100%;                  /* Fill container */
  font-size: 0.85em;            /* Slightly smaller text */
}

/* Define proportional column widths */
.cases-table th:nth-child(1),
.cases-table td:nth-child(1) { width: 7%; }   /* ID */
.cases-table th:nth-child(2),
.cases-table td:nth-child(2) { width: 12%; }  /* Filed By */
.cases-table th:nth-child(3),
.cases-table td:nth-child(3) { width: 17%; }  /* Title */
.cases-table th:nth-child(4),
.cases-table td:nth-child(4) { width: 12%; }  /* Status */
.cases-table th:nth-child(5),
.cases-table td:nth-child(5) { width: 12%; }  /* Victim */
.cases-table th:nth-child(6),
.cases-table td:nth-child(6) { width: 9%; }   /* Victim Age */
.cases-table th:nth-child(7),
.cases-table td:nth-child(7) { width: 12%; }  /* Perpetrator */
.cases-table th:nth-child(8),
.cases-table td:nth-child(8) { width: 10%; }   /* Perpetrator Age */
.cases-table th:nth-child(9),
.cases-table td:nth-child(9) { width: 17%; }  /* Location */
.cases-table th:nth-child(10),
.cases-table td:nth-child(10) { width: 12%; } /* Feedback */
.cases-table th:nth-child(11),
.cases-table td:nth-child(11) { width: 30%; } /* Actions */

/* Wrap long text within cells */
.cases-table th,
.cases-table td {
  white-space: normal;   /* Allow line breaks */
  overflow-wrap: break-word;
  padding: 8px;          /* Slightly less padding */
}

/* Ensure no scrollbars appear—shrink rows if needed */
.cases-table tbody tr {
  height: auto;
}

  </style>
</head>
<body>
  <?php include 'header.php'; ?>

  <aside class="sidebar">
    <!-- your sidebar menu -->
  </aside>
  <aside class="sidebar-right">
    <!-- your right stats -->
  </aside>

  <main class="main-content">
    <!-- File New Case -->
    <div class="case-container">
      <?php if ($case_message): ?>
        <div class="message"><?= htmlspecialchars($case_message) ?></div>
      <?php endif; ?>
      <h2>File a New Case</h2>
      <form method="POST">
        <label for="title">Title</label>
        <input name="title" id="title" required>

         <label for="victim">Victim</label>
        <textarea name="victim" id="victim" rows="1" required></textarea>

        <label for="victim_age">Victim Age</label>
        <input name="victim_age" id="victim_age" required>

        <label for="perpetrator">Perpetrator</label>
        <textarea name="perpetrator" id="perpetrator" rows="1" required></textarea>

        <label for="perpetrator_age">Perpetrator Age</label>
        <input name="perpetrator_age" id="perpetrator_age" required>

        <label for="location">Location</label>
        <input name="location" id="location" required>

        <label for="description">Description</label>
        <textarea name="description" id="description" rows="4" required></textarea>

        <button type="submit" name="file_case">Submit Case</button>
      </form>
    </div>

    <!-- Review & Update Existing Cases -->
    <table class="cases-table">
      <thead>
        <tr>
          <th>ID</th><th>Filed By</th><th>Title</th>
          <th>Status</th><th>Victim</th>
          <th>Victim Age</th>
          <th>Perpetrator</th>
          <th>Perpetrator Age</th>
          <th>Location</th><th>Feedback</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
          <tr>
            <td><?= $row['case_id'] ?></td>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= ucfirst($row['status']) ?></td>
            <td><?= htmlspecialchars($row['victim']) ?></td>
            <td><?= htmlspecialchars($row['victim_age']) ?></td>
            <td><?= htmlspecialchars($row['perpetrator']) ?></td>
            <td><?= htmlspecialchars($row['perpetrator_age']) ?></td>
            <td><?= htmlspecialchars($row['location']) ?></td>
            <td><?= nl2br(htmlspecialchars($row['feedback'])) ?></td>
            <td class="action-links">
              <form method="POST" style="display:inline">
                <input type="hidden" name="case_id" value="<?= $row['case_id'] ?>">
                <select name="status">
                  <?php foreach (['pending','approved','rejected'] as $st): ?>
                    <option value="<?= $st ?>" <?= $row['status']=== $st ? 'selected':'' ?>>
                      <?= ucfirst($st) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <textarea name="feedback" placeholder="Feedback"><?= htmlspecialchars($row['feedback']) ?></textarea>
                <button type="submit">Update</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </main>

  <script>
    // clock & greeting scripts…
  </script>
</body>
</html>

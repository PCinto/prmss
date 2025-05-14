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

// Build search & filter conditions
$whereClauses = [];
$params       = [];

// Full-text search fields
if (!empty($_GET['q'])) {
    $q = '%'. mysqli_real_escape_string($conn, $_GET['q']) .'%';
    $whereClauses[] = "(c.title LIKE ? OR c.victim LIKE ? OR c.perpetrator LIKE ? OR c.location LIKE ? OR c.victim_age LIKE ? OR c.perpetrator_age LIKE ?)";
    for ($i=0; $i<6; $i++) {
        $params[] = $q;
    }
}

// Date range filter
if (!empty($_GET['from_date']) && !empty($_GET['to_date'])) {
    $from = mysqli_real_escape_string($conn, $_GET['from_date']);
    $to   = mysqli_real_escape_string($conn, $_GET['to_date']);
    $whereClauses[] = "c.created_at BETWEEN ? AND ?";
    $params[] = $from . ' 00:00:00';
    $params[] = $to   . ' 23:59:59';
}

// Status filter
if (!empty($_GET['status']) && in_array($_GET['status'], ['pending','approved','rejected'])) {
    $whereClauses[] = "c.status = ?";
    $params[] = $_GET['status'];
}

// Combine WHERE
$whereSQL = '';
if ($whereClauses) {
    $whereSQL = 'WHERE ' . implode(' AND ', $whereClauses);
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['case_id'])) {
    $cid      = intval($_POST['case_id']);
    $status   = mysqli_real_escape_string($conn, $_POST['status']);
    $feedback = mysqli_real_escape_string($conn, $_POST['feedback']);

    // 1) Update the main cases table
    $upd = "
      UPDATE cases
      SET status     = '$status',
          feedback   = '$feedback',
          updated_at = NOW()
      WHERE case_id = $cid
    ";
    mysqli_query($conn, $upd);

    // 2) Archive into approved_cases or rejected_cases
    if (in_array($status, ['approved','rejected'])) {
        // Fetch the just-updated row
        $rowQ = mysqli_query($conn, "SELECT * FROM cases WHERE case_id = $cid LIMIT 1");
        if ($row = mysqli_fetch_assoc($rowQ)) {
            // Decide which archive table to insert into
            $archiveTable = $status === 'approved'
                          ? 'approved_cases'
                          : 'rejected_cases';
            // And which opposite table to clean out
            $otherTable   = $status === 'approved'
                          ? 'rejected_cases'
                          : 'approved_cases';

            // Copy all columns into the proper archive
            $cols = [
              'case_id','filed_by_user_id','title','description',
              'victim','victim_age','perpetrator','perpetrator_age',
              'location','status','feedback','created_at','updated_at'
            ];
            $colList = implode(',', $cols);
            $valList = implode(
              ',',
              array_map(function($c) use($row,$conn) {
                return "'" . mysqli_real_escape_string($conn, $row[$c]) . "'";
              }, $cols)
            );
            $ins = "REPLACE INTO `$archiveTable` ($colList) VALUES ($valList)";
            mysqli_query($conn, $ins);

            // Remove from the opposite archive, if it exists there
            mysqli_query($conn, "DELETE FROM `$otherTable` WHERE case_id = $cid");
        }
    }

    // 3) Redirect back, preserving filters
    header("Location: view_all_cases.php?" . $_SERVER['QUERY_STRING']);
    exit();
}


// Prepare and execute main query
$sql = "
  SELECT c.*, u.username
  FROM cases c
  JOIN users u ON c.filed_by_user_id = u.user_id
  $whereSQL
  ORDER BY c.created_at DESC
";
$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    $types = str_repeat('s', count($params));
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>All Cases</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
  /* Core reset + typography */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}
body, html {
  font-family: Arial, sans-serif;
  background: #f4f6f8;
  color: #333;
  line-height: 1.4;
  font-size: 14px;
}

/* Main container */
.main-content {
  padding: 100px 20px 20px; /* account for header */
  max-width: 1280px;
  margin: 0 auto;
}

/* Filter bar container */
.filter-bar {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  margin-bottom: 1.5rem;
}

/* Text input */
.filter-bar input[type="text"] {
  flex: 1;
  padding: 0.6rem 1rem;
  border: 1px solid #ccc;
  border-radius: 4px;
  font-size: 1rem;
  transition: border-color 0.2s ease;
  max-width: 800px;
}
.filter-bar input[type="text"]:focus {
  border-color: #1ABC9C;
  outline: none;
}

/* Search button */
.filter-bar button {
  padding: 0.6rem 1.2rem;
  background-color: rgb(43, 41, 97);
  color: #fff;
  border: none;
  border-radius: 4px;
  font-size: 1rem;
  cursor: pointer;
  transition: background-color 0.2s ease;
}
.filter-bar button:hover {
  background-color: #16A085;
}

/* Reset link styled as button */
.filter-bar a {
  display: inline-block;
  padding: 0.6rem 1.2rem;
  background-color: #e74c3c; /* reset color */
  color: #fff;
  text-decoration: none;
  border-radius: 4px;
  font-size: 1rem;
  margin-left: 0.5rem;
  transition: background-color 0.2s ease;
}
.filter-bar a:hover {
  background-color: #c0392b;
}

/* Unified table styling with darker grid lines */
.cases-table {
  width: 100%;
  border-collapse: collapse;
  background: #fff;
  box-shadow: 0 1px 4px rgba(0,0,0,0.1);
  border: 1px solid #bbb; /* outer border */
  border-radius: 4px;
  overflow: hidden;
  margin-top: 16px;
}
.cases-table th,
.cases-table td {
  padding: 6px 8px;
  text-align: left;
  border-bottom: 1px solid #bbb; /* horizontal lines */
  border-right: 1px solid #bbb;  /* vertical lines */
  font-size: 0.9em;
  vertical-align: middle;
  background-clip: padding-box;    /* for rounded corner cells */
}
.cases-table th {
  background: #2F4F4F;
  color: #fff;
  font-weight: 600;
  text-transform: uppercase;
}
/* remove right border on last column */
.cases-table th:last-child,
.cases-table td:last-child {
  border-right: none;
}
.cases-table tr:nth-child(even) {
  background: #fafafa;
}
.cases-table tr:hover {
  background: rgb(225, 232, 248);
}

/* Compact action form */
.action-links select,
.action-links textarea,
.action-links button {
  width: 100%;
  margin-top: 4px;
  margin-bottom: 4px;
  font-size: 0.8em;
  padding: 4px 6px;
  border-radius: 3px;
  border: 1px solid #ccc;
}
.action-links button {
  background: #28a745;
  color: #fff;
  border: none;
  cursor: pointer;
}
.action-links button:hover {
  background: #218838;
}

  </style>
</head>
<body>
  <?php include 'oheader.php'; ?>
  <aside class="sidebar">
    <!-- sidebar menu -->
  </aside>
  <main class="main-content">
    <h1>View All Cases</h1>
   <form method="GET" class="filter-bar">
  <input
    type="text"
    name="q"
    placeholder="Search cases by title, victim, perp, location, age, date or status..."
    value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
    style="flex:1; min-width:200px;"
  >
  <button type="submit">Search</button>
  <a href="view_all_cases.php">Reset</a>
</form>


    <table class="cases-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Filed By</th>
          <th>Date</th>
          <th>Title</th>
          <th>Status</th>
          <th>Victim</th>
          <th>V. Age</th>
          <th>Perpetrator</th>
          <th>P. Age</th>
          <th>Location</th>
          <th>Feedback</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <tr>
          <td><?= $row['case_id'] ?></td>
          <td><?= htmlspecialchars($row['username']) ?></td>
          <td><?= htmlspecialchars(substr($row['created_at'],0,10)) ?></td>
          <td><?= htmlspecialchars($row['title']) ?></td>
          <td><?= ucfirst($row['status']) ?></td>
          <td><?= htmlspecialchars($row['victim']) ?></td>
          <td><?= htmlspecialchars($row['victim_age']) ?></td>
          <td><?= htmlspecialchars($row['perpetrator']) ?></td>
          <td><?= htmlspecialchars($row['perpetrator_age']) ?></td>
          <td><?= htmlspecialchars($row['location']) ?></td>
          <td><?= nl2br(htmlspecialchars($row['feedback'])) ?></td>
          <td class="action-links">
            <form method="POST" style="display:flex; flex-direction:column; gap:6px">
              <input type="hidden" name="case_id" value="<?= $row['case_id'] ?>">
              <select name="status">
                <?php foreach (['pending','approved','rejected'] as $st): ?>
                  <option value="<?= $st ?>" <?= $row['status']=== $st ? 'selected':'' ?>>
                    <?= ucfirst($st) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <textarea name="feedback" rows="2"><?= htmlspecialchars($row['feedback']) ?></textarea>
              <button type="submit">Update</button>
            </form>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </main>
</body>
</html>

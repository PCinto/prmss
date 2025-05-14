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

// Build search condition
$whereClauses = [];
$params       = [];

// Single search input
if (!empty($_GET['q'])) {
    $q = '%'. mysqli_real_escape_string($conn, $_GET['q']) .'%';
    $fields = ['title','victim','perpetrator','location','victim_age','perpetrator_age'];
    $criteria = [];
    foreach ($fields as $f) {
        $criteria[] = "$f LIKE ?";
        $params[]  = $q;
    }
    $whereClauses[] = '(' . implode(' OR ', $criteria) . ')';
}
$whereSQL = $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// Handle feedback update
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['case_id'])) {
    $cid      = intval($_POST['case_id']);
    $feedback = mysqli_real_escape_string($conn, $_POST['feedback']);
    mysqli_query($conn, "
      UPDATE rejected_cases
      SET feedback   = '$feedback',
          updated_at = NOW()
      WHERE case_id = $cid
    ");
    header("Location: view_rejected_cases.php?" . $_SERVER['QUERY_STRING']);
    exit();
}

// Fetch rejected cases archive
$sql = "
  SELECT rc.*, u.username
  FROM rejected_cases rc
  JOIN users u ON rc.filed_by_user_id = u.user_id
  $whereSQL
  ORDER BY rc.created_at DESC
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
  <title>Rejected Cases</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    /* [same CSS as earlier pages] */
    *{margin:0;padding:0;box-sizing:border-box;}
    body,html{font-family:Arial,sans-serif;background:#f4f6f8;color:#333;line-height:1.4;font-size:14px;}
    .main-content{padding:100px 20px 20px;max-width:1280px;margin:0 auto;}
    .filter-bar{display:flex;gap:.5rem;align-items:center;margin-bottom:1.5rem;}
    .filter-bar input{flex:1;padding:.6rem 1rem;border:1px solid #ccc;border-radius:4px;transition:border .2s;max-width:800px;}
    .filter-bar input:focus{border-color:#1ABC9C;outline:none;}
    .filter-bar button{padding:.6rem 1.2rem;background:#2B295F;color:#fff;border:none;border-radius:4px;cursor:pointer;transition:background .2s;}
    .filter-bar button:hover{background:#16A085;}
    .filter-bar a{display:inline-block;padding:.6rem 1.2rem;background:#e74c3c;color:#fff;text-decoration:none;border-radius:4px;transition:background .2s;margin-left:.5rem;}
    .filter-bar a:hover{background:#c0392b;}
    .cases-table{width:100%;border-collapse:collapse;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,0.1);border:1px solid #bbb;border-radius:4px;overflow:hidden;margin-top:16px;}
    .cases-table th,.cases-table td{padding:6px 8px;text-align:left;border-bottom:1px solid #bbb;border-right:1px solid #bbb;font-size:.9em;vertical-align:middle;background-clip:padding-box;}
    .cases-table th{background:#2F4F4F;color:#fff;font-weight:600;text-transform:uppercase;}
    .cases-table th:last-child,.cases-table td:last-child{border-right:none;}
    .cases-table tr:nth-child(even){background:#fafafa;}
    .cases-table tr:hover{background:rgb(225,232,248);}
    .action-links select,.action-links textarea,.action-links button{width:100%;margin:4px 0;font-size:.8em;padding:4px 6px;border:1px solid #ccc;border-radius:3px;}
    .action-links button{background:#28a745;color:#fff;border:none;cursor:pointer;}
    .action-links button:hover{background:#218838;}
  </style>
</head>
<body>
  <?php include 'oheader.php'; ?>
  <aside class="sidebar"></aside>
  <main class="main-content">
    <h1>Rejected Cases</h1>
    <form method="GET" class="filter-bar">
      <input
        type="text"
        name="q"
        placeholder="Search rejected cases..."
        value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
      >
      <button type="submit">Search</button>
      <a href="view_rejected_cases.php">Reset</a>
    </form>
    <table class="cases-table">
      <thead>
        <tr>
          <th>ID</th><th>Filed By</th><th>Date</th><th>Title</th>
          <th>Victim</th><th>V. Age</th><th>Perpetrator</th><th>P. Age</th>
          <th>Location</th><th>Feedback</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while($row = mysqli_fetch_assoc($result)): ?>
        <tr>
          <td><?= $row['case_id'] ?></td>
          <td><?= htmlspecialchars($row['username']) ?></td>
          <td><?= htmlspecialchars(substr($row['created_at'],0,10)) ?></td>
          <td><?= htmlspecialchars($row['title']) ?></td>
          <td><?= htmlspecialchars($row['victim']) ?></td>
          <td><?= htmlspecialchars($row['victim_age']) ?></td>
          <td><?= htmlspecialchars($row['perpetrator']) ?></td>
          <td><?= htmlspecialchars($row['perpetrator_age']) ?></td>
          <td><?= htmlspecialchars($row['location']) ?></td>
          <td><?= nl2br(htmlspecialchars($row['feedback'])) ?></td>
          <td class="action-links">
            <form method="POST">
              <input type="hidden" name="case_id" value="<?= $row['case_id'] ?>">
              <textarea name="feedback" rows="2"><?= htmlspecialchars($row['feedback']) ?></textarea>
              <button type="submit">Save</button>
            </form>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </main>
</body>
</html>

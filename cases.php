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
      INSERT INTO cases (filed_by_user_id, title, victim, victim_age, perpetrator, perpetrator_age, location, description)
      VALUES ($user_id, '$title', '$victim', '$victim_age', '$perpetrator', '$perpetrator_age', '$location', '$description')";
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
  <link rel="stylesheet" href="cases.css">
</head>
<body>
  <?php include 'oheader.php'; ?>

  <aside class="sidebar">
    <!-- your sidebar menu -->
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

</body>
</html>

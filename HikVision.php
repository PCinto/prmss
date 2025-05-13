<?php
// qr_attendance_hik.php
date_default_timezone_set('Your/Timezone');
include 'connect.php';

$message    = "";
$today      = date("Y-m-d");
$currentTime= date("H:i:s");

if ($_SERVER['REQUEST_METHOD']==='POST' && !empty($_POST['qrdata'])) {
    $username = trim($_POST['qrdata']);  // scanned code as username
    $userrole = 'user';  // or fetch from users table as needed

    // Check existing record
    $checkQ = "SELECT * FROM attendance WHERE username=? AND date=?";
    $stmt   = $conn->prepare($checkQ);
    $stmt->bind_param("ss",$username,$today);
    $stmt->execute();
    $res    = $stmt->get_result();

    if ($res->num_rows>0) {
        $rec = $res->fetch_assoc();
        // Update checkin if missing
        if (empty($rec['checkin'])) {
            $upd = $conn->prepare("UPDATE attendance SET checkin=? WHERE username=? AND date=?");
            $upd->bind_param("sss",$currentTime,$username,$today);
            $message = $upd->execute()
                ? "[$username] Check‑in recorded at $currentTime."
                : "Error updating check‑in.";
        }
        // Else attempt checkout
        elseif (empty($rec['checkout'])) {
            if ($currentTime < $rec['checkin']) {
                $message = "Error: checkout ($currentTime) before check‑in ({$rec['checkin']}).";
            } else {
                $upd = $conn->prepare("UPDATE attendance SET checkout=? WHERE username=? AND date=?");
                $upd->bind_param("sss",$currentTime,$username,$today);
                $message = $upd->execute()
                    ? "[$username] Checked out at $currentTime."
                    : "Error updating check‑out.";
            }
        } else {
            $message = "[$username] Attendance already completed: in {$rec['checkin']}, out {$rec['checkout']}.";
        }
    } else {
        // First time check‑in
        $ins = $conn->prepare(
          "INSERT INTO attendance (username,userrole,date,checkin) VALUES (?,?,?,?)"
        );
        $ins->bind_param("ssss",$username,$userrole,$today,$currentTime);
        $message = $ins->execute()
            ? "[$username] Checked in at $currentTime."
            : "Error inserting check‑in.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate"/>
  <meta http-equiv="Pragma" content="no-cache"/>
  <meta http-equiv="Expires" content="0"/>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>HikVision QR Attendance</title>
  <link rel="stylesheet" href="attendance.css">
  <style>
    /* Hide the scanner input off-screen */
    #qrinput { 
      position: absolute; top: -100px; 
      opacity: 0; 
    }
  </style>
</head>
<body>
  <header class="main-header">
    <div class="header-inner">
      <div class="logo">
        <img src="logo.png" alt="Logo"><h1>Zain</h1>
      </div>
      <nav>
        <ul class="quick-links">
          <li><a href="userdash.php">Dashboard</a></li>
          <li><a href="help.php">Help</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main class="main-content">
    <section class="dashboard-section">
      <h2>Scan QR Code (HikVision Scanner)</h2>
      <?php if ($message): ?>
        <p class="message"><?php echo htmlspecialchars($message); ?></p>
      <?php endif; ?>

      <!-- Instruction -->
      <p>Please aim your HikVision QR scanner at your user QR code:</p>

      <!-- Hidden form and input; HikVision scanner will 'type' here -->
      <form id="qrForm" method="POST">
        <input type="text" id="qrinput" name="qrdata" autocomplete="off" autofocus>
      </form>
    </section>
  </main>

  <script>
    // When the HikVision scanner sends an "Enter" key, submit the form
    document.getElementById('qrinput').addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('qrForm').submit();
      }
    });
    // Keep focus on the hidden input so scanner always writes here
    window.onload = () => document.getElementById('qrinput').focus();
  </script>
</body>
</html>

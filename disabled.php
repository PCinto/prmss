<?php
session_start();

// Ensure only admins can access this page
if (!isset($_SESSION['userrole']) || strtolower($_SESSION['userrole']) !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'connect.php';

// Update email notifications to disabled
$query = "UPDATE settings SET value = 'disabled' WHERE name = 'email_notifications'";
if (mysqli_query($conn, $query)) {
    header("Location: settings.php?msg=Email+notifications+disabled");
    exit();
} else {
    header("Location: settings.php?msg=Error+updating+settings");
    exit();
}
?>

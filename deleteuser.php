<?php
session_start();

// Only allow admin users to access this page
if (!isset($_SESSION['userrole']) || strtolower($_SESSION['userrole']) !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'connect.php';

// Check if an id parameter is provided
if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $delete_query = "DELETE FROM users WHERE id = $user_id";
    if (mysqli_query($conn, $delete_query)) {
        echo "<script>alert('User deleted successfully.'); window.location.href='manageuser.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error deleting user: " . mysqli_error($conn) . "'); window.location.href='manageuser.php';</script>";
        exit();
    }
} else {
    header("Location: manageuser.php");
    exit();
}
?>

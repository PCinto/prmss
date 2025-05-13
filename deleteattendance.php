<?php
session_start();
include 'connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
    $id = intval($_POST['id']);
    $deleted_by = $_SESSION['user'];  // current admin username

    // 1) Archive with deleted_by
    $archiveStmt = $conn->prepare("
        INSERT INTO deletedattendance
          (attendance_id, username, userrole, date, checkin, checkout, deleted_at, deleted_by)
        SELECT
          id, username, userrole, date, checkin, checkout, NOW(), ?
        FROM attendance
        WHERE id = ?
    ");
    $archiveStmt->bind_param("si", $deleted_by, $id);  // s = string, i = integer :contentReference[oaicite:1]{index=1}
    $archiveStmt->execute();

    // 2) Remove from live table
    $delStmt = $conn->prepare("DELETE FROM attendance WHERE id = ?");
    $delStmt->bind_param("i", $id);
    if ($delStmt->execute()) {
        header("Location: manageattendance.php?status=deleted");
        exit();
    } else {
        echo "Error deleting record.";
    }
} else {
    echo "Invalid request.";
}
?>

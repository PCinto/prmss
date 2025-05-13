<?php
session_start();
include 'connect.php';
include 'header.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login'])) {
    $serial_no = mysqli_real_escape_string($conn, $_POST['serial_no']);
    $password  = $_POST['password'];

    $query  = "SELECT * FROM users WHERE serial_no='$serial_no' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);

        // Verify against the correct hashed column
        if (password_verify($password, $row['password_hash'])) {
            // Set consistent session keys
            $_SESSION['user']     = $row['username'];
            $_SESSION['userrole'] = $row['role'];

            // Redirect based on role
            $role = strtolower($row['role']);
            if ($role === 'officer') {
                header("Location: home.php");
                exit;
            } elseif ($role === 'cid') {
                header("Location: cidash.php");
                exit;
            } else {
                echo "<script>alert('Unknown role.');</script>";
            }
        } else {
            echo "<script>alert('Invalid credentials.');</script>";
        }
    } else {
        echo "<script>alert('User not found.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login</title>
  <link rel="stylesheet" href="login.css">
</head>
<body>
  <div class="login-container">
    <form action="login.php" method="POST">
      <div class="logo"><img src="pl1.jfif" alt="Logo"></div>
      <h2>Login</h2>
      <input type="text" name="serial_no" placeholder="Serial Number" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit" name="login">Login</button>
      <p>Don't have an account? <a href="register.php">Sign up</a></p>
    </form>
  </div>
</body>
</html>

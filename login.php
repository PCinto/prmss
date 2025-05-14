<?php
session_start();
include 'connect.php';
include 'header.php';
require 'expo.php';

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

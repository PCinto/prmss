<?php
session_start();
include 'connect.php';
include 'header.php';
require 'expor.php';


?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Signup</title>
  <style>
    /* Global Reset */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    html, body {
      height: 100%;
      font-family: Arial, sans-serif;
    }
    body {
      background: linear-gradient(to right, rgb(234, 232, 232), rgb(90, 85, 137));
      display: flex;
      justify-content: center;
      align-items: start;    /* align to top so margin-top works */
      margin: 0;
      padding-top: 80px;     /* push everything down below header */
    }
    /* Signup container styling */
    .signup-container {
      background: #fff;
      padding: 10px 20px;
      border-radius: 10px;
      width: 320px;
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
      text-align: center;
      transition: transform 0.3s ease;
      margin-top: 20px;      /* extra space if needed */
    }
    .signup-container:hover {
      transform: translateY(-5px);
    }
    .signup-container h2 {
      margin-bottom: 25px;
      color: #333;
      font-size: 24px;
    }
    .signup-container input,
    .signup-container select,
    .signup-container button {
      width: 100%;
      padding: 12px 15px;
      margin: 12px 0;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 16px;
    }
    .signup-container input:focus,
    .signup-container select:focus {
      outline: none;
      border-color: #28a745;
      box-shadow: 0 0 5px rgba(40, 167, 69, 0.5);
    }
    .signup-container button {
      background-color: #28a745;
      color: #fff;
      border: none;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    .signup-container button:hover {
      background-color: #218838;
    }
    .signup-container p {
      margin-top: 40px;
      font-size: 14px;
      color: #555;
    }
    .signup-container p a {
      color: #28a745;
      text-decoration: none;
      transition: color 0.3s ease;
    }
    .signup-container p a:hover {
      color: #218838;
    }
  </style>
</head>
<body>
  <div class="signup-container">
    <div class="logo"><img src="pl1.jfif" alt="Logo"></div>
    <h2>Signup</h2>
    <?php if ($signup_errors) echo $signup_errors; ?>
    <form action="register.php" method="POST">
      <input type="text"     name="username"  placeholder="Full Name"     required>
      <input type="text"     name="serial_no" placeholder="Serial Number"  required>
      <input type="password" name="password"  placeholder="Password"       required>
      <select name="userrole" required>
        <option value="" disabled selected>Select Role</option>
        <option value="admin">Admin</option>
        <option value="officer">Officer</option>
        <option value="cid">CID</option>
      </select>
      <button type="submit" name="signup">Sign Up</button>
      <p>Already have an account? <a href="login.php">Login</a></p>
    </form>
  </div>
</body>
</html>

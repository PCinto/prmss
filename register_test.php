<?php
session_start();
include 'connect.php';
include 'header.php';

// Include phpqrcode library
require_once __DIR__ . '/phpqrcode/qrlib.php';  // phpqrcode lives in project/phpqrcode :contentReference[oaicite:7]{index=7}

$signup_errors = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['signup'])) {
    // Sanitize inputs
    $username       = mysqli_real_escape_string($conn, $_POST['username']);
    $email          = mysqli_real_escape_string($conn, $_POST['email']);
    $password_plain = $_POST['password'];
    $userrole       = mysqli_real_escape_string($conn, $_POST['userrole']);

    $errors = [];

    // Validation (same as before) …
    if (!preg_match('/^[a-zA-Z]+$/', $username)) {
        $errors[] = "Username should only contain alphabets.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (strlen($password_plain) < 10) {
        $errors[] = "Password must be at least 10 characters long.";
    }
    if (!preg_match('/[^a-zA-Z0-9]/', $password_plain)) {
        $errors[] = "Password must contain at least one special character.";
    }

    if (!empty($errors)) {
        $signup_errors = "<div style='color:red; margin-bottom:10px;'><ul>";
        foreach ($errors as $err) {
            $signup_errors .= "<li>{$err}</li>";
        }
        $signup_errors .= "</ul></div>";
    } else {
        // Hash & insert user
        $password = password_hash($password_plain, PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
            "INSERT INTO users (username, email, password, userrole) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("ssss", $username, $email, $password, $userrole);

        if ($stmt->execute()) {
            // Get the new user ID
            $userId = $stmt->insert_id;

            // Prepare QR code directory
            $qrDir = __DIR__ . '/qrcodes/';
            if (!is_dir($qrDir)) {
                mkdir($qrDir, 0755, true);
            }

            // Generate QR code PNG: encode the username
            $qrFileName = $userId . '.png';
            $qrFilePath = $qrDir . $qrFileName;
            QRcode::png(
                $username,       // data to encode
                $qrFilePath,     // file path to save
                QR_ECLEVEL_H,    // high error correction
                4,               // size of each pixel
                2                // frame around QR
            );  // outputs file :contentReference[oaicite:8]{index=8}

            // Store the QR file path in the database
            $update = $conn->prepare(
                "UPDATE users SET qr_path = ? WHERE id = ?"
            );
            $update->bind_param("si", $qrFileName, $userId);
            $update->execute();  // commit the path :contentReference[oaicite:9]{index=9}

            echo "<script>
                    alert('Signup successful! Your QR code has been generated.');
                    window.location.href = 'login.php';
                  </script>";
            exit();
        } else {
            echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Signup</title>
  <!-- CSS omitted for brevity … -->
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
    /* Center the signup form on the page */
    body {
      background: linear-gradient(to right, rgb(234, 232, 232), rgb(90, 85, 137)); /* Linear gradient background */
      display: flex;
      justify-content: center;
      align-items: center;
      margin: 0;
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
    <div class="logo"><img src="logo.png" alt="Logo"></div>
    <h2>Signup</h2>
    <?php if ($signup_errors) echo $signup_errors; ?>
    <form action="register.php" method="POST">
      <input type="text" name="username" placeholder="Full Name" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <select name="userrole" required>
        <option value="" disabled selected>Select Role</option>
        <option value="User">User</option>
        <option value="Admin">Admin</option>
        <option value="Staff">Staff</option>
      </select>
      <button type="submit" name="signup">Sign Up</button>
      <p>Already have an account? <a href="login.php">Login</a></p>
    </form>
  </div>
</body>
</html>

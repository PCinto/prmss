<?php
session_start();
include 'connect.php';
include 'header.php';

$signup_errors = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['signup'])) {
    // Retrieve and sanitize inputs
    $username       = mysqli_real_escape_string($conn, $_POST['username']);
    $password_plain = $_POST['password'];
    $userrole       = mysqli_real_escape_string($conn, $_POST['userrole']);

    // Only fetch serial_no if provided
    $serial_no = "";
    if (isset($_POST['serial_no'])) {
        $serial_no = mysqli_real_escape_string($conn, $_POST['serial_no']);
    } else {
        $signup_errors .= "<div style='color:red;'><ul><li>Serial number is required.</li></ul></div>";
    }

    $errors = [];

  
    if (!preg_match('/^[A-Za-z]+(?:\s[A-Za-z]+)*$/', $username)) {
        $errors[] = "Username should only contain letters and single spaces between names.";
    }

    // Validate serial_no: non-empty, alphanumeric and slash allowed (e.g. FO540/X1x)
    if (empty($serial_no) || !preg_match('/^[A-Za-z0-9\/]+$/', $serial_no)) {
        $errors[] = "Serial number is invalid; only letters, digits, and “/” are allowed.";
    }

    // Validate password: at least 10 chars
    if (strlen($password_plain) < 10) {
        $errors[] = "Password must be at least 10 characters long.";
    }
    // Validate special character in password
    if (!preg_match('/[^a-zA-Z0-9]/', $password_plain)) {
        $errors[] = "Password must contain at least one special character.";
    }

    if ($errors) {
        // Build error messages
        $signup_errors = "<div style='color:red; margin-bottom:10px;'><ul>";
        foreach ($errors as $error) {
            $signup_errors .= "<li>$error</li>";
        }
        $signup_errors .= "</ul></div>";
    } else {
        // All validations passed; hash and insert
        $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);
        $sqlUser = "
          INSERT INTO users (username, serial_no, password_hash, role)
          VALUES ('$username', '$serial_no', '$password_hash', '$userrole')
        ";
        if (mysqli_query($conn, $sqlUser)) {
            // Also insert placeholder ID card
            $user_id  = mysqli_insert_id($conn);
            $card_sql = "
              INSERT INTO id_cards (user_id, serial_no, generated_at, pdf_path)
              VALUES ($user_id, '$serial_no', NOW(), 'idcards/{$user_id}.pdf')
            ";
            mysqli_query($conn, $card_sql);

            echo "<script>
                    alert('Signup successful! You can now log in.');
                    window.location.href='login.php';
                  </script>";
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

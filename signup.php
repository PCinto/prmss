<?php
session_start();
include 'connect.php';

$signup_errors = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Signup Process
    if (isset($_POST['signup'])) {
        // Retrieve and sanitize inputs
        $username       = mysqli_real_escape_string($conn, $_POST['username']);
        $email          = mysqli_real_escape_string($conn, $_POST['email']);
        $password_plain = $_POST['password'];
        $userrole       = mysqli_real_escape_string($conn, $_POST['userrole']);

        $errors = array();

        // Validate username: only alphabets allowed
        if (!preg_match('/^[a-zA-Z]+$/', $username)) {
            $errors[] = "Username should only contain alphabets.";
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }

        // Validate password: at least 10 characters
        if (strlen($password_plain) < 10) {
            $errors[] = "Password must be at least 10 characters long.";
        }
        // Validate password: must include at least one special character
        if (!preg_match('/[^a-zA-Z0-9]/', $password_plain)) {
            $errors[] = "Password must contain at least one special character.";
        }

        if (!empty($errors)) {
            // Build the error message HTML
            $signup_errors = "<div style='color:red; margin-bottom:10px;'><ul>";
            foreach ($errors as $error) {
                $signup_errors .= "<li>$error</li>";
            }
            $signup_errors .= "</ul></div>";
        } else {
            // All validations passed: hash the password and insert into the database
            $password = password_hash($password_plain, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (username, email, password, userrole) VALUES ('$username', '$email', '$password', '$userrole')";
            if (mysqli_query($conn, $query)) {
                echo "<script>alert('Signup successful! You can now log in.');</script>";
            } else {
                echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
            }
        }
    }

    // Login Process
    if (isset($_POST['login'])) {
        $email    = mysqli_real_escape_string($conn, $_POST['email']);
        $password = $_POST['password'];

        $query  = "SELECT * FROM users WHERE email='$email'";
        $result = mysqli_query($conn, $query);
        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);
            if (password_verify($password, $row['password'])) {
                $_SESSION['user'] = $row['username'];
                echo "<script>alert('Login successful!'); window.location.href='home.php';</script>";
            } else {
                echo "<script>alert('Invalid credentials.');</script>";
            }
        } else {
            echo "<script>alert('User not found.');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup & Login</title>
    <link rel="stylesheet" href="register.css">
</head>
<body>
    <div class="header">
        <h2>Zain Website</h2>
        <div class="buttons">
            <button onclick="toggleForm('signup-container')">Signup</button>
            <button onclick="toggleForm('login-container')">Login</button>
        </div>
    </div>

    <div id="signup-container" class="container">
        <h2>Signup</h2>
        <!-- Display inline errors only after signup is attempted -->
        <?php if (!empty($signup_errors)) { echo $signup_errors; } ?>
        <form action="" method="POST">
            <input type="text" name="username" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <select name="userrole" id="userrole" required>
                <option value="" disabled selected>Select Role</option>
                <option value="User">User</option>
                <option value="Admin">Admin</option>
            </select>
            <button type="submit" name="signup" class="submit-btn">Sign Up</button>
        </form>
    </div>

    <div id="login-container" class="container">
        <h2>Login</h2>
        <form action="" method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login" class="submit-btn">Login</button>
        </form>
    </div>

    <script>
        function toggleForm(formId) {
            let forms = ['signup-container', 'login-container'];
            forms.forEach(id => {
                document.getElementById(id).style.display = id === formId ? 'block' : 'none';
            });
        }
        // If the signup form was submitted, display the signup container to show errors.
        <?php if(isset($_POST['signup'])) { ?>
            toggleForm('signup-container');
        <?php } ?>
    </script>
</body>
</html>

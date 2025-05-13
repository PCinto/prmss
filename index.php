<?php
session_start();
// If a user session exists, ensure the role is "user". Otherwise, redirect.
if (isset($_SESSION['user']) && isset($_SESSION['userrole'])) {
    if (strtolower($_SESSION['userrole']) !== 'user') {
        // Redirect non-user roles to their appropriate dashboard page.
        // You may update the target URL as needed.
        header("Location: " . ($_SESSION['userrole'] === 'admin' ? "admindash.php" : "staffdash.php"));
        exit();
    } else {
        // Optionally, if you prefer to redirect logged in users with role 'user' away from login/signup:
        header("Location: userdash.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup & Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .header {
            background-color: #343a40;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header .buttons button {
            background: transparent;
            border: none;
            color: white;
            font-size: 16px;
            margin: 0 10px;
            cursor: pointer;
            padding: 10px;
        }
        .header .buttons button:hover {
            text-decoration: underline;
        }
        .container {
            display: none;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            width: 300px;
            margin: 50px auto;
        }
        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button.submit-btn {
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button.submit-btn:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Zain Website</h2>
        <div class="buttons">
            <button onclick="toggleForm('home')">Home</button>
            <button onclick="toggleForm('signup-container')">Signup</button>
            <button onclick="toggleForm('login-container')">Login</button>
        </div>
    </div>

    <div id="signup-container" class="container">
        <h2>Signup</h2>
        <form action="#" method="POST">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" class="submit-btn">Sign Up</button>
        </form>
    </div>

    <div id="login-container" class="container">
        <h2>Login</h2>
        <form action="#" method="POST">            
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" class="submit-btn">Login</button>
        </form>
    </div>

    <script>
        function toggleForm(formId) {
            let forms = ['signup-container', 'login-container'];
            forms.forEach(id => {
                document.getElementById(id).style.display = id === formId ? 'block' : 'none';
            });
        }
    </script>
</body>
</html>

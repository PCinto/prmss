<?php
session_start();

// Only allow admin users to access this page
if (!isset($_SESSION['userrole']) || strtolower($_SESSION['userrole']) !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'connect.php';

// Ensure an 'edit' parameter is provided, otherwise redirect to manage users page
if (!isset($_GET['edit'])) {
    header("Location: manageuser.php");
    exit();
}

$user_id = intval($_GET['edit']);

// Retrieve the current user data from the database
$query = "SELECT * FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) != 1) {
    echo "User not found.";
    exit();
}
$user_data = mysqli_fetch_assoc($result);

$edit_errors = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    // Retrieve and sanitize form inputs
    $username       = mysqli_real_escape_string($conn, $_POST['username']);
    $email          = mysqli_real_escape_string($conn, $_POST['email']);
    $userrole       = mysqli_real_escape_string($conn, $_POST['userrole']);
    $new_password_plain = $_POST['password']; // Optional, so no escaping required

    $errors = array();

    // Validate username: only alphabets allowed
    if (!preg_match('/^[a-zA-Z]+$/', $username)) {
        $errors[] = "Username should only contain alphabets.";
    }
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    // If new password is provided, validate it: at least 10 characters and one special character
    if (!empty($new_password_plain)) {
        if (strlen($new_password_plain) < 10) {
            $errors[] = "Password must be at least 10 characters long.";
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $new_password_plain)) {
            $errors[] = "Password must contain at least one special character.";
        }
    }

    // Build error messages if there are any validations issues
    if (!empty($errors)) {
        $edit_errors = "<div style='color:red; margin-bottom:10px;'><ul>";
        foreach ($errors as $error) {
            $edit_errors .= "<li>$error</li>";
        }
        $edit_errors .= "</ul></div>";
    } else {
        // Build the UPDATE query. If a new password was provided, hash and update it.
        if (!empty($new_password_plain)) {
            $new_password = password_hash($new_password_plain, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET username='$username', email='$email', userrole='$userrole', password='$new_password' WHERE id=$user_id";
        } else {
            $update_query = "UPDATE users SET username='$username', email='$email', userrole='$userrole' WHERE id=$user_id";
        }

        if (mysqli_query($conn, $update_query)) {
            echo "<script>alert('User updated successfully.'); window.location.href='manageuser.php';</script>";
            exit();
        } else {
            $edit_errors = "<p>Error updating user: " . mysqli_error($conn) . "</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit User - Admin Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Link to your existing CSS file -->
  <link rel="stylesheet" href="admindash.css">
  <style>
    /* Additional styling for the edit form */
    .form-container {
       background: #fff;
       padding: 20px;
       border-radius: 10px;
       width: 400px;
       margin: 50px auto;
       box-shadow: 0 8px 16px rgba(0,0,0,0.15);
    }
    .form-container input, .form-container select, .form-container button {
       width: 100%;
       padding: 10px;
       margin: 10px 0;
       border: 1px solid #ccc;
       border-radius: 5px;
    }
    .form-container button {
       background-color: #28a745;
       color: #fff;
       border: none;
       cursor: pointer;
       transition: background-color 0.3s ease;
    }
    .form-container button:hover {
       background-color: #218838;
    }
  </style>
</head>
<body>
  <?php include 'header.php'; ?>
  <div class="main-content">
    <div class="form-container">
      <h2>Edit User</h2>
      <?php
         if (!empty($edit_errors)) {
             echo $edit_errors;
         }
      ?>
      <form action="edituser.php?edit=<?php echo $user_id; ?>" method="POST">
         <input type="text" name="username" placeholder="Username" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
         <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
         <select name="userrole" required>
            <option value="User" <?php if($user_data['userrole'] == "User") echo "selected"; ?>>User</option>
            <option value="Admin" <?php if($user_data['userrole'] == "Admin") echo "selected"; ?>>Admin</option>
            <option value="Staff" <?php if($user_data['userrole'] == "Staff") echo "selected"; ?>>Staff</option>
         </select>
         <!-- Optional new password field. Leave blank to retain current password -->
         <input type="password" name="password" placeholder="New Password (leave blank to keep current)">
         <button type="submit" name="update">Update User</button>
      </form>
    </div>
  </div>
</body>
</html>

<?php
session_start();

// Validate that the logged-in user is an officer
if (!isset($_SESSION['userrole']) || strtolower($_SESSION['userrole']) !== 'officer') {
    header("Location: login.php");
    exit();
}

include 'connect.php';

$message = "";

// Process deletion if a 'delete' parameter is set in the URL
if (isset($_GET['delete'])) {
    $deleteId    = intval($_GET['delete']);
    $deleteQuery = "DELETE FROM users WHERE user_id = $deleteId";
    if (mysqli_query($conn, $deleteQuery)) {
        $message = "User deleted successfully.";
    } else {
        $message = "Error deleting user: " . mysqli_error($conn);
    }
}

// Process update if the update form is submitted
$edit_errors = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $user_id            = intval($_POST['user_id']);
    $username           = mysqli_real_escape_string($conn, $_POST['username']);
    $serial_no          = mysqli_real_escape_string($conn, $_POST['serial_no']);
    $role               = mysqli_real_escape_string($conn, $_POST['userrole']);
    $new_password_plain = $_POST['password']; // Optional password update

    $errors = [];

    // Validate username: only letters and spaces
    if (!preg_match('/^[A-Za-z]+(?:\s[A-Za-z]+)*$/', $username)) {
        $errors[] = "Username should only contain letters and spaces.";
    }
    // Validate serial_no: alphanumeric and slash allowed
    if (empty($serial_no) || !preg_match('/^[A-Za-z0-9\/]+$/', $serial_no)) {
        $errors[] = "Serial number is invalid; only letters, digits, and “/” are allowed.";
    }
    // Validate password if provided
    if (!empty($new_password_plain)) {
        if (strlen($new_password_plain) < 10) {
            $errors[] = "Password must be at least 10 characters long.";
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $new_password_plain)) {
            $errors[] = "Password must contain at least one special character.";
        }
    }

    if ($errors) {
        $edit_errors = "<div style='color:red; margin-bottom:10px;'><ul>";
        foreach ($errors as $error) {
            $edit_errors .= "<li>$error</li>";
        }
        $edit_errors .= "</ul></div>";
    } else {
        // Build update query
        if (!empty($new_password_plain)) {
            $new_password = password_hash($new_password_plain, PASSWORD_DEFAULT);
            $update_query = "
              UPDATE users
              SET username      = '$username',
                  serial_no     = '$serial_no',
                  role          = '$role',
                  password_hash = '$new_password'
              WHERE user_id = $user_id
            ";
        } else {
            $update_query = "
              UPDATE users
              SET username  = '$username',
                  serial_no = '$serial_no',
                  role      = '$role'
              WHERE user_id = $user_id
            ";
        }
        if (mysqli_query($conn, $update_query)) {
            echo "<script>alert('User updated successfully.'); window.location.href='manageuser.php';</script>";
            exit();
        } else {
            $edit_errors = "<p>Error updating user: " . mysqli_error($conn) . "</p>";
        }
    }
}

// Fetch user for inline edit form
$editingUser = null;
if (isset($_GET['edit'])) {
    $editId    = intval($_GET['edit']);
    $editQuery = "SELECT user_id, serial_no, username, role FROM users WHERE user_id = $editId";
    $editResult = mysqli_query($conn, $editQuery);
    if (mysqli_num_rows($editResult) == 1) {
        $editingUser = mysqli_fetch_assoc($editResult);
    } else {
        $message = "User not found for editing.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Users - Admin Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <style>
    /* Global Reset */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body, html {
      font-family: Arial, sans-serif;
      background: linear-gradient(135deg, #918190, #adc4d9);
      color: #333;
    }

    /* Main Header (Professional Fixed Header) */
    .main-header {
      width: 100%;
      background: linear-gradient(135deg, #121212, #38536d);
      color: #fff;
      padding: 15px 5%;
      position: fixed;
      top: -5px;
      left: 0;
      z-index: 1000;
    }
    .header-inner {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .logo img {
      height: 60px;
      margin-right: 10px;
      transition: transform 0.3s ease;
    }
    .logo img:hover {
      transform: scale(1.05);
    }
    .logo h1 {
      font-size: 1.75rem;
    }
    .user-info {
      font-size: 16px;
    }
    .user-info .logout {
      color: #fff;
      margin-left: 15px;
      text-decoration: none;
      border: 1px solid #fff;
      padding: 5px 10px;
      border-radius: 4px;
      transition: background 0.3s ease;
    }
    .user-info .logout:hover {
      background: #fff;
      color: #343a40;
    }

    /* Inline edit form – pushed down */
    .form-container {
      background: #fff;
      padding: 20px;
      border-radius: 10px;
      width: 400px;
      margin: 120px auto 20px; /* moved down under header */
      box-shadow: 0 8px 16px rgba(0,0,0,0.15);
    }
    .form-container input,
    .form-container select,
    .form-container button {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 5px;
    }
    .form-container button:hover {
      background-color: #218838;
    }

    /* Push the users table down under the fixed header */
    .main-content table {
      margin-top: 100px;  /* significantly pushed down */
      width: 85%;
      max-width: 900px;
      margin-left: auto;
      margin-right: auto;
      border-collapse: collapse;
      background: #fff;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
      border-radius: 8px;
      overflow: hidden;
    }

    /* Header row */
    .main-content th {
      background-color: #2F4F4F;
      color: #F8F8FF;
      padding: 12px 16px;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.9em;
      border-bottom: 2px solid #556B2F;
    }

    /* Body cells */
    .main-content td {
      padding: 12px 16px;
      font-size: 0.9em;
      color: #333;
      border-bottom: 1px solid #E2E8F0;
    }

    /* Zebra striping */
    .main-content tbody tr:nth-child(odd) {
      background-color: #FAFAFA;
    }
    .main-content tbody tr:nth-child(even) {
      background-color: #FFFFFF;
    }

    /* Hover effect */
    .main-content tbody tr:hover {
      background-color: #F0FFF0;
      transition: background-color 0.25s ease-in-out;
    }

    /* Rounded corners on first/last row */
    .main-content tbody tr:first-child td:first-child {
      border-top-left-radius: 8px;
    }
    .main-content tbody tr:first-child td:last-child {
      border-top-right-radius: 8px;
    }
    .main-content tbody tr:last-child td:first-child {
      border-bottom-left-radius: 8px;
    }
    .main-content tbody tr:last-child td:last-child {
      border-bottom-right-radius: 8px;
      border-bottom: none;
    }

    /* Action links */
    .action-links a {
      display: inline-block;
      margin-right: 6px;
      padding: 6px 10px;
      border-radius: 4px;
      color: #fff;
      text-decoration: none;
      font-size: 0.85em;
      transition: opacity 0.3s ease;
    }
    .edit-link   { background-color: #28a745; }
    .delete-link { background-color: #dc3545; }
    .create-link { background-color: #007bff; }
    .action-links a:hover {
      opacity: 0.85;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .header-inner {
        flex-direction: column;
        text-align: center;
      }
      .user-info {
        margin-top: 10px;
      }
    }
  </style>
</head>
<body>
  <?php include 'manageuserH.php'; ?>

  <div class="main-content">
    <h2>Manage Users</h2>
    <?php if ($message) { echo "<p style='color:green;'>$message</p>"; } ?>

    <!-- Inline Edit Form -->
    <?php if ($editingUser): ?>
      <div class="form-container">
        <h3>Edit User</h3>
        <?php if ($edit_errors) echo $edit_errors; ?>
        <form action="manageuser.php?edit=<?php echo $editingUser['user_id']; ?>" method="POST">
          <input type="hidden" name="user_id"  value="<?php echo $editingUser['user_id']; ?>">
          <input type="text"   name="username" placeholder="Full Name"     value="<?php echo htmlspecialchars($editingUser['username']); ?>" required>
          <input type="text"   name="serial_no" placeholder="Serial Number" value="<?php echo htmlspecialchars($editingUser['serial_no']); ?>" required>
          <select name="userrole" required>
            <option value="officer" <?php if($editingUser['role']=="officer") echo "selected"; ?>>Officer</option>
            <option value="admin"   <?php if($editingUser['role']=="admin")   echo "selected"; ?>>Admin</option>
            <option value="cid"     <?php if($editingUser['role']=="cid")     echo "selected"; ?>>CID</option>
          </select>
          <input type="password" name="password" placeholder="New Password (leave blank to keep current)">
          <button type="submit" name="update">Update User</button>
        </form>
      </div>
    <?php endif; ?>

    <!-- Users Table -->
    <table>
      <thead>
        <tr>
          <th>User ID</th>
          <th>Serial No</th>
          <th>Username</th>
          <th>Role</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $allUsersQuery  = "SELECT user_id, serial_no, username, role FROM users";
        $allUsersResult = mysqli_query($conn, $allUsersQuery);
        while ($row = mysqli_fetch_assoc($allUsersResult)):
        ?>
          <tr>
            <td><?php echo htmlspecialchars($row['user_id']); ?></td>
            <td><?php echo htmlspecialchars($row['serial_no']); ?></td>
            <td><?php echo htmlspecialchars($row['username']); ?></td>
            <td><?php echo htmlspecialchars($row['role']); ?></td>
            <td class="action-links">
              <a class="edit-link"   href="manageuser.php?edit=<?php echo $row['user_id']; ?>">Edit</a>
              <a class="delete-link" href="manageuser.php?delete=<?php echo $row['user_id']; ?>" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
              <a class="create-link" href="create_id_card.php?user_id=<?php echo $row['user_id']; ?>">Create ID</a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</body>
</html>

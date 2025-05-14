<?php
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
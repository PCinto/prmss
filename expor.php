<?php

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
<?php
session_start();
include '../db/config.php';

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Sanitize input
    $login_identifier = mysqli_real_escape_string($conn, $_POST['login_identifier']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Server-side validation
    if (empty($login_identifier) || empty($password)) {
        // Instead of just echoing, you might want to redirect back to login with an error
        $_SESSION['login_error'] = "Please fill in all fields.";
        header("Location: ../view/login.php");
        exit;
    }

    // Prepare query to check for user by username or email
    $query = "SELECT user_id, username, email, password_hash, admin_status, user_type 
              FROM MM_Users 
              WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($query);

    if ($stmt) {
        // Bind both parameters to the same login identifier
        $stmt->bind_param("ss", $login_identifier, $login_identifier);
        $stmt->execute();

        // Get result
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                // Store user information in session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['admin_status'] = $user['admin_status'];

                // Redirect based on admin status and user type
                if ($user['admin_status'] === '1') {
                    // Admin redirect
                    header("Location: ../view/admin/admin_dashboard.php");
                } elseif ($user['user_type'] === 'trainer') {
                    // Trainer redirect
                    header("Location: ../view/admin/trainer_dashboard.php");
                } elseif ($user['user_type'] === 'nutritionist') {
                    // Nutritionist redirect
                    header("Location: ../view/admin/nutritionist_dashboard.php");
                } else {
                    // Regular user redirect
                    header("Location: ../view/admin/user_dashboard.php");
                }
                exit();
            } else {
                // Invalid password
                $_SESSION['login_error'] = "Incorrect login credentials.";
                header("Location: ../view/login.php");
                exit;
            }
        } else {
            // No user found
            $_SESSION['login_error'] = "No account found with this username or email.";
            header("Location: ../view/login.php");
            exit;
        }

        $stmt->close();
    } else {
        // Database query preparation error
        $_SESSION['login_error'] = "Database error. Please try again.";
        header("Location: ../view/login.php");
        exit;
    }

    $conn->close();
} else {
    // Invalid request method
    $_SESSION['login_error'] = "Invalid request method.";
    header("Location: ../view/login.php");
    exit;
}
?>
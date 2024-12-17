<?php
// Include database connection
include '../db/config.php';

$errors = []; // Array to hold all error messages during registration

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Sanitize and validate inputs
    $firstName = mysqli_real_escape_string($conn, $_POST['first_name']);
    $lastName = mysqli_real_escape_string($conn, $_POST['last_name']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirmPassword = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    $userType = isset($_POST['user_type']) ? $_POST['user_type'] : 'regular';
    $profilePicture = null;

    // Server-side validation
    // First Name Validation
    if (empty($firstName) || strlen($firstName) < 2) {
        $errors[] = "First name must be at least 2 characters long.";
    }

    // Last Name Validation
    if (empty($lastName) || strlen($lastName) < 2) {
        $errors[] = "Last name must be at least 2 characters long.";
    }

    // Username Validation
    if (empty($username) || strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters long.";
    } else {
        // Check for duplicate username
        $usernameCheckQuery = "SELECT * FROM MM_Users WHERE username = ?";
        $stmt = $conn->prepare($usernameCheckQuery);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Username already exists.";
        }
        $stmt->close();
    }

    // Email Validation
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    } else {
        // Check for duplicate email
        $emailCheckQuery = "SELECT * FROM MM_Users WHERE email = ?";
        $stmt = $conn->prepare($emailCheckQuery);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Email already registered.";
        }
        $stmt->close();
    }

    // Password Validation
    if (empty($password) || 
        strlen($password) < 6 || 
        strlen($password) > 20 || 
        !preg_match("/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,20}$/", $password)) {
        $errors[] = "Password must be 6-20 characters long, contain at least one digit, one uppercase and one lowercase letter.";
    }

    // Confirm Password Validation
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    // Profile Picture Upload (Optional)
    if (!empty($_FILES['profile_picture']['name'])) {
        $file = $_FILES['profile_picture'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB

        if (in_array($file['type'], $allowedTypes) && $file['size'] <= $maxFileSize) {
            // Read file content
            $profilePicture = file_get_contents($file['tmp_name']);
        } else {
            $errors[] = "Invalid profile picture. Must be JPEG, PNG, or GIF and under 5MB.";
        }
    }

    // Proceed if no errors
    if (empty($errors)) {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Prepare insert query
        $insertQuery = "INSERT INTO MM_Users 
            (username, email, password_hash, first_name, last_name, profile_picture, user_type) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("sssssss", 
            $username, 
            $email, 
            $hashedPassword, 
            $firstName, 
            $lastName, 
            $profilePicture, 
            $userType
        );

        if ($stmt->execute()) {
            // Redirect to login page or dashboard
            header("Location: ../view/login.php");
            exit();
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
        $stmt->close();
    }

    // If there are errors, display them
    if (!empty($errors)) {
        foreach ($errors as $error) {
            echo "<p style='color: red;'>$error</p>";
        }
    }

    $conn->close();
}
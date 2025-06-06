<?php
session_start();
include("../includes/config.php");
include("../includes/header.php");

// Check if form data was submitted
if (isset($_POST['email'], $_POST['password'], $_POST['confirmPass'], $_POST['role'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirmPass = trim($_POST['confirmPass']);
    $role = $_POST['role'];

    // Check if passwords match
    if ($password !== $confirmPass) {
        $_SESSION['message'] = 'Passwords do not match';
        header("Location: register.php");
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = 'Invalid email format';
        header("Location: register.php");
        exit();
    }

    // Check if email is already in use
    $checkEmail = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    if ($checkEmail === false) {
        $_SESSION['message'] = 'Database error: ' . $conn->error;
        header("Location: register.php");
        exit();
    }
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $checkEmail->store_result();

    if ($checkEmail->num_rows > 0) {
        $_SESSION['message'] = 'Email already in use';
        $checkEmail->close();
        header("Location: register.php");
        exit();
    }
    $checkEmail->close();

    // Check if the role is valid (now only 'admin' or 'resident')
    if ($role !== 'resident' && $role !== 'admin') {
        $_SESSION['message'] = 'Invalid role selected';
        header("Location: register.php");
        exit();
    }

    // Additional fields based on the role
    if ($role == 'admin') {
        // Ensure the ID number starts with 1632
        $idNumber = $_POST['id_number'] ?? '';
        if (strpos($idNumber, '1632') !== 0) {
            $_SESSION['message'] = 'ID number must start with "1632"';
            header("Location: register.php");
            exit();
        }

        $position = $_POST['position'] ?? '';
        if (empty($position)) {
            $_SESSION['message'] = 'Position is required for admin';
            header("Location: register.php");
            exit();
        }
    }

    // Hash the password securely
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert the new user into the database without specifying the primary key (auto-increment)
    $stmt = $conn->prepare("INSERT INTO users (email, password, role, id_number, position) VALUES (?, ?, ?, ?, ?)");
    if ($stmt === false) {
        $_SESSION['message'] = 'Database error: ' . $conn->error;
        header("Location: register.php");
        exit();
    }

    // Bind the parameters based on the role
    if ($role == 'admin') {
        $position = $_POST['position'];
        $stmt->bind_param("sssss", $email, $hashedPassword, $role, $idNumber, $position);
    } else {
        $stmt->bind_param("ssss", $email, $hashedPassword, $role, $idNumber);
    }

    if ($stmt->execute()) {
        $_SESSION['user_id'] = $stmt->insert_id;  // Store user ID in session
        $_SESSION['message'] = 'Registration successful!';
        header("Location: profile.php");
        exit();
    } else {
        $_SESSION['message'] = 'Registration error: ' . $stmt->error;
        header("Location: register.php");
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    // If form fields were not submitted, show an error message
    $_SESSION['message'] = 'Please fill in all fields';
    header("Location: register.php");
    exit();
}
?>

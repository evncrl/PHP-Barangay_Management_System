<?php
session_start();
include('../includes/config.php'); // Database connection file

// Initialize messages
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['register'])) {
        // Registration logic
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $address = $_POST['address'] ?? '';

        if ($password !== $confirm_password) {
            $error_message = "Passwords do not match!";
        } else {
            $query = "SELECT email FROM users WHERE email = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error_message = "This email is already registered!";
            } else {
                if (strpos(strtolower(trim($address)), 'lower bicutan') === false) {
                    $error_message = "Residents must be in Lower Bicutan!";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $role = 'resident';

                    $query = "INSERT INTO users (email, password_hash, role, address) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("ssss", $email, $hashed_password, $role, $address);

                    if ($stmt->execute()) {
                        $_SESSION['user_id'] = $stmt->insert_id;
                        $_SESSION['user_email'] = $email;
                        $_SESSION['user_role'] = $role;
                        $success_message = "Registration successful!";
                        header("Refresh: 3; url=../user/profile.php");
                    } else {
                        $error_message = "Registration failed. Please try again.";
                    }
                }
            }
        }
    } elseif (isset($_POST['login'])) {
        // Login logic
        $username = $_POST['username'];
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            header("Location: profile.php");
            exit();
        } else {
            $error_message = "Invalid email or password!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            display: flex;
            height: 100vh;
            justify-content: center;
            align-items: center;
            background: #f0f0f0;
        }

        .container {
            position: relative;
            width: 800px;
            height: 450px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .form-container {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            transition: transform 0.5s ease-in-out;
        }

        .form-box {
            width: 50%;
            padding: 40px;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-box h2 {
            margin-bottom: 15px;
            font-weight: 600;
        }

        .form-box input {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1rem;
        }

        .form-box button {
            width: 100%;
            padding: 12px;
            background-color: #2c3e50;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s ease;
            margin-top: 10px;
        }

        .form-box button:hover {
            background-color: #d4ac00;
        }

        .toggle-container {
            width: 50%;
            background: #2c3e50;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 40px;
        }

        .toggle-container h2 {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .toggle-container button {
            background: transparent;
            color: white;
            border: 2px solid white;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            transition: 0.3s ease;
        }

        .toggle-container button:hover {
            background: white;
            color: #2c3e50;
        }

        .move-right {
            transform: translateX(100%);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="form-container">
        <!-- Register Form -->
        <div class="form-box" id="register-box">
            <h2>Create Account</h2>
            <form action="" method="POST">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <input type="text" name="address" placeholder="Address" required>
                <button type="submit" name="register">Register</button>
            </form>
        </div>

        <!-- Login Form -->
        <div class="form-box move-right" id="login-box">
            <h2>Login</h2>
            <form action="" method="POST">
                <input type="email" name="username" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login">Login</button>
            </form>
        </div>
    </div>

    <!-- Toggle Section -->
    <div class="toggle-container">
        <h2 id="toggle-text">Already have an account?</h2>
        <button id="toggle-btn">Login</button>
    </div>
</div>

<script>
    const registerBox = document.getElementById("register-box");
    const loginBox = document.getElementById("login-box");
    const toggleText = document.getElementById("toggle-text");
    const toggleBtn = document.getElementById("toggle-btn");

    toggleBtn.addEventListener("click", () => {
        registerBox.classList.toggle("move-right");
        loginBox.classList.toggle("move-right");

        if (toggleBtn.textContent === "Login") {
            toggleBtn.textContent = "Register";
            toggleText.textContent = "Don't have an account?";
        } else {
            toggleBtn.textContent = "Login";
            toggleText.textContent = "Already have an account?";
        }
    });
</script>

</body>
</html>

<?php
session_start();
include '../includes/config.php'; // Ensure this file initializes $conn

$error = ""; // Initialize error message variable

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare and execute SQL query to fetch user details along with verification status
    $stmt = mysqli_prepare($conn, "SELECT u.*, r.verification_status FROM users u LEFT JOIN residents r ON u.user_id = r.user_id WHERE u.email = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if (!$user) {
        $error = "No existing user. Please register."; // User not found
    } elseif (!password_verify($password, $user['password_hash'])) {
        $error = "Invalid password."; // Incorrect password
    } elseif ($user['verification_status'] == 'Deactivate') {
        $error = "Your account has been deactivated. Please contact support."; // Account deactivated
    } elseif ($user['verification_status'] != 'Approved') {
        $error = "Your account has not been approved for verification. Please wait for at least 3 business days to proceed."; // Not approved
    } else {
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];
    
        // Redirect based on role
        switch ($_SESSION['role']) {
            case 'admin':
                header("Location: ../item/index.php"); // Admin goes to admin dashboard
                break;
            case 'secretary':
                header("Location: ../item/secretary.php"); // Secretary goes to document processing
                break;
            case 'maintenance':
                    header("Location: ../item/staff_facilities.php"); // Secretary goes to document processing
                    break;
            case 'lupon':
                        header("Location: ../item/staff_lupon.php"); // Secretary goes to document processing
                        break;
            case 'offices':
                     header("Location: ../item/staff_offices.php"); // Secretary goes to document processing
                            break;
            
            default:
                header("Location: ../index.php"); // Regular users go to homepage
        }
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Barangay Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 20px;
        }

        .container {
            display: flex;
            width: 900px;
            height: 500px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .welcome-container {
            width: 50%;
            background: linear-gradient(135deg, #1a2a6c 0%, #2a5298 50%, #4389a2 100%);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        .welcome-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://your-background-pattern.png') center/cover;
            opacity: 0.1;
        }

        .welcome-content {
            position: relative;
            z-index: 1;
        }

        .welcome-container h2 {
            font-size: 2.2rem;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .welcome-container p {
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .welcome-container button {
            background: transparent;
            color: white;
            border: 2px solid white;
            padding: 12px 30px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
        }

        .welcome-container button:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .form-container {
            width: 50%;
            padding: 50px 40px;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo img {
            height: 60px;
            margin-bottom: 10px;
        }

        .logo h3 {
            color: #1a2a6c;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .form-container h2 {
            margin-bottom: 25px;
            font-weight: 600;
            color: #333;
            text-align: center;
            font-size: 1.8rem;
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .form-container input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #f9f9f9;
        }

        .form-container input:focus {
            border-color: #4389a2;
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 137, 162, 0.2);
            background-color: #fff;
        }

        .form-container button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1a2a6c 0%, #2a5298 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            box-shadow: 0 4px 10px rgba(26, 42, 108, 0.2);
        }

        .form-container button:hover {
            background: linear-gradient(135deg, #1a2a6c 0%, #1e3c7b 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(26, 42, 108, 0.3);
        }

        .form-container button:active {
            transform: translateY(0);
        }

        .form-container a {
            text-decoration: none;
            font-size: 0.95rem;
            color: #2a5298;
            margin-top: 15px;
            display: block;
            text-align: center;
            transition: color 0.3s ease;
        }

        .form-container a:hover {
            color: #1a2a6c;
            text-decoration: underline;
        }

        .error-message {
            color: #e74c3c;
            font-size: 0.9rem;
            margin-top: 15px;
            background-color: rgba(231, 76, 60, 0.1);
            padding: 10px;
            border-radius: 6px;
            border-left: 4px solid #e74c3c;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                height: auto;
                width: 95%;
                max-width: 450px;
            }
            
            .welcome-container, .form-container {
                width: 100%;
            }
            
            .welcome-container {
                padding: 30px 20px;
            }
            
            .form-container {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="welcome-container">
            <div class="welcome-content">
                <h2>Welcome Back</h2>
                <p>New to our system? Create an account to access barangay services and stay connected with your community.</p>
                <a href="register.php"><button>Register Now</button></a>
            </div>
        </div>

        <div class="form-container">
            <div class="logo">
                <!-- Replace with your actual logo -->
                <img src="../user/uploads/download (2).png" alt="Barangay Logo" onerror="this.src='../user/uploads/default-logo.png'; this.onerror=null;">
                <h3>Barangay Lower Bicutan</h3>
            </div>
            
            <h2>Sign In</h2>
            <form action="login.php" method="POST">
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="username" placeholder="Email Address" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit">Login</button>

                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
               
            </form>
        </div>
    </div>
</body>
</html>
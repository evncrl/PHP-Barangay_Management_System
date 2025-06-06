<?php
session_start();
include('../includes/config.php'); // Database connection file

// Initialize messages
$error_message = '';
$success_message = '';

// Initialize form variables
$email = '';
$address = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $address = $_POST['address'] ?? '';

    // Validate password match
    if ($password !== $confirm_password) {
        $error_message = "Passwords do not match!";
    }
    // Check if email already exists
    else {
        $query = "SELECT email FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            $error_message = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error_message = "This email is already registered!";
            } else {
                // Validate address for residents (check if the address contains "Lower Bicutan")
                if (strpos(strtolower(trim($address)), 'lower bicutan') === false) {
                    $error_message = "Residents must be in Lower Bicutan!";
                }

                if (empty($error_message)) {
                    // Hash password before storing
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // Insert user with the default role as 'resident'
                    $query = "INSERT INTO users (email, password_hash, role, address) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($query);
                    $role = 'resident'; // Default role set as 'resident'
                    $stmt->bind_param("ssss", $email, $hashed_password, $role, $address);

                    if ($stmt->execute()) {
                        $user_id = $stmt->insert_id;

                        // Insert resident details into the residents table
                        $resident_query = "INSERT INTO residents (user_id, address) VALUES (?, ?)";
                        $resident_stmt = $conn->prepare($resident_query);
                        $resident_stmt->bind_param("is", $user_id, $address);
                        $resident_stmt->execute();

                        // Set session variables for the logged-in user
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['user_email'] = $email;
                        $_SESSION['user_role'] = $role;

                        // Show success message and redirect
                        $success_message = "Registration successful!";
                        header("Refresh: 3; url=../user/profile.php");
                        exit();
                    } else {
                        $error_message = "Registration failed. Please try again.";
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Barangay Management System</title>
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
            height: 550px;
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
            padding: 40px;
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
            margin-bottom: 15px;
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

        .success-message {
            color: #27ae60;
            font-size: 0.9rem;
            margin-top: 15px;
            background-color: rgba(39, 174, 96, 0.1);
            padding: 10px;
            border-radius: 6px;
            border-left: 4px solid #27ae60;
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
        <div class="form-container">
            <div class="logo">
                <!-- Replace with your actual logo -->
                <img src="../user/uploads/download (2).png" alt="Barangay Logo" onerror="this.src='../user/uploads/default-logo.png'; this.onerror=null;">
                <h3>Barangay Lower Bicutan</h3>
            </div>
            
            <h2>Create Account</h2>
            <form action="register.php" method="POST">
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email Address" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-check-circle"></i>
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-map-marker-alt"></i>
                    <input type="text" name="address" placeholder="Complete Address (Lower Bicutan)" value="<?php echo htmlspecialchars($address ?? ''); ?>" required>
                </div>

                <?php if (!empty($success_message)) : ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)) : ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <button type="submit">Register</button>
            </form>
        </div>

        <div class="welcome-container">
            <div class="welcome-content">
                <h2>Welcome!</h2>
                <p>Already have an account? Sign in to access barangay services and stay connected with your community.</p>
                <a href="login.php"><button>Sign In</button></a>
            </div>
        </div>
    </div>
</body>
</html>
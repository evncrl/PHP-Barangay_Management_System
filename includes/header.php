<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$base_url = "/saad/"; 

// Get current page to highlight active menu item
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary-color: #043060;
            --primary-light: #0d4a8f;
            --accent-color: #4CAF50;
            --text-color: #333;
            --text-light: #777;
            --bg-light: #f5f7fa;
            --white: #fff;
            --header-height: 70px;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-light);
            color: var(--text-color);
            padding-top: var(--header-height);
        }
        
        /* Header */
        .header {
            background: var(--white);
            height: var(--header-height);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
        }
        
        /* Branding */
        .branding {
            display: flex;
            align-items: center;
        }
        
        .logo {
            width: 40px;
            height: 40px;
            background-color: var(--primary-color);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: bold;
            font-size: 18px;
            margin-right: 10px;
        }
        
        .brand-text {
            display: flex;
            flex-direction: column;
        }
        
        .brand-name {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-color);
            line-height: 1.2;
        }
        
        .brand-subtitle {
            font-size: 12px;
            color: var(--text-light);
        }
        
        /* Navigation */
        .nav-container {
            display: flex;
            align-items: center;
        }
        
        .nav-list {
            list-style: none;
            display: flex;
            margin: 0;
            padding: 0;
        }
        
        .nav-item {
            position: relative;
            margin: 0 5px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            color: var(--text-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            border-radius: 4px;
            transition: var(--transition);
        }
        
        .nav-link:hover {
            background-color: rgba(4, 48, 96, 0.05);
            color: var(--primary-color);
        }
        
        .nav-link.active {
            background-color: rgba(4, 48, 96, 0.1);
            color: var(--primary-color);
        }
        
        .nav-icon {
            font-size: 16px;
            margin-right: 8px;
        }
        
        /* User menu */
        .user-menu {
            margin-left: 15px;
            position: relative;
        }
        
        .user-button {
            display: flex;
            align-items: center;
            padding: 8px 15px;
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .user-button:hover {
            background-color: var(--primary-light);
        }
        
        .user-icon {
            margin-right: 8px;
        }
        
        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: var(--white);
            border-radius: 4px;
            box-shadow: var(--shadow);
            min-width: 180px;
            margin-top: 10px;
            display: none;
            z-index: 101;
        }
        
        .dropdown-menu.show {
            display: block;
        }
        
        .dropdown-item {
            display: block;
            padding: 10px 15px;
            color: var(--text-color);
            text-decoration: none;
            font-size: 14px;
            transition: var(--transition);
        }
        
        .dropdown-item:hover {
            background-color: rgba(4, 48, 96, 0.05);
            color: var(--primary-color);
        }
        
        .dropdown-divider {
            height: 1px;
            background-color: rgba(0,0,0,0.1);
            margin: 5px 0;
        }
        
        /* Mobile menu */
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--primary-color);
            font-size: 24px;
            cursor: pointer;
        }
        
        /* Main content area */
        .main-content {
            padding: 20px;
            min-height: calc(100vh - var(--header-height));
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .nav-container {
                position: fixed;
                top: var(--header-height);
                left: 0;
                right: 0;
                background-color: var(--white);
                box-shadow: var(--shadow);
                padding: 10px 0;
                flex-direction: column;
                align-items: flex-start;
                transform: translateY(-100%);
                opacity: 0;
                visibility: hidden;
                transition: var(--transition);
            }
            
            .nav-container.show {
                transform: translateY(0);
                opacity: 1;
                visibility: visible;
            }
            
            .nav-list {
                flex-direction: column;
                width: 100%;
            }
            
            .nav-item {
                width: 100%;
                margin: 0;
            }
            
            .nav-link {
                padding: 12px 20px;
                border-radius: 0;
            }
            
            .user-menu {
                margin: 10px 20px;
                width: calc(100% - 40px);
            }
            
            .dropdown-menu {
                position: static;
                box-shadow: none;
                border: 1px solid rgba(0,0,0,0.1);
                margin-top: 5px;
            }
            
            .menu-toggle {
                display: block;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="branding">
            <div class="logo">BMS</div>
            <div class="brand-text">
                <span class="brand-name">Barangay Management System</span>
                <span class="brand-subtitle">Lower Bicutan, Taguig City</span>
            </div>
        </div>
        
        <button class="menu-toggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="nav-container">
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="/saad/index.php" class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                        <span class="nav-icon"><i class="fas fa-home"></i></span>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                
                <?php if (!isset($_SESSION['user_id'])): ?>
                <li class="nav-item">
                    <a href="/saad/user/login.php" class="nav-link <?php echo ($current_page == 'login.php') ? 'active' : ''; ?>">
                        <span class="nav-icon"><i class="fas fa-sign-in-alt"></i></span>
                        <span>Login</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-menu">
                <button class="user-button" id="userMenuBtn">
                    <span class="user-icon"><i class="fas fa-user"></i></span>
                    <span><?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'User'; ?></span>
                </button>
                
                <div class="dropdown-menu" id="userDropdown">
                    <a href="/saad/user/profile.php" class="dropdown-item">
                        <i class="fas fa-user-circle"></i> Profile
                    </a>

                    <div class="dropdown-divider"></div>
                    <a href="/saad/user/logout.php" class="dropdown-item">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main content wrapper -->
    <div class="main-content">
        <!-- Your page content will go here -->

    <script>
        // Mobile menu toggle
        document.querySelector('.menu-toggle').addEventListener('click', function() {
            document.querySelector('.nav-container').classList.toggle('show');
        });
        
        // User dropdown toggle
        const userMenuBtn = document.getElementById('userMenuBtn');
        const userDropdown = document.getElementById('userDropdown');
        
        if (userMenuBtn) {
            userMenuBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                userDropdown.classList.toggle('show');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!userDropdown.contains(e.target) && e.target !== userMenuBtn) {
                    userDropdown.classList.remove('show');
                }
            });
        }
    </script>
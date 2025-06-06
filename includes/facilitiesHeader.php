<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: ../user/login.php"); // Redirect to login if session is missing
    exit();
}

// Define the base URL path for consistent navigation
$base_path = "/saad";
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADMIN - Barangay Lower Bicutan</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            color: #333;
            background-color: #f8f9fa;
            line-height: 1.6;
            padding-top: 120px; /* Adjusted space for fixed header */
        }
        
        /* Header Styles */
        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background-color: #223F61;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
        
        .brgy-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 20px;
            background-color: #1a2f4a;
            color: white;
        }
        
        .brgy-title {
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .brgy-title img {
            height: 30px;
            margin-right: 10px;
        }
        
        .admin-badge {
            background-color: #3383FF;
            color: white;
            padding: 3px 10px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Navigation Styles */
        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            position: relative;
        }
        
        nav {
            width: 100%;
        }
        
        nav ul {
            display: flex;
            list-style: none;
            justify-content: flex-start;
            align-items: center;
        }
        
        nav ul li {
            margin: 0;
            position: relative;
        }
        
        nav ul li a {
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            padding: 15px 20px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        nav ul li a:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        nav ul li a.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.15);
            border-bottom: 3px solid #3383FF;
        }
        
        nav ul li a i {
            margin-right: 8px;
            font-size: 1rem;
        }
        
        /* Right-side User Menu */
        .user-menu {
            display: flex;
            align-items: center;
            margin-left: auto;
        }
        
        .logout-btn {
            background-color: #f44336;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            border: none;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        
        .logout-btn:hover {
            background-color: #d32f2f;
        }
        
        .logout-btn i {
            margin-right: 6px;
        }
        
        /* Mobile Menu */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .nav-container {
                flex-direction: column;
                align-items: flex-start;
            }
            
            nav ul {
                flex-direction: column;
                width: 100%;
                display: none;
                position: absolute;
                top: 60px; /* Adjusted to match the nav height */
                left: 0;
                background-color: #223F61;
                z-index: 1001;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            
            nav ul.show {
                display: flex;
            }
            
            nav ul li {
                width: 100%;
            }
            
            nav ul li a {
                padding: 15px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .mobile-menu-toggle {
                display: block;
                position: absolute;
                right: 20px;
                top: 18px;
            }
            
            .user-menu {
                width: 100%;
                margin: 0;
                padding: 0;
            }
            
            .logout-btn {
                width: 100%;
                justify-content: center;
                margin: 10px 0;
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding-top: 140px; /* Adjusted for smaller screens */
            }
            
            .brgy-header {
                flex-direction: column;
                text-align: center;
                padding: 10px;
            }
            
            .admin-badge {
                margin-top: 5px;
            }
        }
        
        /* Content Container */
        .content-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
    </style>
</head>
<body>

<header>
    <div class="brgy-header">
        <div class="brgy-title">
            <img src="<?php echo $base_path; ?>/includes/style/lowerbicutanlogo.png" alt="Barangay Logo" onerror="this.style.display='none'">
            Barangay Lower Bicutan
        </div>
        <div class="admin-badge">Maintenance Panel</div>
    </div>
    
    <div class="nav-container">
        <button class="mobile-menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <nav>
            <ul id="mainNav">
                <li><a href="../item/staff_facilities.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-house"></i> Dashboard
                </a></li>
             
                
                <li class="user-menu">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="../user/logout.php" class="logout-btn">
                            <i class="fa-solid fa-sign-out-alt"></i> Logout
                        </a>
                    <?php else: ?>
                        <a href="../user/login.php">
                            <i class="fa-solid fa-sign-in-alt"></i> Login
                        </a>
                    <?php endif; ?>
                </li>
            </ul>
        </nav>
    </div>
</header>

<!-- Content Container for Page Content -->
<div class="content-container">
    <!-- Main Content Goes Here -->
</div>

<script>
    // Improved mobile menu toggle
    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.getElementById('menuToggle');
        const mainNav = document.getElementById('mainNav');
        
        if (menuToggle && mainNav) {
            menuToggle.addEventListener('click', function() {
                mainNav.classList.toggle('show');
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', function(event) {
                if (!event.target.closest('#mainNav') && !event.target.closest('#menuToggle')) {
                    mainNav.classList.remove('show');
                }
            });
            
            // Close menu when window is resized to desktop size
            window.addEventListener('resize', function() {
                if (window.innerWidth > 992) {
                    mainNav.classList.remove('show');
                }
            });
        }
    });
</script>

</body>
</html>
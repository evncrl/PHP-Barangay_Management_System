<?php
include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - Barangay Lower Bicutan Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0056b3;
            --secondary-color: #6c757d;
            --accent-color: #ffc107;
            --text-color: #343a40;
            --light-bg: #f8f9fa;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            color: var(--text-color);
            background-color: #f5f5f5;
        }
        
        .navbar {
            background-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand img {
            height: 50px;
        }
        
        .navbar-dark .navbar-nav .nav-link {
            color: white;
            font-weight: 500;
        }
        
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('images/barangay-bicutan.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .hero-section h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .hero-section p {
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .section-title {
            position: relative;
            margin-bottom: 30px;
            padding-bottom: 15px;
            text-align: center;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            display: block;
            width: 100px;
            height: 3px;
            background-color: var(--accent-color);
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
        }
        
        .about-card {
            background-color: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            margin-bottom: 30px;
        }
        
        .about-card:hover {
            transform: translateY(-10px);
        }
        
        .about-card .card-body {
            padding: 25px;
        }
        
        .about-card .card-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .about-icon {
            font-size: 2.5rem;
            color: var(--accent-color);
            margin-bottom: 15px;
        }
        
        .official-card {
            text-align: center;
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .official-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .official-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 15px;
            border: 5px solid var(--light-bg);
        }
        
        .official-name {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .official-position {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }
        
        .stat-card {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px;
            padding: 30px 20px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            height: 100%;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .stat-title {
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        footer {
            background-color: #333;
            color: white;
            padding: 40px 0 20px;
        }
        
        .footer-links h5 {
            color: var(--accent-color);
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .footer-links ul {
            list-style: none;
            padding-left: 0;
        }
        
        .footer-links ul li {
            margin-bottom: 10px;
        }
        
        .footer-links ul li a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer-links ul li a:hover {
            color: white;
        }
        
        .social-links a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            margin-right: 10px;
            transition: all 0.3s ease;
        }
        
        .social-links a:hover {
            background-color: var(--accent-color);
            color: #333;
        }
        
        .copyright {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            color: #aaa;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 60px 0;
            }
            
            .hero-section h1 {
                font-size: 2rem;
            }
            
            .stat-card {
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1>Welcome to Barangay Lower Bicutan</h1>
            <p>Serving the community with dedication and excellence</p>
        </div>
    </section>

    <!-- About Section -->
    <section class="container mb-5">
        <h2 class="section-title">About Our Barangay</h2>
        
        <div class="row">
            <div class="col-md-6">
                <div class="about-card">
                    <div class="card-body">
                        <div class="text-center">
                            <div class="about-icon">
                                <i class="fas fa-map-marked-alt"></i>
                            </div>
                            <h3 class="card-title">Our Location</h3>
                        </div>
                        <p>Barangay Lower Bicutan is located in the southern part of Taguig City, Metro Manila. Strategically positioned near Laguna Lake, it encompasses approximately 4.35 square kilometers of land area. It is bordered by Barangay Upper Bicutan to the north, Barangay Hagonoy to the west, and Laguna Lake to the east.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="about-card">
                    <div class="card-body">
                        <div class="text-center">
                            <div class="about-icon">
                                <i class="fas fa-history"></i>
                            </div>
                            <h3 class="card-title">Our History</h3>
                        </div>
                        <p>Barangay Lower Bicutan has a rich history dating back to the Spanish colonial period. Originally part of the larger settlement of Bicutan, it was later divided into Upper and Lower Bicutan. Over the years, it has transformed from a primarily agricultural community to a thriving residential and commercial area, while still preserving its cultural heritage and community values.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="about-card">
                    <div class="card-body">
                        <div class="text-center">
                            <div class="about-icon">
                                <i class="fas fa-bullseye"></i>
                            </div>
                            <h3 class="card-title">Mission</h3>
                        </div>
                        <p>To provide efficient and responsive governance that empowers residents and fosters community development. We are committed to delivering essential services, maintaining peace and order, and implementing sustainable development programs to enhance the quality of life for all residents of Lower Bicutan.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="about-card">
                    <div class="card-body">
                        <div class="text-center">
                            <div class="about-icon">
                                <i class="fas fa-eye"></i>
                            </div>
                            <h3 class="card-title">Vision</h3>
                        </div>
                        <p>A progressive, peaceful, and self-reliant barangay where residents enjoy inclusive growth, environmental sustainability, and access to quality education, healthcare, and economic opportunities. We envision Lower Bicutan as a model barangay in Taguig City, known for its effective governance and engaged citizenry.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Barangay Officials -->
    <section class="container mb-5">
        <h2 class="section-title">Barangay Officials</h2>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="official-card">
                    <img src="images/placeholder-captain.jpg" alt="Barangay Captain" class="official-img">
                    <h4 class="official-name">Juan M. Dela Cruz</h4>
                    <p class="official-position">Barangay Captain</p>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="official-card">
                    <img src="images/placeholder-kagawad1.jpg" alt="Kagawad" class="official-img">
                    <h4 class="official-name">Maria L. Santos</h4>
                    <p class="official-position">Kagawad - Health & Education</p>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="official-card">
                    <img src="images/placeholder-kagawad2.jpg" alt="Kagawad" class="official-img">
                    <h4 class="official-name">Ricardo D. Reyes</h4>
                    <p class="official-position">Kagawad - Peace & Order</p>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <a href="officials.php" class="btn btn-primary">View All Officials</a>
        </div>
    </section>

    

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5>Contact Information</h5>
                    <p><i class="fas fa-map-marker-alt me-2"></i> Lower Bicutan, Taguig City, Metro Manila</p>
                    <p><i class="fas fa-phone me-2"></i> (02) 8837-XXXX</p>
                    <p><i class="fas fa-envelope me-2"></i> lowerbicutan@taguig.gov.ph</p>
                </div>
                
                
                <div class="col-md-4 mb-4">
                    <h5>Connect With Us</h5>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                    <p class="mt-3">Office Hours:<br>Monday - Friday: 8:00 AM - 5:00 PM<br>Saturday: 8:00 AM - 12:00 PM</p>
                </div>
            </div>
            
            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> Barangay Lower Bicutan Management System. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
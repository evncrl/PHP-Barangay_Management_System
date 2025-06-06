<?php
include 'includes/config.php';
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear admin session if present
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    session_unset(); // Clears all session variables
}

// Fetch user data if logged in
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $query = mysqli_query($conn, "SELECT * FROM residents WHERE user_id = '$userId'");
    $userData = mysqli_fetch_assoc($query);
    
    // Redirect to login if the user is not verified (only for logged-in users)
    if (!isset($userData['verification_status']) || $userData['verification_status'] !== 'Approved') {
        header("Location: /saad/user/login.php");
        exit;
    }
}

// Fetch barangay notes from the database
$sql = "SELECT notes FROM barangay_notes ORDER BY id DESC LIMIT 1"; 
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $barangayNotes = $row['notes'];
} else {
    $barangayNotes = "No barangay notes available.";
}

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --accent-color: #f59e0b;
            --text-color: #1e293b;
            --light-gray: #f8fafc;
            --white: #ffffff;
            --box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
            --border-radius: 12px;
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f1f5f9;
            color: var(--text-color);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        /* Header spacing */
        .header-spacing {
            height: 30px;
            background-color: #f1f5f9;
        }

        .homepage-container {
            display: flex;
            justify-content: space-between;
            gap: 30px;
            padding: 40px;
            max-width: 1280px;
            margin: 0 auto;
        }

        .content {
            flex: 1;
            max-width: 60%; /* Reduced to give more space to right section */
        }

        .right-section {
            width: 38%; /* Increased from 30% to 38% */
        }

        .hero {
            position: relative;
            margin-bottom: 30px;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }

        .hero-image {
            position: relative;
        }

        .hero-image::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0.5));
        }

        .hero-image img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            display: block;
        }

        .hero-content {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 30px;
            color: white;
            z-index: 1;
        }

        .hero-content h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
        }

        .hero-content p {
            font-size: 1.1rem;
            margin-bottom: 20px;
            max-width: 600px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }

        .card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .card-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e2e8f0;
        }

        .card-header h2 {
            color: var(--primary-dark);
            margin: 0;
            font-size: 1.4rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 25px;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .feature-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 25px 20px;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: var(--transition);
            text-decoration: none;
            height: 100%;
            border: 1px solid #e2e8f0;
        }

        .feature-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-light);
        }

        .feature-item i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 15px;
            transition: var(--transition);
        }

        .feature-item:hover i {
            transform: scale(1.1);
            color: var(--primary-light);
        }

        .feature-item h3 {
            font-size: 1.1rem;
            color: var(--text-color);
            margin: 0;
            text-align: center;
            font-weight: 500;
        }

        .initiatives-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .initiative-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 20px 15px;
            background: var(--white);
            border-radius: var(--border-radius);
            transition: var(--transition);
            border: 1px solid #e2e8f0;
        }

        .initiative-item:hover {
            background: linear-gradient(to bottom, #f8fafc, #ffffff);
            border-color: var(--primary-light);
        }

        .initiative-item i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 10px;
            transition: var(--transition);
        }

        .initiative-item:hover i {
            transform: scale(1.1);
            color: var(--primary-light);
        }

        .initiative-item h3 {
            font-size: 0.95rem;
            margin: 0;
            font-weight: 500;
        }

        .barangay-notes {
            background: var(--white);
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            margin-bottom: 30px;
            width: 100%; /* Ensure full width of parent */
        }

        .barangay-notes h3 {
            color: var(--primary-dark);
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--accent-color);
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .barangay-notes p {
            font-size: 15px;
            color: var(--text-color);
            line-height: 1.7;
            margin: 0;
        }

        .appointment-section h2 {
            color: var(--primary-dark);
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--accent-color);
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .appointment-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .appointment-table th {
            background-color: var(--primary-color);
            color: var(--white);
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 15px;
            text-align: left;
        }

        .appointment-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            background-color: var(--white);
        }

        .appointment-table tr:last-child td {
            border-bottom: none;
        }

        .appointment-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .appointment-table tr:hover td {
            background-color: #eef2ff;
        }

        .btn {
            display: inline-block;
            padding: 10px 25px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            font-size: 16px;
            transition: var(--transition);
            text-align: center;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border: 2px solid var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .quick-actions {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: var(--white);
            padding: 30px;
            border-radius: var(--border-radius);
            width: 80%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .close {
            position: absolute;
            top: 20px;
            right: 25px;
            font-size: 24px;
            font-weight: bold;
            color: #94a3b8;
            cursor: pointer;
            transition: var(--transition);
        }

        .close:hover {
            color: var(--primary-color);
        }

        /* Responsive Styles */
        @media (max-width: 1200px) {
            .homepage-container {
                padding: 30px;
            }
        }

        @media (max-width: 992px) {
            .homepage-container {
                flex-direction: column;
            }

            .content, .right-section {
                width: 100%;
                max-width: 100%;
            }

            .feature-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .homepage-container {
                padding: 20px;
            }

            .hero-content h1 {
                font-size: 2rem;
            }

            .hero-content p {
                font-size: 1rem;
            }

            .initiatives-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .feature-grid {
                grid-template-columns: 1fr;
            }

            .initiatives-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .appointment-table th, 
            .appointment-table td {
                padding: 12px 10px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .homepage-container {
                padding: 15px;
            }

            .initiatives-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .hero-content h1 {
                font-size: 1.5rem;
            }

            .modal-content {
                width: 90%;
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<!-- Header spacing -->
<div class="header-spacing"></div>

<!-- Main Homepage Section -->
<div class="homepage-container">
    <!-- Left Section (Main Content) -->
    <div class="content">
        <section class="hero">
            <div class="hero-image">
                <img src="includes/style/lower_bicutan_bgy_hall.jpg" alt="Barangay Management System">
                <div class="hero-content">
                    <h1>Welcome to Barangay Lower Bicutan</h1>
                    <p>A comprehensive platform for efficient community administration and digital service delivery.</p>
                    <div class="quick-actions">
                        <a href="user/document_process.php" class="btn btn-primary">Request Documents</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <h2><i class="fas fa-star-of-life"></i> Key Services</h2>
            </div>
            <div class="card-body">
                <div class="feature-grid">
                    <a href="user/document_process.php" class="feature-item">
                        <i class="fas fa-file-alt"></i>
                        <h3>Document Processing</h3>
                    </a>

                    <a href="user/appointment.php" class="feature-item">
                        <i class="fas fa-calendar-alt"></i>
                        <h3>Schedule Appointments</h3>
                    </a>

                    <a href="user/reservations.php" class="feature-item">
                        <i class="fas fa-building"></i>
                        <h3>Facility Reservations</h3>
                    </a>

                    <a href="user/legal_complaint.php" class="feature-item">
                        <i class="fas fa-gavel"></i>
                        <h3>Legal Complaints</h3>
                    </a>
                </div>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <h2><i class="fas fa-lightbulb"></i> Barangay Initiatives</h2>
            </div>
            <div class="card-body">
                <p>Our Barangay Management System consists of several key components and initiatives aimed at improving community services and governance:</p>
                
                <div class="initiatives-grid">
                    <div class="initiative-item">
                        <i class="fas fa-chart-line"></i>
                        <h3>Digitalization</h3>
                    </div>

                    <div class="initiative-item">
                        <i class="fas fa-users"></i>
                        <h3>Community Participation</h3>
                    </div>

                    <div class="initiative-item">
                        <i class="fas fa-cogs"></i>
                        <h3>Resource Management</h3>
                    </div>

                    <div class="initiative-item">
                        <i class="fas fa-chart-bar"></i>
                        <h3>Monitoring & Evaluation</h3>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Right Section -->
    <div class="right-section">
        <div class="barangay-notes">
            <h3><i class="fas fa-bullhorn"></i> Barangay Announcements</h3>
            <p><?= nl2br(htmlspecialchars($barangayNotes)) ?></p>
        </div>

        <!-- Appointment Table Section -->
        <?php if (isset($_SESSION['user_id'])): ?>
        <section class="card">
            <div class="card-header">
                <h2><i class="fas fa-calendar-check"></i> Your Appointments</h2>
            </div>
            <div class="card-body">
                <table class="appointment-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Purpose</th>
                            <th>Date</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody id="appointments-table-body">
                        <!-- Appointments will be inserted here by JavaScript -->
                    </tbody>
                </table>
            </div>
        </section>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                fetch('/saad/user/fetch_appoinments.php')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error fetching appointments');
                        }
                        return response.json();
                    })
                    .then(data => {
                        let tableBody = document.getElementById('appointments-table-body');
                        tableBody.innerHTML = ''; // Clear existing content

                        if (data.length === 0) {
                            tableBody.innerHTML = '<tr><td colspan="4">No appointments scheduled.</td></tr>';
                        } else {
                            data.forEach(appointment => {
                                let row = document.createElement('tr');
                                row.innerHTML = `
                                    <td>${appointment.type || 'Unknown'}</td>
                                    <td>${appointment.purpose || 'N/A'}</td>
                                    <td>${appointment.appointment_date || 'N/A'}</td>
                                    <td>${appointment.appointment_time ? appointment.appointment_time : '-'}</td>
                                `;
                                tableBody.appendChild(row);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error loading appointments:', error);
                        document.getElementById('appointments-table-body').innerHTML = '<tr><td colspan="4">Failed to load appointments.</td></tr>';
                    });
            });
        </script>
        <?php endif; ?>

        <!-- Modal -->
        <div id="feature-modal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2 id="modal-title"></h2>
                <p id="modal-description"></p>
                <div id="modal-body"></div>
                <a id="modal-link" href="#" class="btn btn-primary" style="display: none; margin-top: 20px;">Proceed</a>
            </div>
        </div>

        <!-- JavaScript for Modal -->
        <script>
            function openModal(title, description, link) {
                document.getElementById('modal-title').innerText = title;
                document.getElementById('modal-description').innerText = description;

                let modalBody = document.getElementById('modal-body');
                let modalLink = document.getElementById('modal-link');

                if (link === 'user/document_process.php') {
                    modalBody.innerHTML = `<iframe src="${link}" style="width:100%; height:400px; border:none; border-radius:8px;"></iframe>`;
                    modalLink.style.display = 'none';
                } else {
                    modalBody.innerHTML = "";
                    modalLink.style.display = 'inline-block';
                    modalLink.href = link;
                }

                document.getElementById('feature-modal').style.display = 'flex';
            }

            // Close modal when clicking the close button
            document.querySelector('.close').onclick = function() {
                document.getElementById('feature-modal').style.display = 'none';
            }

            // Close modal when clicking outside the modal content
            window.onclick = function(event) {
                let modal = document.getElementById('feature-modal');
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            }

            // Close modal when pressing the "Esc" key
            document.addEventListener("keydown", function(event) {
                if (event.key === "Escape") {
                    document.getElementById('feature-modal').style.display = 'none';
                }
            });
        </script>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>
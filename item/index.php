<?php 
ob_start();
include '../includes/adminHeader.php';
include '../includes/config.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /saad/user/login.php");
    exit();
}

// Fetch the latest barangay notes
$query = "SELECT notes FROM barangay_notes ORDER BY updated_at DESC LIMIT 1";
$result = mysqli_query($conn, $query);
$barangayNotes = "";

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $barangayNotes = $row['notes'];
}

// Fetch total population data
$populationQuery = "SELECT SUM(total_family_members) as total_population FROM household";
$populationResult = mysqli_query($conn, $populationQuery);
$totalPopulation = 0;

if ($populationResult && mysqli_num_rows($populationResult) > 0) {
    $populationData = mysqli_fetch_assoc($populationResult);
    $totalPopulation = $populationData['total_population'] ?? 0;
}

// Fetch family distribution data for the graph
$familyDistributionQuery = "SELECT 
    COUNT(CASE WHEN total_family_members BETWEEN 1 AND 2 THEN 1 END) as small_families,
    COUNT(CASE WHEN total_family_members BETWEEN 3 AND 5 THEN 1 END) as medium_families,
    COUNT(CASE WHEN total_family_members >= 6 THEN 1 END) as large_families
FROM household";
$familyDistributionResult = mysqli_query($conn, $familyDistributionQuery);
$familyDistribution = mysqli_fetch_assoc($familyDistributionResult);

// Fetch total users from residents table
$totalUsersQuery = "SELECT COUNT(*) as count FROM residents";
$totalUsersResult = mysqli_query($conn, $totalUsersQuery);
$totalUsers = 0;

if ($totalUsersResult && mysqli_num_rows($totalUsersResult) > 0) {
    $userData = mysqli_fetch_assoc($totalUsersResult);
    $totalUsers = $userData['count'] ?? 0;
}

// Fetch new users registered this week
$newUsersQuery = "SELECT COUNT(*) as count FROM residents 
    WHERE resident_id IN (
        SELECT MAX(resident_id) FROM residents 
        GROUP BY user_id
    ) AND resident_id > (
        SELECT COALESCE(MAX(resident_id), 0) - 100 FROM residents
    )";
$newUsersResult = mysqli_query($conn, $newUsersQuery);
$newUsers = 0;

if ($newUsersResult && mysqli_num_rows($newUsersResult) > 0) {
    $newUserData = mysqli_fetch_assoc($newUsersResult);
    $newUsers = $newUserData['count'] ?? 0;
}

// Fetch completed appointments data
$completedAppointmentsQuery = "SELECT COUNT(*) as count FROM appointments WHERE status = 'Completed'";
$completedAppointmentsResult = mysqli_query($conn, $completedAppointmentsQuery);
$totalCompletedAppointments = 0;

if ($completedAppointmentsResult && mysqli_num_rows($completedAppointmentsResult) > 0) {
    $appointmentData = mysqli_fetch_assoc($completedAppointmentsResult);
    $totalCompletedAppointments = $appointmentData['count'] ?? 0;
}

// Fetch new completed appointments this week
$newCompletedAppointmentsQuery = "SELECT COUNT(*) as count FROM appointments 
    WHERE status = 'Completed' 
    AND updated_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
$newCompletedAppointmentsResult = mysqli_query($conn, $newCompletedAppointmentsQuery);
$newCompletedAppointments = 0;

if ($newCompletedAppointmentsResult && mysqli_num_rows($newCompletedAppointmentsResult) > 0) {
    $newAppointmentData = mysqli_fetch_assoc($newCompletedAppointmentsResult);
    $newCompletedAppointments = $newAppointmentData['count'] ?? 0;
}

// Handle form submission for updating notes
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $newNotes = mysqli_real_escape_string($conn, $_POST['notes']);

    $checkQuery = "SELECT id FROM barangay_notes LIMIT 1";
    $checkResult = mysqli_query($conn, $checkQuery);

    if (mysqli_num_rows($checkResult) > 0) {
        $updateQuery = "UPDATE barangay_notes SET notes = '$newNotes', updated_at = NOW()";
        mysqli_query($conn, $updateQuery);
    } else {
        $insertQuery = "INSERT INTO barangay_notes (notes) VALUES ('$newNotes')";
        mysqli_query($conn, $insertQuery);
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #64748b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
        
        body {
            background-color: #f0f4f8;
            color: #334155;
            line-height: 1.6;
            padding: 2%;
            margin: 2%;
        }
        
        .homepage-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 24px;
            max-width: 1400px;
            margin: 0 auto;
            gap: 24px;
        }
        
        .content {
            flex: 1;
            background: #ffffff;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .right-section {
            width: 32%;
        }
        
        /* Header and Welcome Section */
        .dashboard-header {
            margin-bottom: 24px;
        }
        
        .dashboard-header h1 {
            font-size: 1.75rem;
            color: var(--dark);
            margin-bottom: 8px;
        }
        
        .dashboard-header p {
            color: var(--secondary);
        }
        
        .hero {
            position: relative;
            overflow: hidden;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        
        .hero img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 12px;
        }
        
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.6));
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 30px;
            color: white;
        }
        
        .hero-overlay h2 {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        /* Feature Cards */
        .feature-container {
            margin-top: 32px;
        }
        
        .feature-container h2 {
            font-size: 1.5rem;
            margin-bottom: 16px;
            color: var(--dark);
        }
        
        .feature-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .feature-item {
            background: #ffffff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            text-decoration: none;
            color: inherit;
        }
        
        .feature-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .feature-item i {
            color: var(--primary);
            margin-bottom: 16px;
        }
        
        .feature-item h3 {
            color: var(--dark);
            margin-bottom: 8px;
        }
        
        .feature-item p {
            color: var(--secondary);
            font-size: 0.9rem;
        }
        
        /* Barangay Notes */
        .barangay-notes {
            background: #ffffff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
        }
        
        .barangay-notes h3 {
            color: var(--dark);
            margin-bottom: 16px;
            font-size: 1.2rem;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 8px;
        }
        
        .barangay-notes textarea {
            width: 100%;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            padding: 12px;
            resize: vertical;
            font-size: 0.95rem;
        }
        
        .barangay-notes button {
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            cursor: pointer;
            font-weight: 500;
            margin-top: 12px;
            transition: background-color 0.3s ease;
        }
        
        .barangay-notes button:hover {
            background-color: var(--primary-dark);
        }
        
        /* Pending Items */
        .pending-container {
            display: grid;
            gap: 16px;
        }
        
        .pending-box {
            background: #ffffff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }
        
        .pending-box:hover {
            transform: translateY(-5px);
        }
        
        .pending-box a {
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-decoration: none;
            color: inherit;
        }
        
        .pending-box-left {
            display: flex;
            align-items: center;
        }
        
        .pending-box i {
            color: var(--primary);
            margin-right: 16px;
            font-size: 1.5rem;
        }
        
        .pending-box-content h3 {
            color: var(--dark);
            font-size: 1rem;
            margin-bottom: 4px;
        }
        
        .pending-box p {
            background-color: var(--primary);
            color: white;
            font-weight: bold;
            font-size: 1.1rem;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .population-card {
            grid-column: span 2;
        }
        
        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .stat-card-header h3 {
            font-size: 1rem;
            color: var(--secondary);
        }
        
        .stat-card-header i {
            padding: 12px;
            border-radius: 8px;
            color: white;
        }
        
        .stat-card-body h2 {
            font-size: 1.8rem;
            margin-bottom: 8px;
        }
        
        .stat-card-footer {
            font-size: 0.9rem;
            color: var(--secondary);
        }
        
        .stat-card.users i {
            background-color: #3b82f6;
        }
        
        .stat-card.appointments i {
            background-color: #10b981;
        }
        
        .stat-card.population i {
            background-color: #10b981;
        }
        
        .population-chart-container {
            width: 100%;
            height: 300px;
            margin-top: 20px;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .population-card {
                grid-column: span 2;
            }
        }
        
        @media (max-width: 992px) {
            .homepage-container {
                flex-direction: column;
            }
            
            .content, .right-section {
                width: 100%;
            }
        }
        
        @media (max-width: 768px) {
            .feature-list {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .population-card {
                grid-column: span 1;
            }
        }
        
        @media (max-width: 576px) {
            .feature-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="homepage-container">
    <div class="content">
        <div class="dashboard-header">
            <h1>Admin Dashboard</h1>
            <p>Welcome to the Barangay Management System</p>
        </div>
        
        <section class="hero">
            <img src="../includes/style/lower_bicutan_bgy_hall.jpg" alt="Dashboard Preview">
            <div class="hero-overlay">
                <h2>Barangay Admin Portal</h2>
                <p>Manage your community effectively and efficiently</p>
            </div>
        </section>

        <section class="stats-container">
            <div class="stat-card users">
                <div class="stat-card-header">
                    <h3>Total Resident Users</h3>
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-card-body">
                <h2 id="total-users"><?php echo number_format($totalUsers); ?></h2>
                </div>
                <div class="stat-card-footer">
                <span id="new-users"><?php echo number_format($newUsers); ?></span> new users this week
                </div>
            </div>
            
            <div class="stat-card appointments">
                <div class="stat-card-header">
                    <h3>Completed Appointments</h3>
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-card-body">
                    <h2 id="total-appointments"><?php echo number_format($totalCompletedAppointments); ?></h2>
                </div>
                <div class="stat-card-footer">
                    <span id="new-appointments"><?php echo number_format($newCompletedAppointments); ?></span> this week
                </div>
            </div>
            
            <div class="stat-card population population-card">
                <div class="stat-card-header">
                    <h3>Barangay Population</h3>
                    <i class="fas fa-user-friends"></i>
                </div>
                <div class="stat-card-body">
                    <h2 id="total-population"><?php echo number_format($totalPopulation); ?></h2>
                    <p>Total residents based on household data</p>
                </div>
                <div class="population-chart-container">
                    <canvas id="populationChart"></canvas>
                </div>
                <div class="stat-card-footer">
                    <p>Distribution of family sizes across the barangay</p>
                </div>
            </div>
        </section>

        <section class="feature-container">
            <h2>Quick Access</h2>
            <div class="feature-list">
                <a href="../admin/document_process.php" class="feature-item">
                    <i class="fas fa-file-alt fa-2x"></i>
                    <h3>Document Process</h3>
                    <p>Manage and process document requests</p>
                </a>

                <a href="../admin/appointment.php" class="feature-item">
                    <i class="fas fa-calendar-check fa-2x"></i>
                    <h3>Appointment</h3>
                    <p>View and manage resident appointments</p>
                </a>

                <a href="../admin/reservations.php" class="feature-item">
                    <i class="fas fa-calendar-alt fa-2x"></i>
                    <h3>Reservations</h3>
                    <p>Manage facility and venue bookings</p>
                </a>

                <a href="../admin/inventory.php" class="feature-item">
                    <i class="fas fa-boxes fa-2x"></i>
                    <h3>Inventory</h3>
                    <p>Track barangay resources and supplies</p>
                </a>

                <a href="../admin/legal_complaints.php" class="feature-item">
                    <i class="fas fa-gavel fa-2x"></i>
                    <h3>Legal Complaints</h3>
                    <p>Handle and process resident complaints</p>
                </a>
                
                <a href="../admin/users.php" class="feature-item">
                    <i class="fas fa-users fa-2x"></i>
                    <h3>User Management</h3>
                    <p>Verify and manage system users</p>
                </a>
            </div>
        </section>

        <section class="feature-container">
            <h2>Barangay Services</h2>
            <div class="feature-list">
                <div class="feature-item">
                    <i class="fas fa-chart-line fa-2x"></i>
                    <h3>Efficient Management</h3>
                    <p>Streamlined operations for quick and effective service delivery</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-database fa-2x"></i>
                    <h3>Real-time Data</h3>
                    <p>Access up-to-date information about community activities</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-users fa-2x"></i>
                    <h3>Community Connection</h3>
                    <p>Foster communication between residents and local authorities</p>
                </div>
            </div>
        </section>
    </div>

    <div class="right-section">
        <div class="barangay-notes">
            <h3>Barangay Notes</h3>
            <form method="POST" action="">
                <textarea name="notes" rows="6" placeholder="Enter important announcements, reminders, or notes here..."><?php echo htmlspecialchars($barangayNotes); ?></textarea>
                <button type="submit">Save Notes</button>
            </form>
        </div>
       
        <div class="pending-container">
            <div class="pending-box">
                <a href="../admin/appointment.php">
                    <div class="pending-box-left">
                        <i class="fas fa-calendar-check"></i>
                        <div class="pending-box-content">
                            <h3>Pending Appointments</h3>
                            <small>Awaiting approval</small>
                        </div>
                    </div>
                    <p id="pending-appointments">0</p>
                </a>
            </div>

            <div class="pending-box">
                <a href="../admin/document_process.php">
                    <div class="pending-box-left">
                        <i class="fas fa-file-alt"></i>
                        <div class="pending-box-content">
                            <h3>Pending Documents</h3>
                            <small>Requires processing</small>
                        </div>
                    </div>
                    <p id="pending-documents">0</p>
                </a>
            </div>

            <div class="pending-box">
                <a href="../admin/legal_complaints.php">
                    <div class="pending-box-left">
                        <i class="fas fa-gavel"></i>
                        <div class="pending-box-content">
                            <h3>Pending Complaints</h3>
                            <small>Need attention</small>
                        </div>
                    </div>
                    <p id="pending-complaints">0</p>
                </a>
            </div>

            <div class="pending-box">
                <a href="../admin/reservations.php">
                    <div class="pending-box-left">
                        <i class="fas fa-calendar-alt"></i>
                        <div class="pending-box-content">
                            <h3>Pending Reservations</h3>
                            <small>Awaiting confirmation</small>
                        </div>
                    </div>
                    <p id="pending-reservations">0</p>
                </a>
            </div>

            <div class="pending-box">
                <a href="../admin/users.php">
                    <div class="pending-box-left">
                        <i class="fas fa-user-check"></i>
                        <div class="pending-box-content">
                            <h3>Pending User Verification</h3>
                            <small>Requires verification</small>
                        </div>
                    </div>
                    <p id="pending-users">0</p>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Fetch pending counts
    fetch('/saad/item/fetch_records.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById("pending-appointments").innerText = data.pending_appointments || 0;
            document.getElementById("pending-documents").innerText = data.pending_documents || 0;
            document.getElementById("pending-complaints").innerText = data.pending_complaints || 0;
            document.getElementById("pending-reservations").innerText = data.pending_reservations || 0;
            document.getElementById("pending-users").innerText = data.pending_users || 0;
            
            // Populate stats (if these fields are available in the API response)
            if(data.total_users) document.getElementById("total-users").innerText = data.total_users;
            if(data.new_users) document.getElementById("new-users").innerText = data.new_users;
        })
        .catch(error => {
            console.error("Error fetching data:", error);
        });
        
    // Initialize Population Chart
    const ctx = document.getElementById('populationChart').getContext('2d');
    const populationChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['1-2 Members', '3-5 Members', '6+ Members'],
            datasets: [{
                label: 'Number of Households',
                data: [
                    <?php echo $familyDistribution['small_families']; ?>,
                    <?php echo $familyDistribution['medium_families']; ?>,
                    <?php echo $familyDistribution['large_families']; ?>
                ],
                backgroundColor: [
                    'rgba(37, 99, 235, 0.7)',
                    'rgba(16, 185, 129, 0.7)',
                    'rgba(245, 158, 11, 0.7)'
                ],
                borderColor: [
                    'rgba(37, 99, 235, 1)',
                    'rgba(16, 185, 129, 1)',
                    'rgba(245, 158, 11, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.raw + ' households';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    },
                    title: {
                        display: true,
                        text: 'Number of Households'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Family Size'
                    }
                }
            }
        }
    });
});
</script>
</body>
</html>

<?php ob_end_flush(); ?>
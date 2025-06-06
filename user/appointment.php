<?php
session_start();
include '../includes/config.php'; 
include '../includes/header.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to access this page.");
}

$user_id = $_SESSION['user_id'];

// Fetch resident details based on user_id
$resident_id = null;
$resident_name = null;
$sql = "SELECT resident_id, CONCAT(fname, ' ', mname, ' ', lname) AS resident_name FROM residents WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($resident_id, $resident_name);
$stmt->fetch();
$stmt->close();

// Handle form submission
if (isset($_POST['create'])) {
    $purpose = trim($_POST['purpose']);
    $description = trim($_POST['description']);
    $appointment_date = trim($_POST['appointment_date']);

    if (empty($purpose) || empty($appointment_date) || empty($description)) {
        $error_message = "All fields are required. Please fill out all fields.";
    } else {
        $status = "Pending"; // Default status

        // Insert into appointments table including resident_id and description
        $sql = "INSERT INTO appointments (user_id, resident_id, purpose, description, appointment_date, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iissss", $user_id, $resident_id, $purpose, $description, $appointment_date, $status);
        
        if ($stmt->execute()) {
            $success_message = "Appointment scheduled successfully!";
        } else {
            $error_message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle delete request
if (isset($_POST['delete'])) {
    $appointment_id = $_POST['appointment_id'];

    $delete_sql = "DELETE FROM appointments WHERE appointment_id = ? AND user_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("ii", $appointment_id, $user_id);
    
    if ($stmt->execute()) {
        $success_message = "Appointment deleted successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch user appointments
$result = $conn->query("SELECT * FROM appointments WHERE user_id = $user_id ORDER BY appointment_id DESC");

?>

<!DOCTYPE html>
<html>
<head>
    <title>Appointment Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f7f9fc;
            margin: 0;
            padding: 0;
            color: #333;
            padding-top: 70px;
        }

        .page-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 30px;
            margin-bottom: 30px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            margin-bottom: 25px;
            position: relative;
        }

        h2 {
            font-size: 24px;
            color: #223F61;
            text-align: left;
            margin: 0;
            padding-bottom: 15px;
            position: relative;
        }

        h2::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 3px;
            background-color: #223F61;
        }

        /* Form Styling */
        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #4a5568;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
            box-sizing: border-box;
        }

        .form-control:focus {
            border-color: #223F61;
            box-shadow: 0 0 0 3px rgba(34, 63, 97, 0.2);
            outline: none;
        }

        .form-control[readonly] {
            background-color: #f8f9fa;
            cursor: not-allowed;
            border: 1px solid #e2e8f0;
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
            padding-right: 40px;
        }

        .btn {
            display: inline-block;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 12px 24px;
            font-size: 16px;
            line-height: 1.5;
            border-radius: 8px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .btn-primary {
            color: #fff;
            background-color: #223F61;
            border-color: #223F61;
        }

        .btn-primary:hover {
            background-color: #1a2f4a;
            border-color: #1a2f4a;
        }

        .btn-danger {
            color: #fff;
            background-color: #e53e3e;
            border-color: #e53e3e;
        }

        .btn-danger:hover {
            background-color: #c53030;
            border-color: #c53030;
        }

        .btn-sm {
            padding: 8px 12px;
            font-size: 14px;
        }

        /* Table Styling */
        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 12px;
            overflow: hidden;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background-color: #223F61;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 0.5px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background-color: #f7fafc;
        }

        /* Status Badge */
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-pending {
            background-color: #fbd38d;
            color: #744210;
        }

        .badge-approved {
            background-color: #9ae6b4;
            color: #22543d;
        }

        .badge-completed {
            background-color: #90cdf4;
            color: #2a4365;
        }

        .badge-cancelled {
            background-color: #feb2b2;
            color: #822727;
        }

        /* Alert Styling */
        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
            opacity: 1;
            transition: opacity 0.5s;
        }

        .alert-success {
            background-color: #c6f6d5;
            color: #22543d;
            border-left: 4px solid #48bb78;
        }

        .alert-danger {
            background-color: #fed7d7;
            color: #822727;
            border-left: 4px solid #f56565;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 30px;
            color: #718096;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .page-container {
                padding: 0 15px;
                margin: 20px auto;
            }
            
            .card {
                padding: 20px;
            }
            
            h2 {
                font-size: 20px;
            }
            
            .btn {
                width: 100%;
            }
            
            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        
        <?php if(isset($success_message)): ?>
        <div class="alert alert-success">
            <?php echo $success_message; ?>
        </div>
        <?php endif; ?>
        
        <?php if(isset($error_message)): ?>
        <div class="alert alert-danger">
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>
        
        <!-- Schedule Appointment Card -->
        <div class="card">
    <div class="card-header">
        <h2>Schedule an Appointment</h2>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
            
            <div class="form-group">
                <label for="resident_name">Resident Name:</label>
                <input type="text" id="resident_name" class="form-control" value="<?php echo htmlspecialchars($resident_name); ?>" readonly>
            </div>
            
            <div class="form-group">
                <label for="purpose">Purpose:</label>
                <select id="purpose" name="purpose" class="form-control" required>
                    <option value="">-- Select Purpose --</option>
                    <?php
                    // Fetch office names from the offices table
                    $office_sql = "SELECT office_name FROM offices";
                    $office_result = $conn->query($office_sql);

                    if ($office_result->num_rows > 0) {
                        while ($office_row = $office_result->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($office_row['office_name']) . '">' 
                                . htmlspecialchars($office_row['office_name']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="description">Appointment Description:</label>
                <textarea id="description" name="description" class="form-control" placeholder="Please provide details about your appointment" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="appointment_date">Appointment Date and Time:</label>
                <input type="datetime-local" id="appointment_date" name="appointment_date" class="form-control" required>
            </div>
            
            <button type="submit" name="create" class="btn btn-primary">Schedule Appointment</button>
        </form>
    </div>
</div>
        
        <!-- Appointments List Card -->
        <div class="card">
    <div class="card-header">
        <h2>My Appointments</h2>
    </div>
    <div class="card-body">
        <?php if($result->num_rows > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Resident</th>
                        <th>Purpose</th>
                        <th>Description</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['appointment_id']; ?></td>
                        <td><?php echo htmlspecialchars($resident_name); ?></td>
                        <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                        <td><?php echo isset($row['description']) ? htmlspecialchars($row['description']) : 'N/A'; ?></td>
                        <td><?php echo date('M d, Y - h:i A', strtotime($row['appointment_date'])); ?></td>
                        <td>
                            <span class="badge badge-<?php echo strtolower($row['status']); ?>">
                                <?php echo $row['status']; ?>
                            </span>
                        </td>
                        <td><?php echo isset($row['remarks']) ? htmlspecialchars($row['remarks']) : 'N/A'; ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this appointment?');">
                                <input type="hidden" name="appointment_id" value="<?php echo $row['appointment_id']; ?>">
                                <button type="submit" name="delete" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <p>You have no scheduled appointments. Use the form above to schedule one.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

    <!-- JavaScript for enhanced user experience -->
    <script>
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 500);
                }, 5000);
            });
            
            // Set minimum date for appointment scheduling to current date
            const appointmentDateInput = document.getElementById('appointment_date');
            if (appointmentDateInput) {
                const now = new Date();
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                
                const minDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
                appointmentDateInput.setAttribute('min', minDateTime);
            }
        });
    </script>
</body>
</html>
<?php
session_start();
include_once '../includes/adminHeader.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include '../includes/config.php'; 

require '../src/Exception.php';  
require '../src/PHPMailer.php';   
require '../src/SMTP.php';      

// Ensure admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied.");
}

// Add a new office

    // First, alter the offices table to add soft delete columns if they don't exist
    $alter_query = "ALTER TABLE offices 
                    ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL,
                    ADD COLUMN IF NOT EXISTS deleted_by INT NULL";
    $conn->query($alter_query);

    // Add a new office
    if (isset($_POST['add_office'])) {
        $new_office = trim($_POST['new_office']);

        if (!empty($new_office)) {
            // Check if office already exists (non-deleted only)
            $check_sql = "SELECT COUNT(*) FROM offices WHERE office_name = ? AND deleted_at IS NULL";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param("s", $new_office);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            if ($count > 0) {
                $message = "Office already exists!";
                $message_class = "error";
            } else {
                // Check if this is a restoration of a soft-deleted office
                $check_deleted_sql = "SELECT office_id FROM offices WHERE office_name = ? AND deleted_at IS NOT NULL";
                $stmt = $conn->prepare($check_deleted_sql);
                $stmt->bind_param("s", $new_office);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // Restore the soft-deleted office
                    $row = $result->fetch_assoc();
                    $restore_sql = "UPDATE offices SET deleted_at = NULL, deleted_by = NULL WHERE office_id = ?";
                    $stmt = $conn->prepare($restore_sql);
                    $stmt->bind_param("i", $row['office_id']);
                    if ($stmt->execute()) {
                        $message = "Office restored successfully!";
                        $message_class = "success";
                    } else {
                        $message = "Error restoring office: " . $stmt->error;
                        $message_class = "error";
                    }
                } else {
                    // Insert new office
                    $sql = "INSERT INTO offices (office_name) VALUES (?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("s", $new_office);
                    if ($stmt->execute()) {
                        $message = "Office added successfully!";
                        $message_class = "success";
                    } else {
                        $message = "Error adding office: " . $stmt->error;
                        $message_class = "error";
                    }
                }
                $stmt->close();
            }
        } else {
            $message = "Office name cannot be empty!";
            $message_class = "error";
        }
    }

    // Soft delete an office
    if (isset($_POST['remove_office'])) {
        $office_id = $_POST['delete_office'];

        // Check if the office exists and isn't already deleted
        $check_sql = "SELECT COUNT(*) FROM offices WHERE office_id = ? AND deleted_at IS NULL";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("i", $office_id);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count == 0) {
            $message = "Office not found or already deleted!";
            $message_class = "error";
        } else {
            // Soft delete office (set deleted_at timestamp and deleted_by user)
            $sql = "UPDATE offices SET deleted_at = NOW(), deleted_by = ? WHERE office_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $_SESSION['user_id'], $office_id);
            if ($stmt->execute()) {
                $message = "Office archived successfully!";
                $message_class = "success";
            } else {
                $message = "Error archiving office: " . $stmt->error;
                $message_class = "error";
            }
            $stmt->close();
        }
    }

    // Handle Status Update
    if (isset($_POST['update'])) {
        $appointment_id = $_POST['appointment_id'];
        $status = $_POST['status'];
        $remarks = $_POST['remarks'];

        // Fetch user email
        $sql = "SELECT u.email, CONCAT(r.fname, ' ', r.mname, ' ', r.lname) AS resident_name, 
                    a.purpose, a.description, a.appointment_date 
                FROM appointments a
                JOIN users u ON a.user_id = u.user_id
                JOIN residents r ON a.resident_id = r.resident_id
                WHERE a.appointment_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $resident = $result->fetch_assoc();  
        $stmt->close();

        if ($resident) {
            $email = $resident['email'];
            $resident_name = $resident['resident_name'];
            $purpose = $resident['purpose'];
            $description = $resident['description'];
            $appointment_date = $resident['appointment_date'];

            $sql = "UPDATE appointments SET status = ?, remarks = ?, updated_at = NOW() WHERE appointment_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $status, $remarks, $appointment_id);

            if ($stmt->execute()) {
                $message = "Appointment Updated Successfully!";
                $message_class = "success";

                // Send Email Notification
                sendEmail($email, $resident_name, $status, $remarks, $purpose, $appointment_date);
            } else {
                $message = "Error: " . $stmt->error;
                $message_class = "error";
            }
            $stmt->close();
        } else {
            $message = "Error: No matching appointment found.";
            $message_class = "error";
        }
    }

    // Function to send email
    function sendEmail($to, $name, $status, $remarks, $purpose, $appointment_date) {
        $mail = new PHPMailer(true);

        try {
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'romerosfragrance@gmail.com';
            $mail->Password = 'rrzy yexb qevi hxmw';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Sender & Recipient
            $mail->setFrom('romerosfragrance@gmail.com', 'Barangay Lower Bicutan');
            $mail->addAddress($to, $name);

            // Email Content
            $mail->isHTML(true);
            $mail->Subject = "Appointment Status Update";
            $mail->Body = "
                <h3>Dear $name,</h3>
                <p>Your appointment status has been updated.</p>
                <p><b>Purpose:</b> $purpose</p>
                <p><b>Appointment Date:</b> $appointment_date</p>
                <p><b>Status:</b> $status</p>
                <p><b>Remarks:</b> $remarks</p>
                <p>Thank you.</p>
            ";

            $mail->send();
        } catch (Exception $e) {
            echo "<script>alert('Email could not be sent. Error: {$mail->ErrorInfo}');</script>";
        }
    }

    // Fetch All Appointments
    $result = $conn->query("SELECT appointments.*, CONCAT(residents.fname, ' ', residents.mname, ' ', residents.lname) AS resident_name 
                            FROM appointments 
                            JOIN residents ON appointments.user_id = residents.user_id
                            ORDER BY appointment_id DESC");

    // Fetch only non-deleted offices
    $offices = $conn->query("SELECT * FROM offices WHERE deleted_at IS NULL");

    ?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Appointments</title>
    <style>
        :root {
            --primary-color: #043060;
            --primary-light: #0c4a8e;
            --primary-dark: #032040;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --white: #ffffff;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--gray-100);
            margin: 2%;
            padding: 2%;
            color: var(--dark-color);
        }
        
        .content-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
            transition: margin 0.3s;
        }
        
        .page-header {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--gray-300);
        }
        
        .page-header h2 {
            margin: 0;
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .card {
            background: var(--white);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 15px 20px;
            font-weight: 600;
            font-size: 18px;
            border-radius: 8px 8px 0 0;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -10px;
            margin-left: -10px;
            align-items: center;
        }
        
        .form-group {
            flex: 1;
            padding: 0 10px;
            margin-bottom: 15px;
            min-width: 200px;
        }
        
        .form-control {
            display: block;
            width: 100%;
            padding: 12px 15px;
            font-size: 14px;
            line-height: 1.5;
            color: var(--dark-color);
            background-color: var(--white);
            border: 1px solid var(--gray-400);
            border-radius: 4px;
            transition: border-color 0.15s ease-in-out;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(4, 48, 96, 0.25);
        }
        
        .btn {
            display: inline-block;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 12px 15px;
            font-size: 14px;
            line-height: 1.5;
            border-radius: 4px;
            transition: all 0.15s ease-in-out;
            cursor: pointer;
        }
        
        .btn-primary {
            color: var(--white);
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-light);
            border-color: var(--primary-light);
        }
        
        .btn-danger {
            color: var(--white);
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .btn-danger:hover {
            background-color: #bd2130;
            border-color: #bd2130;
        }
        
        .btn-sm {
            padding: 8px 12px;
            font-size: 13px;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 0;
        }
        
        .table th {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }
        
        .table td {
            padding: 15px;
            border-bottom: 1px solid var(--gray-300);
            font-size: 14px;
            vertical-align: middle;
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        .table tr:hover {
            background-color: rgba(4, 48, 96, 0.03);
        }
        
        .status-pending {
            background-color: rgba(255, 193, 7, 0.1);
            color: #856404;
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .status-approved {
            background-color: rgba(40, 167, 69, 0.1);
            color: #155724;
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .status-completed {
            background-color: rgba(23, 162, 184, 0.1);
            color: #0c5460;
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .status-cancelled {
            background-color: rgba(220, 53, 69, 0.1);
            color: #721c24;
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .update-form {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
            transition: opacity 0.5s;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .form-row {
                flex-direction: column;
            }
            
            .form-group {
                width: 100%;
                padding: 0;
            }
            
            .update-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 10px;
            }
        }
        
        @media (max-width: 768px) {
            .content-wrapper {
                padding: 15px 10px;
            }
            
            .table th, .table td {
                padding: 10px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>

<div class="content-wrapper">
    <?php if (isset($message)): ?>
    <div class="alert alert-<?php echo $message_class; ?>">
        <?php echo $message; ?>
    </div>
    <?php endif; ?>

    <!-- Add link to view archived offices -->
    <div style="text-align: center; margin-bottom: 20px;">
    <a href="/saad/admin/archived_offices.php" class="btn btn-secondary">View Archived Offices</a>
</div>


    <div class="card">
        <div class="card-header">
            Manage Barangay Offices
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <div class="form-row">
                            <div class="form-group" style="flex: 2;">
                                <input type="text" class="form-control" name="new_office" placeholder="Enter new office name" required>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <button type="submit" class="btn btn-primary" name="add_office" style="width: 100%;">Add Office</button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="form-group">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" onsubmit="return confirm('Are you sure you want to archive this office?');">
                        <div class="form-row">
                            <div class="form-group" style="flex: 2;">
                                <select class="form-control" name="delete_office" required>
                                    <option value="">Select office to archive</option>
                                    <?php 
                                    // Reset pointer to the beginning
                                    $offices->data_seek(0);
                                    while ($office = $offices->fetch_assoc()) { 
                                    ?>
                                        <option value="<?php echo $office['office_id']; ?>"><?php echo htmlspecialchars($office['office_name']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <button type="submit" class="btn btn-danger" name="remove_office" style="width: 100%;">Archive Office</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="page-header">
        <h2>Appointment Management</h2>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="15%">Resident Name</th>
                            <th width="15%">Purpose</th>
                            <th width="20%">Description</th> <!-- New Column -->
                            <th width="15%">Appointment Date</th>
                            <th width="10%">Status</th>
                            <th width="15%">Remarks</th>
                            <th width="25%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['appointment_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['resident_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                                    <td><?php echo htmlspecialchars($row['description']); ?></td> <!-- Display Description -->
                                    <td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
                                    <td>
                                        <span class="status-<?php echo strtolower($row['status']); ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['remarks']); ?></td>
<td>
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="update-form">
        <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($row['appointment_id']); ?>">
        <select class="form-control" name="status" style="width: 120px;">
            <option value="Pending" <?php echo ($row['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
            <option value="Approved" <?php echo ($row['status'] == 'Approved') ? 'selected' : ''; ?>>Approved</option>
            <option value="Completed" <?php echo ($row['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
            <option value="Cancelled" <?php echo ($row['status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
        </select>
        
        <!-- Add this text input for remarks -->
        <input type="text" class="form-control" name="remarks" placeholder="Add remarks" value="<?php echo htmlspecialchars($row['remarks']); ?>" style="width: 150px;">
                                          
                                            <button type="submit" class="btn btn-primary btn-sm" name="update">Update</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center;">No appointments found</td> <!-- Updated colspan to 8 -->
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Auto-hide alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            }, 5000);
        });
    });
</script>

</body>
</html>

<?php ob_end_flush(); ?>
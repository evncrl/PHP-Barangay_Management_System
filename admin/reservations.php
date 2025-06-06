<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../includes/config.php';
include '../includes/adminHeader.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../src/Exception.php';
require '../src/PHPMailer.php';
require '../src/SMTP.php';


// Ensure the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo "<p style='color: red; text-align: center; font-size: 18px;'>You must be logged in as an admin to access this page. <a href='/saad/user/login.php'>Log in here</a>.</p>";
    exit();
}
// Function to send email notification
function sendReservationStatusEmail($email, $reservation_id, $status, $facility_name, $equipment_name, $start_date, $end_date) {
    global $conn;
    
    // Get resident's name from residents table
    $name_sql = "SELECT CONCAT(r.fname, ' ', r.lname) AS full_name 
                 FROM reservations res
                 JOIN residents r ON res.user_id = r.user_id
                 WHERE res.reservation_id = ?";
    $stmt = $conn->prepare($name_sql);
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $name = $row['full_name'] ?? 'Resident';
    
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

        // Sender and recipient
        $mail->setFrom('romerosfragrance@gmail.com', 'Barangay Lower Bicutan');
        $mail->addAddress($email, $name);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Reservation Status Update';
        $mail->Body = "
            <h3>Dear $name,</h3>
            <p>Your reservation status has been updated:</p>
            <table border='0' cellpadding='5' cellspacing='0'>
                <tr><td><strong>Reservation ID:</strong></td><td>$reservation_id</td></tr>
                <tr><td><strong>Facility:</strong></td><td>" . ($facility_name ?: 'None') . "</td></tr>
                <tr><td><strong>Equipment:</strong></td><td>" . ($equipment_name ?: 'None') . "</td></tr>
                <tr><td><strong>Start Date:</strong></td><td>$start_date</td></tr>
                <tr><td><strong>End Date:</strong></td><td>$end_date</td></tr>
                <tr><td><strong>New Status:</strong></td><td>$status</td></tr>
            </table>
            <p>You can check your account for more details.</p>
            <p>Thank you,<br>Barangay Lower Bicutan</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Initialize message variable
$message = '';

// Handle actions (Approve, Reject, Delete, Return)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['edit_facilities'])) {
        header("Location: ../admin/inventory.php");
        exit();
    }
    
    if (isset($_POST['reservation_id']) && is_numeric($_POST['reservation_id'])) {
        $reservation_id = $_POST['reservation_id'];
        $user_id = $_SESSION['user_id']; // Get current user ID for soft delete tracking

        // Fetch reservation details with resident's name from residents table
        $sql = "SELECT r.*, f.facility_name, e.equipment_name, u.email, 
                res.fname, res.lname 
                FROM reservations r
                LEFT JOIN facilities f ON r.facility_id = f.facility_id
                LEFT JOIN equipment e ON r.equipment_id = e.equipment_id
                LEFT JOIN users u ON r.user_id = u.user_id
                LEFT JOIN residents res ON u.user_id = res.user_id
                WHERE r.reservation_id = ? AND r.deleted_at IS NULL";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $reservation = $result->fetch_assoc();
        $stmt->close();

        if ($reservation) {
            $equipment_id = $reservation['equipment_id'];
            $quantity_requested = $reservation['quantity_requested'];
            $facility_id = $reservation['facility_id'];
            $user_email = $reservation['email'];
            $facility_name = $reservation['facility_name'];
            $equipment_name = $reservation['equipment_name'];
            $start_date = date('M j, Y g:i A', strtotime($reservation['reservation_date']));
            $end_date = date('M j, Y g:i A', strtotime($reservation['end_date']));

            // APPROVE RESERVATION
            if (isset($_POST['approve'])) {
                // Mark Facility as Reserved (if applicable)
                if ($facility_id > 0) {
                    $update_facility_sql = "UPDATE facilities SET status = 'Reserved' WHERE facility_id = ?";
                    $stmt = $conn->prepare($update_facility_sql);
                    $stmt->bind_param("i", $facility_id);
                    $stmt->execute();
                    $stmt->close();
                }

                // Reduce Equipment Quantity (if applicable)
                if ($equipment_id > 0 && $quantity_requested > 0) {
                    $update_equipment_sql = "UPDATE equipment SET quantity = quantity - ? WHERE equipment_id = ?";
                    $stmt = $conn->prepare($update_equipment_sql);
                    $stmt->bind_param("ii", $quantity_requested, $equipment_id);
                    $stmt->execute();
                    $stmt->close();
                }

                // Update Reservation Status to Approved
                $update_reservation_sql = "UPDATE reservations SET status = 'Approved' WHERE reservation_id = ?";
                $stmt = $conn->prepare($update_reservation_sql);
                $stmt->bind_param("i", $reservation_id);
                $stmt->execute();
                $stmt->close();

                // Send approval email
                $email_sent = sendReservationStatusEmail(
                    $user_email,
                    $reservation_id,
                    'Approved',
                    $facility_name,
                    $equipment_name,
                    $start_date,
                    $end_date
                );

                $message = "<p style='color: green;'>Reservation approved successfully." . ($email_sent ? "" : " Email notification failed to send.") . "</p>";
            }

            // REJECT RESERVATION
            elseif (isset($_POST['reject'])) {
                $sql = "UPDATE reservations SET status = 'Rejected' WHERE reservation_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $reservation_id);
                $stmt->execute();
                $stmt->close();

                // Send rejection email
                $email_sent = sendReservationStatusEmail(
                    $user_email,
                    $reservation_id,
                    'Rejected',
                    $facility_name,
                    $equipment_name,
                    $start_date,
                    $end_date
                );

                $message = "<p style='color: green;'>Reservation rejected." . ($email_sent ? "" : " Email notification failed to send.") . "</p>";
            }

            // SOFT DELETE RESERVATION
            elseif (isset($_POST['delete'])) {
                $sql = "UPDATE reservations SET 
                        deleted_at = NOW(), 
                        deleted_by = ?
                        WHERE reservation_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $user_id, $reservation_id);
                
                if ($stmt->execute()) {
                    $message = "<p style='color: green;'>Reservation archived successfully.</p>";
                } else {
                    $message = "<p style='color: red;'>Error archiving reservation.</p>";
                }
                $stmt->close();
            }

            // RETURN FACILITY/EQUIPMENT
            elseif (isset($_POST['return'])) {
                // Return Equipment if Reserved
                if ($equipment_id > 0 && $quantity_requested > 0) {
                    $update_equipment_sql = "UPDATE equipment SET quantity = quantity + ? WHERE equipment_id = ?";
                    $stmt = $conn->prepare($update_equipment_sql);
                    $stmt->bind_param("ii", $quantity_requested, $equipment_id);
                    $stmt->execute();
                    $stmt->close();
                }

                // Mark Facility as Available if Reserved
                if ($facility_id > 0) {
                    $update_facility_sql = "UPDATE facilities SET status = 'Available' WHERE facility_id = ?";
                    $stmt = $conn->prepare($update_facility_sql);
                    $stmt->bind_param("i", $facility_id);
                    $stmt->execute();
                    $stmt->close();
                }

                // Update Reservation Status to 'Returned'
                $update_reservation_sql = "UPDATE reservations SET status = 'Returned' WHERE reservation_id = ?";
                $stmt = $conn->prepare($update_reservation_sql);
                $stmt->bind_param("i", $reservation_id);
                $stmt->execute();
                $stmt->close();

                // Send return confirmation email
                $email_sent = sendReservationStatusEmail(
                    $user_email,
                    $reservation_id,
                    'Returned',
                    $facility_name,
                    $equipment_name,
                    $start_date,
                    $end_date
                );

                $message = "<p style='color: green;'>Reservation successfully returned." . ($email_sent ? "" : " Email notification failed to send.") . "</p>";
            }
        } else {
            $message = "<p style='color: red;'>Reservation not found.</p>";
        }
    } else {
        $message = "<p style='color: red;'>Invalid reservation ID.</p>";
    }
}

// Calendar functionality
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Get first day of month and total days
$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$totalDays = date('t', $firstDayOfMonth);
$firstDayOfWeek = date('w', $firstDayOfMonth); // 0=Sunday, 6=Saturday

// Get reservations for the month (excluding Pending and deleted)
$startDate = date('Y-m-01', $firstDayOfMonth);
$endDate = date('Y-m-t', $firstDayOfMonth);

$calendarReservations = [];
$sql = "SELECT r.*, f.facility_name, e.equipment_name, u.email 
        FROM reservations r
        LEFT JOIN facilities f ON r.facility_id = f.facility_id
        LEFT JOIN equipment e ON r.equipment_id = e.equipment_id
        LEFT JOIN users u ON r.user_id = u.user_id
        WHERE r.deleted_at IS NULL
        AND r.status != 'Pending'
        AND ((r.reservation_date BETWEEN ? AND ?) OR (r.end_date BETWEEN ? AND ?))
        ORDER BY r.reservation_date, r.end_date";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $startDate, $endDate, $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();


while ($row = $result->fetch_assoc()) {
    $start = new DateTime($row['reservation_date']);
    $end = new DateTime($row['end_date']);
    
    // Add all days in the reservation range
    for ($date = clone $start; $date <= $end; $date->modify('+1 day')) {
        $day = $date->format('j');
        if (!isset($calendarReservations[$day])) {
            $calendarReservations[$day] = [];
        }
        
        $type = $row['facility_id'] ? 'facility' : 'equipment';
        $name = $row['facility_id'] ? $row['facility_name'] : $row['equipment_name'];
        
        // Format time for display
        $start_time = $start->format('H:i');
        $end_time = $end->format('H:i');
        
        $calendarReservations[$day][] = [
            'type' => $type,
            'name' => $name,
            'status' => $row['status'],
            'time' => $start_time . ' - ' . $end_time,
            'user' => $row['email'],
            'purpose' => $row['purpose'],
            'reservation_id' => $row['reservation_id']
        ];
    }
}
$stmt->close();

// Fetch all non-deleted reservations after any updates
// Fetch all non-deleted reservations after any updates
$sql = "
    SELECT r.*, 
           f.facility_name, 
           e.equipment_name,
           u.email
    FROM reservations r
    LEFT JOIN facilities f ON r.facility_id = f.facility_id
    LEFT JOIN equipment e ON r.equipment_id = e.equipment_id
    LEFT JOIN users u ON r.user_id = u.user_id
    WHERE r.deleted_at IS NULL
    ORDER BY r.reservation_id DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$reservations = $stmt->get_result();
?>

<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Reservations</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>includes/style/style.css">
    <style>
        body {
            background-color: #223F61;
            color: black;
        }
        .admin-container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 70px;
            background: #ffffff;
            box-shadow: 0 10px 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        h2 {
            text-align: center;
            font-size: 24px;
            color: #223F61;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
        }
        th, td {
            padding: 8px;
            text-align: center;
            border: 1px solid #ddd;
        }
        th {
            background-color: #223F61;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f8f8f8;
        }
        tr:hover {
            background-color: #e1e6f0;
        }
        .status-pending { color: orange; font-weight: bold; }
        .status-approved { color: green; font-weight: bold; }
        .status-rejected { color: red; font-weight: bold; }
        .status-returned { color: blue; font-weight: bold; }
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        button, .edit-btn, .archive-btn {
            padding: 9px 12px;
            font-size: 14px;
            cursor: pointer;
            border: none;
            border-radius: 600px;
            text-decoration: none;
            display: inline-block;
        }
        .approve-btn { background-color: #28a745; color: white; }
        .reject-btn { background-color: #dc3545; color: white; }
        .delete-btn { background-color: #ff3333; color: white; }
        .return-btn { background-color: #007bff; color: white; }
        .edit-btn { background-color: #1E90FF; color: white; }
        .archive-btn { background-color: #6c757d; color: white; }
        button:hover, .edit-btn:hover, .archive-btn:hover {
            opacity: 0.8;
        }
        .message {
            text-align: center;
            margin-bottom: 20px;
        }
        .top-button-group {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        .bottom-button-group {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }
        
        /* Enhanced Calendar Styles */
        .calendar-container {
            margin: 30px 0;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
            padding: 20px;
            width: 100%;
            overflow-x: auto;
        }
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .calendar-title {
            font-size: 20px;
            color: #223F61;
            font-weight: 600;
            margin: 0;
        }
        .calendar-nav {
            display: flex;
            gap: 10px;
        }
        .calendar-nav a {
            padding: 8px 15px;
            background: linear-gradient(160deg, #2F5DC5, #3BBEE6);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            transition: 0.3s;
        }
        .calendar-nav a:hover {
            background: linear-gradient(160deg, #1a45a0, #2fa5d4);
            transform: translateY(-2px);
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(100px, 1fr));
            gap: 5px;
            width: 100%;
            min-width: 700px;
        }
        .calendar-day-header {
            text-align: center;
            font-weight: 600;
            padding: 10px;
            background: #223F61;
            color: white;
            font-size: 14px;
        }
        .calendar-day {
            min-height: 100px;
            border: 1px solid #ddd;
            padding: 8px;
            position: relative;
            background: white;
        }
        .calendar-day.empty {
            background-color: #f9f9f9;
            border: 1px solid #eee;
        }
        .calendar-date {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        /* Enhanced calendar event styling */
        .calendar-event {
            font-size: 12px;
            color: white;
            padding: 4px 6px;
            border-radius: 4px;
            margin-bottom: 4px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: normal;
            line-height: 1.3;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .calendar-event:hover {
            transform: scale(1.02);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .calendar-event-content {
            display: flex;
            flex-direction: column;
        }
        
        .calendar-event-title {
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .calendar-event-details {
            font-size: 10px;
            display: flex;
            flex-direction: column;
        }
        
        .calendar-event-time {
            font-style: italic;
        }
        
        .calendar-event-user {
            font-size: 9px;
            margin-top: 2px;
            opacity: 0.9;
        }
        
        /* Color coding for different statuses */
        .calendar-event.status-approved {
            background: #4CAF50;
        }
        
        .calendar-event.status-pending {
            background: #FF9800;
        }
        
        .calendar-event.status-rejected {
            background: #F44336;
        }
        
        .calendar-event.status-returned {
            background: #2196F3;
        }
        
        /* Tooltip styling */
        .tooltip {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        
        .tooltip .tooltiptext {
            visibility: hidden;
            width: 200px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 8px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 12px;
        }
        
        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
        
        .current-day {
            background-color: rgba(59, 190, 230, 0.1);
            border: 2px solid #3BBEE6;
        }
        
        @media (max-width: 768px) {
            .calendar-container {
                padding: 15px;
            }
            .calendar-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .calendar-nav {
                width: 100%;
                justify-content: space-between;
            }
            .calendar-nav a {
                flex: 1;
                text-align: center;
            }
            .calendar-day {
                min-height: 80px;
                padding: 5px;
            }
            .calendar-event {
                font-size: 11px;
                padding: 2px 4px;
            }
            .tooltip .tooltiptext {
                width: 150px;
                font-size: 10px;
            }
        }
    </style>
</head>
<body>

<div class="admin-container">
    <h2>Manage Reservations</h2>
    
    <?php if (!empty($message)): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="top-button-group">
        <a href="../admin/archived_reservations.php" class="archive-btn">View Archived Reservations</a>
        <a href="../admin/inventory.php" class="edit-btn">Edit Facilities and Equipments</a>
    </div>

    <!-- Calendar Section -->
    <div class="calendar-container">
        <h3>Reservation Calendar</h3>
        <div class="calendar-header">
            <h3 class="calendar-title"><?php echo date('F Y', $firstDayOfMonth); ?></h3>
            <div class="calendar-nav">
                <a href="?month=<?php echo $month-1 < 1 ? 12 : $month-1; ?>&year=<?php echo $month-1 < 1 ? $year-1 : $year; ?>" class="btn-prev">Previous</a>
                <a href="?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" class="btn-today">Today</a>
                <a href="?month=<?php echo $month+1 > 12 ? 1 : $month+1; ?>&year=<?php echo $month+1 > 12 ? $year+1 : $year; ?>" class="btn-next">Next</a>
            </div>
        </div>
        
        <div class="calendar-grid">
            <!-- Day headers -->
            <div class="calendar-day-header">Sun</div>
            <div class="calendar-day-header">Mon</div>
            <div class="calendar-day-header">Tue</div>
            <div class="calendar-day-header">Wed</div>
            <div class="calendar-day-header">Thu</div>
            <div class="calendar-day-header">Fri</div>
            <div class="calendar-day-header">Sat</div>
            
            <!-- Empty days at start of month -->
            <?php for ($i = 0; $i < $firstDayOfWeek; $i++): ?>
                <div class="calendar-day empty"></div>
            <?php endfor; ?>
            
            <!-- Days of the month -->
            <?php for ($day = 1; $day <= $totalDays; $day++): ?>
                <?php 
                $isCurrentDay = ($day == date('j') && $month == date('n') && $year == date('Y'));
                $hasReservations = isset($calendarReservations[$day]);
                ?>
                <div class="calendar-day <?php echo $isCurrentDay ? 'current-day' : ''; ?>">
                    <div class="calendar-date"><?php echo $day; ?></div>
                    
                    <?php if ($hasReservations): ?>
                        <?php foreach ($calendarReservations[$day] as $event): ?>
                            <div class="tooltip">
                                <div class="calendar-event <?php echo $event['type']; ?> status-<?php echo strtolower($event['status']); ?>">
                                    <div class="calendar-event-content">
                                        <div class="calendar-event-title">
                                            <?php echo htmlspecialchars($event['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                        <div class="calendar-event-details">
                                            <span class="calendar-event-time">
                                                <?php 
                                                // Convert time to AM/PM format
                                                $times = explode(' - ', $event['time']);
                                                $start_time = date('g:i A', strtotime($times[0]));
                                                $end_time = date('g:i A', strtotime($times[1]));
                                                echo htmlspecialchars($start_time . ' - ' . $end_time, ENT_QUOTES, 'UTF-8'); 
                                                ?>
                                            </span>
                                            <span class="calendar-event-user">
                                                <?php echo htmlspecialchars($event['user'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <span class="tooltiptext">
                                    <strong>Reservation ID:</strong> <?php echo htmlspecialchars($event['reservation_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?><br>
                                    <strong>Purpose:</strong> <?php echo htmlspecialchars($event['purpose'] ?? '', ENT_QUOTES, 'UTF-8'); ?><br>
                                    <strong>Status:</strong> <?php echo htmlspecialchars($event['status'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
            
            <!-- Empty days at end of month -->
            <?php 
            $lastDayOfWeek = date('w', mktime(0, 0, 0, $month, $totalDays, $year));
            $remainingDays = 6 - $lastDayOfWeek;
            for ($i = 0; $i < $remainingDays; $i++): ?>
                <div class="calendar-day empty"></div>
            <?php endfor; ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Reservation ID</th>
                <th>User</th>
                <th>Purpose</th>
                <th>Facility</th>
                <th>Equipment</th>
                <th>Quantity</th>
                <th>Start Date/Time</th>
                <th>End Date/Time</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $reservations->fetch_assoc()) { 
                // Convert datetime to AM/PM format
                $start_datetime = date('Y-m-d g:i A', strtotime($row['reservation_date']));
                $end_datetime = date('Y-m-d g:i A', strtotime($row['end_date']));
                ?>
                <tr>
                    <td><?php echo $row['reservation_id']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo $row['purpose']; ?></td>
                    <td><?php echo $row['facility_name'] ?: '-'; ?></td>
                    <td><?php echo $row['equipment_name'] ?: '-'; ?></td>
                    <td><?php echo $row['quantity_requested'] ?: '-'; ?></td>
                    <td><?php echo $start_datetime; ?></td>
                    <td><?php echo $end_datetime; ?></td>
                    <td class="status-<?php echo strtolower($row['status']); ?>"><?php echo $row['status']; ?></td>
                    <td class="action-buttons">
                        <form method="POST" onsubmit="return confirmAction('<?php echo $row['status'] === 'Pending' ? 'approve/reject' : ($row['status'] === 'Approved' ? 'return' : 'archive'); ?>')">
                            <input type="hidden" name="reservation_id" value="<?php echo $row['reservation_id']; ?>">
                            <?php if ($row['status'] == 'Pending') { ?>
                                <button type="submit" name="approve" class="approve-btn">Approve</button>
                                <button type="submit" name="reject" class="reject-btn">Reject</button>
                            <?php } ?>
                            <?php if ($row['status'] == 'Approved') { ?>
                                <button type="submit" name="return" class="return-btn">Return</button>
                            <?php } ?>
                            <button type="submit" name="delete" class="delete-btn">Archive</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<script>
    function confirmAction(action) {
        let message = '';
        switch(action) {
            case 'approve/reject':
                return true; // No confirmation for approve/reject
            case 'return':
                message = 'Are you sure you want to mark this reservation as returned?';
                break;
            case 'archive':
                message = 'Are you sure you want to archive this reservation?';
                break;
            default:
                return true;
        }
        return confirm(message);
    }

    // Make calendar events clickable to show more details
    document.addEventListener('DOMContentLoaded', function() {
        const calendarEvents = document.querySelectorAll('.calendar-event');
        calendarEvents.forEach(event => {
            event.addEventListener('click', function() {
                const tooltip = this.parentElement.querySelector('.tooltiptext');
                tooltip.style.visibility = tooltip.style.visibility === 'visible' ? 'hidden' : 'visible';
                tooltip.style.opacity = tooltip.style.opacity === '1' ? '0' : '1';
            });
        });
    });
</script>

</body>
</html>
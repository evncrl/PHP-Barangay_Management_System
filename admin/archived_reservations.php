<?php
// Start output buffering at the very beginning
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../includes/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once '../includes/facilitiesHeader.php';
// Ensure the user is logged in
// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red; text-align: center; font-size: 18px;'>You must be logged in to access this page. <a href='/saad/user/login.php'>Log in here</a>.</p>";
    exit();
}

// Check for allowed roles (both admin and maintenance)
$allowed_roles = ['admin', 'maintenance'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    echo "<p style='color: red; text-align: center; font-size: 18px;'>You must be logged in as admin or maintenance staff to access this page. <a href='/saad/user/login.php'>Log in here</a>.</p>";
    exit();
}

// Initialize message variable
$message = '';

// Handle restore action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['restore'])) {
    if (isset($_POST['reservation_id']) && is_numeric($_POST['reservation_id'])) {
        $reservation_id = $_POST['reservation_id'];
        
        // Restore the reservation
        $sql = "UPDATE reservations SET deleted_at = NULL, deleted_by = NULL WHERE reservation_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $reservation_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Reservation restored successfully.";
        } else {
            $_SESSION['message'] = "Error restoring reservation.";
        }
        $stmt->close();
        
        // Redirect to avoid form resubmission
        header("Location: archived_reservations.php");
        ob_end_flush();
        exit();
    }
}

// Check for session message
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Fetch all archived reservations
$sql = "
    SELECT r.*, 
           f.facility_name, 
           e.equipment_name,
           u.email AS deleted_by_user
    FROM reservations r
    LEFT JOIN facilities f ON r.facility_id = f.facility_id
    LEFT JOIN equipment e ON r.equipment_id = e.equipment_id
    LEFT JOIN users u ON r.deleted_by = u.user_id
    WHERE r.deleted_at IS NOT NULL
    ORDER BY r.deleted_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$archived_reservations = $stmt->get_result();
?>
<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Archived Reservations</title>
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
        button {
            padding: 9px 12px;
            font-size: 14px;
            cursor: pointer;
            border: none;
            border-radius: 600px;
        }
        .restore-btn { background-color: #28a745; color: white; }
        .back-btn { background-color: #6c757d; color: white; }
        button:hover {
            opacity: 0.8;
        }
        .message {
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="admin-container">
    <h2>Archived Reservations</h2>
    
    <?php if (!empty($message)): ?>
        <div class="message">
            <p style='color: green;'><?php echo htmlspecialchars($message); ?></p>
        </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Reservation ID</th>
                <th>User ID</th>
                <th>Purpose</th>
                <th>Facility</th>
                <th>Equipment</th>
                <th>Quantity</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Status</th>
                <th>Archived On</th>
                <th>Archived By</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $archived_reservations->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['reservation_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                    <td><?php echo htmlspecialchars($row['facility_name'] ?: '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['equipment_name'] ?: '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['quantity_requested'] ?: '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['reservation_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['end_date']); ?></td>
                    <td class="status-<?php echo strtolower(htmlspecialchars($row['status'])); ?>">
                        <?php echo htmlspecialchars($row['status']); ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['deleted_at']); ?></td>
                    <td><?php echo htmlspecialchars($row['deleted_by_user'] ?: 'System'); ?></td>
                    <td class="action-buttons">
                        <form method="POST" onsubmit="return confirm('Are you sure you want to restore this reservation?');">
                            <input type="hidden" name="reservation_id" value="<?php echo htmlspecialchars($row['reservation_id']); ?>">
                            <button type="submit" name="restore" class="restore-btn">Restore</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <div style="text-align: center; margin-top: 20px;">
        <a href="../item/staff_facilities.php" class="back-btn">Back to Active Reservations</a>
    </div>

</div>

</body>
</html>
<?php ob_end_flush(); ?>
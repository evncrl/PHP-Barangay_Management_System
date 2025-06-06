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

include_once '../includes/adminHeader.php';

// Ensure the user is logged in and is an admin
$allowed_roles = ['admin', 'maintenance'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    echo "<p style='color: red; text-align: center; font-size: 18px;'>You must be logged in as admin or maintenance staff to access this page. <a href='/saad/user/login.php'>Log in here</a>.</p>";
    exit();
}


// Initialize message variable
$message = '';

// Handle restore actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Restore equipment
    if (isset($_POST['restore_equipment'])) {
        $equipment_id = $_POST['equipment_id'];
        $sql = "UPDATE equipment SET deleted_at = NULL, deleted_by = NULL WHERE equipment_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $equipment_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Equipment restored successfully.";
        } else {
            $_SESSION['message'] = "Error restoring equipment.";
        }
        $stmt->close();
        header("Location: archived_items.php");
        ob_end_flush();
        exit();
    }
    
    // Restore facility
    if (isset($_POST['restore_facility'])) {
        $facility_id = $_POST['facility_id'];
        $sql = "UPDATE facilities SET deleted_at = NULL, deleted_by = NULL WHERE facility_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $facility_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Facility restored successfully.";
        } else {
            $_SESSION['message'] = "Error restoring facility.";
        }
        $stmt->close();
        header("Location: archived_items.php");
        ob_end_flush();
        exit();
    }
}

// Check for session message
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Fetch all archived equipment and facilities with null-safe handling
$archived_equipment_sql = "SELECT e.*, u.email AS deleted_by_user 
                          FROM equipment e 
                          LEFT JOIN users u ON e.deleted_by = u.user_id
                          WHERE e.deleted_at IS NOT NULL
                          ORDER BY e.deleted_at DESC";
$archived_facilities_sql = "SELECT f.*, u.email AS deleted_by_user 
                           FROM facilities f 
                           LEFT JOIN users u ON f.deleted_by = u.user_id
                           WHERE f.deleted_at IS NOT NULL
                           ORDER BY f.deleted_at DESC";

$archived_equipment = $conn->query($archived_equipment_sql);
$archived_facilities = $conn->query($archived_facilities_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Archived Items</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_url ?? '', ENT_QUOTES, 'UTF-8'); ?>includes/style/style.css">
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
            margin-bottom: 30px;
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
        .restore-btn { 
            background-color: #28a745; 
            color: white; 
        }
        .back-btn { 
            background-color: #6c757d; 
            color: white; 
        }
        button:hover {
            opacity: 0.8;
        }
        .message {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .section-title {
            color: #223F61;
            margin: 20px 0 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #223F61;
        }
    </style>
</head>
<body>

<div class="admin-container">
    <h2>Archived Items</h2>
    
    <?php if (!empty($message)): ?>
        <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
            <?php echo htmlspecialchars($message ?? '', ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div style="text-align: center; margin-bottom: 20px;">
        <a href="inventory.php" class="back-btn">Back to Active Items</a>
    </div>

    <!-- Archived Facilities Section -->
    <h3 class="section-title">Archived Facilities</h3>
    <table>
        <thead>
            <tr>
                <th>Facility ID</th>
                <th>Facility Name</th>
                <th>Description</th>
                <th>Status</th>
                <th>Archived By</th>
                <th>Archived On</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $archived_facilities->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['facility_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['facility_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($row['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['deleted_by_user'] ?? 'System', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['deleted_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="action-buttons">
                        <form method="POST" onsubmit="return confirm('Are you sure you want to restore this facility?');">
                            <input type="hidden" name="facility_id" value="<?php echo htmlspecialchars($row['facility_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" name="restore_facility" class="restore-btn">Restore</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <!-- Archived Equipment Section -->
    <h3 class="section-title">Archived Equipment</h3>
    <table>
        <thead>
            <tr>
                <th>Equipment ID</th>
                <th>Equipment Name</th>
                <th>Quantity</th>
                <th>Archived By</th>
                <th>Archived On</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $archived_equipment->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['equipment_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['equipment_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['quantity'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['deleted_by_user'] ?? 'System', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['deleted_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="action-buttons">
                        <form method="POST" onsubmit="return confirm('Are you sure you want to restore this equipment?');">
                            <input type="hidden" name="equipment_id" value="<?php echo htmlspecialchars($row['equipment_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" name="restore_equipment" class="restore-btn">Restore</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

</div>

</body>
</html>
<?php ob_end_flush(); ?>
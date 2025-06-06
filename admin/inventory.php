<?php

ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../includes/config.php';
include_once '../includes/adminHeader.php';

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


// First, alter tables to add soft delete columns if they don't exist
$alter_queries = [
    "ALTER TABLE equipment ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL",
    "ALTER TABLE equipment ADD COLUMN IF NOT EXISTS deleted_by INT NULL",
    "ALTER TABLE facilities ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL",
    "ALTER TABLE facilities ADD COLUMN IF NOT EXISTS deleted_by INT NULL"
];

foreach ($alter_queries as $query) {
    $conn->query($query);
}

// Fetch all non-deleted equipment and facilities data
$equipment_sql = "SELECT * FROM equipment WHERE deleted_at IS NULL";
$facility_sql = "SELECT * FROM facilities WHERE deleted_at IS NULL";

$equipment_stmt = $conn->prepare($equipment_sql);
$facility_stmt = $conn->prepare($facility_sql);

// Execute the first query and get results
if ($equipment_stmt->execute()) {
    $equipments = $equipment_stmt->get_result();
} else {
    echo "Error fetching equipment data.";
    exit();
}

// Execute the second query and get results
if ($facility_stmt->execute()) {
    $facilities = $facility_stmt->get_result();
} else {
    echo "Error fetching facility data.";
    exit();
}

// Handle delete actions for equipment and facilities
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle equipment deletion (soft delete)
    if (isset($_POST['delete_equipment'])) {
        $equipment_id = $_POST['equipment_id'];
        $delete_sql = "UPDATE equipment SET deleted_at = NOW(), deleted_by = ? WHERE equipment_id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("ii", $_SESSION['user_id'], $equipment_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Equipment archived successfully.";
        } else {
            $_SESSION['message'] = "Error archiving equipment.";
        }
        $stmt->close();
        header("Location: inventory.php");
        exit();
    }

    // Handle facility deletion (soft delete)
    elseif (isset($_POST['delete_facility'])) {
        $facility_id = $_POST['facility_id'];
        $delete_sql = "UPDATE facilities SET deleted_at = NOW(), deleted_by = ? WHERE facility_id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("ii", $_SESSION['user_id'], $facility_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Facility archived successfully.";
        } else {
            $_SESSION['message'] = "Error archiving facility.";
        }
        $stmt->close();
        header("Location: inventory.php");
        exit();
    }

    // Handle adding new equipment
    elseif (isset($_POST['add_equipment'])) {
        $equipment_name = $_POST['equipment_name'];
        $quantity = $_POST['quantity'];
        $add_sql = "INSERT INTO equipment (equipment_name, quantity) VALUES (?, ?)";
        $stmt = $conn->prepare($add_sql);
        $stmt->bind_param("si", $equipment_name, $quantity);
        if ($stmt->execute()) {
            $_SESSION['message'] = "New equipment added successfully.";
        } else {
            $_SESSION['message'] = "Error adding equipment.";
        }
        $stmt->close();
        header("Location: inventory.php");
        exit();
    }

    // Handle adding new facility
    elseif (isset($_POST['add_facility'])) {
        $facility_name = $_POST['facility_name'];
        $description = $_POST['description'];
        $add_sql = "INSERT INTO facilities (facility_name, description) VALUES (?, ?)";
        $stmt = $conn->prepare($add_sql);
        $stmt->bind_param("ss", $facility_name, $description);
    
        if ($stmt->execute()) {
            $_SESSION['message'] = "New facility added successfully.";
        } else {
            $_SESSION['message'] = "Error adding facility.";
        }
        $stmt->close();
        header("Location: inventory.php");
        exit();
    }

    // Handle editing existing equipment
    elseif (isset($_POST['edit_equipment'])) {
        $equipment_id = $_POST['equipment_id'];
        $equipment_name = $_POST['equipment_name'];
        $quantity = $_POST['quantity'];
        $update_sql = "UPDATE equipment SET equipment_name = ?, quantity = ? WHERE equipment_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sii", $equipment_name, $quantity, $equipment_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Equipment updated successfully.";
        } else {
            $_SESSION['message'] = "Error updating equipment.";
        }
        $stmt->close();
        header("Location: inventory.php");
        exit();
    }

    // Handle editing existing facility
    elseif (isset($_POST['edit_facility'])) {
        $facility_id = $_POST['facility_id'];
        $facility_name = $_POST['facility_name'];
        $description = $_POST['description'];
        $status = $_POST['status'];
        $update_sql = "UPDATE facilities SET facility_name = ?, description = ?, status = ? WHERE facility_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssi", $facility_name, $description, $status, $facility_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Facility updated successfully.";
        } else {
            $_SESSION['message'] = "Error updating facility.";
        }
        $stmt->close();
        header("Location: inventory.php");
        exit();
    }
}

// Display any messages
$message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
unset($_SESSION['message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Facilities and Equipments</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>includes/style/style.css">
    <style>
        body {
            background-color: #223F61;
            color: black;
        }
        .admin-container {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            padding: 70px;
            background:rgba(231, 232, 242, 0.94);
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
        .form-wrapper {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-container {
            flex: 1;
            background-color: #f9f9f9;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 8px 8px rgba(0, 0, 0, 0.1);
        }
        .form-container h3 {
            color: #223F61;
            text-align: center;
        }
        .form-container input, .form-container button, .form-container select {
            padding: 10px;
            margin: 10px 0;
            width: 100%;
            font-size: 14px;
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
        .input-group {
            display: flex;
            gap: 10px;
        }
        .input-group input {
            flex: 1;
        }
        .side-by-side {
            display: flex;
            gap: 10px;
        }
        .side-by-side input {
            width: calc(50% - 5px);
        }
        input[name="equipment_id"], input[name="facility_id"] {
            width: 390px;
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
        .archive-btn {
            background-color: #6c757d;
            color: white;
        }
    </style>
</head>
<body>

<div class="admin-container">
    <h2>Manage Facilities and Equipments</h2>

    <?php if (!empty($message)): ?>
        <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <!-- Add link to view archived items -->
    <div style="text-align: center; margin-bottom: 20px;">
        <a href="archived_items.php" class="archive-btn">View Archived Items</a>
    </div>

    <!-- Add New Equipment and Facility Form -->
    <div class="form-wrapper">
        <!-- Add New Equipment Form -->
        <div class="form-container">
            <h3>Add New Equipment</h3>
            <form method="POST">
                <div class="input-group">
                    <input type="text" name="equipment_name" placeholder="Equipment Name" required>
                    <input type="number" name="quantity" placeholder="Quantity" required>
                </div>
                <button type="submit" name="add_equipment">Add Equipment</button>
            </form>
        </div>

        <!-- Add New Facility Form -->
        <div class="form-container">
            <h3>Add New Facility</h3>
            <form method="POST">
                <div class="input-group">
                    <input type="text" name="facility_name" placeholder="Facility Name" required>
                    <input type="text" name="description" placeholder="Description" required>
                </div>
                <button type="submit" name="add_facility">Add Facility</button>
            </form>
        </div>
    </div>

    <!-- Edit Existing Equipment Form -->
    <div class="form-wrapper">
        <div class="form-container">
            <h3>Edit Equipment</h3>
            <form method="POST">
                <input type="number" name="equipment_id" placeholder="Equipment ID" required>
                <div class="side-by-side">
                    <input type="text" name="equipment_name" placeholder="Equipment Name" required>
                    <input type="number" name="quantity" placeholder="Quantity" required>
                </div>
                <button type="submit" name="edit_equipment">Edit Equipment</button>
            </form>
        </div>

        <!-- Edit Existing Facility Form -->
        <div class="form-container">
            <h3>Edit Facility</h3>
            <form method="POST">
                <input type="number" name="facility_id" placeholder="Facility ID" required>
                <div class="side-by-side">
                    <input type="text" name="facility_name" placeholder="Facility Name" required>
                    <input type="text" name="description" placeholder="Description" required>
                </div>
                <select name="status" required>
                    <option value="available">Available</option>
                    <option value="reserved">Reserved</option>
                </select>
                <button type="submit" name="edit_facility">Edit Facility</button>
            </form>
        </div>
    </div>

   <!-- Manage Facilities -->
<div class="container">
    <h3>Manage Facilities</h3>
    <table class="item-table">
        <thead>
            <tr>
                <th>Facility ID</th>
                <th>Facility Name</th>
                <th>Description</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $facilities->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['facility_id'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['facility_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['description'] ?? ''); ?></td>
                    <td><?php echo ucfirst(htmlspecialchars($row['status'] ?? '')); ?></td>
                    <td class="action-buttons">
                        <form method="POST" onsubmit="return confirm('Are you sure you want to archive this facility?');">
                            <input type="hidden" name="facility_id" value="<?php echo htmlspecialchars($row['facility_id'] ?? ''); ?>">
                            <button type="submit" name="delete_facility" class="delete-btn">Archive</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

    <!-- Manage Equipments -->
    <div class="container">
        <h3>Manage Equipments</h3>
        <table class="item-table">
            <thead>
                <tr>
                    <th>Equipment ID</th>
                    <th>Equipment Name</th>
                    <th>Quantity</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $equipments->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['equipment_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['equipment_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                        <td class="action-buttons">
                            <form method="POST" onsubmit="return confirm('Are you sure you want to archive this equipment?');">
                                <input type="hidden" name="equipment_id" value="<?php echo $row['equipment_id']; ?>">
                                <button type="submit" name="delete_equipment" class="delete-btn">Archive</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>

<?php ob_end_flush(); ?>
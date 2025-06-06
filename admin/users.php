<?php
include '../includes/config.php'; 
include_once '../includes/adminHeader.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// Handle verification status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id'], $_POST['status'])) {
    header('Content-Type: application/json'); // Set JSON response header

    $user_id = intval($_POST['user_id']);
    $status = $_POST['status'];

    if (!in_array($status, ['Approved', 'Rejected', 'Pending', 'Deactivate'])) {
        echo json_encode(["message" => "Invalid status!"]);
        exit;
    }

    $sql = "UPDATE residents SET verification_status = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $user_id);

    if ($stmt->execute()) {
        echo json_encode(["message" => "Verification status updated successfully!"]);
    } else {
        echo json_encode(["message" => "Error updating verification status!"]);
    }

    $stmt->close();
    exit;
}

// Handle role update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id'], $_POST['role'])) {
    header('Content-Type: application/json');

    $user_id = intval($_POST['user_id']);
    $role = $_POST['role'];

    // Updated to match the enum values from your database
    if (!in_array($role, ['admin', 'resident', 'secretary', 'maintenance', 'lupon', 'offices'])) {
        echo json_encode(["message" => "Invalid role!"]);
        exit;
    }

    $sql = "UPDATE users SET role = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $role, $user_id);

    if ($stmt->execute()) {
        echo json_encode(["message" => "User role updated successfully!"]);
    } else {
        echo json_encode(["message" => "Error updating user role!"]);
    }

    $stmt->close();
    exit;
}

// Handle user deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user_id'])) {
    header('Content-Type: application/json'); // Set JSON response

    require '../includes/config.php'; // Ensure DB connection

    $delete_user_id = intval($_POST['delete_user_id']);

    if (!$delete_user_id) {
        echo json_encode(["success" => false, "message" => "Invalid user ID!"]);
        exit;
    }

    // Delete user query
    $delete_sql = "DELETE FROM users WHERE user_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $delete_user_id);

    if ($delete_stmt->execute()) {
        echo json_encode(["success" => true, "message" => "User deleted successfully!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error deleting user!"]);
    }

    $delete_stmt->close();
    $conn->close(); 
    exit;
}

$sql = "SELECT u.user_id, u.email, u.role, u.created_at, u.address, 
               r.fname, r.lname, r.verification_status 
        FROM users u
        LEFT JOIN residents r ON u.user_id = r.user_id
        WHERE u.role = 'resident'";

if (!empty($search)) {
    $sql .= " AND (r.fname LIKE '%$search%' OR r.lname LIKE '%$search%')";
}

$sql .= " ORDER BY u.created_at DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User List</title>
    <style>
        :root {
    --primary-color: #3498db;
    --primary-dark: #2980b9;
    --primary-light: #e1f0fa;
    --danger-color: #e74c3c;
    --danger-dark: #c0392b;
    --success-color: #2ecc71;
    --success-light: #eafaf1;
    --warning-color: #f39c12;
    --warning-light: #fef5e7;
    --light-gray: #f8f9fa;
    --border-color: #e0e0e0;
    --text-color: #333;
    --text-secondary: #7f8c8d;
    --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: var(--light-gray);
    margin: 3%;
    padding: 3%;
    color: var(--text-color);
    line-height: 1.6;
}

.content-wrapper {
    margin: 0 auto;
    padding: 30px;
    max-width: 1300px;
    width: 100%;
    box-sizing: border-box;
    transition: all 0.3s ease;
}

.container {
    background: white;
    padding: 30px;
    box-shadow: var(--shadow);
    border-radius: 12px;
    margin-bottom: 30px;
    transition: transform 0.3s ease;
}

.container:hover {
    transform: translateY(-2px);
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    border-bottom: 2px solid var(--primary-light);
    padding-bottom: 20px;
}

.page-header h2 {
    margin: 0;
    color: var(--primary-dark);
    font-weight: 600;
    font-size: 28px;
    letter-spacing: -0.5px;
}

.search-container {
    display: flex;
    margin-bottom: 25px;
    max-width: 600px;
    width: 100%;
    margin-left: auto;
    margin-right: auto;
}

.search-input {
    flex: 1;
    padding: 14px 18px;
    border: 1px solid var(--border-color);
    border-radius: 8px 0 0 8px;
    font-size: 15px;
    outline: none;
    transition: all 0.3s;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.search-input:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
}

.search-button {
    padding: 14px 24px;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 0 8px 8px 0;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
    font-size: 15px;
}

.search-button:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
}

.search-button:active {
    transform: translateY(0);
}

.users-table-container {
    overflow-x: auto;
    border-radius: 8px;
    box-shadow: var(--shadow);
}

.users-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-top: 10px;
    border-radius: 8px;
    overflow: hidden;
}

.users-table th {
    background: var(--primary-color);
    color: white;
    padding: 16px;
    text-align: left;
    font-weight: 600;
    font-size: 15px;
    white-space: nowrap;
}

.users-table th:first-child {
    border-top-left-radius: 8px;
}

.users-table th:last-child {
    border-top-right-radius: 8px;
}

.users-table td {
    padding: 16px;
    border-bottom: 1px solid var(--border-color);
    vertical-align: middle;
    font-size: 14px;
    transition: background-color 0.3s;
}

.users-table tr {
    background-color: white;
    transition: all 0.2s;
}

.users-table tr:hover {
    background-color: var(--primary-light);
    cursor: pointer;
}

.users-table tr:last-child td {
    border-bottom: none;
}

.dropdown-select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    background-color: white;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%233498db' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: calc(100% - 12px) center;
    padding-right: 35px;
}

.dropdown-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
    outline: none;
}

.status-approved {
    background-color: var(--success-light);
}

.status-rejected {
    background-color: rgba(231, 76, 60, 0.1);
}

.status-pending {
    background-color: var(--warning-light);
}

.status-deactivate {
    background-color: rgba(189, 195, 199, 0.1);
}

.action-button {
    padding: 10px 18px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s;
}

.delete-button {
    background-color: var(--danger-color);
    color: white;
}

.delete-button:hover {
    background-color: var(--danger-dark);
    transform: translateY(-1px);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.delete-button:active {
    transform: translateY(0);
    box-shadow: none;
}

.empty-state {
    text-align: center;
    padding: 60px 0;
    color: var(--text-secondary);
    font-size: 16px;
}

.notification {
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 12px 24px;
    border-radius: 8px;
    color: white;
    font-size: 14px;
    z-index: 1000;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    opacity: 1;
    transition: opacity 0.5s, transform 0.3s;
    transform: translateY(0);
}

.notification.success {
    background-color: var(--success-color);
}

.notification.error {
    background-color: var(--danger-color);
}

.notification.info {
    background-color: var(--primary-color);
}

/* Responsive adjustments */
@media (max-width: 1200px) {
    .content-wrapper {
        padding: 20px;
    }
}

@media (max-width: 992px) {
    .content-wrapper {
        padding: 15px;
    }
    
    .container {
        padding: 20px;
    }
    
    .page-header h2 {
        font-size: 24px;
    }
}

@media (max-width: 768px) {
    .search-container {
        flex-direction: column;
    }
    
    .search-input {
        border-radius: 8px;
        margin-bottom: 10px;
    }
    
    .search-button {
        border-radius: 8px;
        width: 100%;
    }
    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .page-header h2 {
        margin-bottom: 10px;
    }
}
    </style>
</head>
<body>

<div class="content-wrapper">
    <div class="container">
        <div class="page-header">
            <h2>User Management</h2>
        </div>

        <form method="GET" action="">
            <div class="search-container">
                <input type="text" class="search-input" name="search" placeholder="Search by name..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="search-button">Search</button>
            </div>
        </form>

        <table class="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created At</th>
                    <th>Address</th>
                    <th>Verification Status</th>
                    
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $statusClass = '';
                        if ($row['verification_status'] == 'Approved') {
                            $statusClass = 'status-approved';
                        } else if ($row['verification_status'] == 'Rejected') {
                            $statusClass = 'status-rejected';
                        } else if ($row['verification_status'] == 'Pending') {
                            $statusClass = 'status-pending';
                        } else if ($row['verification_status'] == 'Deactivate') {
                            $statusClass = 'status-deactivate';
                        }
                        
                        echo "<tr class='clickable-row' data-userid='{$row['user_id']}'>
                            <td>{$row['user_id']}</td>
                            <td>{$row['fname']} {$row['lname']}</td>
                            <td>{$row['email']}</td>
                            <td>
                                <select class='dropdown-select role-dropdown' data-userid='{$row['user_id']}'>
                                    <option value='resident' " . ($row['role'] == 'resident' ? 'selected' : '') . ">Resident</option>
                                    <option value='admin' " . ($row['role'] == 'admin' ? 'selected' : '') . ">Admin</option>
                                    <option value='secretary' " . ($row['role'] == 'secretary' ? 'selected' : '') . ">Secretary</option>
                                    <option value='maintenance' " . ($row['role'] == 'maintenance' ? 'selected' : '') . ">Maintenance</option>
                                    <option value='lupon' " . ($row['role'] == 'lupon' ? 'selected' : '') . ">Lupon</option>
                                    <option value='offices' " . ($row['role'] == 'offices' ? 'selected' : '') . ">Offices</option>
                                </select>
                            </td>
                            <td>{$row['created_at']}</td>
                            <td>{$row['address']}</td>
                            <td class='{$statusClass}'>
                                <select class='dropdown-select verification-status' data-userid='{$row['user_id']}'>
                                    <option value='Pending' " . ($row['verification_status'] == 'Pending' ? 'selected' : '') . ">Pending</option>
                                    <option value='Approved' " . ($row['verification_status'] == 'Approved' ? 'selected' : '') . ">Approved</option>
                                    <option value='Rejected' " . ($row['verification_status'] == 'Rejected' ? 'selected' : '') . ">Rejected</option>
                                    <option value='Deactivate' " . ($row['verification_status'] == 'Deactivate' ? 'selected' : '') . ">Deactivate</option>
                                </select>
                            </td>
                           
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='8' class='empty-state'>No users found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const rows = document.querySelectorAll(".clickable-row");

        rows.forEach(row => {
            row.addEventListener("click", function (event) {
                if (event.target.tagName.toLowerCase() === 'select' || 
                    event.target.tagName.toLowerCase() === 'button') {
                    event.stopPropagation();
                    return;
                }
                const userId = this.getAttribute("data-userid");
                window.location.href = `../profile.php?user_id=${userId}`;
            });
        });

        // Handle dropdown change for verification status update
        const statusDropdowns = document.querySelectorAll(".verification-status");
        statusDropdowns.forEach(dropdown => {
            dropdown.addEventListener("change", function () {
                const userId = this.getAttribute("data-userid");
                const status = this.value;
                
                // Update the status class on the parent TD
                const parentTd = this.parentElement;
                parentTd.className = ''; // Clear existing classes
                if (status === 'Approved') parentTd.classList.add('status-approved');
                if (status === 'Rejected') parentTd.classList.add('status-rejected');
                if (status === 'Pending') parentTd.classList.add('status-pending');
                if (status === 'Deactivate') parentTd.classList.add('status-deactivate');

                fetch("", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: `user_id=${encodeURIComponent(userId)}&status=${encodeURIComponent(status)}`
                })
                .then(response => response.json())
                .then(data => {
                    // Show toast notification instead of alert
                    showNotification(data.message, data.message.includes("successfully") ? 'success' : 'error');
                })
                .catch(error => console.error("Error:", error));
            });
        });

        // Handle role dropdown change
        const roleDropdowns = document.querySelectorAll(".role-dropdown");
        roleDropdowns.forEach(dropdown => {
            dropdown.addEventListener("change", function () {
                const userId = this.getAttribute("data-userid");
                const newRole = this.value;

                fetch("", {  // Changed from update_role.php to current file
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: `user_id=${encodeURIComponent(userId)}&role=${encodeURIComponent(newRole)}`
                })
                .then(response => response.json())
                .then(data => {
                    showNotification(data.message, data.message.includes("successfully") ? 'success' : 'error');
                    
                    // If role was changed from resident, hide this row after a short delay
                    if (newRole !== 'resident') {
                        setTimeout(() => {
                            this.closest("tr").style.display = 'none';
                        }, 1500);
                    }
                })
                .catch(error => console.error("Error:", error));
            });
        });

        // Handle delete button click
        document.querySelectorAll(".delete-user").forEach(button => {
            button.addEventListener("click", function (e) {
                e.stopPropagation();
                const userId = this.getAttribute("data-userid");

                if (confirm("Are you sure you want to delete this user?")) {
                    fetch("users.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: `delete_user_id=${encodeURIComponent(userId)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        showNotification(data.message, data.success ? 'success' : 'error');
                        if (data.success) {
                            this.closest("tr").remove(); // Remove row from UI
                        }
                    })
                    .catch(error => console.error("Error:", error));
                }
            });
        });
        
        // Simple toast notification function
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.textContent = message;
            notification.style.position = 'fixed';
            notification.style.bottom = '20px';
            notification.style.right = '20px';
            notification.style.padding = '10px 20px';
            notification.style.borderRadius = '4px';
            notification.style.color = 'white';
            notification.style.fontSize = '14px';
            notification.style.zIndex = '1000';
            
            if (type === 'success') {
                notification.style.backgroundColor = '#2ecc71';
            } else if (type === 'error') {
                notification.style.backgroundColor = '#e74c3c';
            } else {
                notification.style.backgroundColor = '#3498db';
            }
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transition = 'opacity 0.5s';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 500);
            }, 3000);
        }
    });
</script>

</body>
</html>
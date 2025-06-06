<?php
// Staff Management System - Fast Version
include '../includes/config.php';
include_once '../includes/adminHeader.php';

// CSRF Protection
session_start();
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));

// Handle POST Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Verify CSRF Token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die(json_encode(['error' => 'CSRF validation failed']));
    }

    $response = ['success' => false];
    
    // Update Role
    if (isset($_POST['update_role'])) {
        $allowed_roles = ['admin', 'secretary', 'maintenance', 'lupon', 'offices'];
        if (in_array($_POST['role'], $allowed_roles)) {
            $stmt = $conn->prepare("UPDATE users SET role = ? WHERE user_id = ?");
            $response['success'] = $stmt->execute([$_POST['role'], $_POST['user_id']]);
        }
    }
    // Update Status
    elseif (isset($_POST['update_status'])) {
        $allowed_statuses = ['Approved', 'Rejected', 'Pending', 'Deactivate'];
        if (in_array($_POST['status'], $allowed_statuses)) {
            $stmt = $conn->prepare("UPDATE residents SET verification_status = ? WHERE resident_id = ?");
            $response['success'] = $stmt->execute([$_POST['status'], $_POST['resident_id']]);
        }
    }
    // Delete User
    elseif (isset($_POST['delete_user'])) {
        $conn->begin_transaction();
        try {
            $conn->query("DELETE FROM residents WHERE user_id = ".(int)$_POST['user_id']);
            $conn->query("DELETE FROM users WHERE user_id = ".(int)$_POST['user_id']);
            $conn->commit();
            $response['success'] = true;
        } catch (Exception $e) {
            $conn->rollback();
        }
    }
    
    die(json_encode($response));
}

// Get Staff Data
$search = $_GET['search'] ?? '';
$sql = "SELECT u.user_id, u.email, u.role, u.created_at, 
               r.resident_id, r.verification_status, r.fname, r.lname
        FROM users u
        LEFT JOIN residents r ON u.user_id = r.user_id
        WHERE u.role != 'resident'";

$sql .= " ORDER BY u.created_at DESC";
if ($search) {
    $stmt = $conn->prepare($sql." AND (u.email LIKE ? OR r.fname LIKE ? OR r.lname LIKE ?)");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
} else {
    $stmt = $conn->query($sql);
}

$staff = $stmt->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management System</title>
    <style>
        /* Main Layout */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 25px;
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 25px;
            font-size: 28px;
        }
        
        /* Search Box */
        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .search-box input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .search-box button {
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .search-box button:hover {
            background: #2980b9;
        }
        
        /* Table Styles */
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        th {
            background-color: #3498db;
            color: white;
            font-weight: 600;
        }
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Form Elements */
        select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
            cursor: pointer;
            min-width: 120px;
        }
        
        button {
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        button.delete {
            background-color: #e74c3c;
            color: white;
            border: none;
        }
        
        button.delete:hover {
            background-color: #c0392b;
        }
        
        /* Status Badges */
        .status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 8px;
        }
        
        .Approved {
            background-color: rgba(46, 204, 113, 0.2);
            color: #27ae60;
        }
        
        .Pending {
            background-color: rgba(243, 156, 18, 0.2);
            color: #f39c12;
        }
        
        .Rejected {
            background-color: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }
        
        .Deactivate {
            background-color: rgba(149, 165, 166, 0.2);
            color: #7f8c8d;
        }
        
        /* Toast Notification */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 24px;
            border-radius: 4px;
            color: white;
            z-index: 1000;
            display: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .toast.success {
            background-color: #2ecc71;
            display: block;
        }
        
        .toast.error {
            background-color: #e74c3c;
            display: block;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            th, td {
                padding: 8px 10px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Staff Management</h1>
    
    <div class="search-box">
        <input type="text" id="search" placeholder="Search by name or email..." value="<?= htmlspecialchars($search) ?>">
        <button onclick="handleSearch()">Search</button>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                  
                </tr>
            </thead>
            <tbody>
                <?php foreach ($staff as $member): ?>
                <tr>
                    <td><?= $member['user_id'] ?></td>
                    <td><?= htmlspecialchars(($member['fname'] ?? '') . ' ' . ($member['lname'] ?? '')) ?: 'N/A' ?></td>
                    <td><?= htmlspecialchars($member['email']) ?></td>
                    <td>
                        <select onchange="updateRole(<?= $member['user_id'] ?>, this.value)">
                            <option value="admin" <?= $member['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="secretary" <?= $member['role'] === 'secretary' ? 'selected' : '' ?>>Secretary</option>
                            <option value="maintenance" <?= $member['role'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                            <option value="lupon" <?= $member['role'] === 'lupon' ? 'selected' : '' ?>>Lupon</option>
                            <option value="offices" <?= $member['role'] === 'offices' ? 'selected' : '' ?>>Offices</option>
                        </select>
                    </td>
                    <td>
                        <?php if ($member['resident_id']): ?>
                        <select onchange="updateStatus(<?= $member['resident_id'] ?>, this.value)">
                            <option value="Approved" <?= ($member['verification_status'] ?? '') === 'Approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="Pending" <?= ($member['verification_status'] ?? '') === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Rejected" <?= ($member['verification_status'] ?? '') === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                            <option value="Deactivate" <?= ($member['verification_status'] ?? '') === 'Deactivate' ? 'selected' : '' ?>>Deactivate</option>
                        </select>
                        <span class="status <?= $member['verification_status'] ?? '' ?>"><?= $member['verification_status'] ?? '' ?></span>
                        <?php else: ?>
                        N/A
                        <?php endif; ?>
                    </td>
                  
                </tr>
                <?php endforeach; ?>
                <?php if (empty($staff)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;">No staff members found</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="toast" class="toast"></div>

<script>
const csrfToken = "<?= $_SESSION['csrf_token'] ?>";

// Show toast notification
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast ${type}`;
    
    setTimeout(() => {
        toast.style.display = 'none';
    }, 3000);
}

// Handle search
function handleSearch() {
    const searchTerm = document.getElementById('search').value;
    window.location.href = `?search=${encodeURIComponent(searchTerm)}`;
}

// Update user role
async function updateRole(userId, newRole) {
    try {
        const response = await fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                update_role: true,
                user_id: userId,
                role: newRole,
                csrf_token: csrfToken
            })
        });
        
        const result = await response.json();
        showToast(result.success ? 'Role updated successfully' : 'Failed to update role', result.success ? 'success' : 'error');
        
        if (!result.success) {
            // Revert to original value
            event.target.value = event.target.querySelector('option[selected]').value;
        }
    } catch (error) {
        showToast('Error updating role', 'error');
        console.error('Error:', error);
    }
}

// Update verification status
async function updateStatus(residentId, newStatus) {
    try {
        const response = await fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                update_status: true,
                resident_id: residentId,
                status: newStatus,
                csrf_token: csrfToken
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Status updated successfully', 'success');
            // Update status badge
            const statusBadge = event.target.nextElementSibling;
            statusBadge.className = `status ${newStatus}`;
            statusBadge.textContent = newStatus;
        } else {
            showToast('Failed to update status', 'error');
            // Revert to original value
            event.target.value = event.target.querySelector('option[selected]').value;
        }
    } catch (error) {
        showToast('Error updating status', 'error');
        console.error('Error:', error);
    }
}

// Delete user
async function deleteUser(userId, button) {
    if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        return;
    }
    
    try {
        const response = await fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                delete_user: true,
                user_id: userId,
                csrf_token: csrfToken
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('User deleted successfully', 'success');
            // Fade out and remove row
            const row = button.closest('tr');
            row.style.opacity = '0';
            setTimeout(() => row.remove(), 300);
        } else {
            showToast('Failed to delete user', 'error');
        }
    } catch (error) {
        showToast('Error deleting user', 'error');
        console.error('Error:', error);
    }
}
</script>
</body>
</html>
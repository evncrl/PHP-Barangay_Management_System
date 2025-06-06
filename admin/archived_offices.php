<?php
ob_start();
session_start();
include '../includes/config.php';
include_once '../includes/officesHeader.php';

$allowed_roles = ['admin', 'offices'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    echo "<p style='color: red; text-align: center; font-size: 18px;'>You must be logged in as admin or maintenance staff to access this page. <a href='/saad/user/login.php'>Log in here</a>.</p>";
    exit();
}


// Handle office restoration
if (isset($_POST['restore_office'])) {
    $office_id = $_POST['office_id'];
    $sql = "UPDATE offices SET deleted_at = NULL, deleted_by = NULL WHERE office_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $office_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Office restored successfully!";
        $_SESSION['message_class'] = "success";
    } else {
        $_SESSION['message'] = "Error restoring office: " . $stmt->error;
        $_SESSION['message_class'] = "error";
    }
    $stmt->close();
    header("Location: archived_offices.php");
    exit();
}

// Fetch archived offices
$archived_offices = $conn->query("SELECT o.*, 
                                 IFNULL(u.email, 'System') as deleted_by_user 
                                 FROM offices o
                                 LEFT JOIN users u ON o.deleted_by = u.user_id
                                 WHERE o.deleted_at IS NOT NULL
                                 ORDER BY o.deleted_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Offices</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        
        .btn {
            display: inline-block;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 8px 12px;
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
        
        .btn-success {
            color: var(--white);
            background-color: var(--success-color);
            border-color: var(--success-color);
        }
        
        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
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
        
        .text-muted {
            color: var(--gray-500);
            font-size: 0.9em;
        }
        
        /* Responsive adjustments */
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
    <?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-<?php echo $_SESSION['message_class']; ?>">
        <?php echo $_SESSION['message']; ?>
        <?php unset($_SESSION['message']); unset($_SESSION['message_class']); ?>
    </div>
    <?php endif; ?>

    <div class="page-header">
        <h2>Archived Offices</h2>
        <a href="/saad/item/staff_offices.php" class="btn btn-primary">Back to Active Offices</a>
    </div>
    
    <div class="card">
        <div class="card-header">
            List of Archived Offices
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Office Name</th>
                            <th>Archived By</th>
                            <th>Archived On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($archived_offices->num_rows > 0): ?>
                            <?php while ($office = $archived_offices->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($office['office_name']); ?></td>
                                    <td><?php echo htmlspecialchars($office['deleted_by_user'] ?? 'System'); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($office['deleted_at']))); ?>
                                        <div class="text-muted"><?php echo time_elapsed_string($office['deleted_at']); ?></div>
                                    </td>
                                    <td>
                                        <form method="POST" action="archived_offices.php" onsubmit="return confirm('Are you sure you want to restore this office?');">
                                            <input type="hidden" name="office_id" value="<?php echo $office['office_id']; ?>">
                                            <button type="submit" name="restore_office" class="btn btn-success">Restore</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">No archived offices found</td>
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

<?php
// Helper function to show time elapsed
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>

</body>
</html>
<?php ob_end_flush(); ?>
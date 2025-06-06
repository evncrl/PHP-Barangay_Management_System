<?php
ob_start();
include '../includes/config.php'; 
include_once '../includes/adminHeader.php';
require '../includes/fpdf/fpdf.php';

// Add Household
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_household'])) {
    $head_of_family = $_POST['head_of_family'];
    $address = $_POST['address'];
    $number_of_children = $_POST['number_of_children'];
    $number_of_male = $_POST['number_of_male'];
    $number_of_female = $_POST['number_of_female'];
    $total_family_members = $number_of_male + $number_of_female;

    $stmt = $conn->prepare("INSERT INTO household (head_of_family, address, number_of_children, number_of_male, number_of_female, total_family_members) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiiii", $head_of_family, $address, $number_of_children, $number_of_male, $number_of_female, $total_family_members);
    $stmt->execute();
    $stmt->close();
    
    header("Location: ".$_SERVER['PHP_SELF']."?success=1");
    exit();
}

// Soft Delete
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $stmt = $conn->prepare("UPDATE household SET is_deleted = 1, deleted_at = NOW() WHERE household_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    header("Location: ".$_SERVER['PHP_SELF']."?deleted=1");
    exit();
}

// Restore
if (isset($_GET['restore_id'])) {
    $restore_id = (int)$_GET['restore_id'];
    $stmt = $conn->prepare("UPDATE household SET is_deleted = 0, deleted_at = NULL WHERE household_id = ?");
    $stmt->bind_param("i", $restore_id);
    $stmt->execute();
    $stmt->close();
    header("Location: ".$_SERVER['PHP_SELF']."?restored=1");
    exit();
}

// Permanent Delete
if (isset($_GET['permanent_delete_id'])) {
    $delete_id = (int)$_GET['permanent_delete_id'];
    $stmt = $conn->prepare("DELETE FROM household WHERE household_id = ? AND is_deleted = 1");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    header("Location: ".$_SERVER['PHP_SELF']."?show_deleted=1&permanent_deleted=1");
    exit();
}

// Empty Trash
if (isset($_GET['empty_trash'])) {
    $stmt = $conn->prepare("DELETE FROM household WHERE is_deleted = 1");
    $stmt->execute();
    $stmt->close();
    header("Location: ".$_SERVER['PHP_SELF']."?show_deleted=1&trash_emptied=1");
    exit();
}

// Search and filter
$search = isset($_GET['search']) ? $_GET['search'] : "";
$show_deleted = isset($_GET['show_deleted']) ? (int)$_GET['show_deleted'] : 0;

$where_clauses = [];
if ($search) {
    $where_clauses[] = "(head_of_family LIKE '%$search%' OR address LIKE '%$search%')";
}
if (!$show_deleted) {
    $where_clauses[] = "is_deleted = 0";
}

$where_clause = $where_clauses ? "WHERE " . implode(" AND ", $where_clauses) : "";
$sql = "SELECT * FROM household $where_clause ORDER BY household_id DESC";
$result = $conn->query($sql);

// Stats
$total_active_query = "SELECT COUNT(*) as count FROM household WHERE is_deleted = 0";
$total_active_result = $conn->query($total_active_query);
$total_active = $total_active_result->fetch_assoc()['count'] ?? 0;

$total_members_query = "SELECT SUM(total_family_members) as total FROM household WHERE is_deleted = 0";
$total_members_result = $conn->query($total_members_query);
$total_members = $total_members_result->fetch_assoc()['total'] ?? 0;

$deleted_count_query = "SELECT COUNT(*) as count FROM household WHERE is_deleted = 1";
$deleted_count_result = $conn->query($deleted_count_query);
$deleted_count = $deleted_count_result->fetch_assoc()['count'] ?? 0;

// PDF Generation
if (isset($_GET['generate_pdf'])) {
    ob_end_clean();
    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();
    
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Household Records in Lower Bicutan Taguig City', 0, 1, 'C');
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 10, 'Generated on: ' . date('F d, Y'), 0, 1, 'C');
    $pdf->Ln(5);

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(41, 128, 185);
    $pdf->SetTextColor(255, 255, 255);
    
    $pdf->Cell(15, 10, 'ID', 1, 0, 'C', true);
    $pdf->Cell(70, 10, 'Head of Family', 1, 0, 'C', true);
    $pdf->Cell(70, 10, 'Address', 1, 0, 'C', true);
    $pdf->Cell(30, 10, 'Children', 1, 0, 'C', true);
    $pdf->Cell(30, 10, 'Male', 1, 0, 'C', true);
    $pdf->Cell(30, 10, 'Female', 1, 0, 'C', true);
    $pdf->Cell(30, 10, 'Total Members', 1, 1, 'C', true); 

    $pdf_result = $conn->query("SELECT * FROM household WHERE is_deleted = 0 ORDER BY household_id DESC");
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    $fill = false;
    
    while ($row = $pdf_result->fetch_assoc()) {
        $pdf->Cell(15, 10, $row['household_id'], 1, 0, 'C', $fill);
        $pdf->Cell(70, 10, utf8_decode($row['head_of_family']), 1, 0, 'L', $fill);
        $pdf->Cell(70, 10, utf8_decode($row['address']), 1, 0, 'L', $fill);
        $pdf->Cell(30, 10, $row['number_of_children'], 1, 0, 'C', $fill);
        $pdf->Cell(30, 10, $row['number_of_male'], 1, 0, 'C', $fill);
        $pdf->Cell(30, 10, $row['number_of_female'], 1, 0, 'C', $fill);
        $pdf->Cell(30, 10, $row['total_family_members'], 1, 1, 'C', $fill);
        $fill = !$fill;
    }
    
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, "Total Households: $total_active | Total Members: $total_members", 0, 1, 'L');
    
    $pdf->Output('D', 'household_records.pdf');
    exit;
}

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Household Records</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #64748b;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --light: #f8fafc;
            --dark: #1e293b;
            --restore: #8b5cf6;
            --trash: #dc2626;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f1f5f9;
            color: var(--dark);
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            background: white;
            padding: 25px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            margin-bottom: 25px;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 15px;
        }
        
        .page-header h2 {
            color: var(--dark);
            margin: 0;
            font-size: 1.8rem;
        }
        
        .stats-container {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            flex: 1;
            background-color: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-left: 5px solid var(--primary);
        }
        
        .stat-card.deleted {
            border-left-color: var(--danger);
        }
        
        .stat-card h3 {
            color: var(--secondary);
            font-size: 0.9rem;
            margin: 0 0 5px 0;
        }
        
        .stat-card p {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--dark);
            margin: 0;
        }
        
        .form-container {
            background-color: #f8fafc;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .form-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .form-title h3 {
            margin: 0;
            color: var(--dark);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 5px;
            font-size: 0.9rem;
            color: var(--secondary);
        }
        
        .form-control {
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
        }
        
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            gap: 5px;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #dc2626;
        }
        
        .btn-success {
            background-color: var(--success);
            color: white;
        }
        
        .btn-success:hover {
            background-color: #059669;
        }
        
        .btn-warning {
            background-color: var(--warning);
            color: white;
        }
        
        .btn-warning:hover {
            background-color: #d97706;
        }
        
        .btn-restore {
            background-color: var(--restore);
            color: white;
        }
        
        .btn-restore:hover {
            background-color: #7c3aed;
        }
        
        .btn-trash {
            background-color: var(--trash);
            color: white;
        }
        
        .btn-trash:hover {
            background-color: #b91c1c;
        }
        
        .table-responsive {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            color: var(--dark);
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        th {
            background: var(--primary);
            color: white;
            font-weight: 500;
            position: sticky;
            top: 0;
        }
        
        tbody tr:hover {
            background-color: #f1f5f9;
        }
        
        tr.deleted {
            background-color: #fee2e2;
            opacity: 0.8;
        }
        
        tr.deleted:hover {
            background-color: #fee2e2;
        }
        
        .search-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .search-container input {
            flex: 1;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 4px solid var(--success);
        }
        
        .alert-danger {
            background-color: #fee2e2;
            color: #b91c1c;
            border-left: 4px solid var(--danger);
        }
        
        .alert-info {
            background-color: #dbeafe;
            color: #1e40af;
            border-left: 4px solid var(--primary);
        }
        
        .no-results {
            text-align: center;
            padding: 40px 0;
            color: var(--secondary);
        }
        
        .toggle-form {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--primary);
            font-size: 1.2rem;
        }
        
        .toggle-deleted {
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .deleted-info {
            font-size: 0.8rem;
            color: #6b7280;
            margin-top: 3px;
            font-style: italic;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
                margin: 20px 10px;
            }
            
            .stats-container {
                flex-direction: column;
                gap: 10px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }

            
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h2><i class="fas fa-house-user"></i> Household Records</h2>
            <div>
                <a href="?generate_pdf" class="btn btn-success">
                    <i class="fas fa-file-pdf"></i> Generate PDF
                </a>
            </div>
        </div>
        
        <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span>Household record has been successfully added.</span>
        </div>
        <?php endif; ?>
        
        <?php if(isset($_GET['deleted'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-trash-alt"></i>
            <span>Household record has been moved to trash.</span>
        </div>
        <?php endif; ?>
        
        <?php if(isset($_GET['restored'])): ?>
        <div class="alert alert-info">
            <i class="fas fa-trash-restore"></i>
            <span>Household record has been restored successfully.</span>
        </div>
        <?php endif; ?>
        
        <?php if(isset($_GET['permanent_deleted'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-trash-alt"></i>
            <span>Household record has been permanently deleted.</span>
        </div>
        <?php endif; ?>
        
        <?php if(isset($_GET['trash_emptied'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-trash-alt"></i>
            <span>All items in trash have been permanently deleted.</span>
        </div>
        <?php endif; ?>
        
        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Households</h3>
                <p><?php echo number_format($total_active); ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Population</h3>
                <p><?php echo number_format($total_members); ?></p>
            </div>
            <?php if($deleted_count > 0): ?>
            <div class="stat-card deleted">
                <h3>Deleted Records</h3>
                <p><?php echo number_format($deleted_count); ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="form-container" id="addForm">
            <div class="form-title">
                <h3><i class="fas fa-plus-circle"></i> Add New Household</h3>
            </div>
            
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="head_of_family">Head of Family</label>
                        <input type="text" id="head_of_family" name="head_of_family" class="form-control" placeholder="Full Name" required>
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" class="form-control" placeholder="Complete Address" required>
                    </div>
                    <div class="form-group">
                        <label for="number_of_children">Number of Children</label>
                        <input type="number" id="number_of_children" name="number_of_children" class="form-control" min="0" value="0" required>
                    </div>
                    <div class="form-group">
                        <label for="number_of_male">Number of Males</label>
                        <input type="number" id="number_of_male" name="number_of_male" class="form-control" min="0" value="0" required>
                    </div>
                    <div class="form-group">
                        <label for="number_of_female">Number of Females</label>
                        <input type="number" id="number_of_female" name="number_of_female" class="form-control" min="0" value="0" required>
                    </div>
                </div>
                <button type="submit" name="add_household" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Household
                </button>
            </form>
        </div>
        
        <?php if($deleted_count > 0): ?>
        <div class="toggle-deleted">
            <a href="?show_deleted=<?= $show_deleted ? 0 : 1 ?><?= $search ? '&search='.urlencode($search) : '' ?>" class="btn btn-<?= $show_deleted ? 'primary' : 'warning' ?>">
                <i class="fas fa-<?= $show_deleted ? 'eye-slash' : 'trash' ?>"></i> 
                <?= $show_deleted ? 'Hide Deleted Records' : 'View Deleted Records ('.$deleted_count.')' ?>
            </a>
            
            <?php if($show_deleted): ?>
            <a href="javascript:void(0)" onclick="if(confirm('Are you sure you want to permanently delete ALL trash items? This cannot be undone!')) { window.location='?empty_trash=1&show_deleted=1'; }" class="btn btn-trash">
                <i class="fas fa-trash"></i> Empty Trash
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="search-container">
            <form method="GET" action="" style="display: flex; width: 100%; gap: 10px;">
                <input type="hidden" name="show_deleted" value="<?= $show_deleted ?>">
                <input type="text" name="search" placeholder="Search by head of family or address..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if($search): ?>
                <a href="<?= $_SERVER['PHP_SELF'].($show_deleted ? '?show_deleted=1' : '') ?>" class="btn btn-warning">
                    <i class="fas fa-times"></i> Clear
                </a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Head of Family</th>
                        <th>Address</th>
                        <th>Children</th>
                        <th>Male</th>
                        <th>Female</th>
                        <th>Total Members</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr class="<?= $row['is_deleted'] ? 'deleted' : '' ?>">
                                <td><?= $row['household_id'] ?></td>
                                <td>
                                    <?= htmlspecialchars($row['head_of_family']) ?>
                                    <?php if($row['is_deleted'] && $row['deleted_at']): ?>
                                        <div class="deleted-info">
                                            Deleted on: <?= date('M d, Y', strtotime($row['deleted_at'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['address']) ?></td>
                                <td><?= $row['number_of_children'] ?></td>
                                <td><?= $row['number_of_male'] ?></td>
                                <td><?= $row['number_of_female'] ?></td>
                                <td><?= $row['total_family_members'] ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if($row['is_deleted']): ?>
                                            <a href="?restore_id=<?= $row['household_id'] ?>" class="btn btn-restore" onclick="return confirm('Are you sure you want to restore this household record?')">
                                                <i class="fas fa-trash-restore"></i> Restore
                                            </a>
                                            <a href="?permanent_delete_id=<?= $row['household_id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to PERMANENTLY DELETE this household record? This cannot be undone!')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        <?php else: ?>
                                            <a href="?delete_id=<?= $row['household_id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to move this household record to trash?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="no-results">
                                <i class="fas fa-info-circle"></i> No household records found.
                                <?php if($search): ?>
                                    <div>Try a different search term or <a href="<?= $_SERVER['PHP_SELF'].($show_deleted ? '?show_deleted=1' : '') ?>">view all records</a>.</div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            if (alerts.length > 0) {
                setTimeout(function() {
                    alerts.forEach(alert => {
                        alert.style.opacity = '0';
                        alert.style.transition = 'opacity 0.5s ease';
                        setTimeout(() => alert.remove(), 500);
                    });
                }, 5000);
            }
            
            // Confirmation for empty trash
            const emptyTrashBtn = document.querySelector('.btn-trash');
            if (emptyTrashBtn) {
                emptyTrashBtn.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to permanently delete ALL trash items? This cannot be undone!')) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>
<?php
ob_end_flush();
?>
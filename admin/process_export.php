<?php
// Database connection
include '../includes/config.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Get filter parameters
    $document_type = $_POST['document_type'] ?? '';
    $status = $_POST['status'] ?? '';
    $date_from = $_POST['date_from'] ?? '';
    $date_to = $_POST['date_to'] ?? '';
    $export_format = $_POST['export_format'] ?? 'csv';
    $columns = $_POST['columns'] ?? [];
    
    // Ensure we have at least one column selected
    if (empty($columns)) {
        echo "<script>
                alert('Please select at least one column to include.');
                window.history.back();
              </script>";
        exit;
    }
    
    // Build SQL query with properly formatted full name
    $sql = "SELECT 
            d.document_type,
            d.purpose,
            d.request_date,
            d.issue_date,
            CONCAT(r.lname, ', ', r.fname, ' ', IFNULL(r.mname, '')) AS full_name,
            r.age
            FROM documents d
            JOIN users u ON d.user_id = u.user_id
            LEFT JOIN residents r ON r.user_id = u.user_id
            WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if (!empty($document_type)) {
        $sql .= " AND d.document_type = ?";
        $params[] = $document_type;
        $types .= "s";
    }
    
    if (!empty($status)) {
        $sql .= " AND d.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    if (!empty($date_from)) {
        $sql .= " AND d.request_date >= ?";
        $params[] = $date_from . " 00:00:00";
        $types .= "s";
    }
    
    if (!empty($date_to)) {
        $sql .= " AND d.request_date <= ?";
        $params[] = $date_to . " 23:59:59";
        $types .= "s";
    }
    
    $sql .= " ORDER BY d.request_date DESC";
    
    // Prepare and execute the query
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Check if data exists
    if ($result->num_rows === 0) {
        echo "<script>
                alert('No data found with the selected filters.');
                window.location.href = 'index.php';
              </script>";
        exit;
    }
    
    // Process the data based on action
    if ($action === 'export') {
        exportData($result, $export_format, $columns);
    } else {
        printData($result, $columns);
    }
    
    $stmt->close();
    
} else {
    // Redirect if accessed directly
    header("Location: index.php");
    exit;
}

/**
 * Export data to the requested format
 */
function exportData($result, $format, $columns) {
    // Create data array
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $filteredRow = [];
        foreach ($columns as $column) {
            if ($column === 'request_date' || $column === 'issue_date') {
                $filteredRow[$column] = !empty($row[$column]) ? date('M d, Y', strtotime($row[$column])) : 'N/A';
            } elseif ($column === 'full_name') {
                // Clean up the full name by removing extra spaces
                $filteredRow[$column] = trim(preg_replace('/\s+/', ' ', $row['full_name']));
            } else {
                $filteredRow[$column] = $row[$column] ?? 'N/A';
            }
        }
        $data[] = $filteredRow;
    }
    
    // Format column headers
    $columnHeaders = [];
    foreach ($columns as $column) {
        $header = str_replace('_', ' ', $column);
        $header = ucwords($header);
        // Special case for full_name
        if ($column === 'full_name') {
            $header = 'Full Name';
        }
        $columnHeaders[$column] = $header;
    }
    
    // Export based on selected format
    switch ($format) {
        case 'pdf':
            exportPDF($data, $columnHeaders);
            break;
        case 'csv':
        default:
            exportCSV($data, $columnHeaders);
            break;
    }
}

/**
 * Export data to CSV format
 */
function exportCSV($data, $columnHeaders) {
    // Set headers for download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="document_requests_' . date('Y-m-d') . '.csv"');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fwrite($output, "\xEF\xBB\xBF");
    
    // Add headers
    fputcsv($output, array_values($columnHeaders));
    
    // Add data rows
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    // Close output stream
    fclose($output);
    exit;
}

/**
 * Export data to PDF format using FPDF
 */
function exportPDF($data, $columnHeaders) {
    // Require FPDF library
    require '../includes/fpdf/fpdf.php';
    
    // Create new PDF document - Force Landscape orientation
    $pdf = new FPDF('L', 'mm', 'A4');
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('Arial', 'B', 12);
    
    // Title
    $pdf->Cell(0, 10, 'Document Requests Report', 0, 1, 'C');
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
    $pdf->Ln(5);
    
    // Calculate column width based on the page width and number of columns
    $pageWidth = 277; // A4 Landscape width in mm (minus margins)
    $columnWidths = [];
    
    // Set custom widths for each column type
    foreach ($columnHeaders as $key => $header) {
        switch($key) {
            case 'full_name':
                $columnWidths[$key] = $pageWidth * 0.3; // 30% width for names
                break;
            case 'document_type':
            case 'purpose':
                $columnWidths[$key] = $pageWidth * 0.25; // 25% width for these
                break;
            case 'request_date':
            case 'issue_date':
                $columnWidths[$key] = $pageWidth * 0.2; // 20% width for dates
                break;
            default:
                $columnWidths[$key] = $pageWidth * 0.1; // 10% width for others
        }
    }
    
    // Set up the table header
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(52, 152, 219); // Blue header
    $pdf->SetTextColor(255, 255, 255); // White text
    
    foreach ($columnHeaders as $key => $header) {
        $pdf->Cell($columnWidths[$key], 8, $header, 1, 0, 'C', true);
    }
    $pdf->Ln();
    
    // Set up the table content
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(0, 0, 0); // Black text
    $pdf->SetFillColor(240, 240, 240); // Light gray for alternating rows
    
    $fill = false;
    foreach ($data as $row) {
        foreach ($columnHeaders as $key => $header) {
            $value = $row[$key] ?? '';
            // Limit text to avoid overflow based on column width
            $maxChars = floor($columnWidths[$key] / 1.5);
            $displayValue = strlen($value) > $maxChars ? substr($value, 0, $maxChars - 3) . '...' : $value;
            $pdf->Cell($columnWidths[$key], 7, $displayValue, 1, 0, 'L', $fill);
        }
        $pdf->Ln();
        $fill = !$fill; // Alternate row colors
    }
    
    // Output PDF - Force download
    $pdf->Output('D', 'document_requests_' . date('Y-m-d') . '.pdf');
    exit;
}

/**
 * Generate printable version of the data
 */
function printData($result, $columns) {
    // Start output buffering
    ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Documents</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            body {
                font-size: 10pt;
            }
            .no-print {
                display: none !important;
            }
            .table th {
                background-color: #f1f1f1 !important;
                color: #000 !important;
            }
            .container-fluid {
                width: 100%;
                padding: 0;
                margin: 0;
            }
        }
        .print-header {
            text-align: center;
            margin-bottom: 15px;
        }
        .print-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.8em;
        }
        .full-name-column {
            min-width: 200px;
        }
        .purpose-column {
            min-width: 250px;
        }
        .document-type-column {
            min-width: 150px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="print-header">
            <h3>Barangay Lower Bicutan | Document Requests Report</h3>
            <p class="small">Generated on: <?php echo date('F d, Y h:i A'); ?></p>
        </div>
        
        <div class="no-print mb-3">
            <button onclick="window.print();" class="btn btn-primary btn-sm">
                <i class="fas fa-print"></i> Print
            </button>
            <a href="../admin/document_process.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <?php foreach ($columns as $column): ?>
                        <?php 
                            $class = '';
                            if ($column === 'full_name') {
                                $class = 'full-name-column';
                            } elseif ($column === 'purpose') {
                                $class = 'purpose-column';
                            } elseif ($column === 'document_type') {
                                $class = 'document-type-column';
                            }
                        ?>
                        <th class="<?php echo $class; ?>">
                            <?php echo ucwords(str_replace('_', ' ', $column)); ?>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <?php foreach ($columns as $column): ?>
                        <td>
                            <?php 
                            if ($column === 'request_date' || $column === 'issue_date') {
                                echo !empty($row[$column]) ? date('M d, Y', strtotime($row[$column])) : 'N/A';
                            } elseif ($column === 'full_name') {
                                echo trim(preg_replace('/\s+/', ' ', $row['full_name']));
                            } else {
                                echo $row[$column] ?? 'N/A';
                            }
                            ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <div class="print-footer">
            <p>Barangay Management System &copy; <?php echo date('Y'); ?></p>
        </div>
    </div>
</body>
</html>
<?php
    // End buffering and output
    echo ob_get_clean();
    exit;
}
<?php
// Database connection
include '../includes/config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export/Print Options</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --light-bg: #f8f9fa;
        }
        
        body {
            background-color: var(--light-bg);
            color: #333;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 20px;
        }
        
        .card {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: none;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            font-weight: bold;
            padding: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .columns-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }
        
        .column-group {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .column-group h6 {
            color: var(--secondary-color);
            margin-bottom: 12px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
        }
        
        .form-check {
            margin-bottom: 8px;
        }
        
        .form-check-label {
            margin-left: 5px;
        }
        
        @media (max-width: 768px) {
            .columns-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <?php
                // Check if it's export or print
                $action = isset($_GET['action']) ? $_GET['action'] : '';
                
                if ($action == 'export') {
                    $title = "Export Documents";
                    $icon = "fa-file-export";
                    $btnText = "Export";
                    $btnClass = "btn-success";
                } else {
                    $title = "Print Documents";
                    $icon = "fa-print";
                    $btnText = "Print";
                    $btnClass = "btn-primary";
                }
                ?>
                
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="m-0"><i class="fas <?php echo $icon; ?> me-2"></i> <?php echo $title; ?></h3>
                        <a href="../admin/document_process.php" class="btn btn-outline-light btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                    <div class="card-body">
                        <form action="process_export.php" method="POST">
                            <input type="hidden" name="action" value="<?php echo $action; ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="document_type" class="form-label">Document Type</label>
                                        <select name="document_type" id="document_type" class="form-select">
                                            <option value="">All Documents</option>
                                            <option value="Certificate of Indigency">Certificate of Indigency</option>
                                            <option value="Certificate of Residency">Certificate of Residency</option>
                                            <option value="Barangay Clearance">Barangay Clearance</option>
                                            <option value="Barangay Business Clearance">Barangay Business Clearance</option>
                                            <option value="Clearance for First-time Job Seeker">Clearance for First-time Job Seeker</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="status" class="form-label">Status</label>
                                        <select name="status" id="status" class="form-select">
                                            <option value="">All Statuses</option>
                                            <option value="pending">Pending</option>
                                            <option value="approved">Approved</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Date Range</label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="date_from" class="form-label">From</label>
                                        <input type="date" name="date_from" id="date_from" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="date_to" class="form-label">To</label>
                                        <input type="date" name="date_to" id="date_to" class="form-control">
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($action == 'export') { ?>
                            <div class="form-group">
                                <label for="export_format" class="form-label">Export Format</label>
                                <select name="export_format" id="export_format" class="form-select">
                                    <option value="csv">CSV</option>
                                    <option value="pdf">PDF</option>
                                </select>
                            </div>
                            <?php } ?>
                            
                            <div class="form-group">
                                <label class="form-label">Select Columns to Include</label>
                                <div class="columns-container">
                                    <!-- Personal Information Group -->
                                    <div class="column-group">
                                        <h6>Personal Information</h6>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="columns[]" value="full_name" id="col_full_name" checked>
                                            <label class="form-check-label" for="col_full_name">Full Name</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="columns[]" value="age" id="col_age">
                                            <label class="form-check-label" for="col_age">Age</label>
                                        </div>
                                    </div>
                                    
                                    <!-- Document Information Group -->
                                    <div class="column-group">
                                        <h6>Document Information</h6>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="columns[]" value="document_type" id="col_doc_type" checked>
                                            <label class="form-check-label" for="col_doc_type">Document Type</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="columns[]" value="purpose" id="col_purpose" checked>
                                            <label class="form-check-label" for="col_purpose">Purpose</label>
                                        </div>
                                    </div>
                                    
                                    <!-- Date Information Group -->
                                    <div class="column-group">
                                        <h6>Date Information</h6>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="columns[]" value="request_date" id="col_req_date" checked>
                                            <label class="form-check-label" for="col_req_date">Request Date</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="columns[]" value="issue_date" id="col_issue_date">
                                            <label class="form-check-label" for="col_issue_date">Issue Date</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" class="btn <?php echo $btnClass; ?> btn-lg px-4">
                                    <i class="fas <?php echo $icon; ?> me-2"></i> <?php echo $btnText; ?> Documents
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap script -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set default dates (current month)
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            
            document.getElementById('date_from').valueAsDate = firstDay;
            document.getElementById('date_to').valueAsDate = lastDay;
            
            // Select all/none functionality
            document.getElementById('selectAll').addEventListener('click', function() {
                document.querySelectorAll('.form-check-input').forEach(checkbox => {
                    checkbox.checked = true;
                });
            });
            
            document.getElementById('selectNone').addEventListener('click', function() {
                document.querySelectorAll('.form-check-input').forEach(checkbox => {
                    checkbox.checked = false;
                });
            });
        });
    </script>
</body>
</html>
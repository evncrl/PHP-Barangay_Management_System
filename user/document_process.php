<?php 
include '../includes/config.php'; 
include '../includes/header.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red; text-align: center; font-size: 18px;'>
            Please log in to access this page. <a href='/saad/user/login.php'>Log in here</a>.
          </p>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details from residents table including occupation
$sql = "SELECT fname, mname, lname, occupation FROM residents WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
$full_name = ($user) ? trim("{$user['fname']} {$user['mname']} {$user['lname']}") : 'Unknown User';

// Determine default category based on occupation
$default_category = 'regular';
$category_locked = false;
if (isset($user['occupation'])) {
    $occupation = strtolower($user['occupation']);
    if (strpos($occupation, 'student') !== false) {
        $default_category = 'student';
        $category_locked = true;
    } elseif (strpos($occupation, 'senior') !== false || strpos($occupation, 'retired') !== false) {
        $default_category = 'senior';
        $category_locked = true;
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $document_type = isset($_POST['document_type']) ? trim($_POST['document_type']) : '';
    $purpose = isset($_POST['purpose']) ? trim($_POST['purpose']) : '';
    $age_category = isset($_POST['age_category']) ? trim($_POST['age_category']) : $default_category;

    if ($document_type === '' || $purpose === '') {
        echo "<script>alert('Please fill in all required fields.'); history.back();</script>";
        exit;
    }

    // Initialize variables
    $receipt_path = '';
    $payment_required = false;
    $document_price = 0;
    
    // Set prices based on document type
    if ($document_type === "Barangay Clearance" || $document_type === "Certificate of Residency" || $document_type === "Certificate of Indigency") {
        $document_price = 50;
    } elseif ($document_type === "Barangay Business Clearance") {
        $document_price = 100;
    }
    
    // Adjust price based on category
    if ($age_category === "student" || $age_category === "senior") {
        $document_price = 0;
    } elseif ($age_category === "regular" && $document_type === "Clearance for First-time Job Seeker") {
        $document_price = 0;
    }
    
    $payment_required = ($document_price > 0);

    // Handle file upload if payment is required
    if ($payment_required) {
        if (isset($_FILES["receipt"]) && $_FILES["receipt"]["error"] == 0) {
            $target_dir = "../user/uploads/";

            // Ensure the uploads directory exists
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $file_name = basename($_FILES["receipt"]["name"]);
            $target_file = $target_dir . $file_name;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Validate file type (only JPG and PNG allowed)
            $allowed_types = ["jpg", "jpeg", "png"];
            if (!in_array($imageFileType, $allowed_types)) {
                echo "<script>alert('Only JPG, JPEG, and PNG files are allowed.'); history.back();</script>";
                exit;
            }

            // Move the uploaded file to the target directory
            if (move_uploaded_file($_FILES["receipt"]["tmp_name"], $target_file)) {
                $receipt_path = $target_file;
            } else {
                echo "<script>alert('Error uploading file.'); history.back();</script>";
                exit;
            }
        } else {
            echo "<script>alert('Please upload a payment receipt.'); history.back();</script>";
            exit;
        }
        
        // Validate payment fields
        $gcash_number = isset($_POST['gcash_number']) ? trim($_POST['gcash_number']) : '';
        $reference_number = isset($_POST['reference_number']) ? trim($_POST['reference_number']) : '';
        
        if (empty($gcash_number) || empty($reference_number)) {
            echo "<script>alert('Please fill in all payment information.'); history.back();</script>";
            exit;
        }
    }

    // Insert document request into the documents table
    $sql = "INSERT INTO documents (user_id, document_type, purpose, status, request_date, receipt_file) 
            VALUES (?, ?, ?, 'pending', NOW(), ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isss", $user_id, $document_type, $purpose, $receipt_path);

    if (!mysqli_stmt_execute($stmt)) {
        echo "<script>alert('Error submitting request: " . mysqli_error($conn) . "'); history.back();</script>";
        exit;
    }

    // Proceed with storing business details if the document type is Barangay Business Clearance
    if ($document_type === "Barangay Business Clearance") {
        $business_name = isset($_POST['business_name']) ? trim($_POST['business_name']) : '';
        $business_owner = isset($_POST['business_owner']) ? trim($_POST['business_owner']) : $full_name;
        $business_address = isset($_POST['business_address']) ? trim($_POST['business_address']) : '';

        if ($business_name === '' || $business_address === '') {
            echo "<script>alert('Please enter business details.'); history.back();</script>";
            exit;
        }

        // Insert into business_list table
        $business_sql = "INSERT INTO business_list (user_id, resident_id, business_name, business_owner, business_address) 
                         VALUES (?, (SELECT resident_id FROM residents WHERE user_id = ?), ?, ?, ?)";
        $business_stmt = mysqli_prepare($conn, $business_sql);
        mysqli_stmt_bind_param($business_stmt, "iisss", $user_id, $user_id, $business_name, $business_owner, $business_address);

        if (!mysqli_stmt_execute($business_stmt)) {
            echo "<script>alert('Error storing business information: " . mysqli_error($conn) . "'); history.back();</script>";
            exit;
        }
        mysqli_stmt_close($business_stmt);
    }

    mysqli_stmt_close($stmt);
    echo "<script>alert('Your document request has been submitted successfully!'); window.location.href='document_process.php';</script>";
    exit;
}

// Fetch user's document requests
$request_sql = "SELECT document_id, document_type, purpose, status, request_date, issue_date, file_path 
                FROM documents WHERE user_id = ? ORDER BY request_date DESC";
$request_stmt = mysqli_prepare($conn, $request_sql);
mysqli_stmt_bind_param($request_stmt, "i", $user_id);
mysqli_stmt_execute($request_stmt);
$request_result = mysqli_stmt_get_result($request_stmt);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
   
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Request</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f7f9fc;
            margin: 0;
            padding: 0;
            color: #333;
        }
        
        .locked-field {
            background-color: #f0f2f5;
            cursor: not-allowed;
            border-color: #dde1e7;
            color: #555;
        }

        .page-wrapper {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
            padding-top: 10px;
        }

        .container {
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            padding: 30px;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }

        .container:hover {
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
        }

        h2 {
            color: #223F61;
            font-size: 26px;
            margin-bottom: 25px;
            position: relative;
            text-align: center;
            font-weight: 600;
        }

        h2:after {
            content: '';
            position: absolute;
            width: 60px;
            height: 3px;
            background-color: #223F61;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-size: 15px;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }

        input, select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e1e5ea;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
            color: #333;
            background-color: #f9fafc;
        }

        input:focus, select:focus {
            border-color: #223F61;
            outline: none;
            box-shadow: 0 0 0 3px rgba(34, 63, 97, 0.1);
        }

        input[readonly] {
            background-color: #f0f2f5;
            cursor: not-allowed;
            border-color: #dde1e7;
        }

        .form-section {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eaecef;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }

        .form-col {
            flex: 1;
            padding: 0 10px;
            min-width: 200px;
        }

        .upload-area {
            border: 2px dashed #d0d7de;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-top: 10px;
            transition: all 0.3s;
            background-color: #f9fafc;
            cursor: pointer;
        }

        .upload-area:hover {
            border-color: #223F61;
        }

        .upload-icon {
            color: #223F61;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .file-input {
            display: none;
        }

        .preview-area {
            margin-top: 15px;
            max-width: 100%;
            text-align: center;
        }

        .preview-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .btn {
            display: inline-block;
            background-color: #223F61;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            width: auto;
            text-align: center;
        }

        .btn:hover {
            background-color: #1a2f49;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(34, 63, 97, 0.2);
        }

        .btn-full {
            width: 100%;
        }

        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        thead {
            background-color: #223F61;
            color: white;
        }

        th {
            padding: 15px 20px;
            text-align: left;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 0.5px;
        }

        td {
            padding: 15px 20px;
            border-bottom: 1px solid #edf2f7;
            color: #4a5568;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: #f9fafc;
        }

        .status-pending {
            color: #f59e0b;
            font-weight: 600;
            display: inline-block;
            padding: 5px 10px;
            border-radius: 6px;
            background-color: rgba(245, 158, 11, 0.1);
        }

        .status-approved {
            color: #10b981;
            font-weight: 600;
            display: inline-block;
            padding: 5px 10px;
            border-radius: 6px;
            background-color: rgba(16, 185, 129, 0.1);
        }

        .status-rejected {
            color: #ef4444;
            font-weight: 600;
            display: inline-block;
            padding: 5px 10px;
            border-radius: 6px;
            background-color: rgba(239, 68, 68, 0.1);
        }

        .download-link {
            color: #223F61;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s;
        }

        .download-link:hover {
            color: #1a2f49;
            text-decoration: underline;
        }

        .download-link i {
            margin-right: 5px;
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }
            
            .form-col {
                margin-bottom: 15px;
            }
            
            .container {
                padding: 20px;
            }
            
            th, td {
                padding: 12px 15px;
            }
            
            h2 {
                font-size: 22px;
            }
        }

        #business_fields {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.5s ease-out;
        }

        #business_fields.visible {
            max-height: 500px;
            transition: max-height 0.5s ease-in;
        }

        #payment_fields {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.5s ease-out;
        }

        #payment_fields.visible {
            max-height: 800px;
            transition: max-height 0.5s ease-in;
        }

        .price-badge {
            display: inline-block;
            background-color: #223F61;
            color: white;
            font-size: 13px;
            padding: 3px 8px;
            border-radius: 20px;
            margin-left: 10px;
            font-weight: 500;
        }

        .price-free {
            background-color: #10b981;
        }

        .nav-tabs {
            display: flex;
            border-bottom: 1px solid #e1e5ea;
            margin-bottom: 20px;
        }

        .nav-tab {
            padding: 10px 20px;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-weight: 500;
        }

        .nav-tab.active {
            color: #223F61;
            border-bottom-color: #223F61;
        }

        .empty-state {
            text-align: center;
            padding: 40px 0;
            color: #a0aec0;
        }

        .empty-icon {
            font-size: 48px;
            margin-bottom: 15px;
            color: #cbd5e0;
        }

        .empty-text {
            font-size: 16px;
        }

        .barangay-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e1e5ea;
        }

        .barangay-name {
            color: #223F61;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .gcash-info {
            background-color: #f0f7ff;
            border-left: 4px solid #223F61;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .gcash-info-title {
            display: flex;
            align-items: center;
            font-weight: 600;
            color: #223F61;
            margin-bottom: 10px;
        }

        .gcash-info-title i {
            margin-right: 8px;
            font-size: 18px;
        }

        .gcash-number {
            font-size: 16px;
            font-weight: 500;
            color: #2d3748;
            display: flex;
            align-items: center;
        }

        .gcash-number i {
            margin-right: 8px;
            color: #1f8efa;
        }

        .payment-note {
            font-size: 14px;
            color: #718096;
            margin-top: 8px;
            font-style: italic;
        }

        .highlight {
            border-color: #223F61 !important;
            background-color: #e6f0ff !important;
        }
    </style>
</head>
<body>
<div class="page-wrapper">
    <div class="barangay-header">
        <h1 class="barangay-name">Barangay Lower Bicutan</h1>
        <p>Document Request Portal</p>
    </div>

    <div class="container">
        <h2>Request a Document</h2>
        <form action="" method="POST" enctype="multipart/form-data" id="documentForm">
            
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label for="name"><i class="fas fa-user"></i> Your Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($full_name); ?>" readonly class="locked-field">
                    </div>
                </div>
                
                <div class="form-col">
                    <div class="form-group">
                        <label for="age_category"><i class="fas fa-id-card"></i> Category</label>
                        <?php if ($category_locked): ?>
                            <input type="text" id="age_category_display" value="<?php echo ucfirst($default_category); ?>" readonly class="locked-field">
                            <input type="hidden" id="age_category" name="age_category" value="<?php echo $default_category; ?>">
                        <?php else: ?>
                            <select id="age_category" name="age_category" required onchange="togglePaymentFields()">
                                <option value="regular" <?php echo ($default_category === 'regular') ? 'selected' : ''; ?>>Regular</option>
                                <option value="student" <?php echo ($default_category === 'student') ? 'selected' : ''; ?>>Student</option>
                                <option value="senior" <?php echo ($default_category === 'senior') ? 'selected' : ''; ?>>Senior Citizen</option>
                            </select>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            
            <div class="form-section">
                <div class="form-group">
                    <label for="document_type"><i class="fas fa-file-alt"></i> Document Type</label>
                    <select id="document_type" name="document_type" required onchange="togglePaymentFields()">
                        <option value="">-- Select Document --</option>
                        <option value="Barangay Clearance" data-price="50">Barangay Clearance</option>
                        <option value="Certificate of Residency" data-price="50">Certificate of Residency</option>
                        <option value="Certificate of Indigency" data-price="50">Certificate of Indigency</option>
                        <option value="Clearance for First-time Job Seeker" data-price="0">Clearance for First-time Job Seeker</option>
                        <option value="Barangay Business Clearance" data-price="100">Barangay Business Clearance</option>
                    </select>
                </div>

                <div id="business_fields" class="business-container">
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="business_name"><i class="fas fa-store"></i> Business Name</label>
                                <input type="text" id="business_name" name="business_name">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="business_owner"><i class="fas fa-user-tie"></i> Business Owner</label>
                                <input type="text" id="business_owner" name="business_owner" value="<?php echo htmlspecialchars($full_name); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="business_address"><i class="fas fa-map-marker-alt"></i> Business Address</label>
                        <input type="text" id="business_address" name="business_address">
                    </div>
                </div>
            </div>

            <div id="payment_fields" class="payment-container">
                <div class="form-section">
                    <h3 style="margin-top: 0;"><i class="fas fa-money-bill-wave"></i> Payment Information</h3>
                    
                    <div class="gcash-info">
                        <div class="gcash-info-title">
                            <i class="fas fa-info-circle"></i> Payment Instructions
                        </div>
                        <div class="gcash-number">
                            <i class="fas fa-mobile-alt"></i> GCash Number: <strong>09755823240</strong>
                        </div>
                        <p class="payment-note">Please make your payment to the GCash number above and upload your receipt below.</p>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="gcash_number"><i class="fas fa-mobile-alt"></i> Your GCash Number</label>
                                <input type="text" name="gcash_number" id="gcash_number" pattern="09[0-9]{9}" placeholder="09XXXXXXXXX">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="amount"><i class="fas fa-tag"></i> Amount</label>
                                <div class="amount-wrapper" style="position: relative;">
                                    <span style="position: absolute; left: 15px; top: 12px;">₱</span>
                                    <input type="number" id="amount" name="amount" min="1" readonly style="padding-left: 30px;">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="reference_number"><i class="fas fa-receipt"></i> Reference Number</label>
                        <input type="text" id="reference_number" name="reference_number" placeholder="Enter GCash Reference No.">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-upload"></i> Upload Receipt (JPG/PNG only)</label>
                        <div class="upload-area" id="uploadArea" onclick="triggerFileInput()">
                            <div class="upload-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <p>Click to select a file or drag & drop here</p>
                            <input type="file" id="receipt" name="receipt" accept="image/png, image/jpeg" class="file-input" onchange="previewImage(event)">
                        </div>
                        <div class="preview-area" id="previewArea">
                            <img id="receipt_preview" src="#" alt="Receipt Preview" class="preview-image" style="display:none;">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="purpose"><i class="fas fa-clipboard-list"></i> Purpose</label>
                <input type="text" id="purpose" name="purpose" required placeholder="Explain why you need this document">
            </div>

            <button type="submit" class="btn btn-full">
                <i class="fas fa-paper-plane"></i> Submit Request
            </button>
        </form>
    </div>

    <div class="container">
        <h2>Your Document Requests</h2>
        
        <div class="nav-tabs">
            <div class="nav-tab active" onclick="showTab('all-tab')">All Requests</div>
            <div class="nav-tab" onclick="showTab('pending-tab')">Pending</div>
            <div class="nav-tab" onclick="showTab('approved-tab')">Approved</div>
        </div>
        
        <div class="table-container">
            <?php if (mysqli_num_rows($request_result) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-file-alt"></i> Document Type</th>
                            <th><i class="fas fa-info-circle"></i> Purpose</th>
                            <th><i class="fas fa-spinner"></i> Status</th>
                            <th><i class="fas fa-calendar-alt"></i> Requested</th>
                            <th><i class="fas fa-calendar-check"></i> Issued</th>
                            <th><i class="fas fa-download"></i> Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($request_result)): ?>
                            <tr class="request-row <?php echo 'status-' . strtolower($row['status']) . '-row'; ?>">
                                <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $row['document_type']))); ?></td>
                                <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                                <td>
                                    <span class="<?php echo 'status-' . strtolower($row['status']); ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date("M j, Y", strtotime($row['request_date'])); ?></td>
                                <td><?php echo $row['issue_date'] ? date("M j, Y", strtotime($row['issue_date'])) : 'N/A'; ?></td>
                                <td>
                                    <?php if ($row['status'] === 'approved' && !empty($row['file_path'])): ?>
                                        <a href="<?php echo htmlspecialchars($row['file_path']); ?>" download class="download-link">
                                            <i class="fas fa-file-pdf"></i> Download
                                        </a>
                                    <?php else: ?>
                                        <span class="not-available">Not Available</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <p class="empty-text">You haven't made any document requests yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function previewImage(event) {
        var input = event.target;
        var preview = document.getElementById("receipt_preview");
        var previewArea = document.getElementById("previewArea");

        if (input.files && input.files[0]) {
            var reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = "block";
                previewArea.style.display = "block";
            };
            
            reader.readAsDataURL(input.files[0]);
        }
    }

    function triggerFileInput() {
        document.getElementById('receipt').click();
    }

    function toggleRequiredFields(required) {
        const paymentFields = document.querySelectorAll('#payment_fields input, #payment_fields select');
        paymentFields.forEach(field => {
            field.required = required;
            if (!required) {
                field.value = ''; // Clear the field when not required
            }
        });
        
        // Special handling for receipt file input
        const receiptInput = document.getElementById('receipt');
        receiptInput.required = required;
        if (!required) {
            receiptInput.value = '';
            document.getElementById('receipt_preview').style.display = 'none';
        }
    }

    function togglePaymentFields() {
        var documentType = document.getElementById("document_type");
        var selectedOption = documentType.options[documentType.selectedIndex];
        var price = parseInt(selectedOption.getAttribute("data-price")) || 0;
        var paymentFields = document.getElementById("payment_fields");
        var businessFields = document.getElementById("business_fields");
        var amountField = document.getElementById("amount");
        
        // Get the age category value - either from select or hidden input
        var ageCategory = document.getElementById("age_category").value;

        // Show business fields only for Barangay Business Clearance
        if (documentType.value === "Barangay Business Clearance") {
            businessFields.classList.add('visible');
            // Make business fields required
            document.getElementById('business_name').required = true;
            document.getElementById('business_address').required = true;
        } else {
            businessFields.classList.remove('visible');
            // Make business fields not required
            document.getElementById('business_name').required = false;
            document.getElementById('business_address').required = false;
        }

        // If user is Student or Senior Citizen, all documents are free
        if (ageCategory === "student" || ageCategory === "senior") {
            price = 0;
        }

        // If user is Regular, only First-time Job Seeker Clearance is free
        if (ageCategory === "regular" && documentType.value === "Clearance for First-time Job Seeker") {
            price = 0;
        }

        // Show or hide payment fields based on price
        if (price > 0) {
            paymentFields.classList.add('visible');
            amountField.value = price;
            toggleRequiredFields(true);
        } else {
            paymentFields.classList.remove('visible');
            amountField.value = "0";
            toggleRequiredFields(false);
        }
    }

    function showTab(tabId) {
        // Activate the clicked tab
        document.querySelectorAll('.nav-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        event.currentTarget.classList.add('active');
        
        // Show/hide table rows based on tab
        const rows = document.querySelectorAll('.request-row');
        rows.forEach(row => {
            if (tabId === 'all-tab') {
                row.style.display = '';
            } else if (tabId === 'pending-tab' && row.classList.contains('status-pending-row')) {
                row.style.display = '';
            } else if (tabId === 'approved-tab' && row.classList.contains('status-approved-row')) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Form validation before submission
    document.getElementById('documentForm').addEventListener('submit', function(e) {
        const documentType = document.getElementById('document_type').value;
        const purpose = document.getElementById('purpose').value;
        const paymentFieldsVisible = document.getElementById('payment_fields').classList.contains('visible');
        
        // Basic validation
        if (!documentType || !purpose) {
            alert('Please fill in all required fields.');
            e.preventDefault();
            return;
        }
        
        // Payment fields validation if visible
        if (paymentFieldsVisible) {
            const gcashNumber = document.getElementById('gcash_number').value;
            const referenceNumber = document.getElementById('reference_number').value;
            const receipt = document.getElementById('receipt').files[0];
            
            if (!gcashNumber || !referenceNumber || !receipt) {
                alert('Please fill in all payment information.');
                e.preventDefault();
                return;
            }
            
            // Validate GCash number format
            if (!/^09\d{9}$/.test(gcashNumber)) {
                alert('Please enter a valid GCash number (09XXXXXXXXX).');
                e.preventDefault();
                return;
            }
        }
        
        // Business fields validation if needed
        if (documentType === 'Barangay Business Clearance') {
            const businessName = document.getElementById('business_name').value;
            const businessAddress = document.getElementById('business_address').value;
            
            if (!businessName || !businessAddress) {
                alert('Please fill in all business information.');
                e.preventDefault();
                return;
            }
        }
    });

    // Initialize the form
    document.addEventListener('DOMContentLoaded', function() {
        togglePaymentFields();
        
        // Add drag and drop for file upload
        const uploadArea = document.getElementById('uploadArea');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            uploadArea.classList.add('highlight');
        }
        
        function unhighlight() {
            uploadArea.classList.remove('highlight');
        }
        
        uploadArea.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            const fileInput = document.getElementById('receipt');
            
            fileInput.files = files;
            previewImage({target: fileInput});
        }
    });
</script>
</body>
</html>
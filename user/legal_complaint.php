<?php
include '../includes/config.php';
include '../includes/header.php';

// Check if user is logged in as resident
if (!isset($_SESSION['user_id'])) {
    echo "<div class='alert alert-danger'>
            <i class='fas fa-exclamation-circle'></i> Please log in to access this page. 
            <a href='/saad/user/login.php' class='alert-link'>Log in here</a>.
          </div>";
    exit();
}

// Function to file a complaint
function fileComplaint($user_id, $accussed_person, $complaint_type, $description, $image, $conn) {
    $stmt = $conn->prepare("INSERT INTO complaints (user_id, accussed_person, complaint_type, description, image, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("issss", $user_id, $accussed_person, $complaint_type, $description, $image);

    return $stmt->execute();
}


$message = ""; // Store message
$message_type = ""; // Store message type (success or error)

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $complaint_type = $_POST['complaint_type'];
    $description = $_POST['description'];
    $accussed_person = $_POST['accussed_person'];

    if ($complaint_type === "other" && !empty($_POST['other_complaint'])) {
        $complaint_type = $_POST['other_complaint'];
    }

    $user_id = $_SESSION['user_id'];
    $image = null;

    // Handle image upload
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "../user/uploads/";
        $image = basename($_FILES['image']['name']);
        $target_file = $target_dir . $image;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Validate file type
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($imageFileType, $allowed_types)) {
            $message = "Only JPG, JPEG, PNG, and GIF files are allowed.";
            $message_type = "error";
        } else {
            // Move file to uploads directory
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image = $target_file; // Store path in database
            } else {
                $message = "Error uploading the image.";
                $message_type = "error";
            }
        }
    }

    $success = fileComplaint($user_id, $accussed_person, $complaint_type, $description, $image, $conn);

    if ($success) {
        $message = "Your complaint has been filed successfully. The Purok Leader will personally deliver the subpoena within three (3) business days. ";
        $message_type = "success";
    } else {
        $message = "Error filing complaint. Please try again later.";
        $message_type = "error";
    }
}


// Function to fetch complaints for the logged-in user
function getComplaints($user_id, $conn) {
    $sql = "SELECT complaint_id, complaint_type, description, status, created_at, accussed_person, image 
            FROM complaints 
            WHERE user_id = ? 
            ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $complaints = [];
    while ($row = $result->fetch_assoc()) {
        $complaints[] = $row;
    }
    return $complaints;
}


// Get status color
function getStatusColor($status) {
    switch (strtolower($status)) {
        case 'pending':
            return '#FFC107'; // Amber
        case 'in progress':
            return '#2196F3'; // Blue
        case 'resolved':
            return '#4CAF50'; // Green
        case 'rejected':
            return '#F44336'; // Red
        default:
            return '#9E9E9E'; // Grey
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File a Complaint</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        :root {
            --primary-color: #1a237e;
            --primary-light: #534bae;
            --primary-dark: #000051;
            --accent-color: #ff6d00;
            --text-color: #333333;
            --light-gray: #f5f7fa;
            --medium-gray: #e1e5eb;
            --dark-gray: #6c757d;
            --white: #ffffff;
            --danger: #d32f2f;
            --success: #388e3c;
            --warning: #f57c00;
            --info: #0288d1;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-gray);
            color: var(--text-color);
            line-height: 1.6;
        }
        
        .page-title {
            text-align: center;
            color: var(--primary-color);
            margin: 2rem 0;
            font-size: 2.2rem;
            position: relative;
            padding-bottom: 0.5rem;
        }
        
        .page-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background-color: var(--accent-color);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .card {
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 1.5rem;
            position: relative;
        }
        
        .card-header h3 {
            font-size: 1.5rem;
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .card-header h3 i {
            margin-right: 10px;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group.full-width {
            grid-column: span 2;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--primary-dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            font-size: 1rem;
            border: 1px solid var(--medium-gray);
            border-radius: 6px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(83, 75, 174, 0.25);
            outline: none;
        }
        
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236c757d' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
        }
        
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        
        .btn {
            display: inline-block;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            cursor: pointer;
            padding: 12px 24px;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 6px;
            transition: all 0.2s ease-in-out;
            border: none;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .btn-large {
            padding: 14px 28px;
            font-size: 1.1rem;
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .alert-success {
            background-color: rgba(56, 142, 60, 0.1);
            border-left: 4px solid var(--success);
            color: var(--success);
        }
        
        .alert-danger {
            background-color: rgba(211, 47, 47, 0.1);
            border-left: 4px solid var(--danger);
            color: var(--danger);
        }
        
        .alert-link {
            font-weight: 700;
            text-decoration: none;
            color: inherit;
        }
        
        .alert-link:hover {
            text-decoration: underline;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .complaint-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .complaint-table th {
            background-color: var(--primary-dark);
            color: var(--white);
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .complaint-table td {
            padding: 15px;
            border-bottom: 1px solid var(--medium-gray);
            vertical-align: top;
        }
        
        .complaint-table tr:last-child td {
            border-bottom: none;
        }
        
        .complaint-table tr:hover {
            background-color: rgba(83, 75, 174, 0.05);
        }
        
        .complaint-status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        
        .description-cell {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .table-empty {
            text-align: center;
            padding: 2rem;
            color: var(--dark-gray);
            font-style: italic;
        }
        
        .accordion-toggle {
            cursor: pointer;
        }
        
        .accordion-content {
            display: none;
            padding: 1rem;
            background-color: var(--light-gray);
            border-radius: 6px;
            margin-top: 0.5rem;
            white-space: normal;
        }
        
        .timestamp {
            color: var(--dark-gray);
            font-size: 0.85rem;
        }
        
        .text-center {
            text-align: center;
        }
        
        .mt-4 {
            margin-top: 2rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-group.full-width {
                grid-column: span 1;
            }
            
            .card-body {
                padding: 1.5rem;
            }
            
            .complaint-table th, 
            .complaint-table td {
                padding: 10px;
            }
            
            .btn-large {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h1 class="page-title">File a Complaint</h1>
    
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-exclamation-circle"></i> Submit New Complaint</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $message_type === 'success' ? 'alert-success' : 'alert-danger'; ?>">
                    <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="accussed_person">Resident Name (Whom You Are Complaining About):</label>
                    <input type="text" id="accussed_person" name="accussed_person" class="form-control" required>
                </div>

                <div class="form-group">
    <label for="complaint_type">Complaint Type:</label>
    <select id="complaint_type" name="complaint_type" class="form-control" required onchange="toggleOtherInput()">
        <option value="">-- Select Complaint Type --</option>
        <option value="noise">Noise Disturbance</option>
        <option value="security">Security Concern</option>
        <option value="property_damage">Property Damage</option>
        <option value="other">Other</option>
    </select>
</div>

<!-- Other Complaint Type Input -->
<div class="form-group" id="other_complaint_container" style="display: none;">
    <label for="other_complaint">Specify Other Complaint:</label>
    <input type="text" id="other_complaint" name="other_complaint" class="form-control">
</div>

<script>
    function toggleOtherInput() {
        var complaintType = document.getElementById("complaint_type").value;
        var otherContainer = document.getElementById("other_complaint_container");
        var otherInput = document.getElementById("other_complaint");

        if (complaintType === "other") {
            otherContainer.style.display = "block";
            otherInput.setAttribute("required", "required");
        } else {
            otherContainer.style.display = "none";
            otherInput.removeAttribute("required");
            otherInput.value = ""; // Clears the input field when hidden
        }
    }
</script>

                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" class="form-control" required></textarea>
                </div>

                <div class="form-group">
                    <label for="image">Upload Evidence (Image):</label>
                    <input type="file" id="image" name="image" class="form-control" accept="image/*">
                </div>

                <button type="submit" name="submit_complaint" class="btn btn-primary">Submit Complaint</button>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-history"></i> Your Complaint History</h3>
        </div>
        <div class="card-body">
            <?php
            $user_id = $_SESSION['user_id'];
            $complaints = getComplaints($user_id, $conn);
            ?>
            
            <div class="table-responsive">
                <?php if (!empty($complaints)): ?>
                    <table class="complaint-table">
                        <thead>
                            <tr>
                                <th>Accused Person</th>
                                <th>Complaint Type</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Evidence</th>
                                <th>Date Filed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($complaints as $complaint): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($complaint['accussed_person']); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($complaint['complaint_type'])); ?></td>
                                    <td class="description-cell">
                                        <div class="accordion-toggle" onclick="toggleDescription(this)">
                                            <?php echo htmlspecialchars(substr($complaint['description'], 0, 50)) . (strlen($complaint['description']) > 50 ? '...' : ''); ?>
                                            <i class="fas fa-chevron-down" style="margin-left: 5px; font-size: 0.8rem;"></i>
                                        </div>
                                        <div class="accordion-content">
                                            <?php echo nl2br(htmlspecialchars($complaint['description'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="complaint-status" style="background-color: <?php echo getStatusColor($complaint['status']); ?>20; color: <?php echo getStatusColor($complaint['status']); ?>;">
                                            <?php echo ucfirst(htmlspecialchars($complaint['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($complaint['image'])): ?>
                                            <a href="<?php echo htmlspecialchars($complaint['image']); ?>" target="_blank">
                                                <img src="<?php echo htmlspecialchars($complaint['image']); ?>" width="100">
                                            </a>
                                        <?php else: ?>
                                            No Evidence
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="timestamp">
                                            <?php 
                                            $date = new DateTime($complaint['created_at']);
                                            echo $date->format('M d, Y - h:i A'); 
                                            ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="table-empty">
                        <i class="fas fa-info-circle"></i> You haven't filed any complaints yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>

    function toggleDescription(element) {
        var content = element.nextElementSibling;
        var icon = element.querySelector('i');
        
        if (content.style.display === "block") {
            content.style.display = "none";
            icon.className = "fas fa-chevron-down";
        } else {
            content.style.display = "block";
            icon.className = "fas fa-chevron-up";
        }
    }
</script>


</body>
</html>
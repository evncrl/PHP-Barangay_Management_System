<?php
include '../includes/config.php';
include_once '../includes/adminHeader.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../src/Exception.php';
require '../src/PHPMailer.php';
require '../src/SMTP.php';

// Check if the user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access denied. Admins only.");
}

// Function to send email notification
function sendComplaintStatusEmail($email, $name, $complaint_id, $accused_person, $complaint_type, $status, $appointment_date = null, $appointment_time = null) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'romerosfragrance@gmail.com';
        $mail->Password = 'rrzy yexb qevi hxmw';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('romerosfragrance@gmail.com', 'Barangay Lower Bicutan');
        $mail->addAddress($email, $name);

        // Content
        $mail->isHTML(true);
        
        if ($status === 'in_progress') {
            $mail->Subject = 'Complaint Update: In Progress - ' . htmlspecialchars($complaint_type) . ' vs ' . htmlspecialchars($accused_person);
            $mail->Body = "
                <h2>Complaint Status Update</h2>
                <p>Dear $name,</p>
                <p>Your complaint regarding <strong>" . htmlspecialchars($complaint_type) . "</strong> against <strong>" . htmlspecialchars($accused_person) . "</strong> (Case ID: $complaint_id) is now <strong>In Progress</strong>.</p>
                <p>We have scheduled an appointment for resolution:</p>
                <p><strong>Date:</strong> $appointment_date</p>
                <p><strong>Time:</strong> $appointment_time</p>
                <p>Please visit the Barangay Hall on the scheduled date and time.</p>
                <p>Thank you for your cooperation.</p>
                <p><strong>Barangay Lower Bicutan</strong></p>
            ";
        } elseif ($status === 'resolved') {
            $mail->Subject = 'Complaint Resolved - ' . htmlspecialchars($complaint_type) . ' vs ' . htmlspecialchars($accused_person);
            $mail->Body = "
                <h2>Complaint Status Update</h2>
                <p>Dear $name,</p>
                <p>We are pleased to inform you that your complaint regarding <strong>" . htmlspecialchars($complaint_type) . "</strong> against <strong>" . htmlspecialchars($accused_person) . "</strong> (Case ID: $complaint_id) has been <strong>Resolved</strong>.</p>
                <p>If you have any further concerns, please don't hesitate to contact us.</p>
                <p>Thank you for your patience and cooperation.</p>
                <p><strong>Barangay Lower Bicutan</strong></p>
            ";
        } else {
            $mail->Subject = 'Complaint Received - ' . htmlspecialchars($complaint_type) . ' vs ' . htmlspecialchars($accused_person);
            $mail->Body = "
                <h2>Complaint Status Update</h2>
                <p>Dear $name,</p>
                <p>Your complaint regarding <strong>" . htmlspecialchars($complaint_type) . "</strong> against <strong>" . htmlspecialchars($accused_person) . "</strong> (Case ID: $complaint_id) is currently <strong>Pending</strong> review.</p>
                <p>We will notify you once there are updates on your case.</p>
                <p>Thank you for your patience.</p>
                <p><strong>Barangay Lower Bicutan</strong></p>
            ";
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email failed: ".$e->getMessage());
        return false;
    }
}

// Function to fetch all complaints with resident names
function getAllComplaints($conn) {
    $sql = "SELECT c.complaint_id, c.user_id, c.complaint_type, c.description, c.status, 
                   c.created_at, c.accussed_person, c.image, u.email, r.fname, r.lname 
            FROM complaints c 
            JOIN users u ON c.user_id = u.user_id
            JOIN residents r ON u.user_id = r.user_id
            ORDER BY c.created_at DESC";
    $result = $conn->query($sql);
    
    $complaints = [];
    while ($row = $result->fetch_assoc()) {
        $complaints[] = $row;
    }
    return $complaints;
}

// Main processing
$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $complaint_id = $_POST['complaint_id'];
    $new_status = strtolower(trim($_POST['status']));
    $appointment_date = !empty($_POST['appointment_date']) ? $_POST['appointment_date'] : null;
    $appointment_time = !empty($_POST['appointment_time']) ? $_POST['appointment_time'] : null;

    if ($new_status === "in_progress" && (empty($appointment_date) || empty($appointment_time))) {
        $message = "<div class='message error'>Error: Please select a valid date and time for the appointment.</div>";
    } else {
        $complaint_sql = "SELECT c.accussed_person, c.complaint_type, u.email, r.fname, r.lname 
        FROM complaints c 
        JOIN users u ON c.user_id = u.user_id
        JOIN residents r ON u.user_id = r.user_id
        WHERE c.complaint_id = ?";
$complaint_stmt = $conn->prepare($complaint_sql);
$complaint_stmt->bind_param("i", $complaint_id);
$complaint_stmt->execute();
$complaint_result = $complaint_stmt->get_result();
$complaint_row = $complaint_result->fetch_assoc();

// Check if we got valid data
if ($complaint_row) {
$resident_name = $complaint_row['fname'] . ' ' . $complaint_row['lname'];
$email = $complaint_row['email'];
$accused_person = $complaint_row['accussed_person'] ?? 'Unknown'; // Fallback if null
$complaint_type = $complaint_row['complaint_type'] ?? 'Complaint'; // Fallback if null

// Now update the status
$stmt = $conn->prepare("UPDATE complaints SET status = ? WHERE complaint_id = ?");
$stmt->bind_param("si", $new_status, $complaint_id);

if ($stmt->execute()) {
$message = "<div class='message success'>Complaint status updated successfully.</div>";

// Send email notification
sendComplaintStatusEmail(
   $email,
   $resident_name,
   $complaint_id,
   $accused_person,
   $complaint_type,
   $new_status,
   $appointment_date,
   $appointment_time
);
        

            if ($new_status === "in_progress") {
                if (!empty($appointment_date) && !empty($appointment_time)) {
                    $stmt = $conn->prepare("INSERT INTO appointment_complaints (complaint_id, user_id, appointment_date, appointment_time) 
                                           SELECT ?, user_id, ?, ? FROM complaints WHERE complaint_id = ?");
                    $stmt->bind_param("issi", $complaint_id, $appointment_date, $appointment_time, $complaint_id);

                    if (!$stmt->execute()) {
                        $message = "<div class='message error'>SQL Error: " . $stmt->error . "</div>";
                    } else {
                        $message .= "<div class='message success'>Appointment scheduled successfully.</div>";
                    }
                }
            }

            if ($new_status === "resolved") {
                $stmt = $conn->prepare("DELETE FROM appointment_complaints WHERE complaint_id = ?");
                $stmt->bind_param("i", $complaint_id);

                if (!$stmt->execute()) {
                    $message = "<div class='message error'>SQL Error: " . $stmt->error . "</div>";
                } else {
                    $message .= "<div class='message success'>Appointment removed successfully.</div>";
                }
            }
        } else {
            $message = "<div class='message error'>SQL Error: " . $stmt->error . "</div>";
        }
    }
}

}

$complaints = getAllComplaints($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Complaints</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #2c3e50;
        }
        .container {
            width: 80%;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
        }
        .message-container {
            text-align: center;
            margin-top: 10px;
        }
        .message {
            display: inline-block;
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
        }
        .message.success {
            background-color: #4CAF50;
            color: white;
        }
        .message.error {
            background-color: #f44336;
            color: white;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background: #043060;
            color: white;
        }
        td {
            background: #f9f9f9;
            color: black;
        }
        select, input[type="date"], input[type="time"] {
            padding: 5px;
            margin-top: 5px;
        }
        input[type="submit"] {
            padding: 5px 10px;
            background: #043060;
            color: white;
            border: none;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background: blue;
        }
        h2 {
            color: black;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="message-container">
        <?php echo $message; ?>
    </div>
    <h2>Manage Complaints</h2>
    <table>
        <tr>
            <th>Complaint ID</th>
            <th>Resident Email</th>
            <th>Accused Person</th>
            <th>Complaint Type</th>
            <th>Description</th>
            <th>Evidence</th>
            <th>Status</th>
            <th>Date</th>
            <th>Action</th>
        </tr>
        <?php foreach ($complaints as $complaint) { ?>
            <tr>
                <td><?php echo htmlspecialchars($complaint['complaint_id']); ?></td>
                <td><?php echo htmlspecialchars($complaint['email']); ?></td>
                <td><?php echo htmlspecialchars($complaint['accussed_person']); ?></td>
                <td><?php echo htmlspecialchars($complaint['complaint_type']); ?></td>
                <td><?php echo htmlspecialchars($complaint['description']); ?></td>
                <td>
                    <?php 
                        $image_filename = basename($complaint['image']); // Extract filename only
                        $image_path = "../user/uploads/" . $image_filename; // Rebuild the correct path

                        if (!empty($image_filename) && file_exists($image_path)) { 
                    ?>
                        <a href="<?php echo htmlspecialchars($image_path); ?>" target="_blank">
                            <img src="<?php echo htmlspecialchars($image_path); ?>" alt="Evidence" style="max-width: 100px; max-height: 100px;">
                        </a>
                    <?php 
                        } else { 
                            echo 'No Evidence'; 
                        } 
                    ?>
                </td>
                <td><?php echo htmlspecialchars($complaint['status']); ?></td>
                <td><?php echo htmlspecialchars($complaint['created_at']); ?></td>
                <td>
                    <form method="POST" action="">
                        <input type="hidden" name="complaint_id" value="<?php echo $complaint['complaint_id']; ?>">
                        <select name="status" class="status-dropdown" onchange="toggleDateInput(this, '<?php echo $complaint['complaint_id']; ?>')">
                            <option value="pending" <?php if ($complaint['status'] == 'pending') echo 'selected'; ?>>Pending</option>
                            <option value="in_progress" <?php if ($complaint['status'] == 'in_progress') echo 'selected'; ?>>In Progress</option>
                            <option value="resolved" <?php if ($complaint['status'] == 'resolved') echo 'selected'; ?>>Resolved</option>
                        </select>

                        <div id="appointment_fields_<?php echo $complaint['complaint_id']; ?>" style="display: none;">
                            <label for="appointment_date">Date:</label>
                            <input type="date" name="appointment_date">
                            <label for="appointment_time">Time:</label>
                            <input type="time" name="appointment_time">
                        </div>

                        <button type="submit" name="update_status">Update</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
    </table>
</div>

<script>
    function toggleDateInput(selectElement, complaintId) {
        var appointmentFields = document.getElementById("appointment_fields_" + complaintId);
        appointmentFields.style.display = (selectElement.value === "in_progress") ? "block" : "none";
    }
</script>
</body>
</html>
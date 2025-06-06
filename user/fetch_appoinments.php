<?php
include '../includes/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Assuming the user is logged in and their user_id is stored in the session
if (!isset($_SESSION['user_id'])) {
    echo json_encode([]); // Return empty data if not logged in
    exit;
}

$user_id = $_SESSION['user_id'];

$appointments = [];

// Fetch Approved General Appointments (Extract date & time separately)
$sql1 = "SELECT purpose, 
                DATE_FORMAT(appointment_date, '%Y-%m-%d') AS appointment_date, 
                TIME_FORMAT(appointment_date, '%h:%i %p') AS appointment_time
         FROM appointments 
         WHERE user_id = ? AND status = 'Approved'";

$stmt1 = $conn->prepare($sql1);
if ($stmt1 === false) {
    die(json_encode(['error' => 'Error preparing statement: ' . $conn->error]));
}

$stmt1->bind_param("i", $user_id);
$stmt1->execute();
$result1 = $stmt1->get_result();

while ($row = $result1->fetch_assoc()) {
    $appointments[] = [
        "type" => "General Appointment",
        "purpose" => $row['purpose'],
        "appointment_date" => $row['appointment_date'],
        "appointment_time" => $row['appointment_time']
    ];
}

// Fetch Complaint Appointments with Complaint Type
$sql2 = "SELECT ac.complaint_id, 
                DATE_FORMAT(ac.appointment_date, '%Y-%m-%d') AS appointment_date, 
                TIME_FORMAT(ac.appointment_time, '%h:%i %p') AS appointment_time, 
                c.complaint_type
         FROM appointment_complaints ac
         JOIN complaints c ON ac.complaint_id = c.complaint_id
         WHERE ac.user_id = ?";

$stmt2 = $conn->prepare($sql2);
if ($stmt2 === false) {
    die(json_encode(['error' => 'Error preparing statement: ' . $conn->error]));
}

$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$result2 = $stmt2->get_result();

while ($row = $result2->fetch_assoc()) {
    $appointments[] = [
        "type" => "Complaint Appointment",
        "purpose" => $row['complaint_type'], // Using complaint_type instead of purpose
        "appointment_date" => $row['appointment_date'],
        "appointment_time" => $row['appointment_time']
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($appointments);
?>

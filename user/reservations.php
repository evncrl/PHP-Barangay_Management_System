<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../includes/config.php';
include '../includes/header.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red; text-align: center; font-size: 18px;'>Please log in to access this page. <a href='/saad/user/login.php'>Log in here</a>.</p>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch resident details based on user_id
$resident_id = null;
$resident_name = null;

$sql = "SELECT resident_id, CONCAT(fname, ' ', COALESCE(mname, ''), ' ', lname) AS resident_name FROM residents WHERE user_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($resident_id, $resident_name);
    $stmt->fetch();
    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    // Retrieve and sanitize form inputs
    $purpose_of_reservation = trim($_POST['purpose_of_reservation']);
    $reservation_type = trim($_POST['reservation_type']);
    $facility = isset($_POST['facility']) ? (int)$_POST['facility'] : NULL;
    $equipment = isset($_POST['equipment']) ? (int)$_POST['equipment'] : NULL;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
    $custom_quantity = isset($_POST['custom_quantity']) ? trim($_POST['custom_quantity']) : null;
    $start_date = trim($_POST['start_date']);
    $start_time = trim($_POST['start_time']);
    $end_date = trim($_POST['end_date']);
    $end_time = trim($_POST['end_time']);
    $terms_accepted = isset($_POST['terms']) ? 1 : 0;

    // Combine date and time
    $start_datetime = $start_date . ' ' . $start_time;
    $end_datetime = $end_date . ' ' . $end_time;

    // Validate required fields
    if (empty($purpose_of_reservation) || empty($reservation_type) || empty($start_date) || 
        empty($start_time) || empty($end_date) || empty($end_time) || !$terms_accepted) {
        echo "<p style='color: red;'>All fields are required, and you must accept the terms and conditions.</p>";
        die();
    }

    // Validate reservation time (must be at least 3 days in advance)
    $current_datetime = new DateTime();
    $reservation_datetime = new DateTime($start_datetime);
    $interval = $current_datetime->diff($reservation_datetime);
    
    if ($interval->days < 3) {
        echo "<p style='color: red;'>Reservations must be made at least 3 days in advance.</p>";
        die();
    }

    // Ensure end datetime is after start datetime
    if (strtotime($end_datetime) <= strtotime($start_datetime)) {
        echo "<p style='color: red;'>End date/time must be after start date/time.</p>";
        die();
    }

    // Ensure correct quantity handling
    if (isset($_POST['quantity']) && $_POST['quantity'] === 'others' && !empty($_POST['custom_quantity'])) {
        $quantity = trim($_POST['custom_quantity']);
    } elseif (empty($quantity)) {
        $quantity = null; // Ensuring NULL-safe SQL binding
    }

    $status = "Pending";

    // Fetch available facilities
    $facilities = [];
    $query = "SELECT * FROM facilities WHERE status = 'available'";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $facilities[] = $row; // Store the entire row in an array
    }

    // Fetch available equipment
    $equipment_list = [];
    $query = "SELECT * FROM equipment WHERE quantity > 0";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $equipment_list[] = $row; // Store the entire row in an array
    }

    // Now proceed with validation and reservation logic

    // Handling reservation types
    if ($reservation_type === 'facility') {
        // Check if the facility is valid
        if (!in_array($facility, array_column($facilities, 'facility_id'))) {
            echo "<p style='color: red;'>Selected facility is not available.</p>";
            die();
        }

        // Check for facility availability (no time conflicts)
        $check_sql = "SELECT COUNT(*) FROM reservations 
                     WHERE facility_id = ? 
                     AND status = 'Approved'
                     AND NOT (
                         ? >= end_date OR 
                         ? <= reservation_date
                     )";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("iss", $facility, $end_datetime, $start_datetime);
        $check_stmt->execute();
        $check_stmt->bind_result($conflicting_reservations);
        $check_stmt->fetch();
        $check_stmt->close();

        if ($conflicting_reservations > 0) {
            echo "<p style='color: red;'>The facility is already booked for the selected time period.</p>";
            die();
        }

        // Insert reservation for only a facility
        $sql = "INSERT INTO reservations (user_id, resident_id, purpose, facility_id, equipment_id, quantity_requested, reservation_date, end_date, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisiisss", $user_id, $resident_id, $purpose_of_reservation, $facility, $quantity, $start_datetime, $end_datetime, $status);

    } elseif ($reservation_type === 'equipment') {
        // Check if the equipment is valid
        if (!in_array($equipment, array_column($equipment_list, 'equipment_id'))) {
            echo "<p style='color: red;'>Selected equipment is not available.</p>";
            die();
        }

        // Check equipment availability (no time conflicts)
        $check_sql = "SELECT COUNT(*) FROM reservations 
                     WHERE equipment_id = ? 
                     AND status = 'Approved'
                     AND NOT (
                         ? >= end_date OR 
                         ? <= reservation_date
                     )";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("iss", $equipment, $end_datetime, $start_datetime);
        $check_stmt->execute();
        $check_stmt->bind_result($conflicting_reservations);
        $check_stmt->fetch();
        $check_stmt->close();

        if ($conflicting_reservations > 0) {
            echo "<p style='color: red;'>The equipment is already booked for the selected time period.</p>";
            die();
        }

        // Check equipment quantity availability
        $equipment_query = "SELECT quantity FROM equipment WHERE equipment_id = ?";
        $equipment_stmt = $conn->prepare($equipment_query);
        $equipment_stmt->bind_param("i", $equipment);
        $equipment_stmt->execute();
        $equipment_stmt->bind_result($available_quantity);
        $equipment_stmt->fetch();
        $equipment_stmt->close();

        if ($available_quantity < $quantity) {
            echo "<p style='color: red;'>Not enough quantity available for the selected equipment.</p>";
            die();
        }

        // Insert reservation for only equipment
        $sql = "INSERT INTO reservations (user_id, resident_id, purpose, facility_id, equipment_id, quantity_requested, reservation_date, end_date, status, created_at, updated_at) 
                VALUES (?, ?, ?, NULL, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisissss", $user_id, $resident_id, $purpose_of_reservation, $equipment, $quantity, $start_datetime, $end_datetime, $status);

    } elseif ($reservation_type === 'facility_and_equipment') {
        // Check if the facility and equipment are valid
        if (!in_array($facility, array_column($facilities, 'facility_id')) || !in_array($equipment, array_column($equipment_list, 'equipment_id'))) {
            echo "<p style='color: red;'>Selected facility or equipment is not available.</p>";
            die();
        }

        // Check for facility availability (no time conflicts)
        $check_sql = "SELECT COUNT(*) FROM reservations 
                     WHERE facility_id = ? 
                     AND status = 'Approved'
                     AND NOT (
                         ? >= end_date OR 
                         ? <= reservation_date
                     )";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("iss", $facility, $end_datetime, $start_datetime);
        $check_stmt->execute();
        $check_stmt->bind_result($conflicting_facility_reservations);
        $check_stmt->fetch();
        $check_stmt->close();

        if ($conflicting_facility_reservations > 0) {
            echo "<p style='color: red;'>The facility is already booked for the selected time period.</p>";
            die();
        }

        // Check for equipment availability (no time conflicts)
        $check_sql = "SELECT COUNT(*) FROM reservations 
                     WHERE equipment_id = ? 
                     AND status = 'Approved'
                     AND NOT (
                         ? >= end_date OR 
                         ? <= reservation_date
                     )";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("iss", $equipment, $end_datetime, $start_datetime);
        $check_stmt->execute();
        $check_stmt->bind_result($conflicting_equipment_reservations);
        $check_stmt->fetch();
        $check_stmt->close();

        if ($conflicting_equipment_reservations > 0) {
            echo "<p style='color: red;'>The equipment is already booked for the selected time period.</p>";
            die();
        }

        // Check equipment quantity availability
        $equipment_query = "SELECT quantity FROM equipment WHERE equipment_id = ?";
        $equipment_stmt = $conn->prepare($equipment_query);
        $equipment_stmt->bind_param("i", $equipment);
        $equipment_stmt->execute();
        $equipment_stmt->bind_result($available_quantity);
        $equipment_stmt->fetch();
        $equipment_stmt->close();

        if ($available_quantity < $quantity) {
            echo "<p style='color: red;'>Not enough quantity available for the selected equipment.</p>";
            die();
        }

        // Insert reservation for both facility and equipment
        $sql = "INSERT INTO reservations (user_id, resident_id, purpose, facility_id, equipment_id, quantity_requested, reservation_date, end_date, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisiiisss", $user_id, $resident_id, $purpose_of_reservation, $facility, $equipment, $quantity, $start_datetime, $end_datetime, $status);

    } else {
        echo "<p style='color: red;'>Invalid reservation type selected.</p>";
        die(); // Stop script execution properly
    }

    // Execute query and handle result
    if ($stmt->execute()) {
        echo "<p style='color: green;'>Reservation created successfully.</p>";
    } else {
        echo "<p style='color: red;'>Error: " . $stmt->error . "</p>";
    }

    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve'])) {
    $reservation_id = $_POST['reservation_id']; // Get reservation_id from the form
    $status = 'Approved'; // New status to mark the reservation as approved

    // Retrieve equipment ID and quantity requested
    $sql = "SELECT equipment_id, quantity_requested FROM reservations WHERE reservation_id = ? AND status = 'Pending'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $stmt->bind_result($equipment_id, $quantity_requested);
    $stmt->fetch();
    $stmt->close();

    if ($equipment_id && $quantity_requested) {
        // Update the status of the reservation to 'Approved'
        $update_status_sql = "UPDATE reservations SET status = ? WHERE reservation_id = ?";
        $stmt = $conn->prepare($update_status_sql);
        $stmt->bind_param("si", $status, $reservation_id);
        $stmt->execute();
        $stmt->close();

        // Deduct the quantity from the equipment inventory
        $update_inventory_sql = "UPDATE equipment SET quantity = quantity - ? WHERE equipment_id = ?";
        $stmt = $conn->prepare($update_inventory_sql);
        $stmt->bind_param("ii", $quantity_requested, $equipment_id);
        $stmt->execute();
        $stmt->close();

        echo "<p style='color: green;'>Reservation approved and inventory updated successfully.</p>";
    } else {
        echo "<p style='color: red;'>Error: Reservation not found or invalid quantity.</p>";
    }
}

// Calendar functionality
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Get first day of month and total days
$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$totalDays = date('t', $firstDayOfMonth);
$firstDayOfWeek = date('w', $firstDayOfMonth); // 0=Sunday, 6=Saturday

// Get reservations for the month
$startDate = date('Y-m-01', $firstDayOfMonth);
$endDate = date('Y-m-t', $firstDayOfMonth);

// Get reservations for the month (excluding Pending status)
$calendarReservations = [];
$sql = "SELECT r.*, f.facility_name, e.equipment_name 
        FROM reservations r
        LEFT JOIN facilities f ON r.facility_id = f.facility_id
        LEFT JOIN equipment e ON r.equipment_id = e.equipment_id
        WHERE r.user_id = ? 
        AND r.status != 'Pending'
        AND ((r.reservation_date BETWEEN ? AND ?) OR (r.end_date BETWEEN ? AND ?))";
$stmt = $conn->prepare($sql);
$stmt->bind_param("issss", $user_id, $startDate, $endDate, $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $start = new DateTime($row['reservation_date']);
    $end = new DateTime($row['end_date']);
    
    // Add all days in the reservation range
    for ($date = clone $start; $date <= $end; $date->modify('+1 day')) {
        $day = $date->format('j');
        if (!isset($calendarReservations[$day])) {
            $calendarReservations[$day] = [];
        }
        
        $type = $row['facility_id'] ? 'facility' : 'equipment';
        $name = $row['facility_id'] ? $row['facility_name'] : $row['equipment_name'];
        
        // Format time for display
        $start_time = $start->format('H:i');
        $end_time = $end->format('H:i');
        
        $calendarReservations[$day][] = [
            'type' => $type,
            'name' => $name,
            'status' => $row['status'],
            'time' => $start_time . ' - ' . $end_time
        ];
    }
}
$stmt->close();

?>

<!DOCTYPE html>
<html>
<head>
    <title>Facility & Equipment Reservation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary-color: #223F61;
            --primary-light: #3a5c85;
            --primary-gradient: linear-gradient(160deg, #2F5DC5, #3BBEE6);
            --primary-gradient-hover: linear-gradient(160deg, #1a45a0, #2fa5d4);
            --text-color: #333;
            --accent-color: #3BBEE6;
            --light-bg: #eef1f7;
            --white: #ffffff;
            --shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
            --border-radius: 12px;
            --input-radius: 8px;
            --success: #4CAF50;
            --warning: #FF9800;
            --danger: #F44336;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: var(--light-bg);
            flex-direction: column;
            font-family: 'Segoe UI', 'Arial', sans-serif;
            padding: 30px 20px;
            color: var(--text-color);
            line-height: 1.6;
            padding-top: 100px;
        }

        .page-title {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 20px;
            font-size: 32px;
            font-weight: 600;
        }

        .section-container {
            width: 100%;
            max-width: 920px;
            background: var(--white);
            padding: 35px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .section-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--primary-gradient);
        }

        .container {
            width: 100%;
            max-width: 930px;
            background: var(--white);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin: 0 auto 20px;
            position: relative;
            overflow: hidden;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--primary-gradient);
        }

        h2 {
            text-align: center;
            font-size: 24px;
            color: var(--primary-color);
            margin-bottom: 25px;
            font-weight: 600;
            position: relative;
            padding-bottom: 12px;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--primary-gradient);
            border-radius: 2px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 600;
            margin: 12px 0 8px;
            color: var(--primary-color);
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: var(--input-radius);
            font-size: 15px;
            transition: all 0.3s ease;
            box-sizing: border-box;
            color: var(--text-color);
            background-color: #f9fafc;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--accent-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 190, 230, 0.2);
            background-color: var(--white);
        }

        input[readonly] {
            background-color: #f0f2f5;
            cursor: not-allowed;
            border-color: #ddd;
        }

        textarea {
            resize: none;
            width: 100%;
            height: 200px;
            font-size: 14px;
            line-height: 1.5;
            font-family: 'Segoe UI', 'Arial', sans-serif;
            background-color: #f9fafc;
        }

        button, input[type="submit"] {
            width: 100%;
            padding: 12px 15px;
            background: var(--primary-gradient);
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            border-radius: var(--input-radius);
            transition: 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 10px rgba(47, 93, 197, 0.3);
        }

        button:hover, input[type="submit"]:hover {
            background: var(--primary-gradient-hover);
            box-shadow: 0 6px 15px rgba(47, 93, 197, 0.4);
            transform: translateY(-2px);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-top: 10px;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
        }

        tr:nth-child(even) {
            background-color: #f9fafc;
        }

        tr:hover {
            background-color: #eef1f7;
        }

        .status-pending {
            color: var(--warning);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            background-color: rgba(255, 152, 0, 0.1);
            border-radius: 20px;
            font-size: 13px;
        }

        .status-pending::before {
            content: "•";
            margin-right: 5px;
            font-size: 18px;
        }

        .status-approved {
            color: var(--success);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            background-color: rgba(76, 175, 80, 0.1);
            border-radius: 20px;
            font-size: 13px;
        }

        .status-approved::before {
            content: "•";
            margin-right: 5px;
            font-size: 18px;
        }

        .status-rejected {
            color: var(--danger);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            background-color: rgba(244, 67, 54, 0.1);
            border-radius: 20px;
            font-size: 13px;
        }

        .status-rejected::before {
            content: "•";
            margin-right: 5px;
            font-size: 18px;
        }

        .checkbox-container {
            margin: 20px 0;
            padding: 15px;
            background-color: #f9fafc;
            border-radius: var(--input-radius);
            border: 1px solid #ddd;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            font-size: 14px;
            gap: 8px;
        }

        .checkbox-label input {
            width: auto;
            margin: 0;
            transform: scale(1.2);
        }

        .checkbox-label a {
            color: var(--accent-color);
            font-weight: 600;
            text-decoration: none;
        }

        .checkbox-label a:hover {
            text-decoration: underline;
        }

        .date-inputs {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 15px;
        }

        .date-inputs div {
            flex: 1;
        }

        .date-inputs label {
            display: block;
            margin-bottom: 8px;
        }

        .date-inputs input {
            width: 100%;
        }

        /* Time input container */
        .time-inputs {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .time-inputs div {
            flex: 1;
        }
        
        .time-inputs label {
            display: block;
            margin-bottom: 8px;
        }
        
        .time-inputs input[type="time"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: var(--input-radius);
            font-size: 15px;
            background-color: #f9fafc;
        }

        .reservation-note {
            font-style: italic;
            font-size: 13px;
            color: #777;
            margin-top: -5px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .reservation-note::before {
            content: "\f05a";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            margin-right: 5px;
            color: var(--accent-color);
        }

        .form-section {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23223F61' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 15px;
            padding-right: 40px;
        }

        #custom-quantity {
            padding: 15px;
            background-color: #f9fafc;
            border-radius: var(--input-radius);
            border: 1px solid #ddd;
            margin-top: 10px;
            margin-bottom: 20px;
        }

        .terms-container {
            margin: 20px 0;
            background-color: #f9fafc;
            border-radius: var(--input-radius);
            border: 1px solid #ddd;
        }

        .terms-header {
            padding: 12px 15px;
            font-weight: 600;
            color: var(--primary-color);
            border-bottom: 1px solid #ddd;
            background-color: #eef1f7;
            display: flex;
            align-items: center;
        }

        .terms-header::before {
            content: "\f02d";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            margin-right: 10px;
            color: var(--primary-color);
        }

        .terms-area {
            padding: 15px;
        }

        textarea {
            border: 1px solid #ddd;
        }

        .success-message {
            padding: 15px;
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--success);
            border-radius: var(--input-radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            font-weight: 600;
        }

        .success-message::before {
            content: "\f058";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            margin-right: 10px;
        }

        .error-message {
            padding: 15px;
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--danger);
            border-radius: var(--input-radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            font-weight: 600;
        }

        .error-message::before {
            content: "\f057";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            margin-right: 10px;
        }

        /* Calendar Styles */
        .calendar-container {
            margin-bottom: 30px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 20px;
            width: 100%;
            overflow-x: auto;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .calendar-title {
            font-size: 20px;
            color: var(--primary-color);
            font-weight: 600;
            margin: 0;
        }

        .calendar-nav {
            display: flex;
            gap: 10px;
        }

        .calendar-nav a {
            padding: 8px 15px;
            background: var(--primary-gradient);
            color: white;
            text-decoration: none;
            border-radius: var(--input-radius);
            font-size: 14px;
            transition: 0.3s;
        }

        .calendar-nav a:hover {
            background: var(--primary-gradient-hover);
            transform: translateY(-2px);
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(100px, 1fr));
            gap: 5px;
            width: 100%;
            min-width: 700px;
        }

        .calendar-day-header {
            text-align: center;
            font-weight: 600;
            padding: 10px;
            background: var(--primary-color);
            color: white;
            font-size: 14px;
        }

        .calendar-day {
            min-height: 100px;
            border: 1px solid #ddd;
            padding: 8px;
            position: relative;
            background: white;
        }

        .calendar-day.empty {
            background-color: #f9f9f9;
            border: 1px solid #eee;
        }

        .calendar-date {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .calendar-event {
            font-size: 12px;
            background: var(--accent-color);
            color: white;
            padding: 3px 6px;
            border-radius: 3px;
            margin-bottom: 3px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .calendar-event.facility {
            background: #4CAF50;
        }

        .calendar-event.equipment {
            background: #2196F3;
        }

        /* Calendar event time */
        .calendar-event-time {
            font-size: 10px;
            margin-top: 2px;
            color: white;
            font-style: italic;
        }

        .current-day {
            background-color: rgba(59, 190, 230, 0.1);
            border: 2px solid var(--accent-color);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .section-container, .container {
                padding: 20px;
            }
            
            .date-inputs, .time-inputs {
                flex-direction: column;
                gap: 0;
            }
            
            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
            
            th, td {
                padding: 10px;
            }

            .calendar-container {
                padding: 15px;
            }
            
            .calendar-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .calendar-nav {
                width: 100%;
                justify-content: space-between;
            }
            
            .calendar-nav a {
                flex: 1;
                text-align: center;
            }
            
            .calendar-day {
                min-height: 80px;
                padding: 5px;
            }
            
            .calendar-event {
                font-size: 11px;
                padding: 2px 4px;
            }
        }
    </style>
</head>
<body>

<h1 class="page-title">Facilities & Equipment Management</h1>

<div class="section-container">
    <h2>Create New Reservation</h2>
    
    <?php if(isset($success_message)): ?>
    <div class="success-message">
        <?php echo $success_message; ?>
    </div>
    <?php endif; ?>
    
    <?php if(isset($error_message)): ?>
    <div class="error-message">
        <?php echo $error_message; ?>
    </div>
    <?php endif; ?>
    
    <form method="POST" id="reservation-form">
        <div class="form-section">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Resident Name:</label>
                <input type="text" value="<?php echo $resident_name; ?>" readonly>
            </div>

            <div class="form-group">
                <label><i class="fas fa-clipboard"></i> Purpose of Reservation:</label>
                <input type="text" name="purpose_of_reservation" placeholder="Enter the purpose of reservation" required>
            </div>
        </div>
        
        <div class="form-section">
            <div class="form-group">
                <label for="reservation_type"><i class="fas fa-list-check"></i> Reservation Type</label>
                <select name="reservation_type" id="reservation_type">
                    <option value="facility">Facility</option>
                    <option value="equipment">Equipment</option>
                    <option value="facility_and_equipment">Facility and Equipment</option>
                </select>
            </div>

            <!-- Facility Selection -->
            <div id="facility-dropdown" style="display: none;" class="form-group">
                <label><i class="fas fa-building"></i> Select Facility:</label>
                <select name="facility" id="facility">
                    <option value="">Select a Facility</option>
                    <?php
                    $query = "SELECT * FROM facilities WHERE status = 'available'";
                    $result = mysqli_query($conn, $query);
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<option value='" . $row['facility_id'] . "'>" . $row['facility_name'] . "</option>";
                    }
                    ?>
                </select>
            </div>

            <!--Equipment Selection -->
            <div id="equipment-dropdown" style="display: none;" class="form-group">
                <label><i class="fas fa-tools"></i> Select Equipment:</label>
                <select name="equipment" id="equipment">
                    <option value="">Select Equipment</option>
                    <?php
                    $query = "SELECT * FROM equipment WHERE quantity > 0";
                    $result = mysqli_query($conn, $query);
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<option value='" . $row['equipment_id'] . "'>" . $row['equipment_name'] . " (" . $row['quantity'] . ")</option>";
                    }
                    ?>
                </select>

                <!-- Quantity Selection -->
                <label><i class="fas fa-sort-numeric-up"></i> Select Quantity:</label>
                <select name="quantity" id="quantity">
                    <option value="">--SELECT QUANTITY--</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                    <option value="others">Other (Specify)</option>
                </select>

                <!-- Custom Quantity Input -->
                <div id="custom-quantity" style="display: none;">
                    <label><i class="fas fa-keyboard"></i> Enter Custom Quantity:</label>
                    <input type="number" name="custom_quantity" min="1" placeholder="Enter quantity">
                </div>
            </div>
        </div>
    

        <div class="form-section">
            <div class="date-inputs">
                <div>
                    <label><i class="fas fa-calendar-plus"></i> Start Date:</label>
                    <input type="date" name="start_date" id="start_date" required>
                </div>

                <div>
                    <label><i class="fas fa-calendar-minus"></i> End Date:</label>
                    <input type="date" name="end_date" id="end_date" required>
                </div>
            </div>
            
            <div class="time-inputs">
                <div>
                    <label><i class="fas fa-clock"></i> Start Time:</label>
                    <input type="time" name="start_time" id="start_time" required>
                </div>
                
                <div>
                    <label><i class="fas fa-clock"></i> End Time:</label>
                    <input type="time" name="end_time" id="end_time" required>
                </div>
            </div>
            
            <p class="reservation-note">Reservations must be made at least 3 days in advance and cannot be extended.</p>
        </div>

        <div class="form-section">
            <div class="terms-container">
                <div class="terms-header">Terms and Conditions for Facilities and Equipment Borrowing</div>
                <div class="terms-area">
                    <textarea rows="10" readonly>
Terms and Conditions for Facilities and Equipment Borrowing
1. General Guidelines
    1.1. The borrower must be a registered user of the online reservation system.
    1.2. Reservations must be made at least 3 days in advance.
    1.3. Borrowed equipment and facilities must be used only for their intended purposes.
    1.4. The borrower is responsible for ensuring proper handling and safekeeping.
    1.5. The borrower must adhere to the designated time slots.
    1.6. Any technical issues or damages must be reported immediately.
    1.7. Unauthorized transfer of borrowed equipment is prohibited.

2. Reservation and Approval
    2.1. Reservations are subject to availability and approval.
    2.2. A confirmation will be sent upon approval.
    2.3. Failure to claim the reservation will result in cancellation.
    2.4. Repeated no-shows may result in suspension.

3. Responsibilities of the Borrower
    3.1. Return items in the same condition as received.
    3.2. Use equipment and facilities safely and properly.
    3.3. Report any damage or loss immediately.
    3.4. Borrower is financially accountable for damages.
    3.5. Equipment must be picked up at the specified time.

4. Penalties for Violations
    4.1. Late Return: ₱1,000 per day penalty.
    4.2. Damage or Loss: Repair/replacement costs.
    4.3. No-Show: Three no-shows = annual suspension.
    4.4. Misuse: Immediate suspension.
    4.5. Failure to Report: Full liability for costs.

5. Enforcement and Amendments
    5.1. Administration reserves all rights.
    5.2. Amendments will be communicated officially.

By proceeding, you agree to these terms. Non-compliance may result in penalties.
                    </textarea>
                </div>
            </div>

            <div class="checkbox-container">
                <div class="checkbox-label">
                    <input type="checkbox" name="terms" required>
                    <span>I accept the <a href="#">terms and conditions</a></span>
                </div>
            </div>
        </div>

        <input type="submit" name="create" value="Create Reservation">
    </form>
</div>

<div class="container">
    <h2>Your Reservations</h2>

    <table>
        <thead>
            <tr>
                <th>#ID</th>
                <th>Purpose</th>
                <th>Facility</th>
                <th>Equipment</th>
                <th>Quantity</th>
                <th>Start Date/Time</th>
                <th>End Date/Time</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php
        // Fetch reservations data for the logged-in user with facility and equipment names
        $sql = "SELECT r.reservation_id, r.purpose, 
                   f.facility_name, e.equipment_name, 
                   r.quantity_requested, r.reservation_date, r.end_date, r.status 
            FROM reservations r
            LEFT JOIN facilities f ON r.facility_id = f.facility_id
            LEFT JOIN equipment e ON r.equipment_id = e.equipment_id
            WHERE r.user_id = ? 
            ORDER BY r.created_at DESC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $status_class = '';
            switch ($row['status']) {
                case 'Pending':
                    $status_class = 'status-pending';
                    break;
                case 'Approved':
                    $status_class = 'status-approved';
                    break;
                case 'Rejected':
                    $status_class = 'status-rejected';
                    break;
            }
            echo "<tr>";
            echo "<td>" . $row['reservation_id'] . "</td>";
            echo "<td>" . $row['purpose'] . "</td>";
            echo "<td>" . (!empty($row['facility_name']) ? $row['facility_name'] : '-') . "</td>";
            echo "<td>" . (!empty($row['equipment_name']) ? $row['equipment_name'] : '-') . "</td>";
            echo "<td>" . (!empty($row['quantity_requested']) ? $row['quantity_requested'] : '-') . "</td>";
            echo "<td>" . date('Y-m-d H:i', strtotime($row['reservation_date'])) . "</td>";
            echo "<td>" . date('Y-m-d H:i', strtotime($row['end_date'])) . "</td>";
            echo "<td class='$status_class'>" . $row['status'] . "</td>";
            echo "</tr>";
        }
        $stmt->close();
        ?>
        </tbody>
    </table>
</div>


<!-- Calendar Section -->
<div class="container">
    <h2>Your Reservation Calendar</h2>
    
    <div class="calendar-container">
        <div class="calendar-header">
            <h3 class="calendar-title"><?php echo date('F Y', $firstDayOfMonth); ?></h3>
            <div class="calendar-nav">
                <a href="?month=<?php echo $month-1 < 1 ? 12 : $month-1; ?>&year=<?php echo $month-1 < 1 ? $year-1 : $year; ?>" class="btn-prev">Previous</a>
                <a href="?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" class="btn-today">Today</a>
                <a href="?month=<?php echo $month+1 > 12 ? 1 : $month+1; ?>&year=<?php echo $month+1 > 12 ? $year+1 : $year; ?>" class="btn-next">Next</a>
            </div>
        </div>
        
        <div class="calendar-grid">
            <!-- Day headers -->
            <div class="calendar-day-header">Sun</div>
            <div class="calendar-day-header">Mon</div>
            <div class="calendar-day-header">Tue</div>
            <div class="calendar-day-header">Wed</div>
            <div class="calendar-day-header">Thu</div>
            <div class="calendar-day-header">Fri</div>
            <div class="calendar-day-header">Sat</div>
            
            <!-- Empty days at start of month -->
            <?php for ($i = 0; $i < $firstDayOfWeek; $i++): ?>
                <div class="calendar-day empty"></div>
            <?php endfor; ?>
            
            <!-- Days of the month -->
            <?php for ($day = 1; $day <= $totalDays; $day++): ?>
                <?php 
                $isCurrentDay = ($day == date('j') && $month == date('n') && $year == date('Y'));
                $hasReservations = isset($calendarReservations[$day]);
                ?>
                <div class="calendar-day <?php echo $isCurrentDay ? 'current-day' : ''; ?>">
                    <div class="calendar-date"><?php echo $day; ?></div>
                    
                    <?php if ($hasReservations): ?>
                        <?php foreach ($calendarReservations[$day] as $event): ?>
                            <?php if (!empty($event['name'])): ?>
                                <div class="calendar-event <?php echo $event['type']; ?>">
                                    <?php echo htmlspecialchars($event['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if (!empty($event['status'])): ?>
                                        <span style="font-size:10px;">(<?php echo htmlspecialchars($event['status'] ?? '', ENT_QUOTES, 'UTF-8'); ?>)</span>
                                    <?php endif; ?>
                                    <?php if (!empty($event['time'])): ?>
                                        <?php 
                                        // Convert time to AM/PM format
                                        $times = explode(' - ', $event['time']);
                                        $start_time = date('g:i A', strtotime($times[0]));
                                        $end_time = date('g:i A', strtotime($times[1]));
                                        ?>
                                        <div class="calendar-event-time"><?php echo htmlspecialchars($start_time . ' - ' . $end_time, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
            
            <!-- Empty days at end of month -->
            <?php 
            $lastDayOfWeek = date('w', mktime(0, 0, 0, $month, $totalDays, $year));
            $remainingDays = 6 - $lastDayOfWeek;
            for ($i = 0; $i < $remainingDays; $i++): ?>
                <div class="calendar-day empty"></div>
            <?php endfor; ?>
        </div>
    </div>
</div>


<script>
    document.addEventListener("DOMContentLoaded", function () {
        const reservationType = document.getElementById("reservation_type");
        const facilityDropdown = document.getElementById("facility-dropdown");
        const equipmentDropdown = document.getElementById("equipment-dropdown");
        const quantityDropdown = document.getElementById("quantity");
        const customQuantityField = document.getElementById("custom-quantity");
        const startDateInput = document.getElementById("start_date");
        const endDateInput = document.getElementById("end_date");
        const startTimeInput = document.getElementById("start_time");
        const endTimeInput = document.getElementById("end_time");

        // Set minimum date (today + 3 days)
        const today = new Date();
        const minDate = new Date();
        minDate.setDate(today.getDate() + 3);
        
        const minDateStr = minDate.toISOString().split('T')[0];
        startDateInput.min = minDateStr;
        endDateInput.min = minDateStr;

        function toggleDropdowns() {
            const type = reservationType.value;
            facilityDropdown.style.display = (type === "facility" || type === "facility_and_equipment") ? "block" : "none";
            equipmentDropdown.style.display = (type === "equipment" || type === "facility_and_equipment") ? "block" : "none";
        }

        function toggleCustomQuantity() {
            customQuantityField.style.display = quantityDropdown.value === "others" ? "block" : "none";
        }

        reservationType.addEventListener("change", toggleDropdowns);
        quantityDropdown.addEventListener("change", toggleCustomQuantity);
        
        // When start date changes, update end date min value
        startDateInput.addEventListener("change", function() {
            endDateInput.min = this.value;
            if (endDateInput.value < this.value) {
                endDateInput.value = this.value;
            }
        });
        
        // Set default times
        startTimeInput.value = "08:00";
        endTimeInput.value = "17:00";

        toggleDropdowns(); // Ensure correct state on page load
    });

    // Calendar interaction
    document.querySelectorAll('.calendar-day:not(.empty)').forEach(day => {
        day.addEventListener('click', function() {
            const date = this.querySelector('.calendar-date').textContent;
            const month = <?php echo $month; ?>;
            const year = <?php echo $year; ?>;
            
            // Filter reservations table to show only this day's reservations
            const dateStr = year + '-' + String(month).padStart(2, '0') + '-' + String(date).padStart(2, '0');
            
            document.querySelectorAll('table tbody tr').forEach(row => {
                const rowDate = row.cells[5].textContent.split(' ')[0]; // Start date column (date part only)
                if (rowDate === dateStr) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });

    // Reset table filter when clicking month title
    document.querySelector('.calendar-title').addEventListener('click', function() {
        document.querySelectorAll('table tbody tr').forEach(row => {
            row.style.display = '';
        });
    });
</script>

</body>
</html>
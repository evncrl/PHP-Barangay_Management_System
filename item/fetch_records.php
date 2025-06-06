<?php
require '../includes/config.php'; // Adjust the path as needed

header('Content-Type: application/json');

// Fetch pending counts
$sqlAppointments = "SELECT COUNT(*) as count FROM appointments WHERE status = 'Pending'";
$sqlDocuments = "SELECT COUNT(*) as count FROM documents WHERE status = 'pending'";
$sqlComplaints = "SELECT COUNT(*) as count FROM complaints WHERE status = 'pending'";
$sqlReservations = "SELECT COUNT(*) as count FROM reservations WHERE status = 'pending'";
$sqlUsers = "SELECT COUNT(*) as count FROM residents WHERE verification_status = 'Pending'"; // Pending Users

// Execute queries
$resultAppointments = $conn->query($sqlAppointments);
$resultDocuments = $conn->query($sqlDocuments);
$resultComplaints = $conn->query($sqlComplaints);
$resultReservations = $conn->query($sqlReservations);
$resultUsers = $conn->query($sqlUsers); // Fetch Pending Users

// Fetch the counts
$pendingAppointments = ($resultAppointments) ? $resultAppointments->fetch_assoc()['count'] ?? 0 : 0;
$pendingDocuments = ($resultDocuments) ? $resultDocuments->fetch_assoc()['count'] ?? 0 : 0;
$pendingComplaints = ($resultComplaints) ? $resultComplaints->fetch_assoc()['count'] ?? 0 : 0;
$pendingReservations = ($resultReservations) ? $resultReservations->fetch_assoc()['count'] ?? 0 : 0;
$pendingUsers = ($resultUsers) ? $resultUsers->fetch_assoc()['count'] ?? 0 : 0;

// Return data as JSON
echo json_encode([
    'pending_appointments' => $pendingAppointments,
    'pending_documents' => $pendingDocuments,
    'pending_complaints' => $pendingComplaints,
    'pending_reservations' => $pendingReservations,
    'pending_users' => $pendingUsers
]);

$conn->close();
?>

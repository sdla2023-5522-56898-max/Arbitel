<?php
// Start session to get user information
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Function to send JSON response
function sendResponse($success, $message = '') {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    sendResponse(false, 'User not logged in');
}

// Check if booking_id is provided
if (!isset($_POST['booking_id']) || empty($_POST['booking_id'])) {
    sendResponse(false, 'Booking ID is required');
}

// Get the booking ID from POST data
$booking_id = intval($_POST['booking_id']);
$user_id = $_SESSION['user_id'];

// Connect to the database
$conn = new mysqli("localhost", "root", "", "arbitel_db");

// Check connection
if ($conn->connect_error) {
    sendResponse(false, 'Database connection failed: ' . $conn->connect_error);
}

// First, verify that the booking belongs to the current user (security check)
$verifySQL = "SELECT booking_id FROM event_bookings WHERE booking_id = ? AND user_id = ?";
$verifyStmt = $conn->prepare($verifySQL);

if (!$verifyStmt) {
    sendResponse(false, 'Prepare statement failed: ' . $conn->error);
}

$verifyStmt->bind_param("ii", $booking_id, $user_id);
$verifyStmt->execute();
$verifyResult = $verifyStmt->get_result();

if ($verifyResult->num_rows === 0) {
    // Either booking doesn't exist or doesn't belong to this user
    sendResponse(false, 'You do not have permission to cancel this booking or it does not exist');
}

$verifyStmt->close();

// If we're here, the booking exists and belongs to the current user, so delete it
$deleteSQL = "DELETE FROM event_bookings WHERE booking_id = ? AND user_id = ?";
$deleteStmt = $conn->prepare($deleteSQL);

if (!$deleteStmt) {
    sendResponse(false, 'Prepare statement failed: ' . $conn->error);
}

$deleteStmt->bind_param("ii", $booking_id, $user_id);
$result = $deleteStmt->execute();

if ($result) {
    // Check if any rows were affected
    if ($deleteStmt->affected_rows > 0) {
        sendResponse(true, 'Booking successfully cancelled');
    } else {
        sendResponse(false, 'No booking was deleted. It may have been already cancelled.');
    }
} else {
    sendResponse(false, 'Error deleting booking: ' . $deleteStmt->error);
}

$deleteStmt->close();
$conn->close();
?>
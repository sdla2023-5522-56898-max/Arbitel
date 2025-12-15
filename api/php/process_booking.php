<?php
// File: process_booking.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include your database connection
require_once 'db_connection.php';

// Check if this is a form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get booking data from form
    $customerName = mysqli_real_escape_string($conn, $_POST['customer_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $roomId = (int)$_POST['room_id'];
    $checkInDate = mysqli_real_escape_string($conn, $_POST['check_in_date']);
    $checkOutDate = mysqli_real_escape_string($conn, $_POST['check_out_date']);
    $totalPrice = (float)$_POST['total_price'];
    $specialInstructions = mysqli_real_escape_string($conn, $_POST['special_instructions'] ?? '');
    $reservationDate = date('Y-m-d'); // Current date

    // Insert booking into database
    $query = "INSERT INTO reservations (customer_name, email, room_id, check_in_date, check_out_date, 
              total_price, special_instructions, reservation_date, status) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssisssss",
        $customerName,
        $email,
        $roomId,
        $checkInDate,
        $checkOutDate,
        $totalPrice,
        $specialInstructions,
        $reservationDate
    );

    $result = mysqli_stmt_execute($stmt);

    if ($result) {
        // Get the reservation ID
        $reservationId = mysqli_insert_id($conn);

        // Get room details
        $roomQuery = "SELECT r.name as room_name, c.name as room_category 
                     FROM rooms r 
                     JOIN room_categoris c ON r.category_id = c.id 
                     WHERE r.id = ?";

        $roomStmt = mysqli_prepare($conn, $roomQuery);
        mysqli_stmt_bind_param($roomStmt, "i", $roomId);
        mysqli_stmt_execute($roomStmt);
        $roomResult = mysqli_stmt_get_result($roomStmt);
        $roomData = mysqli_fetch_assoc($roomResult);

        // Store all email data in session for JavaScript to access
        $_SESSION['booking_email_data'] = [
            'reser_id' => $reservationId,
            'customer_name' => $customerName,
            'email' => $email,
            'room_name' => $roomData['room_name'],
            'room_category' => $roomData['room_category'],
            'check_in_date' => $checkInDate,
            'check_out_date' => $checkOutDate,
            'reservation_date' => $reservationDate,
            'total_price' => $totalPrice,
            'special_instructions' => $specialInstructions
        ];

        // Redirect to confirmation page
        header("Location: booking_confirmation.php?id=" . $reservationId);
        exit;
    } else {
        // Handle error
        echo "Booking failed: " . mysqli_error($conn);
    }
}
?>
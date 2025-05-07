<?php
session_start();
require_once 'connect.php';

// Check if user is logged in
if (!isset($_SESSION['IDNO'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

// Get POST data
$laboratory = isset($_POST['laboratory']) ? $_POST['laboratory'] : '';
$day = isset($_POST['day']) ? $_POST['day'] : '';
$time_slot = isset($_POST['time_slot']) ? $_POST['time_slot'] : '';

if (empty($laboratory) || empty($day) || empty($time_slot)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing required parameters']);
    exit();
}

// Check lab schedule
$schedule_check = "SELECT * FROM labschedules 
                  WHERE ROOM_NUMBER = ? 
                  AND DAY_GROUP = ? 
                  AND TIME_SLOT = ? 
                  AND STATUS = 'Available'";
$schedule_stmt = $conn->prepare($schedule_check);
$schedule_stmt->bind_param("sss", $laboratory, $day, $time_slot);
$schedule_stmt->execute();
$schedule_result = $schedule_stmt->get_result();

// Get reserved PCs for the selected time slot
$reserved_pcs = [];
if ($schedule_result->num_rows > 0) {
    $date = date('Y-m-d'); // You might want to pass this as a parameter
    $reserved_check = "SELECT PC_NUMBER FROM reservations 
                      WHERE LABORATORY = ? 
                      AND DATE = ? 
                      AND TIME_SLOT = ? 
                      AND STATUS != 'cancelled'";
    $reserved_stmt = $conn->prepare($reserved_check);
    $reserved_stmt->bind_param("sss", $laboratory, $date, $time_slot);
    $reserved_stmt->execute();
    $reserved_result = $reserved_stmt->get_result();
    
    while ($row = $reserved_result->fetch_assoc()) {
        $reserved_pcs[] = $row['PC_NUMBER'];
    }
}

// Prepare response
$response = [
    'available' => $schedule_result->num_rows > 0,
    'reserved_pcs' => $reserved_pcs
];

header('Content-Type: application/json');
echo json_encode($response); 
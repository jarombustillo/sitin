<?php
session_start();
require_once 'connect.php';

// Check if user is logged in
if (!isset($_SESSION['IDNO'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

// Get parameters
$lab = isset($_GET['lab']) ? $_GET['lab'] : '';
$day = isset($_GET['day']) ? $_GET['day'] : '';

if (empty($lab) || empty($day)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing parameters']);
    exit();
}

// Check lab schedule
$schedule_sql = "SELECT * FROM labschedules 
                 WHERE (ROOM_NUMBER = ? OR ROOM_NUMBER = CONCAT('Lab ', ?))
                 AND DAY_GROUP = ?";
$schedule_stmt = $conn->prepare($schedule_sql);
$schedule_stmt->bind_param("sss", $lab, $lab, $day);

file_put_contents('debug.log', "LAB: $lab, DAY: $day\n", FILE_APPEND);

$schedule_stmt->execute();
$schedule_result = $schedule_stmt->get_result();

$availableSlots = [];
if ($schedule_result->num_rows > 0) {
    while ($row = $schedule_result->fetch_assoc()) {
        $availableSlots[] = [
            'TIME_SLOT' => $row['TIME_SLOT'],
            'STATUS' => $row['STATUS'],
            'NOTES' => $row['NOTES']
        ];
    }
} else {
    // No schedules set by admin for this lab and day
    $availableSlots = []; // Return empty array
}

// Return the available slots
header('Content-Type: application/json');
echo json_encode($availableSlots);
?> 
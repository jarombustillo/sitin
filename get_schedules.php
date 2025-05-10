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

// Define time slots
$timeSlots = [
    '08:00-09:00',
    '09:00-10:00',
    '10:00-11:00',
    '11:00-12:00',
    '13:00-14:00',
    '14:00-15:00',
    '15:00-16:00',
    '16:00-17:00'
];

// Check lab schedule
$schedule_sql = "SELECT * FROM labschedules 
                WHERE ROOM_NUMBER = ? 
                AND DAY_GROUP = ? 
                AND STATUS = 'Available'";
$schedule_stmt = $conn->prepare($schedule_sql);
$schedule_stmt->bind_param("ss", $lab, $day);
$schedule_stmt->execute();
$schedule_result = $schedule_stmt->get_result();

$availableSlots = [];
if ($schedule_result->num_rows > 0) {
    while ($row = $schedule_result->fetch_assoc()) {
        $availableSlots[] = [
            'TIME_SLOT' => $row['TIME_SLOT'],
            'STATUS' => 'Available',
            'NOTES' => $row['NOTES']
        ];
    }
} else {
    // If no specific schedule is set, all time slots are available
    foreach ($timeSlots as $slot) {
        $availableSlots[] = [
            'TIME_SLOT' => $slot,
            'STATUS' => 'Available',
            'NOTES' => null
        ];
    }
}

// Return the available slots
header('Content-Type: application/json');
echo json_encode($availableSlots);
?> 
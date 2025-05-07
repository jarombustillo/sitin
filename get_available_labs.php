<?php
session_start();
require_once 'connect.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if required parameters are present
if (!isset($_POST['date']) || !isset($_POST['time_slot'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$date = $_POST['date'];
$time_slot = $_POST['time_slot'];

// Get the day of the week for the selected date
$day_of_week = date('l', strtotime($date));

// Query to get available labs for the selected day and time slot
$sql = "SELECT DISTINCT ROOM_NUMBER 
        FROM labschedules 
        WHERE DAY_GROUP = ? 
        AND TIME_SLOT = ? 
        AND STATUS = 'Available'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $day_of_week, $time_slot);
$stmt->execute();
$result = $stmt->get_result();

$available_labs = [];
while ($row = $result->fetch_assoc()) {
    $available_labs[] = $row['ROOM_NUMBER'];
}

// Return the available labs as JSON
header('Content-Type: application/json');
echo json_encode(['labs' => $available_labs]);
?> 
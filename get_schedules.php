<?php
require_once 'connect.php';

header('Content-Type: application/json');

if (!isset($_GET['lab']) || !isset($_GET['day'])) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit();
}

$lab = $conn->real_escape_string($_GET['lab']);
$day = $conn->real_escape_string($_GET['day']);

$sql = "SELECT * FROM labschedules 
        WHERE ROOM_NUMBER = ? AND DAY_GROUP = ? 
        ORDER BY TIME_SLOT";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $lab, $day);
$stmt->execute();
$result = $stmt->get_result();

$schedules = [];
while ($row = $result->fetch_assoc()) {
    $schedules[] = $row;
}

echo json_encode($schedules);
?> 
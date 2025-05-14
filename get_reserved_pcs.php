<?php
require_once 'connect.php';
header('Content-Type: application/json');

$laboratory = isset($_GET['lab']) ? $_GET['lab'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';
$time_slot = isset($_GET['time_slot']) ? $_GET['time_slot'] : '';

if (empty($laboratory) || empty($date) || empty($time_slot)) {
    echo json_encode(['reserved_pcs' => []]);
    exit();
}

$sql = "SELECT PC_NUMBER FROM reservations WHERE LABORATORY = ? AND DATE = ? AND TIME_SLOT = ? AND STATUS = 'approved'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $laboratory, $date, $time_slot);
$stmt->execute();
$result = $stmt->get_result();
$reserved_pcs = [];
while ($row = $result->fetch_assoc()) {
    $reserved_pcs[] = (int)$row['PC_NUMBER'];
}
echo json_encode(['reserved_pcs' => $reserved_pcs]); 
<?php
session_start();
require_once 'connect.php';
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['IDNO'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Check if lab parameter is provided
if (!isset($_GET['lab'])) {
    echo json_encode(['error' => 'Laboratory not specified']);
    exit();
}

$lab = $_GET['lab'];

// Get PC status for the selected laboratory
$sql = "SELECT * FROM pc_status WHERE ROOM_NUMBER = ? ORDER BY PC_NUMBER";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $lab);
$stmt->execute();
$result = $stmt->get_result();

$pcs = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $pcs[] = $row;
    }
}

echo json_encode($pcs); 
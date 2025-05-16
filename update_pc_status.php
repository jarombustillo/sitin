<?php
session_start();
require_once 'connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['pc_ids'], $data['room_number'], $data['status'])) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit();
}

$pc_ids = $data['pc_ids'];
$room_number = $data['room_number'];
$status = $data['status'];
$date = isset($data['date']) ? $data['date'] : null;
$time_slot = isset($data['time_slot']) ? $data['time_slot'] : null;

if (!is_array($pc_ids) || count($pc_ids) === 0) {
    echo json_encode(['success' => false, 'error' => 'No PCs selected']);
    exit();
}

$success = true;
foreach ($pc_ids as $pc_number) {
    $pc_number = (int)$pc_number;
    
    // Only set to available if no active reservations for this PC, room, date, and time_slot
    if ($status === 'available' && $date && $time_slot) {
        $check_sql = "SELECT COUNT(*) as cnt FROM reservations WHERE LABORATORY = ? AND PC_NUMBER = ? AND DATE = ? AND TIME_SLOT = ? AND STATUS IN ('pending', 'confirmed', 'approved')";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('siss', $room_number, $pc_number, $date, $time_slot);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $cnt = $result->fetch_assoc()['cnt'];
        if ($cnt > 0) {
            // There are still active reservations, do not set to available
            continue;
        }
    }
    // First check if PC exists
    $check_sql = "SELECT COUNT(*) as count FROM pc_status WHERE PC_NUMBER = ? AND ROOM_NUMBER = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('is', $pc_number, $room_number);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        // PC doesn't exist, insert it
        $insert_sql = "INSERT INTO pc_status (ROOM_NUMBER, PC_NUMBER, STATUS, LAST_UPDATED) VALUES (?, ?, ?, CURRENT_TIMESTAMP)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param('sis', $room_number, $pc_number, $status);
        if (!$insert_stmt->execute()) {
            $success = false;
            break;
        }
    } else {
        // PC exists, update it
        $update_sql = "UPDATE pc_status SET STATUS = ?, LAST_UPDATED = CURRENT_TIMESTAMP WHERE PC_NUMBER = ? AND ROOM_NUMBER = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('sis', $status, $pc_number, $room_number);
        if (!$update_stmt->execute()) {
            $success = false;
            break;
        }
    }
}

echo json_encode(['success' => $success]); 
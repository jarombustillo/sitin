<?php
session_start();
include("connect.php");
require_once 'includes/notifications.php';

header('Content-Type: application/json');

if (!isset($_POST['notification_id'])) {
    echo json_encode(['success' => false, 'message' => 'Notification ID is required']);
    exit;
}

$notification_id = (int)$_POST['notification_id'];

if (markNotificationAsRead($conn, $notification_id)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
}
?> 
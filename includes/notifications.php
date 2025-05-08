<?php
function createNotification($conn, $user_id, $message, $type, $details = '') {
    if (empty($message) || !is_string($message)) {
        return false; // Don't insert empty or invalid notifications
    }
    $user_id = (int)$user_id;
    $message = mysqli_real_escape_string($conn, $message);
    $type = mysqli_real_escape_string($conn, $type);
    $details = mysqli_real_escape_string($conn, $details);

    $sql = "INSERT INTO notifications (USER_ID, MESSAGE, TYPE, DETAILS) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $user_id, $message, $type, $details);
    return $stmt->execute();
}

function getUnreadNotifications($conn, $user_id, $type) {
    $user_id = (int)$user_id;
    $type = mysqli_real_escape_string($conn, $type);

    $sql = "SELECT * FROM notifications 
            WHERE (USER_ID = ?)
            AND TYPE = ?
            AND IS_READ = 0
            ORDER BY CREATED_AT DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $type);
    $stmt->execute();
    return $stmt->get_result();
}

function markNotificationAsRead($conn, $notification_id) {
    $notification_id = (int)$notification_id;
    $sql = "UPDATE notifications SET IS_READ = 1 WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $notification_id);
    return $stmt->execute();
}

function getNotificationCount($conn, $user_id, $type) {
    $user_id = (int)$user_id;
    $type = mysqli_real_escape_string($conn, $type);

    $sql = "SELECT COUNT(*) as count FROM notifications 
            WHERE (USER_ID = ?)
            AND TYPE = ?
            AND IS_READ = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $type);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}
?> 
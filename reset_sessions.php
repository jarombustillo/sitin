<?php
session_start();
include 'connect.php';

// Only allow admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit();
}

// Reset session_count to 30 for users with 0 sessions
$sql = "UPDATE user SET session_count = 30 WHERE session_count = 0";
if ($conn->query($sql) === TRUE) {
    echo '<div style="padding:2em;font-family:sans-serif;"><h2>Session counts reset!</h2><p>All users with 0 sessions now have 30 sessions again.</p><a href="admin.php">Back to Dashboard</a></div>';
} else {
    echo '<div style="padding:2em;font-family:sans-serif;"><h2>Error</h2><p>Could not reset sessions: ' . $conn->error . '</p></div>';
}
?> 
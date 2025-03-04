<?php
session_start();
require_once "connect.php"; // Database connection

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

// Fetch statistics
$sql_stats = "SELECT COUNT(*) AS registered_students FROM students";
$result_stats = $conn->query($sql_stats);
$row_stats = $result_stats->fetch_assoc();
$registered_students = $row_stats['registered_students'];

// Fetch announcements
$sql_announcements = "SELECT * FROM announcements ORDER BY date_posted DESC";
$result_announcements = $conn->query($sql_announcements);

// Handle new announcements
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['announcement'])) {
    $announcement = $conn->real_escape_string($_POST['announcement']);
    $date_posted = date("Y-m-d");
    $conn->query("INSERT INTO announcements (content, date_posted) VALUES ('$announcement', '$date_posted')");
    header("Location: admin.php"); // Refresh page
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="styles.css"> <!-- Add a stylesheet -->
</head>
<body>
    <header>
        <h2>College of Computer Studies Admin</h2>
        <nav>
            <a href="students.php">Students</a>
            <a href="sitin.php">Sit-in Records</a>
            <a href="reports.php">Reports</a>
            <a href="logout.php">Log out</a>
        </nav>
    </header>

    <main>
        <section>
            <h3>Statistics</h3>
            <p>Students Registered: <?php echo $registered_students; ?></p>
        </section>

        <section>
            <h3>Post Announcement</h3>
            <form method="POST">
                <textarea name="announcement" required></textarea><br>
                <button type="submit">Submit</button>
            </form>
        </section>

        <section>
            <h3>Posted Announcements</h3>
            <?php while ($row = $result_announcements->fetch_assoc()): ?>
                <p><strong><?php echo $row['date_posted']; ?></strong>: <?php echo $row['content']; ?></p>
            <?php endwhile; ?>
        </section>
    </main>
</body>
</html>

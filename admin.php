<?php
session_start();
include "connect.php"; // Database connection

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

// Fetch statistics
$sql_stats = "SELECT COUNT(*) AS registered_students FROM user";
$result_stats = $conn->query($sql_stats);
$row_stats = $result_stats->fetch_assoc();
$registered_students = $row_stats['registered_students'];

// Fetch announcements
$sql_announcements = "SELECT * FROM announcement ORDER BY CREATED_AT DESC";
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
    <style>
        /* Basic dashboard styling - Customize as needed */
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }

        header {
            background-color: #212529;
            color: #ffffff;
            padding: 10px;
            align-items: center;
        }

        .offcanvas {
            width: 250px;
        }


        nav a {
            color: #fff;
            text-decoration: none;
            margin: 0 10px;
        }

        .main {
            padding: 20px;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            margin-left: 250px;
            grid-gap: 20px;
        }

        section {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h3 {
            margin-top: 0;
        }

        .stats-section {
            text-align: center;
        }

        .stats-section p {
            font-size: 1.2rem;
            font-weight: bold;
        }

        textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            resize: vertical;
        }

        button {
            background-color: #333;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }
        .announcement-item {
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .announcement-item strong {
            display: block;
            margin-bottom: 5px;
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin.php">College of Computer Studies Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="admin.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="students.php">Students</a></li>
                    <li class="nav-item"><a class="nav-link" href="sitin.php">Sit-in Records</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
                    <li class="nav-item"><a class="nav-link" href="login.php">Log out</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <main class="container mt-4">

    </header>

    <main>
        <section class="stats-section">
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
                <div class="announcement-item">
                    <strong><?php echo $row['date_posted']; ?></strong>
                    <p><?php echo $row['content']; ?></p>
                </div>
            <?php endwhile; ?>
        </section>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

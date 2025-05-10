<?php
session_start();
include 'connect.php';

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

// Get today's date in Y-m-d format
$today = date('Y-m-d');

// Fetch all sit-in records for today with user details
$sql = "SELECT sr.ID, sr.IDNO, sr.PURPOSE, sr.LABORATORY, sr.TIME_IN, sr.TIME_OUT,
        u.Lastname, u.Firstname, u.Midname, u.course, u.year_level
        FROM sitin_records sr
        JOIN user u ON sr.IDNO = u.IDNO
        WHERE DATE(sr.TIME_IN) = '$today'
        ORDER BY sr.TIME_IN DESC";

$result = $conn->query($sql);

if (!$result) {
    die("Error fetching data: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sit-in Records</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }

        .main {
            padding: 20px;
            margin-left: 250px;
            grid-gap: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .status-active {
            color: #28a745;
        }

        .status-completed {
            color: #6c757d;
        }
        
        .logout-btn {
            background-color: #e74c3c;
            color: white !important;
            padding: 8px 20px !important;
            border-radius: 4px;
            transition: all 0.3s ease;
            margin-left: 20px;
            text-decoration: none;
        }

        .logout-btn:hover {
            background-color: #c0392b;
            color: white !important;
            text-decoration: none;
        }
    </style>
</head>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="admin.php">Admin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="students.php">Students</a></li>
                <li class="nav-item"><a class="nav-link" href="current_sitin.php">Sit-In</a></li>
                <li class="nav-item"><a class="nav-link active" href="sitinrecords.php">Sit-in Records</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_reservations.php">Reservations</a></li>
                <li class="nav-item"><a class="nav-link " href="admin/lab_schedules.php">Lab Schedules</a></li>
                <li class="nav-item"><a class="nav-link" href="admin/feedback.php">Feedback</a></li>
                <li class="nav-item"><a class="nav-link" href="labresources.php">Lab Resources</a></li>
                <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
                <li class="nav-item"><a class="nav-link" href="reward.php">Leaderboard</a></li>
            </ul>
            <a href="login.php?logout=true" class="logout-btn ms-auto">Log out</a>
        </div>
    </div>
</nav>
<body class="bg-light">
    <div class="container mt-4">
        <h2>Sit-in Records for <?php echo date('F d, Y'); ?></h2>
        
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID Number</th>
                    <th>Name</th>
                    <th>Course & Year</th>
                    <th>Purpose</th>
                    <th>Laboratory</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $fullname = htmlspecialchars($row['Lastname'] . ', ' . $row['Firstname'] . ' ' . $row['Midname']);
                        $course_year = htmlspecialchars($row['course'] . ' - Year ' . $row['year_level']);
                        $status = $row['TIME_OUT'] ? 'Completed' : 'Active';
                        $status_class = $row['TIME_OUT'] ? 'status-completed' : 'status-active';
                        
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['IDNO']) . "</td>";
                        echo "<td>" . $fullname . "</td>";
                        echo "<td>" . $course_year . "</td>";
                        echo "<td>" . htmlspecialchars($row['PURPOSE']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['LABORATORY']) . "</td>";
                        echo "<td>" . date('h:i A', strtotime($row['TIME_IN'])) . "</td>";
                        echo "<td>" . ($row['TIME_OUT'] ? date('h:i A', strtotime($row['TIME_OUT'])) : '-') . "</td>";
                        echo "<td class='" . $status_class . "'>" . $status . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='8' class='text-center'>No sit-in records found for today</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php
$conn->close();
?>

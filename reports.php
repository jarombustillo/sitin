<?php
session_start();
require_once 'connect.php';

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

// Remove date range variables and use all records
$sql_stats = "SELECT 
    COUNT(*) as total_sessions,
    AVG(TIMESTAMPDIFF(MINUTE, TIME_IN, TIME_OUT)) as avg_duration
    FROM sitin_records";

$stmt = $conn->prepare($sql_stats);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Fetch laboratory usage
$sql_labs = "SELECT LABORATORY, COUNT(*) as usage_count
    FROM sitin_records 
    GROUP BY LABORATORY
    ORDER BY usage_count DESC";

$stmt = $conn->prepare($sql_labs);
$stmt->execute();
$lab_usage = $stmt->get_result();

// Fetch course distribution
$sql_courses = "SELECT u.course, COUNT(*) as student_count
    FROM sitin_records sr
    JOIN user u ON sr.IDNO = u.IDNO
    GROUP BY u.course
    ORDER BY student_count DESC";

$stmt = $conn->prepare($sql_courses);
$stmt->execute();
$course_dist = $stmt->get_result();

// Fetch feedback statistics
$sql_feedback = "SELECT 
    AVG(RATING) as avg_rating,
    COUNT(*) as total_feedback
    FROM feedback f
    JOIN sitin_records sr ON f.SITIN_RECORD_ID = sr.ID";

$stmt = $conn->prepare($sql_feedback);
$stmt->execute();
$feedback_stats = $stmt->get_result()->fetch_assoc();

// Fetch daily usage
$sql_daily = "SELECT 
    DATE(TIME_IN) as date,
    COUNT(*) as session_count
    FROM sitin_records 
    GROUP BY DATE(TIME_IN)
    ORDER BY date";

$stmt = $conn->prepare($sql_daily);
$stmt->execute();
$daily_usage = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Sit-in Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .report-card {
            margin-bottom: 20px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
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
                    <li class="nav-item"><a class="nav-link" href="sitinrecords.php">Sit-in Records</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_reservations.php">Reservations</a></li>
                    <li class="nav-item"><a class="nav-link" href="pc_status.php">PC</a></li>
                    <li class="nav-item"><a class="nav-link " href="admin/lab_schedules.php">Lab Schedules</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin/feedback.php">Feedback</a></li>
                    <li class="nav-item"><a class="nav-link" href="labresources.php">Lab Resources</a></li>
                    <li class="nav-item"><a class="nav-link active" href="reports.php">Reports</a></li>
                    <li class="nav-item"><a class="nav-link" href="reward.php">Leaderboard</a></li>
                </ul>
                <a href="login.php?logout=true" class="logout-btn ms-auto">Log out</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>System Reports</h2>

        <!-- Generate Report Button -->
        <div class="card report-card mb-4">
            <div class="card-body">
                <form method="POST" action="generate_report.php" class="row g-3">
                    <div class="col-md-6">
                        <label for="report_format" class="form-label">Report Format</label>
                        <select class="form-select" id="report_format" name="report_format" required>
                            <option value="csv">CSV</option>
                            <option value="excel">Excel</option>
                            <option value="pdf">PDF</option>
                            <option value="print">Print</option>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-download me-2"></i>Generate Report
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="row">
            <div class="col-md-4">
                <div class="card report-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Sessions</h5>
                        <h2 class="card-text"><?php echo number_format($stats['total_sessions']); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card report-card">
                    <div class="card-body">
                        <h5 class="card-title">Average Duration</h5>
                        <h2 class="card-text"><?php echo round($stats['avg_duration']); ?> min</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card report-card">
                    <div class="card-body">
                        <h5 class="card-title">Average Rating</h5>
                        <h2 class="card-text"><?php echo number_format($feedback_stats['avg_rating'], 1); ?>/5</h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card report-card">
                    <div class="card-body">
                        <h5 class="card-title">Laboratory Usage</h5>
                        <div class="chart-container">
                            <canvas id="labChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card report-card">
                    <div class="card-body">
                        <h5 class="card-title">Course Distribution</h5>
                        <div class="chart-container">
                            <canvas id="courseChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card report-card">
                    <div class="card-body">
                        <h5 class="card-title">Daily Usage Trend</h5>
                        <div class="chart-container">
                            <canvas id="dailyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prepare data for charts
        const labData = {
            labels: [<?php 
                $lab_labels = [];
                $lab_values = [];
                while ($row = $lab_usage->fetch_assoc()) {
                    $lab_labels[] = "'Lab " . $row['LABORATORY'] . "'";
                    $lab_values[] = $row['usage_count'];
                }
                echo implode(',', $lab_labels);
            ?>],
            datasets: [{
                data: [<?php echo implode(',', $lab_values); ?>],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)'
                ]
            }]
        };

        const courseData = {
            labels: [<?php 
                $course_labels = [];
                $course_values = [];
                while ($row = $course_dist->fetch_assoc()) {
                    $course_labels[] = "'" . $row['course'] . "'";
                    $course_values[] = $row['student_count'];
                }
                echo implode(',', $course_labels);
            ?>],
            datasets: [{
                data: [<?php echo implode(',', $course_values); ?>],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)'
                ]
            }]
        };

        const dailyData = {
            labels: [<?php 
                $daily_labels = [];
                $daily_values = [];
                while ($row = $daily_usage->fetch_assoc()) {
                    $daily_labels[] = "'" . date('M d', strtotime($row['date'])) . "'";
                    $daily_values[] = $row['session_count'];
                }
                echo implode(',', $daily_labels);
            ?>],
            datasets: [{
                label: 'Daily Sessions',
                data: [<?php echo implode(',', $daily_values); ?>],
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                fill: true
            }]
        };

        // Create charts
        new Chart(document.getElementById('labChart'), {
            type: 'pie',
            data: labData,
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        new Chart(document.getElementById('courseChart'), {
            type: 'pie',
            data: courseData,
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        new Chart(document.getElementById('dailyChart'), {
            type: 'line',
            data: dailyData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html> 
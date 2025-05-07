<?php
session_start();
require_once '../connect.php';

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Fetch all feedback with user and sit-in details
$sql = "SELECT f.*, u.Firstname, u.Lastname, u.Midname, u.course, u.year_level,
        sr.PURPOSE, sr.LABORATORY, sr.TIME_IN, sr.TIME_OUT
        FROM feedback f
        JOIN user u ON f.STUDENT_ID = u.IDNO
        JOIN sitin_records sr ON f.SITIN_RECORD_ID = sr.ID
        ORDER BY f.CREATED_AT DESC";

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
    <title>Feedback Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        .rating {
            color: #ffc107;
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

        .feedback-card {
            margin-bottom: 20px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .feedback-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .feedback-body {
            padding: 20px;
        }

        .feedback-footer {
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
            padding: 10px 20px;
            font-size: 0.9em;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="../admin.php">Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="../students.php">Students</a></li>
                    <li class="nav-item"><a class="nav-link" href="../current_sitin.php">Sit-In</a></li>
                    <li class="nav-item"><a class="nav-link" href="../sitinrecords.php">Sit-in Records</a></li>
                    <li class="nav-item"><a class="nav-link" href="../manage_reservations.php">Reservations</a></li>
                    <li class="nav-item"><a class="nav-link active" href="feedback.php">Feedback</a></li>
                    <li class="nav-item"><a class="nav-link" href="../labresources.php">Lab Resources</a></li>
                    <li class="nav-item"><a class="nav-link" href="../reports.php">Reports</a></li>
                </ul>
                <a href="../login.php?logout=true" class="logout-btn ms-auto">Log out</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Student Feedback</h2>
        
        <?php if ($result->num_rows > 0): ?>
            <div class="row">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="col-md-6">
                        <div class="card feedback-card">
                            <div class="card-header feedback-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <?php 
                                        echo htmlspecialchars($row['Lastname'] . ', ' . $row['Firstname'] . ' ' . $row['Midname']);
                                        echo ' (' . htmlspecialchars($row['course'] . ' - Year ' . $row['year_level']) . ')';
                                        ?>
                                    </h5>
                                    <div class="rating">
                                        <?php
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= $row['RATING'] ? '★' : '☆';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body feedback-body">
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($row['COMMENT'])); ?></p>
                                <div class="text-muted small">
                                    <strong>Session Details:</strong><br>
                                    Purpose: <?php echo htmlspecialchars($row['PURPOSE']); ?><br>
                                    Laboratory: <?php echo htmlspecialchars($row['LABORATORY']); ?><br>
                                    Time: <?php 
                                        echo date('M d, Y h:i A', strtotime($row['TIME_IN'])) . ' - ' . 
                                             date('h:i A', strtotime($row['TIME_OUT']));
                                    ?>
                                </div>
                            </div>
                            <div class="card-footer feedback-footer">
                                Submitted on: <?php echo date('F d, Y h:i A', strtotime($row['CREATED_AT'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                No feedback submissions yet.
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
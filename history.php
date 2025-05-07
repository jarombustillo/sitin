<?php
session_start();
require_once 'connect.php';

// Check if user is logged in
if (!isset($_SESSION['IDNO'])) {
    header("Location: login.php");
    exit();
}

// Fetch user's sit-in history
$sql = "SELECT sr.*, f.RATING, f.COMMENT 
        FROM sitin_records sr 
        LEFT JOIN feedback f ON sr.ID = f.SITIN_RECORD_ID 
        WHERE sr.IDNO = ? 
        ORDER BY sr.TIME_IN DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION['IDNO']);
$stmt->execute();
$result = $stmt->get_result();

// Get user information
$user_sql = "SELECT Firstname, Lastname, Midname, course, year_level FROM user WHERE IDNO = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("s", $_SESSION['IDNO']);
$user_stmt->execute();
$user_info = $user_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sit-in History - Student Dashboard</title>
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

        .history-card {
            margin-bottom: 20px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: bold;
        }

        .status-completed {
            background-color: #28a745;
            color: white;
        }

        .status-ongoing {
            background-color: #ffc107;
            color: black;
        }

        .rating-stars {
            color: #ffc107;
        }

        .table-responsive {
            margin-top: 20px;
        }

        .table th {
            background-color: #f8f9fa;
        }

        .user-info-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .stats-card {
            background-color: #fff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stats-number {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }

        .stats-label {
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">Student</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="edit.php">Edit Profile</a></li>
                    <li class="nav-item"><a class="nav-link active" href="history.php">History</a></li>
                    <li class="nav-item"><a class="nav-link" href="reservation.php">Reservation</a></li>
                    <li class="nav-item"><a class="nav-link" href="view_lab_resources.php">Lab Resources</a></li>
                </ul>
                <a href="login.php?logout=true" class="logout-btn ms-auto">Log out</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- User Information -->
        <div class="user-info-card">
            <div class="row">
                <div class="col-md-6">
                    <h4>Welcome, <?php echo htmlspecialchars($user_info['Firstname'] . ' ' . $user_info['Lastname']); ?></h4>
                    <p class="mb-1"><?php echo htmlspecialchars($user_info['course'] . ' - Year ' . $user_info['year_level']); ?></p>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <?php
            // Calculate statistics
            $total_sessions = 0;
            $completed_sessions = 0;
            $total_duration = 0;
            $feedback_count = 0;
            $result->data_seek(0); // Reset result pointer
            
            while ($row = $result->fetch_assoc()) {
                $total_sessions++;
                if ($row['TIME_OUT']) {
                    $completed_sessions++;
                    $duration = strtotime($row['TIME_OUT']) - strtotime($row['TIME_IN']);
                    $total_duration += $duration;
                }
                if ($row['RATING']) {
                    $feedback_count++;
                }
            }
            $result->data_seek(0); // Reset result pointer again for the table
            ?>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $total_sessions; ?></div>
                    <div class="stats-label">Total Sessions</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $completed_sessions; ?></div>
                    <div class="stats-label">Completed Sessions</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo round($total_duration / 3600, 1); ?>h</div>
                    <div class="stats-label">Total Hours</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $feedback_count; ?></div>
                    <div class="stats-label">Feedback Given</div>
                </div>
            </div>
        </div>

        <h2>Sit-in History</h2>
        
        <div class="card history-card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Laboratory</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th>Duration</th>
                                <th>Feedback</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($row['TIME_IN'])); ?></td>
                                    <td>
                                        <?php 
                                        echo date('h:i A', strtotime($row['TIME_IN']));
                                        if ($row['TIME_OUT']) {
                                            echo ' - ' . date('h:i A', strtotime($row['TIME_OUT']));
                                        }
                                        ?>
                                    </td>
                                    <td>Lab <?php echo htmlspecialchars($row['LABORATORY']); ?></td>
                                    <td><?php echo htmlspecialchars($row['PURPOSE']); ?></td>
                                    <td>
                                        <?php if ($row['TIME_OUT']): ?>
                                            <span class="status-badge status-completed">Completed</span>
                                        <?php else: ?>
                                            <span class="status-badge status-ongoing">Ongoing</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($row['TIME_OUT']) {
                                            $duration = strtotime($row['TIME_OUT']) - strtotime($row['TIME_IN']);
                                            $hours = floor($duration / 3600);
                                            $minutes = floor(($duration % 3600) / 60);
                                            echo $hours . 'h ' . $minutes . 'm';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($row['RATING']): ?>
                                            <div class="rating-stars">
                                                <?php
                                                for ($i = 1; $i <= 5; $i++) {
                                                    echo $i <= $row['RATING'] ? '★' : '☆';
                                                }
                                                ?>
                                            </div>
                                            <?php if ($row['COMMENT']): ?>
                                                <small class="text-muted d-block">
                                                    <?php echo htmlspecialchars(substr($row['COMMENT'], 0, 50)) . (strlen($row['COMMENT']) > 50 ? '...' : ''); ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php elseif ($row['TIME_OUT']): ?>
                                            <a href="feedback.php?sitin_id=<?php echo $row['ID']; ?>" class="btn btn-sm btn-outline-primary">Give Feedback</a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
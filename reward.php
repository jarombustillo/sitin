<?php
session_start();
include("connect.php");

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

// Handle point adjustments
if (isset($_POST['adjust_points'])) {
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $points = (int)$_POST['points'];
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    
    // Update or insert points
    $check_sql = "SELECT * FROM reward_points WHERE STUDENT_ID = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $student_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing record
        $update_sql = "UPDATE reward_points SET POINTS = POINTS + ?, LAST_REWARD_DATE = CURRENT_DATE WHERE STUDENT_ID = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("is", $points, $student_id);
        $update_stmt->execute();
    } else {
        // Insert new record
        $insert_sql = "INSERT INTO reward_points (STUDENT_ID, POINTS, LAST_REWARD_DATE) VALUES (?, ?, CURRENT_DATE)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("si", $student_id, $points);
        $insert_stmt->execute();
    }
    
    // Record in points history
    $history_sql = "INSERT INTO points_history (IDNO, FULLNAME, POINTS_EARNED, CONVERSION_DATE) VALUES (?, ?, ?, NOW())";
    $history_stmt = $conn->prepare($history_sql);
    $history_stmt->bind_param("ss", $student_id, $fullname);
    $history_stmt->execute();
    
    header("Location: reward.php?success=1");
    exit();
}

// Handle point conversion to sessions
if (isset($_POST['convert_to_session'])) {
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $points = (int)$_POST['points'];
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    
    // Convert points to sessions (1 point = 1 session)
    $update_sql = "UPDATE user SET session_count = session_count + ? WHERE IDNO = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("is", $points, $student_id);
    $update_stmt->execute();
    
    // Update points history
    $history_sql = "UPDATE points_history SET CONVERTED_TO_SESSION = 1 WHERE IDNO = ? AND CONVERTED_TO_SESSION = 0";
    $history_stmt = $conn->prepare($history_sql);
    $history_stmt->bind_param("s", $student_id);
    $history_stmt->execute();
    
    // Reset points
    $reset_sql = "UPDATE reward_points SET POINTS = 0 WHERE STUDENT_ID = ?";
    $reset_stmt = $conn->prepare($reset_sql);
    $reset_stmt->bind_param("s", $student_id);
    $reset_stmt->execute();
    
    header("Location: reward.php?converted=1");
    exit();
}

// Handle reward action: convert 2 points to 1 session, max 30 sessions
if (isset($_POST['reward_action'])) {
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    // Get current points and session count
    $get_sql = "SELECT COALESCE(rp.POINTS,0) as points, u.session_count FROM user u LEFT JOIN reward_points rp ON u.IDNO = rp.STUDENT_ID WHERE u.IDNO = ?";
    $get_stmt = $conn->prepare($get_sql);
    $get_stmt->bind_param("s", $student_id);
    $get_stmt->execute();
    $get_result = $get_stmt->get_result();
    $row = $get_result->fetch_assoc();
    $points = (int)$row['points'];
    $sessions = (int)$row['session_count'];
    if ((int)$points >= 2 && (int)$sessions < 30) {
        // Subtract 3 points, add 1 session (max 30)
        $new_sessions = min($sessions + 1, 30);
        $update_points_sql = "UPDATE reward_points SET POINTS = POINTS - 3 WHERE STUDENT_ID = ?";
        $update_points_stmt = $conn->prepare($update_points_sql);
        $update_points_stmt->bind_param("s", $student_id);
        $update_points_stmt->execute();
        $update_sessions_sql = "UPDATE user SET session_count = ? WHERE IDNO = ?";
        $update_sessions_stmt = $conn->prepare($update_sessions_sql);
        $update_sessions_stmt->bind_param("is", $new_sessions, $student_id);
        $update_sessions_stmt->execute();
        // Record in points history
        $history_sql = "INSERT INTO points_history (IDNO, FULLNAME, POINTS_EARNED, CONVERSION_DATE, CONVERTED_TO_SESSION) VALUES (?, ?, -2, NOW(), 1)";
        $history_stmt = $conn->prepare($history_sql);
        $history_stmt->bind_param("ss", $student_id, $fullname);
        $history_stmt->execute();
        header("Location: reward.php?rewarded=1");
        exit();
    } else {
        error_log("DEBUG: Not enough points or session limit reached for student $student_id: points=$points, sessions=$sessions");
        header("Location: reward.php?reward_error=1");
        exit();
    }
}

// Get all students with their points
$sql = "SELECT 
            u.IDNO, 
            u.Firstname, 
            u.Lastname, 
            u.course, 
            u.year_level, 
            u.session_count, 
            COALESCE(SUM(rp.POINTS), 0) as points,
            MAX(rp.LAST_REWARD_DATE) as LAST_REWARD_DATE
        FROM user u
        LEFT JOIN reward_points rp ON u.IDNO = rp.STUDENT_ID
        GROUP BY u.IDNO, u.Firstname, u.Lastname, u.course, u.year_level, u.session_count
        ORDER BY points DESC, u.Lastname, u.Firstname";
$result = $conn->query($sql);

// Get points history
$history_sql = "SELECT ph.*, u.Firstname, u.Lastname 
                FROM points_history ph
                JOIN user u ON ph.IDNO = u.IDNO
                ORDER BY ph.CONVERSION_DATE DESC
                LIMIT 50";
$history_result = $conn->query($history_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: white;
        }
        .leaderboard-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(20, 86, 155, 0.10);
            overflow: hidden;
            margin-top: 30px;
            background: white;
        }
        .leaderboard-header {
            background: #14569b;
            color: #fff;
            padding: 18px 32px;
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
            display: flex;
            align-items: center;
            font-size: 1.3rem;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(20,86,155,0.08);
        }
        .leaderboard-header .fa-trophy {
            margin-right: 12px;
            font-size: 1.5em;
        }
        .leaderboard-table {
            width: 100%;
            background: #fff;
            border-radius: 0 0 16px 16px;
            overflow: hidden;
        }
        .leaderboard-table th, .leaderboard-table td {
            padding: 12px 16px;
            text-align: left;
        }
        .leaderboard-table th {
            background: #fff;
            color: #14569b;
            font-weight: 700;
            border-bottom: 2px solid #e2e8f0;
        }
        .leaderboard-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        .leaderboard-table tr:nth-child(odd) {
            background: #fff;
        }
        .medal {
            font-size: 1.2em;
            margin-right: 6px;
        }
        .gold { color: #FFD700; }
        .silver { color: #C0C0C0; }
        .bronze { color: #CD7F32; }
        .usericon { color: #14569b; margin-right: 6px; }
        .points-cell {
            font-weight: bold;
            font-size: 1.1em;
            color: #14569b;
        }
        .name-cell {
            display: flex;
            align-items: center;
        }
        .leaderboard-card:hover {
            transform: translateY(-5px);
        }
        .points-badge {
            font-size: 1.2em;
            padding: 0.5em 1em;
        }
        .rank-badge {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }
        .rank-1 { background-color: #FFD700; color: #222 !important; }
        .rank-2 { background-color: #C0C0C0; }
        .rank-3 { background-color: #CD7F32; }
        .rank-other { background-color: #6c757d; }
        .top-performer {
            background: linear-gradient(45deg, #FFD700, #FFA500);
            color: white;
        }
        .history-item {
            border-left: 4px solid #007bff;
            padding-left: 1rem;
            margin-bottom: 1rem;
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
        .student-card {
            border-radius: 10px;
            margin-bottom: 15px;
            padding: 15px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .student-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .top-3-section {
            margin-bottom: 30px;
        }
        .medal-icon {
            font-size: 2em;
            margin-right: 10px;
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
                <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
                <li class="nav-item"><a class="nav-link active" href="reward.php">Leaderboard</a></li>
            </ul>
            <a href="login.php?logout=true" class="logout-btn ms-auto">Log out</a>
        </div>
    </div>
</nav>

    <div class="container mt-4">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Points updated successfully!</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['converted'])): ?>
            <div class="alert alert-success">Points converted to sessions successfully!</div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="leaderboard-card">
                    <div class="leaderboard-header">
                        <i class="fa fa-trophy"></i> Leaderboard
                    </div>
                    <div class="table-responsive">
                        <table class="leaderboard-table">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>ID No</th>
                                    <th>Name</th>
                                    <th>Points</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $result->data_seek(0);
                                $rank = 1;
                                while (($row = $result->fetch_assoc()) && $rank <= 5):
                                    $icon = '<i class="fa fa-user usericon"></i>';
                                    $name = htmlspecialchars($row['Lastname'] . ', ' . $row['Firstname']);
                                    if ($rank == 1) {
                                        $icon = '<i class="fa fa-trophy medal gold"></i>';
                                    } elseif ($rank == 2) {
                                        $icon = '<i class="fa fa-medal medal silver"></i>';
                                    } elseif ($rank == 3) {
                                        $icon = '<i class="fa fa-medal medal bronze"></i>';
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $rank; ?></td>
                                    <td><?php echo htmlspecialchars($row['IDNO']); ?></td>
                                    <td class="name-cell"><?php echo $icon . $name; ?></td>
                                    <td class="points-cell"><?php echo $row['points']; ?></td>
                                </tr>
                                <?php $rank++; endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- All Students List with Reward Action -->
        <div class="row justify-content-center mt-4">
            <div class="col-lg-8 col-md-10">
                <div class="leaderboard-card">
                    <div class="leaderboard-header">
                        <i class="fa fa-users"></i> All Students
                    </div>
                    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID No</th>
                                    <th>Name</th>
                                    <th>Course & Year</th>
                                    <th>Points</th>
                                    <th>Sessions</th>
                                    <th>Reward</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $result->data_seek(0);
                                while ($row = $result->fetch_assoc()):
                                    $fullname = htmlspecialchars($row['Firstname'] . ' ' . $row['Lastname']);
                                    $course_year = htmlspecialchars($row['course'] . ' - Year ' . $row['year_level']);
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['IDNO']); ?></td>
                                    <td><?php echo $fullname; ?></td>
                                    <td><?php echo $course_year; ?></td>
                                    <td><?php echo $row['points']; ?></td>
                                    <td><?php echo $row['session_count']; ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($row['IDNO']); ?>">
                                            <input type="hidden" name="fullname" value="<?php echo $fullname; ?>">
                                            <button type="submit" name="reward_action" class="btn btn-sm btn-success"
                                                <?php if ($row['points'] < 3 || $row['session_count'] >= 30) echo 'disabled'; ?>>
                                                Reward
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Points History Section -->
        <div class="row">
            <div class="col-md-12">
                <div class="card leaderboard-card">
                    <div class="leaderboard-header">
                        <h5 class="card-title mb-0">Points History</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Student</th>
                                    <th>Points Earned</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($history = $history_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y H:i', strtotime($history['CONVERSION_DATE'])); ?></td>
                                        <td><?php echo htmlspecialchars($history['FULLNAME']); ?></td>
                                        <td>
                                            <?php if ($history['POINTS_EARNED'] > 0): ?>
                                                <span class="text-success">+<?php echo $history['POINTS_EARNED']; ?></span>
                                            <?php else: ?>
                                                <span class="text-danger"><?php echo $history['POINTS_EARNED']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            if ($history['CONVERTED_TO_SESSION']) {
                                                echo '<span class="badge bg-info">Converted to Session</span>';
                                            } else {
                                                echo '<span class="badge bg-success">Sit-in/Manual</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Adjust Points Modal -->
    <div class="modal fade" id="adjustPointsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adjust Points</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="reward.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="student_id" id="adjust_student_id">
                        <input type="hidden" name="fullname" id="adjust_student_name">
                        <div class="mb-3">
                            <label class="form-label">Student</label>
                            <input type="text" class="form-control" id="adjust_student_display" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Current Points</label>
                            <input type="text" class="form-control" id="adjust_current_points" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Points to Add/Subtract</label>
                            <input type="number" class="form-control" name="points" required>
                            <small class="text-muted">Use negative numbers to subtract points</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="adjust_points" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Convert Points Modal -->
    <div class="modal fade" id="convertPointsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Convert Points to Sessions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="reward.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="student_id" id="convert_student_id">
                        <input type="hidden" name="fullname" id="convert_student_name">
                        <div class="mb-3">
                            <label class="form-label">Student</label>
                            <input type="text" class="form-control" id="convert_student_display" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Available Points</label>
                            <input type="text" class="form-control" id="convert_available_points" readonly>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Converting points will add the same number of sessions to the student's account.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="convert_to_session" class="btn btn-success">Convert Points</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['rewarded'])): ?>
        <div class="alert alert-success text-center">Reward applied: 3 points converted to 1 session!</div>
    <?php elseif (isset($_GET['reward_error'])): ?>
        <div class="alert alert-danger text-center">Cannot reward: Not enough points or session limit reached.</div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Adjust Points Modal
        document.querySelectorAll('[data-bs-target="#adjustPointsModal"]').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('adjust_student_id').value = this.dataset.studentId;
                document.getElementById('adjust_student_name').value = this.dataset.studentName;
                document.getElementById('adjust_student_display').value = this.dataset.studentName;
                document.getElementById('adjust_current_points').value = this.dataset.currentPoints;
            });
        });

        // Convert Points Modal
        document.querySelectorAll('[data-bs-target="#convertPointsModal"]').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('convert_student_id').value = this.dataset.studentId;
                document.getElementById('convert_student_name').value = this.dataset.studentName;
                document.getElementById('convert_student_display').value = this.dataset.studentName;
                document.getElementById('convert_available_points').value = this.dataset.availablePoints;
            });
        });

        function setAdjustModal(id, name, points) {
            document.getElementById('adjust_student_id').value = id;
            document.getElementById('adjust_student_name').value = name;
            document.getElementById('adjust_student_display').value = name;
            document.getElementById('adjust_current_points').value = points;
        }
    </script>
</body>
</html> 
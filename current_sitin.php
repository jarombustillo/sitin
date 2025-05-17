<?php
session_start();
include 'connect.php';

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

// Only try to get profile picture if it's a regular user
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $sql_profile = "SELECT profilepic FROM user WHERE username = ?";
    $stmt_profile = $conn->prepare($sql_profile);
    $stmt_profile->bind_param("s", $username);
    $stmt_profile->execute();
    $result_profile = $stmt_profile->get_result();
    $user = $result_profile->fetch_assoc();
}

// Handle sit-out
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['time_out_submit'])) {
    $sit_id = $conn->real_escape_string($_POST['sit_id']);
    $timeout_time = date("Y-m-d H:i:s");
    
    // First get the current session count and IDNO
    $get_session_sql = "SELECT u.session_count, u.IDNO, sr.LABORATORY, sr.PURPOSE, sr.TIME_IN 
                       FROM sitin_records sr 
                       JOIN user u ON sr.IDNO = u.IDNO 
                       WHERE sr.ID = '$sit_id'";
    $session_result = $conn->query($get_session_sql);
    $session_row = $session_result->fetch_assoc();
    $current_session = $session_row['session_count'];
    $idno = $session_row['IDNO'];
    $laboratory = $session_row['LABORATORY'];
    $purpose = $session_row['PURPOSE'];
    $time_in = $session_row['TIME_IN'];
    
    // Decrease session count by 1
    $new_session_count = $current_session - 1;
    
    // Update the record with time out
    $sql_timeout = "UPDATE sitin_records SET TIME_OUT = '$timeout_time' WHERE ID = '$sit_id'";
    
    // Update the user's session count
    $update_session_sql = "UPDATE user SET session_count = '$new_session_count' WHERE IDNO = '$idno'";

    // Mark the reservation as USED if it matches this session
    $update_reservation_sql = "UPDATE reservations SET USED = 1 WHERE IDNO = '$idno' AND LABORATORY = '$laboratory' AND PURPOSE = '$purpose' AND STATUS = 'confirmed' AND USED = 0 LIMIT 1";

    if ($conn->query($sql_timeout) === TRUE && $conn->query($update_session_sql) === TRUE) {
        $conn->query($update_reservation_sql);
        // Success
        header("Location: current_sitin.php?timeout_success=true");
        exit();
    } else {
        // Error
        $error_message = "Error: " . $conn->error;
    }
}

// Handle start session for reserved users
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['start_session'])) {
    $idno = $conn->real_escape_string($_POST['idno']);
    $laboratory = $conn->real_escape_string($_POST['laboratory']);
    $purpose = $conn->real_escape_string($_POST['purpose']);
    $time_in = date("Y-m-d H:i:s");
    // Insert into sitin_records with TIME_OUT as NULL
    $sql_insert = "INSERT INTO sitin_records (IDNO, PURPOSE, LABORATORY, TIME_IN, TIME_OUT) VALUES ('$idno', '$purpose', '$laboratory', '$time_in', NULL)";
    if ($conn->query($sql_insert) === TRUE) {
        header("Location: current_sitin.php?session_started=true");
        exit();
    } else {
        $error_message = "Error starting session: " . $conn->error;
    }
}

// Fetch current sit-in users
$sql_sitin = "SELECT sr.ID, sr.IDNO, sr.PURPOSE, sr.LABORATORY, sr.TIME_IN,
        u.Lastname, u.Firstname, u.Midname, u.course, u.year_level, u.session_count,
        NULL as reservation_status, NULL as PC_NUMBER, NULL as TIME_SLOT, 'Sit-In' as user_status
        FROM sitin_records sr
        JOIN user u ON sr.IDNO = u.IDNO
        WHERE sr.TIME_OUT IS NULL";

// Fetch users with approved reservations who are not currently sitting in
$sql_reservation = "SELECT NULL as ID, r.IDNO, r.PURPOSE, r.LABORATORY, NULL as TIME_IN,
        u.Lastname, u.Firstname, u.Midname, u.course, u.year_level, u.session_count,
        r.STATUS as reservation_status, r.PC_NUMBER, r.TIME_SLOT, 'Reserved' as user_status
        FROM reservations r
        JOIN user u ON r.IDNO = u.IDNO
        WHERE r.STATUS = 'confirmed' AND r.USED = 0
        AND r.IDNO NOT IN (
            SELECT IDNO FROM sitin_records WHERE TIME_OUT IS NULL
        )";

$reservation_result = $conn->query($sql_reservation);

// Combine both results
$result = $conn->query($sql_sitin);
$users = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
if ($reservation_result && $reservation_result->num_rows > 0) {
    while ($row = $reservation_result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Current Sit-In</title>
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

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-sitout {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-sitout:hover {
            background-color: #c82333;
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
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="students.php">Students</a></li>
                <li class="nav-item"><a class="nav-link active" href="current_sitin.php">Sit-In</a></li>
                <li class="nav-item"><a class="nav-link" href="sitinrecords.php">Sit-in Records</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_reservations.php">Reservations</a></li>
                <li class="nav-item"><a class="nav-link" href="pc_status.php">PC</a></li>
                <li class="nav-item"><a class="nav-link " href="admin/lab_schedules.php">Lab Schedules</a></li>
                <li class="nav-item"><a class="nav-link" href="admin/feedback.php">Feedback</a></li>
                <li class="nav-item"><a class="nav-link" href="labresources.php">Lab Resources</a></li>
                <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
                <li class="nav-item"><a class="nav-link" href="reward.php">Leaderboard</a></li>
                </ul>
            </ul>
            <a href="login.php?logout=true" class="logout-btn ms-auto">Log out</a>
        </div>
    </div>
</nav>
<body class="bg-light">
    <div class="container mt-4">
        <h2>Current Sit-In Users</h2>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID Number</th>
                    <th>Name</th>
                    <th>Course & Year</th>
                    <th>Purpose</th>
                    <th>Laboratory</th>
                    <th>Time In</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (count($users) > 0) {
                    foreach ($users as $row) {
                        $fullname = htmlspecialchars($row['Lastname'] . ', ' . $row['Firstname'] . ' ' . $row['Midname']);
                        $course_year = htmlspecialchars($row['course'] . ' - Year ' . $row['year_level']);
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['IDNO']) . "</td>";
                        echo "<td>" . $fullname . "</td>";
                        echo "<td>" . $course_year . "</td>";
                        echo "<td>" . htmlspecialchars($row['PURPOSE']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['LABORATORY']) . "</td>";
                        echo "<td>" . ($row['TIME_IN'] ? date('h:i A', strtotime($row['TIME_IN'])) : '-') . "</td>";
                        echo "<td>" . $row['user_status'] . "</td>";
                        echo "<td class='action-buttons'>";
                        if ($row['user_status'] === 'Sit-In') {
                            echo "<span class='me-2'>Sessions: " . htmlspecialchars($row['session_count']) . "</span>";
                            echo "<form method='POST' style='display: inline;'>";
                            echo "<input type='hidden' name='sit_id' value='" . $row['ID'] . "'>";
                            echo "<button type='submit' name='time_out_submit' class='btn-sitout'>Time Out</button>";
                            echo "</form>";
                        } else if ($row['user_status'] === 'Reserved') {
                            echo "<form method='POST' style='display: inline;'>";
                            echo "<input type='hidden' name='idno' value='" . htmlspecialchars($row['IDNO']) . "'>";
                            echo "<input type='hidden' name='laboratory' value='" . htmlspecialchars($row['LABORATORY']) . "'>";
                            echo "<input type='hidden' name='purpose' value='" . htmlspecialchars($row['PURPOSE']) . "'>";
                            echo "<button type='submit' name='start_session' class='btn btn-primary btn-sm'>Start Session</button>";
                            echo "</form>";
                        } else {
                            echo "-";
                        }
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='9' class='text-center'>No users currently sitting in or reserved</td></tr>";
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

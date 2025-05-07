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
    $get_session_sql = "SELECT u.session_count, u.IDNO 
                       FROM sitin_records sr 
                       JOIN user u ON sr.IDNO = u.IDNO 
                       WHERE sr.ID = '$sit_id'";
    $session_result = $conn->query($get_session_sql);
    $session_row = $session_result->fetch_assoc();
    $current_session = $session_row['session_count'];
    $idno = $session_row['IDNO'];
    
    // Decrease session count by 1
    $new_session_count = $current_session - 1;
    
    // Update the record with time out
    $sql_timeout = "UPDATE sitin_records SET TIME_OUT = '$timeout_time' WHERE ID = '$sit_id'";
    
    // Update the user's session count
    $update_session_sql = "UPDATE user SET session_count = '$new_session_count' WHERE IDNO = '$idno'";

    if ($conn->query($sql_timeout) === TRUE && $conn->query($update_session_sql) === TRUE) {
        // Success
        header("Location: current_sitin.php?timeout_success=true");
        exit();
    } else {
        // Error
        $error_message = "Error: " . $conn->error;
    }
}

// Fetch current sit-in records with user details
$sql = "SELECT sr.ID, sr.IDNO, sr.PURPOSE, sr.LABORATORY, sr.TIME_IN,
        u.Lastname, u.Firstname, u.Midname, u.course, u.year_level, u.session_count
        FROM sitin_records sr
        JOIN user u ON sr.IDNO = u.IDNO
        WHERE sr.TIME_OUT IS NULL
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
                <li class="nav-item"><a class="nav-link" href="admin/feedback.php">Feedback</a></li>
                <li class="nav-item"><a class="nav-link" href="labresources.php">Lab Resources</a></li>
                <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
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
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $fullname = htmlspecialchars($row['Lastname'] . ', ' . $row['Firstname'] . ' ' . $row['Midname']);
                        $course_year = htmlspecialchars($row['course'] . ' - Year ' . $row['year_level']);
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['IDNO']) . "</td>";
                        echo "<td>" . $fullname . "</td>";
                        echo "<td>" . $course_year . "</td>";
                        echo "<td>" . htmlspecialchars($row['PURPOSE']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['LABORATORY']) . "</td>";
                        echo "<td>" . date('h:i A', strtotime($row['TIME_IN'])) . "</td>";
                        echo "<td class='action-buttons'>";
                        echo "<span class='me-2'>Sessions: " . htmlspecialchars($row['session_count']) . "</span>";
                        echo "<form method='POST' style='display: inline;'>";
                        echo "<input type='hidden' name='sit_id' value='" . $row['ID'] . "'>";
                        echo "<button type='submit' name='time_out_submit' class='btn-sitout'>Time Out</button>";
                        echo "</form>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' class='text-center'>No users currently sitting in</td></tr>";
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

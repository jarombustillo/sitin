<?php
session_start();
include 'connect.php';

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

//get the profile picture from database
$username = $_SESSION['user']['USERNAME']; // Assuming you store username in session
$sql_profile = "SELECT PROFILE_PIC FROM user WHERE USERNAME = ?";
$stmt_profile = $conn->prepare($sql_profile);
$stmt_profile->bind_param("s", $username);
$stmt_profile->execute();
$result_profile = $stmt_profile->get_result();
$user = $result_profile->fetch_assoc();

// Handle sit-out
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sit_out_submit'])) {
    $sit_id = $conn->real_escape_string($_POST['sit_id']);
    $timeout_time = date("Y-m-d H:i:s");

    $sql_timeout = "UPDATE sitin_records SET TIME_OUT = '$timeout_time' WHERE sit_id = '$sit_id'";

    if ($conn->query($sql_timeout) === TRUE) {
        // Success
        header("Location: current_sitin.php?timeout_success=true");
        exit();
    } else {
        // Error
        $error_message = "Error: " . $conn->error;
    }
}


// Fetch sit-in records from the database
$sql = "SELECT * FROM sitin_records WHERE TIME_OUT IS NULL";
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
    <title>Sit-In</title>
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
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
    </style>
</head>
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
                    <li class="nav-item"><a class="nav-link" href="current_sitin.php">Sit-In</a></li>
                    <li class="nav-item"><a class="nav-link" href="sitin.php">Sit-in Records</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Reports</a></li>
                    <li class="nav-item"><a class="nav-link" href="login.php">Log out</a></li>
                </ul>
            </div>
        </div>
    </nav>
<body class="bg-light">
    <h2>Current Sit in</h2>
    <table border="1">
        <tr>
            <th>Sit ID Number</th>
            <th>ID Number</th>
            <th>Name</th>
            <th>Purpose</th>
            <th>Sit Lab</th>
            <th>Session</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['sit_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['student_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['purpose']) . "</td>";
                echo "<td>" . htmlspecialchars($row['lab']) . "</td>";
                echo "<td>" . htmlspecialchars($row['session']) . "</td>";
                echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                echo "<td><a href='edit_sitin.php?id=" . $row['sit_id'] . "'>Edit</a> | <a href='delete_sitin.php?id=" . $row['sit_id'] . "' onclick='return confirm(\"Are you sure?\")'>Delete</a></td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='8'>No data available</td></tr>";
        }
        ?>
    </table>
</body>
</html>

<?php
$conn->close();
?>

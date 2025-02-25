<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }
        .container-fluid {
            flex: 1;
        }
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
        }
        .sidebar-sticky {
            position: -webkit-sticky;
            position: sticky;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        .card {
            margin-bottom: 20px;
            text-align: center;

        }
        .announcement-box, .rules-box {
            max-height: 250px;
            overflow-y: auto;
        }
        .col-md-4{
            margin-left: 350px;
        }
        .mt-4{
            margin-left: 200px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <a class="navbar-brand" href="#">Dashboard</a>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="sidebar-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="edit.php">Edit Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="view_remaining_system.php">View Remaining Session</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="history.php">History</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reservation.php">Reservation</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php" onclick="return confirm('Are you sure you want to log out?');">Logout</a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main role="main" class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <h1 class="mt-4">Welcome to Sit-in Monitoring System</h1>
                <div class="col-md-4">
    
                    <!-- Profile -->
                    <div class="card">
                    <div class="card-header bg-primary text-white">Profile</div>
                    <div class="card-body text-center">
                        <?php
                        session_start();
                        include "connect.php";

                        if (!isset($_SESSION['username'])) {
                            echo "<p>Please <a href='login.php'>log in</a> to view your profile.</p>";
                            exit();
                        }

                        $username = $_SESSION['username'];
                        $sql = "SELECT * FROM user WHERE username='$username'";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            $row = $result->fetch_assoc();
                            $fullname = htmlspecialchars($row['Firstname']) . " " . htmlspecialchars($row['Midname']) . " " . htmlspecialchars($row['Lastname']);
                            $profilepic = !empty($row['profilepic']) ? "uploads/" . htmlspecialchars($row['profilepic']) : "default-avatar.png";

                            echo "<img src='$profilepic' class='rounded-circle mb-3' width='100' height='100' alt='Profile Picture'>";
                            echo "<p><strong>IDNO:</strong> " . htmlspecialchars($row['IDNO']) . "</p>";
                            echo "<p><strong>Name:</strong> $fullname</p>";
                            echo "<p><strong>Course:</strong> " . htmlspecialchars($row['course']) . "</p>";
                            echo "<p><strong>Year Level:</strong> " . htmlspecialchars($row['year_level']) . "</p>";
                        } else {
                            echo "<script>alert('No user data found');</script>";
                        }
                        ?>
                    </div>
                </div>
            </div>
                    
                    <!-- Announcements -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-info text-white">Announcements</div>
                            <div class="card-body announcement-box">
                                <p>No announcement yet.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Rules & Regulations -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">Rules & Regulations</div>
                            <div class="card-body rules-box">
                                <h5 class="text-center">University of Cebu</h5>
                                <h6 class="text-center">COLLEGE OF INFORMATION & COMPUTER STUDIES</h6>
                                <p><strong>LABORATORY RULES AND REGULATIONS</strong></p>
                                <p>To avoid embarrassment and maintain camaraderie with your friends and superiors at our laboratories, please observe the following:</p>
                                <ul>
                                    <li>Maintain silence, proper decorum, and discipline inside the laboratory. Mobile phones, walkmans, and other personal pieces of equipment must be switched off.</li>
                                    <li>Games are not allowed inside the lab. This includes computer-related games, card games, and other games that may disturb the operation of the lab.</li>
                                    <li>Surfing the Internet is allowed only with the permission of the instructor. Downloading and installing of software are strictly prohibited.</li>
                                    <li>Getting access to other websites not related to the
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

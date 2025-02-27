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
        .offcanvas {
            width: 250px;
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
            position: relative;
            width: 80%;
            justify-content: center;
        }
        .mt-4{
            display: inline-block;
            margin-top: 20px;
            margin-bottom: 20px;
            justify-content: center;
        }
    </style>
</head>
<body>
        <nav class="navbar navbar-dark bg-dark">
                <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <a class="navbar-brand" href="#">Dashboard</a>
            </nav>

            <!-- Sidebar -->
            <div class="offcanvas offcanvas-start bg-light" tabindex="-1" id="sidebarMenu">
                <div class="offcanvas-header">
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
                </div>
                <div class="offcanvas-body">
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link" href="dashboard.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="edit.php">Edit Profile</a></li>
                        <li class="nav-item"><a class="nav-link" href="view_remaining_system.php">View Remaining Session</a></li>
                        <li class="nav-item"><a class="nav-link" href="history.php">History</a></li>
                        <li class="nav-item"><a class="nav-link" href="reservation.php">Reservation</a></li>
                        <li class="nav-item"><a class="nav-link" href="login.php" onclick="return confirm('Are you sure you want to log out?');">Logout</a></li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <main role="main" class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <?php
                session_start();
                include "connect.php";

                if (isset($_SESSION['username'])) {
                    $username = $_SESSION['username'];
                    $sql = "SELECT * FROM user WHERE username='$username'";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $Firstname = htmlspecialchars($row['Firstname']);
                        echo "<h1 class='mt-4'>Welcome $Firstname to Sit-in Monitoring System</h1>";
                    } else {
                        echo "<h1 class='mt-4'>Welcome to Sit-in Monitoring System</h1>";
                    }
                } else {
                    echo "<h1 class='mt-4'>Welcome to Sit-in Monitoring System</h1>";
                }
                ?>
                <div class="row"></div>
                    <!-- Profile -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-primary text-white">Profile</div>
                            <div class="card-body text-center">
                                <?php
                                include "connect.php";

                                if (!isset($_SESSION['username'])) {
                                    echo "<p>Please <a href='login.php'>log in</a> to view your profile.</p>";
                                    exit();
                                }

                                $username = $_SESSION['username'];
                                $sql = "SELECT * FROM user WHERE username='$username'";
                                $result = $conn->query($sql);
                                $profilepic = "uploads/default.png"; // Default profile picture

                                if (!empty($row['profilepic']) && file_exists("uploads/" . $row['profilepic'])) {
                                    $profilepic = "uploads/" . htmlspecialchars($row['profilepic']);
                                }

                                echo "<img src='$profilepic' class='rounded-circle mb-3' width='100' height='100' alt='Profile Picture'>";

                                if ($result->num_rows > 0) {
                                    $row = $result->fetch_assoc();
                                    $fullname = htmlspecialchars($row['Firstname']) . " " . htmlspecialchars($row['Midname']) . " " . htmlspecialchars($row['Lastname']);

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
                                <ol>
                                    <li>Maintain silence, proper decorum, and discipline inside the laboratory. Mobile phones, walkmans, and other personal equipment must be switched off.</li>
                                    <li>Games are not allowed inside the lab. This includes computer-related games, card games, and other games that may disturb the operation of the lab.</li>
                                    <li>Surfing the Internet is allowed only with the permission of the instructor. Downloading and installing software are strictly prohibited.</li>
                                    <li>Getting access to other websites not related to the course (especially pornographic and illicit sites) is strictly prohibited.</li>
                                    <li>Deleting computer files and changing the set-up of the computer is a major offense.</li>
                                    <li>Observe computer time usage carefully. A fifteen-minute allowance is given for each use. Otherwise, the unit will be given to those who wish to "sit-in".</li>
                                    <li>Observe proper decorum while inside the laboratory.
                                        <ul>
                                            <li>Do not get inside the lab unless the instructor is present.</li>
                                            <li>All bags, knapsacks, and the like must be deposited at the counter.</li>
                                            <li>Follow the seating arrangement of your instructor.</li>
                                            <li>At the end of class, all software programs must be closed.</li>
                                            <li>Return all chairs to their proper places after use.</li>
                                        </ul>
                                    </li>
                                    <li>Chewing gum, eating, drinking, smoking, and other forms of vandalism are prohibited inside the lab.</li>
                                    <li>Anyone causing a continual disturbance will be asked to leave the lab. Acts or gestures offensive to the community, including public display of physical intimacy, are not tolerated.</li>
                                    <li>Persons exhibiting hostile or threatening behavior such as yelling, swearing, or disregarding requests made by lab personnel will be asked to leave the lab.</li>
                                    <li>For serious offenses, the lab personnel may call the Civil Security Office (CSU) for assistance.</li>
                                    <li>Any technical problem or difficulty must be addressed to the laboratory supervisor, student assistant, or instructor immediately.</li>
                                </ol>
                                <p><strong>DISCIPLINARY ACTION</strong></p>
                                <ul>
                                    <li><strong>First Offense:</strong> The Head, Dean, or OIC recommends suspension from classes to the Guidance Center.</li>
                                    <li><strong>Second and Subsequent Offenses:</strong> A recommendation for a heavier sanction will be endorsed to the Guidance Center.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
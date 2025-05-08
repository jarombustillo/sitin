<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .announcement-box, .rules-box {
            max-height: 250px;
            overflow-y: auto;
        }
        .card {
            border: none;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease-in-out;
            margin-bottom: 20px;
            height: 100%; /* Make cards equal height */
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            background-color: #007bff;
            color: white;
            border-bottom: none;
        }
        .page-title {
            text-align: center;
            margin: 20px 0;
        }
        .profile-pic img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #007bff;
        }
        .card-body {
            padding: 20px;
            background-color: #fff;
        }
        .dashboard-container {
            padding: 0 15px;
        }
        .dashboard-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -15px;
        }
        .dashboard-col {
            flex: 0 0 33.333333%;
            max-width: 33.333333%;
            padding: 0 15px;
            margin-bottom: 20px;
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
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .dashboard-col {
                flex: 0 0 50%;
                max-width: 50%;
            }
        }
        @media (max-width: 768px) {
            .dashboard-col {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
        
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">Student Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="edit.php">Edit Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="history.php">History</a></li>
                    <li class="nav-item"><a class="nav-link" href="reservation.php">Reservation</a></li>
                    <li class="nav-item"><a class="nav-link" href="view_lab_resources.php">Lab Resources</a></li>
                </ul>
                <?php
                session_start();
                include "connect.php";
                require_once 'includes/notifications.php';
                $user_id = isset($_SESSION['IDNO']) ? $_SESSION['IDNO'] : null;
                $notification_count = $user_id ? getNotificationCount($conn, $user_id, 'user') : 0;
                ?>
                <div class="nav-item dropdown ms-auto">
                    <a class="nav-link dropdown-toggle text-white" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <?php if ($notification_count > 0): ?>
                            <span class="badge bg-danger"><?php echo $notification_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                        <?php
                        if ($user_id) {
                            $notifications = getUnreadNotifications($conn, $user_id, 'user');
                            if ($notifications->num_rows > 0):
                                while ($notification = $notifications->fetch_assoc()):
                                    $msg = htmlspecialchars($notification['MESSAGE']);
                                    // Add icon/label based on message content
                                    if (strpos($msg, 'reservation') !== false) {
                                        $icon = '<i class="fas fa-calendar-check text-success"></i> ';
                                    } elseif (strpos($msg, 'announcement') !== false) {
                                        $icon = '<i class="fas fa-bullhorn text-info"></i> ';
                                    } else {
                                        $icon = '<i class="fas fa-info-circle text-primary"></i> ';
                                    }
                        ?>
                            <li>
                                <a class="dropdown-item" href="#" onclick="handleNotification(<?php echo $notification['ID']; ?>, '<?php echo htmlspecialchars($notification['MESSAGE']); ?>', event)">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <p class="mb-0"><?php echo $icon . $msg; ?></p>
                                            <small class="text-muted">
                                                <?php
                                                if (isset($notification['CREATED_AT']) && strtotime($notification['CREATED_AT']) > 0) {
                                                    echo date('M d, Y H:i', strtotime($notification['CREATED_AT']));
                                                } else {
                                                    echo '';
                                                }
                                                ?>
                                            </small>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        <?php
                                endwhile;
                            else:
                        ?>
                            <li><a class="dropdown-item" href="#">No new notifications</a></li>
                        <?php
                            endif;
                        } else {
                        ?>
                            <li><a class="dropdown-item" href="#">Please log in to see notifications</a></li>
                        <?php } ?>
            </div>
                <a href="login.php?logout=true" class="logout-btn ms-2">Log out</a>
            </div>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <!-- Main Content -->
        <main role="main" class="px-md-4">
            <?php
            include "connect.php";

            if (isset($_SESSION['username'])) {
                $username = $_SESSION['username'];
                $sql = "SELECT * FROM user WHERE username='$username'";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $Firstname = htmlspecialchars($row['Firstname']);
                    echo "<h1 class='page-title'>Welcome $Firstname to Sit-in Monitoring System</h1>";
                } else {
                    echo "<h1 class='page-title'>Welcome to Sit-in Monitoring System</h1>";
                }
            } else {
                echo "<h1 class='page-title'>Welcome to Sit-in Monitoring System</h1>";
            }
            ?>
            
            <div class="dashboard-container">
                <div class="dashboard-row">
                    <!-- Profile -->
                    <div class="dashboard-col">
                        <div class="card">
                            <div class="card-header">Profile</div>
                            <div class="card-body">
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

                                echo "<div class='text-center mb-3'><img src='$profilepic' class='rounded-circle border shadow' width='110' height='110' alt='Profile Picture'></div>";

                                if ($result->num_rows > 0) {
                                    $row = $result->fetch_assoc();
                                    $fullname = htmlspecialchars($row['Firstname']) . " " . htmlspecialchars($row['Midname']) . " " . htmlspecialchars($row['Lastname']);

                                    echo "<div class='profile-info'>";
                                    echo "<p><strong>Name:</strong> $fullname</p>";
                                    echo "<p><strong>ID Number:</strong> " . htmlspecialchars($row['IDNO']) . "</p>";
                                    echo "<p><strong>Course:</strong> " . htmlspecialchars($row['course']) . "</p>";
                                    echo "<p><strong>Year Level:</strong> " . htmlspecialchars($row['year_level']) . "</p>";
                                    echo "<p><strong>Remaining Sessions:</strong> " . htmlspecialchars($row['session_count']) . "</p>";
                                    echo "</div>";
                                } else {
                                    echo "<script>alert('No user data found');</script>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Announcements -->
                    <div class="dashboard-col">
                        <div class="card">
                            <div class="card-header" style="background-color: #17a2b8;">Announcements</div>
                            <div class="card-body announcement-box" style="background-color: #f0f8ff;">
                                <?php
                                // Fetch announcements from database
                                $announcement_sql = "SELECT * FROM announcement ORDER BY CREATED_AT DESC";
                                $announcement_result = $conn->query($announcement_sql);

                                if ($announcement_result && $announcement_result->num_rows > 0) {
                                    while ($announcement = $announcement_result->fetch_assoc()) {
                                        echo "<div class='announcement-item mb-3'>";
                                        echo "<h6 class='mb-2'>" . htmlspecialchars($announcement['TITLE']) . "</h6>";
                                        echo "<p class='mb-1'>" . htmlspecialchars($announcement['CONTENT']) . "</p>";
                                        echo "<small class='text-muted'>Posted on: " . date("Y-m-d H:i:s", strtotime($announcement['CREATED_AT'])) . "</small>";
                                        echo "</div>";
                                        echo "<hr>";
                                    }
                                } else {
                                    echo "<p class='text-center'>No announcements available.</p>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Rules & Regulations -->
                    <div class="dashboard-col">
                        <div class="card">
                            <div class="card-header" style="background-color: #ffc107; color: #212529;">Rules & Regulations</div>
                            <div class="card-body rules-box" style="background-color: #fff8dc;">
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
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function handleNotification(notificationId, message, event) {
            // Prevent default link behavior
            event.preventDefault();
            
            // Mark as read in the background
            fetch('mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'notification_id=' + notificationId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the notification count without reloading
                    const badge = document.querySelector('.badge');
                    if (badge) {
                        const currentCount = parseInt(badge.textContent);
                        if (currentCount > 0) {
                            badge.textContent = currentCount - 1;
                            if (currentCount - 1 === 0) {
                                badge.style.display = 'none';
                            }
                        }
                    }
                    
                    // Handle different types of notifications
                    if (message.includes('sit-in')) {
                        // Redirect to history page
                        window.location.href = 'history.php';
                    } else if (message.includes('reservation')) {
                        // Redirect to reservation page
                        window.location.href = 'reservation.php';
                    } else if (message.includes('announcement')) {
                        // Scroll to announcements section
                        document.querySelector('.announcement-box').scrollIntoView({ behavior: 'smooth' });
                    } else if (message.includes('points')) {
                        // Redirect to history page to show points
                        window.location.href = 'history.php';
                    }
                }
            });
        }
    </script>
</body>
</html>
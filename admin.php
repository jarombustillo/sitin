<?php
session_start();
include "connect.php"; // Database connection

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

// Fetch statistics
$sql_stats = "SELECT COUNT(*) AS registered_students FROM user";
$result_stats = $conn->query($sql_stats);
$row_stats = $result_stats->fetch_assoc();
$registered_students = $row_stats['registered_students'];

// Fetch currently sitting in students
$sql_current_sitin = "SELECT COUNT(*) AS current_sitin FROM sitin_records WHERE TIME_OUT IS NULL";
$result_current_sitin = $conn->query($sql_current_sitin);
$row_current_sitin = $result_current_sitin->fetch_assoc();
$current_sitin = $row_current_sitin['current_sitin'];

// Fetch total sit-in count
$sql_total_sitin = "SELECT COUNT(*) AS total_sitin FROM sitin_records";
$result_total_sitin = $conn->query($sql_total_sitin);
$row_total_sitin = $result_total_sitin->fetch_assoc();
$total_sitin = $row_total_sitin['total_sitin'];

// Fetch announcements
$sql_announcements = "SELECT * FROM announcement ORDER BY created_at DESC";
$result_announcements = $conn->query($sql_announcements);

// Handle new announcements
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['announcement'])) {
    $announcement = $conn->real_escape_string($_POST['announcement']);
    $date_posted = date("Y-m-d");
    $conn->query("INSERT INTO announcement (CONTENT, CREATED_AT) VALUES ('$announcement', '$CREATED_AT')");
    header("Location: admin.php"); // Refresh page
    exit();
}

// Handle sit-in form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sit_in_submit'])) {
    $id_number = $conn->real_escape_string($_POST['id_number']);
    $student_name = $conn->real_escape_string($_POST['student_name']);
    $purpose = $conn->real_escape_string($_POST['purpose']);
    $pc = $conn->real_escape_string($_POST['pc']);
    $remaining_session = $conn->real_escape_string($_POST['remaining_session']);
    $checkin_time = date("Y-m-d H:i:s");
    
    $sql_sitin = "INSERT INTO sitin_records (ID, IDNO, PURPOSE, LABORATORY, TIME_IN, TIME_OUT) 
                 VALUES ('$id_number', '$student_name', '$purpose', '$pc', '$remaining_session', '$checkin_time')";
    
    if ($conn->query($sql_sitin) === TRUE) {
        // Success
        header("Location: admin.php?sitin_success=true");
        exit();
    } else {
        // Error
        $error_message = "Error: " . $conn->error;
    }
}

// Handle student search
$search_result = null;
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = $conn->real_escape_string($_GET['search']);
    $sql_search = "SELECT * FROM user WHERE IDNO LIKE '%$search_term%' OR CONCAT(Firstname, 'Midname' , Lastname) LIKE '%$search_term%'";
    $search_result = $conn->query($sql_search);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Basic dashboard styling - Customize as needed */
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }

        header {
            background-color: #212529;
            color: #ffffff;
            padding: 10px;
            align-items: center;
        }

        .offcanvas {
            width: 250px;
        }

        nav a {
            color: #fff;
            text-decoration: none;
            margin: 0 10px;
        }

        .main {
            padding: 20px;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            margin-left: 250px;
            grid-gap: 20px;
        }

        section {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        h3 {
            margin-top: 0;
        }

        .stats-section {
            text-align: center;
        }

        .stats-section p {
            font-size: 1.2rem;
            font-weight: bold;
        }

        textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            resize: vertical;
        }

        button {
            background-color: #333;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }

        .announcement-item {
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .announcement-item strong {
            display: block;
            margin-bottom: 5px;
        }
        
        .modal-dialog {
            max-width: 500px;
        }
        
        .chart-container {
            width: 100%;
            height: 300px;
        }
    </style>
</head>

<body>
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
                <form class="d-flex" action="admin.php" method="GET">
                    <input class="form-control me-2" type="search" name="search" placeholder="Search Students" aria-label="Search">
                    <input type="hidden" name="show_search_modal" value="true">
                    <button class="btn btn-outline-light" type="submit">
                        Search
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <main class="container mt-4">
        <?php if(isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if(isset($_GET['sitin_success']) && $_GET['sitin_success'] == 'true'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Student sit-in recorded successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Search Results Modal -->
        <?php if(isset($_GET['search']) && $search_result && $search_result->num_rows > 1): ?>
        <div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="searchModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="searchModalLabel">Search Results</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="list-group">
                            <?php while($row = $search_result->fetch_assoc()): ?>
                            <a href="#" class="list-group-item list-group-item-action student-select" 
                                data-id="<?php echo $row['IDNO']; ?>"
                                data-name="<?php echo $row['firstname'] . ' ' . $row['lastname']; ?>">
                                <?php echo $row['IDNO']; ?> - <?php echo $row['firstname'] . ' ' . $row['lastname']; ?>
                            </a>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Sit-in Form Modal -->
        <div class="modal fade" id="sitInModal" tabindex="-1" aria-labelledby="sitInModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="sitInModalLabel">Sit In Form</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="admin.php">
                            <div class="mb-3">
                                <label for="id_number" class="form-label">ID Number</label>
                                <input type="text" class="form-control" id="id_number" name="id_number" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="student_name" class="form-label">Student Name</label>
                                <input type="text" class="form-control" id="student_name" name="student_name" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="purpose" class="form-label">Purpose</label>
                                <select class="form-select" id="purpose" name="purpose" required>
                                    <option value="Programming">Programming</option>
                                    <option value="Research">Research</option>
                                    <option value="Assignments">Assignments</option>
                                    <option value="Project">Project</option>
                                    <option value="Others">Others</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="pc" class="form-label">PC</label>
                                <input type="text" class="form-control" id="pc" name="pc" required>
                            </div>
                            <div class="mb-3">
                                <label for="remaining_session" class="form-label">Remaining Session</label>
                                <input type="number" class="form-control" id="remaining_session" name="remaining_session" required>
                            </div>
                            <div class="text-end">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" name="sit_in_submit">Sit In</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <section class="stats-section">
                    <h3>Statistics</h3>
                    <p>Students Registered: <?php echo $registered_students; ?></p>
                    <p>Currently Sit-In: <?php echo $current_sitin; ?></p>
                    <p>Total Sit-In: <?php echo $total_sitin; ?></p>
                </section>
            </div>
            
            <div class="col-md-8">
                <section>
                    <h3>Sit-In Distribution</h3>
                    <div class="chart-container">
                        <!-- Insert your chart here - using a canvas element for example -->
                        <canvas id="sitInChart"></canvas>
                    </div>
                </section>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <section>
                    <h3>Post Announcement</h3>
                    <form method="POST">
                        <textarea name="announcement" class="form-control" rows="3" required></textarea><br>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </section>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <section>
                    <h3>Posted Announcements</h3>
                    <?php if($result_announcements->num_rows > 0): ?>
                        <?php while ($row = $result_announcements->fetch_assoc()): ?>
                            <div class="announcement-item">
                                <strong><?php echo $row['CREATED_AT']; ?></strong>
                                <p><?php echo $row['CONTENT']; ?></p>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No announcements posted yet.</p>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Add this at the beginning of your script
        if (window.performance && window.performance.navigation.type === window.performance.navigation.TYPE_RELOAD) {
            // Remove any showing modals on page refresh
            let openModals = document.querySelectorAll('.modal.show');
            openModals.forEach(modal => {
                let modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            });
            
            // Remove search parameters from URL on refresh
            if (window.location.search.includes('search=')) {
                let url = new URL(window.location.href);
                url.searchParams.delete('search');
                url.searchParams.delete('show_search_modal');
                window.history.replaceState({}, document.title, url.toString());
            }
        }
        
        // Show appropriate modal based on search results
        document.addEventListener('DOMContentLoaded', function() {
            <?php if(isset($_GET['show_search_modal']) && $_GET['show_search_modal'] == 'true' && isset($_GET['search']) && $search_result): ?>
                <?php if($search_result->num_rows == 1): // If exactly one result, show sit-in form directly ?>
                    <?php $row = $search_result->fetch_assoc(); ?>
                    document.getElementById('id_number').value = '<?php echo $row['IDNO']; ?>';                    
                    document.getElementById('student_name').value = '<?php echo $row['Firstname'] . ' ' .$row['Midname'] . ' '. $row['Lastname']; ?>';
                    var sitInModal = new bootstrap.Modal(document.getElementById('sitInModal'));
                    sitInModal.show();
                <?php elseif($search_result->num_rows > 1): // If multiple results, show search modal ?>
                    var searchModal = new bootstrap.Modal(document.getElementById('searchModal'));
                    searchModal.show();
                <?php endif; ?>
            <?php endif; ?>
    
            // Handle student selection
            const studentLinks = document.querySelectorAll('.student-select');
            studentLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    
                    document.getElementById('id_number').value = id;
                    document.getElementById('student_name').value = name;
                    
                    var searchModal = bootstrap.Modal.getInstance(document.getElementById('searchModal'));
                    searchModal.hide();
                    
                    var sitInModal = new bootstrap.Modal(document.getElementById('sitInModal'));
                    sitInModal.show();
                });
            });

            
            // Initialize chart if chart.js is loaded
            if (typeof Chart !== 'undefined') {
                const ctx = document.getElementById('sitInChart');
                if (ctx) {
                    new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: ['Programming', 'Research', 'Assignments', 'Project', 'Others'],
                            datasets: [{
                                data: [30, 15, 25, 20, 10], // Replace with actual data
                                backgroundColor: [
                                    '#007bff',
                                    '#dc3545',
                                    '#ffc107',
                                    '#fd7e14',
                                    '#20c997'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false
                        }
                    });
                }
            }
        });
    </script>
</body>
</html>

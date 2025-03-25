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
$sql_announcements = "SELECT * FROM announcement ORDER BY CREATED_AT DESC";
$result_announcements = $conn->query($sql_announcements);

// Fetch student counts by course
$sql_course_counts = "SELECT course, COUNT(*) as count FROM user GROUP BY course";
$result_course_counts = $conn->query($sql_course_counts);
$course_counts = [];
if ($result_course_counts->num_rows > 0) {
    while ($row = $result_course_counts->fetch_assoc()) {
        $course_counts[$row['course']] = $row['count'];
    }
}

// Fetch laboratory distribution
$sql_lab_counts = "SELECT LABORATORY, COUNT(*) as count FROM sitin_records WHERE TIME_OUT IS NULL GROUP BY LABORATORY";
$result_lab_counts = $conn->query($sql_lab_counts);
$lab_counts = [];
if ($result_lab_counts->num_rows > 0) {
    while ($row = $result_lab_counts->fetch_assoc()) {
        $lab_counts[$row['LABORATORY']] = $row['count'];
    }
}

// Prepare data for the charts
$chart_labels = json_encode(array_keys($course_counts));
$chart_data = json_encode(array_values($course_counts));
$lab_labels = json_encode(array_keys($lab_counts));
$lab_data = json_encode(array_values($lab_counts));

// Handle new announcements
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['announcement'])) {
    $content = $conn->real_escape_string($_POST['announcement']);
    $title = $conn->real_escape_string($_POST['announcement_title']);
    $date_posted = date("Y-m-d H:i:s");
    $conn->query("INSERT INTO announcement (TITLE, CONTENT, CREATED_AT) VALUES ('$title', '$content', '$date_posted')");
    header("Location: admin.php"); // Refresh page
    exit();
}

// Handle sit-in form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sit_in_submit'])) {
    $id_number = $conn->real_escape_string($_POST['id_number']);
    $student_name = $conn->real_escape_string($_POST['student_name']);
    $purpose = $conn->real_escape_string($_POST['purpose']);
    $laboratory = $conn->real_escape_string($_POST['laboratory']);
    $checkin_time = date("Y-m-d H:i:s");
    
    // Check if student is already sitting in
    $check_sql = "SELECT * FROM sitin_records WHERE IDNO = '$id_number' AND TIME_OUT IS NULL";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        $error_message = "Error: Student is already sitting in.";
    } else {
        // Get the next available ID
        $get_next_id = "SELECT MAX(ID) as max_id FROM sitin_records";
        $result = $conn->query($get_next_id);
        $row = $result->fetch_assoc();
        $next_id = ($row['max_id'] ?? 0) + 1;
        
        // Insert sit-in record without deducting session
        $sql_sitin = "INSERT INTO sitin_records (ID, IDNO, PURPOSE, LABORATORY, TIME_IN) 
                     VALUES ('$next_id', '$id_number', '$purpose', '$laboratory', '$checkin_time')";
        
        if ($conn->query($sql_sitin) === TRUE) {
            header("Location: admin.php?sitin_success=true");
            exit();
        } else {
            $error_message = "Error: " . $conn->error;
        }
    }
}

// Handle sign-out
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sign_out'])) {
    $id_number = $conn->real_escape_string($_POST['id_number']);
    $checkout_time = date("Y-m-d H:i:s");
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update sit-in record with checkout time
        $update_sql = "UPDATE sitin_records SET TIME_OUT = '$checkout_time' 
                      WHERE IDNO = '$id_number' AND TIME_OUT IS NULL";
        
        if (!$conn->query($update_sql)) {
            throw new Exception("Error updating sit-in record: " . $conn->error);
        }
        
        // Deduct session count only on sign-out
        $update_session = "UPDATE user SET session_count = session_count - 1 WHERE IDNO = '$id_number'";
        if (!$conn->query($update_session)) {
            throw new Exception("Error updating session count: " . $conn->error);
        }
        
        // Commit transaction
        $conn->commit();
        
        header("Location: admin.php?signout_success=true");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error_message = "Error: " . $e->getMessage();
    }
}

// Handle student search with improved error handling
$search_result = null;
$search_error = null;
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = $conn->real_escape_string($_GET['search']);
    
    // Validate search term
    if (strlen($search_term) < 2) {
        $search_error = "Search term must be at least 2 characters long.";
    } else {
        // Check if searching by IDNO
        if (is_numeric($search_term)) {
            $sql_search = "SELECT * FROM user WHERE IDNO = '$search_term'";
        } else {
            // Search by name
            $sql_search = "SELECT * FROM user WHERE CONCAT(Firstname, ' ', Lastname) LIKE '%$search_term%'";
        }
        
        $search_result = $conn->query($sql_search);
        
        if (!$search_result) {
            $search_error = "Database error: " . $conn->error;
        } elseif ($search_result->num_rows === 0) {
            $search_error = "No students found matching your search.";
        } elseif ($search_result->num_rows === 1) {
            // If exactly one result, get the student data
            $student = $search_result->fetch_assoc();
            $student_data = [
                'id' => $student['IDNO'],
                'name' => $student['Firstname'] . ' ' . $student['Lastname'],
                'course' => $student['course'],
                'year' => $student['year_level'],
                'session_count' => $student['session_count']
            ];
        } else {
            $search_error = "Multiple results found. Please be more specific.";
        }
    }
}

// Handle announcement deletion
if (isset($_GET['delete_announcement'])) {
    $id = $conn->real_escape_string($_GET['delete_announcement']);
    $conn->query("DELETE FROM announcement WHERE ID = '$id'");
    header("Location: admin.php");
    exit();
}

// Handle announcement update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_announcement'])) {
    $id = $conn->real_escape_string($_POST['announcement_id']);
    $content = $conn->real_escape_string($_POST['announcement_content']);
    $title = $conn->real_escape_string($_POST['announcement_title']);
    $conn->query("UPDATE announcement SET TITLE = '$title', CONTENT = '$content' WHERE ID = '$id'");
    header("Location: admin.php");
    exit();
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
                    <li class="nav-item"><a class="nav-link" href="sitinrecords.php">Sit-in Records</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Reports</a></li>
                </ul>
                <form class="d-flex" action="admin.php" method="GET">
                    <input class="form-control me-2" type="search" name="search" placeholder="Search by ID or Name" aria-label="Search" 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button class="btn btn-outline-light" type="submit">Search</button>
                </form>
                <a href="login.php?logout=true" class="logout-btn ms-auto">Log out</a>
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
        
        <?php if(isset($search_error)): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <?php echo $search_error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if(isset($_GET['sitin_success']) && $_GET['sitin_success'] == 'true'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Student sit-in recorded successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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
                                <label for="student_details" class="form-label">Course & Year</label>
                                <input type="text" class="form-control" id="student_details" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="purpose" class="form-label">Purpose</label>
                                <select class="form-select" id="purpose" name="purpose" required>
                                    <option value="" disabled selected>Select Purpose</option>
                                    <option value="C#">C#</option>
                                    <option value="C">C</option>
                                    <option value="Java">Java</option>
                                    <option value="Python">Python</option>
                                    <option value="PHP">PHP</option>
                                    <option value="ASP.Net">ASP.Net</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="laboratory" class="form-label">Laboratory</label>
                                <select class="form-select" id="laboratory" name="laboratory" required>
                                    <option value="" disabled selected>Select Laboratory</option>
                                    <option value="524">524</option>
                                    <option value="526">526</option>
                                    <option value="530">530</option>
                                    <option value="542">542</option>
                                    <option value="544">544</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="remaining_session" class="form-label">Remaining Session</label>
                                <input type="number" class="form-control" id="remaining_session" name="remaining_session" value="<?php echo isset($student_data) ? $student_data['session_count'] : ''; ?>" readonly>
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
                    <div class="row">
                        <div class="col-md-6">
                            <div class="chart-container">
                                <canvas id="courseChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-container">
                                <canvas id="labChart"></canvas>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <section>
                    <h3>Post Announcement</h3>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="announcement_title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="announcement_title" name="announcement_title" required>
                        </div>
                        <div class="mb-3">
                            <label for="announcement" class="form-label">Content</label>
                            <textarea name="announcement" class="form-control" rows="3" placeholder="Enter new announcement..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Post Announcement</button>
                    </form>
                    <div class="mt-4">
                        <?php
                        if ($result_announcements->num_rows > 0) {
                            while ($announcement = $result_announcements->fetch_assoc()) {
                                echo "<div class='announcement-item'>";
                                echo "<div class='d-flex justify-content-between align-items-start'>";
                                echo "<div>";
                                echo "<strong>" . htmlspecialchars($announcement['TITLE']) . "</strong>";
                                echo "<p>" . htmlspecialchars($announcement['CONTENT']) . "</p>";
                                echo "<small class='text-muted d-block'>Posted on: " . date("Y-m-d H:i:s", strtotime($announcement['CREATED_AT'])) . "</small>";
                                echo "</div>";
                                echo "<div class='btn-group'>";
                                echo "<button type='button' class='btn btn-sm btn-warning' data-bs-toggle='modal' data-bs-target='#editModal" . $announcement['ID'] . "'>Edit</button>";
                                echo "<a href='admin.php?delete_announcement=" . $announcement['ID'] . "' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure you want to delete this announcement?\")'>Delete</a>";
                                echo "</div>";
                                echo "</div>";
                                echo "</div>";

                                // Edit Modal for each announcement
                                echo "<div class='modal fade' id='editModal" . $announcement['ID'] . "' tabindex='-1' aria-labelledby='editModalLabel" . $announcement['ID'] . "' aria-hidden='true'>";
                                echo "<div class='modal-dialog'>";
                                echo "<div class='modal-content'>";
                                echo "<div class='modal-header'>";
                                echo "<h5 class='modal-title' id='editModalLabel" . $announcement['ID'] . "'>Edit Announcement</h5>";
                                echo "<button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>";
                                echo "</div>";
                                echo "<form method='POST' action=''>";
                                echo "<div class='modal-body'>";
                                echo "<input type='hidden' name='announcement_id' value='" . $announcement['ID'] . "'>";
                                echo "<div class='mb-3'>";
                                echo "<label for='edit_title" . $announcement['ID'] . "' class='form-label'>Title</label>";
                                echo "<input type='text' class='form-control' id='edit_title" . $announcement['ID'] . "' name='announcement_title' value='" . htmlspecialchars($announcement['TITLE']) . "' required>";
                                echo "</div>";
                                echo "<div class='mb-3'>";
                                echo "<label for='edit_content" . $announcement['ID'] . "' class='form-label'>Content</label>";
                                echo "<textarea name='announcement_content' class='form-control' rows='4' required>" . htmlspecialchars($announcement['CONTENT']) . "</textarea>";
                                echo "</div>";
                                echo "</div>";
                                echo "<div class='modal-footer'>";
                                echo "<button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button>";
                                echo "<button type='submit' name='update_announcement' class='btn btn-primary'>Update</button>";
                                echo "</div>";
                                echo "</form>";
                                echo "</div>";
                                echo "</div>";
                                echo "</div>";
                            }
                        } else {
                            echo "<p>No announcements available.</p>";
                        }
                        ?>
                    </div>
                </section>
            </div>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Remove search parameters from URL on refresh
            if (window.performance && window.performance.navigation.type === window.performance.navigation.TYPE_RELOAD) {
                if (window.location.search.includes('search=')) {
                    let url = new URL(window.location.href);
                    url.searchParams.delete('search');
                    window.history.replaceState({}, document.title, url.toString());
                }
            }

            // Show sit-in modal if we have student data
            <?php if(isset($student_data)): ?>
            const studentData = <?php echo json_encode($student_data); ?>;
            
            // Fill the sit-in form
            document.getElementById('id_number').value = studentData.id;
            document.getElementById('student_name').value = studentData.name;
            document.getElementById('student_details').value = `${studentData.course} - Year ${studentData.year}`;
            document.getElementById('remaining_session').value = studentData.session_count;
            
            // Show the sit-in modal
            var sitInModal = new bootstrap.Modal(document.getElementById('sitInModal'));
            sitInModal.show();
            <?php endif; ?>

            // Initialize charts if chart.js is loaded
            if (typeof Chart !== 'undefined') {
                // Course Distribution Chart
                const courseCtx = document.getElementById('courseChart');
                if (courseCtx) {
                    new Chart(courseCtx, {
                        type: 'pie',
                        data: {
                            labels: <?php echo $chart_labels; ?>,
                            datasets: [{
                                data: <?php echo $chart_data; ?>,
                                backgroundColor: [
                                    'rgba(255, 99, 132, 0.8)',
                                    'rgba(54, 162, 235, 0.8)',
                                    'rgba(255, 206, 86, 0.8)',
                                    'rgba(75, 192, 192, 0.8)',
                                    'rgba(153, 102, 255, 0.8)'
                                ],
                                borderColor: [
                                    'rgba(255, 99, 132, 1)',
                                    'rgba(54, 162, 235, 1)',
                                    'rgba(255, 206, 86, 1)',
                                    'rgba(75, 192, 192, 1)',
                                    'rgba(153, 102, 255, 1)'
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            layout: {
                                padding: 10
                            }
                        }
                    });
                }

                // Laboratory Distribution Chart
                const labCtx = document.getElementById('labChart');
                if (labCtx) {
                    new Chart(labCtx, {
                        type: 'pie',
                        data: {
                            labels: <?php echo $lab_labels; ?>,
                            datasets: [{
                                data: <?php echo $lab_data; ?>,
                                backgroundColor: [
                                    'rgba(255, 99, 132, 0.8)',
                                    'rgba(54, 162, 235, 0.8)',
                                    'rgba(255, 206, 86, 0.8)',
                                    'rgba(75, 192, 192, 0.8)',
                                    'rgba(153, 102, 255, 0.8)'
                                ],
                                borderColor: [
                                    'rgba(255, 99, 132, 1)',
                                    'rgba(54, 162, 235, 1)',
                                    'rgba(255, 206, 86, 1)',
                                    'rgba(75, 192, 192, 1)',
                                    'rgba(153, 102, 255, 1)'
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            layout: {
                                padding: 10
                            }
                        }
                    });
                }
            }
        });
    </script>
</body>
</html>

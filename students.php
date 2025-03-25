<?php
session_start();
include 'connect.php';

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

// Fetch all users from the database with filtering and search
$sql_users = "SELECT IDNO, Firstname, Midname, Lastname, course, year_level, session_count FROM user WHERE 1=1"; // Base query

// Filtering variables initialization
$filter_course = "";
$filter_year = "";
$search_term = ""; // Initialize search term

// Check if filters are submitted
if (isset($_GET['filter_course']) && !empty($_GET['filter_course'])) {
    $filter_course = mysqli_real_escape_string($conn, $_GET['filter_course']);
    $sql_users .= " AND course = '$filter_course'";
}

if (isset($_GET['filter_year']) && !empty($_GET['filter_year'])) {
    $filter_year = mysqli_real_escape_string($conn, $_GET['filter_year']);
    $sql_users .= " AND year_level = '$filter_year'";
}

// Check if search term is submitted
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = mysqli_real_escape_string($conn, $_GET['search']);
    // Search by IDNO or name (Firstname, Midname, Lastname)
    $sql_users .= " AND (IDNO LIKE '%$search_term%' OR Firstname LIKE '%$search_term%' OR Midname LIKE '%$search_term%' OR Lastname LIKE '%$search_term%')";
}

$result_users = $conn->query($sql_users);

$users = [];
if ($result_users->num_rows > 0) {
    while ($row = $result_users->fetch_assoc()) {
        $users[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students</title>
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

        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .user-table th,
        .user-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .user-table th {
            background-color: #f2f2f2;
        }

        /* Profile picture styles */
        .profile-pic-container {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto;
        }

        .profile-pic {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Navigation styles */
        .navbar {
            padding: 0.5rem 1rem;
        }

        .navbar-nav {
            margin-right: auto;
        }

        .nav-item {
            margin-right: 1rem;
        }

        .nav-link {
            color: rgba(255,255,255,.8) !important;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: #fff !important;
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
        <a class="navbar-brand" href="admin.php">College of Computer Studies Admin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="admin.php">Home</a></li>
                <li class="nav-item"><a class="nav-link active" href="students.php">Students</a></li>
                <li class="nav-item"><a class="nav-link" href="current_sitin.php">Sit-In</a></li>
                <li class="nav-item"><a class="nav-link" href="sitinrecords.php">Sit-in Records</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Reports</a></li>
            </ul>
            <a href="login.php?logout=true" class="logout-btn">Log out</a>
        </div>
    </div>
</nav>
<body class="bg-light">
    <div class="container mt-4">
        <h2>Student List</h2>
        
        <!-- Search and Filter Section -->
        <div class="row mb-4">
            <div class="col-md-6">
                <form method="GET" class="d-flex gap-2">
                    <input type="text" name="search" class="form-control" placeholder="Search by ID/Name" value="<?php echo htmlspecialchars($search_term); ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="students.php" class="btn btn-secondary">Clear</a>
                </form>
            </div>
            <div class="col-md-6">
                <form method="GET" class="d-flex gap-2 justify-content-end">
                    <select name="filter_course" class="form-select" style="width: auto;">
                        <option value="">All Courses</option>
                        <option value="BSIT" <?php echo ($filter_course == 'BSIT') ? 'selected' : ''; ?>>BSIT</option>
                        <option value="BSCS" <?php echo ($filter_course == 'BSCS') ? 'selected' : ''; ?>>BSCS</option>
                        <option value="BSCpE" <?php echo ($filter_course == 'BSCpE') ? 'selected' : ''; ?>>BSCpE</option>
                    </select>
                    <select name="filter_year" class="form-select" style="width: auto;">
                        <option value="">All Year Levels</option>
                        <option value="1" <?php echo ($filter_year == '1') ? 'selected' : ''; ?>>1</option>
                        <option value="2" <?php echo ($filter_year == '2') ? 'selected' : ''; ?>>2</option>
                        <option value="3" <?php echo ($filter_year == '3') ? 'selected' : ''; ?>>3</option>
                        <option value="4" <?php echo ($filter_year == '4') ? 'selected' : ''; ?>>4</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="students.php" class="btn btn-secondary">Clear Filters</a>
                </form>
            </div>
        </div>

        <!-- Student Table -->
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID Number</th>
                    <th>Full Name</th>
                    <th>Course</th>
                    <th>Year Level</th>
                    <th>Remaining Sessions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($users) > 0) : ?>
                    <?php foreach ($users as $user) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['IDNO']); ?></td>
                            <td><?php echo htmlspecialchars($user['Lastname'] . ', ' . $user['Firstname'] . ' ' . $user['Midname']); ?></td>
                            <td><?php echo htmlspecialchars($user['course']); ?></td>
                            <td><?php echo htmlspecialchars($user['year_level']); ?></td>
                            <td><?php echo htmlspecialchars($user['session_count']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5" class="text-center">No students found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php
$conn->close();
?>

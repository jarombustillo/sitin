<?php
session_start();
require_once 'connect.php';

// Check if user is logged in
if (!isset($_SESSION['IDNO'])) {
    header("Location: login.php");
    exit();
}

// Get user information
$user_sql = "SELECT Firstname, Lastname, Midname, course, year_level FROM user WHERE IDNO = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("s", $_SESSION['IDNO']);
$user_stmt->execute();
$user_info = $user_stmt->get_result()->fetch_assoc();

// Get selected laboratory (default to all)
$selected_lab = isset($_GET['lab']) ? $_GET['lab'] : 'all';

// Get search query
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build the query
$sql = "SELECT * FROM lab_resources WHERE 1=1";
$params = [];
$types = "";

if ($selected_lab !== 'all') {
    $sql .= " AND CATEGORY = ?";
    $params[] = $selected_lab;
    $types .= "s";
}

if ($search) {
    $sql .= " AND (TITLE LIKE ? OR DESCRIPTION LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$sql .= " ORDER BY CATEGORY, TITLE";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resources = $stmt->get_result();

// Get unique laboratories for filter
$labs_sql = "SELECT DISTINCT CATEGORY FROM lab_resources ORDER BY CATEGORY";
$labs_result = $conn->query($labs_sql);
$laboratories = [];
while ($row = $labs_result->fetch_assoc()) {
    $laboratories[] = $row['CATEGORY'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Resources - Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
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

        .resource-card {
            margin-bottom: 20px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }

        .resource-card:hover {
            transform: translateY(-5px);
        }

        .resource-image {
            height: 200px;
            object-fit: cover;
        }

        .lab-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
        }

        .search-box {
            position: relative;
            margin-bottom: 20px;
        }

        .search-box input {
            padding-right: 40px;
        }

        .search-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .lab-filter {
            margin-bottom: 20px;
        }

        .lab-filter .btn {
            margin: 0 5px 10px 0;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">Student</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="edit.php">Edit Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="history.php">History</a></li>
                    <li class="nav-item"><a class="nav-link" href="reservation.php">Reservation</a></li>
                    <li class="nav-item"><a class="nav-link active" href="view_lab_resources.php">Lab Resources</a></li>
                </ul>
                <a href="login.php?logout=true" class="logout-btn ms-auto">Log out</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- User Information -->
        <div class="card mb-4">
            <div class="card-body">
                <h4>Welcome, <?php echo htmlspecialchars($user_info['Firstname'] . ' ' . $user_info['Lastname']); ?></h4>
                <p class="mb-1"><?php echo htmlspecialchars($user_info['course'] . ' - Year ' . $user_info['year_level']); ?></p>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="row mb-4">
            <div class="col-md-6">
                <form method="GET" class="search-box">
                    <input type="text" class="form-control" name="search" placeholder="Search resources..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <i class="fas fa-search"></i>
                </form>
            </div>
            <div class="col-md-6">
                <div class="lab-filter">
                    <a href="?lab=all<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                       class="btn <?php echo $selected_lab === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        All Labs
                    </a>
                    <?php foreach ($laboratories as $lab): ?>
                        <a href="?lab=<?php echo urlencode($lab); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="btn <?php echo $selected_lab === $lab ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            Lab <?php echo htmlspecialchars($lab); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Resources Grid -->
        <div class="row">
            <?php if ($resources->num_rows > 0): ?>
                <?php while ($resource = $resources->fetch_assoc()): ?>
                    <div class="col-md-4">
                        <div class="card resource-card">
                            <?php if ($resource['FILE_PATH']): ?>
                                <img src="uploads/resources/<?php echo htmlspecialchars($resource['FILE_PATH']); ?>" 
                                     class="card-img-top resource-image" alt="<?php echo htmlspecialchars($resource['TITLE']); ?>">
                            <?php endif; ?>
                            <span class="lab-badge"><?php echo htmlspecialchars($resource['CATEGORY']); ?></span>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($resource['TITLE']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($resource['DESCRIPTION']); ?></p>
                                <?php if ($resource['LINK']): ?>
                                    <p class="card-text">
                                        <small class="text-muted">
                                            <i class="fas fa-link"></i> <a href="<?php echo htmlspecialchars($resource['LINK']); ?>" target="_blank">Resource Link</a>
                                        </small>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        No resources found. <?php echo $search ? 'Try a different search term.' : ''; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
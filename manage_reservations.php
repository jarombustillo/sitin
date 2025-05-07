<?php
session_start();
require_once 'connect.php';

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// Handle reservation status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['reservation_id']) && isset($_POST['action'])) {
        $reservation_id = (int)$_POST['reservation_id'];
        $action = $_POST['action'];
        
        if ($action === 'approve' || $action === 'cancel') {
            $new_status = $action === 'approve' ? 'confirmed' : 'cancelled';
            
            $update_sql = "UPDATE reservations SET STATUS = ? WHERE ID = ?";
            $stmt = $conn->prepare($update_sql);
            
            if ($stmt->bind_param("si", $new_status, $reservation_id)) {
                if ($stmt->execute()) {
                    $message = "Reservation has been " . $new_status;
                } else {
                    $error = "Error updating reservation status.";
                }
            }
        }
    }
}

// Fetch all reservations with user details
$sql = "SELECT r.*, u.Firstname, u.Lastname, u.Midname, u.course, u.year_level 
        FROM reservations r 
        JOIN user u ON r.IDNO = u.IDNO 
        ORDER BY r.DATE, r.TIME_SLOT";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reservations - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: bold;
        }

        .status-pending {
            background-color: #ffc107;
            color: black;
        }

        .status-confirmed {
            background-color: #28a745;
            color: white;
        }

        .status-cancelled {
            background-color: #dc3545;
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-approve {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-cancel {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }

        .filter-section {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin.php">Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="students.php">Students</a></li>
                    <li class="nav-item"><a class="nav-link" href="current_sitin.php">Sit-In</a></li>
                    <li class="nav-item"><a class="nav-link" href="sitinrecords.php">Sit-in Records</a></li>
                    <li class="nav-item"><a class="nav-link active" href="manage_reservations.php">Reservations</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin/feedback.php">Feedback</a></li>
                    <li class="nav-item"><a class="nav-link" href="labresources.php">Lab Resources</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
                </ul>
                <a href="login.php?logout=true" class="btn btn-danger ms-auto">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Manage PC Reservations</h2>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo isset($_GET['status']) && $_GET['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo isset($_GET['status']) && $_GET['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="cancelled" <?php echo isset($_GET['status']) && $_GET['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" name="date" id="date" class="form-control" value="<?php echo isset($_GET['date']) ? $_GET['date'] : ''; ?>">
                </div>
                <div class="col-md-3">
                    <label for="laboratory" class="form-label">Laboratory</label>
                    <select name="laboratory" id="laboratory" class="form-select">
                        <option value="">All Laboratories</option>
                        <option value="Lab 1" <?php echo isset($_GET['laboratory']) && $_GET['laboratory'] === 'Lab 1' ? 'selected' : ''; ?>>Laboratory 1</option>
                        <option value="Lab 2" <?php echo isset($_GET['laboratory']) && $_GET['laboratory'] === 'Lab 2' ? 'selected' : ''; ?>>Laboratory 2</option>
                        <option value="Lab 3" <?php echo isset($_GET['laboratory']) && $_GET['laboratory'] === 'Lab 3' ? 'selected' : ''; ?>>Laboratory 3</option>
                        <option value="Lab 4" <?php echo isset($_GET['laboratory']) && $_GET['laboratory'] === 'Lab 4' ? 'selected' : ''; ?>>Laboratory 4</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block">Apply Filters</button>
                </div>
            </form>
        </div>

        <!-- Reservations Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student</th>
                        <th>Course & Year</th>
                        <th>Laboratory</th>
                        <th>PC Number</th>
                        <th>Date</th>
                        <th>Time Slot</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $fullname = htmlspecialchars($row['Lastname'] . ', ' . $row['Firstname'] . ' ' . $row['Midname']);
                            $course_year = htmlspecialchars($row['course'] . ' - Year ' . $row['year_level']);
                            ?>
                            <tr>
                                <td><?php echo $row['ID']; ?></td>
                                <td><?php echo $fullname; ?></td>
                                <td><?php echo $course_year; ?></td>
                                <td><?php echo htmlspecialchars($row['LABORATORY']); ?></td>
                                <td><?php echo htmlspecialchars($row['PC_NUMBER']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['DATE'])); ?></td>
                                <td><?php echo htmlspecialchars($row['TIME_SLOT']); ?></td>
                                <td><?php echo htmlspecialchars($row['PURPOSE']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($row['STATUS']); ?>">
                                        <?php echo ucfirst($row['STATUS']); ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <?php if ($row['STATUS'] === 'pending'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="reservation_id" value="<?php echo $row['ID']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn-approve">Approve</button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="reservation_id" value="<?php echo $row['ID']; ?>">
                                            <input type="hidden" name="action" value="cancel">
                                            <button type="submit" class="btn-cancel">Cancel</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo "<tr><td colspan='10' class='text-center'>No reservations found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
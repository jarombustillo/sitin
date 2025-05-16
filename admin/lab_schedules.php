<?php
session_start();
require_once '../connect.php';

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../login.php");
    exit();
}

$message = '';
$error = '';

// Handle schedule submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $room_number = $conn->real_escape_string($_POST['room_number']);
            $day_group = $conn->real_escape_string($_POST['day_group']);
            $time_slot = $conn->real_escape_string($_POST['time_slot']);
            $status = $conn->real_escape_string($_POST['status']);
            $notes = $conn->real_escape_string($_POST['notes']);

            // Check for duplicate schedule
            $check_sql = "SELECT * FROM labschedules WHERE ROOM_NUMBER = ? AND DAY_GROUP = ? AND TIME_SLOT = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("sss", $room_number, $day_group, $time_slot);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $error = "A schedule for this room, day, and time slot already exists!";
            } else {
                $sql = "INSERT INTO labschedules (ROOM_NUMBER, DAY_GROUP, TIME_SLOT, STATUS, NOTES) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssss", $room_number, $day_group, $time_slot, $status, $notes);

                if ($stmt->execute()) {
                    $message = "Schedule added successfully!";
                } else {
                    $error = "Error adding schedule: " . $conn->error;
                }
            }
        } elseif ($_POST['action'] == 'update') {
            $id = (int)$_POST['schedule_id'];
            $status = $conn->real_escape_string($_POST['status']);
            $notes = $conn->real_escape_string($_POST['notes']);

            $sql = "UPDATE labschedules SET STATUS = ?, NOTES = ? WHERE ID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $status, $notes, $id);

            if ($stmt->execute()) {
                $message = "Schedule updated successfully!";
            } else {
                $error = "Error updating schedule: " . $conn->error;
            }
        } elseif ($_POST['action'] == 'delete') {
            $id = (int)$_POST['schedule_id'];

            $sql = "DELETE FROM labschedules WHERE ID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $message = "Schedule deleted successfully!";
            } else {
                $error = "Error deleting schedule: " . $conn->error;
            }
        }
    }
}

// Fetch all schedules
$sql = "SELECT * FROM labschedules ORDER BY ROOM_NUMBER, DAY_GROUP, TIME_SLOT";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Lab Schedules - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .schedule-card {
            margin-bottom: 20px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .status-available {
            background-color: #d4edda;
            color: #155724;
        }
        .status-reserved {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-maintenance {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="../admin.php">Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="../students.php">Students</a></li>
                    <li class="nav-item"><a class="nav-link" href="../current_sitin.php">Sit-In</a></li>
                    <li class="nav-item"><a class="nav-link" href="../sitinrecords.php">Sit-in Records</a></li>
                    <li class="nav-item"><a class="nav-link" href="../manage_reservations.php">Reservations</a></li>
                    <li class="nav-item"><a class="nav-link" href="../pc_status.php">PC</a><li> 
                    <li class="nav-item"><a class="nav-link active" href="lab_schedules.php">Lab Schedules</a></li>
                    <li class="nav-item"><a class="nav-link" href="feedback.php">Feedback</a></li>
                    <li class="nav-item"><a class="nav-link" href="../labresources.php">Lab Resources</a></li>
                    <li class="nav-item"><a class="nav-link" href="../reports.php">Reports</a></li>
                    <li class="nav-item"><a class="nav-link" href="../reward.php">Leaderboard</a></li>
                </ul>
                <a href="../login.php?logout=true" class="btn btn-danger ms-auto">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Manage Lab Schedules</h2>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Add New Schedule Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Add New Schedule</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="room_number" class="form-label">Room Number</label>
                                <select class="form-select" id="room_number" name="room_number" required>
                                    <option value="524">Lab 524</option>
                                    <option value="526">Lab 526</option>
                                    <option value="528">Lab 528</option>
                                    <option value="530">Lab 530</option>
                                    <option value="542">Lab 542</option>
                                    <option value="544">Lab 544</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="day_group" class="form-label">Day</label>
                                <select class="form-select" id="day_group" name="day_group" required>
                                    <option value="Monday">Monday</option>
                                    <option value="Tuesday">Tuesday</option>
                                    <option value="Wednesday">Wednesday</option>
                                    <option value="Thursday">Thursday</option>
                                    <option value="Friday">Friday</option>
                                    <option value="Saturday">Saturday</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="time_slot" class="form-label">Time Slot</label>
                                <select class="form-select" id="time_slot" name="time_slot" required>
                                    <option value="">Select Time Slot</option>
                                    <option value="8:00AM-9:00AM">8:00AM - 9:00AM</option>
                                    <option value="9:00AM-10:00AM">9:00AM - 10:00AM</option>
                                    <option value="10:00AM-11:00AM">10:00AM - 11:00AM</option>
                                    <option value="11:00AM-12:00PM">11:00AM - 12:00PM</option>
                                    <option value="12:00PM-1:00PM">12:00PM - 1:00PM</option>
                                    <option value="1:00PM-2:00PM">1:00PM - 2:00PM</option>
                                    <option value="2:00PM-3:00PM">2:00PM - 3:00PM</option>
                                    <option value="3:00PM-4:00PM">3:00PM - 4:00PM</option>
                                    <option value="4:00PM-5:00PM">4:00PM - 5:00PM</option>
                                    <option value="5:00PM-6:00PM">5:00PM - 6:00PM</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="Available">Available</option>
                                    <option value="Occupied">Occupied</option>

                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Schedule</button>
                </form>
            </div>
        </div>

        <!-- Existing Schedules -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Existing Schedules</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Room</th>
                                <th>Day</th>
                                <th>Time Slot</th>
                                <th>Status</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    ?>
                                    <tr>
                                        <td>Lab <?php echo htmlspecialchars($row['ROOM_NUMBER']); ?></td>
                                        <td><?php echo htmlspecialchars($row['DAY_GROUP']); ?></td>
                                        <td><?php echo htmlspecialchars($row['TIME_SLOT']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($row['STATUS']); ?>">
                                                <?php echo htmlspecialchars($row['STATUS']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['NOTES']); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-warning" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editModal<?php echo $row['ID']; ?>">
                                                Edit
                                            </button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="schedule_id" value="<?php echo $row['ID']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" 
                                                        onclick="return confirm('Are you sure you want to delete this schedule?')">
                                                    Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal<?php echo $row['ID']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Schedule</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST" action="">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="update">
                                                        <input type="hidden" name="schedule_id" value="<?php echo $row['ID']; ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Room</label>
                                                            <input type="text" class="form-control" 
                                                                   value="Lab <?php echo htmlspecialchars($row['ROOM_NUMBER']); ?>" readonly>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Day</label>
                                                            <input type="text" class="form-control" 
                                                                   value="<?php echo htmlspecialchars($row['DAY_GROUP']); ?>" readonly>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Time Slot</label>
                                                            <input type="text" class="form-control" 
                                                                   value="<?php echo htmlspecialchars($row['TIME_SLOT']); ?>" readonly>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="status<?php echo $row['ID']; ?>" class="form-label">Status</label>
                                                            <select class="form-select" id="status<?php echo $row['ID']; ?>" name="status" required>
                                                                <option value="Available" <?php echo $row['STATUS'] == 'Available' ? 'selected' : ''; ?>>Available</option>
                                                                <option value="Reserved" <?php echo $row['STATUS'] == 'Reserved' ? 'selected' : ''; ?>>Occupied</option>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="notes<?php echo $row['ID']; ?>" class="form-label">Notes</label>
                                                            <textarea class="form-control" id="notes<?php echo $row['ID']; ?>" 
                                                                      name="notes" rows="3"><?php echo htmlspecialchars($row['NOTES']); ?></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center'>No schedules found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
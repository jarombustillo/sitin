<?php
session_start();
require_once 'connect.php';

// Check if user is logged in
if (!isset($_SESSION['IDNO'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// Get user information
$user_sql = "SELECT Firstname, Lastname, Midname, course, year_level FROM user WHERE IDNO = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("s", $_SESSION['IDNO']);
$user_stmt->execute();
$user_info = $user_stmt->get_result()->fetch_assoc();

// Function to get PC status for a specific laboratory
function getPCStatus($conn, $roomNumber) {
    $sql = "SELECT * FROM pc_status WHERE ROOM_NUMBER = ? ORDER BY PC_NUMBER";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $roomNumber);
    $stmt->execute();
    $result = $stmt->get_result();
    $pcs = array();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $pcs[] = $row;
        }
    }
    return $pcs;
}

// Get PC status when laboratory is selected
if (isset($_POST['laboratory'])) {
    $pcStatus = getPCStatus($conn, $_POST['laboratory']);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate required fields
    if (!isset($_POST['laboratory']) || empty($_POST['laboratory'])) {
        $error = "Please select a laboratory.";
    } else if (!isset($_POST['pc_number']) || empty($_POST['pc_number'])) {
        $error = "Please select a PC.";
    } else if (!isset($_POST['date']) || empty($_POST['date'])) {
        $error = "Please select a date.";
    } else if (!isset($_POST['time_slot']) || empty($_POST['time_slot'])) {
        $error = "Please select a time slot.";
    } else if (!isset($_POST['purpose']) || empty(trim($_POST['purpose']))) {
        $error = "Please enter a purpose.";
    } else {
        $laboratory = $_POST['laboratory'];
        $pc_number = $_POST['pc_number'];
        $date = $_POST['date'];
        $time_slot = $_POST['time_slot'];
        $purpose = trim($_POST['purpose']);
        
        // Validate date (must be today or future)
        if (strtotime($date) < strtotime(date('Y-m-d'))) {
            $error = "Please select a valid date (today or future).";
        } else {
            // Check if the PC is already reserved for the selected time slot
            $check_sql = "SELECT * FROM reservations 
                         WHERE LABORATORY = ? AND PC_NUMBER = ? 
                         AND DATE = ? AND TIME_SLOT = ? 
                         AND STATUS IN ('pending', 'confirmed', 'approved')";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ssss", $laboratory, $pc_number, $date, $time_slot);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "This PC is already reserved for the selected time slot.";
            } else {
                // Insert the reservation
                $insert_sql = "INSERT INTO reservations (IDNO, LABORATORY, PC_NUMBER, DATE, TIME_SLOT, PURPOSE, STATUS, CREATED_AT) 
                              VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())";
                $insert_stmt = $conn->prepare($insert_sql);
                if ($insert_stmt->bind_param("ssssss", $_SESSION['IDNO'], $laboratory, $pc_number, $date, $time_slot, $purpose)) {
                    if ($insert_stmt->execute()) {
                        // Update PC status to unavailable
                        $update_pc_sql = "UPDATE pc_status SET STATUS = 'unavailable' WHERE ROOM_NUMBER = ? AND PC_NUMBER = ?";
                        $update_pc_stmt = $conn->prepare($update_pc_sql);
                        $update_pc_stmt->bind_param("ss", $laboratory, $pc_number);
                        $update_pc_stmt->execute();
                        
                        $message = "Reservation submitted successfully!";
                        // Notify admin of new reservation
                        require_once 'includes/notifications.php';
                        $student_id = $_SESSION['IDNO'];
                        $msg = "New reservation submitted by student ID $student_id for Lab $laboratory, PC $pc_number, $date ($time_slot).";
                        createNotification($conn, 'admin', $msg, 'admin');
                    } else {
                        $error = "Error submitting reservation. Please try again.";
                    }
                }
            }
        }
    }
}

// Get user's active reservations
$reservations_sql = "SELECT r.* FROM reservations r
LEFT JOIN sitin_records s
  ON r.IDNO = s.IDNO
  AND r.LABORATORY = s.LABORATORY
  AND r.DATE = DATE(s.TIME_IN)
  AND r.TIME_SLOT = DATE_FORMAT(s.TIME_IN, '%H:%i-%H:%i')
  AND s.TIME_OUT IS NOT NULL
WHERE r.IDNO = ?
  AND r.DATE >= CURDATE()
  AND s.ID IS NULL
ORDER BY r.DATE, r.TIME_SLOT";
$reservations_stmt = $conn->prepare($reservations_sql);
$reservations_stmt->bind_param("s", $_SESSION['IDNO']);
$reservations_stmt->execute();
$reservations = $reservations_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PC Reservation - Student Dashboard</title>
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

        .pc-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin-top: 20px;
        }

        .pc-item {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .pc-item:hover {
            background-color: #f8f9fa;
        }

        .pc-item.selected {
            background-color: #007bff;
            color: white;
        }

        .pc-item.reserved {
            background-color: #dc3545;
            color: white;
            cursor: not-allowed;
        }

        .time-slots {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 20px;
        }

        .time-slot {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .time-slot:hover {
            background-color: #f8f9fa;
        }

        .time-slot.selected {
            background-color: #007bff;
            color: white;
        }

        .time-slot.reserved {
            background-color: #dc3545;
            color: white;
            cursor: not-allowed;
        }

        .reservation-card {
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
                    <li class="nav-item"><a class="nav-link active" href="reservation.php">Reservation</a></li>
                    <li class="nav-item"><a class="nav-link" href="view_lab_resources.php">Lab Resources</a></li>
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

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- Reservation Form -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Reservation Form</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="laboratory" class="form-label">Laboratory</label>
                                <select class="form-select" id="laboratory" name="laboratory" required>
                                    <option value="">Select Laboratory</option>
                                    <option value="524">Lab 524</option>
                                    <option value="526">Lab 526</option>
                                    <option value="528">Lab 528</option>
                                    <option value="530">Lab 530</option>
                                    <option value="542">Lab 542</option>
                                    <option value="544">Lab 544</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="pc_number" class="form-label">PC Number</label>
                                <div class="pc-grid">
                                    <?php
                                    $dateSelected = isset($_POST['date']) && !empty($_POST['date']);
                                    $timeSlotSelected = isset($_POST['time_slot']) && !empty($_POST['time_slot']);
                                    for ($i = 1; $i <= 50; $i++) {
                                        $status = 'available'; // Default status
                                        $disabled = '';
                                        $statusClass = 'btn-outline-primary';
                                        // Check if PC exists in pc_status table
                                        if (isset($pcStatus)) {
                                            foreach ($pcStatus as $pc) {
                                                if ($pc['PC_NUMBER'] == $i) {
                                                    $status = $pc['STATUS'];
                                                    if ($status == 'maintenance') {
                                                        $disabled = 'disabled';
                                                        $statusClass = 'btn-danger';
                                                    } elseif ($status == 'in-use') {
                                                        $disabled = 'disabled';
                                                        $statusClass = 'btn-warning';
                                                    }
                                                    break;
                                                }
                                            }
                                        }
                                        // Only check reservations if both date and time slot are selected
                                        if ($dateSelected && $timeSlotSelected) {
                                            $check_sql = "SELECT * FROM reservations 
                                                        WHERE LABORATORY = ? AND PC_NUMBER = ? 
                                                        AND DATE = ? AND TIME_SLOT = ? 
                                                        AND STATUS IN ('pending', 'confirmed', 'approved')";
                                            $check_stmt = $conn->prepare($check_sql);
                                            $check_stmt->bind_param("ssss", $_POST['laboratory'], $i, $_POST['date'], $_POST['time_slot']);
                                            $check_stmt->execute();
                                            if ($check_stmt->get_result()->num_rows > 0) {
                                                $disabled = 'disabled';
                                                $statusClass = 'btn-secondary';
                                                $status = 'reserved';
                                            }
                                        }
                                    ?>
                                        <button type="button" 
                                                class="btn <?php echo $statusClass; ?> pc-item" 
                                                data-pc="<?php echo $i; ?>"
                                                <?php echo $disabled; ?>>
                                            PC <?php echo $i; ?>
                                            <small class="d-block"><?php echo ucfirst($status); ?></small>
                                        </button>
                                    <?php } ?>
                                </div>
                                <input type="hidden" name="pc_number" id="selected_pc" required>
                            </div>

                            <div class="mb-3">
                                <label for="date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="date" name="date" required>
                            </div>

                            <div class="mb-3">
                                <label for="time_slot" class="form-label">Time Slot</label>
                                <select class="form-select" id="time_slot" name="time_slot" required>
                                    <option value="">Select Time Slot</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="purpose" class="form-label">Purpose</label>
                                <textarea class="form-control" id="purpose" name="purpose" rows="3" required></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Submit Reservation</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Active Reservations -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Available Schedules</h5>
                    </div>
                    <div class="card-body">
                        <div id="scheduleList">
                            <p class="text-muted">Select a laboratory to view available schedules.</p>
                        </div>
                    </div>
                </div>
                <!-- My Active Reservations moved here -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">My Active Reservations</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($reservations->num_rows > 0): ?>
                            <div class="d-flex flex-column gap-3">
                                <?php while ($row = $reservations->fetch_assoc()): ?>
                                    <div class="reservation-card p-3 border rounded shadow-sm">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <strong>Lab:</strong> <?php echo htmlspecialchars($row['LABORATORY']); ?>
                                                <span class="mx-2">|</span>
                                                <strong>PC #:</strong> <?php echo htmlspecialchars($row['PC_NUMBER']); ?>
                                            </div>
                                            <span class="status-badge status-<?php echo strtolower($row['STATUS']); ?>">
                                                <?php echo ucfirst($row['STATUS']); ?>
                                            </span>
                                        </div>
                                        <div><strong>Date:</strong> <?php echo htmlspecialchars($row['DATE']); ?></div>
                                        <div><strong>Time Slot:</strong> <?php echo htmlspecialchars($row['TIME_SLOT']); ?></div>
                                        <div><strong>Purpose:</strong> <?php echo htmlspecialchars($row['PURPOSE']); ?></div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">You have no active reservations.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function fetchSchedules(lab, date) {
            const dayOfWeek = new Date(date).toLocaleDateString('en-US', { weekday: 'long' });
            fetch('get_schedules.php?lab=' + lab + '&day=' + dayOfWeek)
                .then(response => response.json())
                .then(data => {
                    const scheduleList = document.getElementById('scheduleList');
                    const timeSlotSelect = document.getElementById('time_slot');
                    // Clear existing options
                    timeSlotSelect.innerHTML = '<option value="">Select Time Slot</option>';
                    if (data.length > 0) {
                        let html = '<div class="table-responsive"><table class="table table-bordered">';
                        html += '<thead><tr><th>Time Slot</th><th>Status</th><th>Notes</th></tr></thead><tbody>';
                        data.forEach(schedule => {
                            if (schedule.STATUS === 'Available') {
                                // Add to time slot dropdown
                                const option = document.createElement('option');
                                option.value = schedule.TIME_SLOT;
                                option.textContent = schedule.TIME_SLOT;
                                timeSlotSelect.appendChild(option);
                            }
                            // Add to schedule table
                            html += `<tr>
                                <td>${schedule.TIME_SLOT}</td>
                                <td><span class="badge bg-${schedule.STATUS === 'Available' ? 'success' : 'warning'}">${schedule.STATUS}</span></td>
                                <td>${schedule.NOTES || '-'}</td>
                            </tr>`;
                        });
                        html += '</tbody></table></div>';
                        scheduleList.innerHTML = html;
                    } else {
                        scheduleList.innerHTML = '<p class="text-muted">No schedules available for this laboratory on the selected day.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('scheduleList').innerHTML = '<p class="text-danger">Error loading schedules.</p>';
                });
        }
        document.getElementById('laboratory').addEventListener('change', function() {
            const lab = this.value;
            const date = document.getElementById('date').value;
            if (lab && date) {
                fetchSchedules(lab, date);
            }
        });
        document.getElementById('date').addEventListener('change', function() {
            const lab = document.getElementById('laboratory').value;
            const date = this.value;
            if (lab && date) {
                fetchSchedules(lab, date);
            }
        });
        function updatePCGrid() {
            const lab = document.getElementById('laboratory').value;
            const date = document.getElementById('date').value;
            const timeSlot = document.getElementById('time_slot').value;
            if (!lab || !date || !timeSlot) {
                // Enable all PCs (except those under maintenance/in-use)
                document.querySelectorAll('.pc-item').forEach(btn => {
                    if (!btn.classList.contains('btn-danger') && !btn.classList.contains('btn-warning')) {
                        btn.disabled = false;
                        btn.classList.remove('btn-secondary');
                        btn.classList.add('btn-outline-primary');
                        btn.querySelector('small').textContent = 'Available';
                    }
                });
                return;
            }
            fetch(`get_reserved_pcs.php?lab=${lab}&date=${date}&time_slot=${encodeURIComponent(timeSlot)}`)
                .then(response => response.json())
                .then(data => {
                    document.querySelectorAll('.pc-item').forEach(btn => {
                        const pcNum = parseInt(btn.getAttribute('data-pc'));
                        if (data.reserved_pcs.includes(pcNum)) {
                            btn.disabled = true;
                            btn.classList.remove('btn-outline-primary');
                            btn.classList.add('btn-secondary');
                            btn.querySelector('small').textContent = 'Reserved';
                        } else if (!btn.classList.contains('btn-danger') && !btn.classList.contains('btn-warning')) {
                            btn.disabled = false;
                            btn.classList.remove('btn-secondary');
                            btn.classList.add('btn-outline-primary');
                            btn.querySelector('small').textContent = 'Available';
                        }
                    });
                });
        }
        function enableAvailablePCs() {
            document.querySelectorAll('.pc-item').forEach(btn => {
                // Only enable if not reserved, not maintenance, not in-use
                if (
                    !btn.classList.contains('btn-secondary') &&
                    !btn.classList.contains('btn-danger') &&
                    !btn.classList.contains('btn-warning')
                ) {
                    btn.disabled = false;
                }
            });
        }
        function attachPCClickHandlers() {
            document.querySelectorAll('.pc-item').forEach(button => {
                button.addEventListener('click', function() {
                    // Remove selected class from all buttons
                    document.querySelectorAll('.pc-item').forEach(btn => {
                        btn.classList.remove('btn-primary');
                        btn.classList.add('btn-outline-primary');
                    });
                    // Add selected class to clicked button
                    this.classList.remove('btn-outline-primary');
                    this.classList.add('btn-primary');
                    // Update hidden input
                    document.getElementById('selected_pc').value = this.dataset.pc;
                });
            });
        }
        // Call this after the PC grid is updated
        document.getElementById('time_slot').addEventListener('change', function() {
            updatePCGrid();
            attachPCClickHandlers();
        });
        window.addEventListener('DOMContentLoaded', function() {
            attachPCClickHandlers();
        });
    </script>
</body>
</html> 
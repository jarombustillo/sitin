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
                         AND STATUS != 'cancelled'";
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
                        $message = "Reservation submitted successfully!";
                    } else {
                        $error = "Error submitting reservation. Please try again.";
                    }
                }
            }
        }
    }
}

// Get user's active reservations
$reservations_sql = "SELECT * FROM reservations 
                    WHERE IDNO = ? AND DATE >= CURDATE() 
                    AND STATUS != 'cancelled' 
                    ORDER BY DATE, TIME_SLOT";
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
            <div class="col-md-8">
                <div class="card reservation-card">
                    <div class="card-header">
                        <h5 class="mb-0">Make a Reservation</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="laboratory" class="form-label">Select Laboratory</label>
                                <select class="form-select" id="laboratory" name="laboratory" required>
                                    <option value="">Choose a laboratory...</option>
                                    <option value="Lab 524">524</option>
                                    <option value="Lab 526">526</option>
                                    <option value="Lab 530">530</option>
                                    <option value="Lab 542">542</option>
                                    <option value="Lab 544">544</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Select PC</label>
                                <div class="pc-grid">
                                    <?php for ($i = 1; $i <= 50; $i++): ?>
                                        <div class="pc-item" data-pc="<?php echo $i; ?>">
                                            PC <?php echo $i; ?>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                                <input type="hidden" name="pc_number" id="pc_number" required>
                            </div>

                            <div class="mb-3">
                                <label for="date" class="form-label">Select Date</label>
                                <input type="date" class="form-control" id="date" name="date" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Select Time Slot</label>
                                <div class="time-slots">
                                    <div class="time-slot" data-time="08:00-09:00">8:00 AM - 9:00 AM</div>
                                    <div class="time-slot" data-time="09:00-10:00">9:00 AM - 10:00 AM</div>
                                    <div class="time-slot" data-time="10:00-11:00">10:00 AM - 11:00 AM</div>
                                    <div class="time-slot" data-time="11:00-12:00">11:00 AM - 12:00 PM</div>
                                    <div class="time-slot" data-time="13:00-14:00">1:00 PM - 2:00 PM</div>
                                    <div class="time-slot" data-time="14:00-15:00">2:00 PM - 3:00 PM</div>
                                    <div class="time-slot" data-time="15:00-16:00">3:00 PM - 4:00 PM</div>
                                    <div class="time-slot" data-time="16:00-17:00">4:00 PM - 5:00 PM</div>
                                </div>
                                <input type="hidden" name="time_slot" id="time_slot" required>
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
            <div class="col-md-4">
                <div class="card reservation-card">
                    <div class="card-header">
                        <h5 class="mb-0">Your Active Reservations</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($reservations->num_rows > 0): ?>
                            <?php while ($reservation = $reservations->fetch_assoc()): ?>
                                <div class="reservation-item mb-3">
                                    <h6>Lab <?php echo htmlspecialchars($reservation['LABORATORY']); ?> - PC <?php echo htmlspecialchars($reservation['PC_NUMBER']); ?></h6>
                                    <p class="mb-1">Date: <?php echo date('M d, Y', strtotime($reservation['DATE'])); ?></p>
                                    <p class="mb-1">Time: <?php echo htmlspecialchars($reservation['TIME_SLOT']); ?></p>
                                    <p class="mb-1">Purpose: <?php echo htmlspecialchars($reservation['PURPOSE']); ?></p>
                                    <span class="status-badge status-<?php echo strtolower($reservation['STATUS']); ?>">
                                        <?php echo ucfirst($reservation['STATUS']); ?>
                                    </span>
                                </div>
                                <hr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-center">No active reservations.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Time Slot Selection
        document.querySelectorAll('.time-slot').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.time-slot').forEach(slot => slot.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('time_slot').value = this.dataset.time;
            });
        });

        // Check PC availability when laboratory or date changes
        document.getElementById('laboratory').addEventListener('change', checkAvailability);
        document.getElementById('date').addEventListener('change', checkAvailability);

        function checkAvailability() {
            const laboratory = document.getElementById('laboratory').value;
            const date = document.getElementById('date').value;
            
            if (laboratory && date) {
                // Here you would typically make an AJAX call to check availability
                // For now, we'll just simulate it
                document.querySelectorAll('.pc-item').forEach(pc => {
                    pc.classList.remove('reserved');
                    // Randomly mark some PCs as reserved for demonstration
                    if (Math.random() > 0.7) {
                        pc.classList.add('reserved');
                    }
                });
            }
        }

        // Add form validation before submit
        document.querySelector('form').addEventListener('submit', function(e) {
            const laboratory = document.getElementById('laboratory');
            const pcNumber = document.getElementById('pc_number');
            const date = document.getElementById('date');
            const timeSlot = document.getElementById('time_slot');
            const purpose = document.getElementById('purpose');

            if (!laboratory.value) {
                e.preventDefault();
                alert('Please select a laboratory.');
                return false;
            }
            if (!timeSlot.value) {
                e.preventDefault();
                alert('Please select a time slot.');
                return false;
            }
            if (!purpose.value.trim()) {
                e.preventDefault();
                alert('Please enter a purpose.');
                return false;
            }
        });

        // PC Selection
        document.querySelectorAll('.pc-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.pc-item').forEach(pc => pc.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('pc_number').value = this.dataset.pc;
            });
        });
    </script>
</body>
</html> 
<?php
session_start();
include("connect.php");

// Check if user is logged in
if (!isset($_SESSION['IDNO'])) {
    header("Location: login.php");
    exit();
}

// Fetch existing schedules
$query = "SELECT * FROM labschedules ORDER BY ROOM_NUMBER, DAY_GROUP, TIME_SLOT";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Schedules</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        html, body {
            background: linear-gradient(135deg, #14569b, #2a3f5f);
            min-height: 100vh;
            width: 100%;
        }

        /* Navbar Styles */
        .navbar {
            background-color: #212529 !important;
            padding: 0.5rem 1rem;
        }

        .navbar-brand {
            color: white !important;
            font-weight: 500;
        }

        .nav-link {
            color: rgba(255,255,255,.8) !important;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: #fff !important;
        }

        .nav-link.active {
            color: #fff !important;
            font-weight: 500;
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

        /* Content Area */
        .content {
            padding: 30px;
            min-height: calc(100vh - 56px);
            background: #f0f2f5;
        }

        .parent {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            min-height: calc(100vh - 116px);
        }

        h1 {
            color: #14569b;
            margin-bottom: 25px;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .schedule-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .schedule-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .schedule-meta {
            font-size: 0.85rem;
            color: #94a3b8;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
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

        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .filter-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #14569b;
            font-weight: 500;
        }

        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            transition: all 0.2s;
        }

        select:focus {
            border-color: #14569b;
            outline: none;
            box-shadow: 0 0 0 3px rgba(20, 86, 155, 0.1);
        }

        .schedules-list {
            height: 100%;
            overflow-y: auto;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: #14569b;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #0f4578;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Student Portal</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="reservation.php">Reservations</a></li>
                    <li class="nav-item"><a class="nav-link" href="history.php">History</a></li>
                    <li class="nav-item"><a class="nav-link" href="feedback.php">Feedback</a></li>
                        
                </ul>
                <a href="login.php?logout=true" class="logout-btn ms-auto">Log out</a>
            </div>
        </div>
    </nav>

    <div class="content">
        <div class="parent">
            <h1>Lab Schedules</h1>

            <div class="filter-section">
                <div class="row">
                    <div class="col-md-4">
                        <div class="filter-group">
                            <label for="room_filter">Filter by Room</label>
                            <select id="room_filter" onchange="filterSchedules()">
                                <option value="">All Rooms</option>
                                <option value="524">Lab 524</option>
                                <option value="526">Lab 526</option>
                                <option value="528">Lab 528</option>
                                <option value="530">Lab 530</option>
                                <option value="542">Lab 542</option>
                                <option value="544">Lab 544</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="filter-group">
                            <label for="day_filter">Filter by Day</label>
                            <select id="day_filter" onchange="filterSchedules()">
                                <option value="">All Days</option>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="filter-group">
                            <label for="status_filter">Filter by Status</label>
                            <select id="status_filter" onchange="filterSchedules()">
                                <option value="">All Status</option>
                                <option value="Available">Available</option>
                                <option value="Reserved">Reserved</option>
                                <option value="Maintenance">Maintenance</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="schedules-list">
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class="schedule-card" data-room="<?php echo htmlspecialchars($row['ROOM_NUMBER']); ?>" 
                         data-day="<?php echo htmlspecialchars($row['DAY_GROUP']); ?>"
                         data-status="<?php echo htmlspecialchars($row['STATUS']); ?>">
                        <div class="schedule-title">Lab <?php echo htmlspecialchars($row['ROOM_NUMBER']); ?></div>
                        <div class="schedule-meta">
                            Day: <?php echo htmlspecialchars($row['DAY_GROUP']); ?> |
                            Time: <?php echo htmlspecialchars($row['TIME_SLOT']); ?> |
                            Status: <span class="status-badge status-<?php echo strtolower($row['STATUS']); ?>"><?php echo htmlspecialchars($row['STATUS']); ?></span>
                            <?php if (!empty($row['NOTES'])): ?>
                                | Notes: <?php echo htmlspecialchars($row['NOTES']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterSchedules() {
            const roomFilter = document.getElementById('room_filter').value;
            const dayFilter = document.getElementById('day_filter').value;
            const statusFilter = document.getElementById('status_filter').value;
            
            const cards = document.querySelectorAll('.schedule-card');
            
            cards.forEach(card => {
                const room = card.dataset.room;
                const day = card.dataset.day;
                const status = card.dataset.status;
                
                const roomMatch = !roomFilter || room === roomFilter;
                const dayMatch = !dayFilter || day === dayFilter;
                const statusMatch = !statusFilter || status === statusFilter;
                
                if (roomMatch && dayMatch && statusMatch) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html> 
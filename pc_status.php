<?php
session_start();
require_once 'connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

// Handle PC deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_pc'])) {
    $pcId = $_POST['pc_id'];
    $sql = "DELETE FROM pc_status WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pcId);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "PC deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting PC: " . $conn->error;
    }
    header("Location: pc_management.php");
    exit();
}

// Handle PC addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_pc'])) {
    $roomNumber = $_POST['room_number'];
    $pcNumber = $_POST['pc_number'];
    $status = $_POST['status'];
    
    $sql = "INSERT INTO pc_status (ROOM_NUMBER, PC_NUMBER, STATUS) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $roomNumber, $pcNumber, $status);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "PC added successfully!";
    } else {
        $_SESSION['error'] = "Error adding PC: " . $conn->error;
    }
    header("Location: pc_management.php");
    exit();
}

// Handle PC update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_pc'])) {
    $pcId = $_POST['pc_id'];
    $roomNumber = $_POST['room_number'];
    $pcNumber = $_POST['pc_number'];
    $status = $_POST['status'];
    
    $sql = "UPDATE pc_status SET ROOM_NUMBER = ?, PC_NUMBER = ?, STATUS = ? WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $roomNumber, $pcNumber, $status, $pcId);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "PC updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating PC: " . $conn->error;
    }
    header("Location: pc_management.php");
    exit();
}

// Get unique room numbers for the dropdown
$roomSql = "SELECT DISTINCT ROOM_NUMBER FROM pc_status ORDER BY ROOM_NUMBER";
$roomResult = $conn->query($roomSql);
$rooms = array();
if ($roomResult->num_rows > 0) {
    while($row = $roomResult->fetch_assoc()) {
        $rooms[] = $row['ROOM_NUMBER'];
    }
}

// Get selected room
$currentRoom = isset($_GET['room']) ? $_GET['room'] : (count($rooms) > 0 ? $rooms[0] : '');

// Get PCs for the selected room
$pcSql = "SELECT * FROM pc_status WHERE ROOM_NUMBER = ? ORDER BY PC_NUMBER";
$pcStmt = $conn->prepare($pcSql);
$pcStmt->bind_param("s", $currentRoom);
$pcStmt->execute();
$pcResult = $pcStmt->get_result();
$pcs = array();
while ($row = $pcResult->fetch_assoc()) {
    $pcs[] = $row;
}

// Build a map for quick lookup
$pc_map = [];
foreach ($pcs as $pc) {
    $pc_map[(int)$pc['PC_NUMBER']] = $pc;
}
$total_pcs = 50;
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<style>
    body {
        min-height: 100vh;
        background: #fafbfc;
    }
    .main-content {
        max-width: 1400px;
        margin: 0 auto;
        padding: 32px 16px 0 16px;
    }
    .pc-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 22px;
        margin-top: 24px;
    }
    .pc-card {
        border: 2px solid #e0e0e0;
        border-radius: 16px;
        padding: 32px 0 18px 0;
        text-align: center;
        background: #fff;
        color: #1565c0;
        font-size: 1.15rem;
        font-weight: 500;
        box-shadow: 0 2px 8px 0 #f3f3f3;
        min-width: 120px;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .pc-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        border-color: #1565c0;
    }
    .pc-card.selected {
        border: 2px solid #1565c0;
        background-color: #e3f2fd;
        box-shadow: 0 4px 12px rgba(21,101,192,0.2);
    }
    .pc-card.status-available {
        border-color: #198754;
    }
    .pc-card.status-available:hover {
        border-color: #198754;
        background-color: #f8fff9;
    }
    .pc-card.status-in-use {
        border-color: #dc3545;
    }
    .pc-card.status-in-use:hover {
        border-color: #dc3545;
        background-color: #fff8f8;
    }
    .pc-card.status-maintenance {
        border-color: #fd7e14;
    }
    .pc-card.status-maintenance:hover {
        border-color: #fd7e14;
        background-color: #fff9f0;
    }
    .pc-card.selected.status-available {
        background-color: #e8f5e9;
    }
    .pc-card.selected.status-in-use {
        background-color: #ffebee;
    }
    .pc-card.selected.status-maintenance {
        background-color: #fff3e0;
    }
    .bulk-actions {
        margin-bottom: 18px;
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }
    .status-legend {
        display: flex;
        gap: 24px;
        align-items: center;
        margin-top: 12px;
        margin-bottom: 24px;
    }
    .status-legend .legend-dot {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
    }
    .legend-available { background: #198754; }
    .legend-in-use { background: #dc3545; }
    .legend-maintenance { background: #fd7e14; }
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
    @media (max-width: 900px) {
        .main-content { padding: 24px 4px 0 4px; }
        .pc-grid { grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); }
    }
    @media (max-width: 600px) {
        .main-content { padding: 12px 2px 0 2px; }
        .pc-grid { grid-template-columns: repeat(2, 1fr); }
    }
</style>


    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-3">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin.php">Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="students.php">Students</a></li>
                    <li class="nav-item"><a class="nav-link" href="current_sitin.php">Sit-In</a></li>
                    <li class="nav-item"><a class="nav-link" href="sitinrecords.php">Sit-in Records</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_reservations.php">Reservations</a></li>
                    <li class="nav-item"><a class="nav-link active" href="pc_status.php">PC</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin/lab_schedules.php">Lab Schedules</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin/feedback.php">Feedback</a></li>
                    <li class="nav-item"><a class="nav-link" href="labresources.php">Lab Resources</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
                    <li class="nav-item"><a class="nav-link" href="reward.php">Leaderboard</a></li>
                </ul>
                <a href="login.php?logout=true" class="logout-btn ms-auto">Log out</a>
            </div>
        </div>
    </nav>
    <div class="container-fluid main-content">
    <h2 class="mb-4">PC Management</h2>
    <!-- Room Selection -->
    <div class="mb-3">
        <label for="roomSelect" class="form-label">Select Lab Room:</label>
        <select class="form-select" id="roomSelect" style="max-width: 200px; display: inline-block;" onchange="location.href='pc_status.php?room='+this.value">
            <option value="524" <?php echo $currentRoom === '524' ? 'selected' : ''; ?>>Lab 524</option>
            <option value="526" <?php echo $currentRoom === '526' ? 'selected' : ''; ?>>Lab 526</option>
            <option value="528" <?php echo $currentRoom === '528' ? 'selected' : ''; ?>>Lab 528</option>
            <option value="530" <?php echo $currentRoom === '530' ? 'selected' : ''; ?>>Lab 530</option>
            <option value="542" <?php echo $currentRoom === '542' ? 'selected' : ''; ?>>Lab 542</option>
            <option value="544" <?php echo $currentRoom === '544' ? 'selected' : ''; ?>>Lab 544</option>
        </select>
    </div>

    <!-- Bulk Actions -->
    <div class="bulk-actions">
        <button class="btn btn-outline-secondary" id="selectAllBtn"><i class="bi bi-list-check"></i> Select All</button>
        <button class="btn btn-success" id="setAvailableBtn"><i class="bi bi-check-circle-fill"></i> Set Available</button>
        <button class="btn btn-danger" id="setUsedBtn"><i class="bi bi-x-circle-fill"></i> Set Used</button>
        <button class="btn btn-warning" id="setMaintenanceBtn"><i class="bi bi-wrench"></i> Set Maintenance</button>
    </div>

    <!-- Status Legend -->
    <div class="status-legend">
        <span><span class="legend-dot legend-available"></span>Available</span>
        <span><span class="legend-dot legend-in-use"></span>Used</span>
        <span><span class="legend-dot legend-maintenance"></span>Maintenance</span>
    </div>

    <!-- PC Status Grid (Bulk Actions) -->
    <h4>PC Number</h4>
    <div class="pc-grid">
        <?php for ($i = 1; $i <= $total_pcs; $i++): ?>
            <?php $pc = isset($pc_map[$i]) ? $pc_map[$i] : null; ?>
            <?php
                $status = $pc ? $pc['STATUS'] : 'available';
                $statusClass = 'status-available';
                if ($status === 'in-use') $statusClass = 'status-in-use';
                if ($status === 'maintenance') $statusClass = 'status-maintenance';
            ?>
            <div class="pc-card <?php echo $statusClass; ?>" data-pc-number="<?php echo $i; ?>" data-status="<?php echo $status; ?>">
                PC <?php echo $i; ?>
                <span class="status">
                    <?php echo ucfirst($status); ?>
                </span>
            </div>
        <?php endfor; ?>
    </div>
</div>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const pcCards = document.querySelectorAll('.pc-card');
    let selectedPCs = new Set();

    pcCards.forEach(card => {
        card.addEventListener('click', function() {
            const pcNumber = this.getAttribute('data-pc-number');
            if (this.classList.contains('selected')) {
                this.classList.remove('selected');
                selectedPCs.delete(pcNumber);
            } else {
                this.classList.add('selected');
                selectedPCs.add(pcNumber);
            }
        });
    });

    document.getElementById('selectAllBtn').addEventListener('click', function() {
        pcCards.forEach(card => {
            card.classList.add('selected');
            selectedPCs.add(card.getAttribute('data-pc-number'));
        });
    });

    document.getElementById('setAvailableBtn').addEventListener('click', function() {
        updateStatusBulk('available');
    });
    document.getElementById('setUsedBtn').addEventListener('click', function() {
        updateStatusBulk('in-use');
    });
    document.getElementById('setMaintenanceBtn').addEventListener('click', function() {
        updateStatusBulk('maintenance');
    });

    function updateStatusBulk(status) {
        if (selectedPCs.size === 0) {
            alert('Please select at least one PC.');
            return;
        }
        const room = '<?php echo $currentRoom; ?>';
        fetch('update_pc_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pc_ids: Array.from(selectedPCs), status: status, room_number: room })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Update UI instantly
                selectedPCs.forEach(pcNumber => {
                    const card = document.querySelector('.pc-card[data-pc-number="' + pcNumber + '"]');
                    card.setAttribute('data-status', status);
                    card.classList.remove('status-available', 'status-in-use', 'status-maintenance');
                    if (status === 'available') card.classList.add('status-available');
                    if (status === 'in-use') card.classList.add('status-in-use');
                    if (status === 'maintenance') card.classList.add('status-maintenance');
                    card.querySelector('.status').textContent = status.charAt(0).toUpperCase() + status.slice(1);
                });
                selectedPCs.clear();
                pcCards.forEach(card => card.classList.remove('selected'));
            } else {
                alert('Failed to update status.');
            }
        });
    }
});
</script> 
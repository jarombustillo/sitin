<?php
session_start();
require_once 'connect.php';

// Check if user is logged in
if (!isset($_SESSION['IDNO'])) {
    header("Location: login.php");
    exit();
}

// Function to get all laboratories
function getLaboratories($conn) {
    $sql = "SELECT DISTINCT ROOM_NUMBER FROM pc_status ORDER BY ROOM_NUMBER";
    $result = $conn->query($sql);
    $labs = array();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $labs[] = $row['ROOM_NUMBER'];
        }
    }
    return $labs;
}

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

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $pcId = $_POST['pc_id'];
    $newStatus = $_POST['status'];
    $roomNumber = $_POST['room_number'];
    
    $sql = "UPDATE pc_status SET STATUS = ?, LAST_UPDATED = CURRENT_TIMESTAMP WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $newStatus, $pcId);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "PC status updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating PC status: " . $conn->error;
    }
    
    header("Location: pc_status.php?room=" . urlencode($roomNumber));
    exit();
}

// Get current laboratory from URL parameter
$currentLab = isset($_GET['room']) ? $_GET['room'] : '';

// Get all laboratories
$laboratories = getLaboratories($conn);

// If no laboratory is selected, show the first one
if (empty($currentLab) && !empty($laboratories)) {
    $currentLab = $laboratories[0];
}

// Get PC status for current laboratory
$pcStatus = getPCStatus($conn, $currentLab);

// Fetch all PC statuses for the user's reservations
$pc_status_map = [];
$pc_status_sql = "SELECT ROOM_NUMBER, PC_NUMBER, STATUS FROM pc_status";
$pc_status_result = $conn->query($pc_status_sql);
if ($pc_status_result) {
    while ($row = $pc_status_result->fetch_assoc()) {
        $pc_status_map[$row['ROOM_NUMBER'] . '-' . $row['PC_NUMBER']] = $row['STATUS'];
    }
}

// Include the dashboard header which contains the navbar
include 'dashboard.php';
?>

<div class="container mt-4">
    <h2 class="mb-4">PC Status Management</h2>

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

    <!-- Laboratory Selection -->
    <div class="mb-4">
        <label for="labSelect" class="form-label">Select Laboratory:</label>
        <select class="form-select" id="labSelect" onchange="window.location.href='pc_status.php?room='+this.value">
            <?php foreach ($laboratories as $lab): ?>
                <option value="<?php echo htmlspecialchars($lab); ?>" <?php echo ($lab === $currentLab) ? 'selected' : ''; ?>>
                    Laboratory <?php echo htmlspecialchars($lab); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- PC Status Grid -->
    <div class="row row-cols-1 row-cols-md-4 g-4">
        <?php foreach ($pcStatus as $pc): ?>
            <div class="col">
                <div class="card pc-card h-100 <?php echo 'status-' . strtolower($pc['STATUS']); ?>">
                    <div class="card-body">
                        <h5 class="card-title">PC <?php echo htmlspecialchars($pc['PC_NUMBER']); ?></h5>
                        <p class="card-text">
                            Status: <span class="badge bg-<?php echo $pc['STATUS'] === 'available' ? 'success' : ($pc['STATUS'] === 'in-use' ? 'warning' : 'danger'); ?>">
                                <?php echo htmlspecialchars($pc['STATUS']); ?>
                            </span>
                        </p>
                        <p class="card-text">
                            <small class="text-muted">Last updated: <?php echo date('M d, Y H:i', strtotime($pc['LAST_UPDATED'])); ?></small>
                        </p>
                        
                        <!-- Status Update Form -->
                        <form method="POST" class="mt-2">
                            <input type="hidden" name="pc_id" value="<?php echo $pc['ID']; ?>">
                            <input type="hidden" name="room_number" value="<?php echo htmlspecialchars($currentLab); ?>">
                            <select name="status" class="form-select mb-2">
                                <option value="available" <?php echo $pc['STATUS'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="in-use" <?php echo $pc['STATUS'] === 'in-use' ? 'selected' : ''; ?>>In Use</option>
                                <option value="maintenance" <?php echo $pc['STATUS'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            </select>
                            <button type="submit" name="update_status" class="btn btn-primary btn-sm w-100">Update Status</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
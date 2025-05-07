<?php
// Student feedback submission page
session_start();
require_once 'connect.php';

// Check if user is logged in
if (!isset($_SESSION['IDNO'])) {
    header("Location: login.php");
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_SESSION['IDNO'];
    $sitin_record_id = (int)$_POST['sitin_record_id'];
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);
    
    if (empty($comment)) {
        $error = "Please provide your feedback comment.";
    } else {
        $sql = "INSERT INTO feedback (SITIN_RECORD_ID, STUDENT_ID, RATING, COMMENT, CREATED_AT) 
                VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        if ($stmt->execute([$sitin_record_id, $student_id, $rating, $comment])) {
            $success = "Thank you for your feedback!";
            // Clear form data
            $comment = '';
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}

// Fetch user's recent sit-in records that don't have feedback yet
$sql = "SELECT sr.ID, sr.PURPOSE, sr.LABORATORY, sr.TIME_IN, sr.TIME_OUT 
        FROM sitin_records sr 
        LEFT JOIN feedback f ON sr.ID = f.SITIN_RECORD_ID 
        WHERE sr.IDNO = ? AND f.ID IS NULL AND sr.TIME_OUT IS NOT NULL 
        ORDER BY sr.TIME_OUT DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION['IDNO']);
$stmt->execute();
$result = $stmt->get_result();
$sitin_records = [];
while ($row = $result->fetch_assoc()) {
    $sitin_records[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Feedback</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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
            <a class="navbar-brand" href="dashboard.php">Student Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="edit.php">Edit Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="history.php">History</a></li>
                    <li class="nav-item"><a class="nav-link" href="reservation.php">Reservation</a></li>
                </ul>
                <a href="login.php?logout=true" class="logout-btn ms-auto">Log out</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Submit Feedback</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <?php if (empty($sitin_records)): ?>
                            <div class="alert alert-info">
                                You don't have any completed sit-in sessions that need feedback.
                            </div>
                        <?php else: ?>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="sitin_record" class="form-label">Select Session</label>
                                    <select class="form-select" id="sitin_record" name="sitin_record_id" required>
                                        <option value="">Choose a session...</option>
                                        <?php foreach ($sitin_records as $record): ?>
                                            <option value="<?php echo $record['ID']; ?>">
                                                <?php 
                                                echo date('M d, Y H:i', strtotime($record['TIME_IN'])) . ' - ' . 
                                                     date('H:i', strtotime($record['TIME_OUT'])) . 
                                                     ' (' . htmlspecialchars($record['PURPOSE']) . 
                                                     ($record['LABORATORY'] ? ' - Lab ' . htmlspecialchars($record['LABORATORY']) : '') . ')';
                                                ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="rating" class="form-label">Rating</label>
                                    <select class="form-select" id="rating" name="rating" required>
                                        <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                                        <option value="4">⭐⭐⭐⭐ Very Good</option>
                                        <option value="3">⭐⭐⭐ Good</option>
                                        <option value="2">⭐⭐ Fair</option>
                                        <option value="1">⭐ Poor</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="comment" class="form-label">Comment</label>
                                    <textarea class="form-control" id="comment" name="comment" rows="5" required><?php echo isset($comment) ? htmlspecialchars($comment) : ''; ?></textarea>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Submit Feedback</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

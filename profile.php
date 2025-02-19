<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="profile-container">
        <h1 class="profile-title">Profile</h1>
        <?php
        
        session_start();
        include "connect.php";

        if (!isset($_SESSION['username'])) {
            header("Location: profile.php");
            exit();
        }

        $username = $_SESSION['username'];
        $sql = "SELECT * FROM user WHERE username='$username'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo "<p>IDNO: " . htmlspecialchars($row['IDNO']) . "</p>";
            echo "<p>Fullname: " . htmlspecialchars($row['Firstname']) . "</p>";
            echo "<p>Firstname: " . htmlspecialchars($row['Midname']) . "</p>";
            echo "<p>Midname: " . htmlspecialchars($row['Lastname']) . "</p>";
            echo "<p>Course: " . htmlspecialchars($row['course']) . "</p>";
            echo "<p>Year Level: " . htmlspecialchars($row['year_level']) . "</p>";
        } else {
            echo "<p>No user data found.</p>";
        }
        ?>
    </div>
</body>
</html></head>
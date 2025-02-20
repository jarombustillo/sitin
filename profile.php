<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="styles.css">
    <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        margin: 0;
        padding: 0;
    }

    .profile-container {
        max-width: 600px;
        margin: 50px auto;
        padding: 20px;
        background-color: #fff;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
    }

    .profile-title {
        text-align: center;
        color: #333;
    }

    .profile-container p {
        font-size: 20px;
        color: #555;
        line-height: 1.6;
    }
</style>
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
        $sql = "SELECT profilepic FROM user WHERE username='$username'";
        $result = $conn->query($sql);

        $username = $_SESSION['username'];
        $sql = "SELECT * FROM user WHERE username='$username'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $fullname = htmlspecialchars($row['Firstname']) . " " . htmlspecialchars($row['Midname']) . " " . htmlspecialchars($row['Lastname']);
            echo "<p>IDNO: " . htmlspecialchars($row['IDNO']) . "</p>";
            echo "<p>Fullname: " . $fullname . "</p>";
            echo "<p>Course: " . htmlspecialchars($row['course']) . "</p>";
            echo "<p>Year Level: " . htmlspecialchars($row['year_level']) . "</p>";
        } else {
            echo "<script>alert('No user data found');</script>";
        }
        ?>
    </div>
</body>
</html></head>
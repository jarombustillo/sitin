<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="profile-container">
        <h1 class="profile-title">Edit Profile</h1>
        <?php
        session_start();
        include "connect.php";

        $username = $_SESSION['username'];
        $sql = "SELECT * FROM user WHERE username='$username'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            ?>
            <div class="profile-pic">
                <img src="uploads/<?php echo htmlspecialchars($row['profilepic']); ?>" alt="Profile Picture">
            </div>
            <form action="update_profile.php" method="post">
                <div class="form-group">
                    <label for="firstname"></label>
                    <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($row['Firstname']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="midname"></label>
                    <input type="text" id="midname" name="midname" value="<?php echo htmlspecialchars($row['Midname']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="lastname"></label>
                    <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($row['Lastname']); ?>" required>
                </div>
                <div class="form-group">
                <select id="year_level" name="year_level" required>
                    <option value="" disabled selected>Year Level</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" class="edit-button">Update</button>
            </div>
            </form>
            <?php
        } else {
            echo "<script>alert('No user data found');</script>";
        }
        ?>
    </div>
</body>
</html>
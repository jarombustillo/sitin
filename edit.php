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
include("connect.php");

$username = $_SESSION['username'];

// Fetch user data from the database - changed table name from 'register' to 'user'
$query = "SELECT * FROM user WHERE username = '$username'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    
    // Check if profile picture exists and is not empty
    $profilepic = 'default.png'; // Default value
    if (!empty($user['profilepic'])) {
        $picPath = "uploads/" . $user['profilepic'];
        if (file_exists($picPath)) {
            $profilepic = $user['profilepic'];
        }
    }

    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] != UPLOAD_ERR_NO_FILE) {
            $target_dir = "uploads/";

            // Get file details
            $file_name = basename($_FILES["profile_pic"]["name"]);
            $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_types = ["jpg", "jpeg", "png", "gif","jfif"];
            $file_size = $_FILES["profile_pic"]["size"];

            // Validate file type
            if (!in_array($file_type, $allowed_types)) {
                echo "<script>alert('Error: Only JPG, JPEG, PNG , JFIF & GIF files are allowed.');</script>";
            }
            // Validate file size (max 2MB)
            else if ($file_size > 2 * 1024 * 1024) { 
                echo "<script>alert('Error: File size should not exceed 2MB.');</script>";
            }
            else {
                // Rename file to prevent overwrites (Example: user_123456789.jpg)
                $new_file_name = "user_" . time() . "." . $file_type;
                $target_file = $target_dir . $new_file_name;

                // Move uploaded file to "uploads" folder
                if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
                    // Update profile picture in database - changed column name to lowercase
                    $query = "UPDATE user SET profilepic = '$new_file_name' WHERE username = '$username'";
                    mysqli_query($conn, $query);

                    // Update session with new image
                    $_SESSION['profilepic'] = $new_file_name;
                    echo "<script>alert('Profile picture updated successfully!'); window.location.href='edit.php';</script>";
                } else {
                    echo "<script>alert('Error uploading file.');</script>";
                }
            }
        } else {
            $lastname = $_POST['lastname'];
            $firstname = $_POST['firstname'];
            $midname = $_POST['midname'];
            $year_level = $_POST['year_level'];

            // Update user information in the database - fixed column name capitalization
            $query = "UPDATE user SET Lastname = '$lastname', Firstname = '$firstname', Midname = '$midname', year_level = '$year_level' WHERE username = '$username'";
            $update_result = mysqli_query($conn, $query);

            if ($update_result) {
                echo "<script>alert('Profile updated successfully!'); window.location.href='edit.php';</script>";
            } else {
                echo "<script>alert('Error updating profile: " . mysqli_error($conn) . "');</script>";
            }
        }
    }
?>
    <div class="profile-pic" style="text-align: center;">
        <img src="uploads/<?php echo htmlspecialchars($profilepic); ?>" alt="Profile Picture" 
            onerror="this.src='uploads/default.png';" style="max-width: 200px; height: auto; border-radius: 50%;" />
    </div>
    <form action="" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="profile_pic">Change Profile Picture</label>
            <input type="file" id="profile_pic" name="profile_pic" accept="image/*">
        </div>
        <div class="form-group">
            <label for="firstname">First Name</label>
            <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($user['Firstname']); ?>" required>
        </div>
        <div class="form-group">
            <label for="midname">Middle Name</label>
            <input type="text" id="midname" name="midname" value="<?php echo htmlspecialchars($user['Midname']); ?>" required>
        </div>
        <div class="form-group">
            <label for="lastname">Last Name</label>
            <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($user['Lastname']); ?>" required>
        </div>
        <div class="form-group">
            <label for="year_level">Year Level</label>
            <select id="year_level" name="year_level" required>
                <option value="" disabled>Select Year Level</option>
                <?php
                for ($i = 1; $i <= 4; $i++) {
                    $selected = ($user['year_level'] == $i) ? 'selected' : '';
                    echo "<option value='$i' $selected>$i</option>";
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <input type="submit" value="Update Profile" class="edit-button">
        </div>
    </form>
<?php
} else {
    echo "<script>alert('No user data found');</script>";
?>
    <div class="profile-pic">
        <img src="uploads/default.png" alt="Default Profile Picture" style="max-width: 200px; height: auto;" />
    </div>
<?php
}
?>
    </div>
</body>
</html>

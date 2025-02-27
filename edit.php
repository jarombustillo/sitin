<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }
        .container-fluid {
            flex: 1;
        }
        .offcanvas {
            width: 250px;
        }
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
        }
        .sidebar-sticky {
            position: -webkit-sticky;
            position: sticky;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        .navbar-toggler {
            position: absolute;
            top: 0;
            right: 10px;
            left: 600px;
        }

    </style>
<body>
            <nav class="navbar navbar-dark bg-dark">
                <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
                    <span class="navbar-toggler-icon" style="filter: invert(100%);"></span>
                </button>
            </nav>

            <!-- Sidebar -->
            <div class="offcanvas offcanvas-start bg-light" tabindex="-1" id="sidebarMenu">
                <div class="offcanvas-header">
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
                </div>
                <div class="offcanvas-body">
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link" href="dashboard.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="edit.php">Edit Profile</a></li>
                        <li class="nav-item"><a class="nav-link" href="view_remaining_system.php">View Remaining Session</a></li>
                        <li class="nav-item"><a class="nav-link" href="history.php">History</a></li>
                        <li class="nav-item"><a class="nav-link" href="reservation.php">Reservation</a></li>
                        <li class="nav-item"><a class="nav-link" href="login.php" onclick="return confirm('Are you sure you want to log out?');">Logout</a></li>
                    </ul>
                </div>
            </div>
    <div class="profile-container">
        <div></div>    
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
            $allowed_types = ["jpg", "jpeg", "png", "gif"];
            $file_size = $_FILES["profile_pic"]["size"];

            // Validate file type
            if (!in_array($file_type, $allowed_types)) {
                echo "<script>alert('Error: Only JPG, JPEG, PNG & GIF files are allowed.');</script>";
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
    <div class="profile-pic text-center">
        <img src="uploads/<?php echo htmlspecialchars($profilepic); ?>" alt="Profile Picture" 
            onerror="this.src='uploads/default.png';" class='rounded-circle mb-3' width='100' height='100' alt='Profile Picture' />
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
            <input type="text" id="midname" name="midname" value="<?php echo htmlspecialchars($user['Midname']); ?>">
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
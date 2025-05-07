<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #212529 !important;
        }
        .profile-container {
            max-width: 600px;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .profile-pic img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #007bff;
        }
        .edit-button {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
        }
        .edit-button:hover {
            background-color: #0056b3;
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
        
        
        

    </style>
<body>
<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">Student</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link active" href="edit.php">Edit Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="history.php">History</a></li>
                    <li class="nav-item"><a class="nav-link" href="reservation.php">Reservation</a></li>
                    <li class="nav-item"><a class="nav-link" href="view_lab_resources.php">Lab Resources</a></li>
                </ul>
                <<a href="login.php?logout=true" class="logout-btn ms-auto">Log out</a>
            </div>
        </div>
    </nav>
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
            $allowed_types = ["jpg", "jpeg", "png", "gif", "jfif"];
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
            $course = $_POST['course'];

            // Update user information in the database - fixed column name capitalization
            $query = "UPDATE user SET Lastname = '$lastname', Firstname = '$firstname', Midname = '$midname', course = '$course', year_level = '$year_level' WHERE username = '$username'";
            $update_result = mysqli_query($conn, $query);

            if ($update_result) {
                echo "<script>alert('Profile updated successfully!'); window.location.href='edit.php';</script>";
            } else {
                echo "<script>alert('Error updating profile: " . mysqli_error($conn) . "');</script>";
            }
        }
    }
?>
    <form action="" method="post" enctype="multipart/form-data" class="container mt-4" style="max-height: 500px; overflow-y: auto;">
    <div class="text-center mb-3">
        <img src="uploads/<?php echo htmlspecialchars($profilepic); ?>" alt="Profile Picture" 
        onerror="this.src='uploads/default.png';" class="rounded-circle border shadow" width="120" height="120">
    </div>

    <div class="mb-3">
            <label for="profile_pic" class="form-label">Change Profile Picture</label>
            <input type="file" id="profile_pic" name="profile_pic" class="form-control" accept="image/*">
        </div>

        <div class="mb-3">
            <label for="idno" class="form-label">ID Number</label>
            <input type="text" id="idno" name="idno" class="form-control" 
                value="<?php echo htmlspecialchars($user['IDNO']); ?>" readonly>
        </div>

        <div class="mb-3">
            <label for="firstname" class="form-label">First Name</label>
            <input type="text" id="firstname" name="firstname" class="form-control" 
                value="<?php echo htmlspecialchars($user['Firstname']); ?>" required>
        </div>

        <div class="mb-3">
            <label for="midname" class="form-label">Middle Name</label>
            <input type="text" id="midname" name="midname" class="form-control" 
                value="<?php echo htmlspecialchars($user['Midname']); ?>">
        </div>

        <div class="mb-3">
            <label for="lastname" class="form-label">Last Name</label>
            <input type="text" id="lastname" name="lastname" class="form-control" 
                value="<?php echo htmlspecialchars($user['Lastname']); ?>" required>
        </div>

        <div class="mb-3">
        <label for="course" class="form-label">Course</label>
        <select id="course" name="course" class="form-control" required>
            <option value="" disabled>Select Course</option>
            <option value="BSIT" <?php echo ($user['course'] == 'BSIT') ? 'selected' : ''; ?>>BSIT</option>
            <option value="BSCS" <?php echo ($user['course'] == 'BSCS') ? 'selected' : ''; ?>>BSCS</option>
            <option value="BSCPE" <?php echo ($user['course'] == 'BSCPE') ? 'selected' : ''; ?>>BSCPE</option>
        </select>
    </div>


        <div class="mb-3">
            <label for="year_level" class="form-label">Year Level</label>
            <select id="year_level" name="year_level" class="form-select" required>
                <option value="" disabled>Select Year Level</option>
                <?php
                for ($i = 1; $i <= 4; $i++) {
                    $selected = ($user['year_level'] == $i) ? 'selected' : '';
                    echo "<option value='$i' $selected>$i</option>";
                }
                ?>
            </select>
        </div>

        <div class="text-center">
            <button type="submit" class="btn btn-primary">Save Changes</button>
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
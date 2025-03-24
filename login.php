<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CSS Sitin Monitoring System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="login-container">
        <img src="uc-removebg-preview.png" alt="UC" style="width: 80px; height: 80px;">
        <img src="ccs-removebg-preview.png" alt="CCS" style="width:80px; height: 80px;">
        <h1 class="login-title">CSS Sitin Monitoring System</h1>
        <form action="login.php" method="POST">
            <div class="form-group">
                <input type="text" id="username" name="username" placeholder="Username" required>
            </div>
            <div class="form-group">
                <input type="password" id="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit" class="login-button">Login</button>
            <div class="signup-link">
                <a href="register.php">Register</a>
            </div>
        </form>
    </div>
    <script>
    // Add a simple drop-in animation for the login container
    document.addEventListener('DOMContentLoaded', function() {
        const loginContainer = document.querySelector('.login-container');
        loginContainer.style.transform = 'translateY(-50px)';
        loginContainer.style.opacity = '0';
        loginContainer.style.transition = 'transform 1s ease-in-out, opacity 1s ease-in-out';
        setTimeout(() => {
            loginContainer.style.transform = 'translateY(0)';
            loginContainer.style.opacity = '1';
        }, 10);
    });
</script>
</body>
</html>

<?php

session_start();
include "connect.php";

// Define admin credentials
$admin_username = "admin";
$admin_password = "admin123"; // Replace with a strong password

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if admin login
    if ($_POST['username'] == $admin_username && $_POST['password'] == $admin_password) {
        $_SESSION['is_admin'] = true;
        header("Location: admin.php");
        exit();
    }

    // Regular user login
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    // Query to check username
    $sql = "SELECT * FROM user WHERE username = '$username'";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        // Verify the password
        if (password_verify($password, $user['password'])) {
             // Start session and store user data
             $_SESSION['username'] = $username;
             $_SESSION['IDNO'] = $user['IDNO'];
             $_SESSION['Firstname'] = $user['Firstname'];
             $_SESSION['Lastname'] = $user['Lastname'];
             
             // Redirect to dashboard
             header("Location: dashboard.php");
            exit();
        } else {
            echo "<script>alert('Invalid username or password');</script>";
        }
    } else {
        echo "<script>alert('Invalid username or password');</script>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="register-container">
        <h1 class="register-title">Register</h1>
        <form action="register.php" method="POST" id="registrationForm"> 
            <div class="form-group">
                <input type="text" id="IDNO" name="IDNO" placeholder="IDNO" required>
            </div>
            <div class="form-group">
                <input type="text" id="Lastname" name="Lastname" placeholder="Lastname" required>
            </div>
            <div class="form-group">
                <input type="text" id="Firstname" name="Firstname" placeholder="Firstname" required>
            </div>
            <div class="form-group">
                <input type="text" id="Midname" name="Midname" placeholder="Midname">
            </div>
            <div class="form-group">
                <select id="course" name="course" required>
                    <option value="" disabled selected>Course</option>
                    <option value="BSIT">BSIT</option>
                    <option value="BSCS">BSCS</option>
                    <option value="BSCPE">BSCPE</option>
                </select>
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
                <input type="text" id="username" name="username" placeholder="Username" required>
            </div>
            <div class="form-group">
                <input type="password" id="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit" class="register-button">Register</button>
            <div class="login-link">
                <a href="login.php">Back to Login</a>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('registrationForm').onsubmit = function(e) {
            const inputs = this.querySelectorAll('input[required], select[required]');
            let allFilled = true;
    
            inputs.forEach(input => {
                if (!input.value) {
                allFilled = false;
            }
        });
    
    if (!allFilled) {
        e.preventDefault();
        alert('Please fill in all required fields.');
        return false;
    }
    
        if (!confirm('Are you sure you want to register?')) {
            e.preventDefault();
            return false;
        }
    
        return true;
    };
    // Add a simple drop-in animation for the registration container
    document.addEventListener('DOMContentLoaded', function() {
        const registerContainer = document.querySelector('.register-container');
        registerContainer.style.transform = 'translateY(-50px)';
        registerContainer.style.opacity = '0';
        registerContainer.style.transition = 'transform 1s ease-in-out, opacity 1s ease-in-out';
        setTimeout(() => {
            registerContainer.style.transform = 'translateY(0)';
            registerContainer.style.opacity = '1';
        }, 10);
    });
    </script>
</body>
</html>

<?php
include "connect.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $IDNO = mysqli_real_escape_string($conn, $_POST['IDNO']);
    $Lastname = mysqli_real_escape_string($conn, $_POST['Lastname']);
    $Firstname = mysqli_real_escape_string($conn, $_POST['Firstname']);
    $Midname = mysqli_real_escape_string($conn, $_POST['Midname']);
    $course = mysqli_real_escape_string($conn, $_POST['course']);
    $year_level = mysqli_real_escape_string($conn, $_POST['year_level']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $sql = "INSERT INTO user (IDNO, Lastname, Firstname, Midname, course, year_level, username, password, session_count) 
        VALUES ('$IDNO', '$Lastname', '$Firstname', " . ($Midname ? "'$Midname'" : "''") . ", '$course', '$year_level', '$username', '$hashedPassword', 30)";

    $checkIDNO = "SELECT * FROM user WHERE IDNO='$IDNO'";
    $resultIDNO = $conn->query($checkIDNO);
    if ($resultIDNO->num_rows > 0) {
        echo "<script>alert('IDNO already exists. Please use another one.');</script>";
        exit();
    }
    
    $checkUser = "SELECT * FROM user WHERE username='$username'";
    $result = $conn->query($checkUser);
    if ($result->num_rows > 0) {
        echo "<script>alert('Username already exists. Please choose another one.');</script>";
    } else {
        // Proceed to registration
    }

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Account Created Successfully!');</script>";
        echo "<script>window.location = 'login.php';</script>";
    } else {
        echo "<script>alert('Registration Failed');</script>";
    }

}
?>
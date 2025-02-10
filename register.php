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
            <button type="button" onclick="confirmRegistration()" class="register-button">Register</button>
            <div class="login-link">
                <a href="login.php">Back to Login</a>
            </div>
        </form>
    </div>

    <script>
        function confirmRegistration() {
            const form = document.getElementById('registrationForm');
            const inputs = form.querySelectorAll('input[required], select[required]');
            let allFilled = true;
            
            inputs.forEach(input => {
                if (!input.value) {
                    allFilled = false;
                }
            });
            
            if (!allFilled) {
                alert('Please fill in all required fields.');
                return;
            }
            
            const isConfirmed = confirm;
            
            if (isConfirmed) {
                // Store the registration data
                const username = document.getElementById('username').value;
                const password = document.getElementById('password').value;
                
                // Save to localStorage
                localStorage.setItem('registeredUsername', username);
                localStorage.setItem('registeredPassword', password);
                
                alert('Registration successful!');
                window.location.href = 'login.php'; // Redirect to login page
            }
        }
    </script>
</body>
</html>

<?php
    include ("connect.php");

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

        $sql = "INSERT INTO user(IDNO, Lastname, Firstname, Midname, course, year_level, username, password) 
                VALUES ('$IDNO', '$Lastname', '$Firstname', '$Midname', '$course', '$year_level', '$username', '$hashedPassword')";

        if ($conn->query($sql) === TRUE) {
            echo "Account Created!";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }   
?>
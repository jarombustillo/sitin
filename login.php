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
        <form action="connect.php" method="POST" id="loginForm">
            <div class="form-group">
                <input type="text" id="username" name="username" placeholder="Username" required>
            </div>
            <div class="form-group">
                <input type="password" id="password" name="password" placeholder="Password" required>
            </div>
            <button type="button" onclick="validateLogin()" class="login-button">Login</button>
            <div class="signup-link">
                <a href="register.php">Register</a>
            </div>
        </form>
    </div>

    <script>
        function getUrlParams() {
            const params = new URLSearchParams(window.location.search);
            return {
                username: params.get('username'),
                password: params.get('password')
            };
        }

        function validateLogin() {
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            const storedUsername = localStorage.getItem('registeredUsername');
            const storedPassword = localStorage.getItem('registeredPassword');

            if (!username || !password) {
                alert('Please fill in all fields');
                return;
            }

            if (username === storedUsername && password === storedPassword) {
                alert('Login successful!');
                window.location.href = 'dashboard.html';
            } else {
                alert('Invalid username or password');
            }
        }

        function storeRegistrationData(username, password) {
            localStorage.setItem('registeredUsername', username);
            localStorage.setItem('registeredPassword', password);
        }
    </script>
</body>
</html>

<?php
session_start();
include("connect.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM user WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['username'] = $user['username'];
            $_SESSION['loggedin'] = true;
            header("Location: dashboard.php");
            exit();
        } else {
            // Invalid password
            echo "Invalid username or password.";
        }
    } else {
        // User not found
        echo "Invalid username or password.";
    }

    $stmt->close();
}

$conn->close();
?>

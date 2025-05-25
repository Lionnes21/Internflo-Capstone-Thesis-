<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Database connection
    $db_host = "localhost";
    $db_user = "root";
    $db_pass = "";
    $db_name = "student_registration";

    // Create connection
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    // Prepare the SQL statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT advisor_id, email, password FROM m_advisors WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        
        // Verify password (assuming passwords are hashed in database)
        if (password_verify($password, $row['password'])) {
            // Password is correct, start a new session
            $_SESSION['advisor_id'] = $row['advisor_id'];
            $_SESSION['email'] = $row['email'];
            
            // Redirect to dashboard
            header("Location: InsDashboard.php");
            exit();
        } else {
            $error_message = "Invalid email or password";
        }
    } else {
        $error_message = "Invalid email or password";
    }
    
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advisor Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="advisorlogin.css">
</head>
<body>
    <div class="login-container">
        <div class="widthcont">
            <h1>Welcome!</h1>
            <p>Please enter your credentials to log in</p>
            <span style="display: block; width: 100%; height: 0.5px; background-color: #ddd;"></span>
            <br>
            <?php if(isset($error_message)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <input type="email" name="email" placeholder="Email" required>
                <div class="password-container">
                    <input type="password" id="password" name="password" placeholder="Password" required>
                    <i class="toggle-password fas fa-eye-slash"></i>
                </div>
                <div class="remember-forgot">
                    <label style="font-size: 14px; color: #666;">
                        <input type="checkbox" name="remember"> Remember Me
                    </label>
                    <a href="#" class="forgot-password">Forgot Password</a>
                </div>
                <button type="submit" class="login-button">Login</button>
            </form>
            <div class="divider">
                <span>Or</span>
            </div>
            <div class="student-login">
                Sign in as a Student? <a href="../STUDENTCOORLOG/login.php" style="text-decoration: none; color: #0000EE;">Click here</a>
            </div>
        </div>
    </div>

    <script>
        document.querySelector('.toggle-password').addEventListener('click', function() {
            const passwordInput = document.querySelector('#password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>
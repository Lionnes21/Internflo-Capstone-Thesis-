<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Database connection
$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "monitoring";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = isset($_POST['email']) ? $_POST['email'] : null;
    $username = isset($_POST['username']) ? $_POST['username'] : null;
    $password = $_POST['password'];

    // Check if user is a student (username is provided)
    if (!empty($username)) {
        // Prepare SQL query to fetch the user by username
        $stmt = $conn->prepare("SELECT * FROM student WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        // Check if student exists
        if ($result->num_rows == 1) {
            // Fetch the student data
            $user = $result->fetch_assoc();

            // Verify the password
            if (password_verify($password, $user['password'])) {
                // Store student information in session
                $_SESSION['role'] = 'student';
                $_SESSION['username'] = $user['username'];
                $_SESSION['student_id'] = $user['student_id'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];

                // Redirect to the student dashboard
                header("Location: std_dashboard.php");
                exit();
            } else {
                echo "Incorrect password!";
            }
        } else {
            echo "Username not found!";
        }
    }
    // Check if user is an adviser (email is provided)
    elseif (!empty($email)) {
        // Prepare SQL query to fetch user by email from the 'advisors' table
        $stmt_adviser = $conn->prepare("SELECT * FROM advisors WHERE email = ?");
        $stmt_adviser->bind_param("s", $email);
        $stmt_adviser->execute();
        $result_adviser = $stmt_adviser->get_result();

        // Check if adviser exists
        if ($result_adviser->num_rows == 1) {
            // Fetch the adviser data
            $user = $result_adviser->fetch_assoc();

            // Verify the password
            if (password_verify($password, $user['password'])) {
                // Store adviser information in session
                $_SESSION['role'] = 'advisors';
                $_SESSION['email'] = $user['email'];
                $_SESSION['adviser_id'] = $user['advisor_id'];  // Use 'advisor_id'
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['middle_initial'] = $user['middle_initial'];
                $_SESSION['suffix'] = $user['suffix'];

                // Redirect to the adviser dashboard
                header("Location: ADVISER/InsDashboard.php");
                exit();
            } else {
                echo "Incorrect password for adviser!";
            }
        } else {
            echo "Email not found!";
        }
    }
}

// Close the statements and connection
if (isset($stmt)) $stmt->close();
if (isset($stmt_adviser)) $stmt_adviser->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>
    <form action="login.php" method="POST">
        <label for="username">Username (for students):</label>
        <input type="text" name="username"><br><br>

        <label for="email">Email (for advisers):</label>
        <input type="email" name="email"><br><br>

        <label for="password">Password:</label>
        <input type="password" name="password" required><br><br>

        <button type="submit">Login</button>
    </form>
</body>
</html>

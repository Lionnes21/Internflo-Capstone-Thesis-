<?php
// Enable error reporting to help with debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$servername = "localhost"; // or the server where your database is hosted
$username = "root"; // your MySQL username
$password = ""; // your MySQL password
$dbname = "monitoring";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $student_id = $_POST['student_id'];
    $last_name = $_POST['last_name'];
    $first_name = $_POST['first_name'];
    $middle_initial = $_POST['middle_initial'];
    $years = $_POST['years'];
    $course = $_POST['course'];
    $section = $_POST['section'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password

    // Check if the username already exists
    $result = $conn->query("SELECT * FROM student WHERE username = '$username'");
    if ($result->num_rows > 0) {
        echo "Username already exists!";
    } else {
        // Prepare and bind SQL statement
        $stmt = $conn->prepare("INSERT INTO student (student_id, last_name, first_name, middle_initial, years, course, section, username, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssss", $student_id, $last_name, $first_name, $middle_initial, $years, $course, $section, $username, $password);

        // Execute statement
        if ($stmt->execute()) {
            echo "Registration successful!";
        } else {
            echo "Error: " . $stmt->error;
        }

        // Close statement
        $stmt->close();
    }

    // Close connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration Form</title>
</head>
<body>
    <h2>Student Registration</h2>
    <form action="register.php" method="POST">
        <label for="student_id">Student ID:</label>
        <input type="text" name="student_id" required><br><br>

        <label for="last_name">Last Name:</label>
        <input type="text" name="last_name" required><br><br>

        <label for="first_name">First Name:</label>
        <input type="text" name="first_name" required><br><br>

        <label for="middle_initial">Middle Initial:</label>
        <input type="text" name="middle_initial" maxlength="1"><br><br>

        <label for="years">Years:</label>
        <input type="text" name="years" required><br><br>

        <label for="course">Course:</label>
        <input type="text" name="course" required><br><br>

        <label for="section">Section:</label>
        <input type="text" name="section" required><br><br>

        <label for="username">Username:</label>
        <input type="text" name="username" required><br><br>

        <label for="password">Password:</label>
        <input type="password" name="password" required><br><br>

        <button type="submit">Register</button>
        
    </form>
</body>
</html>

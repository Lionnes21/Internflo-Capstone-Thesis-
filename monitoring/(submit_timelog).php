<?php
session_start(); 

// Database connection for the "monitoring" system
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "student_registration";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the user is logged in
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    // Fetch student information from the "monitoring" database using the logged-in user ID
    $stmt = $conn->prepare("SELECT first_name, last_name, name, student_id FROM students WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();

    if ($student) {
        // Check if first_name or last_name are empty
        if (empty($student['first_name']) && empty($student['last_name'])) {
            // Use the "name" column as a fallback if both first_name and last_name are empty
            $fullName = $student['name'];
        } else {
            // Concatenate first name and last name only
            $fullName = $student['first_name'] . " " . $student['last_name'];
        }
        $studentId = $student['student_id'];  // Use the student_id from the "monitoring" DB
    } else {
        // Fallback values in case no user data is found
        $fullName = "Student Name";
        $studentId = "Student ID";
    }
} else {
    // Fallback values if no user is logged in
    $fullName = "Student Name";
    $studentId = "Student ID";
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $date = $_POST['date'];
    $time_in = $_POST['time-in'];
    $break_time = $_POST['break-time'];
    $time_out = $_POST['time-out'];
    $activity = $_POST['activity'];

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO m_timelog (date, time_in, break_time, time_out, activity_description, student_id, student_name) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssiss", $ $date, $time_in, $break_time, $time_out, $activity, $studentId, $fullName);

    // Execute the statement
    if ($stmt->execute()) {
        echo "New timelog entry created successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close the connection
    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request method.";
}
?>

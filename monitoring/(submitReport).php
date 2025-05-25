<?php
$servername = "localhost"; // your server name
$username = "root"; // your database username
$password = ""; // your database password
$dbname = "monitoring"; // your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$week = $_POST['week'];
$submit_to = $_POST['submit_to'];
$student_id = 'example_student_id'; // Replace with actual student ID logic

// Handle file upload
$report_file = $_FILES['report']['name'];
$target_dir = "weeklyReport_uploads/"; // Specify your upload directory
$target_file = $target_dir . basename($report_file);

if (move_uploaded_file($_FILES['report']['tmp_name'], $target_file)) {
    // Prepare SQL statement
    $stmt = $conn->prepare("INSERT INTO ojt_reports (student_id, week, report_file, submit_to) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $student_id, $week, $report_file, $submit_to);

    // Execute the statement
    if ($stmt->execute()) {
        echo "Report submitted successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close the statement
    $stmt->close();
} else {
    echo "Error uploading file.";
}

// Close the connection
$conn->close();
?>

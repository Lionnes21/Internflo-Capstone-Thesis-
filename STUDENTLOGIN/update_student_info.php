<?php
session_start();
include 'config.php'; // Include database connection

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    // Get the updated values from the form
    $course = $_POST['course'];
    $schoolYear = $_POST['school_year'];
    $studentNumber = $_POST['student_id'];

    // Prepare the SQL statement to update the student info
    $sql = "UPDATE students SET course = ?, school_year = ?, student_id = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssi', $course, $schoolYear, $studentNumber, $userId);

    // Execute the statement and check if it was successful
    if ($stmt->execute()) {
        // Redirect back to the profile page or show a success message
        header("Location: studentprofile.php");
        exit();
    } else {
        echo "Error updating student information: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
} else {
    // Redirect to login page if the user is not logged in
    header("Location: studentprofile.php");
    exit();
}
?>

<?php
session_start();
include 'config.php';

if (isset($_SESSION['user_id']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_SESSION['user_id'];

    // Validate and sanitize input
    $firstName = htmlspecialchars($_POST['first_name']);
    $lastName = htmlspecialchars($_POST['last_name']);
    $email = htmlspecialchars($_POST['email']);
    $phone = htmlspecialchars($_POST['mobile_number']);
    $yearSection = htmlspecialchars($_POST['school_year']);
    $studentNumber = htmlspecialchars($_POST['student_id']);
    $course = htmlspecialchars($_POST['course']);

    // Prepare the SQL statement
    $sql = "UPDATE students SET 
            first_name = ?, 
            last_name = ?, 
            email = ?, 
            mobile_number = ?, 
            school_year = ?, 
            student_id = ?, 
            course = ? 
            WHERE id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssssssi', 
        $firstName, 
        $lastName, 
        $email, 
        $phone, 
        $yearSection, 
        $studentNumber, 
        $course, 
        $userId
    );

    if ($stmt->execute()) {
        $_SESSION['message'] = "Profile updated successfully";
    } else {
        $_SESSION['error'] = "Error updating profile: " . $stmt->error;
    }

    header("Location: student-profile.php");
    exit();
} else {
    echo "You must be logged in to update your profile.";
}
?>

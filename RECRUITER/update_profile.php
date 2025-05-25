<?php
session_start();
include 'config.php';

if (isset($_SESSION['user_id']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_SESSION['user_id'];
    
    // Validate and sanitize input
    $firstName = htmlspecialchars($_POST['first_name']);
    $lastName = htmlspecialchars($_POST['last_name']);
    $email = htmlspecialchars($_POST['email']);
    $mobileNumber = htmlspecialchars($_POST['mobile_number']);
    
    // First try to update in approvedrecruiters table
    $sql = "UPDATE approvedrecruiters SET 
            first_name = ?, 
            last_name = ?, 
            email = ?, 
            mobile_number = ? 
            WHERE id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssi', 
        $firstName,
        $lastName,
        $email,
        $mobileNumber,
        $userId
    );
    
    $success = $stmt->execute();
    
    if (!$success || $stmt->affected_rows === 0) {
        // If no rows were updated in approvedrecruiters, try recruiters table
        $sql = "UPDATE recruiters SET 
                first_name = ?, 
                last_name = ?, 
                email = ?, 
                mobile_number = ? 
                WHERE id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssi', 
            $firstName,
            $lastName,
            $email,
            $mobileNumber,
            $userId
        );
        
        $success = $stmt->execute();
    }

    if ($success) {
        $_SESSION['message'] = "Profile updated successfully";
    } else {
        $_SESSION['error'] = "Error updating profile: " . $stmt->error;
    }

    header("Location: company-profile.php"); // Adjust this to your profile page
    exit();
} else {
    echo "You must be logged in to update your profile.";
}
?>
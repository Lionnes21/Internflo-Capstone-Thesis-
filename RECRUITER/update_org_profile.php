<?php
session_start();
include 'config.php';

if (isset($_SESSION['user_id']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_SESSION['user_id'];
    
    // Validate and sanitize input
    $companyName = htmlspecialchars($_POST['company_name']);
    $companyAddress = htmlspecialchars($_POST['company_address']);
    $companyPhone = htmlspecialchars($_POST['company_phone']);
    $companyWebsite = htmlspecialchars($_POST['company_email']); // Note: field name is company_email but stores website
    
    // First try to update in approvedrecruiters table
    $sql = "UPDATE approvedrecruiters SET 
            company_name = ?, 
            company_address = ?, 
            company_email = ? 
            WHERE id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssi', 
        $companyName,
        $companyAddress,
        $companyWebsite, // Corrected: company_email should be the website
        $userId
    );
    
    $success = $stmt->execute();
    
    if (!$success || $stmt->affected_rows === 0) {
        // If no rows were updated in approvedrecruiters, try recruiters table
        $sql = "UPDATE recruiters SET 
                company_name = ?, 
                company_address = ?, 
                company_phone = ?, 
                company_email = ? 
                WHERE id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssi', 
            $companyName,
            $companyAddress,
            $companyPhone,
            $companyWebsite,
            $userId
        );
        
        $success = $stmt->execute();
    }

    if ($success) {
        $_SESSION['message'] = "Company profile updated successfully";
    } else {
        $_SESSION['error'] = "Error updating profile: " . $stmt->error;
    }

    header("Location: company-org-profile.php"); // Adjust this to your profile page
    exit();
} else {
    echo "You must be logged in to update your profile.";
}
?>
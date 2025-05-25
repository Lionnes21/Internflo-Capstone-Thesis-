<?php
session_start();
require 'config.php';

// Check if user is properly verified
if (!isset($_SESSION['temp_verified_recruiter']) || !isset($_SESSION['phone_verified']) || $_SESSION['phone_verified'] !== true) {
    header("Location: companysignin.php");
    exit();
}

try {
    // Log successful verification
    error_log("User successfully verified: " . $_SESSION['temp_verified_user']['email']);
    
    // Create success message
    $_SESSION['registration_success'] = "Your account has been successfully verified. Please log in.";
    
    // Clear verification sessions
    unset($_SESSION['temp_verified_user']);
    unset($_SESSION['phone_verified']);
    unset($_SESSION['email_verified']);
    
    // Redirect to login page
    header("Location: companysignin.php");
    exit();

} catch (Exception $e) {
    error_log("Error in final verification step: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred during the final verification step. Please contact support.";
    header("Location: companysignin.php");
    exit();
}
?>
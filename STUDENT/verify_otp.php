<?php
session_start();
require 'config.php';

error_log("verify_otp.php loaded. Session data: " . print_r($_SESSION, true));

// Check if user is properly verified
if (!isset($_SESSION['temp_verified_user']) || !isset($_SESSION['phone_verified']) || $_SESSION['phone_verified'] !== true) {
    error_log("Verification check failed in verify_otp.php: " . 
              "temp_verified_user set: " . (isset($_SESSION['temp_verified_user']) ? 'yes' : 'no') . ", " .
              "phone_verified set: " . (isset($_SESSION['phone_verified']) ? 'yes' : 'no') . ", " .
              "phone_verified value: " . (isset($_SESSION['phone_verified']) ? $_SESSION['phone_verified'] : 'N/A'));
              
    header("Location: ../STUDENTCOORLOG/login.php");
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
    header("Location: ../STUDENTCOORLOG/login.php");
    exit();

} catch (Exception $e) {
    error_log("Error in final verification step: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred during the final verification step. Please contact support.";
    header("Location: ../STUDENTCOORLOG/login.php");
    exit();
}
?>
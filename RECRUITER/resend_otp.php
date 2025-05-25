<?php
session_start();
require 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['temp_verified_user'])) {
    $user = $_SESSION['temp_verified_user'];
    
    // Generate new OTP
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_timestamp'] = time();

    // Format phone number for Semaphore
    $phoneNumber = '63' . substr($user['mobile_number'], 1);

    // Semaphore API configuration
    $apiKey = 'c3c8e83cf2c526850b168a57416cde0e'; // Replace with your actual API key
    $message = "Your new OTP verification code is: $otp. Valid for 5 minutes.";

    // Send OTP via Semaphore API
    $ch = curl_init();
    $parameters = [
        'apikey' => $apiKey,
        'number' => $phoneNumber,
        'message' => $message,
        'sendername' => 'Internflo'
    ];
    
    curl_setopt($ch, CURLOPT_URL, 'https://api.semaphore.co/api/v4/messages');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        echo json_encode(['status' => 'success', 'message' => 'New OTP sent successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to send new OTP']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
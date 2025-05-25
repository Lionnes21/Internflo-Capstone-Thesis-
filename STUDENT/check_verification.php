<?php
session_start();
require 'config.php';

header('Content-Type: application/json');

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Check if the token exists and is verified in the database
    $stmt = $conn->prepare("SELECT * FROM unverified_users WHERE verification_token = ? AND email_verified = 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Check if user exists and is verified
    if ($user) {
        // Store the user in session for the next step
        $_SESSION['temp_verified_user'] = $user;
        echo json_encode(['verified' => true]);
    } else {
        echo json_encode(['verified' => false]);
    }
} else {
    echo json_encode(['verified' => false, 'error' => 'No token provided']);
}
?>
<?php
session_start();
require 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_email']) || !isset($_SESSION['verification_token'])) {
    echo json_encode(['verified' => false, 'error' => 'Missing session data']);
    exit;
}

try {
    $email = $_SESSION['user_email'];
    $token = $_SESSION['verification_token'];
    
    // Connect to database
    $pdo = new PDO("mysql:host=localhost;dbname=u798912504_internflo", "u798912504_root", "Internfloucc2025*");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if email is verified AND the token matches
    $stmt = $pdo->prepare("SELECT * FROM unverified_recruiters WHERE email = ? AND verification_token = ? AND email_verified = 1");
    $stmt->execute([$email, $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Email is verified and token matches
        $_SESSION['temp_verified_recruiter'] = $user;
        $_SESSION['email_verified'] = true;
        echo json_encode(['verified' => true]);
    } else {
        echo json_encode(['verified' => false]);
    }
} catch (Exception $e) {
    echo json_encode(['verified' => false, 'error' => $e->getMessage()]);
}
?>
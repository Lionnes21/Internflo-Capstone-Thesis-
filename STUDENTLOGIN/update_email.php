<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['email'])) {
        throw new Exception('Email not provided');
    }

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    $email = $_POST['email'];
    $userId = $_SESSION['user_id'];
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM students WHERE email = ? AND id != ?");
    if (!$stmt) {
        throw new Exception('Prepare statement failed: ' . $conn->error);
    }
    
    $stmt->bind_param('si', $email, $userId);
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'exists' => $row['count'] > 0
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
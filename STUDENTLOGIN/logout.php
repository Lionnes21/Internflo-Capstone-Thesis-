<?php
session_start();

// Include database configuration
include 'config.php';

// Clear the remember_me token from database if it exists
if (isset($_SESSION['user_id'])) {
    $updateStmt = $conn->prepare('UPDATE students SET remember_token = NULL WHERE id = ?');
    $updateStmt->bind_param('i', $_SESSION['user_id']);
    $updateStmt->execute();
}

// Clear the remember_me cookie if it exists
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/', '', true, true);
}

// Note: We're NOT clearing the remember_email cookie here so it persists after logout
// If you want to clear it too, uncomment the following:
// if (isset($_COOKIE['remember_email'])) {
//     setcookie('remember_email', '', time() - 3600, '/', '', true, true);
// }

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: ../STUDENTCOORLOG/login.php');
exit;
?>
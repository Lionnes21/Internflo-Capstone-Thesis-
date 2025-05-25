<?php
session_start();
include 'config.php';

if(isset($_GET['code'])) {
    $verificationCode = $_GET['code'];

    $sql = "SELECT * FROM `admin` WHERE otp = ? AND is_verified = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $verificationCode);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows == 1) {
        $sql = "UPDATE `admin` SET is_verified = 1, otp = NULL WHERE otp = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $verificationCode);
        if($stmt->execute()) {
            $_SESSION['message'] = 'Email verification successful. You can now sign in.';
        } else {
            $_SESSION['error'] = 'Verification failed. Please try again later.';
        }
    } else {
        $_SESSION['error'] = 'Invalid verification code or email already verified.';
    }
} else {
    $_SESSION['error'] = 'No verification code provided.';
}

header('Location: signin.php');
exit;
?>

<?php
session_start();
include 'config.php';

$message = '';
$error = '';

if (isset($_GET['code'])) {
    $verificationCode = $_GET['code'];

    $sql = "SELECT * FROM `admin` WHERE otp = ? AND is_verified = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $verificationCode);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $sql = "UPDATE `admin` SET is_verified = 1, otp = NULL WHERE otp = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $verificationCode);
        if ($stmt->execute()) {
            $message = 'You are successfully verified!';
        } else {
            $error = 'Verification failed. Please try again later.';
        }
    } else {
        $error = 'Invalid verification code or email already verified.';
    }
} else {
    $error = 'No verification code provided.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Success</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="container">
    <h2>Email Verification</h2>
    <?php if ($message): ?>
        <p class="success"><?php echo $message; ?></p>
        <a href="signin.php">Sign In</a>
    <?php else: ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>
</div>

</body>
</html>

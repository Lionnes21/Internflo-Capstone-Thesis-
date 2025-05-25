<?php
session_start();
$errors = [];

if (isset($_POST['submit'])) {
    $enteredOtp = $_POST['otp'];

    // Check if the OTP is correct
    if (empty($enteredOtp)) {
        $errors[] = 'OTP is required.';
    } elseif ($enteredOtp != $_SESSION['otp']) {
        $errors[] = 'Invalid OTP. Please try again.';
    } else {
        // OTP is correct, clear the session OTP and log the user in
        unset($_SESSION['otp']); // Clear OTP session
        unset($_SESSION['otp_email']); // Clear OTP email session

        // Redirect to the admin index or dashboard
        header('Location: admin_index.php');
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <title>OTP Verification</title>
</head>
<body>

<div class="container"> 
    <h2>OTP Verification</h2>
    <form action="otp-verification.php" method="post">
        <div class="form-group">
            <input type="text" id="otp" name="otp" placeholder="Enter OTP" required>
        </div>
        <button type="submit" name="submit">Verify OTP</button>
    </form>
    <?php if (!empty($errors)): ?>
        <div class="error-messages">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>

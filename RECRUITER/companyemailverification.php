<?php
session_start();
require 'vendor/autoload.php';
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Retrieve the email verification token from the session
$email_verification_token = $_SESSION['verification_token'] ?? '';
$user_email = $_SESSION['user_email'] ?? '';

if (empty($email_verification_token) || empty($user_email)) {
    die("Verification details are missing. Please try again.");
}

// Send verification email
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username   = 'rogermalabananbusi@gmail.com';
    $mail->Password   = 'fhnt amet zziu tlow';  // Use environment variables or secure storage for credentials
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('rogermalabananbusi@gmail.com', 'Company Registration');
    $mail->addAddress($user_email);
    $mail->isHTML(true);
    $mail->Subject = 'Email Verification';

    $mail->addEmbeddedImage('pics/click.png', 'click_image');
    // Email body with design, background, and link to verify
    $mail->Body = '
    <div style="font-family: Arial, sans-serif; text-align: center; color: #333; max-width: 600px; margin: auto;">
        <h1 style="background-color: #478831; color: white; padding: 10px 0; font-size: 24px;">
            THANK YOU FOR REGISTERING
        </h1>
        <img src="cid:click_image" alt="Click to Verify" style="width: 200px; margin: 20px 0; pointer-events: none;" oncontextmenu="return false;" />
        <p style="font-size: 18px; color: #333;">Please click the button below to verify your email:</p>
        <a href="https://internflo-ucc.com/RECRUITER/company_verify_email.php?token=' . urlencode($email_verification_token) . '" 
            style="background: #478831; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; font-weight: bold;">
            VERIFY EMAIL
        </a>
        <p style="margin-top: 20px; font-size: 14px;">If you didn\'t register, please ignore this email.</p>
    </div>';

    // Send email
    $mail->send();
    $status = "Verification email sent. Please check your inbox.";
} catch (Exception $e) {
    $status = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <link rel="icon" href="pics/ucc.png">
    <link rel="stylesheet" href="../STUDENT/verify_emails.css">
    <style>
        .verify-instruction {

            text-align: center;
            color: #478831;
            font-size: 16px;
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
            background-color: #f8f9fa;
            border-left: 4px solid #478831;
            font-weight: 500;

        }
    </style>
    <script>
        // Check email verification status periodically
        function checkEmailVerification() {
            fetch('check_email_verification.php')
                .then(response => response.json())
                .then(data => {
                    if (data.verified) {
                        window.location.href = 'company_verify_otp.php';
                    }
                })
                .catch(error => console.error('Error checking verification:', error));
        }

        // Check every 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            setInterval(checkEmailVerification, 5000);
        });
    </script>
</head>
<body>
    <fieldset>
        <div class="logo-container">
            <img src="pics/gmail.png" alt="Gmail Logo" width="200" height="200">
            <p class="logo-message">Email Verification</p>
        </div>
        <p class="otp-message"><?php echo htmlspecialchars($status); ?></p>
        <p class="verify-instruction">Once you verify your email, you will be automatically redirected to the next step.</p>
    </fieldset>
</body>
</html>
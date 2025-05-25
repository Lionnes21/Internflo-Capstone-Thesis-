<?php
    session_start();
    require 'vendor/autoload.php'; // Ensure this path is correct
    require 'PHPMailer/src/Exception.php';
    require 'PHPMailer/src/PHPMailer.php';
    require 'PHPMailer/src/SMTP.php';
    require 'config.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    // Retrieve the email verification token from the session
    $email_verification_token = $_SESSION['verification_token'] ?? '';

    if (empty($email_verification_token)) {
        die("No email verification token found.");
    }

    // Retrieve the email associated with the token from the database
    $stmt = $conn->prepare("SELECT email FROM unverified_users WHERE verification_token = ?");
    $stmt->bind_param("s", $email_verification_token);
    $stmt->execute();
    $stmt->bind_result($email);
    $stmt->fetch();
    $stmt->close();

    if (empty($email)) {
        die("Invalid email verification token.");
    }

    // Check if email is already verified (check database, not session)
    $verify_stmt = $conn->prepare("SELECT email_verified FROM unverified_users WHERE verification_token = ?");
    $verify_stmt->bind_param("s", $email_verification_token);
    $verify_stmt->execute();
    $result = $verify_stmt->get_result();
    $verification_status = $result->fetch_assoc();
    $verify_stmt->close();

    if ($verification_status && $verification_status['email_verified'] == 1) {
        // If email is verified, redirect to SMS verification
        header("Location: sms_verification.php");
        exit();
    }

    // Send verification email
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username   = 'rogermalabananbusi@gmail.com';
        $mail->Password   = 'fhnt amet zziu tlow';  // Keep credentials secure
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('rogermalabananbusi@gmail.com', 'UCC INTERNFLO');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification';

        // Email body with design, green background, and colored "flo"
        $mail->Body = '
        <div style="font-family: Open Sans, sans-serif; text-align: center; color: #333; max-width: 600px; margin: auto;">
            <h1 style="background-color: #478831; color: white; padding: 10px 0; margin: 0; font-size: 24px;">
                THANK YOU FOR CHOOSING INTERN<span style="color: #fd6f41;">FLO</span>
            </h1>
            <img src="cid:click_image" alt="Click to Verify" style="width: 200px; margin: 20px 0; pointer-events: none;" oncontextmenu="return false;" />
            <p style="font-size: 18px; color: #333;">Please click the button below to verify your email:</p>
            <a href="https://internflo-ucc.com/STUDENT/verify_email.php?token=' . urlencode($email_verification_token) . '" 
                style="background: #478831; color: white; text-decoration: none; padding: 10px 20px; border-radius: 25px; font-weight: 600;">
                VERIFY EMAIL
            </a>
            <p style="margin-top: 20px; font-size: 14px;">If you did not request this email, please ignore it.</p>
        </div>';

        // Attach image
        $mail->addEmbeddedImage('pics/click.png', 'click_image');

        $mail->send();
        $status = "Verification email sent. Please check your inbox and verify your email.";
    } catch (Exception $e) {
        $status = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="pics/ucclogo2.png">
    <title>UCC - Internflo Verification</title>
    <link rel="stylesheet" href="verify_emails.css">
    <style>
        /* Additional CSS for the new paragraph */
        .redirect-notice {
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
        function checkVerificationStatus() {
            fetch('check_verification.php?token=<?php echo $email_verification_token; ?>')
            .then(response => response.json())
            .then(data => {
                if(data.verified) {
                    // If verified, redirect to SMS verification
                    window.location.href = 'sms_verification.php';
                } else {
                    // Continue checking every 5 seconds
                    setTimeout(checkVerificationStatus, 5000);
                }
            })
            .catch(error => {
                console.error('Error checking verification status:', error);
                // Continue checking despite errors
                setTimeout(checkVerificationStatus, 5000);
            });
        }

        // Start checking when page loads
        window.onload = function() {
            setTimeout(checkVerificationStatus, 5000);
        };
    </script>
</head>
<body>
    <fieldset>
        <div class="logo-container">
            <img src="pics/gmail.png" alt="Gmail Logo" width="200" height="200">
            <p class="logo-message">Email Verification</p>
        </div>
        <p class="otp-message"><?php echo htmlspecialchars($status); ?></p>
        <p class="redirect-notice">Once you verify your email, you will be automatically redirected to the next step.</p>
    </fieldset>
</body>
</html>
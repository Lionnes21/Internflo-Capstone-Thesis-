<?php
    // Start the session
    session_start();

    // Include PHPMailer library
    require 'vendor/autoload.php';
    require 'PHPMailer/src/Exception.php';
    require 'PHPMailer/src/PHPMailer.php';
    require 'PHPMailer/src/SMTP.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    // Handle OTP verification
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['otp'])) {
        // Get the OTP entered by the user
        $otp = implode('', $_POST['otp']); // Combine array into a single string

// Retrieve the email and table name from session
if (isset($_SESSION['email']) && isset($_SESSION['table_name'])) {
    $email = $_SESSION['email'];
    $table_name = $_SESSION['table_name'];

    // Database connection
    $mysqli = new mysqli("localhost", "u798912504_root", "Internfloucc2025*", "u798912504_internflo");

    // Check connection
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }

    // Retrieve the stored OTP from the database
    $stmt = $mysqli->prepare("SELECT otp_code FROM $table_name WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

            if ($row) {
                $stored_code = $row['otp_code'];

                // Check if the entered OTP matches the stored code
                if ($otp === $stored_code) {
                    // OTP is correct, show success message and redirect
                    $success_message = 'OTP Verification confirmed. Redirecting...';
                    header("Refresh:2; url=newpassword.php"); // Redirect after 2 seconds
                } else {
                    // OTP is incorrect, show error message
                    $error_message = 'Incorrect OTP. Please try again.';
                }
            } else {
                // Email not found in the database
                $error_message = 'Verification code not found.';
            }

            $stmt->close();
            $mysqli->close();
        } else {
            // Email not found in session
            $error_message = 'Verification code not found.';
        }
    }

    // Handle Resend OTP
    if (isset($_GET['action']) && $_GET['action'] == 'resend') {
        if (isset($_SESSION['email'])) {
            $email = $_SESSION['email'];

            // Generate a new OTP
            $new_otp = rand(100000, 999999);

            // Update the OTP in the database
            $mysqli = new mysqli("localhost", "u798912504_root", "Internfloucc2025*", "u798912504_internflo");

            if ($mysqli->connect_error) {
                die("Connection failed: " . $mysqli->connect_error);
            }

            $update_stmt = $mysqli->prepare("UPDATE approvedrecruiters SET otp_code = ? WHERE email = ?");
            $update_stmt->bind_param("ss", $new_otp, $email);
            $update_stmt->execute();
            $update_stmt->close();

            // Send the new OTP via email
            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username   = 'rogermalabananbusi@gmail.com';
                $mail->Password   = 'fhnt amet zziu tlow';
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;
            
                $mail->setFrom('rogermalabananbusi@gmail.com', 'Mailer');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Resend Password Reset Verification Code';
                $mail->Body    = "Your new verification code is: <b>$new_otp</b>";

                $mail->send();

                // Success message
                $success_message = "A new OTP has been sent to your email.";
            } catch (Exception $e) {
                $error_message = "Resend failed. Mailer Error: " . $mail->ErrorInfo;
            }

            $mysqli->close();
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UCC - Forgot Password</title>
    <link rel="icon" href="pics/ucc.png">
    <link rel="stylesheet" href="resetcode.css">
</head>
<body>
<fieldset class="otp-container">
    <!-- Logo and message -->
    <div class="logo-wrapper">
        <h1>OTP CODE VERIFICATION</h1>
    </div>

    <!-- Message displaying the OTP details -->
    <p class="otp-info-message">
        We have sent you a verification code <br> to reset your password
    </p>

    <form id="otpForm" action="resetcode.php" method="POST" class="otp-form">
        <input type="hidden" name="phone_number" value="">
        
        <!-- Pin code input boxes -->
        <div class="otp-input-wrapper">
            <input type="number" class="otp-field" name="otp[]" maxlength="1">
            <input type="number" class="otp-field" name="otp[]" maxlength="1">
            <input type="number" class="otp-field" name="otp[]" maxlength="1">
            <input type="number" class="otp-field" name="otp[]" maxlength="1">
            <input type="number" class="otp-field" name="otp[]" maxlength="1">
            <input type="number" class="otp-field" name="otp[]" maxlength="1">
        </div>


        <!-- Error message for empty OTP -->
        <div id="otpErrorMessage" class="error-text" style="display: none;">Please enter OTP</div>

        <button type="submit" id="verifyButton" class="verify-button">Verify OTP <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M472-120q-42-1-102.5-9.5t-116-27.5Q198-176 159-206.5T120-280v-100q0 36 28.5 63.5t71.5 46q43 18.5 94.5 30T411-224q9 29 24.5 55.5T472-120Zm-71-205q-45-5-94.5-16.5t-91-30.5Q174-391 147-417.5T120-480v-100q0 38 31.5 66t78 47.5q46.5 19.5 101 30T430-422q-12 22-19.5 46.5T401-325Zm79-195q-149 0-254.5-47T120-680q0-66 105.5-113T480-840q150 0 255 47t105 113q0 66-105 113t-255 47Zm180 400q-75 0-127.5-52.5T480-300q0-75 52.5-127.5T660-480q75 0 127.5 52.5T840-300q0 26-7.5 50T812-204l80 80q11 11 11 28t-11 28q-11 11-28 11t-28-11l-80-80q-22 13-46 20.5t-50 7.5Zm0-80q42 0 71-29t29-71q0-42-29-71t-71-29q-42 0-71 29t-29 71q0 42 29 71t71 29Z"/></svg></button>
        
        <!-- Error message for incorrect OTP -->
        <?php if (isset($error_message)): ?>
            <p class="error-text"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>

        <!-- Success message for correct OTP -->
        <?php if (isset($success_message)): ?>
            <p class="success-text"><?= htmlspecialchars($success_message) ?></p>
        <?php endif; ?>
    </form>

    <!-- Resend Code Option -->
    <div class="resend-message-container">
        <!-- Placeholder for dynamic success message -->
    </div>
    <p class="resend-info">
        Didn't get the code? 
        <a href="resetcode.php?action=resend" class="resend-link">Click to resend</a>
    </p>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const verifyButton = document.getElementById('verifyButton');
            const otpFields = document.querySelectorAll('.otp-field');
            const otpErrorMessage = document.getElementById('otpErrorMessage');

            // Function to apply error styles
            function applyErrorStyles(inputField) {
                inputField.style.borderColor = "red";
                inputField.style.boxShadow = "0 0 0 0.2rem rgba(255, 0, 0, 0.25)";
            }

            // Function to apply success styles
            function applySuccessStyles(inputField) {
                inputField.style.borderColor = "green";
                inputField.style.boxShadow = "none";
            }

            // Function to clear error styles
            function clearErrorStyles(inputField) {
                inputField.style.borderColor = "";
                inputField.style.boxShadow = "";
            }

            // Function to validate OTP fields
            function validateOTP() {
                let allFilled = true;

                otpFields.forEach(field => {
                    if (field.value.trim() === '') {
                        applyErrorStyles(field);
                        allFilled = false;
                    } else {
                        clearErrorStyles(field);
                    }
                });

                if (allFilled) {
                    otpErrorMessage.style.display = 'none';
                    document.getElementById('otpForm').submit(); // Submit form
                } else {
                    otpErrorMessage.style.display = 'block';
                }
            }

            // Function to move to the next input field
            function moveToNext(current) {
                current.value = current.value.slice(0, 1); // Restrict to 1 digit

                // Move to the next input if a digit was entered
                if (current.value !== '') {
                    const nextField = current.nextElementSibling;
                    if (nextField) {
                        nextField.focus(); // Move to the next input
                    }
                }
            }

            // Add event listener to Verify button
            verifyButton.addEventListener('click', (event) => {
                event.preventDefault(); // Prevent default form submission
                validateOTP(); // Validate OTP on button click
            });

            // Add event listener to handle input events for all fields
            otpFields.forEach(field => {
                field.addEventListener('input', () => {
                    if (field.value.trim() !== '') {
                        applySuccessStyles(field); // Apply success styles
                    } else {
                        clearErrorStyles(field); // Clear error styles
                    }
                    moveToNext(field); // Manage focus
                });

                // Handle backspace to move to the previous input
                field.addEventListener('keydown', (event) => {
                    if (event.key === 'Backspace' && field.value === '') {
                        const prevField = field.previousElementSibling;
                        if (prevField) {
                            prevField.focus(); // Move to previous input
                        }
                    }
                });
            });
        });
    </script>
</fieldset>

<script src="resetcoder.js"></script>
</body>
</html>

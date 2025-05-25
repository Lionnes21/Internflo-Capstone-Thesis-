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

    // Check if the form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = $_POST['email'];

        // Database connection
        $mysqli = new mysqli("localhost", "u798912504_root", "Internfloucc2025*", "u798912504_internflo");

        // Check connection
        if ($mysqli->connect_error) {
            die("Connection failed: " . $mysqli->connect_error);
        }

        // Check if the email exists in the database
$stmt = $mysqli->prepare("SELECT 'approvedrecruiters' as table_name, email FROM approvedrecruiters WHERE email = ? 
                         UNION 
                         SELECT 'recruiters' as table_name, email FROM recruiters WHERE email = ?");
$stmt->bind_param("ss", $email, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Get which table the email belongs to
    $row = $result->fetch_assoc();
    $table_name = $row['table_name'];
    $_SESSION['table_name'] = $table_name; // Store table name in session for later use
    
    // Generate verification code
    $verification_code = rand(100000, 999999); // 6-digit verification code

    // Store verification code in session
    $_SESSION['verification_code'] = $verification_code;
    $_SESSION['email'] = $email; // Store the email for later verification

    // Update the OTP code in the appropriate table
    $update_stmt = $mysqli->prepare("UPDATE $table_name SET otp_code = ? WHERE email = ?");
    $update_stmt->bind_param("ss", $verification_code, $email);
    $update_stmt->execute();
    $update_stmt->close();
            // Send OTP email
            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username   = 'rogermalabananbusi@gmail.com'; // Sender email
                $mail->Password   = 'fhnt amet zziu tlow'; // Sender password
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                $mail->setFrom('rogermalabananbusi@gmail.com', 'UCC INTERNFLO');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Verification Code';

                // OTP email body with similar design
                $mail->Body = '
                <div style="font-family: Open Sans, sans-serif; text-align: center; color: #333; max-width: 600px; margin: auto;">
                    <h1 style="background-color: #478831; color: white; padding: 10px 0; margin: 0; font-size: 24px;">
                        PASSWORD RESET CODE FROM INTERN<span style="color: #fd6f41;">FLO</span>
                    </h1>
                    <p style="font-size: 18px; color: #333; margin-top: 20px;">Here is your verification code to reset your password:</p>
                    <p style="font-size: 24px; color: #fd6f41; font-weight: bold; margin: 10px 0;">' . $verification_code . '</p>
                    <p style="font-size: 14px; color: #333; margin-top: 20px;">
                        Please enter this code in the password reset form to continue. If you did not request a password reset, you can safely ignore this email.
                    </p>
                </div>';

                // Send email
                $mail->send();

                // Redirect to reset code verification page
                header('Location: resetcode.php');
                exit();
            } catch (Exception $e) {
                // Redirect to resetcode.php with error message
                header('Location: resetcode.php?status=Email could not be sent. Mailer Error: ' . $mail->ErrorInfo);
                exit();
            }
        } else {
            // Redirect to resetcode.php with error message
            header('Location: resetcode.php?status=Email not found in our database');
            exit();
        }

        $stmt->close();
        $mysqli->close();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UCC - Forgot Password</title>
    <link rel="icon" href="pics/ucc.png">
    <link rel="stylesheet" href="forgotpassword.css">
    <link rel="stylesheet" href="../css/NAV.css">
    <link rel="stylesheet" href="../css/FOOTER.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Font Awesome -->
</head>
<body>
<div class="main-container">
    <div class="content-wrapper">
        <!-- Forgot Password Section -->
        <fieldset class="form-section">
            <div class="form-header">
                <h1>FORGOT PASSWORD VERIFICATION</h1>
                <p>Please enter your email address to reset your account</p>
            </div>
            <form name="resetForm" action="forgotpassword.php" method="POST">
                <div class="form-row">
                    <div class="form-input-group">
                        <input type="text" name="email" id="email" placeholder="Enter Email">
                        <div id="emailError" class="error-message" style="display: none; font-size: 15px; margin-top: 8px; color: red"></div>
                    </div>
                </div>
                <button class="submit-button" type="submit">Send Code <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M767-160H640q-17 0-28.5-11.5T600-200q0-17 11.5-28.5T640-240h127l-36-36q-12-12-11.5-28t12.5-28q12-11 28-11.5t28 11.5l104 104q6 6 9 13t3 15q0 8-3 15t-9 13L788-68q-11 11-27.5 11.5T732-68q-11-11-11-28t11-28l35-36Zm-607 0q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h640q33 0 56.5 23.5T880-720v260q0 18-15.5 28t-32.5 3q-17-5-35.5-8t-36.5-3q-100 0-170 70t-70 170q0 17-11.5 28.5T480-160H160Zm320-360L212-688q-17-11-34.5-1T160-659q0 9 4 16.5t12 12.5l283 177q10 6 21 6t21-6l283-177q8-5 12-12.5t4-16.5q0-20-17.5-30t-34.5 1L480-520Z"/></svg></button>
            </form>
            <div class="back-to-login">
                <a href="companysignin.php" class="back-link">Back to login</a>
            </div>
        </fieldset>
    </div>
</div>


    
    
    <script src="forgotpasswords.js"></script>

</body>
</html>


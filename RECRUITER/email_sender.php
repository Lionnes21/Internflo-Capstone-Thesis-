    <?php
    // Include necessary PHPMailer files
    require 'vendor/autoload.php';
    require 'PHPMailer/src/Exception.php';
    require 'PHPMailer/src/PHPMailer.php';
    require 'PHPMailer/src/SMTP.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    function sendInterviewScheduleEmail($student_email, $interview_date, $interview_time) {
        // Create a new PHPMailer instance
        $mail = new PHPMailer(true);

        try {
            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username   = 'rogermalabananbusi@gmail.com';  // Replace with your email
            $mail->Password   = 'fhnt amet zziu tlow';     // Replace with your app password
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            // Email content configuration
            $mail->setFrom('rogermalabananbusi@gmail.com', 'Internflo');
            $mail->addAddress($student_email);
            $mail->isHTML(true);
            $mail->Subject = 'Interview Schedule Confirmation';

            // Format the date and time
            $formatted_date = date('F j, Y', strtotime($interview_date));
            $formatted_time = date('h:i A', strtotime($interview_time));

            // Email body
            $mail->Body = '
            <div style="font-family: Arial, sans-serif; text-align: center; color: #333; max-width: 600px; margin: auto;">
                <h1 style="background-color: #478831; color: white; padding: 10px 0; font-size: 24px;">
                    Interview Scheduled
                </h1>
                <div style="padding: 20px; background-color: #f4f4f4; border-radius: 10px;">
                    <p style="font-size: 18px; color: #333;">Your interview has been scheduled:</p>
                    <p style="font-size: 16px;">
                        <strong>Date:</strong> ' . htmlspecialchars($formatted_date) . '<br>
                        <strong>Time:</strong> ' . htmlspecialchars($formatted_time) . '
                    </p>
                    <p style="margin-top: 20px;">
                        <a href="videoconference.html" 
                        style="background: #478831; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; font-weight: bold;">
                            Join Interview
                        </a>
                    </p>
                </div>
                <p style="margin-top: 20px; font-size: 14px; color: #666;">
                    If you have any questions, please contact our recruitment team.
                </p>
            </div>';

            // Send email
            $mail->send();
            return true;
        } catch (Exception $e) {
            // Log the error or handle it as needed
            error_log("Email sending failed: " . $mail->ErrorInfo);
            return false;
        }
    }

    // Usage in your interview scheduling script
    if (isset($_POST['schedule_interview'])) {
        // ... (your existing interview scheduling code)

        // After successful interview schedule insertion
        if ($insert_stmt->execute()) {
            // Send email to the student
            $email_sent = sendInterviewScheduleEmail(
                $student_email, 
                $interview_date, 
                $interview_time
            );

            if ($email_sent) {
                $_SESSION['interview_scheduled_success'] = "Interview scheduled and confirmation email sent!";
            } else {
                $_SESSION['interview_scheduled_warning'] = "Interview scheduled, but email failed to send.";
            }
        }
    }
    ?>
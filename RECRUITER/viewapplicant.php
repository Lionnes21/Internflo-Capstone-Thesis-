<?php
    session_start();
    include 'config.php';
    // Define company_name and internship_title from URL parameters

    // Get the internship ID from the URL
    if (isset($_GET['logout'])) {
        session_destroy();
        header("Location: companymainpage.html");
        exit();
    }

    function getFullName() {
        if (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
            return htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']);
        }
        return 'Your Name'; // Fallback if names are not set
    }

    // Current user (recruiter) verification
    $current_user_id = null;
    if (isset($_SESSION['email']) && isset($_SESSION['source_table']) && $_SESSION['source_table'] === 'approvedrecruiters') {
        $recruiter_email = $_SESSION['email'];
        $query = "SELECT id FROM approvedrecruiters WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $recruiter_email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $current_user_id = $row['id'];
        } else {
            echo "Recruiter not found.";
            exit();
        }
    } else {
        echo "Unauthorized access. Please log in as an approved recruiter.";
        exit();
    }

    function sendSMS($phoneNumber, $message) {
        $ch = curl_init();
        
        // Ensure Philippine phone number format
        if (substr($phoneNumber, 0, 2) === '09') {
            $phoneNumber = '63' . substr($phoneNumber, 1);
        }
        
        $parameters = array(
            'apikey' => 'c3c8e83cf2c526850b168a57416cde0e', // Replace with your actual API key
            'number' => $phoneNumber,
            'message' => $message,
            'sendername' => 'Internflo'
        );
        
        curl_setopt_array($ch, array(
            CURLOPT_URL => 'https://api.semaphore.co/api/v4/messages',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($parameters),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded'
            )
        ));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Log the response and HTTP status for debugging
        error_log("SMS Send Response: " . $response);
        error_log("HTTP Status Code: " . $httpCode);
        
        // Return true if successful (you might want to add more robust error checking)
        return $httpCode == 200;
    }
    
    function sendInterviewSMS($phoneNumber, $student_email, $interview_date, $interview_time, $company_name, $internship_title) {
        $formatted_date = date('F j, Y', strtotime($interview_date));
        $formatted_time = date('h:i A', strtotime($interview_time));
    
        // Construct the SMS message without the meeting link
        $message = "Internflo Interview Notification:
            An interview for the {$internship_title} position at {$company_name} has been scheduled.
            Date: {$formatted_date}
            Time: {$formatted_time}
            Check your email for complete details.";
    
        // Send SMS using the sendSMS function
        return sendSMS($phoneNumber, $message);
    }    

    $company_name = isset($_GET['company_name']) ? urldecode($_GET['company_name']) : '';
    $internship_title = isset($_GET['internship_title']) ? urldecode($_GET['internship_title']) : '';
    $company_email = isset($_GET['email']) ? urldecode($_GET['email']) : '';
    $company_mobile = isset($_GET['mobile_number']) ? $_GET['mobile_number'] : '';

    // Handle interview schedulingrequire 'vendor/autoload.php';
    require 'PHPMailer/src/Exception.php';
    require 'PHPMailer/src/PHPMailer.php';
    require 'PHPMailer/src/SMTP.php';

    use PHPMailer\PHPMailer\PHPMailer;  
    use PHPMailer\PHPMailer\Exception;

    function generateRoomID() {
        return mt_rand(10000, 99999); // Generates a random 5-digit number
    }

    function sendInterviewScheduleEmail($student_email, $interview_date, $interview_time, $company_name, $internship_title, $meeting_link) {
        $mail = new PHPMailer(true);

        
        try {
            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username   = 'rogermalabananbusi@gmail.com';
            $mail->Password   = 'fhnt amet zziu tlow';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            // Email content configuration
            $mail->setFrom('rogermalabananbusi@gmail.com', 'Internflo');
            $mail->addAddress($student_email);
            $mail->isHTML(true);
            $mail->Subject = 'Interview Schedule Confirmation for ' . htmlspecialchars($internship_title);

            // Format the date and time
            $formatted_date = date('F j, Y', strtotime($interview_date));
            $formatted_time = date('h:i A', strtotime($interview_time));

            // Email body
            $mail->Body = '
            <div style="font-family: Arial, sans-serif; text-align: center; color: #333; max-width: 600px; margin: auto;">
                <h1 style="background-color: #478831; color: white; padding: 10px 0; font-size: 24px;">
                    Virtual Interview Invitation
                </h1>
                <div style="padding: 20px; background-color: #f4f4f4; border-radius: 10px;">
                    <p style="font-size: 18px; color: #333;">Dear Candidate,</p>
                    
                    <p style="font-size: 16px; text-align: left;">
                        We are excited to invite you to a virtual interview for the 
                        <strong>' . htmlspecialchars($internship_title) . '</strong> internship 
                        at <strong>' . htmlspecialchars($company_name) . '</strong>.
                    </p>
                    
                    <p style="font-size: 16px;">
                        <strong>Virtual Interview Details:</strong><br>
                        <strong>Date:</strong> ' . htmlspecialchars($formatted_date) . '<br>
                        <strong>Time:</strong> ' . htmlspecialchars($formatted_time) . '<br>
                        <strong>Meeting Link:</strong><br>
                        <a href="' . htmlspecialchars($meeting_link) . '">' . htmlspecialchars($meeting_link) . '</a><br>
                        <strong>Platform:</strong> Internflo Virtual Conference
                    </p>

                    
                    
                    <p style="margin-top: 20px; font-size: 16px; text-align: left; color: #333;">
                        <strong>Interview Preparation for Your ' . htmlspecialchars($internship_title) . ' Opportunity:</strong>
                        <ul style="text-align: left; padding-left: 20px;">
                            <li>Ensure a stable internet connection</li>
                            <li>Choose a quiet, well-lit location for the interview</li>
                            <li>Test your camera and microphone beforehand</li>
                            <li>Have a copy of your resume ready</li>
                            <li>Dress professionally as you would for an in-person interview</li>
                        </ul>
                    </p>
                    
                    <p style="margin-top: 20px; font-size: 16px; text-align: left; color: #333;">
                        Our recruitment team from ' . htmlspecialchars($company_name) . ' will send a detailed 
                        virtual interview invite with the specific video conference link 30 minutes prior to 
                        the scheduled interview time for the ' . htmlspecialchars($internship_title) . ' position. 
                        Please be prepared and logged in a few minutes early.
                    </p>
                    
                    <p style="margin-top: 20px; font-size: 16px; text-align: left; color: #333;">
                        We look forward to meeting you virtually and discussing how you can contribute 
                        to ' . htmlspecialchars($company_name) . ' through the ' . htmlspecialchars($internship_title) . ' internship.
                    </p>
                </div>
                <p style="margin-top: 20px; font-size: 14px; color: #666;">
                    If you have any technical difficulties or questions about the virtual interview, 
                    please contact our recruitment team.
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

    function sendCompanyInterviewNotificationEmail($company_email, $interview_date, $interview_time, $company_name, $internship_title, $student_email, $meeting_link) {
        $mail = new PHPMailer(true);

        try {
            // SMTP configuration remains the same...
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username   = 'rogermalabananbusi@gmail.com';
            $mail->Password   = 'fhnt amet zziu tlow';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;
    
            $mail->setFrom('rogermalabananbusi@gmail.com', 'Internflo');
            $mail->addAddress($company_email);
            $mail->isHTML(true);
            $mail->Subject = 'Interview Schedule Confirmation - ' . htmlspecialchars($internship_title);
    
            $formatted_date = date('F j, Y', strtotime($interview_date));
            $formatted_time = date('h:i A', strtotime($interview_time));

            // Email body for company
            $mail->Body = '
            <div style="font-family: Arial, sans-serif; text-align: center; color: #333; max-width: 600px; margin: auto;">
                <h1 style="background-color: #478831; color: white; padding: 10px 0; font-size: 24px;">
                    Interview Schedule Reminder
                </h1>
                <div style="padding: 20px; background-color: #f4f4f4; border-radius: 10px;">
                    <p style="font-size: 18px; color: #333;">Dear ' . htmlspecialchars($company_name) . ' Recruitment Team,</p>
                    
                    <p style="font-size: 16px; text-align: left;">
                        This email is to confirm that you have scheduled a virtual interview for the 
                        <strong>' . htmlspecialchars($internship_title) . '</strong> position.
                    </p>
                    
                    <div style="background-color: white; padding: 15px; border-radius: 5px; margin: 20px 0;">
                        <p style="font-size: 16px;">
                            <strong>Interview Details:</strong><br>
                            <strong>Position:</strong> ' . htmlspecialchars($internship_title) . '<br>
                            <strong>Date:</strong> ' . htmlspecialchars($formatted_date) . '<br>
                            <strong>Time:</strong> ' . htmlspecialchars($formatted_time) . '<br>
                            <strong>Candidate Email:</strong> ' . htmlspecialchars($student_email) . '<br>
                            <strong>Meeting Link:</strong><br>
                            <a href="' . htmlspecialchars($meeting_link) . '">' . htmlspecialchars($meeting_link) . '</a><br>
                            <strong>Platform:</strong> Internflo Virtual Conference
                        </p>
                    </div>
                    
                    <p style="font-size: 16px; text-align: left; color: #333;">
                        <strong>Important Reminders:</strong>
                        <ul style="text-align: left; padding-left: 20px;">
                            <li>We will provide the video conference link for both you and the candidates 30 minutes before the interview</li>
                            <li>Ensure your interviewing team is prepared and available</li>
                            <li>Have the candidate\'s application materials ready for review</li>
                            <li>Test your audio/video equipment before the interview</li>
                        </ul>
                    </p>
                    
                    <p style="margin-top: 20px; font-size: 16px; text-align: left; color: #333;">
                        The candidate has been notified of the schedule.
                    </p>
                </div>
                <p style="margin-top: 20px; font-size: 14px; color: #666;">
                    If you need to reschedule or have any questions, please contact our support team.
                </p>
            </div>';

            // Send email
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Company notification email failed: " . $mail->ErrorInfo);
            return false;
        }
    }

    // Handle interview scheduling
    // Handle interview scheduling
    if (isset($_POST['schedule_interview'])) {
        // Get student_id from the hidden input
        $student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;

        if ($student_id === 0) {
            echo "Invalid student ID.";
            exit();
        }

        // Retrieve student application details
        $student_query = "SELECT internshipad_id, application_id, email, phone_number 
                        FROM studentapplication 
                        WHERE student_id = ?";
        $student_stmt = $conn->prepare($student_query);
        $student_stmt->bind_param("i", $student_id);
        $student_stmt->execute();
        $student_result = $student_stmt->get_result();

        if ($student_row = $student_result->fetch_assoc()) {
            // Prepare data for insertion
            $internshipad_id = $student_row['internshipad_id'];
            $application_id = $student_row['application_id'];
            $student_email = $student_row['email'];
            $student_phone = $student_row['phone_number'];

            // Get interview schedule details from form
            $interview_date = $_POST['schedule-day'];
            $interview_time = $_POST['schedule-time'];

            $room_id = generateRoomID();
            $meeting_link = "https://internflo-ucc.com/RECRUITER/videoconference.html?roomID=" . $room_id;
        
            // Modified insert query to include meeting_link
            $insert_query = "INSERT INTO student_interview (
                recruiter_id, 
                student_id, 
                internshipad_id, 
                application_id, 
                email, 
                company_email, 
                phone_number, 
                company_mobile_number,
                interview_date, 
                interview_time,
                meeting_link
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
            $insert_stmt = $conn->prepare($insert_query);
        
            $insert_stmt->bind_param(
                "iiiisssssss", // Added one more 's' for a total of 11 placeholders
                $current_user_id, 
                $student_id, 
                $internshipad_id, 
                $application_id, 
                $student_email, 
                $company_email,
                $student_phone, 
                $company_mobile,
                $interview_date, 
                $interview_time,
                $meeting_link
            );
            
            // Execute the insert
            if ($insert_stmt->execute()) {
                $update_status_query = "UPDATE studentapplication 
                             SET status = 'For Interview' 
                             WHERE student_id = ? AND internshipad_id = ?";
    
                $update_status_stmt = $conn->prepare($update_status_query);
                $update_status_stmt->bind_param("ii", $student_id, $internshipad_id);
                
                if ($update_status_stmt->execute()) {
                    // Status successfully updated
                    $_SESSION['status_update_success'] = "Application status updated to 'For Interview'";
                } else {
                    // Failed to update status
                    $_SESSION['status_update_error'] = "Could not update application status";
                }
                
                $update_status_stmt->close();
                // Send email to both student and company
                $student_email_sent = sendInterviewScheduleEmail(
                    $student_email, 
                    $interview_date, 
                    $interview_time,
                    $company_name,
                    $internship_title,
                    $meeting_link
                );
                
                $company_email_sent = sendCompanyInterviewNotificationEmail(
                    $_GET['email'],
                    $interview_date,
                    $interview_time,
                    $_GET['company_name'],
                    $_GET['internship_title'],
                    $student_email,
                    $meeting_link
                );
                // Send SMS to student
                $student_sms_sent = sendInterviewSMS(
                    $student_phone, 
                    $student_email, 
                    $interview_date, 
                    $interview_time, 
                    $company_name, 
                    $internship_title
                );
                
                $company_sms_sent = sendInterviewSMS(
                    $company_mobile, 
                    $student_email, 
                    $interview_date, 
                    $interview_time, 
                    $company_name, 
                    $internship_title
                );
                // Update success/warning messages to include SMS status
                if ($student_email_sent && $company_email_sent && $student_sms_sent && $company_sms_sent) {
                    $_SESSION['interview_scheduled_success'] = "Interview scheduled. Confirmation emails and SMS sent to both parties!";
                } else {
                    // Various scenarios of partial success
                    $warning_message = "Interview scheduled, but some notifications failed:";
                    $warning_message .= !$student_email_sent ? " Student email failed." : "";
                    $warning_message .= !$company_email_sent ? " Company email failed." : "";
                    $warning_message .= !$student_sms_sent ? " Student SMS failed." : "";
                    $warning_message .= !$company_sms_sent ? " Company SMS failed." : "";
                    
                    $_SESSION['interview_scheduled_warning'] = $warning_message;
                }

                

                $insert_stmt->close();
            } else {
                // Insertion failed
                $_SESSION['interview_scheduled_error'] = "Error scheduling interview: " . $insert_stmt->error;
            }
        } else {
            $_SESSION['interview_scheduled_error'] = "Could not find student application details.";
        }
        

        // Replace the existing redirection code with:
        header("Location: applicants.php?internship_id=" . $internshipad_id);
        exit();
    }
    function rescheduleInterview($student_id, $internshipad_id, $interview_date, $interview_time, $meeting_link) {
        global $conn;
        
        // Update the interview record in the database
        $update_query = "UPDATE student_interview 
                        SET interview_date = ?, 
                            interview_time = ?, 
                            meeting_link = ? 
                        WHERE student_id = ? 
                        AND internshipad_id = ?";
                        
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("sssii", 
            $interview_date, 
            $interview_time, 
            $meeting_link,
            $student_id, 
            $internshipad_id
        );
        
        return $update_stmt->execute();
    }
    
    // Add this to handle the form submission for rescheduling
    // Place this after your existing interview scheduling handler
    if (isset($_POST['reschedule_interview'])) {
        // Get student_id from the hidden input
        $student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
    
        if ($student_id === 0) {
            echo "Invalid student ID.";
            exit();
        }
    
        // Retrieve student application details
        $student_query = "SELECT internshipad_id, application_id, email, phone_number 
                        FROM studentapplication 
                        WHERE student_id = ?";
        $student_stmt = $conn->prepare($student_query);
        $student_stmt->bind_param("i", $student_id);
        $student_stmt->execute();
        $student_result = $student_stmt->get_result();
    
        if ($student_row = $student_result->fetch_assoc()) {
            // Prepare data for updating
            $internshipad_id = $student_row['internshipad_id'];
            $student_email = $student_row['email'];
            $student_phone = $student_row['phone_number'];
    
            // Get interview schedule details from form
            $interview_date = $_POST['schedule-day'];
            $interview_time = $_POST['schedule-time'];
    
            // Generate a new room ID for the meeting
            $room_id = generateRoomID();
            $meeting_link = "https://internflo-ucc.com/RECRUITER/videoconference.html?roomID=" . $room_id;
        
            // Update the interview record
            if (rescheduleInterview($student_id, $internshipad_id, $interview_date, $interview_time, $meeting_link)) {
                // Send updated email notifications
                $student_email_sent = sendInterviewScheduleEmail(
                    $student_email, 
                    $interview_date, 
                    $interview_time,
                    $company_name,
                    $internship_title,
                    $meeting_link
                );
                
                $company_email_sent = sendCompanyInterviewNotificationEmail(
                    $_GET['email'],
                    $interview_date,
                    $interview_time,
                    $_GET['company_name'],
                    $_GET['internship_title'],
                    $student_email,
                    $meeting_link
                );
    
                // Send SMS notifications
                $student_sms_sent = sendInterviewSMS(
                    $student_phone, 
                    $student_email, 
                    $interview_date, 
                    $interview_time, 
                    $company_name, 
                    $internship_title
                );
                
                $company_sms_sent = sendInterviewSMS(
                    $company_mobile, 
                    $student_email, 
                    $interview_date, 
                    $interview_time, 
                    $company_name, 
                    $internship_title
                );
    
                // Update success/warning messages
                if ($student_email_sent && $company_email_sent && $student_sms_sent && $company_sms_sent) {
                    $_SESSION['interview_scheduled_success'] = "Interview rescheduled. Confirmation emails and SMS sent to both parties!";
                } else {
                    // Various scenarios of partial success
                    $warning_message = "Interview rescheduled, but some notifications failed:";
                    $warning_message .= !$student_email_sent ? " Student email failed." : "";
                    $warning_message .= !$company_email_sent ? " Company email failed." : "";
                    $warning_message .= !$student_sms_sent ? " Student SMS failed." : "";
                    $warning_message .= !$company_sms_sent ? " Company SMS failed." : "";
                    
                    $_SESSION['interview_scheduled_warning'] = $warning_message;
                }
            } else {
                $_SESSION['interview_scheduled_error'] = "Error rescheduling interview.";
            }
    
            // Redirect back to the applicant page
            header("Location: applicants.php?internship_id=" . $internshipad_id);
            exit();
        } else {
            $_SESSION['interview_scheduled_error'] = "Could not find student application details.";
            header("Location: applicants.php");
            exit();
        }
    }

    // Assuming you want to pass the student's ID from the previous page
    $student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

    if ($student_id === 0) {
        // No valid student ID provided
        header("Location: applicant.php");
        exit();
    }
    // Add this before sending the email


    // Fetch student details including course from students table
    $stmt = $conn->prepare("
        SELECT s.*, 
                sa.first_name, 
                sa.last_name, 
                sa.address, 
                sa.email, 
                sa.phone_number, 
                sa.portfolio_link, 
                sa.cv_file, 
                sa.endorsement_file, 
                sa.assessment_score,
                sa.demo_video 
        FROM students s 
        JOIN studentapplication sa ON s.id = sa.student_id 
        WHERE s.id = ?
        ");

    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Student not found
        header("Location: applicant.php");
        exit();
    }
    

    $student = $result->fetch_assoc();

    function getAssessmentCategory($score_string) {
        // Split the score string into correct answers and total questions
        list($correct, $total) = explode('/', $score_string);
        $correct = (int)$correct;
        $total = (int)$total;
        
        // Calculate percentage
        $percentage = ($correct / $total) * 100;
    
        if ($percentage >= 60) {  // 60% or higher
            return array('text' => 'Excellent Score', 'class' => 'badge badge-success');
        } elseif ($percentage >= 40) {  // 40-59%
            return array('text' => 'Average Score', 'class' => 'badge badge-warning');
        } else {
            return array('text' => 'Failed Score', 'class' => 'badge badge-danger');
        }
    }
    
    function getAssessmentDetails($score_string) {
        // Check if score is empty or blank
        if (empty($score_string) || trim($score_string) == '') {
            return array(
                'text' => 'N/A', 
                'text_color' => '#6c757d',
                'progress_class' => 'progress-bar-secondary',
                'progress' => 0,
                'badge_class' => 'badge-secondary'
            );
        }
        
        // Split the score string into correct answers and total questions
        list($correct, $total) = explode('/', $score_string);
        $correct = (int)$correct;
        $total = (int)$total;
        
        // Handle division by zero
        if ($total == 0) {
            return array(
                'text' => 'N/A', 
                'text_color' => '#6c757d',
                'progress_class' => 'progress-bar-secondary',
                'progress' => 0,
                'badge_class' => 'badge-secondary'
            );
        }
        
        // Calculate percentage
        $percentage = ($correct / $total) * 100;
    
        if ($percentage >= 60) {
            return array(
                'text' => 'Excellent performance', 
                'text_color' => '#1e7e34',
                'progress_class' => 'progress-bar-success',
                'progress' => $percentage,
                'badge_class' => 'badge-success'
            );
        } elseif ($percentage >= 40) {
            return array(
                'text' => 'Average performance', 
                'text_color' => '#856404',
                'progress_class' => 'progress-bar-warning',
                'progress' => $percentage,
                'badge_class' => 'badge-warning'
            );
        } else {
            return array(
                'text' => 'Poor performance', 
                'text_color' => '#b91e1e',
                'progress_class' => 'progress-bar-danger',
                'progress' => $percentage,
                'badge_class' => 'badge-danger'
            );
        }
    }
    
    function getScorePercentage($conn, $score_string) {
        // Check if score is empty or blank
        if (empty($score_string) || trim($score_string) == '') {
            return array(
                'percentage' => 'N/A',
                'color' => '#6c757d'
            );
        }
        
        // Split the score string into correct answers and total questions
        list($correct, $total) = explode('/', $score_string);
        $correct = (int)$correct;
        $total = (int)$total;
        
        // Handle division by zero
        if ($total == 0) {
            return array(
                'percentage' => 'N/A',
                'color' => '#6c757d'
            );
        }
        
        // Rest of your existing code...
        // Fetch all assessment scores
        $query = "SELECT assessment_score FROM studentapplication";
        $result = $conn->query($query);
        
        if (!$result) {
            return array(
                'percentage' => '70.0%',
                'color' => '#b91e1e'
            );
        }
    
        // Collect all scores and their percentages
        $all_scores = [];
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['assessment_score']) && strpos($row['assessment_score'], '/') !== false) {
                list($c, $t) = explode('/', $row['assessment_score']);
                if ((int)$t > 0) {
                    $all_scores[] = ((int)$c / (int)$t) * 100;
                }
            }
        }
    
        // If no scores found
        if (empty($all_scores)) {
            return array(
                'percentage' => '70.0%',
                'color' => '#b91e1e'
            );
        }
    
        // Calculate the number of scores lower than current score
        $current_percentage = ($correct / $total) * 100;
        $scores_below_current = array_filter($all_scores, function($score) use ($current_percentage) {
            return $score < $current_percentage;
        });
    
        // Calculate percentile
        $total_scores = count($all_scores);
        $percentile = (count($scores_below_current) / $total_scores) * 100;
    
        // Logic for low scores (below 40%)
        if ($current_percentage < 40) {
            return array(
                'percentage' => '70.0%',
                'color' => '#b91e1e'
            );
        }
    
        // Logic for average scores (40-59%)
        if ($current_percentage >= 40 && $current_percentage < 60) {
            $additional_decimal = round($percentile / 10, 1);
            $final_percentage = number_format(70.0 + $additional_decimal, 1, '.', '');
            
            return array(
                'percentage' => $final_percentage . '%',
                'color' => '#856404'  // Brown for average scores
            );
        }
    
        // Logic for excellent scores (60% and above)
        if ($current_percentage >= 60) {
            $additional_decimal = round($percentile / 5, 1);
            $final_percentage = number_format(80.0 + $additional_decimal, 1, '.', '');
            
            return array(
                'percentage' => $final_percentage . '%',
                'color' => '#1e7e34'  // Green for excellent scores
            );
        }
    
        // Fallback
        return array(
            'percentage' => '70.0%',
            'color' => '#b91e1e'
        );
    }
    
    // Populate assessment details
    $assessment_score = $student['assessment_score'] ?? '';
    // Check if assessment score is empty
    if (empty($assessment_score) || trim($assessment_score) == '') {
        $assessment = array('text' => 'N/A', 'class' => 'badge badge-secondary');
        $scoreDetails = array(
            'text' => 'N/A', 
            'text_color' => '#6c757d',
            'progress_class' => 'progress-bar-secondary',
            'progress' => 0,
            'badge_class' => 'badge-secondary'
        );
        $scorePercentage = array(
            'percentage' => 'N/A',
            'color' => '#6c757d'
        );
    } else {
        $scorePercentage = getScorePercentage($conn, $assessment_score);
        $assessment = getAssessmentCategory($assessment_score);
        $scoreDetails = getAssessmentDetails($assessment_score);
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="pics/ucc.png">
    <title>UCC - Applicants</title>
    <link rel="stylesheet" href="viewapplicant.css">
    <link rel="stylesheet" href="NAV-login.css">
    <link rel="stylesheet" href="FOOTER.css">
</head>
<body>
    
    <!-- NAVIGATION -->
    <div class="navbar">
        <div class="logo-container">
            <button class="menu-toggle" aria-label="Toggle Navigation">☰</button>
            <img src="pics/ucc-logo.png" alt="Logo" class="logo-img">
        </div>
        <div class="nav-links">
            <a href="companyloginpage.php">HOME</a>
            <a href="#about">ABOUT US</a>
            <a href="#contact">CONTACT US</a>
            <?php if(!isset($_SESSION['email'])): ?>
                <a href="../STUDENTCOORLOG/login.php" class="login-btn">LOGIN</a>
                <a href="../MAIN/MAIN.php" class="employer-btn">APPLICANT SITE</a>
            <?php endif; ?>
        </div>
        <?php if(isset($_SESSION['email'])): ?>
        <div class="auth-buttons">
            <div class="dropdown-container">
                <div class="border">
                    <span class="greeting-text"><?php echo htmlspecialchars($_SESSION['email']); ?></span>
                    <div class="dropdown-btn" onclick="toggleDropdown(event)">
                        <img src="pics/profile.png" alt="Profile" onerror="this.onerror=null;this.src='pics/default_profile.jpg';">
                    </div>
                </div>
                <div id="dropdown-content" class="dropdown-content">
                    <div class="user-fullname"><?php echo getFullName(); ?></div>
                    <hr style="margin: 0 auto">
                    <a href="company-profile.php">Profile</a>
                    <a href="company-overview.php">Interns</a>
                    <a href="chat-inbox.php">Emails</a>
                    <a href="company-account.php">Settings</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Elements
            const navbar = document.querySelector('.navbar');
            const menuToggle = document.querySelector('.menu-toggle');
            const dropdownContent = document.getElementById("dropdown-content");
            let timeout;

            // Navbar visibility functions
            const hideNavbar = () => {
                if (window.scrollY > 0) {
                    navbar.style.opacity = '0';
                    navbar.style.pointerEvents = 'none';
                }
            };

            const showNavbar = () => {
                navbar.style.opacity = '1';
                navbar.style.pointerEvents = 'auto';
            };

            const resetNavbarTimeout = () => {
                showNavbar();
                clearTimeout(timeout);
                if (window.scrollY > 0) {
                    timeout = setTimeout(hideNavbar, 1000);
                }
            };

            // Scroll event listeners
            window.addEventListener('scroll', () => {
                if (window.scrollY === 0) {
                    showNavbar();
                    clearTimeout(timeout);
                } else {
                    resetNavbarTimeout();
                }
            });

            // User interaction listeners
            window.addEventListener('mousemove', resetNavbarTimeout);
            window.addEventListener('click', resetNavbarTimeout);
            window.addEventListener('keydown', resetNavbarTimeout);

            // Initial check
            if (window.scrollY > 0) {
                timeout = setTimeout(hideNavbar, 1000);
            }

            // Mobile menu toggle functionality
            menuToggle.addEventListener('click', function(event) {
                event.stopPropagation(); // Prevent event from bubbling
                navbar.classList.toggle('active');
                
                if (navbar.classList.contains('active')) {
                    menuToggle.innerHTML = '☰';
                    menuToggle.style.color = '#e77d33';
                } else {
                    menuToggle.innerHTML = '☰';
                    menuToggle.style.color = '#fd6f41';
                }
            });

            // Dropdown toggle function
            window.toggleDropdown = function(event) {
                if (event) {
                    event.stopPropagation();
                }
                
                const isDisplayed = dropdownContent.style.display === "block";
                
                // Close dropdown if it's open
                if (isDisplayed) {
                    dropdownContent.style.display = "none";
                } else {
                    // Close any other open dropdowns first
                    const allDropdowns = document.querySelectorAll('.dropdown-content');
                    allDropdowns.forEach(dropdown => {
                        dropdown.style.display = "none";
                    });
                    
                    // Open this dropdown
                    dropdownContent.style.display = "block";
                }
            };

            // Close menu and dropdown when clicking outside
            document.addEventListener('click', function(event) {
                // Handle mobile menu
                const isClickInsideNavbar = navbar.contains(event.target);
                if (!isClickInsideNavbar && navbar.classList.contains('active')) {
                    navbar.classList.remove('active');
                    menuToggle.innerHTML = '☰';
                    menuToggle.style.color = '#fd6f41';
                }

                // Handle dropdown
                const isClickInsideDropdown = event.target.closest('.dropdown-container');
                if (!isClickInsideDropdown && dropdownContent) {
                    dropdownContent.style.display = "none";
                }
            });

            // Window resize handler
            window.addEventListener('resize', function() {
                if (window.innerWidth > 1300) {
                    navbar.classList.remove('active');
                    menuToggle.innerHTML = '☰';
                    menuToggle.style.color = '#fd6f41';
                }
            });
        });
    </script>
    <!-- NAVIGATION -->


    <div class="applicantwidth">
        <div class="profile-card">
            <div class="profile-image-container">
                <div class="profile-image">
                <img src="../STUDENTLOGIN/<?php echo htmlspecialchars($student['profile_pic']); ?>" alt="<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>">
                </div>
            </div>
            <div class="profile-content">
                <span class="applicantnum">Applicant</span>
                <h1><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h1>
                <h2><?php echo htmlspecialchars($student['course']); ?></h2>
                <div class="profile-details">
                <span class="items">
                    <svg xmlns="http://www.w3.org/2000/svg" height="22px" viewBox="0 -960 960 960" width="22px" fill="#2E3849"><path d="M480-197.46q117.33-105.08 177.23-192.09 59.9-87.02 59.9-160.41 0-103.31-67.67-171.24t-169.47-67.93q-101.79 0-169.46 67.93-67.66 67.93-67.66 171.24 0 73.39 59.9 160.29 59.9 86.89 177.23 192.21Zm-.06 85.16q-13.9 0-26.25-4.74-12.36-4.74-24.04-14.22-41.43-35.72-88.89-82.96-47.46-47.24-88.05-101.71-40.6-54.48-66.72-114.06-26.12-59.58-26.12-119.97 0-137.28 91.45-229.72 91.45-92.45 228.68-92.45 136.23 0 228.18 92.45 91.95 92.44 91.95 229.72 0 60.39-26.62 120.47t-66.72 114.56q-40.09 54.47-87.55 101.21-47.46 46.74-88.89 82.46-11.71 9.48-24.11 14.22-12.4 4.74-26.3 4.74ZM480-552Zm0 74.39q31.2 0 52.79-21.6 21.6-21.59 21.6-52.79t-21.6-52.79q-21.59-21.6-52.79-21.6t-52.79 21.6q-21.6 21.59-21.6 52.79t21.6 52.79q21.59 21.6 52.79 21.6Z"/></svg>
                    <?php echo htmlspecialchars($student['address']); ?>
                </span>
                <span class="items">
                    <svg xmlns="http://www.w3.org/2000/svg" height="22px" viewBox="0 -960 960 960" width="22px" fill="#2E3849"><path d="M763.48-136.87q-122.44-9-232.37-60.1-109.94-51.1-197.37-138.29-87.44-87.44-138.03-197.49-50.6-110.05-59.6-232.49-2-24.35 14.65-42.12 16.65-17.77 41-17.77h135.76q22.5 0 37.87 12.53 15.37 12.53 20.81 33.56l23.76 101.97q2.95 16.59-1.38 31.22-4.34 14.63-15.21 24.78l-98.44 96.09q19.05 35.37 43.61 68.46 24.57 33.09 55.61 63.13 28.81 28.8 60.42 52.63 31.6 23.83 66.26 41.91L621.5-395.8q10.63-10.4 25.02-14.37 14.39-3.98 30.98-1.03l100.54 22.29q22.03 6.43 34.06 21.44 12.03 15.01 12.03 37.04v137.67q0 24.35-18.27 41.12-18.27 16.77-42.38 14.77Z"/></svg>
                    <?php echo htmlspecialchars($student['phone_number']); ?>
                </span>
                <span class="items">
                    <svg xmlns="http://www.w3.org/2000/svg" height="22px" viewBox="0 -960 960 960" width="22px" fill="#2E3849"><path d="M479.76-87.87q-80.93 0-152.12-30.6-71.18-30.6-124.88-84.29-53.69-53.7-84.29-125.11-30.6-71.41-30.6-152.61 0-81.19 30.6-152.13 30.6-70.93 84.29-124.63 53.7-53.69 125.11-84.29 71.41-30.6 152.61-30.6 81.19 0 152.13 30.6 70.93 30.6 124.63 84.29 53.69 53.7 84.29 124.57 30.6 70.87 30.6 152.43v60q0 56.44-40.41 96.13-40.42 39.7-97.57 39.7-34.77 0-63.96-17.24-29.19-17.24-49.35-46.2-26.88 29.96-63.62 46.7-36.74 16.74-77.22 16.74-81.35 0-138.47-57.19-57.12-57.18-57.12-138.63 0-81.44 57.19-138.4 57.18-56.96 138.63-56.96 81.44 0 138.4 57.12 56.96 57.12 56.96 138.47v57.85q0 24.56 17.26 41.56 17.26 17 41.54 17 24.27 0 41.3-17 17.03-17 17.03-41.56v-58.09q0-130-91.36-221.24Q610-792.72 480-792.72t-221.36 91.36Q167.28-610 167.28-480t91.24 221.36q91.24 91.36 221.59 91.36h152.3q16.83 0 28.21 11.32Q672-144.64 672-127.91q0 16.65-11.38 28.34-11.38 11.7-28.21 11.7H479.76Zm.28-275.72q48.53 0 82.45-33.96 33.92-33.97 33.92-82.49 0-48.53-33.96-82.45-33.97-33.92-82.49-33.92-48.53 0-82.45 33.96-33.92 33.97-33.92 82.49 0 48.53 33.96 82.45 33.97 33.92 82.49 33.92Z"/></svg>

                    <?php echo htmlspecialchars($student['email']); ?>
                </span>
                <span class="link">
                    <?php echo htmlspecialchars($student['portfolio_link']); ?>
                    <?php if (!empty($student['portfolio_link'])): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#0000ee">
                            <path d="M202.87-111.87q-37.78 0-64.39-26.61t-26.61-64.39v-554.26q0-37.78 26.61-64.39t64.39-26.61H434.5q19.15 0 32.33 13.17Q480-821.78 480-802.63t-13.17 32.33q-13.18 13.17-32.33 13.17H202.87v554.26h554.26V-434.5q0-19.15 13.17-32.33Q783.48-480 802.63-480t32.33 13.17q13.17 13.18 13.17 32.33v231.63q0 37.78-26.61 64.39t-64.39 26.61H202.87Zm554.26-581.85L427-363.59q-12.67 12.68-31.59 12.56-18.91-.12-31.58-12.8-12.68-12.67-12.68-31.7 0-19.04 12.68-31.71l329.89-329.89H605.5q-19.15 0-32.33-13.17Q560-783.48 560-802.63t13.17-32.33q13.18-13.17 32.33-13.17h197.13q19.15 0 32.33 13.17 13.17 13.18 13.17 32.33v197.13q0 19.15-13.17 32.33Q821.78-560 802.63-560t-32.33-13.17q-13.17-13.18-13.17-32.33v-88.22Z"/>
                        </svg>
                    <?php endif; ?>
                </span>
                </div>
            </div>
            <div class="actions">
                <?php
                    // First, get the necessary IDs from URL/POST parameters
                    $internshipad_id = isset($_GET['internship_id']) ? (int)$_GET['internship_id'] : 0;
                    $student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

                    // Initialize the flag
                    $is_for_interview = false;

                    // Check if we have valid IDs
                    if ($internshipad_id > 0 && $student_id > 0) {
                        // Prepare query to check applicant status
                        $check_status_query = "SELECT * FROM studentapplication
                                            WHERE student_id = ? 
                                            AND internshipad_id = ?";
                                            
                        $check_stmt = $conn->prepare($check_status_query);
                        $check_stmt->bind_param("ii", $student_id, $internshipad_id);
                        $check_stmt->execute();
                        $status_result = $check_stmt->get_result();

                        if ($status_result->num_rows > 0) {
                            $status_data = $status_result->fetch_assoc();
                            $is_for_interview = ($status_data['Status'] === 'For Interview');
                        }
                        
                        $check_stmt->close();
                    }
                ?>
                <button class="hire-btn">Hire Applicant <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#FFFFFF"><path d="M724.3-528.37h-76.17q-17.81 0-29.86-12.05t-12.05-29.86q0-17.82 12.05-29.87t29.86-12.05h76.17v-76.17q0-17.81 12.05-29.86t29.87-12.05q17.81 0 29.86 12.05t12.05 29.86v76.17h76.17q17.82 0 29.87 12.05t12.05 29.87q0 17.81-12.05 29.86t-29.87 12.05h-76.17v76.17q0 17.82-12.05 29.87t-29.86 12.05q-17.82 0-29.87-12.05T724.3-452.2v-76.17Zm-363.58 44.3q-69.59 0-118.86-49.27-49.27-49.27-49.27-118.86 0-69.58 49.27-118.74 49.27-49.15 118.86-49.15 69.58 0 118.86 49.15 49.27 49.16 49.27 118.74 0 69.59-49.27 118.86-49.28 49.27-118.86 49.27ZM32.59-238.8v-29.61q0-36.23 18.74-66.59 18.74-30.37 49.8-46.35 62.72-31.24 127.66-46.98 64.95-15.74 131.93-15.74 67.43 0 132.39 15.62 64.96 15.62 127.19 46.86 31.06 15.95 49.81 46.25 18.74 30.3 18.74 66.93v29.61q0 37.78-26.61 64.39t-64.39 26.61H123.59q-37.79 0-64.39-26.61-26.61-26.61-26.61-64.39Z"/></svg></button>
                <div id="hireModal" class="hire-modal" style="display: none;">
                    <div class="hire-modal-content">
                        <div class="hire-modal-header">
                            <h2>Hire this Applicant?</h2>
                        </div>
                        <div class="hire-modal-body">
                            <p class="hire-action" id="hireMessage"></p>
                        </div>
                        <div class="hire-modal-footer">
                            <button id="confirmHire" class="hire-btn-confirm">YES</button>
                            <button id="cancelHire" class="hire-btn-cancel">NO</button>
                        </div>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        // Get DOM elements
                        const hireBtn = document.querySelector('.hire-btn');
                        const hireModal = document.getElementById('hireModal');
                        const confirmHireBtn = document.getElementById('confirmHire');
                        const cancelHireBtn = document.getElementById('cancelHire');
                        
                        // Get PHP variables - properly escaped
                        const studentId = <?php echo json_encode($student_id); ?>;
                        const internshipadId = <?php echo json_encode($internshipad_id); ?>;
                        const isForInterview = <?php echo $is_for_interview ? 'true' : 'false'; ?>;
                        
                        // Show modal function
                        function showHireModal(event) {
                            event.preventDefault();
                            
                            // Set the appropriate message based on application status
                            const message = isForInterview 
                                ? "This applicant is currently scheduled for interview.<br>Are you sure you want to proceed with hiring?"
                                : "This applicant hasn't been interviewed yet.<br>Are you sure you want to proceed with hiring?";

                            document.getElementById('hireMessage').innerHTML = message;
                            
                            // Show the modal
                            hireModal.style.display = 'flex';
                        }
                        
                        // Hide modal function
                        function hideHireModal() {
                            hireModal.style.display = 'none';
                        }
                        
                        // Function to handle the hiring process
                        function handleHiring() {
                            // Create form data
                            const formData = new FormData();
                            formData.append('action', 'hire_applicant');
                            formData.append('student_id', studentId);
                            formData.append('internshipad_id', internshipadId);
                            
                            // Show loading state
                            confirmHireBtn.disabled = true;
                            confirmHireBtn.textContent = 'Processing...';
                            cancelHireBtn.style.display = 'none'; // Hide cancel button
                            
                            // Add a delay before making the request
                            setTimeout(() => {
                                // Send AJAX request
                                fetch('update_interview_status.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error('Network response was not ok');
                                    }
                                    return response.json();
                                })
                                .then(data => {
                                    if (data.success) {
                                        // Hide hire message and buttons
                                        document.getElementById('hireMessage').style.display = 'none';
                                        document.querySelector('.hire-modal-footer').style.display = 'none';
                                        
                                        // Create success message div
                                        const successDiv = document.createElement('div');
                                        successDiv.style.cssText = `
                                            background-color: #e6f9e6;
                                            padding: 15px;
                                            text-align: center;
                                            margin: 0;
                                            border-radius: 4px;
                                            font-weight: 600;
                                        `;
                                        successDiv.innerHTML = `
                                            <h2 style="
                                                color: #2d6a2d; 
                                                margin: 0; 
                                                font-size: 14px;
                                                display: flex;
                                                align-items: center;
                                                justify-content: center;
                                                gap: 8px;
                                            ">
                                                APPLICANT SUCCESSFULLY HIRED 
                                                <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#2d6a2d" style="display: inline-block; vertical-align: middle;">
                                                    <path d="M428.28-331.22 669.87-571.8l-57.46-57.7-184.13 183.13-82.13-81.13-57.45 57.7 139.58 138.58ZM480-87.87q-80.91 0-152.34-30.62-71.44-30.62-125-84.17-53.55-53.56-84.17-125Q87.87-399.09 87.87-480q0-81.91 30.62-152.84 30.62-70.94 84.17-124.5 53.56-53.55 125-84.17 71.43-30.62 152.34-30.62 81.91 0 152.84 30.62 70.94 30.62 124.5 84.17 53.55 53.56 84.17 124.5 30.62 70.93 30.62 152.84 0 80.91-30.62 152.34-30.62 71.44-84.17 125-53.56 53.55-124.5 84.17Q561.91-87.87 480-87.87Zm0-83q129.04 0 219.09-90.04 90.04-90.05 90.04-219.09 0-129.04-90.04-219.09-90.05-90.04-219.09-90.04-129.04 0-219.09 90.04-90.04 90.05-90.04 219.09 0 129.04 90.04 219.09 90.05 90.04 219.09 90.04ZM480-480Z"/>
                                                </svg>
                                            </h2>`;
                                        // Add success message to modal body
                                        document.querySelector('.hire-modal-body').appendChild(successDiv);
                                        
                                        // Redirect after 3 seconds
                                        setTimeout(() => {
                                            window.location.href = `applicants.php?internship_id=${internshipadId}`;
                                        }, 3000);
                                    } else {
                                        // Reset buttons if there's an error
                                        confirmHireBtn.disabled = false;
                                        confirmHireBtn.textContent = 'YES';
                                        cancelHireBtn.style.display = 'block';
                                        // Show error message
                                        document.getElementById('hireMessage').innerHTML = data.message || 'Failed to hire applicant';
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    // Reset buttons
                                    confirmHireBtn.disabled = false;
                                    confirmHireBtn.textContent = 'YES';
                                    cancelHireBtn.style.display = 'block';
                                    // Show error message
                                    document.getElementById('hireMessage').innerHTML = 'An error occurred while processing your request.';
                                });
                            }, 1000); // 1 second delay before making the request
                        }
                        
                        // Event Listeners
                        if (hireBtn) {
                            hireBtn.addEventListener('click', showHireModal);
                        }
                        
                        if (confirmHireBtn) {
                            confirmHireBtn.addEventListener('click', handleHiring);
                        }
                        
                        if (cancelHireBtn) {
                            cancelHireBtn.addEventListener('click', hideHireModal);
                        }
                        
                        // Close modal when clicking outside
                        window.onclick = function(event) {
                            if (event.target === hireModal) {
                                hideHireModal();
                            }
                        };
                        
                        // Close modal with Escape key
                        document.addEventListener('keydown', function(event) {
                            if (event.key === 'Escape' && hireModal.style.display === 'flex') {
                                hideHireModal();
                            }
                        });
                    });
                </script>
                <?php
                    // First, get the necessary IDs from URL/POST parameters
                    $internshipad_id = isset($_GET['internship_id']) ? (int)$_GET['internship_id'] : 0;
                    $student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

                    // Initialize the flag
                    $is_for_interview = false;
                    $applicant_data = null;

                    // Check if we have valid IDs
                    if ($internshipad_id > 0 && $student_id > 0) {
                        // Prepare query to check applicant status
                        $check_status_query = "SELECT * FROM studentapplication
                                            WHERE student_id = ? 
                                            AND internshipad_id = ?";
                                                
                        $check_stmt = $conn->prepare($check_status_query);
                        $check_stmt->bind_param("ii", $student_id, $internshipad_id);
                        $check_stmt->execute();
                        $status_result = $check_stmt->get_result();

                        if ($status_result->num_rows > 0) {
                            $applicant_data = $status_result->fetch_assoc();
                            $is_for_interview = ($applicant_data['Status'] === 'For Interview');
                        }
                        
                        $check_stmt->close();
                    }

                    // Now render the button with the appropriate state
                ?>
<button 
    id="interviewModal" 
    class="interview-btn <?php echo $is_for_interview ? 'scheduled' : ''; ?>"
>
    <?php echo $is_for_interview ? 'Reschedule Interview' : 'Set Interview'; ?>
    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="<?php echo $is_for_interview ? '#1e48aa' : '#1e48aa'; ?>">
        <path d="M202.87-71.87q-37.78 0-64.39-26.61t-26.61-64.39v-554.26q0-37.78 26.61-64.39t64.39-26.61H240v-37.37q0-17.96 12.46-30.29 12.45-12.34 30.41-12.34t30.29 12.34q12.34 12.33 12.34 30.29v37.37h309v-37.37q0-17.96 12.46-30.29 12.45-12.34 30.41-12.34t30.29 12.34Q720-863.46 720-845.5v37.37h37.13q37.78 0 64.39 26.61t26.61 64.39V-559q0 19.15-13.17 32.33-13.18 13.17-32.33 13.17t-32.33-13.17q-13.17-13.18-13.17-32.33v-1H202.87v397.13h230.2q19.15 0 32.32 13.17 13.18 13.18 13.18 32.33t-13.18 32.33q-13.17 13.17-32.32 13.17h-230.2Zm355.7-45.5v-65.04q0-9.2 3.47-17.54 3.48-8.33 10.2-15.05L781-423q9.72-9.72 21.55-14.08 11.84-4.35 23.8-4.35 12.95 0 24.79 4.85 11.84 4.86 21.56 14.58l37 37q8.71 9.72 13.57 21.55 4.86 11.84 4.86 23.8 0 12.19-4.36 24.41T909.7-293.3l-208 208q-6.72 6.71-15.06 10.07-8.34 3.36-17.53 3.36h-65.04q-19.16 0-32.33-13.17-13.17-13.18-13.17-32.33Zm270.17-185.67 34.61-36.61-37-37-35.61 35.61 38 38Z"/>
    </svg>
</button>

            </div>
            
            <div id="schedule" class="interview">
    <div class="container">
        <div class="left-content">
            <form method="POST" action="">
                <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                <div class="details">
                    <h2><?php echo $is_for_interview ? 'Reschedule Interview' : 'Schedule Interview'; ?></h2>
                    <p><?php echo $is_for_interview ? 'Change interview time for applicant' : 'Schedule for Applicants'; ?></p>
                    <div class="box-day">
                        <div class="interview-date" id="selected-date-day"></div>
                        <div class="interview-day" id="selected-day"></div>
                    </div>
                    <div class="interview-details">
                        <div class="interview-time">
                            <input 
                                type="date" 
                                name="schedule-day" 
                                style="display: none" 
                                id="datepicker" 
                                placeholder="MM-DD-YY" 
                                class="date-input" 
                                required
                            />
                            <input 
                                type="time" 
                                name="schedule-time" 
                                id="time-input" 
                                required
                            />
                            <div class="custom-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#666666">
                                    <path d="M200-80q-33 0-56.5-23.5T120-160v-560q0-33 23.5-56.5T200-800h40v-40q0-17 11.5-28.5T280-880q17 0 28.5 11.5T320-840v40h320v-40q0-17 11.5-28.5T680-880q17 0 28.5 11.5T720-840v40h40q33 0 56.5 23.5T840-720v187q0 17-11.5 28.5T800-493q-17 0-28.5-11.5T760-533v-27H200v400h232q17 0 28.5 11.5T472-120q0 17-11.5 28.5T432-80H200Zm520 40q-83 0-141.5-58.5T520-240q0-83 58.5-141.5T720-440q83 0 141.5 58.5T920-240q0 83-58.5 141.5T720-40Zm20-208v-92q0-8-6-14t-14-6q-8 0-14 6t-6 14v91q0 8 3 15.5t9 13.5l61 61q6 6 14 6t14-6q6-6 6-14t-6-14l-61-61Z" />
                                </svg>
                            </div>
                        </div>                        
                        <div class="interview-date-full" id="selected-date-full">Thursday, July 20, 2025</div>
                        <div class="interview-btn-container">
                            <button 
                                type="submit" 
                                name="<?php echo $is_for_interview ? 'reschedule_interview' : 'schedule_interview'; ?>" 
                                class="set-interview-btn"
                            >
                                <?php echo $is_for_interview ? 'Update Schedule' : 'Set Schedule'; ?>
                                <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#FFFFFF">
                                    <path d="M202.87-71.87q-37.78 0-64.39-26.61t-26.61-64.39v-554.26q0-37.78 26.61-64.39t64.39-26.61H240v-37.37q0-17.96 12.46-30.29 12.45-12.34 30.41-12.34t30.29 12.34q12.34 12.33 12.34 30.29v37.37h309v-37.37q0-17.96 12.46-30.29 12.45-12.34 30.41-12.34t30.29 12.34Q720-863.46 720-845.5v37.37h37.13q37.78 0 64.39 26.61t26.61 64.39V-559q0 19.15-13.17 32.33-13.18 13.17-32.33 13.17t-32.33-13.17q-13.17-13.18-13.17-32.33v-1H202.87v397.13h230.2q19.15 0 32.32 13.17 13.18 13.18 13.18 32.33t-13.18 32.33q-13.17 13.17-32.32 13.17h-230.2Zm355.7-45.5v-65.04q0-9.2 3.47-17.54 3.48-8.33 10.2-15.05L781-423q9.72-9.72 21.55-14.08 11.84-4.35 23.8-4.35 12.95 0 24.79 4.85 11.84 4.86 21.56 14.58l37 37q8.71 9.72 13.57 21.55 4.86 11.84 4.86 23.8 0 12.19-4.36 24.41T909.7-293.3l-208 208q-6.72 6.71-15.06 10.07-8.34 3.36-17.53 3.36h-65.04q-19.16 0-32.33-13.17-13.17-13.18-13.17-32.33Zm270.17-185.67 34.61-36.61-37-37-35.61 35.61 38 38Z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
                    <div class="calendar-container">
                        <table class="calendar">
                            <thead>
                                <tr>
                                    <th class="calendar-header" colspan="7">
                                            <select id="month-select" class="calendar-select1"></select>
                                            <select id="year-select" class="calendar-select"></select>
                                    </th>
                                </tr>
                                <tr class="calendar-days">
                                    <th class="day">Sun</th>
                                    <th class="day">Mon</th>
                                    <th class="day">Tue</th>
                                    <th class="day">Wed</th>
                                    <th class="day">Thu</th>
                                    <th class="day">Fri</th>
                                    <th class="day">Sat</th>
                                </tr>                            
                            </thead>
                            <tbody id="calendar-body"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <style>
                .disabled {
                    color: #ccc;
                    cursor: not-allowed;
                    text-decoration: line-through;
                }
                button.disabled {
                    opacity: 0.5;
                    cursor: not-allowed;
                }
            </style>

            <script>
                // Get the modal and open button
                const modal = document.getElementById("schedule");
                const openModalBtn = document.getElementById("interviewModal");

                // Show the modal when the button is clicked
                openModalBtn.addEventListener("click", () => {
                    modal.style.display = "flex";
                });

                // Hide the modal when clicking outside the modal content
                window.addEventListener("click", (event) => {
                    if (event.target === modal) {
                        modal.style.display = "none";
                    }
                });

                const datePicker = document.getElementById('datepicker');
                const selectedDateDay = document.getElementById('selected-date-day');
                const selectedDay = document.getElementById('selected-day');
                const selectedDateFull = document.getElementById('selected-date-full');

                const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

                // Create header container for navigation
                const headerNavigation = document.createElement('div');
                headerNavigation.classList.add('calendar-header-navigation');

                // Create previous button with SVG
                const prevButton = document.createElement('button');
                prevButton.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2E3849">
                        <path d="m313-480 155 156q11 11 11.5 27.5T468-268q-11 11-28 11t-28-11L228-452q-6-6-8.5-13t-2.5-15q0-8 2.5-15t8.5-13l184-184q11-11 27.5-11.5T468-692q11 11 11 28t-11 28L313-480Zm264 0 155 156q11 11 11.5 27.5T732-268q-11 11-28 11t-28-11L492-452q-6-6-8.5-13t-2.5-15q0-8 2.5-15t8.5-13l184-184q11-11 27.5-11.5T732-692q11 11 11 28t-11 28L577-480Z"/>
                    </svg>
                `;
                prevButton.classList.add('prev-month');

                // Create next button with SVG
                const nextButton = document.createElement('button');
                nextButton.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2E3849">
                        <path d="M383-480 228-636q-11-11-11.5-27.5T228-692q11-11 28-11t28 11l184 184q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13L284-268q-11 11-27.5 11.5T228-268q-11-11-11-28t11-28l155-156Zm264 0L492-636q-11-11-11.5-27.5T492-692q11-11 28-11t28 11l184 184q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13L548-268q-11 11-27.5 11.5T492-268q-11-11-11-28t11-28l155-156Z"/>
                    </svg>
                `;
                nextButton.classList.add('next-month');

                const monthYearDisplay = document.createElement('span');
                monthYearDisplay.classList.add('month-year-display');

                // Assemble navigation elements
                headerNavigation.appendChild(prevButton);
                headerNavigation.appendChild(monthYearDisplay);
                headerNavigation.appendChild(nextButton);

                // Replace dropdowns with new navigation elements
                const headerRow = document.querySelector('.calendar thead tr:first-child th');
                headerRow.innerHTML = '';
                headerRow.appendChild(headerNavigation);

                // Get current date for validation
                const today = new Date();
                today.setHours(0, 0, 0, 0); // Reset time to start of day

                let currentYear = today.getFullYear();
                let currentMonth = today.getMonth();

                function updateMonthYearDisplay() {
                    monthYearDisplay.textContent = `${months[currentMonth]} ${currentYear}`;
                }

                function updateDateDetails(year, month, day) {
                    const selectedDate = new Date(year, month, day);
                    
                    // Update day
                    selectedDateDay.textContent = day;
                    
                    // Update day of week
                    selectedDay.textContent = days[selectedDate.getDay()];
                    
                    // Update full date
                    selectedDateFull.textContent = `${days[selectedDate.getDay()]}, ${months[month]} ${day}, ${year}`;
                }

                function isPastDate(year, month, day) {
                    const checkDate = new Date(year, month, day);
                    checkDate.setHours(0, 0, 0, 0); // Reset time for accurate comparison
                    return checkDate < today;
                }

                function createCalendar(year, month) {
                    const calendarBody = document.getElementById('calendar-body');
                    calendarBody.innerHTML = '';

                    const daysInMonth = new Date(year, month + 1, 0).getDate();
                    const firstDay = new Date(year, month, 1).getDay();

                    updateMonthYearDisplay();

                    let date = 1;
                    for (let i = 0; i < 6; i++) {
                        const row = document.createElement('tr');
                        for (let j = 0; j < 7; j++) {
                            if (i === 0 && j < firstDay) {
                                const cell = document.createElement('td');
                                row.appendChild(cell);
                            } else if (date > daysInMonth) {
                                break;
                            } else {
                                const cell = document.createElement('td');
                                cell.textContent = date;
                                
                                // Check if the date is in the past
                                if (isPastDate(year, month, date)) {
                                    cell.classList.add('disabled');
                                    cell.title = "Past dates cannot be selected";
                                } else {
                                    cell.addEventListener('click', () => {
                                        const selectedCells = document.querySelectorAll('.selected');
                                        selectedCells.forEach(selected => selected.classList.remove('selected'));
                                        cell.classList.add('selected');

                                        // Automatically input the selected date into the date input field
                                        const selectedDate = new Date(year, month, parseInt(cell.textContent));
                                        const dateString = 
                                            selectedDate.getFullYear() + '-' + 
                                            ('0' + (selectedDate.getMonth() + 1)).slice(-2) + '-' + 
                                            ('0' + selectedDate.getDate()).slice(-2);

                                        datePicker.value = dateString;

                                        // Update additional date details
                                        updateDateDetails(year, month, parseInt(cell.textContent));
                                    });
                                }
                                
                                row.appendChild(cell);
                                date++;
                            }
                        }
                        calendarBody.appendChild(row);
                        if (date > daysInMonth) break;
                    }
                }

                // Restrict previous month button if it would lead to past dates
                function updatePrevButtonState() {
                    const prevMonth = new Date(currentYear, currentMonth - 1, 1);
                    const lastDayPrevMonth = new Date(currentYear, currentMonth, 0);
                    
                    // If the entire previous month is in the past, disable the button
                    if (lastDayPrevMonth < today) {
                        prevButton.disabled = true;
                        prevButton.classList.add('disabled');
                    } else {
                        prevButton.disabled = false;
                        prevButton.classList.remove('disabled');
                    }
                }

                // Previous month button
                prevButton.addEventListener('click', () => {
                    currentMonth--;
                    if (currentMonth < 0) {
                        currentMonth = 11;
                        currentYear--;
                    }
                    createCalendar(currentYear, currentMonth);
                    updatePrevButtonState();
                });

                // Next month button
                nextButton.addEventListener('click', () => {
                    currentMonth++;
                    if (currentMonth > 11) {
                        currentMonth = 0;
                        currentYear++;
                    }
                    createCalendar(currentYear, currentMonth);
                    updatePrevButtonState();
                });

                // Set min attribute on date input to prevent selecting past dates
                const todayFormatted = 
                    today.getFullYear() + '-' + 
                    ('0' + (today.getMonth() + 1)).slice(-2) + '-' + 
                    ('0' + today.getDate()).slice(-2);
                datePicker.setAttribute('min', todayFormatted);

                // Date input change listener
                datePicker.addEventListener('change', () => {
                    const inputDate = new Date(datePicker.value);
                    
                    // Validate input date is not in the past
                    if (inputDate < today) {
                        alert("You cannot select a date in the past.");
                        datePicker.value = todayFormatted;
                        inputDate = new Date(todayFormatted);
                    }
                    
                    currentYear = inputDate.getFullYear();
                    currentMonth = inputDate.getMonth();
                    
                    createCalendar(currentYear, currentMonth);
                    
                    // Find and select the correct date cell
                    const calendarBody = document.getElementById('calendar-body');
                    const cells = calendarBody.getElementsByTagName('td');
                    for (let cell of cells) {
                        if (cell.textContent && 
                            parseInt(cell.textContent) === inputDate.getDate() &&
                            !cell.classList.contains('disabled')) {
                            const selectedCells = document.querySelectorAll('.selected');
                            selectedCells.forEach(selected => selected.classList.remove('selected'));
                            cell.classList.add('selected');
                            
                            // Update additional date details
                            updateDateDetails(currentYear, currentMonth, inputDate.getDate());
                            break;
                        }
                    }
                    
                    updatePrevButtonState();
                });

                // Initialize calendar with current date details
                createCalendar(currentYear, currentMonth);

                // Select today's date if it's available
                const todayDate = today.getDate();
                const cells = document.querySelectorAll('#calendar-body td');
                for (let cell of cells) {
                    if (cell.textContent && parseInt(cell.textContent) === todayDate && !cell.classList.contains('disabled')) {
                        cell.classList.add('selected');
                        
                        // Set the date input value
                        datePicker.value = todayFormatted;
                        
                        // Update date details
                        updateDateDetails(currentYear, currentMonth, todayDate);
                        break;
                    }
                }

                // Initial check for previous button state
                updatePrevButtonState();
            </script>
        </div>
        <table class="list-container">
                <thead>
                <tr class="list-header">
                    <th class="list-header-cell left">Applicant Assessment</th>
                    <th class="list-header-cell centered">
                    <div style="display: flex; align-items: center; justify-content: center;">
                        <span>Outcome</span>
                        <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#666666" style="margin-left: 4px;">
                        <path d="M478-240q21 0 35.5-14.5T528-290q0-21-14.5-35.5T478-340q-21 0-35.5 14.5T428-290q0 21 14.5 35.5T478-240Zm2 160q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Zm4-172q25 0 43.5 16t18.5 40q0 22-13.5 39T502-525q-23 20-40.5 44T444-427q0 14 10.5 23.5T479-394q15 0 25.5-10t13.5-25q4-21 18-37.5t30-31.5q23-22 39.5-48t16.5-58q0-51-41.5-83.5T484-720q-38 0-72.5 16T359-655q-7 12-4.5 25.5T368-609q14 8 29 5t25-17q11-15 27.5-23t34.5-8Z"/>
                        </svg>
                    </div>
                    </th>

                    <th class="list-header-cell right">
                    <div style="display: inline-flex; align-items: center; justify-content: flex-end; width: 100%;">
                        <span>Average</span>
                        <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#666666" style="margin-left: 4px;">
                        <path d="M478-240q21 0 35.5-14.5T528-290q0-21-14.5-35.5T478-340q-21 0-35.5 14.5T428-290q0 21 14.5 35.5T478-240Zm2 160q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Zm4-172q25 0 43.5 16t18.5 40q0 22-13.5 39T502-525q-23 20-40.5 44T444-427q0 14 10.5 23.5T479-394q15 0 25.5-10t13.5-25q4-21 18-37.5t30-31.5q23-22 39.5-48t16.5-58q0-51-41.5-83.5T484-720q-38 0-72.5 16T359-655q-7 12-4.5 25.5T368-609q14 8 29 5t25-17q11-15 27.5-23t34.5-8Z"/>
                        </svg>
                    </div>
                    </th>

                </tr>
                </thead>
                <tbody>
                <tr class="list-row">
                    <td class="list-cell">
                    <span class="<?php echo $assessment['class']; ?>">
                        <?php echo $assessment['text']; ?>
                    </span>
                    </td>
                    <td class="list-cell centered">
                    <div class="score-value" style="color: <?php echo $scoreDetails['text_color']; ?>">
                        <div class="progress-bar <?php echo $scoreDetails['progress_class']; ?>">
                            <div class="progress-bar-fill" style="width: <?php echo $scoreDetails['progress']; ?>%;"></div>
                        </div>
                        <?php echo $scoreDetails['text']; ?><span style="color: #666666;"> in the assessment</span>
                    </div>
                    </td>
                    <td class="list-cell right">
                    <div class="score-percentage" style="color: <?php echo $scorePercentage['color']; ?>">
                        <?php echo $scorePercentage['percentage']; ?>
                    </div>
                    </td>
                </tr>
                </tbody>
        </table>

        <div class="demo-video">
            <?php if (!empty($student['demo_video'])): ?>
                <p class="docu-text">Demo Video</p>
                <iframe src="../STUDENTLOGIN/demovids/<?php echo htmlspecialchars($student['demo_video']); ?>" width="100%" height="400px" frameborder="0"></iframe>
            <?php else: ?>
                <p class="docu-text">Demo Video</p>
                <div class="document demo-video">
                    No Demo Video uploaded
                </div>
            <?php endif; ?>
        </div>
        <br>
        <br>

        <div class="documents-container">
            <?php if (!empty($student['cv_file'])): ?>
                <div class="document cv">
                    <p class="docu-text">Curriculum Vitae</p>
                    <iframe src="../STUDENTLOGIN/cv/<?php echo htmlspecialchars($student['cv_file']); ?>" width="100%" height="600px" frameborder="0"></iframe>
                </div>
            <?php else: ?>
                <div class="document cv">
                    No CV uploaded
                </div>
                <?php endif; ?>

            <?php if (!empty($student['endorsement_file'])): ?>
                <div class="document endorsement">
                    <p class="docu-text">Endorsement Letter</p>
                    <iframe src="../STUDENTLOGIN/endorse/<?php echo htmlspecialchars($student['endorsement_file']); ?>" width="100%" height="600px" frameborder="0"></iframe>
                </div>
            <?php else: ?>
                <div class="document endorsement">
                    No Endorsement uploaded
                </div>
            <?php endif; ?>
        </div>

    </div>
    

    <script>
                (function(){if(!window.chatbase||window.chatbase("getState")!=="initialized"){window.chatbase=(...arguments)=>{if(!window.chatbase.q){window.chatbase.q=[]}window.chatbase.q.push(arguments)};window.chatbase=new Proxy(window.chatbase,{get(target,prop){if(prop==="q"){return target.q}return(...args)=>target(prop,...args)}})}const onLoad=function(){const script=document.createElement("script");script.src="https://www.chatbase.co/embed.min.js";script.id="sqZ5VD70WA_0wO97JZLEZ";script.domain="www.chatbase.co";document.body.appendChild(script)};if(document.readyState==="complete"){onLoad()}else{window.addEventListener("load",onLoad)}})();
    </script>
    <!-- FOOTER -->
        <div class="footbg">
            <div class="properdiv">
                <footer>
        
                    <!-- Logo Section -->
                    <div class="rightside">
                        <h2 class="university-name">UNIVERSITY OF CALOOCAN CITY</h2>
                        <h4 class="program-name">COMPUTER SCIENCE DEPARTMENT</h4>
                        <p>
                            Biglang Awa Street <br> Cor 11th Ave Catleya,<br> Caloocan 1400 Metro Manila, Philippines
                        </p>
                        <br>
                        <p style="margin: 0">
                            <strong>Phone:</strong>&nbsp;(02) 5310 6855
                        </p>
                        <p style="margin: 0">
                            <strong>Email:</strong>&nbsp;support@uccinternshipportal.ph
                        </p>
    
                    </div>
                
                    <!-- Internship Seekers Section -->
                    <div class="centerside">
                        <h4>INTERNSHIP SEEKERS</h4>
                        <ul>
                            <li><a href="companyloginpage.php#advertise">Internship by Company</a></li>
                            <li><a href="companyloginpage.php#advertise">Internship by City</a></li>
                            <li><a href="companyloginpage.php#advertise">Search Nearby Internship</a></li>
                        </ul>
                    </div>
                
                    <!-- Employers Section -->
                    <div class="centerside">
                        <h4>EMPLOYERS</h4>
                        <ul>
                            <li><a href="companyloginpage.php">Post Internships</a></li>
                        </ul>
                    </div>
                
                    <!-- About Interflo Section -->
                    <div class="centerside">
                        <h4>ABOUT INTERNFLO</h4>
                        <ul>
                            <li><a href="companyloginpage.php#about">About Us</a></li>
                            <li><a href="companyloginpage.php#chatbot">How It Works</a></li>
                            <li><a href="companyloginpage.php#contact">Contact Us</a></li>
                        </ul>
                    </div>
                
                </footer>
            </div>
        </div>
        <div class="underfooter-bg">
            <div class="underfooter">
                <div class="uf-content">
                    <p>Copyright <strong>University of Caloocan City</strong> Internflo©2025. All Rights Reserved</p>
                </div>
            </div>
        </div>
    <!-- FOOTER -->	
</body>
</html>
<?php
// Initialize database connection
require_once 'config.php';

// Include PHPMailer classes
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set header to return JSON response
header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Check if the action is hire_applicant
if (!isset($_POST['action']) || $_POST['action'] !== 'hire_applicant') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action'
    ]);
    exit;
}

// Get and validate the POST parameters
$student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
$internshipad_id = isset($_POST['internshipad_id']) ? (int)$_POST['internshipad_id'] : 0;

// Validate the IDs
if ($student_id <= 0 || $internshipad_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid student or internship ID'
    ]);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // First, get all the required data from studentapplication
    $select_query = "SELECT application_id, internshipad_id, student_id, first_name, last_name, 
                            address, email, phone_number, cv_file, endorsement_file, 
                            assessment_score, demo_video, portfolio_link, application_date 
                     FROM studentapplication 
                     WHERE student_id = ? AND internshipad_id = ?";
    
    $select_stmt = $conn->prepare($select_query);
    
    if (!$select_stmt) {
        throw new Exception("Prepare select failed: " . $conn->error);
    }
    
    $select_stmt->bind_param("ii", $student_id, $internshipad_id);
    
    if (!$select_stmt->execute()) {
        throw new Exception("Execute select failed: " . $select_stmt->error);
    }
    
    $result = $select_stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("No application found with the provided IDs");
    }
    
    $application_data = $result->fetch_assoc();
    
    // Debug: Log the original assessment score
    error_log("Original assessment_score: " . $application_data['assessment_score']);
    
    // Fetch company_name and internship_title for the email BEFORE deleting the record
    $getEmailStmt = $conn->prepare("
        SELECT sa.application_id, sa.email, sa.first_name, sa.last_name, i.internship_title, c.company_name  
        FROM studentapplication sa
        JOIN internshipad i ON sa.internshipad_id = i.internship_id
        LEFT JOIN recruiters r ON i.user_id = r.id
        LEFT JOIN approvedrecruiters ar ON i.user_id = ar.id
        LEFT JOIN (
            SELECT id, company_name FROM recruiters
            UNION
            SELECT id, company_name FROM approvedrecruiters
        ) c ON i.user_id = c.id
        WHERE sa.student_id = ? AND sa.internshipad_id = ?
    ");
    
    if (!$getEmailStmt) {
        throw new Exception("Prepare email details query failed: " . $conn->error);
    }
    
    $getEmailStmt->bind_param("ii", $student_id, $internshipad_id);
    
    if (!$getEmailStmt->execute()) {
        throw new Exception("Execute email details query failed: " . $getEmailStmt->error);
    }
    
    $emailResult = $getEmailStmt->get_result();
    
    if ($emailResult->num_rows === 0) {
        throw new Exception("No email details found for the provided IDs");
    }
    
    $emailData = $emailResult->fetch_assoc();
    $getEmailStmt->close();
    
    // Use direct SQL query with properly escaped values
    $insert_query = "INSERT INTO hired_applicants (
                        application_id,
                        internshipad_id,
                        student_id,
                        first_name,
                        last_name,
                        address,
                        email,
                        phone_number,
                        cv_file,
                        endorsement_file,
                        assessment_score,
                        demo_video,
                        portfolio_link,
                        application_date,
                        Status
                    ) VALUES (
                        {$application_data['application_id']},
                        {$application_data['internshipad_id']},
                        {$application_data['student_id']},
                        '{$conn->real_escape_string($application_data['first_name'])}',
                        '{$conn->real_escape_string($application_data['last_name'])}',
                        '{$conn->real_escape_string($application_data['address'])}',
                        '{$conn->real_escape_string($application_data['email'])}',
                        '{$conn->real_escape_string($application_data['phone_number'])}',
                        '{$conn->real_escape_string($application_data['cv_file'])}',
                        '{$conn->real_escape_string($application_data['endorsement_file'])}',
                        '{$conn->real_escape_string($application_data['assessment_score'])}',
                        '{$conn->real_escape_string($application_data['demo_video'])}',
                        '{$conn->real_escape_string($application_data['portfolio_link'])}',
                        '{$conn->real_escape_string($application_data['application_date'])}',
                        'Hired'
                    )";
    
    error_log("Executing SQL: " . $insert_query);
    
    // Execute direct query
    if (!$conn->query($insert_query)) {
        throw new Exception("Insert failed: " . $conn->error);
    }
    
    // Delete the record from studentapplication table
    $delete_query = "DELETE FROM studentapplication 
                    WHERE student_id = ? 
                    AND internshipad_id = ?";
    
    $delete_stmt = $conn->prepare($delete_query);
    
    if (!$delete_stmt) {
        throw new Exception("Prepare delete failed: " . $conn->error);
    }
    
    $delete_stmt->bind_param("ii", $student_id, $internshipad_id);
    
    if (!$delete_stmt->execute()) {
        throw new Exception("Execute delete failed: " . $delete_stmt->error);
    }
    
    // Commit the transaction
    $conn->commit();
    
    // Close all statements
    $select_stmt->close();
    $delete_stmt->close();
    
    // Send email to the applicant
    $mail = new PHPMailer(true);
    
    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'rogermalabananbusi@gmail.com'; // Replace with your email
        $mail->Password = 'fhnt amet zziu tlow'; // Replace with your email password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Email content configuration
        $mail->setFrom('rogermalabananbusi@gmail.com', 'Internflo');
        $mail->addAddress($application_data['email']);
        $mail->isHTML(true);
        $mail->Subject = 'Internship Application Status Update';

        // Email body with design
        $mail->Body = '
        <div style="font-family: Arial, sans-serif; text-align: center; color: #333; max-width: 600px; margin: auto;">
            <h1 style="background-color: #478831; color: white; padding: 10px 0; font-size: 24px;">
                APPLICATION STATUS UPDATE
            </h1>
            <p style="font-size: 18px; color: #333;">Dear ' . htmlspecialchars($emailData['first_name'] . ' ' . $emailData['last_name']) . ',</p>
            <p style="font-size: 16px; color: #555; line-height: 1.5;">
                We are pleased to inform you that after careful consideration of your application, 
                you have been selected for the <strong>' . htmlspecialchars($emailData['internship_title']) . '</strong> 
                position at <strong>' . htmlspecialchars($emailData['company_name']) . '</strong>.
            </p>
            <p style="font-size: 16px; color: #555; line-height: 1.5;">
                Congratulations on this achievement! Your skills and qualifications stood out among 
                the applicants, and we are excited to welcome you to our team.
            </p>
            <p style="margin-top: 30px; font-size: 14px; color: #777;">
                This is an automated message. Please do not reply to this email.
            </p>
        </div>';

        // Send email
        $mail->send();
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Applicant successfully hired and transferred to hired applicants. Notification email sent.'
        ]);

    } catch (Exception $e) {
        // Log the error or handle it as needed
        error_log("Email sending failed: " . $mail->ErrorInfo);
        
        // Return success response even if email fails, but log the error
        echo json_encode([
            'success' => true,
            'message' => 'Applicant successfully hired and transferred to hired applicants, but email notification failed.'
        ]);
    }

} catch (Exception $e) {
    // Rollback the transaction in case of error
    $conn->rollback();
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

// Close the database connection
$conn->close();
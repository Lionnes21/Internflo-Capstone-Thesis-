<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
$isLoggedIn = isset($_SESSION['advisor_id']) || isset($_SESSION['user_id']);

if (!$isLoggedIn) {
    // Return JSON error response
    echo json_encode([
        'success' => false,
        'error' => 'You are not logged in. Please log in to continue.'
    ]);
    exit();
}

// Get logged in user ID
$userId = $_SESSION['advisor_id'] ?? $_SESSION['user_id'];

// Database connection
$conn = new mysqli("localhost", "u798912504_root", "Internfloucc2025*", "u798912504_internflo");


// Check connection
if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $conn->connect_error
    ]);
    exit();
}

// Get sender email from m_advisors table
$stmt = $conn->prepare("SELECT email FROM m_advisors WHERE advisor_id = ?");
$stmt->bind_param('s', $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode([
        'success' => false,
        'error' => 'User not found in the database.'
    ]);
    exit();
}

$sender_email = $user['email'];

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Get form data
        $company_name = $_POST['company_name'] ?? '';
        $company_industry = $_POST['company_industry'] ?? '';
        $company_address = $_POST['company_address'] ?? '';
        $company_phone = $_POST['company_phone'] ?? '';
        $company_email = $_POST['company_email'] ?? '';
        $recruiter_email = $_POST['recruiter_email'] ?? '';
        $recruiter_mobile = $_POST['recruiter_mobile'] ?? '';
        $certificate_of_registration = $_POST['certificate_of_registration'] ?? '';
        $bir_registration = $_POST['bir_registration'] ?? '';
        $business_permit = $_POST['business_permit'] ?? '';
        
        // Set default values
        $recipient_type = 'recruiter';
        $subject = 'Internship Agreement';
        $content = 'Copy of Agreement';
        
        // Handle file upload
        $file_path = '';
        if (isset($_FILES['file-agreement']) && $_FILES['file-agreement']['error'] == 0) {
            $upload_dir = 'agreements/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $filename = time() . '_' . basename($_FILES['file-agreement']['name']);
            $target_file = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['file-agreement']['tmp_name'], $target_file)) {
                $file_path = $target_file;
            } else {
                throw new Exception("Error uploading file.");
            }
        } else {
            throw new Exception("No file uploaded or file upload error.");
        }
        
        // Insert data into messaging_agreement table
        $sql = "INSERT INTO messaging_agreement_company (
            sender_email, recipient_email, recipient_type, subject, content, file_path,
            company_name, company_industry, company_address, company_phone, company_email,
            recruiter_email, recruiter_mobile, certificate_of_registration, bir_registration, business_permit
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            'ssssssssssssssss',
            $sender_email, $recruiter_email, $recipient_type, $subject, $content, $file_path,
            $company_name, $company_industry, $company_address, $company_phone, $company_email,
            $recruiter_email, $recruiter_mobile, $certificate_of_registration, $bir_registration, $business_permit
        );
        
        if ($stmt->execute()) {
            // Return JSON success response
            echo json_encode([
                'success' => true,
                'message' => 'Agreement successfully forwarded to the company.'
            ]);
        } else {
            throw new Exception("Database error: " . $stmt->error);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method.'
    ]);
}

$conn->close();
?>
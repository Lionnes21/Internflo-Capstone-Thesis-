<?php
// Save this as get_file_details.php
// Turn off error reporting for production to prevent HTML errors in JSON output
error_reporting(0);
ini_set('display_errors', 0);

// Set header to JSON before any output
header('Content-Type: application/json');

// Direct database connection
try {
    $conn = new mysqli("localhost", "u798912504_root", "Internfloucc2025*", "u798912504_internflo");
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

if(isset($_GET['message_id'])) {
    $messageId = intval($_GET['message_id']);
    
    try {
        // Prepare the query to get all the required fields
        $query = "SELECT 
                    bir_registration,
                    certificate_of_registration,
                    business_permit,
                    company_name,
                    company_industry,
                    company_address,
                    company_phone,
                    company_email,
                    recruiter_email,
                    recruiter_mobile,
                    file_path
                  FROM messaging_agreement_company
                  WHERE message_id = ?";
        
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $messageId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $fileDetails = $result->fetch_assoc();
            echo json_encode($fileDetails);
        } else {
            echo json_encode(['error' => 'No file details found']);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['error' => 'Database query error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'No message ID provided']);
}

// Close the connection
$conn->close();
?>
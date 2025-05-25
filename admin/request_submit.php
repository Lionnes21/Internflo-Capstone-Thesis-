<?php
include 'config.php';
session_start();

// Set header for JSON response
header('Content-Type: application/json');

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate required fields
if (empty($_POST['practicumCoordinator']) || empty($_POST['practicumCoordinatorEmail'])) {
    echo json_encode(['success' => false, 'message' => 'Practicum coordinator information is required']);
    exit;
}

try {
    // Get the practicum coordinator name based on selected ID
    $coordinator_id = $_POST['practicumCoordinator'];
    
    // Use the same join query as in your main page
    $coordinator_query = "SELECT a.advisor_id, a.full_name 
                         FROM m_advisor_assignments a
                         INNER JOIN m_advisors b ON a.advisor_id = b.id
                         WHERE a.advisor_id = ?";
    
    $stmt = $conn->prepare($coordinator_query);
    $stmt->bind_param("i", $coordinator_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $coordinator_name = '';
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $coordinator_name = $row['full_name'];
    } else {
        throw new Exception('Selected coordinator not found');
    }

    // Insert data into messaging_agreement table
    $insert_query = "INSERT INTO messaging_agreement (
        recipient_email,
        file_path,
        company_name,
        company_industry,
        company_address,
        company_phone,
        company_email,
        company_overview,
        recruiter_full_name,
        recruiter_email,
        recruiter_mobile,
        certificate_of_registration,
        bir_registration,
        business_permit,
        practicum_coordinator
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param(
        "sssssssssssssss",
        $_POST['practicumCoordinatorEmail'],
        $_POST['agreementFile'],
        $_POST['companyName'],
        $_POST['industry'],
        $_POST['address'],
        $_POST['phone'],
        $_POST['companywebsite'],
        $_POST['overview'],
        $_POST['fullName'],
        $_POST['email'],
        $_POST['mobileNumber'],
        $_POST['certReg'],
        $_POST['birReg'],
        $_POST['businessPermit'],
        $coordinator_name
    );
    
    if ($stmt->execute()) {
        // Log the successful action
        error_log("Agreement sent to coordinator {$coordinator_name} <{$_POST['practicumCoordinatorEmail']}> for company {$_POST['companyName']}");
        
        // Success response
        echo json_encode([
            'success' => true,
            'message' => 'Agreement sent successfully to ' . $coordinator_name
        ]);
    } else {
        throw new Exception('Database error: ' . $stmt->error);
    }
    
} catch (Exception $e) {
    // Log the error
    error_log("Error sending agreement: " . $e->getMessage());
    
    // Error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
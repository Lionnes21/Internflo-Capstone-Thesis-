<?php
// Start session and connect to database
session_start();
header('Content-Type: application/json');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$conn = new mysqli("localhost", "u798912504_root", "Internfloucc2025*", "u798912504_internflo");

// Check connection
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die(json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]));
}

// Get JSON data from request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Log received data
error_log("Received data: " . print_r($data, true));

if (!isset($data['message_id'])) {
    error_log("Message ID not provided");
    echo json_encode([
        'status' => 'error',
        'message' => 'Message ID is required'
    ]);
    exit;
}

$message_id = $data['message_id'];
error_log("Processing message_id: " . $message_id);

// Start transaction
$conn->begin_transaction();

try {
    // First, get the message data
    $stmt = $conn->prepare("SELECT * FROM messaging WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $message_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $message = $result->fetch_assoc();

    if (!$message) {
        throw new Exception('Message not found for ID: ' . $message_id);
    }

    error_log("Found message: " . print_r($message, true));

    // Insert into messaging_inboxs table
// Insert into messaging_inboxs table
$stmt = $conn->prepare("INSERT INTO messaging_inbox
    (message_id, sender_email, recipient_email, recipient_type, subject, content, timestamp, moved_timestamp) 
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");

if (!$stmt) {
    throw new Exception("Prepare insert failed: " . $conn->error);
}

$stmt->bind_param("issssss", 
    $message['id'],
    $message['sender_email'],
    $message['recipient_email'],
    $message['recipient_type'],
    $message['subject'],
    $message['content'],
    $message['timestamp']  // Original timestamp from messaging table
);
    if (!$stmt->execute()) {
        throw new Exception("Insert failed: " . $stmt->error);
    }

    error_log("Successfully inserted into messaging_inboxs");

    // Delete from messaging table
    $stmt = $conn->prepare("DELETE FROM messaging WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Prepare delete failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $message_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Delete failed: " . $stmt->error);
    }

    error_log("Successfully deleted from messaging");

    // Commit transaction
    $conn->commit();
    error_log("Transaction committed successfully");

    echo json_encode([
        'status' => 'success',
        'message' => 'Message moved to trash successfully'
    ]);

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    error_log("Error occurred: " . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
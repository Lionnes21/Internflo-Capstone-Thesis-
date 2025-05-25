<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['message_id']) || !isset($_POST['source_table'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or invalid request']);
    exit;
}

$conn = new mysqli("localhost", "u798912504_root", "Internfloucc2025*", "u798912504_internflo");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $conn->begin_transaction();

    $messageId = (int)$_POST['message_id'];
    $sourceTable = $_POST['source_table'];
    
    // Validate source table name to prevent SQL injection
    if (!in_array($sourceTable, ['messaging_deleted', 'messaging_inbox'])) {
        throw new Exception("Invalid source table");
    }

    // Determine target table based on source
    $targetTable = ($sourceTable === 'messaging_deleted') ? 'messaging_sent' : 'messaging';
    
    // Fetch the message from source table
    $stmt = $conn->prepare("SELECT * FROM $sourceTable WHERE message_id = ?");
    $stmt->bind_param("i", $messageId);
    $stmt->execute();
    $result = $stmt->get_result();
    $message = $result->fetch_assoc();

    if (!$message) {
        throw new Exception("Message not found");
    }

    // Prepare the insert statement based on target table
    if ($targetTable === 'messaging_sent') {
        $stmt = $conn->prepare("
            INSERT INTO messaging_sent 
            (message_id, sender_email, recipient_email, recipient_type, subject, content, timestamp) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param("issssss", 
            $message['message_id'],
            $message['sender_email'],
            $message['recipient_email'],
            $message['recipient_type'],
            $message['subject'],
            $message['content'],
            $message['timestamp']
        );
    } else { // messaging table
        $stmt = $conn->prepare("
            INSERT INTO messaging 
            (id, sender_email, recipient_email, recipient_type, subject, content) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        // Use the message_id from messaging_inbox as the id in messaging table
        $stmt->bind_param("isssss", 
            $message['message_id'],
            $message['sender_email'],
            $message['recipient_email'],
            $message['recipient_type'],
            $message['subject'],
            $message['content']
        );
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to restore message. The message ID might already exist.");
    }

    // Delete from source table
    $stmt = $conn->prepare("DELETE FROM $sourceTable WHERE message_id = ?");
    $stmt->bind_param("i", $messageId);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to remove message from source table");
    }

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die('Unauthorized access');
}

$conn = new mysqli("localhost", "u798912504_root", "Internfloucc2025*", "u798912504_internflo");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$data = json_decode(file_get_contents('php://input'), true);
$messageId = $data['message_id'];

// Start transaction
$conn->begin_transaction();

try {
    // Fetch the message from messaging_sent
    $stmt = $conn->prepare("SELECT * FROM messaging_sent WHERE message_id = ?");
    $stmt->bind_param('i', $messageId);
    $stmt->execute();
    $result = $stmt->get_result();
    $message = $result->fetch_assoc();

    if ($message) {
        // Get current timestamp
        $deletedTimestamp = date('Y-m-d H:i:s');

        // Insert the message into messaging_deleted
        $stmt = $conn->prepare("INSERT INTO messaging_deleted (message_id, sender_email, recipient_email, recipient_type, subject, content, timestamp, deleted_timestamp) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('isssssss', 
            $message['message_id'], 
            $message['sender_email'], 
            $message['recipient_email'], 
            $message['recipient_type'], 
            $message['subject'], 
            $message['content'], 
            $message['timestamp'],
            $deletedTimestamp
        );
        $stmt->execute();

        // Delete the message from messaging_sent
        $stmt = $conn->prepare("DELETE FROM messaging_sent WHERE message_id = ?");
        $stmt->bind_param('i', $messageId);
        $stmt->execute();
    }

    // Commit transaction
    $conn->commit();
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>

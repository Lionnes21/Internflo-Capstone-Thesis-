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

    // Delete from the specified table
    $stmt = $conn->prepare("DELETE FROM $sourceTable WHERE message_id = ?");
    $stmt->bind_param("i", $messageId);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete the message");
    }

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
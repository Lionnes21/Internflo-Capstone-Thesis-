<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Unauthorized access');
}

include 'config.php';

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {
    $uploadDir = 'pics/';
    $uploadFile = $uploadDir . basename($_FILES['profile_pic']['name']);

    // Check if directory exists, if not, create it
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Move uploaded file to the designated directory
    if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $uploadFile)) {
        // File uploaded successfully. Update the database with the file path.
        $sql = "UPDATE students SET profile_pic = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $uploadFile, $userId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Profile picture updated successfully', 'path' => $uploadFile]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update profile picture in database']);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
    }

    $conn->close();
}
?>

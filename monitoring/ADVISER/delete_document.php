<?php
session_start();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['advisor_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Database connection
$servername = "localhost";
$username = "u798912504_root";
$password = "Internfloucc2025*"; // Update with your database password
$dbname = "u798912504_internflo";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get file path from POST data
    $filePath = $_POST['file_path'] ?? '';
    if (empty($filePath)) {
        throw new Exception('File path is required');
    }

    // First, check if the file exists in the database and belongs to this advisor
    $stmt = $conn->prepare("
        SELECT id, file_path 
        FROM m_instructor_documents 
        WHERE file_path = ? AND advisor_id = ?
    ");
    $stmt->execute([$filePath, $_SESSION['advisor_id']]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$document) {
        throw new Exception('Document not found or unauthorized');
    }

    // Delete the physical file
    $fullPath = '../uploads/' . basename($filePath);
    if (file_exists($fullPath) && !unlink($fullPath)) {
        throw new Exception('Failed to delete file from server');
    }

    // Delete the database record
    $stmt = $conn->prepare("DELETE FROM m_instructor_documents WHERE id = ?");
    $stmt->execute([$document['id']]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Document deleted successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
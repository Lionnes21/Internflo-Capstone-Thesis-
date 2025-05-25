<?php
session_start();
header('Content-Type: application/json'); // Explicitly set content type to JSON

$studentId = $_SESSION['student_id'];
$documentType = $_GET['document_type'];
$type = isset($_GET['type']) ? $_GET['type'] : 'regular';

// Database connection
$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "student_registration";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Return JSON error response
    echo json_encode([
        'success' => false, 
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit();
}

try {
    // Prepare the select statement
    $sql = "SELECT file_path FROM m_ojt_documents WHERE student_id = :student_id AND document_type = :document_type";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':student_id' => $studentId, ':document_type' => $documentType]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($file) {
        // Remove the file from the server
        if (file_exists($file['file_path'])) {
            if (!unlink($file['file_path'])) {
                // Failed to delete file from server
                echo json_encode([
                    'success' => false, 
                    'message' => 'Failed to delete file from server'
                ]);
                exit();
            }
        }
        
        // Remove the file record from the database
        $deleteSql = "DELETE FROM m_ojt_documents WHERE student_id = :student_id AND document_type = :document_type";
        $deleteStmt = $conn->prepare($deleteSql);
        $result = $deleteStmt->execute([':student_id' => $studentId, ':document_type' => $documentType]);

        if ($result) {
            // Successfully deleted
            echo json_encode(['success' => true]);
        } else {
            // Failed to delete from database
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to delete record from database'
            ]);
        }
    } else {
        // No file found
        echo json_encode([
            'success' => false, 
            'message' => 'No file found to delete'
        ]);
    }
} catch (Exception $e) {
    // Catch any unexpected errors
    echo json_encode([
        'success' => false, 
        'message' => 'An unexpected error occurred: ' . $e->getMessage()
    ]);
}
exit();
?>
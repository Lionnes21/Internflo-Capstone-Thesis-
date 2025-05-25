<?php
// downloadInstructorFile.php

// Start session if not already started
session_start();

// Function to sanitize file path
function sanitizeFilePath($path) {
    // Remove any directory traversal attempts
    $path = str_replace(['../', '..\\'], '', $path);
    return $path;
}

// Check if file parameter exists
if (isset($_GET['file']) && isset($_GET['filename'])) {
    // Base directory where files are stored
    $baseDir = 'uploads/instructor_documents/'; // Adjust this path to match your file storage location
    
    // Get and sanitize the file path
    $fileName = sanitizeFilePath($_GET['file']);
    $displayName = $_GET['filename'];
    $filePath = $baseDir . $fileName;

    // Verify the file exists and is within allowed directory
    if (file_exists($filePath) && is_file($filePath) && strpos(realpath($filePath), realpath($baseDir)) === 0) {
        // Get file extension
        $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        // Set appropriate Content-Type header based on file extension
        switch ($fileExtension) {
            case 'pdf':
                $contentType = 'application/pdf';
                break;
            case 'doc':
                $contentType = 'application/msword';
                break;
            case 'docx':
                $contentType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                break;
            default:
                $contentType = 'application/octet-stream';
        }

        // Set headers for file download
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . basename($displayName) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: public');
        
        // Clear output buffer
        ob_clean();
        flush();
        
        // Read and output file
        readfile($filePath);
        exit;
    }
}

// If we get here, something went wrong
header('HTTP/1.0 404 Not Found');
echo 'File not found or access denied.';
exit;
?>
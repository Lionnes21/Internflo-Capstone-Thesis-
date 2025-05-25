<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering
ob_start();

// Define upload directory
$uploadDir = 'weeklyReport_uploads/';

// Get the file path from the URL
if (isset($_GET['file'])) {
    // Sanitize the input to prevent directory traversal attacks
    $fileName = basename($_GET['file']);
    $filePath = realpath($uploadDir . $fileName);

    // Debugging output
    echo "File Path: " . htmlspecialchars($filePath) . "<br>";

    // Check if file exists and is readable
    if ($filePath && file_exists($filePath) && is_readable($filePath)) {
        // Get file size
        $fileSize = filesize($filePath);
        if ($fileSize === false) {
            echo "Error getting file size.";
            exit;
        }

        // Debugging output
        echo "File Size: " . $fileSize . " bytes<br>";

        // Determine content type based on file extension
        $fileType = pathinfo($filePath, PATHINFO_EXTENSION);
        switch ($fileType) {
            case 'pdf':
                header('Content-Type: application/pdf');
                break;
            case 'doc':
                header('Content-Type: application/msword');
                break;
            case 'docx':
                header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
                break;
            default:
                echo "Unsupported file type.";
                exit;
        }

        // Set headers for file transfer
        header('Content-Description: File Transfer');
        header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . $fileSize);

        // Clear the output buffer before sending the file
        ob_clean();
        flush();

        // Read and send the file
        if (readfile($filePath) === false) {
            echo "Error reading file."; // Additional debugging output
            error_log("Error reading file: $filePath");
        }
        exit;
    } else {
        echo "File not found or not readable.";
    }
} else {
    echo "No file specified.";
}

// End output buffering
ob_end_flush();
?>

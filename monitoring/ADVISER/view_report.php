<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['advisor_id'])) {
    die("Unauthorized access");
}

// Database connection
    $servername = "localhost";
    $username = "u798912504_root";
    $password = "Internfloucc2025*"; // Update with your database password
    $dbname = "u798912504_internflo";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get the filename from GET parameter and sanitize it
    $reportFile = filter_var($_GET['file'] ?? '', FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

    if (empty($reportFile)) {
        die("No file specified");
    }

    // Explicitly construct the full path
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/monitoring/weeklyReport_uploads/';
    $filePath = $uploadDir . basename($reportFile);

    // Extensive debugging information
    echo "Debug Information:<br>";
    echo "Report File: " . htmlspecialchars($reportFile) . "<br>";
    echo "Upload Directory: " . htmlspecialchars($uploadDir) . "<br>";
    echo "Full File Path: " . htmlspecialchars($filePath) . "<br>";

    // More comprehensive file existence and security checks
    if (!file_exists($filePath)) {
        echo "File does not exist<br>";
        echo "Directory contents:<br>";
        
        // List directory contents for debugging
        if (is_dir($uploadDir)) {
            $files = scandir($uploadDir);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    echo htmlspecialchars($file) . "<br>";
                }
            }
        } else {
            echo "Upload directory does not exist!<br>";
            echo "Actual upload directory path: " . $uploadDir . "<br>";
        }
        
        die("File not found: " . htmlspecialchars($filePath));
    }

    // Additional security checks
    if (!is_readable($filePath)) {
        die("File is not readable");
    }

    // Validate file extension
    $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        die("Invalid file type");
    }

    // Set appropriate headers
    switch ($fileExtension) {
        case 'pdf':
            header('Content-Type: application/pdf');
            break;
        case 'doc':
        case 'docx':
            header('Content-Type: application/msword');
            break;
        case 'jpg':
        case 'jpeg':
            header('Content-Type: image/jpeg');
            break;
        case 'png':
            header('Content-Type: image/png');
            break;
        default:
            header('Content-Type: application/octet-stream');
    }

    // Prevent file download, just view
    header('Content-Disposition: inline; filename="' . basename($filePath) . '"');

    // Output file contents
    readfile($filePath);
    exit();

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<?php
session_start();

header('Content-Type: application/json');

// Check login
if (!isset($_SESSION['advisor_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access'
    ]);
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

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_FILES['files']['name'][0])) {
        // Validate course details
        if (empty($_POST['documentType'])) {
            throw new Exception("No course selected");
        }

        // Decode course details from select option
        $courseDetails = json_decode($_POST['documentType'], true);
        
        // Validate decoded course details
        if (!$courseDetails || 
            !isset($courseDetails['program_id']) || 
            !isset($courseDetails['course_id']) || 
            !isset($courseDetails['year'])) {
            throw new Exception("Invalid course details");
        }

        // Validate file upload directory
        $uploadDir = '../uploads/instructor_documents/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Process multiple file uploads
        $successCount = 0;
        $errorMessages = [];

        foreach ($_FILES['files']['name'] as $key => $fileName) {
            $fileTmpName = $_FILES['files']['tmp_name'][$key];
            $fileSize = $_FILES['files']['size'][$key];
            $fileError = $_FILES['files']['error'][$key];
            $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            // Additional file validation
            $allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'png'];
            $maxFileSize = 5 * 1024 * 1024; // 5MB max

            if ($fileError !== UPLOAD_ERR_OK) {
                $errorMessages[] = "Upload error for $fileName: " . $fileError;
                continue;
            }

            if (!in_array($fileType, $allowedTypes)) {
                $errorMessages[] = "Invalid file type for $fileName. Allowed types: " . implode(', ', $allowedTypes);
                continue;
            }

            if ($fileSize > $maxFileSize) {
                $errorMessages[] = "File $fileName exceeds 5MB size limit";
                continue;
            }

            // Generate unique filename
            $uniqueFileName = uniqid() . '_' . $fileName;
            $uploadPath = $uploadDir . $uniqueFileName;

            // Move uploaded file
            if (move_uploaded_file($fileTmpName, $uploadPath)) {
                try {
                    // Insert file record into database
                    $stmt = $conn->prepare("
                        INSERT INTO m_instructor_documents 
                        (advisor_id, program_id, course_id, year, file_name, file_path, file_type, file_size, upload_date) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $result = $stmt->execute([
                        $_SESSION['advisor_id'],
                        $courseDetails['program_id'],
                        $courseDetails['course_id'],
                        $courseDetails['year'],
                        $fileName,
                        $uploadPath,
                        $fileType,
                        $fileSize
                    ]);
                    
                    if ($result) {
                        $successCount++;
                    } else {
                        $errorMessages[] = "Database insert failed for $fileName: " . print_r($stmt->errorInfo(), true);
                    }
                } catch (PDOException $e) {
                    $errorMessages[] = "Database error for $fileName: " . $e->getMessage();
                }
            } else {
                $errorMessages[] = "File upload failed for $fileName";
            }
        }

        // Return JSON response
        if ($successCount > 0) {
            echo json_encode([
                'status' => 'success',
                'message' => "$successCount file(s) uploaded successfully",
                'warnings' => !empty($errorMessages) ? $errorMessages : null
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Upload failed',
                'errors' => $errorMessages
            ]);
        }
        exit();
    }
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'status' => 'error',
        'message' => 'Upload failed',
        'errors' => [$e->getMessage()]
    ]);
    exit();
}
?>
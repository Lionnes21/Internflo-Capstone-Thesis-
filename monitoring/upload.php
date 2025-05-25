<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "u798912504_root";
$password = "Internfloucc2025*"; // Update with your database password
$dbname = "u798912504_internflo";


try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Debugging: Print session and user information
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'User not logged in', 'session' => $_SESSION]);
        exit();
    }

    // Get student ID from the students table
    try {
        $stmt = $conn->prepare("SELECT student_id FROM students WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            echo json_encode(['success' => false, 'message' => 'Student not found', 'user_id' => $_SESSION['user_id']]);
            exit();
        }

        $studentId = $student['student_id'];
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching student ID: ' . $e->getMessage()]);
        exit();
    }

    $targetDir = "uploads/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $uploadedBy = 'student';
    $uploadedFiles = [];
    $errors = [];

    function handleFileUpload($fileInputName, $documentType, $conn, $studentId, $uploadedBy) {
        global $targetDir;
        
        if (isset($_FILES[$fileInputName]) && !empty($_FILES[$fileInputName]['name'])) {
            $fileName = basename($_FILES[$fileInputName]['name']);
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['doc', 'docx', 'pdf'];
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                throw new Exception("Invalid file type for $documentType. Only DOC, DOCX, and PDF files are allowed.");
            }

            $uniqueFileName = uniqid() . '_' . $fileName;
            $targetFile = $targetDir . $uniqueFileName;
            
            if (move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $targetFile)) {
                // Check if document already exists
                $checkSql = "SELECT document_id FROM m_ojt_documents WHERE student_id = :student_id AND document_type = :document_type";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->execute([':student_id' => $studentId, ':document_type' => $documentType]);
                
                if ($checkStmt->rowCount() > 0) {
                    // Update existing document
                    $sql = "UPDATE m_ojt_documents SET 
                            file_name = :file_name, 
                            file_path = :file_path, 
                            upload_date = NOW() 
                            WHERE student_id = :student_id AND document_type = :document_type";
                } else {
                    // Insert new document
                    $sql = "INSERT INTO m_ojt_documents 
                            (student_id, document_type, file_name, file_path, upload_date, uploaded_by) 
                            VALUES 
                            (:student_id, :document_type, :file_name, :file_path, NOW(), :uploaded_by)";
                }

                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':student_id' => $studentId,
                    ':document_type' => $documentType,
                    ':file_name' => $fileName,
                    ':file_path' => $targetFile,
                    ':uploaded_by' => $uploadedBy
                ]);

                return true;
            } else {
                throw new Exception("Failed to move uploaded file for $documentType");
            }
        }
        return false;
    }

    try {
        // Handle regular document uploads
        $documents = [
            'moa-upload' => 'MOA',
            'acceptance-letter-upload' => 'Acceptance Letter',
            'parent-consent-upload' => 'Parent Consent',
            'medical-certificate-upload' => 'Medical Certificate',
            'registration-upload' => 'Registration Form'
        ];

        foreach ($documents as $inputName => $documentType) {
            if (handleFileUpload($inputName, $documentType, $conn, $studentId, $uploadedBy)) {
                $uploadedFiles[] = $documentType;
            }
        }

        // Handle additional document upload
        if (isset($_FILES['additional-document-upload']) && 
            !empty($_FILES['additional-document-upload']['name']) && 
            !empty($_POST['document-type'])) {
            $additionalDocumentType = trim($_POST['document-type']);
            if (handleFileUpload('additional-document-upload', $additionalDocumentType, $conn, $studentId, $uploadedBy)) {
                $uploadedFiles[] = $additionalDocumentType;
            }
        }

        if (!empty($uploadedFiles)) {
            echo json_encode([
                'success' => true,
                'message' => 'Files uploaded successfully: ' . implode(', ', $uploadedFiles)
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No files were uploaded'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
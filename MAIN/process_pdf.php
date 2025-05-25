<?php
// process_pdf.php - Create this as a separate file
require 'vendor/autoload.php';
use Smalot\PdfParser\Parser;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['cv'])) {
    $response = ['success' => false, 'message' => '', 'keywordMatch' => false];
    
    $fileTmpPath = $_FILES['cv']['tmp_name'];
    $fileName = $_FILES['cv']['name'];
    $fileSize = $_FILES['cv']['size'];
    $fileType = $_FILES['cv']['type'];
    
    $keywords = [
        'BACHELOR OF SCIENCE IN INFORMATION SYSTEM',
        'BSIS',
        'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY',
        'BSIT',
        'BACHELOR OF SCIENCE IN COMPUTER SCIENCE',
        'BSCS',
        'BACHELOR OF SCIENCE IN ENTERTAINMENT AND MULTIMEDIA COMPUTING',
        'BSEMC'
    ];

    if ($fileType === 'application/pdf' && $fileSize <= 2 * 1024 * 1024) {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($fileTmpPath);
            $pdfText = $pdf->getText();

            // Check for keywords
            foreach ($keywords as $keyword) {
                if (stripos($pdfText, $keyword) !== false) {
                    $response['keywordMatch'] = true;
                    break;
                }
            }

            // Database connection
            $pdo = new PDO("mysql:host=localhost;dbname=u798912504_internflo", "u798912504_root", "Internfloucc2025*");
            $stmt = $pdo->prepare("INSERT INTO pdf_content (file_name, content) VALUES (:file_name, :content)");
            $stmt->execute([
                ':file_name' => $fileName,
                ':content' => $pdfText,
            ]);

            $response['success'] = true;
            $response['message'] = 'PDF content successfully stored!' . 
                ($response['keywordMatch'] ? ' ICT-related course detected.' : '');

        } catch (Exception $e) {
            $response['message'] = 'Error processing PDF: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Invalid file. Ensure it is a PDF and under 2MB.';
    }
    
    echo json_encode($response);
    exit;
}
?>
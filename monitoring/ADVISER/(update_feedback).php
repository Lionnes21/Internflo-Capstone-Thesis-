<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "student_registration";

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get report ID from the report file name
    $stmt = $conn->prepare("SELECT id FROM m_weekly_reports WHERE report_file = ?");
    $stmt->execute([$data['report_file']]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($report) {
        // Update the status
        $updateStmt = $conn->prepare("UPDATE m_weekly_reports SET status = ? WHERE id = ?");
        $status = $data['status'];
        $updateStmt->execute([$status, $report['id']]);

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Report not found']);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
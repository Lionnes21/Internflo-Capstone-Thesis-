<?php
session_start();
include 'config.php';

$response = ['hasExistingForm' => false];

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $checkSql = "SELECT COUNT(*) as count FROM assessment_forms WHERE user_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $user_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $row = $result->fetch_assoc();
    $response['hasExistingForm'] = ($row['count'] > 0);
    $checkStmt->close();
}

header('Content-Type: application/json');
echo json_encode($response);
?>
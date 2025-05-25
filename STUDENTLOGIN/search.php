<?php
session_start();
include 'config.php';

if (isset($_GET['query'])) {
    $query = '%' . $_GET['query'] . '%';
    
    $sql = "SELECT ai.*, s.course 
            FROM approvedinternship ai 
            LEFT JOIN students s ON ai.student_id = s.id 
            WHERE ai.company_name LIKE ? 
               OR ai.industry LIKE ? 
               OR ai.internship_title LIKE ? 
               OR ai.internship_type LIKE ? 
               OR ai.department LIKE ? 
               OR s.course LIKE ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssss', $query, $query, $query, $query, $query, $query);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $jobs = array();
    while ($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }
    
    echo json_encode($jobs);
}
?>
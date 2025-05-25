<?php
header('Content-Type: application/json');

if (isset($_GET['term'])) {
    $conn = new mysqli("localhost", "u798912504_root", "Internfloucc2025*", "u798912504_internflo");
    
    if ($conn->connect_error) {
        die(json_encode(['error' => 'Connection failed']));
    }
    
    $term = '%' . $_GET['term'] . '%';
    
    // Query with added conditions to check for non-empty names
    $sql = "SELECT 
                email,
                CONCAT(first_name, ' ', last_name) as full_name,
                'Student' as role 
            FROM students 
            WHERE (email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)
                AND first_name IS NOT NULL 
                AND last_name IS NOT NULL 
                AND first_name != ''
                AND last_name != ''
            UNION
            SELECT 
                email,
                CONCAT(first_name, ' ', last_name) as full_name,
                'Recruiter' as role
            FROM approvedrecruiters 
            WHERE (email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)
                AND first_name IS NOT NULL 
                AND last_name IS NOT NULL 
                AND first_name != ''
                AND last_name != ''
            UNION
            SELECT 
                email,
                CONCAT(first_name, ' ', last_name) as full_name,
                'Advisor' as role
            FROM m_advisors 
            WHERE (email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)
                AND first_name IS NOT NULL 
                AND last_name IS NOT NULL 
                AND first_name != ''
                AND last_name != ''
            LIMIT 10";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssssssss', $term, $term, $term, $term, $term, $term, $term, $term, $term);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $suggestions = [];
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = [
            'label' => "{$row['full_name']} ({$row['role']}) - {$row['email']}",
            'value' => $row['email']
        ];
    }
    
    echo json_encode($suggestions);
    exit;
}
?>
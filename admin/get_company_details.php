<?php
include 'config.php';
header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $query = "SELECT * FROM approvedrecruiters WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Sanitize the data
        $data = array_map('htmlspecialchars', $row);
        
        // Convert the data to UTF-8 if needed
        array_walk_recursive($data, function(&$item) {
            if (is_string($item)) {
                $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
            }
        });
        
        echo json_encode($data);
    } else {
        echo json_encode(['error' => 'Company not found']);
    }
} else {
    echo json_encode(['error' => 'No ID provided']);
}

$conn->close();
?>
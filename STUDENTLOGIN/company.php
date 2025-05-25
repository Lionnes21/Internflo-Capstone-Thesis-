<?php
// get_locations.php
session_start();

// Include database connection
include 'config.php';

try {
    // Prepare the SQL query to fetch all locations
    $sql = 'SELECT latitude, longitude, company_name as title FROM reqinternshippost WHERE latitude IS NOT NULL AND longitude IS NOT NULL';
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Fetch all locations
    $locations = array();
    while ($row = $result->fetch_assoc()) {
        $locations[] = array(
            'latitude' => $row['latitude'],
            'longitude' => $row['longitude'],
            'title' => $row['title']
        );
    }
    
    // Send the locations as JSON
    header('Content-Type: application/json');
    echo json_encode($locations);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch locations']);
}
?>
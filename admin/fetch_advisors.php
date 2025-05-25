<?php
// fetch_advisors.php
include 'config.php';
// Connection is already established in config.php as $conn

// Query to fetch advisors
$sql = "SELECT id, full_name, email FROM m_advisor_assignments";
$result = mysqli_query($conn, $sql);

$advisors = array();
if (mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        $advisors[] = $row;
    }
}

// Return as JSON
header('Content-Type: application/json');
echo json_encode($advisors);

// Don't close the connection here if it's needed elsewhere
?>
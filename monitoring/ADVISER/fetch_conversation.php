// fetch_conversation.php
<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "u798912504_root", "Internfloucc2025*", "u798912504_internflo");


// Check connection
if ($conn->connect_error) {
    header("HTTP/1.1 500 Internal Server Error");
    die("Connection failed: " . $conn->connect_error);
}

// Get conversation ID from query parameter
if (!isset($_GET['conversation_id'])) {
    header("HTTP/1.1 400 Bad Request");
    exit();
}

$conversationId = $_GET['conversation_id'];
$userEmail = $_SESSION['user_email']; // Assuming you store the user's email in the session

// Fetch conversation
$stmt = $conn->prepare("
    SELECT 
        m.*,
        COALESCE(s.first_name, ar.first_name, ma.first_name) as sender_name,
        DATE_FORMAT(m.created_at, '%M %d, %Y %h:%i %p') as formatted_date
    FROM messages m
    LEFT JOIN students s ON m.sender_email = s.email
    LEFT JOIN approvedrecruiters ar ON m.sender_email = ar.email
    LEFT JOIN m_advisors ma ON m.sender_email = ma.email
    WHERE (m.id = ? OR m.parent_id = ?) AND (m.sender_email = ? OR m.receiver_email = ?)
    ORDER BY m.created_at ASC
");
$stmt->bind_param('iiss', $conversationId, $conversationId, $userEmail, $userEmail);
$stmt->execute();
$result = $stmt->get_result();
$conversation = $result->fetch_all(MYSQLI_ASSOC);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($conversation);
?>
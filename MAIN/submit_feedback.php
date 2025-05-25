
<?php
// Database configuration
$host = 'localhost';
$db   = 'u798912504_internflo';
$user = 'u798912504_root';
$pass = 'Internfloucc2025*';
$charset = 'utf8mb4';

// Create database connection
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

header('Content-Type: application/json');

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Sanitize and validate input
    $name = isset($_POST['name']) ? trim($_POST['name']) : null;
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);

    // Validate inputs
    if (!$email) {
        throw new Exception('Invalid email address');
    }

    if (empty($title)) {
        throw new Exception('Title is required');
    }

    if (empty($message)) {
        throw new Exception('Message is required');
    }

    // Prepare SQL to insert data into feedback table
    $sql = "INSERT INTO feedback (name, email, title, message, created_at) 
            VALUES (:name, :email, :title, :message, NOW())";

    $stmt = $pdo->prepare($sql);
    
    // Execute the statement
    $result = $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':title' => $title,
        ':message' => $message
    ]);

    // Check if insertion was successful
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Feedback submitted successfully']);
    } else {
        throw new Exception('Failed to submit feedback');
    }

} catch (PDOException $e) {
    // Handle database connection errors
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Handle validation or submission errors
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>
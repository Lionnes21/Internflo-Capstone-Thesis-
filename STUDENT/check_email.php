<?php
// Database connection details
$host = 'localhost';
$dbname = 'u798912504_internflo';
$username = 'u798912504_root';
$password = 'Internfloucc2025*';

try {
    // Create a new PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (isset($_POST['email'])) {
        $email = $_POST['email'];

        // Check if email exists in students or unverified_users table
        $stmt = $pdo->prepare("SELECT email FROM students WHERE email = ? UNION SELECT email FROM unverified_users WHERE email = ?");
        $stmt->execute([$email, $email]);

        // Check if email exists
        if ($stmt->rowCount() > 0) {
            echo json_encode(['exists' => true]);
        } else {
            echo json_encode(['exists' => false]);
        }
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>

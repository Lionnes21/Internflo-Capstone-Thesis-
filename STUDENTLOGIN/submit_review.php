<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to submit a review']);
    exit;
}

// Database connection
$conn = new mysqli("localhost", "u798912504_root", "Internfloucc2025*", "u798912504_internflo");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get and validate input
$company_id = intval($_POST['company_id']);
$student_id = $_SESSION['user_id'];
$rating = floatval($_POST['rating']);
$review_text = trim($_POST['review_text']);

// Validate inputs
if ($rating < 1 || $rating > 5 || empty($review_text)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Get user's information
$sql_user = "SELECT first_name, last_name FROM students WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $student_id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
$user = $user_result->fetch_assoc();

// Insert the review with reviewer names
$sql = "INSERT INTO company_reviews (company_id, student_id, rating, review_text, reviewer_first_name, reviewer_last_name) 
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iidsss", $company_id, $student_id, $rating, $review_text, $user['first_name'], $user['last_name']);

if ($stmt->execute()) {
    // Get updated rating info
    $sql_rating = "SELECT 
                    COUNT(*) as total_reviews,
                    ROUND(AVG(rating), 1) as average_rating
                FROM company_reviews 
                WHERE company_id = ?";
    
    $stmt_rating = $conn->prepare($sql_rating);
    $stmt_rating->bind_param("i", $company_id);
    $stmt_rating->execute();
    $rating_result = $stmt_rating->get_result();
    $rating_info = $rating_result->fetch_assoc();
    
    // Get the newly added review with user info
    $sql_new_review = "SELECT cr.*, 
    cr.reviewer_first_name,
    cr.reviewer_last_name,
    cr.created_at
FROM company_reviews cr
WHERE cr.review_id = LAST_INSERT_ID()";
    
    $new_review_result = $conn->query($sql_new_review);
    $new_review = $new_review_result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'message' => 'Review submitted successfully',
        'rating_info' => $rating_info,
        'new_review' => $new_review
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit review']);
}

$conn->close();
?>
<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session at the very beginning
session_start();

// Database connection
$db_host = "localhost";
$db_user = "u798912504_root";
$db_pass = "Internfloucc2025*";
$db_name = "u798912504_internflo";

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("User not logged in");
        }

        // Get user_id from session
        $user_id = $_SESSION['user_id'];

        // Decode JSON data
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (!$data || !isset($data['formData'])) {
            throw new Exception("Invalid or missing form data");
        }

        $formData = $data['formData'];

        // Ensure required fields have default values
        $title = $formData['title'] ?? 'Untitled Form';
        $description = $formData['description'] ?? '';
        $internship_id = $formData['internship_id'] ?? null;

        // Start transaction
        $conn->begin_transaction();
        
        // Insert form details with user_id
        $stmt = $conn->prepare("INSERT INTO assessment_forms (title, description, user_id, internship_id) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("ssii", $title, $description, $user_id, $internship_id);
        if (!$stmt->execute()) {
            throw new Exception("Error inserting form: " . $stmt->error);
        }
        
        $formId = $conn->insert_id;
        
        // Insert questions and their options
        if (!empty($formData['questions'])) {
            foreach ($formData['questions'] as $questionOrder => $question) {
                // Validate question data
                if (empty($question['title']) || empty($question['type'])) {
                    throw new Exception("Invalid question data at position " . $questionOrder);
                }
                
                // Insert question
                $stmt = $conn->prepare("INSERT INTO assessment_questions (form_id, title, question_type, difficulty, is_required, question_order) VALUES (?, ?, ?, ?, ?, ?)");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $required = isset($question['required']) ? (bool)$question['required'] : false;
                $difficulty = isset($question['difficulty']) ? $question['difficulty'] : 'basic';
                
                $stmt->bind_param("isssii", $formId, $question['title'], $question['type'], $difficulty, $required, $questionOrder);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error inserting question: " . $stmt->error);
                }
                
                $questionId = $conn->insert_id;
                
                // Handle different question types
                if ($question['type'] === 'paragraph') {
                    // Insert into assessment_paragraph_answers
                    $stmt = $conn->prepare("INSERT INTO assessment_paragraph_answers (question_id, answer_text, created_at) VALUES (?, ?, NOW())");
                    if (!$stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    
                    $answerText = isset($question['paragraphAnswer']) ? $question['paragraphAnswer'] : '';
                    $stmt->bind_param("is", $questionId, $answerText);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Error inserting paragraph answer: " . $stmt->error);
                    }
                } else if (!empty($question['options'])) {
                    // Handle multiple choice/checkbox options
                    foreach ($question['options'] as $optionOrder => $option) {
                        if (!isset($option['text'])) {
                            throw new Exception("Invalid option data at question " . $questionOrder . ", option " . $optionOrder);
                        }
                        
                        $stmt = $conn->prepare("INSERT INTO assessment_options (question_id, option_text, is_correct, option_order) VALUES (?, ?, ?, ?)");
                        if (!$stmt) {
                            throw new Exception("Prepare failed: " . $conn->error);
                        }
                        
                        $isCorrect = isset($option['isCorrect']) ? (bool)$option['isCorrect'] : false;
                        $stmt->bind_param("isii", $questionId, $option['text'], $isCorrect, $optionOrder);
                        
                        if (!$stmt->execute()) {
                            throw new Exception("Error inserting option: " . $stmt->error);
                        }
                    }
                }
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // Send success response
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Assessment form created successfully!']);
        
    } else {
        throw new Exception("Invalid request method");
    }
    
} catch (Exception $e) {
    // Rollback transaction if it was started
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    // Send error response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Error creating assessment form: ' . $e->getMessage()
    ]);
} finally {
    // Close connection if it was opened
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}
?>
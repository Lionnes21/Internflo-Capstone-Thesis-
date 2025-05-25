<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

$db_host = "localhost";
$db_user = "u798912504_root";
$db_pass = "Internfloucc2025*";
$db_name = "u798912504_internflo";

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    $conn->begin_transaction();

    // Initialize scoring variables
    $correct_answers = 0;
    $total_questions = 0;
    $form_id = null;

    // Get form_id from the first question
    $first_question_id = array_key_first($_POST['question']);
    $stmt = $conn->prepare("
        SELECT q.form_id 
        FROM assessment_questions q 
        WHERE q.question_id = ?
    ");
    $stmt->bind_param("i", $first_question_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $question_data = $result->fetch_assoc();
    $form_id = $question_data['form_id'];

    // Get total questions (excluding paragraph type)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM assessment_questions 
        WHERE form_id = ? AND question_type != 'paragraph'
    ");
    $stmt->bind_param("i", $form_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_data = $result->fetch_assoc();
    $total_questions = $total_data['total'];

    // Process each submitted answer
    foreach ($_POST['question'] as $questionId => $answer) {
        $questionId = (int)$questionId;
        
        // Get question information
        $stmt = $conn->prepare("SELECT question_type FROM assessment_questions WHERE question_id = ?");
        $stmt->bind_param("i", $questionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $question = $result->fetch_assoc();

        if ($question['question_type'] === 'paragraph') {
            // Store paragraph answers but don't include in scoring
            $stmt = $conn->prepare("
                INSERT INTO assessment_paragraph_answers 
                (question_id, answer_text) 
                VALUES (?, ?)
            ");
            $stmt->bind_param("is", $questionId, $answer);
            $stmt->execute();
            continue; // Skip to next question without affecting score
        } else {
            // Handle multiple choice and checkbox questions
            $stmt = $conn->prepare("
                SELECT option_id 
                FROM assessment_options 
                WHERE question_id = ? AND is_correct = 1
            ");
            $stmt->bind_param("i", $questionId);
            $stmt->execute();
            $correct_options_result = $stmt->get_result();
            $correct_options = [];
            while ($row = $correct_options_result->fetch_assoc()) {
                $correct_options[] = $row['option_id'];
            }

            if ($question['question_type'] === 'multiple-choice') {
                // Single answer (radio)
                if (in_array($answer, $correct_options)) {
                    $correct_answers++;
                }
                
                // Store the response
                $stmt = $conn->prepare("
                    INSERT INTO assessment_responses 
                    (question_id, option_id) 
                    VALUES (?, ?)
                ");
                $stmt->bind_param("ii", $questionId, $answer);
                $stmt->execute();
            } else {
                // Multiple answers (checkbox)
                $user_answers = is_array($answer) ? $answer : [$answer];
                
                // Convert all user answers to integers
                $user_answers = array_map('intval', $user_answers);
                
                // Convert all correct options to integers
                $correct_options = array_map('intval', $correct_options);
                
                // Sort both arrays to ensure consistent comparison
                sort($user_answers);
                sort($correct_options);
                
                // Debug logging
                error_log('User answers: ' . print_r($user_answers, true));
                error_log('Correct options: ' . print_r($correct_options, true));
                
                // Compare arrays using array_diff
                if (empty(array_diff($user_answers, $correct_options)) && 
                    empty(array_diff($correct_options, $user_answers))) {
                    $correct_answers++;
                    error_log('Answer marked as correct');
                } else {
                    error_log('Answer marked as incorrect');
                }
                
                // Store responses
                foreach ($user_answers as $optionId) {
                    $stmt = $conn->prepare("
                        INSERT INTO assessment_responses 
                        (question_id, option_id) 
                        VALUES (?, ?)
                    ");
                    $stmt->bind_param("ii", $questionId, $optionId);
                    $stmt->execute();
                }
            }
        }
    }

    // Calculate score percentage using only non-paragraph questions
    $score_percentage = $total_questions > 0 ? 
        round(($correct_answers / $total_questions) * 100, 2) : 0;

    // Store the assessment results
    $stmt = $conn->prepare("
        INSERT INTO assessment_results 
        (form_id, total_questions, correct_answers, score_percentage) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("iiid", $form_id, $total_questions, $correct_answers, $score_percentage);
    $stmt->execute();

    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Assessment submitted successfully',
        'score' => [
            'total_questions' => $total_questions,
            'correct_answers' => $correct_answers,
            'percentage' => $score_percentage
        ]
    ]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
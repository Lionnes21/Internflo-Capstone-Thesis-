<?php
// save_form.php
header('Content-Type: application/json');

try {
    // Get JSON data
    $jsonData = file_get_contents('php://input');
    $formData = json_decode($jsonData, true);

    // Validate form data
    if (!isset($formData['title']) || empty($formData['questions'])) {
        throw new Exception('Invalid form data');
    }

    // Generate unique form ID
    $formId = uniqid('form_');

    // Store form data in JSON file
    $fileName = "forms/{$formId}.json";
    
    // Create forms directory if it doesn't exist
    if (!file_exists('forms')) {
        mkdir('forms', 0777, true);
    }

    // Save form data
    file_put_contents($fileName, json_encode($formData, JSON_PRETTY_PRINT));

    // Return success response
    echo json_encode([
        'success' => true,
        'formId' => $formId,
        'message' => 'Form saved successfully'
    ]);

} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
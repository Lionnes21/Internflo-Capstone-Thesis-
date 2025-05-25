<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "student_registration";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => "Connection failed: " . $conn->connect_error]));
}

// Verify if student_id is set in session
if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'error' => "Student ID not found in session."]);
    exit;
}

$studentId = $_SESSION['student_id'];
$date = $_POST['date'] ?? '';
$time_in = $_POST['time-in'] ?? '';
$time_out = $_POST['time-out'] ?? '';
$break_time_input = $_POST['break-time'] ?? '';
$activity = $_POST['activity'] ?? '';

// Calculate hours worked
$time_in_obj = new DateTime($time_in);
$time_out_obj = new DateTime($time_out);
$interval = $time_in_obj->diff($time_out_obj);
$total_hours = $interval->h + ($interval->i / 60);
$hours_worked = $total_hours; // Initialize with total hours

// Handle break time calculation
if (!empty($break_time_input)) {
    $break_time_input = strtolower(trim($break_time_input));
    
    // Regular expression to extract number and unit
    if (preg_match('/^(\d+)\s*(hour|hours|minute|minutes)?\s*$/', $break_time_input, $matches)) {
        $break_value = intval($matches[1]);
        $break_unit = $matches[2] ?? 'minutes';
        
        if ($break_unit === 'hour' || $break_unit === 'hours') {
            // If break is in hours, subtract hours directly
            $hours_worked -= $break_value;
        } else {
            // If break is in minutes, convert to hours and subtract
            $hours_worked -= ($break_value / 60);
        }
    }
}

// Ensure hours worked is not negative
$hours_worked = max(0, $hours_worked);

// Handle image upload
$image_path = null;
if (isset($_FILES['image-upload']) && $_FILES['image-upload']['error'] == 0) {
    $upload_dir = 'timelogImage_upload/';
    
    // Ensure upload directory exists
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_name = uniqid() . '_' . $_FILES['image-upload']['name'];
    $upload_path = $upload_dir . $file_name;
    
    if (move_uploaded_file($_FILES['image-upload']['tmp_name'], $upload_path)) {
        $image_path = $upload_path;
    } else {
        echo json_encode(['success' => false, 'error' => "Failed to upload image."]);
        exit;
    }
}

// Insert timelog entry
$stmt = $conn->prepare("INSERT INTO m_timelog (student_id, date, time_in, time_out, break_time, activity, hours_worked, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssds", $studentId, $date, $time_in, $time_out, $break_time_input, $activity, $hours_worked, $image_path);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true, 
        'hoursWorked' => round($hours_worked, 2), 
        'totalHours' => round($total_hours, 2),
        'breakTime' => $break_time_input
    ]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
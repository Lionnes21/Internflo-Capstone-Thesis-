<?php
session_start();

// Database configuration
$servername = "localhost";
$username = "u798912504_root";
$password = "Internfloucc2025*"; // Update with your database password
$dbname = "u798912504_internflo";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get student ID from session
$student_id = $_SESSION['user_id'] ?? '';

// Variable to store the latest form data
$latest_form_data = null;

// Fetch the latest pull-out letter data for this student
if ($student_id) {
    $sql = "SELECT * FROM ojt_pullout_letters WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $latest_form_data = $result->fetch_assoc();
        }
        $stmt->close();
    }
}

// Fetch student data
if ($student_id) {
    // Get student's basic information
    $sql_student = "SELECT first_name, middle_name, last_name, suffix, course FROM students WHERE id = ?";
    $stmt = $conn->prepare($sql_student);
    if ($stmt) {
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $student_data = $result->fetch_assoc();
        }
        $stmt->close();
    }
}

// Format full name
$full_name = '';
if ($student_data) {
    $full_name = $student_data['first_name'];
    if (!empty($student_data['middle_name'])) {
        $full_name .= ' ' . $student_data['middle_name'];
    }
    $full_name .= ' ' . $student_data['last_name'];
    if (!empty($student_data['suffix'])) {
        $full_name .= ' ' . $student_data['suffix'];
    }
}

// Handle the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $department = $_POST['department'] ?? '';
    $letter_date = $_POST['date'] ?? '';
    $company_name = $_POST['company'] ?? '';
    $student_name = $_POST['studentName'] ?? '';
    $pullout_reason = $_POST['reason-pullout'] ?? '';
    $coordinator_name = $_POST['coordinatorName'] ?? '';
    $created_at = date("Y-m-d H:i:s");

    // Prepare and execute SQL query
    $sql = "INSERT INTO ojt_pullout_letters (
        user_id, department, letter_date, company_name, 
        student_name, pullout_reason, coordinator_name, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param(
            "ssssssss",
            $student_id, $department, $letter_date, $company_name,
            $student_name, $pullout_reason, $coordinator_name, $created_at
        );

        if ($stmt->execute()) {
            echo "Record saved successfully!";
        } else {
            echo "Error saving record: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error preparing query: " . $conn->error;
    }
    
    exit();
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="css/PullOutLetter.css">
    <link rel="icon" href="css/ucclogo2.png">
    <title>OJT Endorsement Letter</title>

</head>
<body>

<div class="form-container">
<a href="std_documents.php" class="back-link" style="display: inline-block; padding: 7px 13px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; font-family: Arial, sans-serif; font-size: 15px; transition: background-color 0.3s; margin-top: -10px;">← Back</a>

    <h2>OJT Pull-out Letter</h2>
    <form id="letterForm">
    <div class="form-group">
            <label>Department:</label>
            <input type="text" name="department" class="live-input" value="<?php echo htmlspecialchars($latest_form_data['department'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Date:</label>
            <input type="date" name="date" class="live-input" value="<?php echo htmlspecialchars($latest_form_data['letter_date'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Company Name:</label>
            <input type="text" name="company" class="live-input" value="<?php echo htmlspecialchars($latest_form_data['company_name'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Student Name:</label>
            <input type="text" name="studentName" class="live-input" placeholder="Last Name, First Name, MI" required
                value="<?php echo htmlspecialchars($full_name ?: $savedData['student_name'] ?? ''); ?>" readonly>
        </div>
        
        <div class="form-group">
            <label>Reason for Pull-out</label>
            <textarea name="reason-pullout" class="live-input" rows="3" style="width: 80%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;" required><?php echo htmlspecialchars($latest_form_data['pullout_reason'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label>Coordinator Name:</label>
            <input type="text" name="coordinatorName" class="live-input" value="<?php echo htmlspecialchars($latest_form_data['coordinator_name'] ?? ''); ?>" required>
        </div>
    </form>
</div>

<div class="preview-container">
    <div class="preview-content" id="previewContent">
        <div class="page">
            <div style="position: relative; width: 100%; height: 10px; margin-bottom: 0;">
            <img src="css/ucc_parentconsent.png" alt="UCC Logo 1" style="position: absolute; left: 70px; width: 100px; height: auto; top: 0;">
                    <img src="css/CLAS_parentConsent.png" alt="UCC Logo 2" style="position: absolute; right: 70px; width: 60px; height: auto; top: 0;">
                    <img src="css/educ_logo.png" alt="UCC Logo 2" style="position: absolute; right: 120px; width: 60px; height: auto; top: 40px;">
                    <img src="css/crim_logo.png" alt="UCC Logo 2" style="position: absolute; right: 20px; width: 60px; height: auto; top: 40px;">
                    <img src="css/Business_logo.png" alt="UCC Logo 2" style="position: absolute; right: 70px; width: 60px; height: auto; top: 80px;">
            </div>

            <div style="text-align: center; margin-bottom: 20px;">
                <h2 style="font-family: 'Arial', Times, serif; font-size: 18px; margin: 0;">UNIVERSITY OF CALOOCAN CITY</h2>
                <p style="font-family: 'Times New Roman', Times, serif; font-size: 14px; margin: 0;"><em>Biglang Awa St. Catleya cor., 12th Ave., Caloocan City <br>South Campus</em></p>
                <p style="font-family: 'Times New Roman', Times, serif; font-size: 14px; margin: 0;"><em>Tel # 310-6855</em></p>
            </div>

            <div id="contentArea">
                <!-- Content will be dynamically updated here -->
            </div>
        </div>
    </div>
    <div class="button-container">
        <button type="button" onclick="saveForm()">Save</button>
        <button type="button" onclick="generatePDF()">Generate PDF</button>
    </div>
</div>

<script src="templateScript/PullOutLetter2.js"></script>
</body>
</html>
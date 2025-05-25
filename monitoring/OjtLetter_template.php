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

// Initialize variables to store form data
$letter_data = [
    'department' => '',
    'letter_date' => '',
    'company_name' => '',
    'student_name' => '',
    'course' => '',
    'required_hours' => '',
    'coordinator_name' => ''
];

// Fetch the latest OJT letter data for the student
if ($student_id) {
    $sql = "SELECT * FROM ojt_letters WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $letter_data = [
                'department' => $row['department'],
                'letter_date' => $row['letter_date'],
                'company_name' => $row['company_name'],
                'student_name' => $row['student_name'],
                'course' => $row['course'],
                'required_hours' => $row['required_hours'],
                'coordinator_name' => $row['coordinator_name']
            ];
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
    $letter_data = [
        'department' => $_POST['department'] ?? '',
        'letter_date' => $_POST['date'] ?? '',
        'company_name' => $_POST['company'] ?? '',
        'student_name' => $_POST['studentName'] ?? '',
        'course' => $_POST['course'] ?? '',
        'required_hours' => $_POST['hours'] ?? '',
        'coordinator_name' => $_POST['coordinatorName'] ?? ''
    ];
    
    $created_at = date("Y-m-d H:i:s");

    // Prepare and execute SQL query
    $sql = "INSERT INTO ojt_letters (
        user_id, department, letter_date, company_name, student_name, 
        course, required_hours, coordinator_name, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param(
            "sssssssss",
            $student_id, $letter_data['department'], $letter_data['letter_date'], 
            $letter_data['company_name'], $letter_data['student_name'],
            $letter_data['course'], $letter_data['required_hours'], 
            $letter_data['coordinator_name'], $created_at
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
    <link rel="stylesheet" href="css/EndoresmentLetters.css">
    <link rel="icon" href="css/ucclogo2.png">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <title>OJT Endorsement Letter</title>
</head>
<body>

<div class="form-container">
<a href="std_documents.php" class="back-link" style="display: inline-block; padding: 7px 13px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; font-family: Arial, sans-serif; font-size: 15px; transition: background-color 0.3s; margin-top: -10px;">← Back</a>

    <h2>OJT Endorsement Letter</h2>
    <form id="letterForm">
    <div class="form-group">
            <label>Department:</label>
            <input type="text" name="department" class="live-input" value="<?php echo htmlspecialchars($letter_data['department']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Date:</label>
            <input type="date" name="date" class="live-input" value="<?php echo htmlspecialchars($letter_data['letter_date']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Company Name:</label>
            <input type="text" name="company" class="live-input" value="<?php echo htmlspecialchars($letter_data['company_name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Student Name:</label>
            <input type="text" name="studentName" class="live-input" placeholder="Last Name, First Name, MI" required
                value="<?php echo htmlspecialchars($full_name ?: $savedData['student_name'] ?? ''); ?>" readonly>
        </div>
        
        <div class="form-group">
        <label>Course:</label>
        <select name="course" class="live-input" required disabled>
            <option value="">Select a course</option>
            <?php
            $courses = [
                "AB Political Science",
                "BA Communication",
                "Bachelor of Public Administration",
                "Bachelor of Science in Computer Science",
                "Bachelor of Science in Entertainment and Multimedia Computing",
                "Bachelor of Science in Information System",
                "Bachelor of Science in Information Technology",
                "Bachelor of Science in Mathematics",
                "Bachelor of Science in Psychology",
                "Bachelor of Science in Accountancy",
                "Bachelor of Science in Accounting Information System",
                "Bachelor of Science in Business Administration, Major in Financial Management",
                "Bachelor of Science in Business Administration, Major in Human Resource Management",
                "Bachelor of Science in Business Administration, Major in Marketing Management",
                "Bachelor of Science in Entrepreneurship",
                "Bachelor of Science in Hospitality Management",
                "Bachelor of Science in Office Administration",
                "Bachelor of Science in Tourism Management",
                "Bachelor in Secondary Education Major in English",
                "Bachelor in Secondary Education Major in English - Chinese",
                "Bachelor in Secondary Education Major in Science",
                "Bachelor in Secondary Education Major in Technology and Livelihood Education",
                "Bachelor of Early Childhood Education",
                "Bachelor of Science in Criminology"
            ];
            foreach ($courses as $course) {
                $selected = '';
                if ($student_data && $student_data['course'] === $course) {
                    $selected = 'selected';
                } elseif (($savedData['course'] ?? '') === $course) {
                    $selected = 'selected';
                }
                echo "<option value=\"" . htmlspecialchars($course) . "\" $selected>" . htmlspecialchars($course) . "</option>";
            }
            ?>
        </select>
        <!-- Hidden input to ensure the course value is submitted when the select is disabled -->
        <input type="hidden" name="course" value="<?php echo htmlspecialchars($student_data['course'] ?? $savedData['course'] ?? ''); ?>">
    </div>
        
        <div class="form-group">
            <label>Required Hours:</label>
            <input type="text" name="hours" class="live-input" value="<?php echo htmlspecialchars($letter_data['required_hours']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Coordinator Name:</label>
            <input type="text" name="coordinatorName" class="live-input" value="<?php echo htmlspecialchars($letter_data['coordinator_name']); ?>" required>
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



     <script src="templateScript/EndoresmentLetters.js"></script>
     
</body>
</html>
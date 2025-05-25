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

// Variables to store student data and saved form data
$student_data = null;
$savedData = null;

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

// Function to fetch saved form data
function getSavedFormData($conn, $student_id) {
    $sql = "SELECT * FROM student_form WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// If not a POST request, try to fetch saved data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $savedData = getSavedFormData($conn, $student_id);
}

// Handle the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_name = $full_name; // Use the formatted full name
    $company_name = $_POST['companyName'] ?? '';
    $weeks = $_POST['weeks'] ?? '';
    $start_date = $_POST['startDate'] ?? '';
    $end_date = $_POST['endDate'] ?? '';
    $hours = $_POST['hours'] ?? '';
    $course = $student_data['course'] ?? $_POST['course']; // Use the student's course
    $parent_name = $_POST['parentName'] ?? '';
    $relationship = $_POST['relationship'] ?? '';
    $created_at = date("Y-m-d H:i:s");

    // Handle signature file upload
    $signature_path = "";
    if (isset($_FILES['signatureUpload']) && $_FILES['signatureUpload']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/signatures/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['signatureUpload']['name'], PATHINFO_EXTENSION));
        $new_filename = $student_id . '_signature_' . uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($_FILES['signatureUpload']['tmp_name'], $target_file)) {
            $signature_path = $target_file;
        } else {
            echo "Error uploading signature.";
            exit();
        }
    }

    // Handle parent consent image upload
    $parent_consent_image = "";
    if (isset($_FILES['consentImageUpload']) && $_FILES['consentImageUpload']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/ParentConsImage/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['consentImageUpload']['name'], PATHINFO_EXTENSION));
        $new_filename = $student_id . '_consent_' . uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($_FILES['consentImageUpload']['tmp_name'], $target_file)) {
            $parent_consent_image = $target_file;
        } else {
            echo "Error uploading consent image.";
            exit();
        }
    }

    // Prepare and execute SQL query
    $sql = "INSERT INTO student_form (
        user_id, student_name, company_name, weeks, start_date, end_date, 
        hours, course, parent_name, relationship, signature_path, parent_consent_image, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param(
            "sssssssssssss",
            $student_id, $student_name, $company_name, $weeks, $start_date, 
            $end_date, $hours, $course, $parent_name, $relationship, 
            $signature_path, $parent_consent_image, $created_at
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

    <link rel="stylesheet" href="css/ParentConsentLetters.css">
    <link rel="icon" href="css/ucclogo2.png">
    <title>Parent Consent Form</title>

</head>
<style>
        /* Additional styling for image preview */
        .image-preview-container {
            position: relative;
            margin-top: 10px;
            display: inline-block;
        }
        
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            border: 1px solid #ddd;
        }
        
        .remove-button {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: #ff4d4d;
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 30px;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .remove-button:hover {
            background-color: #ff1a1a;
        }
    </style>
<body>


<div class="form-container">
<a href="std_documents.php" class="back-link" style="display: inline-block; padding: 7px 13px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; font-family: Arial, sans-serif; font-size: 15px; transition: background-color 0.3s; margin-top: -10px;">← Back</a>

        <h2>Parent Consent Form</h2>
        <form id="consentForm">
        <div class="form-group">
            <label>Student Name:</label>
            <input type="text" name="studentName" class="live-input" placeholder="Last Name, First Name, MI" required
                value="<?php echo htmlspecialchars($full_name ?: $savedData['student_name'] ?? ''); ?>" readonly>
        </div>
            
        <div class="form-group">
            <label>Company Name:</label>
            <input type="text" name="companyName" class="live-input" required
                   value="<?php echo htmlspecialchars($savedData['company_name'] ?? ''); ?>">
        </div>
            
        <div class="form-group">
            <label>Number of Weeks:</label>
            <input type="number" name="weeks" class="live-input" required
                   value="<?php echo htmlspecialchars($savedData['weeks'] ?? ''); ?>">
        </div>
            
        <div class="form-group">
            <label>Starting Date:</label>
            <input type="text" name="startDate" class="live-input" placeholder="Month only" required
                   value="<?php echo htmlspecialchars($savedData['start_date'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>End Date:</label>
            <input type="text" name="endDate" class="live-input" placeholder="Month only" required
                   value="<?php echo htmlspecialchars($savedData['end_date'] ?? ''); ?>">
        </div>
            
        <div class="form-group">
            <label>Hours:</label>
            <input type="text" name="hours" class="live-input" required
                   value="<?php echo htmlspecialchars($savedData['hours'] ?? ''); ?>">
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
            <label>Parent/Guardian Name:</label>
            <input type="text" name="parentName" class="live-input" required
                   value="<?php echo htmlspecialchars($savedData['parent_name'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label>Relationship to Student:</label>
            <input type="text" name="relationship" class="live-input" required
                   value="<?php echo htmlspecialchars($savedData['relationship'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label>Upload Signature:</label>
            <input type="file" accept="image/*" id="signatureUpload" name="signatureUpload" onchange="handleSignatureUpload(event)">
            <?php if (!empty($savedData['signature_path'])): ?>
                <img id="signaturePreview" class="signature-preview" src="<?php echo htmlspecialchars($savedData['signature_path']); ?>">
            <?php else: ?>
                <img id="signaturePreview" class="signature-preview" style="display: none;">
            <?php endif; ?>
        </div>

        <div class="form-group">
    <label>Upload Valid ID</label>
    <input type="file" accept="image/*" id="consentImageUpload" name="consentImageUpload" onchange="handleConsentImageUpload(event)">
    <?php if (!empty($savedData['parent_consent_image'])): ?>
        <div class="image-preview-container">
            <img id="consentImagePreview" class="image-preview" src="<?php echo htmlspecialchars($savedData['parent_consent_image']); ?>" alt="Valid ID">
            <button type="button" class="remove-button" onclick="removeConsentImage()">Remove</button>
        </div>
    <?php else: ?>
        <div class="image-preview-container" style="display: none;">
            <img id="consentImagePreview" class="image-preview" alt="Valid ID">
            <button type="button" class="remove-button" onclick="removeConsentImage()">Remove</button>
        </div>
    <?php endif; ?>
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

            <div style="text-align: center; margin: -10px auto 0; max-width: 700px; padding: 0 20px;">
                <h2 style="font-family: 'Times New Roman', Times, serif; font-size: 21px; font-weight: bold; margin: 0 0 2px 0;">UNIVERSITY OF CALOOCAN CITY</h2>
                    <p style="font-family: 'Times New Roman', Times, serif; font-size: 14.67px; margin: 0 0 2px 0;">(formerly Caloocan City Polytechnic College)</p>
                    <p style="font-family: 'Times New Roman', Times, serif; font-size: 14px; margin: 0 0 2px 0;"><em>Catleya cor. Biglang Awa Sts. 11th Ave., Caloocan City</em></p>
                    <p style="font-family: 'Times New Roman', Times, serif; font-size: 14px; margin: 0 0 2px 0;"><em>Tel Nos. 310-6855 (Registrar's Office)</em></p>
                    <p style="font-family: 'Times New Roman', Times, serif; font-size: 14px; margin: 0 0 15px 0;"><em>310-6843 (Academic Office)</em></p>
                <!--<h3 style="font-family: 'Lucida Bright', Times, serif; font-size: 18.67px; font-weight: bold; margin: 0 0 2px 0;">COLLEGE OF LIBERAL ARTS AND SCIENCES</h3>-->
                    <p style="font-family: 'Lucida Bright', Times, serif; font-size: 18.67px; font-weight: bold; margin: 0 0 2px 0;">o0o</p>
                <h3 style="font-family: 'Lucida Bright', Times, serif; font-size: 18.67px; font-weight: bold; margin: 0 0 15px 0;">OFFICE OF THE DEAN</h3>
                    <div style="border-bottom: 1px solid black; margin: 0 0 2px 0;"></div>
                    <div style="border-bottom: 1px solid black; margin: 0 0 15px 0;"></div>
                <h2 style="font-family: 'Arial Black', Times, serif; font-size: 21px; font-weight: bold; margin: 0;">PARENTS' CONSENT</h2>
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

<script>
    function handleConsentImageUpload(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const container = document.querySelector('.image-preview-container');
            const preview = document.getElementById('consentImagePreview');
            preview.src = e.target.result;
            container.style.display = 'inline-block';
        };
        reader.readAsDataURL(file);
    }
}

function removeConsentImage() {
    const input = document.getElementById('consentImageUpload');
    const container = document.querySelector('.image-preview-container');
    const preview = document.getElementById('consentImagePreview');
    
    // Clear the input
    input.value = '';
    
    // Hide the preview container
    container.style.display = 'none';
    
    // Clear the image source
    preview.src = '';
}
</script>
<script src="templateScript/ParentConsLetter.js"></script>
</body>
</html>
<?php
// AR_template.php
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

// Variable to store student data and latest form data
$student_data = null;
$latest_form_data = null;
$report_images = [];

// Fetch student data
if ($student_id) {
    // First get student's basic information
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

    // Then get the latest report data
    $sql_report = "SELECT * FROM m_accomplishmentreport WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
    $stmt = $conn->prepare($sql_report);
    if ($stmt) {
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $latest_form_data = $result->fetch_assoc();
            
            // Fetch images related to this report
            if ($latest_form_data['id']) {
                $sql_images = "SELECT * FROM ar_images WHERE report_id = ? ORDER BY image_date ASC";
                $stmt_img = $conn->prepare($sql_images);
                if ($stmt_img) {
                    $stmt_img->bind_param("i", $latest_form_data['id']);
                    $stmt_img->execute();
                    $result_img = $stmt_img->get_result();
                    while ($row = $result_img->fetch_assoc()) {
                        $report_images[] = $row;
                    }
                    $stmt_img->close();
                }
            }
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if it's an AJAX request with form data
    if (isset($_POST['practicumerName'])) {
        $practicumer_name = $_POST['practicumerName'] ?? '';
        $course = $_POST['course'] ?? '';
        $company_name = $_POST['company'] ?? '';
        $department = $_POST['department'] ?? '';
        $inclusive_date_from = $_POST['inclusiveDateFrom'] ?? '';
        $inclusive_date_to = $_POST['inclusiveDateTo'] ?? '';  // New field
        $week_number = $_POST['weekNumber'] ?? '';
        $time_in = $_POST['timeIn'] ?? '';
        $time_out = $_POST['timeOut'] ?? '';
        $weekly_report = $_POST['weeklyReport'] ?? '';
        $prepared_by = $practicumer_name;
        $certified_by = $_POST['certifiedBy'] ?? '';
        $created_at = date("Y-m-d H:i:s");
        
        // Initialize image filename variable
        $image_filename = '';
        
        // Main report submission
        $sql = "INSERT INTO m_accomplishmentreport (
            user_id, practicumer_name, course, company_name, department,
            inclusive_date_from, inclusive_date_to, week_number, time_in, time_out, weekly_report, 
            prepared_by, certified_by, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param(
                "ssssssssssssss",
                $student_id, $practicumer_name, $course, $company_name, $department,
                $inclusive_date_from, $inclusive_date_to, $week_number, $time_in, $time_out, $weekly_report, 
                $prepared_by, $certified_by, $created_at
            );

            if ($stmt->execute()) {
                $report_id = $conn->insert_id;
                
                // Process multiple images
                $image_count = isset($_POST['imageCount']) ? intval($_POST['imageCount']) : 0;
                $success = true;
                
                for ($i = 0; $i < $image_count; $i++) {
                    $image_date_field = "imageDate_" . $i;
                    $image_file_field = "reportImage_" . $i;
                    
                    $image_date = $_POST[$image_date_field] ?? '';
                    
                    // Check if image was uploaded
                    if (isset($_FILES[$image_file_field]) && $_FILES[$image_file_field]['error'] == 0) {
                        // Process image upload
                        $upload_dir = 'ARpicture/';
                        
                        // Create directory if it doesn't exist
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        
                        // Generate unique filename
                        $file_extension = pathinfo($_FILES[$image_file_field]['name'], PATHINFO_EXTENSION);
                        $image_filename = $student_id . '_' . time() . '_' . $i . '.' . $file_extension;
                        $target_file = $upload_dir . $image_filename;
                        
                        // Move uploaded file
                        if (move_uploaded_file($_FILES[$image_file_field]['tmp_name'], $target_file)) {
                            // Insert image record
                            $sql_img = "INSERT INTO ar_images (report_id, image_filename, image_date, created_at) 
                                      VALUES (?, ?, ?, ?)";
                            $stmt_img = $conn->prepare($sql_img);
                            
                            if ($stmt_img) {
                                $stmt_img->bind_param("isss", $report_id, $image_filename, $image_date, $created_at);
                                if (!$stmt_img->execute()) {
                                    $success = false;
                                    echo "Error saving image record: " . $stmt_img->error;
                                }
                                $stmt_img->close();
                            } else {
                                $success = false;
                                echo "Error preparing image query: " . $conn->error;
                            }
                        } else {
                            $success = false;
                            echo "Error uploading file.";
                        }
                    }
                }
                
                if ($success) {
                    echo "success";
                }
            } else {
                echo "Error saving record: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Error preparing query: " . $conn->error;
        }
        
        exit();
    }
    
    // AJAX request to add an image to an existing report
    if (isset($_POST['action']) && $_POST['action'] == 'add_image' && isset($_POST['report_id'])) {
        $report_id = $_POST['report_id'];
        $image_date = $_POST['image_date'] ?? '';
        $created_at = date("Y-m-d H:i:s");
        $success = false;
        
        // Check if image was uploaded
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == 0) {
            // Process image upload
            $upload_dir = 'ARpicture/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
            $image_filename = $student_id . '_' . time() . '.' . $file_extension;
            $target_file = $upload_dir . $image_filename;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_file)) {
                // Insert image record
                $sql_img = "INSERT INTO ar_images (report_id, image_filename, image_date, created_at) 
                          VALUES (?, ?, ?, ?)";
                $stmt_img = $conn->prepare($sql_img);
                
                if ($stmt_img) {
                    $stmt_img->bind_param("isss", $report_id, $image_filename, $image_date, $created_at);
                    if ($stmt_img->execute()) {
                        $success = true;
                    } else {
                        echo "Error saving image record: " . $stmt_img->error;
                    }
                    $stmt_img->close();
                } else {
                    echo "Error preparing image query: " . $conn->error;
                }
            } else {
                echo "Error uploading file.";
            }
        }
        
        if ($success) {
            echo "success";
        } else {
            echo "failed";
        }
        exit();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Accomplishment Report</title>
    <link rel="stylesheet" href="css/AR_template.css">
    <link rel="icon" href="css/ucclogo2.png">
</head>
<body>
<div class="form-container">
    <a href="std_reports.php" class="back-link" style="display: inline-block; padding: 7px 13px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; font-family: Arial, sans-serif; font-size: 15px; transition: background-color 0.3s; margin-top: -10px;">← Back</a>
    
    <h2>Weekly Accomplishment Report</h2>
    <div class="pagination">
        <span class="page-indicator">Page <span id="currentPage">1</span> of 2</span>
        <div class="page-buttons">
            <button type="button" id="prevBtn" onclick="navigatePage(-1)" disabled>Previous</button>
            <button type="button" id="nextBtn" onclick="navigatePage(1)">Next</button>
        </div>
    </div>
    
    <form id="reportForm">
        <!-- Page 1 - Basic Information -->
        <div class="form-page" id="page1">
            <div class="form-group">
                <label>Practicumer Name:</label>
                <input type="text" name="practicumerName" class="live-input" 
                    value="<?php echo htmlspecialchars($full_name); ?>" readonly>
            </div>
            
            <div class="form-group">
                <label>Course:</label>
                <input type="text" name="course" class="live-input" 
                    value="<?php echo htmlspecialchars($student_data['course'] ?? ''); ?>" readonly>
            </div>
            
            <div class="form-group">
                <label>Company:</label>
                <input type="text" name="company" class="live-input" value="<?php echo htmlspecialchars($latest_form_data['company_name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Department:</label>
                <input type="text" name="department" class="live-input" value="<?php echo htmlspecialchars($latest_form_data['department'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Inclusive Date:</label>
                <div class="date-range">
                    <input type="date" name="inclusiveDateFrom" class="live-input" value="<?php echo htmlspecialchars($latest_form_data['inclusive_date_from'] ?? ''); ?>" required>
                    <span>to</span>
                    <input type="date" name="inclusiveDateTo" class="live-input" value="<?php echo htmlspecialchars($latest_form_data['inclusive_date_to'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Week Number:</label>
                <select name="weekNumber" class="live-input" required>
                    <?php 
                    $selected_week = htmlspecialchars($latest_form_data['week_number'] ?? '');
                    for ($i = 1; $i <= 10; $i++) {
                        $selected = ($selected_week == $i) ? 'selected' : '';
                        echo "<option value=\"$i\" $selected>Week $i</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group time-container">
                <label>Time In:</label>
                <input type="time" name="timeIn" class="live-input" value="<?php echo htmlspecialchars($latest_form_data['time_in'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group time-container">
                <label>Time Out:</label>
                <input type="time" name="timeOut" class="live-input" value="<?php echo htmlspecialchars($latest_form_data['time_out'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Weekly Report:</label>
                <textarea name="weeklyReport" class="live-input" rows="10" style="width: 85%;" required><?php echo htmlspecialchars($latest_form_data['weekly_report'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Prepared By:</label>
                <input type="text" name="preparedBy" class="live-input" 
                    value="<?php echo htmlspecialchars($full_name); ?>" readonly>
            </div>
            
            <div class="form-group">
                <label>Certified By:</label>
                <input type="text" name="certifiedBy" class="live-input" value="<?php echo htmlspecialchars($latest_form_data['certified_by'] ?? ''); ?>" required>
            </div>
        </div>
        
        <!-- Page 2 - Image Upload Section -->
        <div class="form-page" id="page2" style="display: none;">
            <div class="form-group">
                <label>Images / Timecards:</label>
                
                <!-- Container for existing images (if any) -->
                <?php if (!empty($report_images)): ?>
                    <div class="existing-images-container">
                        <h4>Uploaded Images:</h4>
                        <?php foreach ($report_images as $img): ?>
                            <div class="existing-image-item">
                                <p>Date: <?php echo htmlspecialchars(date('F d, Y', strtotime($img['image_date']))); ?></p>
                                <img src="ARpicture/<?php echo htmlspecialchars($img['image_filename']); ?>" alt="Report Image" class="image-preview">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Container for dynamically added image entries -->
                <div id="imageEntriesContainer">
                    <div class="image-entry">
                        <div class="form-group">
                            <label>Date for Image:</label>
                            <input type="date" name="imageDate_0" required>
                        </div>
                        <div class="form-group">
                            <label>Upload Image / Timecard:</label>
                            <input type="file" name="reportImage_0" accept="image/*" required>
                            <div class="image-preview-container"></div>
                        </div>
                        <button type="button" class="remove-image-btn" onclick="removeImageEntry(this)" style="display: none;">Remove</button>
                    </div>
                </div>
                
                <!-- Button to add more images -->
                <button type="button" class="add-image-btn" onclick="addImageEntry()">+ Add Another Image</button>
                
                <!-- Hidden field to keep track of image count -->
                <input type="hidden" name="imageCount" id="imageCount" value="1">
            </div>
            
        </div>
    </form>
</div>

<div class="preview-container">
    <div class="preview-content" id="previewContent">
        <div class="page">
            

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

<script src="AccomplishmentReports.js"></script>
<script>
        // Current page tracker
        let currentPage = 1;
    const totalPages = 2;
    
    // Function to navigate between pages
    function navigatePage(direction) {
        // Hide current page
        document.getElementById(`page${currentPage}`).style.display = 'none';
        
        // Calculate new page number
        currentPage += direction;
        
        // Update UI
        document.getElementById(`page${currentPage}`).style.display = 'block';
        document.getElementById('currentPage').textContent = currentPage;
        
        // Update buttons
        document.getElementById('prevBtn').disabled = (currentPage === 1);
        document.getElementById('nextBtn').disabled = (currentPage === totalPages);
        
        // Scroll to top
        window.scrollTo(0, 0);
    }
    // Function to add a new image entry
function addImageEntry() {
    // Get the current count of image entries
    let imageCount = parseInt(document.getElementById('imageCount').value);
    
    // Create a new image entry element
    const imageEntriesContainer = document.getElementById('imageEntriesContainer');
    const newEntry = document.createElement('div');
    newEntry.className = 'image-entry';
    
    // Set the inner HTML with incremented index
    newEntry.innerHTML = `
        <div class="form-group">
            <label>Date for Image:</label>
            <input type="date" name="imageDate_${imageCount}" required>
        </div>
        <div class="form-group">
            <label>Upload Image / Timecard:</label>
            <input type="file" name="reportImage_${imageCount}" accept="image/*" required>
            <div class="image-preview-container"></div>
        </div>
        <button type="button" class="remove-image-btn" onclick="removeImageEntry(this)">Remove</button>
    `;
    
    // Add the new entry to the container
    imageEntriesContainer.appendChild(newEntry);
    
    // Show remove buttons if there's more than one entry
    const removeButtons = document.querySelectorAll('.remove-image-btn');
    if (removeButtons.length > 1) {
        removeButtons.forEach(btn => {
            btn.style.display = 'inline-block';
        });
    }
    
    // Update the image count
    imageCount++;
    document.getElementById('imageCount').value = imageCount;
    
    // Add event listener for image preview
    addImagePreviewListener(newEntry.querySelector('input[type="file"]'));
}

// Function to remove an image entry
function removeImageEntry(button) {
    const imageEntriesContainer = document.getElementById('imageEntriesContainer');
    const entry = button.parentNode;
    
    // Remove the entry
    imageEntriesContainer.removeChild(entry);
    
    // Update the image count
    let imageCount = parseInt(document.getElementById('imageCount').value);
    imageCount--;
    document.getElementById('imageCount').value = imageCount;
    
    // Hide remove buttons if there's only one entry left
    const removeButtons = document.querySelectorAll('.remove-image-btn');
    if (removeButtons.length <= 1) {
        removeButtons.forEach(btn => {
            btn.style.display = 'none';
        });
    }
    
    // Re-index the remaining entries
    reindexImageEntries();
}

// Function to re-index image entries after removal
function reindexImageEntries() {
    const imageEntries = document.querySelectorAll('.image-entry');
    
    imageEntries.forEach((entry, index) => {
        const dateInput = entry.querySelector('input[type="date"]');
        const fileInput = entry.querySelector('input[type="file"]');
        
        if (dateInput) {
            dateInput.name = `imageDate_${index}`;
        }
        
        if (fileInput) {
            fileInput.name = `reportImage_${index}`;
        }
    });
}

// Function to add image preview functionality
function addImagePreviewListener(fileInput) {
    fileInput.addEventListener('change', function() {
        const previewContainer = this.nextElementSibling;
        previewContainer.innerHTML = '';
        
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'image-preview';
                previewContainer.appendChild(img);
            }
            
            reader.readAsDataURL(this.files[0]);
        }
    });
}

// Initialize image preview for existing file inputs
document.addEventListener('DOMContentLoaded', function() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        addImagePreviewListener(input);
    });
});

// Modified saveForm function to handle multiple images
function saveForm() {
    const form = document.getElementById('reportForm');
    const formData = new FormData(form);
    
    // Create an AJAX request
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'AR_template.php', true);
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            if (xhr.responseText === 'success') {
                alert('Report saved successfully!');
                // Optional: Reload the page to show the latest data
                window.location.reload();
            } else {
                alert('Error: ' + xhr.responseText);
            }
        } else {
            alert('Request failed. Status: ' + xhr.status);
        }
    };
    
    xhr.send(formData);
}
</script>

</body>
</html>
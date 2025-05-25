<?php
session_start(); 

// Database connection
$servername = "localhost";
$username = "u798912504_root";
$password = "Internfloucc2025*"; // Update with your database password
$dbname = "u798912504_internflo";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the user is logged in and get user info
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT first_name, last_name, name, student_id, course, school_year, profile_pic, status FROM students WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();

    if ($student) {
        if (empty($student['first_name']) && empty($student['last_name'])) {
            $fullName = $student['name'];
        } else {
            $fullName = $student['first_name'] . " " . $student['last_name'];
        }
        $studentId = $student['student_id'];
    } else {
        $fullName = "Student Name";
        $studentId = "Student ID";
    }
} else {
    $fullName = "Student Name";
    $studentId = "Student ID";
}

// Function to get assigned advisors
function getAssignedAdvisors($conn, $studentId) {
    // First get the student's course and year
    $stmt = $conn->prepare("
        SELECT 
            s.course,
            s.school_year,
            c.id as course_id,
            c.program_id
        FROM students s
        LEFT JOIN m_courses c ON s.course = c.name
        WHERE s.student_id = ?
    ");
    
    $stmt->bind_param("s", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    
    if (!$student) {
        return array();
    }
    
    // Get assigned advisors based on course_id, program_id and year
    $stmt = $conn->prepare("
        SELECT DISTINCT 
            a.advisor_id,
            a.first_name,
            a.last_name,
            a.middle_initial,
            a.suffix
        FROM m_advisors a
        INNER JOIN m_advisor_assignments aa ON a.id = aa.advisor_id
        WHERE aa.course_id = ?
        AND aa.program_id = ?
        AND aa.year = ?
    ");
    
    $stmt->bind_param("iis", 
        $student['course_id'],
        $student['program_id'],
        $student['school_year']
    );
    $stmt->execute();
    $result = $stmt->get_result();
    
    $advisors = array();
    while ($row = $result->fetch_assoc()) {
        // Construct full name with middle initial and suffix if available
        $fullName = $row['first_name'];
        
        if (!empty($row['middle_initial'])) {
            $fullName .= ' ' . $row['middle_initial'] . '.';
        }
        
        $fullName .= ' ' . $row['last_name'];
        
        if (!empty($row['suffix'])) {
            $fullName .= ' ' . $row['suffix'];
        }
        
        $advisors[] = array(
            'advisor_id' => $row['advisor_id'],
            'name' => $fullName
        );
    }
    
    return $advisors;
}

// Check if uploads directory exists and is writable
$target_dir = "weeklyReport_uploads/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}
if (!is_writable($target_dir)) {
    die("Error: Upload directory is not writable");
}

$uploadSuccess = false;
$uploadError = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle file upload
    if (isset($_POST['submit_report'])) {
        $week = $_POST['week'];
        $submit_to = $_POST['submit_to'];
        $student_id = $studentId;

        // Check if a report for this week already exists
        $check_sql = "SELECT id FROM m_weekly_reports WHERE student_id = ? AND week = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $student_id, $week);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $uploadError = "A report for Week $week has already been submitted. Please delete the existing report before submitting a new one.";
        } else {
            // File upload handling
            if (isset($_FILES["report"]) && $_FILES["report"]["error"] == 0) {
                $target_file = $target_dir . basename($_FILES["report"]["name"]);
                $uploadOk = 1;
                $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

                // Check if file already exists
                if (file_exists($target_file)) {
                    $uploadOk = 0;
                    $uploadError = "File already exists.";
                }

                // Check file size (limit to 5MB)
                if ($_FILES["report"]["size"] > 5000000) {
                    $uploadOk = 0;
                    $uploadError = "File is too large. Maximum size is 5MB.";
                }

                // Allow certain file formats
                if ($fileType != "pdf" && $fileType != "doc" && $fileType != "docx") {
                    $uploadOk = 0;
                    $uploadError = "Only PDF, DOC, and DOCX files are allowed.";
                }

                // Upload file and insert into database
                if ($uploadOk == 1) {
                    if (move_uploaded_file($_FILES["report"]["tmp_name"], $target_file)) {
                        $sql = "INSERT INTO m_weekly_reports (student_id, week, report_file, submitted_to, submission_date, status)
                                VALUES (?, ?, ?, ?, NOW(), 'Pending Review')";
                        
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("siss", $student_id, $week, $target_file, $submit_to);
                        
                        if ($stmt->execute()) {
                            $uploadSuccess = true;
                        } else {
                            $uploadError = "Error inserting data into database.";
                        }
                        $stmt->close();
                    } else {
                        $uploadError = "Error uploading file.";
                    }
                }
            } else {
                $uploadError = "No file was uploaded or there was an error with the upload.";
            }
        }
        $check_stmt->close();
    }

    // Handle report deletion
    if (isset($_POST['delete_report'])) {
        $reportId = $_POST['report_id'];
        
        $report_sql = "SELECT report_file FROM m_weekly_reports WHERE id = ?";
        $stmt = $conn->prepare($report_sql);
        $stmt->bind_param("i", $reportId);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($reportFile);
            $stmt->fetch();
            
            $delete_sql = "DELETE FROM m_weekly_reports WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $reportId);
            
            if ($delete_stmt->execute()) {
                if (file_exists($reportFile)) {
                    unlink($reportFile);
                }
            }
            $delete_stmt->close();
        }
        $stmt->close();
    }
}

// Fetch submitted reports
$reports_sql = "SELECT * FROM m_weekly_reports WHERE student_id = ? ORDER BY submission_date DESC";
$stmt = $conn->prepare($reports_sql);
$stmt->bind_param("s", $studentId);
$stmt->execute();
$result = $stmt->get_result();

// Fetch weeks with submitted reports
$submitted_weeks = array();
while ($row = $result->fetch_assoc()) {
    $submitted_weeks[] = $row['week'];
}
$result->data_seek(0); // Reset result pointer
?>

 

<!DOCTYPE html>

<html lang="en" dir="ltr">
  <head>
    <meta charset="UTF-8">
    <!--Drop Down Sidebar Menu -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/std_report2.css">
    <link rel="icon" href="css/ucclogo2.png">


    <!-- Boxiocns CDN Link -->
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
     <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- FOR DELETE AND VIEW ICON -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

     <!-- SweetAlert popup message for logout -->
     <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
     </head>
     <style>

    </style>
     <body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <a href="#" class="brand">
            <img src="css/ucc1.png" alt="Logo" class="logo-img">
        </a>
        <ul class="side-menu">
            <li><a href="std_dashboard.php"><i class='bx bxs-dashboard icon'></i> Dashboard</a></li>
            <!--<li><a href="stdTimelog.php"><i class='bx bx-time icon'></i> Timelog</a></li>-->
            <li><a href="#" class="active"><i class='bx bxs-report icon'></i> Reports</a></li>
            <li><a href="std_documents.php"><i class='bx bxs-file-doc icon'></i> Documents</a></li>
            <li>
				<a href="#"><i class='bx bxs-notepad icon' ></i> Templates <i class='bx bx-chevron-right icon-right' ></i></a>
				<ul class="side-dropdown">
					<li><a href="ParentConsent_template.php">PARENTS' CONSENT</a></li>
					<li><a href="OjtLetter_template.php">Endorsement Letter</a></li>
                    <li><a href="moa_template.php">Memorandum of Agreement (MOA)</a></li>
					<li><a href="OJTPullOutLetter_template.php">OJT Pull-out Letter</a></li>
				</ul>
			</li>
        </ul>
        <br><br><br><br>
        <div class="ads">
            <div class="wrapper">
                <a href="../STUDENTLOGIN/studentfrontpage.php" class="btn-upgrade">
                    <i class='bx bx-log-out' style="margin-right: 10px; font-size: 24px;"></i> Back to Home
                </a>
            </div>
        </div>
    </section>

    <!-- CONTENT -->
    <section id="content">
        <!-- NAVBAR -->
        <nav>
    <i class='bx bx-menu toggle-sidebar'></i>
    <div class="profile">
        <div class="profile-info">
            <span class="user-name"><?php echo htmlspecialchars($fullName); ?></span>
            <span class="user-id"><?php echo htmlspecialchars($studentId); ?></span>
        </div>
        <img src="../STUDENTLOGIN/<?php echo $student['profile_pic']; ?>" alt="">
    </div>
</nav>
    
        <!-- MAIN -->
        <main>
                    <!-- Approval Status Banner -->
        <?php if ($student['status'] === 'pending' || !$student['status']): ?>
            <div class="approval-banner pending">
                <i class='bx bx-time-five'></i>
                <div class="approval-content">
                    <h3>Waiting for Account Approval</h3>
                    <p>Your account is pending approval from your Practicum Coordinator: 
                        <?php echo htmlspecialchars($student['coordinator_name'] ?? 'Not yet assigned'); ?></p>
                </div>

            </div>
            <?php elseif ($student['status'] === 'rejected'): ?>
            <div class="approval-banner rejected">
                <i class='bx bx-x-circle'></i>
                <div class="approval-content">
                    <h3>Account Access Denied</h3>
                    <p>Your account registration has been rejected. Please contact your Practicum Coordinator for more information.</p>
                </div>

            </div>
            <?php endif; ?>

            <!-- Wrap dashboard content in a conditional class -->
            <div class="<?php echo ($student['status'] === 'approved') ? '' : 'disabled-content'; ?>">
        <!-- Report Submission Form -->
    <div class="report-section">
        <h3>Submit Your Weekly Report</h3>

                    <td>
                        <a href="AR_template.php" class="edit-btn">
                            <i class="fas fa-edit"></i> Template
                        </a>
                    </td>

            <?php if ($uploadError): ?>
                <p style="color: red;"><?php echo $uploadError; ?></p>
            <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" enctype="multipart/form-data">
            <label for="week">Week:</label>
                <select id="week" name="week" required>
                    <option value="">Select week</option>
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo in_array($i, $submitted_weeks) ? 'disabled' : ''; ?>>
                        Week <?php echo $i; ?><?php echo in_array($i, $submitted_weeks) ? ' (Submitted)' : ''; ?>
                    </option>
                        <?php endfor; ?>
                </select><br>

                <label for="submit_to">Submit to:</label>
                <select id="submit_to" name="submit_to" required>
                    <option value="">Select Practicum Coordinator</option>
                    <?php 
                    $assignedAdvisors = getAssignedAdvisors($conn, $studentId);
                    if (empty($assignedAdvisors)) {
                        echo '<option value="" disabled>No advisors assigned for your course and year</option>';
                    } else {
                        foreach ($assignedAdvisors as $advisor): 
                    ?>
                        <option value="<?php echo htmlspecialchars($advisor['name']); ?>">
                            <?php echo htmlspecialchars($advisor['name']); ?>
                        </option>
                    <?php 
                        endforeach;
                    } 
                    ?>
                </select><br>

        <label for="report">Upload Report:</label>
        <input type="file" id="report" name="report" required><br><br>

        <input type="submit" name="submit_report" value="Submit">

      </form>
    </div>


        <!-- Submitted Reports Section -->
        <div class="submitted-section">
            <h3>Submitted Reports</h3>
            <table>
                <thead>
                    <tr>
                        <th>Week</th>
                        <th>Date Submitted</th>
                        <th>Submitted To</th>
                        <th>Status</th>
                        <!--<th>Feedback</th>-->
                        <th>File</th>
                    </tr>
                </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['week'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars(date('F j, Y h:i A', strtotime($row['submission_date'] ?? ''))); ?></td>
                    <td><?php echo htmlspecialchars($row['submitted_to'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['status'] ?? ''); ?></td>
                    <!--<td><?php echo htmlspecialchars($row['feedback'] ?? ''); ?></td>-->
                    <td>
                        <button onclick="openReportModal('<?php echo urlencode(basename($row['report_file'] ?? '')); ?>')" 
                        style="background-color: #4CAF50; color: white; padding: 5px; border-radius: 7px; margin-left:30px; border: none; cursor: pointer;">
                        <i class="fas fa-eye"></i> <!-- View Icon -->
                        </button>
                    </td>

                    <td>
                        <button class="delete-btn" style="background-color: red; color: white; padding:7px; cursor:pointer;border-radius: 5px; border: none;" 
                        onclick="confirmDelete(<?php echo $row['id']; ?>)">
                        <i class="fas fa-trash"></i> <!-- Delete Icon -->
                        </button>
                    </td>

              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
            </div>
      </main>
      </section>


            <!-- FOR MODAL  -->
            <div id="reportModal" class="modal">
                <div class="modal-content">
                    <span class="close-btn">&times;</span>
                    <div class="iframe-container">
                        <iframe id="reportViewer"></iframe>
                    </div>
                </div>
            </div>



<script>
      // FOR SUCCESSFULLY UPLOADED REPORT
      <?php if ($uploadSuccess): ?>
        window.onload = function() {
          Swal.fire({
            title: 'Success!',
            text: 'File uploaded successfully.',
            icon: 'success',
            confirmButtonText: 'OK'
          });
        }
      <?php endif; ?>
</script>



<script src="js/std_report.js"></script><!-- For delete confirmation and modal  -->
<script src="css/student_dashboard.js"></script>
<script src="js/logMessage.js"></script>
</body>
</html>
<?php
session_start(); 

// Database connection for the "student_registration" system
$servername = "localhost";
$username = "u798912504_root";
$password = "Internfloucc2025*"; // Update with your database password
$dbname = "u798912504_internflo";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit();
}

// Check if the user is logged in
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    // Fetch student information from the student_registration database using the logged-in user ID
    // Modified query to include status information
    $stmt = $conn->prepare("
        SELECT 
            s.first_name, 
            s.last_name, 
            s.name, 
            s.student_id, 
            s.profile_pic,
            s.status,
            s.course,
            CONCAT(a.first_name, ' ', a.last_name) as coordinator_name
        FROM students s
        LEFT JOIN m_advisor_assignments aa ON s.course = aa.course_id AND s.school_year = aa.year
        LEFT JOIN m_advisors a ON aa.advisor_id = a.id
        WHERE s.id = :user_id
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        // Check if first_name or last_name are empty
        if (empty($student['first_name']) && empty($student['last_name'])) {
            // Use the "name" column as a fallback if both first_name and last_name are empty
            $fullName = $student['name'];
        } else {
            // Concatenate first name and last name only
            $fullName = $student['first_name'] . " " . $student['last_name'];
        }
        $studentId = $student['student_id'];  // Use the student_id from the student_registration DB
    } else {
        // Fallback values in case no user data is found
        $fullName = "Student Name";
        $studentId = "Student ID";
    }
} else {
    // Fallback values if no user is logged in
    $fullName = "Student Name";
    $studentId = "Student ID";
}

// Function to count the total uploaded documents for the student
function countUploadedDocuments($studentId, $conn) {
    $sql = "SELECT COUNT(*) as total FROM m_ojt_documents WHERE student_id = :student_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':student_id' => $studentId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'];
}

// Get the total number of submitted documents
$totalDocuments = countUploadedDocuments($studentId, $conn);

// Function to count the total reports submitted by the student
function countTotalReports($studentId, $conn) {
    $sql = "SELECT COUNT(*) as total FROM m_weekly_reports WHERE student_id = :studentId";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':studentId', $studentId); // Bind the parameter correctly
    $stmt->execute(); // Execute the query
    $result = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch the result
    return $result['total'];
}

// Get the total number of reports
$totalReports = countTotalReports($studentId, $conn);

// Get the student's course and year information
$stmt = $conn->prepare("
    SELECT s.course, s.school_year,
           c.id as course_id,
           c.program_id
    FROM students s
    JOIN m_courses c ON c.name = s.course
    WHERE s.id = ?
");
$stmt->execute([$userId]);
$studentInfo = $stmt->fetch(PDO::FETCH_ASSOC);

// Function to get recent weekly reports for a student
function getRecentWeeklyReports($studentId, $conn) {
    $sql = "SELECT 
                id,
                week,
                submission_date,
                status,
                feedback
            FROM m_weekly_reports 
            WHERE student_id = :student_id 
            ORDER BY submission_date DESC 
            LIMIT 3";
            
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':student_id', $studentId);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get recent reports
$recentReports = getRecentWeeklyReports($studentId, $conn);

// Fetch relevant announcements for this student
if ($studentInfo) {
    $stmt = $conn->prepare("
        SELECT 
            a.*,
        CONCAT(adv.first_name, ' ', adv.last_name) as advisor_name
        FROM m_announcements a
        JOIN m_advisors adv ON adv.id = a.advisor_id
        WHERE a.program_id = ? 
        AND a.course_id = ?
        AND a.year = ?
        ORDER BY a.date_posted DESC
        LIMIT 5
    ");
    $stmt->execute([
        $studentInfo['program_id'],
        $studentInfo['course_id'],
        $studentInfo['school_year']
    ]);
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to calculate total OJT hours worked by a student
function calculateTotalOJTHours($studentId, $conn) {
    $sql = "SELECT SUM(hours_worked) as total_hours FROM m_timelog WHERE student_id = :student_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':student_id', $studentId);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Return total hours, default to 0 if no hours logged
    return $result['total_hours'] ? round($result['total_hours'], 2) : 0;
}

// Calculate total OJT hours
$totalOJTHours = calculateTotalOJTHours($studentId, $conn);

?>


<!DOCTYPE html>

<html lang="en" dir="ltr">
  <head>
    <meta charset="UTF-8">
    <!--Drop Down Sidebar Menu -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/student_dashboard2.css">
    <link rel="icon" href="css/ucclogo2.png">


    <!-- Boxiocns CDN Link -->
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
     <meta name="viewport" content="width=device-width, initial-scale=1.0">

     <!-- SweetAlert popup message for logout -->
     <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
     </head>

     <body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <a href="#" class="brand">
            <img src="css/ucc1.png" alt="Logo" class="logo-img">
        </a>
        
        <ul class="side-menu">
            <li><a href="#" class="active"><i class='bx bxs-dashboard icon'></i> Dashboard</a></li>
            <!--<li><a href="stdTimelog.php"><i class='bx bx-time icon'></i> Timelog</a></li>-->
            <li><a href="std_reports.php"><i class='bx bxs-report icon'></i> Reports</a></li>
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
            <h1 class="title">Dashboard</h1>
            
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
            <!-- Stats Cards -->
            <div class="info-data">
                <div class="card">
                    <div class="head">
                        <div>
                            <h2><?php echo $totalReports; ?></h2>
                            <p>Weekly Reports</p>
                        </div>
                        <i class='bx bxs-report icon'></i>
                    </div>
                </div>
                <div class="card">
                    <div class="head">
                        <div>
                            <h2><?php echo $totalDocuments; ?></h2>
                            <p>Documents</p>
                        </div>
                        <i class='bx bxs-file-doc icon'></i>
                    </div>
                </div>
                
                <!--<div class="card">
                    <div class="head">
                        <div>
                            <h2><?php echo $totalOJTHours; ?></h2>
                            <p>OJT Hours</p>
                        </div>
                        <i class='bx bx-time icon'></i>
                    </div>
                </div>-->
            </div>

            <!-- Reports and Announcements Section -->
            <div class="data">
                <!-- Weekly Reports Section -->
                <div class="content-data">
                    <div class="head">
                        <h3>Recent Weekly Reports</h3>
                        <div class="menu">
                            <a href="std_reports.php" class="btn-view">See All</a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="reports-table">
                            <thead>
                                <tr>
                                    <th>Week</th>
                                    <th>Date Submitted</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentReports)): ?>
                                    <?php foreach ($recentReports as $report): ?>
                                        <tr>
                                            <td>Week <?php echo htmlspecialchars($report['week']); ?></td>
                                            <td><?php echo date('F j, Y', strtotime($report['submission_date'])); ?></td>
                                            <td>
                                                <span class="status <?php echo strtolower($report['status']); ?>">
                                                    <?php echo htmlspecialchars($report['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="no-reports">No reports submitted yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Announcements Section -->
                <div class="content-data">
                    <div class="head">
                        <h3>Announcements</h3>
                    </div>
                    <div class="announcements-list">
                        <?php if (!empty($announcements)): ?>
                            <?php foreach ($announcements as $announcement): ?>
                                <div class="announcement-item">
                                    <div class="announcement-header">
                                        <h4><?php echo htmlspecialchars($announcement['title']); ?></h4>
                                        <span class="date"><?php echo date('F j, Y', strtotime($announcement['date_posted'])); ?></span>
                                    </div>
                                    <p><?php echo htmlspecialchars($announcement['content']); ?></p>
                                    <div class="announcement-footer">
                                        <span>Posted by: Prof. <?php echo htmlspecialchars($announcement['advisor_name']); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-announcements">
                                No announcements available.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            </div>
        </main>
    </section>


    <script src="css/student_dashboard.js"></script>

</body>
</html>
<?php
session_start();

        // Add user_id session variable with the same value as advisor_id
        if (isset($_SESSION['advisor_id'])) {
            $_SESSION['user_id'] = $_SESSION['advisor_id'];
        }

        // Check if user is logged in
        if (!isset($_SESSION['advisor_id'])) {
            // Redirect to login page if not logged in
            header("Location: login.php");
            exit();
        }
    $servername = "localhost";
    $username = "u798912504_root";
    $password = "Internfloucc2025*"; 
    $dbname = "u798912504_internflo";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // First get the advisor's database ID from their advisor_id
    $advisorStmt = $conn->prepare("
        SELECT id, first_name, last_name 
        FROM m_advisors 
        WHERE advisor_id = ?
    ");
    $advisorStmt->execute([$_SESSION['advisor_id']]);
    $advisor = $advisorStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$advisor) {
        throw new Exception("Advisor not found");
    }

    // Get all assignments for this advisor
    $assignmentStmt = $conn->prepare("
        SELECT 
            aa.year,
            aa.section,
            c.name AS course_name,
            p.name AS program_name
        FROM m_advisor_assignments aa
        JOIN m_courses c ON aa.course_id = c.id
        JOIN m_programs p ON aa.program_id = p.id
        WHERE aa.advisor_id = ?
    ");
    $assignmentStmt->execute([$advisor['id']]);
    $assignments = $assignmentStmt->fetchAll(PDO::FETCH_ASSOC);

    function getAdvisorStudentReports($conn, $advisorId) {
        $query = "
            SELECT 
                s.first_name,
                s.last_name,
                s.student_id,
                s.id as student_db_id,
                c.name as course_name,
                s.school_year,
                CASE WHEN ha.student_id IS NOT NULL THEN 'Hired' ELSE 'N/A' END as hired_status,
                ar.company_name,
                DATE(ha.application_date) as start_date,
                MAX(CASE WHEN wr.week = 1 THEN wr.report_file END) as week1_report,
                MAX(CASE WHEN wr.week = 2 THEN wr.report_file END) as week2_report,
                MAX(CASE WHEN wr.week = 3 THEN wr.report_file END) as week3_report,
                MAX(CASE WHEN wr.week = 4 THEN wr.report_file END) as week4_report,
                MAX(CASE WHEN wr.week = 5 THEN wr.report_file END) as week5_report,
                MAX(CASE WHEN wr.week = 6 THEN wr.report_file END) as week6_report,
                MAX(CASE WHEN wr.week = 7 THEN wr.report_file END) as week7_report,
                MAX(CASE WHEN wr.week = 8 THEN wr.report_file END) as week8_report,
                MAX(CASE WHEN wr.week = 9 THEN wr.report_file END) as week9_report,
                MAX(CASE WHEN wr.week = 10 THEN wr.report_file END) as week10_report,
                MAX(CASE WHEN wr.week = 11 THEN wr.report_file END) as week11_report,
                MAX(CASE WHEN wr.week = 12 THEN wr.report_file END) as week12_report,
                MAX(CASE WHEN wr.week = 1 THEN wr.status END) as week1_status,
                MAX(CASE WHEN wr.week = 2 THEN wr.status END) as week2_status,
                MAX(CASE WHEN wr.week = 3 THEN wr.status END) as week3_status,
                MAX(CASE WHEN wr.week = 4 THEN wr.status END) as week4_status,
                MAX(CASE WHEN wr.week = 5 THEN wr.status END) as week5_status,
                MAX(CASE WHEN wr.week = 6 THEN wr.status END) as week6_status,
                MAX(CASE WHEN wr.week = 7 THEN wr.status END) as week7_status,
                MAX(CASE WHEN wr.week = 8 THEN wr.status END) as week8_status,
                MAX(CASE WHEN wr.week = 9 THEN wr.status END) as week9_status,
                MAX(CASE WHEN wr.week = 10 THEN wr.status END) as week10_status,
                MAX(CASE WHEN wr.week = 11 THEN wr.status END) as week11_status,
                MAX(CASE WHEN wr.week = 12 THEN wr.status END) as week12_status,
                MAX(CASE WHEN wr.week = 1 THEN wr.submission_date END) as week1_submission_date,
                MAX(CASE WHEN wr.week = 2 THEN wr.submission_date END) as week2_submission_date,
                MAX(CASE WHEN wr.week = 3 THEN wr.submission_date END) as week3_submission_date,
                MAX(CASE WHEN wr.week = 4 THEN wr.submission_date END) as week4_submission_date,
                MAX(CASE WHEN wr.week = 5 THEN wr.submission_date END) as week5_submission_date,
                MAX(CASE WHEN wr.week = 6 THEN wr.submission_date END) as week6_submission_date,
                MAX(CASE WHEN wr.week = 7 THEN wr.submission_date END) as week7_submission_date,
                MAX(CASE WHEN wr.week = 8 THEN wr.submission_date END) as week8_submission_date,
                MAX(CASE WHEN wr.week = 9 THEN wr.submission_date END) as week9_submission_date,
                MAX(CASE WHEN wr.week = 10 THEN wr.submission_date END) as week10_submission_date,
                MAX(CASE WHEN wr.week = 11 THEN wr.submission_date END) as week11_submission_date,
                MAX(CASE WHEN wr.week = 12 THEN wr.submission_date END) as week12_submission_date
            FROM students s
            LEFT JOIN m_weekly_reports wr ON s.student_id = wr.student_id
            INNER JOIN m_courses c ON s.course = c.name
            INNER JOIN m_advisor_assignments aa ON (c.id = aa.course_id AND s.school_year = aa.year)
            INNER JOIN m_advisors a ON aa.advisor_id = a.id
            LEFT JOIN hired_applicants ha ON s.id = ha.student_id
            LEFT JOIN internshipad ia ON ha.internshipad_id = ia.internship_id
            LEFT JOIN approvedrecruiters ar ON ia.user_id = ar.id
            WHERE a.advisor_id = ? AND s.status = 'approved'
            GROUP BY s.student_id, s.first_name, s.last_name, c.name, s.school_year, hired_status, s.id, ar.company_name, start_date
            ORDER BY s.last_name, s.first_name";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$advisorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Fetch reports for the logged-in advisor
    $advisorReports = getAdvisorStudentReports($conn, $_SESSION['advisor_id']);
    
    $fullName = htmlspecialchars($advisor['first_name'] . ' ' . $advisor['last_name']);
    $advisorId = htmlspecialchars($_SESSION['advisor_id']);

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>

<html lang="en" dir="ltr">
  <head>
    <meta charset="UTF-8">
    <!--Drop Down Sidebar Menu -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="InsReport.css">
    <link rel="icon" href="Ins_css/ucclogo2.png">

    <!-- Boxiocns CDN Link -->
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
     <meta name="viewport" content="width=device-width, initial-scale=1.0">

     <!-- SweetAlert popup message for logout -->
     <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
     </head>
     <style>
.submission-date {
    font-size: 0.7em;
    color: #666;
    margin-top: 5px;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.review-btn + .submission-date {
    display: block;
    width: 100%;
}
.search-filter-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    gap: 10px;
    width: 80%;
    margin-left: 100px;
}

.search-input {
    flex-grow: 1;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
}

.filter-options {
    display: flex;
    gap: 10px;
    width: 40%;
}

.filter-options select {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
}

@media (max-width: 768px) {
    .search-filter-container {
        flex-direction: column;
        align-items: stretch;
    }

    .search-input {
        width: 100%;
        margin-bottom: 10px;
    }

    .filter-options {
        flex-direction: column;
    }

    .filter-options select {
        width: 100%;
    }
}

@media (max-width: 768px) {
    .submission-date {
        font-size: 0.6em;
    }
}
</style>
     <body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <a href="#" class="brand">
            <img src="Ins_css/ucc1.png" alt="Logo" class="logo-img">
        </a>
        <ul class="side-menu">
            <li><a href="InsDashboard.php"><i class='bx bxs-dashboard icon'></i> Dashboard</a></li>
            <li><a href="Ins_Student.php"><i class='bx bxs-contact icon'></i> Student List</a></li>
            <!--<li><a href="Ins_timelog.php"><i class='bx bx-time icon'></i> Timelogs</a></li>-->
            <li><a href="#" class="active"><i class='bx bxs-report icon'></i> Reports</a></li>
            <li><a href="documents.php"><i class='bx bxs-file-doc icon'></i> Documents</a></li>
            <li><a href="announcement.php"><i class='bx bxs-chat icon'></i> Announcement</a></li>
            <li><a href="chat-inbox.php"><i class='bx bxs-chat icon'></i> Emails</a></li>

        </ul>
        <br><br><br>
        <div class="ads">
            <div class="wrapper" style="margin-top: -80px;">
                <a href="../../COORDINATOR/companysignin.php" class="btn-upgrade">
                    <i class='bx bx-log-out' style="float: left; margin-left: 15px; margin-right: -20px; font-size: 20px;"></i>
                    <span style="display: inline-block; width: 100%; text-align: center;">Log out</span>
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
        <span class="user-id"><?php echo htmlspecialchars($advisorId); ?></span>
        </div>
    </div>
</nav>
 

<main>
<!-- Course Switch Section -->
<div class="course-switch">
    <div class="switch-container">
        <?php 
        // Group assignments by course and year
        $groupedAssignments = [];
        foreach ($assignments as $assignment) {
            $key = $assignment['course_name'] . '-' . $assignment['year'];
            if (!isset($groupedAssignments[$key])) {
                $groupedAssignments[$key] = [
                    'course_name' => $assignment['course_name'],
                    'year' => $assignment['year'],
                    'sections' => []
                ];
            }
            if (!empty($assignment['section'])) {
                $groupedAssignments[$key]['sections'][] = $assignment['section'];
            }
        }

        foreach ($groupedAssignments as $key => $group): 
        ?>
            <button class="course-btn" data-course="<?php echo htmlspecialchars($group['course_name']); ?>" 
                    data-year="<?php echo htmlspecialchars($group['year']); ?>">
                <?php echo htmlspecialchars($group['course_name'] . ' ' . $group['year']); ?>
            </button>
        <?php endforeach; ?>
    </div>
</div>
  
<!-- Filter Section -->
        <br><br><br>
                <div class="search-filter-container">
    <input type="text" id="globalSearch" placeholder="Search by Name, Company, or Submission Date" class="search-input">
    <div class="filter-options">
        <select id="weekFilter">
            <option value="all">All Weeks</option>
            <?php for($i = 1; $i <= 12; $i++): ?>
                <option value="<?php echo $i; ?>">Week <?php echo $i; ?></option>
            <?php endfor; ?>
        </select>
        <select id="statusFilter">
            <option value="all">All Status</option>
            <option value="pending">Pending</option>
            <option value="reviewed">Reviewed</option>
        </select>
    </div>
</div>
        <div class="reports-section">
    <h3>Submitted Reports</h3>
    <div class="table-responsive">
    <table class="reports-table">
    <thead>
        <tr>
            <th>Student Name</th>
            <th>Student ID</th>
            <th>Company</th>
            <th>Start Date</th>
            <th>Status</th>
            <?php for($i = 1; $i <= 12; $i++): ?>
                <th>Week <?php echo $i; ?></th>
            <?php endfor; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach($advisorReports as $report): ?>
            <tr data-course="<?php echo htmlspecialchars($report['course_name']); ?>" 
                data-year="<?php echo htmlspecialchars($report['school_year']); ?>">
                <td><?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?></td>
                <td><?php echo htmlspecialchars($report['student_id']); ?></td>
                <td><?php echo !empty($report['company_name']) ? htmlspecialchars($report['company_name']) : ''; ?></td>
                <td><?php echo !empty($report['start_date']) ? htmlspecialchars($report['start_date']) : ''; ?></td>
                <td><?php echo htmlspecialchars($report['hired_status']); ?></td>

                <?php for($i = 1; $i <= 12; $i++): ?>
                    <td>
<?php 
$reportFile = $report["week{$i}_report"];
$status = $report["week{$i}_status"];
$submissionDate = $report["week{$i}_submission_date"]; // Add this line
if ($reportFile): ?>
    <button class="review-btn <?php echo strtolower($status); ?>" 
            onclick="reviewReport('<?php echo htmlspecialchars($reportFile); ?>', 
            '<?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?>')">
        <?php echo $status ?: 'Review'; ?>
    </button>
    <?php if ($submissionDate): ?>
        <div class="submission-date">
            Submitted: <?php echo htmlspecialchars(date('M d, Y H:i', strtotime($submissionDate))); ?>
        </div>
    <?php endif; ?>
<?php else: ?>
    <span class="no-report">-</span>
<?php endif; ?>
                    </td>
                <?php endfor; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
    </div>
</div>
</main>
        </section>

<!-- Review Modal -->
<div id="reviewModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeReviewModal()">&times;</span>
        <h3>Review Report for <span id="studentNameReview"></span></h3>
        <iframe id="studentFile" style="width: 100%; height: 300px;" src="" frameborder="0"></iframe>
        <button class="reviewed-btn" onclick="markReviewed()">Mark as Reviewed</button>
    </div>
</div>


<!-- Feedback Modal
<div id="feedbackModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeFeedbackModal()">&times;</span>
        <h3>Feedback for <span id="studentNameFeedback"></span></h3>
        <textarea id="feedbackMessage" placeholder="Enter your feedback here..."><?php echo isset($report['feedback']) ? htmlspecialchars($report['feedback']) : ''; ?></textarea>
        <input type="hidden" id="currentReportId" value="">
        <button onclick="sendFeedback()">Send Feedback</button>
    </div>
</div>-->



  <!--Footer
  <div class="underfooter-bg">
      <div class="underfooter">
          <div class="uf-content">
              <p>Copyright Internflo©2024. All Rights Reserved</p>
          </div>
      </div>
  </div>-->

<script>
// Function to review report//MGA BINAGO
function reviewReport(reportFile, studentName) {
    console.log("Attempting to review report:", reportFile);
    
    // Get the modal elements
    const modal = document.getElementById('reviewModal');
    const studentNameSpan = document.getElementById('studentNameReview');
    const fileViewer = document.getElementById('studentFile');

    // Set the student name in the modal
    studentNameSpan.textContent = studentName;

    // Use a PHP script to serve the file securely
    const filePath = `view_report.php?file=${encodeURIComponent(reportFile)}`;
    
    // Set the source of the iframe to display the file
    fileViewer.src = filePath;

    // Show the modal
    modal.style.display = 'block';
}
//HANGGANG DITO

// Function to close review modal
function closeReviewModal() {
    const modal = document.getElementById('reviewModal');
    const fileViewer = document.getElementById('studentFile');
    
    // Clear the iframe source
    fileViewer.src = '';
    
    // Hide the modal
    modal.style.display = 'none';
}

// Function to mark report as reviewed
function markReviewed() {
    // Get the current file path from the iframe
    const fileViewer = document.getElementById('studentFile');
    const filePath = fileViewer.src;
    
    // Extract the report file name from the URL parameters
    const urlParams = new URLSearchParams(filePath.split('?')[1]);
    const reportFile = urlParams.get('file');
    
    if (!reportFile) {
        Swal.fire({
            title: 'Error!',
            text: 'Could not identify the report file',
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    // Send AJAX request to update status
    fetch('update_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            report_file: reportFile,
            status: 'Reviewed'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Success!',
                text: 'Report marked as reviewed successfully!',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Reload the page to reflect the updated status
                    window.location.reload();
                }
            });
        } else {
            Swal.fire({
                title: 'Error!',
                text: data.message || 'Error updating report status',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            title: 'Error!',
            text: 'Error updating report status',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    });
}


// Close modals when clicking outside
window.onclick = function(event) {
    const reviewModal = document.getElementById("reviewModal");
    const feedbackModal = document.getElementById("feedbackModal");
    
    if (event.target === reviewModal) {
        closeReviewModal();
    }
    if (event.target === feedbackModal) {
        closeFeedbackModal();
    }
}


document.addEventListener('DOMContentLoaded', function() {
    // Get filter elements
    const studentSearch = document.getElementById('studentSearch');
    const weekSelect = document.getElementById('reportWeek');
    const statusSelect = document.getElementById('reportStatus');
    const filterBtn = document.querySelector('.filter-btn');
    const tableBody = document.querySelector('.reports-table tbody');

    // Function to filter table rows
    function filterReports() {
        const rows = tableBody.getElementsByTagName('tr');
        const searchTerm = studentSearch.value.toLowerCase();
        const selectedWeek = weekSelect.value;
        const selectedStatus = statusSelect.value;

        for (let row of rows) {
            const studentName = row.cells[0].textContent.toLowerCase();
            const studentId = row.cells[1].textContent.toLowerCase();
            const week = row.cells[2].textContent.replace('Week ', '');
            const status = row.cells[4].textContent.toLowerCase();

            // Check if row matches all selected filters
            const matchesSearch = searchTerm === '' || 
                                studentName.includes(searchTerm) || 
                                studentId.includes(searchTerm);
            const matchesWeek = selectedWeek === 'all' || week === selectedWeek;
            const matchesStatus = selectedStatus === 'all' || status === selectedStatus.toLowerCase();

            // Show/hide row based on filter matches
            row.style.display = (matchesSearch && matchesWeek && matchesStatus) ? '' : 'none';
        }
    }

    // Add event listeners
    filterBtn.addEventListener('click', filterReports);

    // Optional: Add real-time filtering on input/change events
    studentSearch.addEventListener('input', filterReports);
    weekSelect.addEventListener('change', filterReports);
    statusSelect.addEventListener('change', filterReports);

    // Optional: Add reset functionality
    function resetFilters() {
        studentSearch.value = '';
        weekSelect.value = 'all';
        statusSelect.value = 'all';
        filterReports();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const courseButtons = document.querySelectorAll('.course-btn');
    const tbody = document.querySelector('.reports-table tbody');
    const rows = tbody.getElementsByTagName('tr');
    
    // Activate the first button by default
    if (courseButtons.length > 0) {
        courseButtons[0].classList.add('active');
        filterReports(courseButtons[0].dataset.course, courseButtons[0].dataset.year);
    }
    
    courseButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            courseButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Get the course and year from the button's data attributes
            const selectedCourse = this.dataset.course;
            const selectedYear = this.dataset.year;
            
            // Filter reports
            filterReports(selectedCourse, selectedYear);
        });
    });
    
    function filterReports(course, year) {
        // Loop through all rows in the table
        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            if (row.dataset.course === course && row.dataset.year === year) {
                row.style.display = ''; // Show the row
            } else {
                row.style.display = 'none'; // Hide the row
            }
        }
    }
    
    // Integrate with existing search functionality
    const studentSearch = document.getElementById('studentSearch');
    if (studentSearch) {
        studentSearch.addEventListener('input', function() {
            const searchText = this.value.toLowerCase();
            const activeButton = document.querySelector('.course-btn.active');
            const selectedCourse = activeButton ? activeButton.dataset.course : '';
            const selectedYear = activeButton ? activeButton.dataset.year : '';
            
            for (let row of rows) {
                let showRow = false;
                if (row.dataset.course === selectedCourse && row.dataset.year === selectedYear) {
                    const cells = row.getElementsByTagName('td');
                    for (let cell of cells) {
                        if (cell.textContent.toLowerCase().includes(searchText)) {
                            showRow = true;
                            break;
                        }
                    }
                }
                row.style.display = showRow ? '' : 'none';
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const globalSearch = document.getElementById('globalSearch');
    const weekFilter = document.getElementById('weekFilter');
    const statusFilter = document.getElementById('statusFilter');
    const courseButtons = document.querySelectorAll('.course-btn');
    const tbody = document.querySelector('.reports-table tbody');
    const rows = tbody.getElementsByTagName('tr');

    // Activate the first button by default
    if (courseButtons.length > 0) {
        courseButtons[0].classList.add('active');
        applyFilters();
    }

    // Add event listeners for filtering
    globalSearch.addEventListener('input', applyFilters);
    weekFilter.addEventListener('change', applyFilters);
    statusFilter.addEventListener('change', applyFilters);

    // Course button event listeners
    courseButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            courseButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Apply filters
            applyFilters();
        });
    });

    function applyFilters() {
        // Get current active course and year
        const activeButton = document.querySelector('.course-btn.active');
        const selectedCourse = activeButton ? activeButton.dataset.course : '';
        const selectedYear = activeButton ? activeButton.dataset.year : '';

        // Get filter values
        const searchText = globalSearch.value.toLowerCase().trim();
        const selectedWeek = weekFilter.value;
        const selectedStatus = statusFilter.value.toLowerCase();

        // Loop through all rows
        for (let row of rows) {
            // Check course and year first
            const rowCourse = row.dataset.course;
            const rowYear = row.dataset.year;

            // Skip rows that don't match the current course/year
            if (rowCourse !== selectedCourse || rowYear !== selectedYear) {
                row.style.display = 'none';
                continue;
            }

            // Get row data
            const studentName = row.cells[0].textContent.toLowerCase();
            const studentId = row.cells[1].textContent.toLowerCase();
            const company = row.cells[2].textContent.toLowerCase();

            // Check search text against name, ID, and company
            const matchesSearch = searchText === '' || 
                studentName.includes(searchText) || 
                studentId.includes(searchText) || 
                company.includes(searchText);

            // Check week and status filters
            const weekCells = Array.from(row.cells).slice(5); // Week columns start from index 5
            let matchesWeek = selectedWeek === 'all';
            let matchesStatus = selectedStatus === 'all';

            // Check week filter
            if (selectedWeek !== 'all') {
                const weekIndex = parseInt(selectedWeek) + 4; // Adjust for earlier columns
                const weekCell = row.cells[weekIndex];
                const weekButton = weekCell.querySelector('button');
                const submissionDate = weekCell.querySelector('.submission-date');
                
                if (weekButton || (submissionDate && submissionDate.textContent.toLowerCase().includes(searchText))) {
                    matchesWeek = true;
                } else {
                    matchesWeek = false;
                }
            }

            // Check status filter
            if (selectedStatus !== 'all') {
                const weekStatusCells = Array.from(row.cells).slice(5);
                matchesStatus = weekStatusCells.some(cell => {
                    const button = cell.querySelector('button');
                    return button && button.classList.contains(selectedStatus);
                });
            }

            // Determine row visibility
            row.style.display = (matchesSearch && matchesWeek && matchesStatus) ? '' : 'none';
        }
    }
});
</script>

  
  <!-- SweetAlert popup message for logout -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
  <script src="Ins_js/mobileResponsive.js"></script>
  <script src="Ins_css/student_dashboard.js"></script>
</body>
</html>
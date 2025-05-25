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
$password = "Internfloucc2025*"; // Update with your database password
$dbname = "u798912504_internflo";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Handle approval action
        if (isset($_POST['action']) && isset($_POST['student_id'])) {
            $action = $_POST['action'];
            $studentId = $_POST['student_id'];
            
            $updateStmt = $conn->prepare("
                UPDATE students 
                SET status = ?, approved_by = ?, approved_at = NOW()
                WHERE student_id = ?
            ");
            
            $status = ($action === 'approve') ? 'approved' : 'rejected';
            $updateStmt->execute([$status, $_SESSION['advisor_id'], $studentId]);
            
            // Redirect to prevent form resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
        
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

        // Fetch students based on assignments
        // Fetch students based on assignments with internship status
// Modify your SQL query to fetch students based on assignments
$students = [];
if (!empty($assignments)) {
    $query = "
        SELECT DISTINCT
            s.id,
            s.student_id,
            s.first_name,
            s.middle_name,
            s.last_name,
            s.suffix,
            s.course,
            s.school_year,
            s.status,
            s.approved_by,
            s.approved_at
        FROM students s
        WHERE (";

    $conditions = [];
    $params = [];
    
    foreach ($assignments as $assignment) {
        $conditions[] = "(s.course = ? AND s.school_year = ?)";
        $params[] = $assignment['course_name'];
        $params[] = $assignment['year'];
    }
    
    $query .= implode(" OR ", $conditions);
    $query .= ") ORDER BY s.status ASC, s.last_name, s.first_name";

    $studentStmt = $conn->prepare($query);
    $studentStmt->execute($params);
    $students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);
}

        $fullName = htmlspecialchars($advisor['first_name'] . ' ' . $advisor['last_name']);
        $advisorId = htmlspecialchars($_SESSION['advisor_id']);

    } catch (Exception $e) {
        error_log("Error in advisor dashboard: " . $e->getMessage());
        echo "An error occurred. Please try again later.";
        exit();
    }
?>


<!DOCTYPE html>

<html lang="en" dir="ltr">
  <head>
    <meta charset="UTF-8">
    <!--Drop Down Sidebar Menu -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="Ins_css/Ins_Student2.css">
    <link rel="icon" href="Ins_css/ucclogo2.png">

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
            <img src="Ins_css/ucc1.png" alt="Logo" class="logo-img">
        </a>
        <ul class="side-menu">
            <li><a href="InsDashboard.php"><i class='bx bxs-dashboard icon'></i> Dashboard</a></li>
            <li><a href="#" class="active"><i class='bx bxs-contact icon'></i> Student List</a></li>
            <!--<li><a href="Ins_timelog.php"><i class='bx bx-time icon'></i> Timelogs</a></li>-->
            <li><a href="Ins_Report.php"><i class='bx bxs-report icon'></i> Reports</a></li>
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
  
    
<!-- Debug information section with section switcher -->
<div class="debug-info" style="margin: 20px; padding: 10px; background: #f5f5f5;">
    <h3>Your Assigned Class:</h3>
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
    
    // Store current section in session if switched
    if (isset($_POST['selected_section'])) {
        $_SESSION['current_section'] = $_POST['selected_section'];
    }
    
    // Get current selected section
    $currentSection = $_SESSION['current_section'] ?? '';
    
    foreach ($groupedAssignments as $key => $group):
    ?>
        <div style="margin: 5px 0;">
            Course: <?php echo htmlspecialchars($group['course_name']); ?> | 
            Year: <?php echo htmlspecialchars($group['year']); ?>
            <?php if (!empty($group['sections'])): ?>
                | Section: 
                <form method="POST" style="display: inline;">
                    <select name="selected_section" onchange="this.form.submit()" style="padding: 2px 5px; border-radius: 4px;">
                        <option value="">All Sections</option>
                        <?php foreach ($group['sections'] as $section): ?>
                            <option value="<?php echo htmlspecialchars($section); ?>"
                                <?php echo ($currentSection === $section) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($section); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<div class="course-switch" style="margin: 20px; padding: 10px;">
    <div class="switch-container">
        <?php foreach ($groupedAssignments as $key => $group): ?>
            <button class="course-btn" data-course="<?php echo htmlspecialchars($group['course_name']); ?>" 
                    data-year="<?php echo htmlspecialchars($group['year']); ?>">
                <?php echo htmlspecialchars($group['course_name'] . ' ' . $group['year']); ?>
            </button>
        <?php endforeach; ?>
    </div>
</div>

        <!-- Search Field -->
        <div class="search-container">
            <input type="text" id="searchInput" onkeyup="searchFunction()" 
                   placeholder="Search for students.." title="Type in a name">
        </div>

        <!-- Student List Table -->
        <div class="student-list">
            <table id="studentTable">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Middle Name</th>
                        <th>Course</th>
                        <th>Year/Section</th>
                        <th>Internship</th>
                        <th>Approval</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="studentTableBody">
                    <?php if (!empty($students)): ?>
                        <?php foreach ($students as $student): ?>
                            <tr class="<?php echo htmlspecialchars($student['status'] ?? 'pending'); ?>">
    <td><?php echo htmlspecialchars($student['student_id']); ?></td>
    <td><?php echo htmlspecialchars($student['last_name']); ?></td>
    <td><?php echo htmlspecialchars($student['first_name']); ?></td>
    <td><?php echo htmlspecialchars($student['middle_name']); ?></td>
    <td><?php echo htmlspecialchars($student['course']); ?></td>
    <td><?php echo htmlspecialchars($student['school_year']); ?></td>
    <td>
        <?php
            // Check if this student is in hired_applicants table
            $hireCheckStmt = $conn->prepare("SELECT 1 FROM hired_applicants WHERE student_id = ?");
            $hireCheckStmt->execute([$student['id']]);
            echo ($hireCheckStmt->rowCount() > 0) ? "Hired" : "No internship";
        ?>
    </td>
    <td><?php echo ucfirst(htmlspecialchars($student['status'] ?? 'pending')); ?></td>
    <td>
        <?php if (!$student['status'] || $student['status'] === 'pending'): ?>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student['student_id']); ?>">
                <div class="action-buttons">
                    <button type="submit" name="action" value="approve" class="approve-btn">Approve</button>
                    <button type="submit" name="action" value="reject" class="reject-btn">Reject</button>
                </div>
            </form>
        <?php else: ?>
            <?php 
                $statusDate = new DateTime($student['approved_at']);
                echo "Processed on " . $statusDate->format('M d, Y');
            ?>
        <?php endif; ?>
    </td>
</tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="no-students">No students found for your assigned courses and years</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
         <!-- Pagination controls -->
    <div id="paginationControls" class="pagination"></div>
    </main>
    </section>
  
<script>
function searchFunction() {
    var input, filter, table, tr, td, i, txtValue;
    input = document.getElementById("searchInput");
    filter = input.value.toUpperCase();
    table = document.getElementById("studentTable");
    tr = table.getElementsByTagName("tr");

    for (i = 0; i < tr.length; i++) {
        if (tr[i].getElementsByTagName("td").length > 0) {
            var found = false;
            var td = tr[i].getElementsByTagName("td");
            for (var j = 0; j < td.length; j++) {
                if (td[j]) {
                    txtValue = td[j].textContent || td[j].innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
            }
            tr[i].style.display = found ? "" : "none";
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const courseButtons = document.querySelectorAll('.course-btn');
    const tbody = document.getElementById('studentTableBody');
    const rows = tbody.getElementsByTagName('tr');
    
    // Activate the first button by default
    if (courseButtons.length > 0) {
        courseButtons[0].classList.add('active');
        filterStudents(courseButtons[0].dataset.course, courseButtons[0].dataset.year);
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
            
            // Filter students
            filterStudents(selectedCourse, selectedYear);
        });
    });
    
    function filterStudents(course, year) {
        // Loop through all rows in the table
        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const courseCell = row.cells[4]; // Course column (0-based index)
            const yearCell = row.cells[5];   // Year/Section column
            
            if (courseCell && yearCell) {
                const studentCourse = courseCell.textContent.trim();
                const studentYear = yearCell.textContent.trim();
                
                // Check if the student's course and year match the selected filters
                if (studentCourse === course && studentYear === year) {
                    row.style.display = ''; // Show the row
                } else {
                    row.style.display = 'none'; // Hide the row
                }
            }
        }
    }
    
    // Add search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            const activeButton = document.querySelector('.course-btn.active');
            const selectedCourse = activeButton ? activeButton.dataset.course : '';
            const selectedYear = activeButton ? activeButton.dataset.year : '';
            
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const courseCell = row.cells[4];
                const yearCell = row.cells[5];
                let showRow = false;
                
                // First check if the row matches the selected course and year
                if (courseCell && yearCell) {
                    const studentCourse = courseCell.textContent.trim();
                    const studentYear = yearCell.textContent.trim();
                    
                    if (studentCourse === selectedCourse && studentYear === selectedYear) {
                        // Then check if it matches the search text
                        const cells = row.getElementsByTagName('td');
                        for (let cell of cells) {
                            if (cell.textContent.toLowerCase().includes(searchText)) {
                                showRow = true;
                                break;
                            }
                        }
                    }
                }
                
                row.style.display = showRow ? '' : 'none';
            }
        });
    }
});
</script>



  <!--Footer
  <div class="underfooter-bg">
      <div class="underfooter">
          <div class="uf-content">
              <p>Copyright Internflo©2024. All Rights Reserved</p>
          </div>
      </div>
  </div>-->

  <!-- SweetAlert popup message for logout -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 

  <script src="Ins_css/student_dashboard.js"></script>
  <script src="Ins_js/Ins_Students.js"></script>
  <script src="Ins_js/mobileResponsive.js"></script>
  
 
</body>
</html>
<?php
    // Database connection
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

    // Variables for alert message
    $message = '';
    $success = false;

    // Fetch all advisors for the dropdown
    $advisorQuery = "SELECT id, advisor_id, CONCAT(last_name, ', ', first_name, ' ', middle_initial, ' ', suffix) AS full_name, 
                            last_name, first_name, middle_initial, suffix 
                    FROM m_advisors 
                    ORDER BY advisor_id";  // Ordering by advisor_id instead of name
    $advisorResult = $conn->query($advisorQuery);
    $advisors = [];
    while($row = $advisorResult->fetch_assoc()) {
        $advisors[] = $row;
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Get the advisor's database ID and full name using the advisor_id
        $advisorQuery = "SELECT id, CONCAT(last_name, ', ', first_name, 
            CASE 
                WHEN middle_initial IS NOT NULL AND middle_initial != '' 
                THEN CONCAT(' ', middle_initial, '.')
                ELSE ''
            END,
            CASE 
                WHEN suffix IS NOT NULL AND suffix != '' 
                THEN CONCAT(' ', suffix)
                ELSE ''
            END
        ) AS full_name FROM m_advisors WHERE advisor_id = ?";
        
        $stmt = $conn->prepare($advisorQuery);
        $stmt->bind_param("s", $_POST['advisor_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if (!$row) {
            $message = 'Error: Advisor not found';
        } else {
            $advisorDatabaseId = $row['id'];
            $advisorFullName = $row['full_name'];
            
            // Check if assignment already exists
            $checkQuery = "SELECT id FROM m_advisor_assignments 
                        WHERE advisor_id = ? AND program_id = ? AND course_id = ? AND year = ? AND section = ?";
            $stmt = $conn->prepare($checkQuery);
            $stmt->bind_param("iiiss", 
                $advisorDatabaseId, 
                $_POST['programId'], 
                $_POST['courseId'], 
                $_POST['year'], 
                $_POST['section']
            );
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $message = 'This advisor is already assigned to this course, year, and section';
            } else {
                // Insert the new assignment WITH the full name
                $stmt = $conn->prepare("INSERT INTO m_advisor_assignments (advisor_id, program_id, course_id, year, section, full_name) 
                                    VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iiisss", 
                    $advisorDatabaseId, 
                    $_POST['programId'], 
                    $_POST['courseId'], 
                    $_POST['year'], 
                    $_POST['section'],
                    $advisorFullName
                );
                
                if ($stmt->execute()) {
                    $message = 'Advisor assigned successfully';
                    $success = true;
                } else {
                    $message = 'Error: ' . $stmt->error;
                }
            }
        }
        $stmt->close();
    }

    $yearSections = [
        '3-A',
        '3-B',
        '3-C',
        '4-A',
        '4-B',
        '4-C'
    ];

    function getAssignedAdvisors($conn, $studentId) {
        // First get the student's course and year
        $stmt = $conn->prepare("
            SELECT s.course, s.school_year 
            FROM students s
            WHERE s.student_id = ?
        ");
        $stmt->bind_param("s", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        
        if (!$student) {
            return array();
        }
        
        // Get the course_id from m_courses
        $stmt = $conn->prepare("
            SELECT c.id, c.program_id
            FROM m_courses c
            WHERE c.name = ?
        ");
        $stmt->bind_param("s", $student['course']);
        $stmt->execute();
        $result = $stmt->get_result();
        $course = $result->fetch_assoc();
        
        if (!$course) {
            return array();
        }
        
        // Get assigned advisors with proper joins
        $stmt = $conn->prepare("
            SELECT DISTINCT 
                a.advisor_id,
                CONCAT(a.last_name, ', ', a.first_name, 
                    CASE 
                        WHEN a.middle_initial IS NOT NULL AND a.middle_initial != '' 
                        THEN CONCAT(' ', a.middle_initial, '.')
                        ELSE ''
                    END,
                    CASE 
                        WHEN a.suffix IS NOT NULL AND a.suffix != '' 
                        THEN CONCAT(' ', a.suffix)
                        ELSE ''
                    END
                ) as name
            FROM m_advisors a
            INNER JOIN m_advisor_assignments aa ON a.id = aa.advisor_id
            WHERE aa.course_id = ?
            AND aa.program_id = ?
            AND aa.year = ?
        ");
        
        $stmt->bind_param("iis", $course['id'], $course['program_id'], $student['school_year']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $advisors = array();
        while ($row = $result->fetch_assoc()) {
            $advisors[] = array(
                'advisor_id' => $row['advisor_id'],
                'name' => $row['name']
            );
        }
        
        return $advisors;
    }

    // Fetch programs
    $programQuery = "SELECT id, name FROM m_programs";
    $programResult = $conn->query($programQuery);

    // Fetch courses
    $courseQuery = "SELECT id, name, program_id FROM m_courses";
    $courseResult = $conn->query($courseQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internflo - Administrator</title>
    <link rel="icon" href="ucc-logo1.png">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel="stylesheet">
    <link rel="stylesheet" href="css/admin_styles.css">
    <link rel="stylesheet" href="css/assignAdvisor.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
</head>
<body>
<section id="sidebar">
    <div class="logo">
        <img src="ucc.png" class="logo-full" alt="UCC Full Logo">
        <img src="ucc-logo1.png" class="logo-icon" alt="UCC Icon">
    </div>
    <ul class="side-menu">
        <li><a href="admin_index.php" ><i class='bx bxs-dashboard icon'></i>Dashboard</a></li>
        <li><a href="interns.php" ><i class='bx bxs-graduation icon'></i>Interns</a></li>
        <li>
            <a href="#" ><i class='bx bxs-analyse icon' ></i>Affiliates<i class='bx bx-chevron-right icon-right'></i></a>
            <ul class="side-dropdown">
                <li><a href="partnership.php"><i class='bx bxs-briefcase icon'></i>Partnership</a></li>
                <li><a href="requests.php"><i class='bx bxs-envelope icon'></i>Requests</a></li>
            </ul>
        </li>
        <li>
            <a href="#" ><i class='bx bxs-message-rounded-detail icon'></i>Feedbacks<i class='bx bx-chevron-right icon-right'></i></a>
            <ul class="side-dropdown">
                <li><a href="studentnumberfeedback.php"><i class='bx bxs-id-card icon'></i>Student Number</a></li>
                <li><a href="websitefeedback.php"><i class='bx bx-globe icon'></i>Website</a></li>

            </ul>
        </li>
        <li>
            <a href="#" ><i class='bx bxs-analyse icon'></i>Accounts<i class='bx bx-chevron-right icon-right'></i></a>
            <ul class="side-dropdown">
                <li><a href="studentList.php"><i class='bx bxs-user-detail icon'></i>Students</a></li>
                <li><a href="companyList.php"><i class='bx bx-run icon'></i> Companies</a></li>
            </ul>
        </li>
        <li>
            <a href="#" class="active"><i class='bx bxs-user-account icon'></i>Adviser Account<i class='bx bx-chevron-right icon-right'></i></a>
                <ul class="side-dropdown">
                        <li><a href="create_account.php"><i class='bx bxs-user-detail icon'></i>Create Advisor</a></li> 
                        <li><a href="assignAdvisor.php"><i class='bx bxs-book-add icon'></i>Assign Adviser</a></li>
                        <li><a href="advisorList.php"><i class='bx bxs-user-detail icon'></i>List of Adviser</a></li>  
                </ul>
        </li>
        <li>
        <a href="#"><i class='bx bxs-file-archive icon'></i>All Student Records<i class='bx bx-chevron-right icon-right'></i></a>
            <ul class="side-dropdown">
                <li><a href="student_clas.php"><i class='bx bxs-graduation icon'></i> CLAS</a></li> 
                <li><a href="student_cba.php"><i class='bx bxs-briefcase-alt-2 icon'></i> CBA</a></li>
                <li><a href="student_ce.php"><i class='bx bxs-building-house icon'></i> CE</a></li>
                <li><a href="student_crim.php"><i class='bx bxs-shield icon'></i> CCJE</a></li>  
            </ul>

        </li>
    </ul>
</section>

    <main>
        <div class="form-container">
            <h2>Assign Practicum Coordinator</h2>
            <form action="#" method="POST">
            <div class="form-group">
            <label>Advisor ID</label>
                    <select name="advisor_id" id="advisorSelect" required>
                        <option value="">Select an Advisor ID</option>
                        <?php foreach($advisors as $advisor): ?>
                            <option value="<?php echo htmlspecialchars($advisor['advisor_id']); ?>" 
                                    data-fullname="<?php echo htmlspecialchars($advisor['full_name']); ?>">
                                <?php echo htmlspecialchars($advisor['advisor_id']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Practicum Coordinator Name</label>
                    <input type="text" id="advisorName" readonly>
                </div>

                <div class="form-group">
                    <label>Program</label>
                    <select name="programId" id="programSelect" required>
                    <option value="">Select an Program</option>
                        <?php while($program = $programResult->fetch_assoc()): ?>
                            <option value="<?php echo $program['id']; ?>"><?php echo $program['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Course</label>
                    <select name="courseId" id="courseSelect" required>
                    <option value="">Select an Course</option>
                        <?php while($course = $courseResult->fetch_assoc()): ?>
                            <option value="<?php echo $course['id']; ?>" data-program="<?php echo $course['program_id']; ?>"><?php echo $course['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                        <div class="form-group">
                <label>Year-Section</label>
                <select name="year" required>
                    <option value="">Select Year-Section</option>
                    <?php foreach($yearSections as $yearSection): ?>
                        <option value="<?php echo htmlspecialchars($yearSection); ?>">
                            <?php echo htmlspecialchars($yearSection); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

                <!--<div class="form-group">
                    <label>Section</label>
                    <input type="text" name="section">
                </div>-->

                <div class="full-width">
                    <button type="submit">Assign</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Advisor selection handling
        document.getElementById('advisorSelect').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const advisorNameField = document.getElementById('advisorName');
            
            if(this.value) {
                advisorNameField.value = selectedOption.getAttribute('data-fullname');
            } else {
                advisorNameField.value = '';
            }
        });

        // Course filtering based on program selection
        document.getElementById('programSelect').addEventListener('change', function() {
            var programId = this.value;
            var courseSelect = document.getElementById('courseSelect');
            var courseOptions = courseSelect.options;

            // Reset course selection
            courseSelect.value = '';

            // Show/hide course options based on program
            for (var i = 0; i < courseOptions.length; i++) {
                if (courseOptions[i].value === '' || courseOptions[i].getAttribute('data-program') === programId) {
                    courseOptions[i].style.display = '';
                } else {
                    courseOptions[i].style.display = 'none';
                }
            }
        });

        // SweetAlert handling
        <?php if ($message): ?>
            <?php if ($success): ?>
                Swal.fire({
                    title: 'Success!',
                    text: '<?php echo $message; ?>',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'advisorList.php';
                    }
                });
            <?php else: ?>
                Swal.fire({
                    title: 'Error!',
                    text: '<?php echo $message; ?>',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            <?php endif; ?>
        <?php endif; ?>
    </script>
    <script src="js/script.js"></script>
</body>
</html>
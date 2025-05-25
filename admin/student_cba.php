<?php
    include 'config.php';
    session_start();

    // Initialize variables
    $search_query = "";
    $course_filter = "";
    $limit = 30; // Maximum records per page
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $start = ($page - 1) * $limit;
    
    // Parse search and filter parameters
    if(isset($_GET['search_query'])) {
        $search_query = $_GET['search_query'];
    }
    
    if(isset($_GET['course_filter'])) {
        $course_filter = $_GET['course_filter'];
    }
    
    // Build the base query
    $query = "SELECT student_id, firstname, lastname, middlename, suffix, course, year, section FROM student_ba WHERE 1=1";
    $count_query = "SELECT COUNT(*) as total FROM student_ba WHERE 1=1";
    
    // Add search condition if search query is provided
    if(!empty($search_query)) {
        $search_param = "%$search_query%";
        $query .= " AND (student_id LIKE ? OR firstname LIKE ? OR lastname LIKE ? OR CONCAT(firstname, ' ', lastname) LIKE ?)";
        $count_query .= " AND (student_id LIKE ? OR firstname LIKE ? OR lastname LIKE ? OR CONCAT(firstname, ' ', lastname) LIKE ?)";
    }
    
    // Add course filter if selected
    if(!empty($course_filter)) {
        $query .= " AND course = ?";
        $count_query .= " AND course = ?";
    }
    
    // Add pagination
    $query .= " ORDER BY lastname, firstname LIMIT ?, ?";
    
    // Prepare and execute count query to get total records
    $count_stmt = $conn->prepare($count_query);
    
    // Bind parameters for the count query
    if(!empty($search_query) && !empty($course_filter)) {
        $count_stmt->bind_param("sssss", $search_param, $search_param, $search_param, $search_param, $course_filter);
    } elseif(!empty($search_query)) {
        $count_stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
    } elseif(!empty($course_filter)) {
        $count_stmt->bind_param("s", $course_filter);
    }
    
    if($count_stmt) {
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total_records = $count_result->fetch_assoc()['total'];
        $total_pages = ceil($total_records / $limit);
    } else {
        $total_records = 0;
        $total_pages = 0;
    }
    
    // Prepare and execute the main query
    $stmt = $conn->prepare($query);
    
    // Bind parameters for the main query
    if(!empty($search_query) && !empty($course_filter)) {
        $stmt->bind_param("sssssii", $search_param, $search_param, $search_param, $search_param, $course_filter, $start, $limit);
    } elseif(!empty($search_query)) {
        $stmt->bind_param("ssssii", $search_param, $search_param, $search_param, $search_param, $start, $limit);
    } elseif(!empty($course_filter)) {
        $stmt->bind_param("sii", $course_filter, $start, $limit);
    } else {
        $stmt->bind_param("ii", $start, $limit);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    // Available courses for filter dropdown
    // Available courses for filter dropdown
    $courses = [
        'Bachelor of Science in Accountancy',
        'Bachelor of Science in Accounting Information System',
        'Bachelor of Science in Business Administration, Major in Financial Management',
        'Bachelor of Science in Business Administration, Major in Human Resource Management',
        'Bachelor of Science in Business Administration, Major in Marketing Management',
        'Bachelor of Science in Entrepreneurship',
        'Bachelor of Science in Hospitality Management',
        'Bachelor of Science in Office Administration',
        'Bachelor of Science in Tourism Management'
    ];

    // Process student update if form is submitted
    if(isset($_POST['action']) && $_POST['action'] == 'update_student') {
        $student_id = $_POST['student_id'];
        $firstname = $_POST['firstname'];
        $lastname = $_POST['lastname'];
        $middlename = $_POST['middlename'];
        $suffix = $_POST['suffix'];
        $course = $_POST['course'];
        $year = $_POST['year'];
        $section = $_POST['section'];
        
        // Prepare and execute update query
        $update_stmt = $conn->prepare("UPDATE student_ba SET firstname = ?, lastname = ?, middlename = ?, suffix = ?, course = ?, year = ?, section = ? WHERE student_id = ?");
        $update_stmt->bind_param("ssssssss", $firstname, $lastname, $middlename, $suffix, $course, $year, $section, $student_id);
        
        if($update_stmt->execute()) {
            // Set success message
            $_SESSION['message'] = "Student information updated successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            // Set error message
            $_SESSION['message'] = "Error updating student information: " . $conn->error;
            $_SESSION['message_type'] = "error";
        }
        
        // Redirect back to the same page with existing filters and search
        $redirect_url = $_SERVER['PHP_SELF'];
        $params = [];
        
        if(!empty($search_query)) {
            $params[] = "search_query=" . urlencode($search_query);
        }
        
        if(!empty($course_filter)) {
            $params[] = "course_filter=" . urlencode($course_filter);
        }
        
        if(!empty($page)) {
            $params[] = "page=" . $page;
        }
        
        if(!empty($params)) {
            $redirect_url .= "?" . implode("&", $params);
        }
        
        header('Location: ' . $redirect_url);
        exit();
    }

    // Process AJAX request to get student details
    if(isset($_POST['action']) && $_POST['action'] == 'get_student') {
        $student_id = $_POST['id'];
        
        // Prepare and execute query to get student details
        $student_stmt = $conn->prepare("SELECT student_id, firstname, lastname, middlename, suffix, course, year, section FROM student_ba WHERE student_id = ?");
        $student_stmt->bind_param("s", $student_id);
        $student_stmt->execute();
        $student_result = $student_stmt->get_result();
        
        if($row = $student_result->fetch_assoc()) {
            // Return student details as JSON
            echo json_encode($row);
        } else {
            echo json_encode(['error' => 'Student not found']);
        }
        exit();
    }
    // Process student addition if form is submitted
    if(isset($_POST['action']) && $_POST['action'] == 'add_student') {
        $student_id = $_POST['student_id'];
        $firstname = $_POST['firstname'];
        $lastname = $_POST['lastname'];
        $middlename = $_POST['middlename'];
        $suffix = $_POST['suffix'];
        $course = $_POST['course'];
        $year = $_POST['year'];
        $section = $_POST['section'];
        
        // Prepare and execute insert query
        $insert_stmt = $conn->prepare("INSERT INTO student_ba (student_id, firstname, lastname, middlename, suffix, course, year, section) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insert_stmt->bind_param("ssssssss", $student_id, $firstname, $lastname, $middlename, $suffix, $course, $year, $section);
        
        if($insert_stmt->execute()) {
            $_SESSION['message'] = "Student added successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error adding student: " . $conn->error;
            $_SESSION['message_type'] = "error";
        }
        
        // Redirect logic (same as your update redirect)
        $redirect_url = $_SERVER['PHP_SELF'];
        $params = [];
        
        if(!empty($search_query)) {
            $params[] = "search_query=" . urlencode($search_query);
        }
        
        if(!empty($course_filter)) {
            $params[] = "course_filter=" . urlencode($course_filter);
        }
        
        if(!empty($page)) {
            $params[] = "page=" . $page;
        }
        
        if(!empty($params)) {
            $redirect_url .= "?" . implode("&", $params);
        }
        
        header('Location: ' . $redirect_url);
        exit();
    }
    // Process AJAX request to check if student ID exists
    if(isset($_POST['action']) && $_POST['action'] == 'check_student_id') {
        $student_id = $_POST['id'];
        
        $check_stmt = $conn->prepare("SELECT student_id FROM student_ba WHERE student_id = ?");
        $check_stmt->bind_param("s", $student_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        $response = ['exists' => $check_result->num_rows > 0];
        echo json_encode($response);
        
        $check_stmt->close();
        exit();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel="stylesheet">
    <link rel="stylesheet" href="css/admin_styles.css">
    <title>Internflo - Administrator</title>
    <link rel="icon" href="ucc-logo1.png">
    <style>
        .add-button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        .add-button:hover {
            background-color: #45a049;
        }
        .btn-edit {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            margin-right: 5px;
        }
        
        .btn-delete {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        
        .btn-edit:hover {
            background-color: #2980b9;
        }
        
        .btn-delete:hover {
            background-color: #c0392b;
        }
        
        .action-button {
            display: flex;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 60%;
            max-height: 80vh;
            overflow-y: auto;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
        }

        .student-form {
            margin-top: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .btn-submit {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .btn-submit:hover {
            background-color: #45a049;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Filter and search styles */
        .filter-section {
            margin-bottom: 20px;
            display: flex !important;
            flex-wrap: wrap !important;
            flex-direction: row !important; /* Override column direction */
            align-items: center;
            gap: 15px;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
        }
        
        .filter-group label {
            margin-right: 10px;
            font-weight: bold;
        }
        
        .filter-group select, .filter-group input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 0 !important;
        }
        
        .filter-button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .filter-button:hover {
            background-color: #45a049;
        }
        
        .reset-button {
            background-color: #95a5a6;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .reset-button:hover {
            background-color: #7f8c8d;
        }
        
        /* Pagination styles */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        
        .pagination a, .pagination span {
            color: black;
            padding: 8px 16px;
            text-decoration: none;
            background-color: #f1f1f1;
            margin: 0 4px;
            border-radius: 4px;
        }
        
        .pagination a.active {
            background-color: #4CAF50;
            color: white;
        }
        
        .pagination a:hover:not(.active) {
            background-color: #ddd;
        }
        
        .pagination .disabled {
            color: #aaa;
            background-color: #f8f8f8;
            cursor: not-allowed;
        }
        
        .records-info {
            text-align: center;
            margin-bottom: 10px;
            color: #666;
        }
    </style>
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
            <a href="#" ><i class='bx bxs-user-account icon'></i>Adviser Account<i class='bx bx-chevron-right icon-right'></i></a>
                <ul class="side-dropdown">
                        <li><a href="create_account.php"><i class='bx bxs-user-detail icon'></i>Create Advisor</a></li> 
                        <li><a href="assignAdvisor.php"><i class='bx bxs-book-add icon'></i>Assign Adviser</a></li>
                        <li><a href="advisorList.php"><i class='bx bxs-user-detail icon'></i>List of Adviser</a></li>  
                </ul>
        </li>
        <li>
        <a href="#" class="active"><i class='bx bxs-file-archive icon'></i>All Student Records<i class='bx bx-chevron-right icon-right'></i></a>
            <ul class="side-dropdown">
                <li><a href="student_clas.php"><i class='bx bxs-graduation icon'></i> CLAS</a></li> 
                <li><a href="student_cba.php"><i class='bx bxs-briefcase-alt-2 icon'></i> CBA</a></li>
                <li><a href="student_ce.php"><i class='bx bxs-building-house icon'></i> CE</a></li>
                <li><a href="student_crim.php"><i class='bx bxs-shield icon'></i> CCJE</a></li>  
            </ul>

        </li>
    </ul>
</section>

<section id="content">
    <nav>
        <i class='bx bx-menu toggle-sidebar'></i>
        <div class="profile">
            <img src="user.jpg" alt="">
            <ul class="profile-link">
                <p>Username: <span><?php echo $_SESSION['username']; ?></span></p>
                <li><a href="profile.php"><i class='bx bxs-user-circle icon'></i> Profile</a></li>
                <li><a href="logout.php"><i class='bx bxs-log-out-circle icon'></i> Logout</a></li>
            </ul>
        </div>
    </nav>

    <main>
        <h1 class="title">COLLEGE OF BUSINESS AND ACCOUNTANCY</h1>
        
        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3>CBA Students List</h3>
                </div>
                
                <!-- Filter and Search Section -->
                <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="filter-section">
                    <div class="filter-group">
                        <input type="text" id="search_query" name="search_query" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search by ID or name">
                    </div>
                    
                    <div class="filter-group">
                        <select id="course_filter" name="course_filter">
                            <option value="">All Courses</option>
                            <?php foreach($courses as $course): ?>
                                <option value="<?php echo htmlspecialchars($course); ?>" <?php echo ($course_filter == $course) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="filter-button">Apply Filters</button>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="reset-button">Reset</a>
                    <button type="button" class="add-button" onclick="openAddStudentModal()">Add Student</button>
                </form>

                <!-- Student Add Modal -->
                <div id="addStudentModal" class="modal">
                    <div class="modal-content">
                        <span class="close" onclick="closeAddModal()">×</span>
                        <h2>Add New Student</h2>
                        <form id="addStudentForm" method="post" action="" class="student-form">
                            <input type="hidden" name="action" value="add_student">
                            
                            <div class="form-group">
                                <label for="add_student_id">Student ID:</label>
                                <input type="text" id="add_student_id" name="student_id" required>
                                <span id="studentIdWarning" style="display: none; color: #721c24; font-size: 14px;"></span>
                            </div>
                            
                            <div class="form-group">
                                <label for="add_firstname">First Name:</label>
                                <input type="text" id="add_firstname" name="firstname" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="add_lastname">Last Name:</label>
                                <input type="text" id="add_lastname" name="lastname" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="add_middlename">Middle Name:</label>
                                <input type="text" id="add_middlename" name="middlename">
                            </div>
                            
                            <div class="form-group">
                                <label for="add_suffix">Suffix:</label>
                                <input type="text" id="add_suffix" name="suffix">
                            </div>
                            
                            <div class="form-group">
                                <label for="add_course">Course:</label>
                                <select id="add_course" name="course" required>
                                    <?php foreach($courses as $course): ?>
                                        <option value="<?php echo htmlspecialchars($course); ?>">
                                            <?php echo htmlspecialchars($course); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="add_year">Year:</label>
                                <select id="add_year" name="year" required>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="add_section">Section:</label>
                                <input type="text" id="add_section" name="section" required>
                            </div>
                            
                            <button type="submit" class="btn-submit">Add Student</button>
                        </form>
                    </div>
                </div>
                
                <script>
                    // Function to open add student modal
                    function openAddStudentModal() {
                        // Clear form fields
                        document.getElementById("addStudentForm").reset();
                        document.getElementById("addStudentModal").style.display = "block";
                    }

                    // Function to close add student modal
                    function closeAddModal() {
                        document.getElementById("addStudentModal").style.display = "none";
                    }

                    // Modify the window.onclick to handle both modals
                    window.onclick = function(event) {
                        var editModal = document.getElementById("studentModal");
                        var addModal = document.getElementById("addStudentModal");
                        if (event.target == editModal) {
                            editModal.style.display = "none";
                        }
                        if (event.target == addModal) {
                            addModal.style.display = "none";
                        }
                    }

                    // Function to check if student ID exists
                    function checkStudentId(studentId) {
                        var xhr = new XMLHttpRequest();
                        xhr.open("POST", "<?php echo $_SERVER['PHP_SELF']; ?>", true);
                        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xhr.onreadystatechange = function() {
                            if (this.readyState === 4 && this.status === 200) {
                                try {
                                    var response = JSON.parse(this.responseText);
                                    var warningElement = document.getElementById("studentIdWarning");
                                    if(response.exists) {
                                        warningElement.style.display = "block";
                                        warningElement.textContent = "Warning: This Student ID is already taken!";
                                        document.getElementById("addStudentForm").querySelector(".btn-submit").disabled = true;
                                    } else {
                                        warningElement.style.display = "none";
                                        document.getElementById("addStudentForm").querySelector(".btn-submit").disabled = false;
                                    }
                                } catch (e) {
                                    console.error("Error parsing JSON response:", e);
                                }
                            }
                        };
                        xhr.send("action=check_student_id&id=" + encodeURIComponent(studentId));
                    }

                    // Add event listener to student ID input
                    document.getElementById("add_student_id").addEventListener("input", function() {
                        var studentId = this.value.trim();
                        if(studentId.length > 0) {
                            checkStudentId(studentId);
                        } else {
                            document.getElementById("studentIdWarning").style.display = "none";
                            document.getElementById("addStudentForm").querySelector(".btn-submit").disabled = false;
                        }
                    });
                </script>
                <div class="records-info">
                    Showing <?php echo min($total_records, ($start + 1)); ?> to <?php echo min($start + $limit, $total_records); ?> of <?php echo $total_records; ?> records
                </div>
                
                <div class="table-data">
                    <div class="order">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Course</th>
                                    <th>Year</th>
                                    <th>Section</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if($result && $result->num_rows > 0) {
                                    while($row = $result->fetch_assoc()) {
                                        // Format name with suffix if present
                                        $fullname = $row['lastname'] . ', ' . $row['firstname'];
                                        if (!empty($row['middlename'])) {
                                            $fullname .= ' ' . substr($row['middlename'], 0, 1) . '.';
                                        }
                                        if (!empty($row['suffix'])) {
                                            $fullname .= ' ' . $row['suffix'];
                                        }
                                ?>
                                <tr>
                                    <td><?php echo $row['student_id']; ?></td>
                                    <td><?php echo $fullname; ?></td>
                                    <td><?php echo $row['course']; ?></td>
                                    <td><?php echo $row['year']; ?></td>
                                    <td><?php echo $row['section']; ?></td>
                                    <td class="action-button">
                                        <button class="btn-edit" onclick="editStudent('<?php echo $row['student_id']; ?>')">Edit</button>
                                        <button class="btn-delete" onclick="deleteStudent('<?php echo $row['student_id']; ?>')">Delete</button>
                                    </td>
                                </tr>
                                <?php
                                    }
                                } else {
                                    echo "<tr><td colspan='6' style='text-align: center;'>No students found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Pagination -->
                <?php if($total_pages > 1): ?>
                <div class="pagination">
                    <?php
                    // Previous page link
                    if($page > 1) {
                        echo '<a href="' . $_SERVER['PHP_SELF'] . '?page=' . ($page - 1);
                        if(!empty($search_query)) echo '&search_query=' . urlencode($search_query);
                        if(!empty($course_filter)) echo '&course_filter=' . urlencode($course_filter);
                        echo '">&laquo; Previous</a>';
                    } else {
                        echo '<span class="disabled">&laquo; Previous</span>';
                    }
                    
                    // Page numbers
                    $start_page = max(1, $page - 2);
                    $end_page = min($start_page + 4, $total_pages);
                    
                    if($start_page > 1) {
                        echo '<a href="' . $_SERVER['PHP_SELF'] . '?page=1';
                        if(!empty($search_query)) echo '&search_query=' . urlencode($search_query);
                        if(!empty($course_filter)) echo '&course_filter=' . urlencode($course_filter);
                        echo '">1</a>';
                        
                        if($start_page > 2) {
                            echo '<span class="disabled">...</span>';
                        }
                    }
                    
                    for($i = $start_page; $i <= $end_page; $i++) {
                        if($i == $page) {
                            echo '<a class="active" href="javascript:void(0);">' . $i . '</a>';
                        } else {
                            echo '<a href="' . $_SERVER['PHP_SELF'] . '?page=' . $i;
                            if(!empty($search_query)) echo '&search_query=' . urlencode($search_query);
                            if(!empty($course_filter)) echo '&course_filter=' . urlencode($course_filter);
                            echo '">' . $i . '</a>';
                        }
                    }
                    
                    if($end_page < $total_pages) {
                        if($end_page < $total_pages - 1) {
                            echo '<span class="disabled">...</span>';
                        }
                        
                        echo '<a href="' . $_SERVER['PHP_SELF'] . '?page=' . $total_pages;
                        if(!empty($search_query)) echo '&search_query=' . urlencode($search_query);
                        if(!empty($course_filter)) echo '&course_filter=' . urlencode($course_filter);
                        echo '">' . $total_pages . '</a>';
                    }
                    
                    // Next page link
                    if($page < $total_pages) {
                        echo '<a href="' . $_SERVER['PHP_SELF'] . '?page=' . ($page + 1);
                        if(!empty($search_query)) echo '&search_query=' . urlencode($search_query);
                        if(!empty($course_filter)) echo '&course_filter=' . urlencode($course_filter);
                        echo '">Next &raquo;</a>';
                    } else {
                        echo '<span class="disabled">Next &raquo;</span>';
                    }
                    ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</section>

<!-- Student Edit Modal -->
<div id="studentModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Edit Student Information</h2>
        <form id="studentForm" method="post" action="" class="student-form">
            <input type="hidden" name="action" value="update_student">
            <input type="hidden" id="student_id" name="student_id">
            
            <div class="form-group">
                <label for="firstname">First Name:</label>
                <input type="text" id="firstname" name="firstname" required>
            </div>
            
            <div class="form-group">
                <label for="lastname">Last Name:</label>
                <input type="text" id="lastname" name="lastname" required>
            </div>
            
            <div class="form-group">
                <label for="middlename">Middle Name:</label>
                <input type="text" id="middlename" name="middlename">
            </div>
            
            <div class="form-group">
                <label for="suffix">Suffix:</label>
                <input type="text" id="suffix" name="suffix">
            </div>
            
            <div class="form-group">
                <label for="course">Course:</label>
                <select id="course" name="course" required>
                    <?php foreach($courses as $course): ?>
                        <option value="<?php echo htmlspecialchars($course); ?>">
                            <?php echo htmlspecialchars($course); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="year">Year:</label>
                <select id="year" name="year" required>
                    <option value="1">1st Year</option>
                    <option value="2">2nd Year</option>
                    <option value="3">3rd Year</option>
                    <option value="4">4th Year</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="section">Section:</label>
                <input type="text" id="section" name="section" required>
            </div>
            
            <button type="submit" class="btn-submit">Update Student</button>
        </form>
    </div>
</div>

<script src="js/script.js"></script>
<script>
    // Function to edit student
    function editStudent(id) {
        // AJAX request to get student details
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "<?php echo $_SERVER['PHP_SELF']; ?>", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (this.readyState === 4 && this.status === 200) {
                try {
                    var student = JSON.parse(this.responseText);
                    if(student.error) {
                        alert(student.error);
                        return;
                    }
                    
                    // Populate form fields
                    document.getElementById("student_id").value = student.student_id;
                    document.getElementById("firstname").value = student.firstname;
                    document.getElementById("lastname").value = student.lastname;
                    document.getElementById("middlename").value = student.middlename || '';
                    document.getElementById("suffix").value = student.suffix || '';
                    
                    // Set course dropdown
                    var courseSelect = document.getElementById("course");
                    for(var i = 0; i < courseSelect.options.length; i++) {
                        if(courseSelect.options[i].value === student.course) {
                            courseSelect.selectedIndex = i;
                            break;
                        }
                    }
                    
                    document.getElementById("year").value = student.year;
                    document.getElementById("section").value = student.section;
                    
                    // Show modal
                    document.getElementById("studentModal").style.display = "block";
                } catch (e) {
                    console.error("Error parsing JSON response:", e);
                    alert("Error retrieving student data. Please try again.");
                }
            }
        };
        xhr.send("action=get_student&id=" + id);
    }

    // Function to close the modal
    function closeModal() {
        document.getElementById("studentModal").style.display = "none";
    }

    // Function to delete student (you'll need to implement this if needed)
    function deleteStudent(id) {
        if (confirm("Are you sure you want to delete this student?")) {
            // Implement deletion or redirect to a deletion handler
            // window.location.href = "<?php echo $_SERVER['PHP_SELF']; ?>?action=delete&id=" + id;
            alert("Delete functionality to be implemented");
        }
    }

    // Close the modal if clicked outside of it
    window.onclick = function(event) {
        var modal = document.getElementById("studentModal");
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
</script>

</body>
</html>
<?php
require_once 'config.php';

// Pagination Configuration
$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Search Parameters
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$search_field = isset($_GET['search_field']) ? $_GET['search_field'] : 'all';

// Handle Delete
if(isset($_POST['delete_student'])) {
    $student_id = $_POST['student_id'];
    $sql = "DELETE FROM students WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $operation_status = "success";
        $operation_message = "Student deleted successfully!";
    } else {
        $operation_status = "error";
        $operation_message = "Error deleting student: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}

// Handle Update
if(isset($_POST['update_student'])) {
    $id = $_POST['id'];
    $student_id = $_POST['student_id'];
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $last_name = $_POST['last_name'];
    $suffix = $_POST['suffix'];
    $course = $_POST['course'];
    $school_year = $_POST['school_year'];
    $email = $_POST['email'];
    $mobile_number = $_POST['mobile_number'];
    $city = $_POST['city'];
    $region = $_POST['region'];
    $postal_code = $_POST['postal_code'];
    $barangay = $_POST['barangay'];
    $home_address = $_POST['home_address'];
    
    $sql = "UPDATE students SET 
            student_id = ?,
            first_name = ?,
            middle_name = ?,
            last_name = ?,
            suffix = ?,
            course = ?,
            school_year = ?,
            email = ?,
            mobile_number = ?,
            city = ?,
            region = ?,
            postal_code = ?,
            barangay = ?,
            home_address = ?
            WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssssssssssssi", 
        $student_id, $first_name, $middle_name, $last_name, 
        $suffix, $course, $school_year, $email, 
        $mobile_number, $city, $region, $postal_code,
        $barangay, $home_address, $id
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $operation_status = "success";
        $operation_message = "Student updated successfully!";
    } else {
        $operation_status = "error";
        $operation_message = "Error updating student: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}

// Build search condition
$search_condition = "";
if (!empty($search)) {
    switch ($search_field) {
        case 'name':
            $search_condition = "WHERE CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE '%$search%'";
            break;
        case 'student_id':
            $search_condition = "WHERE student_id LIKE '%$search%'";
            break;
        case 'course':
            $search_condition = "WHERE course LIKE '%$search%'";
            break;
        case 'year':
            $search_condition = "WHERE school_year LIKE '%$search%'";
            break;
        default:
            $search_condition = "WHERE 
                CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE '%$search%' OR 
                student_id LIKE '%$search%' OR 
                course LIKE '%$search%' OR 
                school_year LIKE '%$search%'";
    }
}

// Count total students for pagination with search
$sql_count = "SELECT COUNT(*) AS count FROM students $search_condition";
$result_count = mysqli_query($conn, $sql_count);
$row_count = mysqli_fetch_assoc($result_count);
$total_students = $row_count['count'];
$total_pages = ceil($total_students / $items_per_page);

// Fetch Students with pagination, row number, and search
$sql = "SELECT 
            (@row_number:=@row_number + 1) AS row_num,
            id, profile_pic, student_id, last_name, first_name, middle_name, 
            suffix, course, school_year, email, mobile_number, city, region, 
            postal_code, barangay, home_address, created_at 
        FROM students, (SELECT @row_number:=?) AS r 
        $search_condition
        ORDER BY last_name ASC 
        LIMIT ?, ?";
$row_start = $offset;
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iii", $row_start, $offset, $items_per_page);
mysqli_stmt_execute($stmt);
$students = mysqli_stmt_get_result($stmt);
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

    <link rel="stylesheet" href="studentList.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.min.css">
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
            <a href="#" class="active"><i class='bx bxs-analyse icon'></i>Accounts<i class='bx bx-chevron-right icon-right'></i></a>
            <ul class="side-dropdown">
                <li><a href="studentList.php"><i class='bx bxs-user-detail icon'></i>Students</a></li>
                <li><a href="companyList.php"><i class='bx bx-run icon'></i> Companies</a></li>
            </ul>
        </li>
        <li>
            <a href="#"><i class='bx bxs-user-account icon'></i>Adviser Account<i class='bx bx-chevron-right icon-right'></i></a>
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
<h2>Student List</h2>
<div class="search-container">
    <form method="GET" class="search-form">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
               placeholder="Search..." class="search-input">
        <select name="search_field" class="search-select">
            <option value="all" <?php echo $search_field == 'all' ? 'selected' : ''; ?>>All Fields</option>
            <option value="name" <?php echo $search_field == 'name' ? 'selected' : ''; ?>>Name</option>
            <option value="student_id" <?php echo $search_field == 'student_id' ? 'selected' : ''; ?>>Student ID</option>
            <option value="course" <?php echo $search_field == 'course' ? 'selected' : ''; ?>>Course</option>
            <option value="year" <?php echo $search_field == 'year' ? 'selected' : ''; ?>>School Year</option>
        </select>
        <button type="submit" class="search-button">Search</button>
        <?php if (!empty($search)): ?>
            <a href="?page=1" class="reset-button">Reset</a>
        <?php endif; ?>
    </form>
</div>

        <div class="table-container">

            <table id="students-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Profile</th>
                        <th>Student ID</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Middle Name</th>
                        <th>Suffix</th>
                        <th>Course</th>
                        <th>School Year</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $students->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['row_num']; ?></td>
                        <td>
                            <img src="../STUDENTLOGIN/<?php echo $row['profile_pic'] ? $row['profile_pic'] : 'default-profile.png'; ?>" 
                                 alt="Profile" class="profile-pic">
                        </td>
                        <td><?php echo $row['student_id']; ?></td>
                        <td><?php echo $row['last_name']; ?></td>
                        <td><?php echo $row['first_name']; ?></td>
                        <td><?php echo $row['middle_name']; ?></td>
                        <td><?php echo $row['suffix']; ?></td>
                        <td><?php echo $row['course']; ?></td>
                        <td><?php echo $row['school_year']; ?></td>
                        <td>
                            <button class="edit-btn" onclick='openEditModal(<?php echo json_encode($row); ?>)'>Edit</button>
                            <form method="POST" style="display: inline;" onsubmit="return confirmDelete()">
                                <input type="hidden" name="student_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="delete_student" class="delete-btn">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="pagination-container">
    <ul class="pagination">
        <?php if($current_page > 1): ?>
            <li><a href="?page=<?php echo ($current_page - 1); ?>&search=<?php echo urlencode($search); ?>&search_field=<?php echo urlencode($search_field); ?>">&laquo;</a></li>
        <?php endif; ?>
        
        <?php for($i = 1; $i <= $total_pages; $i++): ?>
            <li>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&search_field=<?php echo urlencode($search_field); ?>" 
                   class="<?php echo ($i == $current_page) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            </li>
        <?php endfor; ?>
        
        <?php if($current_page < $total_pages): ?>
            <li><a href="?page=<?php echo ($current_page + 1); ?>&search=<?php echo urlencode($search); ?>&search_field=<?php echo urlencode($search_field); ?>">&raquo;</a></li>
        <?php endif; ?>
    </ul>
</div>
        

        <!-- Edit Modal -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <div class="section-header1">Edit Student Information</div>
                <form id="editForm" method="POST" onsubmit="return confirmUpdate()">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="update_student" value="1">
                    
                    <!-- Personal Information Section -->
                    <div class="section-header">Personal Information</div>
                    <div class="form-group">
                    <label for="edit_student_id">Student ID:</label>
                    <input type="text" id="edit_student_id" name="student_id" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_last_name">Last Name:</label>
                        <input type="text" id="edit_last_name" name="last_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_first_name">First Name:</label>
                        <input type="text" id="edit_first_name" name="first_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_middle_name">Middle Name:</label>
                        <input type="text" id="edit_middle_name" name="middle_name">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_suffix">Suffix:</label>
                        <input type="text" id="edit_suffix" name="suffix">
                    </div>

                    <!-- Academic Information Section -->
                    <div class="section-header">Academic Information</div>
                    <div class="form-group">
                        <label for="edit_course">Course:</label>
                        <input type="text" id="edit_course" name="course" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_school_year">School Year:</label>
                        <input type="text" id="edit_school_year" name="school_year" required>
                    </div>

                    <!-- Contact Information Section -->
                    <div class="section-header">Contact Information</div>
                    <div class="form-group">
                        <label for="edit_email">Email:</label>
                        <input type="email" id="edit_email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_mobile_number">Mobile Number:</label>
                        <input type="text" id="edit_mobile_number" name="mobile_number" required>
                    </div>

                    <!-- Address Information Section -->
                    <div class="section-header">Address Information</div>
                    <div class="form-group">
                        <label for="edit_region">Region:</label>
                        <input type="text" id="edit_region" name="region" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_city">City:</label>
                        <input type="text" id="edit_city" name="city" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_barangay">Barangay:</label>
                        <input type="text" id="edit_barangay" name="barangay" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_postal_code">Postal Code:</label>
                        <input type="text" id="edit_postal_code" name="postal_code" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_home_address">Home Address:</label>
                        <input type="text" id="edit_home_address" name="home_address" required>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="update-btn">Update</button>
                        <button type="button" class="cancel-btn" onclick="closeEditModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </main>


    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.all.min.js"></script>
    
    <script>
        // Show SweetAlert based on operation status
        <?php if(!empty($operation_status)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '<?php echo $operation_status; ?>',
                title: '<?php echo $operation_status == "success" ? "Success!" : "Error!"; ?>',
                text: '<?php echo $operation_message; ?>',
                confirmButtonColor: '<?php echo $operation_status == "success" ? "#4CAF50" : "#f44336"; ?>'
            });
        });
        <?php endif; ?>

        function openEditModal(student) {
            document.getElementById('editModal').style.display = 'block';
            document.getElementById('edit_id').value = student.id;
            document.getElementById('edit_student_id').value = student.student_id;
            document.getElementById('edit_last_name').value = student.last_name;
            document.getElementById('edit_first_name').value = student.first_name;
            document.getElementById('edit_middle_name').value = student.middle_name;
            document.getElementById('edit_suffix').value = student.suffix;
            document.getElementById('edit_course').value = student.course;
            document.getElementById('edit_school_year').value = student.school_year;
            document.getElementById('edit_email').value = student.email;
            document.getElementById('edit_mobile_number').value = student.mobile_number;
            document.getElementById('edit_region').value = student.region;
            document.getElementById('edit_city').value = student.city;
            document.getElementById('edit_barangay').value = student.barangay;
            document.getElementById('edit_postal_code').value = student.postal_code;
            document.getElementById('edit_home_address').value = student.home_address;
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) {
                closeEditModal();
            }
        }

        function confirmDelete() {
            return Swal.fire({
                title: 'Delete Student',
                text: 'Are you sure you want to delete this student? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f44336',
                cancelButtonColor: '#aaa',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                return result.isConfirmed;
            });
        }
        
        function confirmUpdate() {
            return Swal.fire({
                title: 'Update Student',
                text: 'Are you sure you want to update this student information?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4CAF50',
                cancelButtonColor: '#aaa',
                confirmButtonText: 'Yes, update it!'
            }).then((result) => {
                return result.isConfirmed;
            });
        }
    </script>
    <script src="js/script.js"></script>
</body>
</html>
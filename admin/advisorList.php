<?php
// Database connection
$servername = "localhost";
$username = "u798912504_root";
$password = "Internfloucc2025*"; // Update with your database password
$dbname = "u798912504_internflo";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Pagination Configuration
$items_per_page = 5;

// Get current page for advisors
$current_page_advisors = isset($_GET['advisors_page']) ? (int)$_GET['advisors_page'] : 1;
$offset_advisors = ($current_page_advisors - 1) * $items_per_page;

// Get current page for assignments
$current_page_assignments = isset($_GET['assignments_page']) ? (int)$_GET['assignments_page'] : 1;
$offset_assignments = ($current_page_assignments - 1) * $items_per_page;

// Handle Assignment Deletion
if(isset($_POST['delete_assignment'])) {
    $assignment_id = $_POST['assignment_id'];
    $sql = "DELETE FROM m_advisor_assignments WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $assignment_id);
    
    if ($stmt->execute()) {
        // Success message will be shown via SweetAlert
    } else {
        // Error message will be shown via SweetAlert
    }
}

// Handle Delete
if(isset($_POST['delete_advisor'])) {
    $advisor_id = $_POST['advisor_id'];
    $sql = "DELETE FROM m_advisors WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $advisor_id);
    
    if ($stmt->execute()) {
        // Success message will be shown via SweetAlert
    } else {
        // Error message will be shown via SweetAlert
    }
}

// Handle Update
if(isset($_POST['update_advisor'])) {
    $advisor_id = $_POST['advisor_id'];
    $advisor_id_number = $_POST['advisor_id_number'];
    $last_name = $_POST['last_name'];
    $first_name = $_POST['first_name'];
    $middle_initial = $_POST['middle_initial'];
    $suffix = $_POST['suffix'];
    $email = $_POST['email'];
    $contact_no = $_POST['contact_no'];
    $current_datetime = date('Y-m-d H:i:s');
    
    // Check if password needs to be updated
    if (!empty($_POST['password'])) {
        // Hash the new password
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql = "UPDATE m_advisors SET 
                advisor_id = ?,
                last_name = ?, 
                first_name = ?, 
                middle_initial = ?, 
                suffix = ?,
                email = ?,
                contact_no = ?,
                password = ?,
                updated_at = ?
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssi", $advisor_id_number, $last_name, $first_name, $middle_initial, 
                         $suffix, $email, $contact_no, $hashed_password, $current_datetime, $advisor_id);
    } else {
        // Update without changing password
        $sql = "UPDATE m_advisors SET 
                advisor_id = ?,
                last_name = ?, 
                first_name = ?, 
                middle_initial = ?, 
                suffix = ?,
                email = ?,
                contact_no = ?,
                updated_at = ?
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssi", $advisor_id_number, $last_name, $first_name, $middle_initial, 
                         $suffix, $email, $contact_no, $current_datetime, $advisor_id);
    }
    
    if ($stmt->execute()) {
        // Success message will be shown via SweetAlert
    } else {
        // Error message will be shown via SweetAlert
    }
}

// Count total advisors for pagination
$sql_count_advisors = "SELECT COUNT(*) AS count FROM m_advisors";
$result_count_advisors = $conn->query($sql_count_advisors);
$row_count_advisors = $result_count_advisors->fetch_assoc();
$total_advisors = $row_count_advisors['count'];
$total_pages_advisors = ceil($total_advisors / $items_per_page);

// Fetch Advisors with pagination
$sql = "SELECT * FROM m_advisors LIMIT $offset_advisors, $items_per_page";
$advisors = $conn->query($sql);

// Count total assignments for pagination
$sql_count_assignments = "SELECT COUNT(*) AS count FROM m_advisor_assignments";
$result_count_assignments = $conn->query($sql_count_assignments);
$row_count_assignments = $result_count_assignments->fetch_assoc();
$total_assignments = $row_count_assignments['count'];
$total_pages_assignments = ceil($total_assignments / $items_per_page);

// Fetch Assignments with course and program names (with pagination)
$assignments_sql = "SELECT aa.*, 
                          m_courses.name as course_name,
                          m_programs.name as program_name,
                          m_advisors.first_name,
                          m_advisors.last_name
                   FROM m_advisor_assignments aa
                   JOIN m_courses ON aa.course_id = m_courses.id
                   JOIN m_programs ON aa.program_id = m_programs.id
                   JOIN m_advisors ON aa.advisor_id = m_advisors.id
                   LIMIT $offset_assignments, $items_per_page";
$assignments = $conn->query($assignments_sql);

// Store operation status for SweetAlert
$operation_status = "";
$operation_message = "";

if(isset($_POST['delete_assignment'])) {
    if ($stmt->execute()) {
        $operation_status = "success";
        $operation_message = "Advisor assignment removed successfully!";
    } else {
        $operation_status = "error";
        $operation_message = "Error removing assignment: " . $stmt->error;
    }
}

if(isset($_POST['delete_advisor'])) {
    if ($stmt->execute()) {
        $operation_status = "success";
        $operation_message = "Advisor deleted successfully!";
    } else {
        $operation_status = "error";
        $operation_message = "Error deleting advisor: " . $stmt->error;
    }
}

if(isset($_POST['update_advisor'])) {
    if ($stmt->execute()) {
        $operation_status = "success";
        $operation_message = "Advisor updated successfully!";
    } else {
        $operation_status = "error";
        $operation_message = "Error updating advisor: " . $stmt->error;
    }
}
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

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.min.css">
</head>
<body>
<style>
    *{
        font-size: 13px;
    }
        .table-container {
            margin: 500px 20px 20px 20px;
            overflow-x: auto;
            

        }
        h2{
           margin-left:170px;
           margin-top: 0;
        }
        table {
            border-collapse: collapse;
            width: 80%;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            margin-left: 20%;
            margin-bottom: 30px;
            
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
            
        }
        th {
            background-color: #4CAF50;
            font-weight: bold;
            color: #f4f4f4;
        }
        tr:nth-child(even) {
            background-color: #f8f8f8;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .edit-btn, .delete-btn, .remove-btn {
            padding: 5px 10px;
            margin: 2px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        .edit-btn {
            background-color: #4CAF50;
            color: white;
        }
        .delete-btn, .remove-btn {
            background-color: #f44336;
            color: white;
        }
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        z-index: 1000;
    }
    .modal-content {
        background-color: white;
        margin: 5% auto; /* Changed from 15% to 5% to position higher */
        padding: 20px;
        width: 70%;
        max-width: 500px;
        border-radius: 5px;
        max-height: 80vh; /* Added to ensure modal doesn't exceed viewport height */
        overflow-y: auto; /* Added scrolling for overflow content */
    }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
            /* Pagination Styles */
    .pagination {
        display: flex;
        justify-content: center;
        list-style: none;
        margin: 20px 0;
        padding: 0;
        margin-left: 170px;
    }
    .pagination li {
        margin: 0 5px;
    }
    .pagination a {
        text-decoration: none;
        padding: 8px 12px;
        background-color: #f4f4f4;
        color: #333;
        border-radius: 3px;
        transition: background-color 0.3s;
    }
    .pagination a.active {
        background-color: #4CAF50;
        color: white;
    }
    .pagination a:hover:not(.active) {
        background-color: #ddd;
    }
</style>
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
    <div class="table-container">
        <h2>Practicum Coordinator List</h2>
        <table id="advisors-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Practicum Coordinator ID</th>
                    <th>Last Name</th>
                    <th>First Name</th>
                    <th>Middle Initial</th>
                    <th>Suffix</th>
                    <th>Email</th>
                    <th>Contact No</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $advisors->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['advisor_id']; ?></td>
                    <td><?php echo $row['last_name']; ?></td>
                    <td><?php echo $row['first_name']; ?></td>
                    <td><?php echo $row['middle_initial']; ?></td>
                    <td><?php echo $row['suffix']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo $row['contact_no']; ?></td>
                    <td class="timestamp"><?php echo $row['created_at']; ?></td>
                    <td class="timestamp"><?php echo $row['updated_at']; ?></td>
                    <td>
                        <button class="edit-btn" onclick='openEditModal(<?php echo json_encode($row); ?>)'>Edit</button>
                        <form method="POST" style="display: inline;" onsubmit="return confirmDelete('advisor')">
                            <input type="hidden" name="advisor_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="delete_advisor" class="delete-btn">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

                <!-- Advisors Pagination -->
                <div class="pagination-container">
            <ul class="pagination">
                <?php if($current_page_advisors > 1): ?>
                    <li><a href="?advisors_page=<?php echo ($current_page_advisors - 1); ?>&assignments_page=<?php echo $current_page_assignments; ?>">&laquo;</a></li>
                <?php endif; ?>
                
                <?php for($i = 1; $i <= $total_pages_advisors; $i++): ?>
                    <li>
                        <a href="?advisors_page=<?php echo $i; ?>&assignments_page=<?php echo $current_page_assignments; ?>" 
                           class="<?php echo ($i == $current_page_advisors) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <?php if($current_page_advisors < $total_pages_advisors): ?>
                    <li><a href="?advisors_page=<?php echo ($current_page_advisors + 1); ?>&assignments_page=<?php echo $current_page_assignments; ?>">&raquo;</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <h2>Practicum Coordinator Class</h2>
        <table id="assignments-table">
            <thead>
                <tr>
                    <th>Practicum Coordinator Name</th>
                    <th>Program</th>
                    <th>Course</th>
                    <th>Year/Section</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $assignments->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['last_name'] . ', ' . $row['first_name']; ?></td>
                    <td><?php echo $row['program_name']; ?></td>
                    <td><?php echo $row['course_name']; ?></td>
                    <td><?php echo $row['year']; ?></td>
                    <td>
                        <form method="POST" style="display: inline;" onsubmit="return confirmDelete('assignment')">
                            <input type="hidden" name="assignment_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="delete_assignment" class="remove-btn">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
                <!-- Assignments Pagination -->
                <div class="pagination-container">
            <ul class="pagination">
                <?php if($current_page_assignments > 1): ?>
                    <li><a href="?advisors_page=<?php echo $current_page_advisors; ?>&assignments_page=<?php echo ($current_page_assignments - 1); ?>">&laquo;</a></li>
                <?php endif; ?>
                
                <?php for($i = 1; $i <= $total_pages_assignments; $i++): ?>
                    <li>
                        <a href="?advisors_page=<?php echo $current_page_advisors; ?>&assignments_page=<?php echo $i; ?>" 
                           class="<?php echo ($i == $current_page_assignments) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <?php if($current_page_assignments < $total_pages_assignments): ?>
                    <li><a href="?advisors_page=<?php echo $current_page_advisors; ?>&assignments_page=<?php echo ($current_page_assignments + 1); ?>">&raquo;</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    </div>

    

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h2>Edit Advisor</h2>
            <form id="editForm" method="POST" onsubmit="return confirmUpdate()">
                <input type="hidden" name="advisor_id" id="edit_id">
                <input type="hidden" name="update_advisor" value="1">
                
                <div class="form-group">
                    <label for="edit_advisor_id_number">Advisor ID:</label>
                    <input type="text" id="edit_advisor_id_number" name="advisor_id_number" required>
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
                    <label for="edit_middle_initial">Middle Initial:</label>
                    <input type="text" id="edit_middle_initial" name="middle_initial">
                </div>
                
                <div class="form-group">
                    <label for="edit_suffix">Suffix:</label>
                    <input type="text" id="edit_suffix" name="suffix">
                </div>
                
                <div class="form-group">
                    <label for="edit_email">Email:</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_contact_no">Contact No:</label>
                    <input type="text" id="edit_contact_no" name="contact_no" required>
                </div>

                <div class="form-group">
                    <label for="edit_password">New Password (leave blank to keep current):</label>
                    <input type="password" id="edit_password" name="password">
                </div>

                <div class="form-group">
                    <label>Created At:</label>
                    <input type="text" id="edit_created_at" readonly class="timestamp">
                </div>

                <div class="form-group">
                    <label>Updated At:</label>
                    <input type="text" id="edit_updated_at" readonly class="timestamp">
                </div>

                <button type="submit" class="edit-btn">Update</button>
                <button type="button" class="delete-btn" onclick="closeEditModal()">Cancel</button>
            </form>
        </div>
    </div>

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

        function openEditModal(advisor) {
            document.getElementById('editModal').style.display = 'block';
            document.getElementById('edit_id').value = advisor.id;
            document.getElementById('edit_advisor_id_number').value = advisor.advisor_id;
            document.getElementById('edit_last_name').value = advisor.last_name;
            document.getElementById('edit_first_name').value = advisor.first_name;
            document.getElementById('edit_middle_initial').value = advisor.middle_initial;
            document.getElementById('edit_suffix').value = advisor.suffix;
            document.getElementById('edit_email').value = advisor.email;
            document.getElementById('edit_contact_no').value = advisor.contact_no;
            document.getElementById('edit_created_at').value = advisor.created_at;
            document.getElementById('edit_updated_at').value = advisor.updated_at;
            // Password field is left empty by default
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

        // Confirmation using SweetAlert
        function confirmDelete(type, id) {
            let title, text, confirmButtonText;
            
            if (type === 'advisor') {
                title = 'Delete Advisor';
                text = 'Are you sure you want to delete this advisor? This action cannot be undone.';
                confirmButtonText = 'Yes, delete it!';
            } else if (type === 'assignment') {
                title = 'Remove Assignment';
                text = 'Are you sure you want to remove this advisor assignment? This action cannot be undone.';
                confirmButtonText = 'Yes, remove it!';
            }
            
            Swal.fire({
                title: title,
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f44336',
                cancelButtonColor: '#aaa',
                confirmButtonText: confirmButtonText,
            }).then((result) => {
                if (result.isConfirmed) {
                    if (type === 'advisor') {
                        document.getElementById('delete_advisor_id').value = id;
                        document.getElementById('delete_advisor_form').submit();
                    } else if (type === 'assignment') {
                        document.getElementById('delete_assignment_id').value = id;
                        document.getElementById('delete_assignment_form').submit();
                    }
                }
            });
        }
        
        // Form submission with SweetAlert confirmation
        document.getElementById('editForm').onsubmit = function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Update Advisor',
                text: 'Are you sure you want to update this advisor information?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4CAF50',
                cancelButtonColor: '#aaa',
                confirmButtonText: 'Yes, update it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        };
    </script>
    <script src="js/script.js"></script>
</body>
</html>
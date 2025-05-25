<?php
    include 'config.php';
    session_start();

    // Process concern deletion if requested
    if(isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $id = $_GET['id'];
        
        // Prepare and execute deletion query
        $stmt = $conn->prepare("DELETE FROM student_concerns WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        $stmt->execute();
        
        // Just reload the page (no redirection to different page)
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    // Process AJAX request for concern details
    if(isset($_POST['action']) && $_POST['action'] == 'get_concern' && isset($_POST['id'])) {
        $id = $_POST['id'];
        
        // Prepare and execute query with join to get advisor information
        $stmt = $conn->prepare("
            SELECT sc.id, sc.name, sc.student_number, sc.course_and_year, 
                   sc.title, sc.additional_info, sc.attachment,
                   CONCAT(ma.first_name, ' ', ma.last_name) AS advisor_name
            FROM student_concerns sc
            LEFT JOIN m_advisors ma ON sc.advisor_id = ma.id
            WHERE sc.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $concern = $result->fetch_assoc();
            
            // Return concern data as JSON
            echo json_encode($concern);
            exit();
        } else {
            echo json_encode(['error' => 'Concern not found']);
            exit();
        }
    }

    // Query to fetch concerns with advisor information
    $query = "
        SELECT sc.id, sc.name, sc.student_number, sc.course_and_year, sc.title,
               CONCAT(ma.first_name, ' ', ma.last_name) AS advisor_name
        FROM student_concerns sc
        LEFT JOIN m_advisors ma ON sc.advisor_id = ma.id
        ORDER BY sc.id DESC
    ";
    
    // Execute the query
    $result = mysqli_query($conn, $query);
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
        .btn-view {
            background-color: #4CAF50;
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
        
        .btn-view:hover {
            background-color: #4aa629;
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

        .concern-details {
            margin-top: 20px;
        }

        .concern-details h4 {
            margin-bottom: 5px;
            color: #333;
        }

        .concern-details p {
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .attachment-section {
            margin-top: 20px;
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 5px;
        }
        
        .attachment-link {
            display: inline-block;
            margin-top: 10px;
            padding: 5px 15px;
            background-color: #3498db;
            color: white;
            border-radius: 3px;
            text-decoration: none;
        }
        
        .attachment-link:hover {
            background-color: #2980b9;
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
            <a href="#" class="active"><i class='bx bxs-message-rounded-detail icon'></i>Feedbacks<i class='bx bx-chevron-right icon-right'></i></a>
            <ul class="side-dropdown">
                <li><a href="studentnumberfeedback.php"><i class='bx bxs-id-card icon'></i>Student Number</a></li>
                <li><a href="websitefeedback.php"><i class='bx bx-globe icon'></i>Website</a></li>

            </ul>
        </li>
        <li>
            <a href="#"><i class='bx bxs-analyse icon'></i>Accounts<i class='bx bx-chevron-right icon-right'></i></a>
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

<section id="content">
    <nav>
        <i class='bx bx-menu toggle-sidebar'></i>
        <form id="searchForm" method="post" action="">
            <div class="form-group">
                <input type="text" name="search_query" id="search_query" placeholder="Search">
                <i class='bx bx-search icon' onclick="submitSearchForm()" style="cursor: pointer;"></i>
            </div>
        </form>

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
        <h1 class="title">Student Concerns</h1>
        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3>Internflo Student Concern Submissions</h3>
                </div>
                <div class="table-data">
                    <div class="order">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Student Number</th>
                                    <th>Course & Year</th>
                                    <th>Advisor</th>
                                    <th>Title</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if(mysqli_num_rows($result) > 0) {
                                    while($row = mysqli_fetch_assoc($result)) {
                                        $advisor = !empty($row['advisor_name']) ? $row['advisor_name'] : 'Not Assigned';
                                ?>
                                <tr>
                                    <td><?php echo $row['name']; ?></td>
                                    <td><?php echo $row['student_number']; ?></td>
                                    <td><?php echo $row['course_and_year']; ?></td>
                                    <td><?php echo $advisor; ?></td>
                                    <td><?php echo $row['title']; ?></td>
                                    <td class="action-button">
                                        <button class="btn-view" onclick="viewConcern(<?php echo $row['id']; ?>)">View</button>
                                        <button class="btn-delete" onclick="deleteConcern(<?php echo $row['id']; ?>)">Delete</button>
                                    </td>
                                </tr>
                                <?php
                                    }
                                } else {
                                    echo "<tr><td colspan='6' style='text-align: center;'>No student concerns found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</section>

<!-- Concern Modal -->
<div id="concernModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Student Concern Details</h2>
        <div id="concernDetails" class="concern-details">
            <!-- Concern details will be loaded here -->
        </div>
    </div>
</div>

<script src="js/script.js"></script>
<script>
    function submitSearchForm() {
        document.getElementById("searchForm").submit();
    }

    // Function to view concern details
    function viewConcern(id) {
        // AJAX request to get concern details
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "<?php echo $_SERVER['PHP_SELF']; ?>", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (this.readyState === 4 && this.status === 200) {
                try {
                    var response = JSON.parse(this.responseText);
                    displayConcernDetails(response);
                } catch (e) {
                    console.error("Error parsing JSON response:", e);
                }
            }
        };
        xhr.send("action=get_concern&id=" + id);
    }

    // Function to display concern details in the modal
    function displayConcernDetails(data) {
        var advisor = data.advisor_name ? data.advisor_name : 'Not Assigned';
        var attachmentSection = '';
        
        if (data.attachment && data.attachment.trim() !== '') {
            attachmentSection = `
                <div class="attachment-section">
                    <h4>Registration Document:</h4>
                    <a href="../STUDENT/${data.attachment}" class="attachment-link" target="_blank">View Document</a>
                </div>
            `;
        }
        
        var details = `
            <h4>Student:</h4>
            <p>${data.name}</p>
            <h4>Student Number:</h4>
            <p>${data.student_number}</p>
            <h4>Course & Year:</h4>
            <p>${data.course_and_year}</p>
            <h4>Advisor:</h4>
            <p>${advisor}</p>
            <h4>Concern/Title:</h4>
            <p>${data.title}</p>
            <h4>Additional Information:</h4>
            <p>${data.additional_info}</p>
            ${attachmentSection}
        `;
        
        document.getElementById("concernDetails").innerHTML = details;
        document.getElementById("concernModal").style.display = "block";
    }

    // Function to close the modal
    function closeModal() {
        document.getElementById("concernModal").style.display = "none";
    }

    // Function to delete concern
    function deleteConcern(id) {
        if (confirm("Are you sure you want to delete this student concern?")) {
            window.location.href = "<?php echo $_SERVER['PHP_SELF']; ?>?action=delete&id=" + id;
        }
    }

    // Close the modal if clicked outside of it
    window.onclick = function(event) {
        var modal = document.getElementById("concernModal");
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
</script>

</body>
</html>
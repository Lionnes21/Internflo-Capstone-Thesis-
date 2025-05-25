<?php
    include 'config.php';
    session_start();

    // Handle delete request
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $applicantId = $_GET['id'];
        
        // Delete the applicant
        $query = "DELETE FROM hired_applicants WHERE application_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $applicantId);
        
        if (mysqli_stmt_execute($stmt)) {
            // Set success message
            $_SESSION['success_message'] = "Hired applicant removed successfully.";
        } else {
            // Set error message
            $_SESSION['error_message'] = "Error removing hired applicant: " . mysqli_error($conn);
        }
        
        mysqli_stmt_close($stmt);
        // Redirect to the same page to refresh
        header("Location: interns.php");
        exit();
    }

    // Handle AJAX request for intern details
    if (isset($_GET['action']) && $_GET['action'] == 'get_details' && isset($_GET['application_id']) && isset($_GET['student_id'])) {
        $applicationId = $_GET['application_id'];
        $studentId = $_GET['student_id'];
        
        // Get intern details
        $internQuery = "SELECT ha.first_name, ha.last_name, ia.internship_title, ar.company_name 
                       FROM hired_applicants ha 
                       INNER JOIN internshipad ia ON ha.internshipad_id = ia.internship_id 
                       INNER JOIN approvedrecruiters ar ON ia.user_id = ar.id 
                       WHERE ha.application_id = ?";
        
        $stmt = mysqli_prepare($conn, $internQuery);
        mysqli_stmt_bind_param($stmt, "i", $applicationId);
        mysqli_stmt_execute($stmt);
        $internResult = mysqli_stmt_get_result($stmt);
        $internData = mysqli_fetch_assoc($internResult);
        
        // Get progress data from m_weekly_reports
        $progressQuery = "SELECT mwr.week, mwr.status 
        FROM m_weekly_reports mwr 
        INNER JOIN students s ON mwr.student_id = s.student_id 
        WHERE s.id = ? 
        ORDER BY mwr.week ASC";
        
        $stmt = mysqli_prepare($conn, $progressQuery);
        mysqli_stmt_bind_param($stmt, "s", $studentId);
        mysqli_stmt_execute($stmt);
        $progressResult = mysqli_stmt_get_result($stmt);
        
        $progressData = [];
        while ($progressRow = mysqli_fetch_assoc($progressResult)) {
            $progressData[] = $progressRow;
        }
        
        // Prepare the response
        $response = [
            'intern' => $internData,
            'progress' => $progressData
        ];
        
        // Return the data as JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    // Query to fetch hired applicants with related internship and company information
    $query = "SELECT ha.application_id, ha.first_name, ha.last_name, ha.student_id, ia.internship_title, ar.company_name 
              FROM hired_applicants ha 
              INNER JOIN internshipad ia ON ha.internshipad_id = ia.internship_id 
              INNER JOIN approvedrecruiters ar ON ia.user_id = ar.id";
    
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

        /* Modal Styles */
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
            margin: 10% auto;
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
        
        .modal-header {
            padding-bottom: 10px;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .modal-title {
            margin: 0;
            color: #333;
        }
        
        .intern-details {
            margin-bottom: 20px;
        }
        
        .intern-details p {
            margin: 10px 0;
            font-size: 16px;
        }
        
        .progress-section {
            margin-top: 20px;
        }
        
        .progress-section h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .progress-bar {
            background-color: #f0f0f0;
            border-radius: 5px;
            height: 25px;
            margin-bottom: 5px;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 5px;
            background-color: #4CAF50;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .week-label {
            font-weight: bold;
            margin-top: 15px;
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
        <li><a href="interns.php" class="active"><i class='bx bxs-graduation icon'></i>Interns</a></li>
        <li>
            <a href="#"><i class='bx bxs-analyse icon'></i>Affiliates<i class='bx bx-chevron-right icon-right'></i></a>
            <ul class="side-dropdown">
                <li><a href="partnership.php"><i class='bx bxs-briefcase icon'></i>Partnership</a></li>
                <li><a href="requests.php"><i class='bx bxs-envelope icon'></i>Requests</a></li>
            </ul>
        </li>
        <li>
            <a href="#"><i class='bx bxs-message-rounded-detail icon'></i>Feedbacks<i class='bx bx-chevron-right icon-right'></i></a>
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
        <h1 class="title">Hired Interns</h1>
        <?php if(isset($_SESSION['success_message'])): ?>
            <div class="alert success">
                <?php 
                echo $_SESSION['success_message']; 
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error_message'])): ?>
            <div class="alert error">
                <?php 
                echo $_SESSION['error_message']; 
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3>Internflo Hired Interns</h3>
                </div>
                <div class="table-data">
                    <div class="order">
                        <table>
                            <thead>
                                <tr>
                                    <th>Company</th>
                                    <th>Name</th>
                                    <th>Internship</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if(mysqli_num_rows($result) > 0) {
                                    while($row = mysqli_fetch_assoc($result)) {
                                ?>
                                <tr>
                                    <td><?php echo $row['company_name']; ?></td>
                                    <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                                    <td><?php echo $row['internship_title']; ?></td>
                                    <td class="action-button">
                                        <button class="btn-view" onclick="viewDetails(<?php echo $row['application_id']; ?>, '<?php echo $row['student_id']; ?>')">View</button>
                                        <button class="btn-delete" onclick="deleteApplicant(<?php echo $row['application_id']; ?>)">Delete</button>
                                    </td>
                                </tr>
                                <?php
                                    }
                                } else {
                                    echo "<tr><td colspan='4' style='text-align: center;'>No hired applicants found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div id="forwardFormContainer"></div>
            </div>
        </div>
    </main>
</section>

<!-- Modal Dialog for Viewing Intern Details -->
<div id="internModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close">&times;</span>
            <h2 class="modal-title">Intern Details</h2>
        </div>
        <div class="intern-details" id="internDetails">
            <!-- Intern details will be loaded here -->
        </div>
        <div class="progress-section">
            <h3>Internship Progress</h3>
            <div id="progressContainer">
                <!-- Progress details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script src="js/script.js"></script>
<script>
    // Get the modal
    var modal = document.getElementById("internModal");
    
    // Get the <span> element that closes the modal
    var span = document.getElementsByClassName("close")[0];
    
    // Function to view intern details
    function viewDetails(applicationId, studentId) {
        // Show the modal
        modal.style.display = "block";
        
        // Use AJAX to fetch the intern details
        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                // Parse the JSON response
                var response = JSON.parse(this.responseText);
                
                // Update the modal content with intern details
                document.getElementById("internDetails").innerHTML = `
                    <p><strong>NAME:</strong> ${response.intern.first_name} ${response.intern.last_name}</p>
                    <p><strong>COMPANY NAME:</strong> ${response.intern.company_name}</p>
                    <p><strong>INTERNSHIP:</strong> ${response.intern.internship_title}</p>
                `;
                
// Update the progress container
var progressHtml = '';
if (response.progress.length > 0) {
    response.progress.forEach(function(week) {
        // Determine the color based on status
        let statusColor = '#156828'; // Default green text color
        let statusBgColor = '#e8f5e9'; // Default green background
        
        if (week.status === 'Pending Review') {
            statusColor = '#856404'; // Yellow text color
            statusBgColor = '#fff3cd'; // Yellow background
        } else if (week.status === 'Review') {
            statusColor = '#156828'; // Green text color
            statusBgColor = '#e8f5e9'; // Green background
        }
        
        progressHtml += `
            <div style="display: inline-block; padding: 10px 15px; background-color: #e8f5e9; border-radius: 8px; text-align: center;">
                <div style="color: #156828; font-size: 16px; font-weight: 600;">WEEK ${week.week}</div>
            </div>
            <div style="display: inline-block; padding: 10px 15px; background-color: ${statusBgColor}; border-radius: 8px; text-align: center;">
                <div style="color: ${statusColor}; font-size: 16px; font-weight: 600;">${week.status || 'PENDING'}</div>
            </div>
        `;
    });
} else {
    progressHtml = '<p>No progress data available.</p>';
}
document.getElementById("progressContainer").innerHTML = progressHtml;
            }
        };
        xhr.open("GET", "interns.php?action=get_details&application_id=" + applicationId + "&student_id=" + studentId, true);
        xhr.send();
    }
    
    // Function to delete an applicant
    function deleteApplicant(applicationId) {
        if (confirm("Are you sure you want to delete this hired applicant?")) {
            window.location.href = "interns.php?action=delete&id=" + applicationId;
        }
    }
    
    // Close the modal when the user clicks on <span> (x)
    span.onclick = function() {
        modal.style.display = "none";
    }
    
    // Close the modal when the user clicks anywhere outside of it
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
    
    function submitSearchForm() {
        document.getElementById("searchForm").submit();
    }
</script>

</body>
</html>
<?php
    include 'config.php';
    session_start();

    // Process company deletion if requested
    if(isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $id = $_GET['id'];
        
        // Prepare and execute deletion query
        $stmt = $conn->prepare("DELETE FROM approvedrecruiters WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        $stmt->execute();
        
        // Just reload the page
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    // Query to fetch company accounts from approvedrecruiters table
    $query = "SELECT id, company_name, first_name, last_name, industry FROM approvedrecruiters";
    
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
        <h1 class="title">Company Accounts</h1>
        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3>Internflo Company Accounts</h3>
                </div>
                <div class="table-data">
                    <div class="order">
                        <table>
                            <thead>
                                <tr>
                                    <th>Company</th>
                                    <th>Representative</th>
                                    <th>General Industry</th>
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
                                    <td><?php echo $row['industry']; ?></td>
                                    <td class="action-button">
                                        <button class="btn-delete" onclick="deleteCompany(<?php echo $row['id']; ?>)">Delete</button>
                                    </td>
                                </tr>
                                <?php
                                    }
                                } else {
                                    echo "<tr><td colspan='4' style='text-align: center;'>No company accounts found</td></tr>";
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

<script src="js/script.js"></script>
<script>
    function submitSearchForm() {
        document.getElementById("searchForm").submit();
    }
    
    // Function to delete company
    function deleteCompany(id) {
        if (confirm("Are you sure you want to delete this company account?")) {
            window.location.href = "<?php echo $_SERVER['PHP_SELF']; ?>?action=delete&id=" + id;
        }
    }
</script>

</body>
</html>
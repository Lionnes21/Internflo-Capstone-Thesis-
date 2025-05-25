<?php
    include 'config.php';
    session_start();

    // Process feedback deletion if requested
    if(isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $id = $_GET['id'];
        
        // Prepare and execute deletion query
        $stmt = $conn->prepare("DELETE FROM feedback WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        $stmt->execute();
        
        // Just reload the page (no redirection to different page)
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    // Process AJAX request for feedback details
    if(isset($_POST['action']) && $_POST['action'] == 'get_feedback' && isset($_POST['id'])) {
        $id = $_POST['id'];
        
        // Prepare and execute query
        $stmt = $conn->prepare("SELECT id, name, email, title, message FROM feedback WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $feedback = $result->fetch_assoc();
            
            // Return feedback data as JSON
            echo json_encode($feedback);
            exit();
        } else {
            echo json_encode(['error' => 'Feedback not found']);
            exit();
        }
    }

    // Query to fetch feedback from feedback table
    $query = "SELECT id, name, email, title, message FROM feedback";
    
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

        .feedback-details {
            margin-top: 20px;
        }

        .feedback-details h4 {
            margin-bottom: 5px;
            color: #333;
        }

        .feedback-details p {
            margin-bottom: 15px;
            line-height: 1.5;
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
        <h1 class="title">Feedback List</h1>
        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3>Internflo User Feedback</h3>
                </div>
                <div class="table-data">
                    <div class="order">
                        <table>
                            <thead>
                                <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Concern</th>
                                        <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if(mysqli_num_rows($result) > 0) {
                                    while($row = mysqli_fetch_assoc($result)) {
                                        // Display "Anonymous" if name is null or empty
                                        $displayName = !empty($row['name']) ? $row['name'] : 'Anonymous';
                                ?>
                                <tr>
                                    <td><?php echo $displayName; ?></td>
                                    <td><?php echo $row['email']; ?></td>
                                    <td><?php echo $row['title']; ?></td>
                                    <td class="action-button">
                                        <button class="btn-view" onclick="viewFeedback(<?php echo $row['id']; ?>)">View</button>
                                        <button class="btn-delete" onclick="deleteFeedback(<?php echo $row['id']; ?>)">Delete</button>
                                    </td>
                                </tr>
                                <?php
                                    }
                                } else {
                                    echo "<tr><td colspan='4' style='text-align: center;'>No feedback found</td></tr>";
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

<!-- Feedback Modal -->
<div id="feedbackModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Feedback Details</h2>
        <div id="feedbackDetails" class="feedback-details">
            <!-- Feedback details will be loaded here -->
        </div>
    </div>
</div>

<script src="js/script.js"></script>
<script>
    function submitSearchForm() {
        document.getElementById("searchForm").submit();
    }

    // Function to view feedback details
    function viewFeedback(id) {
        // AJAX request to get feedback details
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "<?php echo $_SERVER['PHP_SELF']; ?>", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (this.readyState === 4 && this.status === 200) {
                try {
                    var response = JSON.parse(this.responseText);
                    displayFeedbackDetails(response);
                } catch (e) {
                    console.error("Error parsing JSON response:", e);
                }
            }
        };
        xhr.send("action=get_feedback&id=" + id);
    }

    // Function to display feedback details in the modal
    function displayFeedbackDetails(data) {
        var displayName = data.name ? data.name : 'Anonymous';
        var details = `
            <h4>Name:</h4>
            <p>${displayName}</p>
            <h4>Email:</h4>
            <p>${data.email}</p>
            <h4>Concern:</h4>
            <p>${data.title}</p>
            <h4>Message:</h4>
            <p>${data.message}</p>
        `;
        document.getElementById("feedbackDetails").innerHTML = details;
        document.getElementById("feedbackModal").style.display = "block";
    }

    // Function to close the modal
    function closeModal() {
        document.getElementById("feedbackModal").style.display = "none";
    }

    // Function to delete feedback
    function deleteFeedback(id) {
        if (confirm("Are you sure you want to delete this feedback?")) {
            window.location.href = "<?php echo $_SERVER['PHP_SELF']; ?>?action=delete&id=" + id;
        }
    }

    // Close the modal if clicked outside of it
    window.onclick = function(event) {
        var modal = document.getElementById("feedbackModal");
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
</script>

</body>
</html>
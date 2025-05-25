<?php
    include 'config.php';
    session_start();

    // Clear any existing output and set JSON header for AJAX requests
    if (isset($_POST['approve_id']) || isset($_POST['decline_id']) || isset($_GET['id'])) {
        ob_clean();
        header('Content-Type: application/json');

    // Handle decline request
    if (isset($_POST['decline_id'])) {
        $id = intval($_POST['decline_id']);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid recruiter ID']);
            exit;
        }
        
        try {
            // Delete from recruiters table
            $delete_query = "DELETE FROM recruiters WHERE id = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Recruiter request declined and deleted successfully'
                ]);
            } else {
                throw new Exception('Failed to delete recruiter');
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }

    // Handle approval request
    if (isset($_POST['approve_id'])) {
        $id = intval($_POST['approve_id']);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid recruiter ID']);
            exit;
        }

        // Start transaction
        $conn->begin_transaction();

        try {
            // First, get all the recruiter data
            $select_query = "SELECT * FROM recruiters WHERE id = ?";
            $stmt = $conn->prepare($select_query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Recruiter not found');
            }
            
            $recruiter = $result->fetch_assoc();
            
            // Insert into approved recruiters
            $insert_query = "INSERT INTO approvedrecruiters (
                email, mobile_number, password, created_at, 
                first_name, middle_name, last_name, suffix,
                company_name, industry, company_phone, company_email,
                company_overview, company_address, latitude, longitude,
                linkedin_url, certificate_of_registration, bir_registration,
                business_permit, verification_token, email_verified, company_logo
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param(
                "sssssssssssssssssssssss",
                $recruiter['email'],
                $recruiter['mobile_number'],
                $recruiter['password'],
                $recruiter['created_at'],
                $recruiter['first_name'],
                $recruiter['middle_name'],
                $recruiter['last_name'],
                $recruiter['suffix'],
                $recruiter['company_name'],
                $recruiter['industry'],
                $recruiter['company_phone'],
                $recruiter['company_email'],
                $recruiter['company_overview'],
                $recruiter['company_address'],
                $recruiter['latitude'],
                $recruiter['longitude'],
                $recruiter['linkedin_url'],
                $recruiter['certificate_of_registration'],
                $recruiter['bir_registration'],
                $recruiter['business_permit'],
                $recruiter['verification_token'],
                $recruiter['email_verified'],
                $recruiter['company_logo']
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to insert into approvedrecruiters');
            }
            
            // Delete from recruiters table
            $delete_query = "DELETE FROM recruiters WHERE id = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("i", $id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to delete from recruiters');
            }
            
            // Commit transaction
            $conn->commit();
            
            // Send success response
            echo json_encode([
                'success' => true,
                'message' => 'Recruiter approved successfully'
            ]);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }

    // Handle AJAX request for recruiter details
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $query = "SELECT * FROM recruiters WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $recruiter = $result->fetch_assoc();
            echo json_encode($recruiter);
        } else {
            echo json_encode(['error' => 'Recruiter not found']);
        }
        exit();
    }
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

</head>
<body>
<section id="sidebar">
    <div class="logo">
        <img src="ucc.png" class="logo-full" alt="UCC Full Logo">
        <img src="ucc-logo1.png" class="logo-icon" alt="UCC Icon">
    </div>
    <ul class="side-menu">
        <li><a href="admin_index.php" ><i class='bx bxs-dashboard icon'></i>Dashboard</a></li>
        <li><a href="studentList.php"><i class='bx bxs-user-detail icon'></i>Students</a></li>
        <li><a href="feedbacks.php" class="active"><i class='bx bxs-message-rounded-detail icon'></i>Feedbacks</a></li>
        <li>
            <a href="#"><i class='bx bxs-analyse icon'></i>Analytics<i class='bx bx-chevron-right icon-right'></i></a>
            <ul class="side-dropdown">
                <li><a href="#"><i class='bx bxs-map icon'></i>Interns</a></li>
                <li><a href="internshipeval.php"><i class='bx bx-run icon'></i>Companies</a></li>
            </ul>
        </li>
        <li><a href="#"><i class='bx bxs-map icon'></i>Interns</a></li>
        <li><a href="internshipeval.php"><i class='bx bx-run icon'></i>Companies</a></li>
        <li>
            <a href="#"><i class='bx bxs-user-account icon'></i>Adviser Account<i class='bx bx-chevron-right icon-right'></i></a>
                <ul class="side-dropdown">
                        <li><a href="create_account.php"><i class='bx bxs-user-detail icon'></i>Create Advisor</a></li> 
                        <li><a href="assignAdvisor.php"><i class='bx bxs-book-add icon'></i>Assign Adviser</a></li>
                        <li><a href="advisorList.php"><i class='bx bxs-user-detail icon'></i>List of Adviser</a></li>  
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
            <h1 class="title">Recruiter Registration Requests</h1>
            <div class="data">
                <div class="content-data">
                    <div class="head">
                        <h3>Internflo Recruiter Requests</h3>
                    </div>
                    <div class="table-data">
                        <div class="order">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Company</th>
                                        <th>Name</th>
                                        <th>Industry</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div id="forwardFormContainer"></div>
                </div>
            </div>
        </main>
    </section>

    <div id="recruiterModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div id="modalContent"></div>
        </div>
    </div>

<script src="js/script.js"></script>

</body>
</html>
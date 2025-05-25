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
            
            // Delete related records from messaging_agreement tables
            $delete_messaging_agreement = "DELETE FROM messaging_agreement WHERE recruiter_email = ?";
            $stmt = $conn->prepare($delete_messaging_agreement);
            $stmt->bind_param("s", $recruiter['email']);
            $stmt->execute();
            
            $delete_messaging_agreement_company = "DELETE FROM messaging_agreement_company WHERE recruiter_email = ?";
            $stmt = $conn->prepare($delete_messaging_agreement_company);
            $stmt->bind_param("s", $recruiter['email']);
            $stmt->execute();
            
            $delete_messaging_agreement_admin = "DELETE FROM messaging_agreement_admin WHERE recruiter_email = ?";
            $stmt = $conn->prepare($delete_messaging_agreement_admin);
            $stmt->bind_param("s", $recruiter['email']);
            $stmt->execute();
            
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

    function getAdvisors($conn) {
        // Join the tables to get both name and email
        $sql = "SELECT a.advisor_id as id, a.full_name, b.email 
                FROM m_advisor_assignments a
                INNER JOIN m_advisors b ON a.advisor_id = b.id";
        
        $result = mysqli_query($conn, $sql);
        
        if (!$result) {
            // If query fails, return empty array and log error
            error_log("Database query failed: " . mysqli_error($conn));
            return array();
        }
        
        $advisors = array();
        if (mysqli_num_rows($result) > 0) {
            while($row = mysqli_fetch_assoc($result)) {
                $advisors[] = $row;
            }
        }
        
        return $advisors;
    }

    // Get advisors for use in page
    $advisors = getAdvisors($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel="stylesheet">
    <link rel="stylesheet" href="css/admin_styles.css">
    <link rel="stylesheet" href="requests.css">
    <title>Internflo - Administrator</title>
    <link rel="icon" href="ucc-logo1.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.2/mammoth.browser.min.js"></script>

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
            <a href="#" class="active"><i class='bx bxs-analyse icon' ></i>Affiliates<i class='bx bx-chevron-right icon-right'></i></a>
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
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_query'])) {
                                        $search = $_POST['search_query'];
                                        $searchQuery = "SELECT r.*, 
                                                    (SELECT COUNT(*) FROM messaging_agreement ma WHERE ma.recruiter_email = r.email) as has_agreement,
                                                    (SELECT COUNT(*) FROM messaging_agreement_company mac WHERE mac.recruiter_email = r.email) as has_company_agreement,
                                                    (SELECT COUNT(*) FROM messaging_agreement_admin maa WHERE maa.recruiter_email = r.email) as has_admin_agreement 
                                                    FROM recruiters r WHERE 
                                                    r.company_name LIKE ? OR 
                                                    CONCAT(r.first_name, ' ', r.middle_name, ' ', r.last_name) LIKE ? OR 
                                                    r.industry LIKE ?";
                                        $stmt = $conn->prepare($searchQuery);
                                        $searchParam = "%$search%";
                                        $stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                    } else {
                                        $result = $conn->query("SELECT r.*, 
                                                            (SELECT COUNT(*) FROM messaging_agreement ma WHERE ma.recruiter_email = r.email) as has_agreement,
                                                            (SELECT COUNT(*) FROM messaging_agreement_company mac WHERE mac.recruiter_email = r.email) as has_company_agreement,
                                                            (SELECT COUNT(*) FROM messaging_agreement_admin maa WHERE maa.recruiter_email = r.email) as has_admin_agreement 
                                                            FROM recruiters r");
                                    }

                                    if ($result) {
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                $fullName = htmlspecialchars($row['first_name'] . ' ' . 
                                                        ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . 
                                                        $row['last_name'] . 
                                                        ($row['suffix'] ? ' ' . $row['suffix'] : ''));
                                                
                                                // Priority check with messaging_agreement_admin as highest priority
                                                if ($row['has_admin_agreement'] > 0) {
                                                    $status = 'AFFILIATION AGREEMENT COMPLETED';
                                                    $statusClass = 'status-confirmed-by-company'; // New green class
                                                    $showForwardButton = false; // Don't show forward button
                                                }
                                                // Check messaging_agreement_company
                                                else if ($row['has_company_agreement'] > 0) {
                                                    $status = 'REVIEWED BY ADVISOR';
                                                    $statusClass = 'status-reviewed-by-advisor';
                                                    $showForwardButton = false; // Don't show forward button
                                                } 
                                                // Check messaging_agreement
                                                else if ($row['has_agreement'] > 0) {
                                                    $status = 'TO BE REVIEWED';
                                                    $statusClass = 'status-to-be-reviewed';
                                                    $showForwardButton = false; // Don't show forward button
                                                } 
                                                // Default status
                                                else {
                                                    $status = 'PENDING APPROVAL';
                                                    $statusClass = 'status-not-approved';
                                                    
                                                    // Check if the recruiter's email already exists in messaging_agreement for the company
                                                    $checkExistingQuery = "SELECT COUNT(*) as count FROM messaging_agreement 
                                                                        WHERE recruiter_email = ?";
                                                    $checkStmt = $conn->prepare($checkExistingQuery);
                                                    $checkStmt->bind_param("s", $row['email']);
                                                    $checkStmt->execute();
                                                    $checkResult = $checkStmt->get_result();
                                                    $checkRow = $checkResult->fetch_assoc();
                                                    
                                                    if ($checkRow['count'] > 0) {
                                                        $showForwardButton = false; // Don't show forward button if already exists in messaging_agreement
                                                    } else {
                                                        $showForwardButton = true; // Show forward button only if not exists in messaging_agreement
                                                    }
                                                    $checkStmt->close();
                                                }
                                                
                                                echo '<tr>
                                                    <td>' . htmlspecialchars($row['company_name']) . '</td>
                                                    <td>' . $fullName . '</td>
                                                    <td>' . htmlspecialchars($row['industry']) . '</td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <button class="view-btn" onclick="viewRecruiter(' . $row['id'] . ')">View</button>';
                                                
                                                // Only show forward button if status is PENDING APPROVAL and no record in messaging_agreement
                                                if ($showForwardButton) {
                                                    echo '<button class="forward-btn" onclick="showForwardForm(' . $row['id'] . ')">Forward</button>';
                                                }
                                                
                                                echo '</div>
                                                    </td>
                                                    <td>
                                                        <div class="status-badge ' . $statusClass . '">' . $status . '</div>
                                                    </td>
                                                    </tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="5"><p class="not-found">No recruiter requests found</p></td></tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="5"><p class="error">Error executing query</p></td></tr>';
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

    <!-- Modal for Recruiter Details -->
    <div id="recruiterModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeRecruiterModal()">&times;</span>
            <div id="recruiterModalContent">
                <!-- Recruiter Details Content -->
                <div class="company-logo" style="text-align: center;">
                    <img class="circular-logo" id="recruiterLogo" src="" alt="Company Logo" style="max-width: 100px;">
                </div>
                <h1 class="company-name" id="recruiterCompanyName" style="text-align: center;"></h1>
                <p class="company-details" id="recruiterIndustry" style="text-align: center;"></p>
                <p class="company-details" id="recruiterAddress" style="text-align: center;"></p>
                <p class="company-details" id="recruiterPhone" style="text-align: center;"></p>
                <p class="company-details" id="recruiterLink" style="text-align: center;"></p>
                <p class="company-details" id="recruiterOverview" style="text-align: center;"></p>
                
                <h2 class="company-title">Recruiter Details</h2>
                <p><strong>Full Name:</strong> <span id="recruiterFullName"></span></p>
                <p><strong>Email:</strong> <span id="recruiterEmail"></span></p>
                <p><strong>Mobile Number:</strong> <span id="recruiterMobile"></span></p>
                <p><strong>Documents:</strong></p>
                <ul>
                    <li>Certificate of Registration: <a id="recruiterCertReg" href="" target="_blank">View</a></li>
                    <li>BIR Registration: <a id="recruiterBIR" href="" target="_blank">View</a></li>
                    <li>Business Permit: <a id="recruiterBusinessPermit" href="" target="_blank">View</a></li>
                </ul>
                <div style="text-align: center; margin-top: 20px;">
                    <button onclick="approveRecruiter()" style="background-color: #4CAF50; color: white; padding: 8px 15px 10px 15px; margin-right: 5px; border: none; border-radius: 5px; cursor: pointer;">Approve</button>
                    <button onclick="declineRecruiter()" style="background-color: #f44336; color: white; padding: 8px 15px 10px 15px; border: none; border-radius: 5px; cursor: pointer;">Decline</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Forward Form -->
    <!-- Modal for Forward Form -->
    <div id="forwardModal" class="modal">
        <div class="modal-content2">
            <span class="close" onclick="closeForwardModal()">&times;</span>
            <div id="forwardModalContent">
                <!-- Forward Form -->
                <form id="forwardForm" class="forward-form">
                    <h2>Create Affiliate Agreement</h2>
                    
                    <div style="display: none;">
                        <label for="companyName">Company Name</label>
                        <input type="text" id="companyName" name="companyName" readonly>
                    </div>
                    
                    <div style="display: none;">
                        <label for="industry">Industry</label>
                        <input type="text" id="industry" name="industry" readonly>
                    </div>

                    <div style="display: none;">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" readonly>
                    </div>

                    <div style="display: none;">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone" readonly>
                    </div style="display: none;">

                    <div style="display: none;">
                        <label for="recruiterEmail">Email</label>
                        <input type="email" id="companywebsite" name="companywebsite" readonly>
                    </div>

                    <div style="display: none;">
                        <label for="overview">Company Overview</label>
                        <textarea id="overview" name="overview" readonly></textarea>
                    </div>

                    <h2 style="display: none;">Recruiter Details</h2>

                    <div style="display: none;">
                        <label for="fullName">Full Name</label>
                        <input type="text" id="fullName" name="fullName" readonly>
                    </div>

                    <div style="display: none;">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" readonly>
                    </div>

                    <div style="display: none;">
                        <label for="mobileNumber">Mobile Number</label>
                        <input type="text" id="mobileNumber" name="mobileNumber" readonly>
                    </div>

                    <h2 style="display: none;">Documents</h2>
                    <div style="display: none;">
                        <label for="certReg">Certificate of Registration</label>
                        <input type="text" id="certReg" name="certReg" readonly>
                        <a id="certRegLink" href="#" target="_blank">View</a>
                    </div>

                    <div style="display: none;">
                        <label for="birReg">BIR Registration</label>
                        <input type="text" id="birReg" name="birReg" readonly>
                        <a id="birRegLink" href="#" target="_blank">View</a>
                    </div style="display: none;">

                    <div style="display: none;">
                        <label for="businessPermit">Business Permit</label>
                        <input type="text" id="businessPermit" name="businessPermit" readonly>
                        <a id="businessPermitLink" href="#" target="_blank">View</a>
                    </div>

                    <div>
                        <label for="agreementFile">Upload Agreement File Template:</label>
                        <input style="display: none;" type="text" id="agreementFile" name="agreementFile" readonly value="agreement/affiliate-agreements.docx">
                        <a href="agreement/affiliate-agreements.docx" target="_blank" class="document-link">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#666666">
                                <path d="M361.91-239.28h236.42q17.71 0 29.69-12.05T640-281.2q0-17.71-11.98-29.69t-29.69-11.98H361.67q-17.71 0-29.69 11.98Q320-298.9 320-281.19t12.05 29.81q12.05 12.1 29.86 12.1Zm0-160h236.42q17.71 0 29.69-12.05T640-441.2q0-17.71-11.98-29.69t-29.69-11.98H361.67q-17.71 0-29.69 11.98Q320-458.9 320-441.19t12.05 29.81q12.05 12.1 29.86 12.1ZM242.87-71.87q-37.78 0-64.39-26.61t-26.61-64.39v-634.26q0-37.78 26.61-64.39t64.39-26.61H525.8q18.22 0 34.72 6.84 16.5 6.83 29.18 19.51L781.78-669.7q12.68 12.68 19.51 29.18 6.84 16.5 6.84 34.72v442.93q0 37.78-26.61 64.39t-64.39 26.61H242.87Zm274.26-570.76v-154.5H242.87v634.26h474.26v-434.26h-154.5q-19.15 0-32.33-13.17-13.17-13.18-13.17-32.33Zm-274.26-154.5V-597.13v-200 634.26-634.26Z"/>
                            </svg>
                            Company Affiliate Agreement
                        </a>
                    </div>

                    <h2>Review company</h2>
                    <div>
                        <label for="practicumCoordinator">Practicum Coordinator:</label>
                        <select id="practicumCoordinator" name="practicumCoordinator" required>
                            <option value="">Select Practicum Coordinator</option>
                            <?php foreach ($advisors as $advisor): ?>
                                <option value="<?php echo $advisor['id']; ?>" data-email="<?php echo $advisor['email']; ?>">
                                    <?php echo $advisor['full_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: none;">
                        <label for="practicumCoordinatorEmail">Practicum Coordinator Email</label>
                        <input type="text" id="practicumCoordinatorEmail" name="practicumCoordinatorEmail" readonly>
                    </div>

         

                    <div style="text-align: center; margin: 10px 0;">
                        <button type="submit">Send</button>
                        <button type="button" onclick="closeForwardModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

 
    <script>
        // Function to handle the change event of the practicumCoordinator select
        document.getElementById('practicumCoordinator').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const selectedEmail = selectedOption.getAttribute('data-email');
            
            // Update the practicum coordinator email field instead of the recruiter email
            document.getElementById('practicumCoordinatorEmail').value = selectedEmail || '';
        });
        // Existing form submission and modal handling code
        document.getElementById('forwardForm').addEventListener('submit', function(event) {
            event.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('request_submit.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Agreement sent successfully');
                    closeForwardModal();
                } else {
                    alert('Failed to send agreement: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while sending the agreement');
            });
        });

            // Function to show the forward form modal
            function showForwardForm(id) {
                fetch(`requests.php?id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        // Company Details
                        document.getElementById('companyName').value = data.company_name;
                        document.getElementById('industry').value = data.industry;
                        document.getElementById('address').value = data.company_address;
                        document.getElementById('phone').value = data.company_phone;
                        document.getElementById('companywebsite').value = data.company_email;
                        document.getElementById('overview').value = data.company_overview;
                        
                        // Recruiter Details
                        const fullName = `${data.first_name} ${data.middle_name || ''} ${data.last_name} ${data.suffix || ''}`;
                        document.getElementById('fullName').value = fullName;
                        document.getElementById('email').value = data.email;
                        document.getElementById('mobileNumber').value = data.mobile_number;

                        // Document Details
                        document.getElementById('certReg').value = data.certificate_of_registration;
                        document.getElementById('certRegLink').href = `../RECRUITER/${data.certificate_of_registration}`;
                        
                        document.getElementById('birReg').value = data.bir_registration;
                        document.getElementById('birRegLink').href = `../RECRUITER/${data.bir_registration}`;
                        
                        document.getElementById('businessPermit').value = data.business_permit;
                        document.getElementById('businessPermitLink').href = `../RECRUITER/${data.business_permit}`;

                        document.getElementById('forwardModal').style.display = "block";
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error fetching recruiter details');
                    });
            }

            // Function to close the forward modal
            function closeForwardModal() {
                document.getElementById('forwardModal').style.display = "none";
            }
    </script>

    <script>
        // Function to view recruiter details
        function viewRecruiter(id) {
            fetch(`requests.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    // Store the recruiter ID in a data attribute for use in approve/decline functions
                    document.getElementById('recruiterModalContent').setAttribute('data-recruiter-id', data.id);
                    
                    // Populate the recruiter details into the modal
                    document.getElementById('recruiterLogo').src = `../RECRUITER/${data.company_logo}`;
                    document.getElementById('recruiterCompanyName').textContent = data.company_name;
                    document.getElementById('recruiterIndustry').textContent = data.industry;
                    document.getElementById('recruiterAddress').textContent = data.company_address;
                    document.getElementById('recruiterPhone').textContent = data.company_phone;
                    document.getElementById('recruiterLink').textContent = data.company_email;
                    document.getElementById('recruiterOverview').textContent = data.company_overview;

                    // Populate recruiter personal details
                    const fullName = `${data.first_name} ${data.middle_name || ''} ${data.last_name} ${data.suffix || ''}`;
                    document.getElementById('recruiterFullName').textContent = fullName;
                    document.getElementById('recruiterEmail').textContent = data.email;
                    document.getElementById('recruiterMobile').textContent = data.mobile_number;

                    // Populate document links
                    document.getElementById('recruiterCertReg').href = `../RECRUITER/${data.certificate_of_registration}`;
                    document.getElementById('recruiterBIR').href = `../RECRUITER/${data.bir_registration}`;
                    document.getElementById('recruiterBusinessPermit').href = `../RECRUITER/${data.business_permit}`;

                    // Show the recruiter modal
                    recruiterModal.style.display = "block";
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error fetching recruiter details');
                });
        }

        // Function to approve a recruiter
        function approveRecruiter() {
            const recruiterId = document.getElementById('recruiterModalContent').getAttribute('data-recruiter-id');
            
            if (!recruiterId) {
                alert('Error: Recruiter ID not found');
                return;
            }
            
            // Create form data to send
            const formData = new FormData();
            formData.append('approve_id', recruiterId);
            
            // Show loading indicator or disable button
            const approveButton = document.querySelector('button[onclick="approveRecruiter()"]');
            const originalText = approveButton.textContent;
            approveButton.disabled = true;
            approveButton.textContent = 'Processing...';
            
            // Send approval request
            fetch('requests.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message || 'Recruiter approved successfully');
                    closeRecruiterModal();
                    // Refresh the page to update the table
                    window.location.reload();
                } else {
                    alert(data.message || 'Error approving recruiter');
                    // Reset button
                    approveButton.disabled = false;
                    approveButton.textContent = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error processing request');
                // Reset button
                approveButton.disabled = false;
                approveButton.textContent = originalText;
            });
        }

        // Function to decline a recruiter
        function declineRecruiter() {
            const recruiterId = document.getElementById('recruiterModalContent').getAttribute('data-recruiter-id');
            
            if (!recruiterId) {
                alert('Error: Recruiter ID not found');
                return;
            }
            
            // Confirm before declining
            if (!confirm('Are you sure you want to decline this recruiter?')) {
                return;
            }
            
            // Create form data to send
            const formData = new FormData();
            formData.append('decline_id', recruiterId);
            
            // Show loading indicator or disable button
            const declineButton = document.querySelector('button[onclick="declineRecruiter()"]');
            const originalText = declineButton.textContent;
            declineButton.disabled = true;
            declineButton.textContent = 'Processing...';
            
            // Send decline request
            fetch('requests.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message || 'Recruiter declined successfully');
                    closeRecruiterModal();
                    // Refresh the page to update the table
                    window.location.reload();
                } else {
                    alert(data.message || 'Error declining recruiter');
                    // Reset button
                    declineButton.disabled = false;
                    declineButton.textContent = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error processing request');
                // Reset button
                declineButton.disabled = false;
                declineButton.textContent = originalText;
            });
        }

        // Function to close the recruiter modal
        function closeRecruiterModal() {
            recruiterModal.style.display = "none";
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target == recruiterModal) {
                closeRecruiterModal();
            }
        }
    </script>

    <script>
           document.addEventListener('DOMContentLoaded', function() {
        // Store advisor data
        const advisorData = <?php echo json_encode($advisors); ?>;
        const emailLookup = {};
        
        // Create email lookup object
        advisorData.forEach(advisor => {
            emailLookup[advisor.id] = advisor.email;
        });
        
        // Add event listener
        document.getElementById('practicumCoordinator').addEventListener('change', function() {
            const selectedId = this.value;
            const emailField = document.getElementById('practicumCoordinatorEmail');
            
            if (selectedId && emailLookup[selectedId]) {
                emailField.value = emailLookup[selectedId];
            } else {
                emailField.value = '';
            }
        });
    });
    </script>


<script src="js/script.js"></script>

</body>
</html>
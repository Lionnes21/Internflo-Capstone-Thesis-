<?php
    include 'config.php';
    session_start();

    // Delete functionality
    if(isset($_POST['delete_id'])) {
        $id = intval($_POST['delete_id']);
        $delete_query = "DELETE FROM approvedrecruiters WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $id);
        
        if($stmt->execute()) {
            $_SESSION['message'] = "Recruiter deleted successfully";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['message'] = "Error deleting recruiter";
            $_SESSION['msg_type'] = "error";
        }
        
        header("Location: approved_recruiters.php");
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
    <link rel="stylesheet" href="companyList.css">
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
        <h1 class="title">Registered Companies</h1>
        
        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert alert-<?=$_SESSION['msg_type']?>">
                <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']);
                    unset($_SESSION['msg_type']);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="data">
            <div class="content-data">
                
                <div class="company-list">
                    <?php
                    // Prepare the query based on search
                    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_query'])) {
                        $search = $_POST['search_query'];
                        $query = "SELECT id, company_logo, company_name, company_email, first_name, middle_name, last_name, suffix 
                                FROM approvedrecruiters 
                                WHERE company_name LIKE ? OR company_email LIKE ?
                                ORDER BY company_name ASC";
                        $stmt = $conn->prepare($query);
                        $searchParam = "%$search%";
                        $stmt->bind_param("ss", $searchParam, $searchParam);
                    } else {
                        $query = "SELECT id, company_logo, company_name, company_email, first_name, middle_name, last_name, suffix 
                                FROM approvedrecruiters 
                                ORDER BY company_name ASC";
                        $stmt = $conn->prepare($query);
                    }
                    
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        echo '<div class="company-grid">';
                        while ($row = $result->fetch_assoc()) {
                            $fullName = htmlspecialchars($row['first_name'] . ' ' . 
                                      ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . 
                                      $row['last_name'] . 
                                      ($row['suffix'] ? ' ' . $row['suffix'] : ''));
                                      
                            $logoPath = '../RECRUITER/' . $row['company_logo'];
                            if (!file_exists($logoPath) || empty($row['company_logo'])) {
                                $logoPath = 'default_company_logo.png'; // Default logo if not found
                            }
                            
                            echo '<div class="company-card">
                                    <div class="company-logo-container">
                                        <img src="' . htmlspecialchars($logoPath) . '" alt="' . htmlspecialchars($row['company_name']) . ' Logo" class="company-logo">
                                    </div>
                                    <div class="company-info">
                                        <div class="company-name">' . htmlspecialchars($row['company_name']) . '</div>
                                        <div class="company-email">' . htmlspecialchars($row['company_email']) . '</div>
                                        <div class="company-rep">Representative: ' . $fullName . '</div>
                                    </div>
                                    <div class="action-buttons">
                                        <button class="view-btn" onclick="viewCompany(' . $row['id'] . ')">View</button>
                                        <!--<button class="edit-btn" onclick="editCompany(' . $row['id'] . ')">Edit</button>-->
                                        <form method="post" action="" style="display: inline;">
                                            <button type="submit" name="delete_id" value="' . $row['id'] . '" class="delete-btn" 
                                            onclick="return confirm(\'Are you sure you want to delete this company?\');">Delete</button>
                                        </form>
                                    </div>
                                </div>';
                        }
                        echo '</div>';
                    } else {
                        echo '<p class="not-found">No approved companies found</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </main>
</section>

<!-- Company Details Modal -->
<div id="companyModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div id="modalContent"></div>
    </div>
</div>

<script src="js/script.js"></script>
<script>
// Get the modal
var modal = document.getElementById("companyModal");

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close")[0];

// When the user clicks on <span> (x), close the modal
span.onclick = function() {
    modal.style.display = "none";
}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

// Function to submit search form
function submitSearchForm() {
    document.getElementById("searchForm").submit();
}

// Function to view company details
function viewCompany(id) {
    fetch(`get_company_details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            let content = `
                <div class="modal-header">
                    <div class="company-logo">
                        <img class="circular-logo" src="../RECRUITER/${data.company_logo}" alt="${data.company_name} Logo">
                    </div>
                    <h1 class="company-name">${data.company_name}</h1>
                    <p class="company-industry">${data.industry}</p>
                </div>
                
                <div class="modal-body">
                    <div class="contact-info">
                        <h2>Contact Information</h2>
                        <p><i class='bx bx-map'></i> ${data.company_address}</p>
                        <p><i class='bx bx-phone'></i> ${data.company_phone}</p>
                        <p><i class='bx bx-envelope'></i> ${data.company_email}</p>
                    </div>

                    <div class="company-overview">
                        <h2>Company Overview</h2>
                        <p>${data.company_overview}</p>
                    </div>

                    <div class="recruiter-details">
                        <h2>Recruiter Details</h2>
                        <p><strong>Name:</strong> ${data.first_name} ${data.middle_name || ''} ${data.last_name} ${data.suffix || ''}</p>
                        <p><strong>Email:</strong> ${data.email}</p>
                        <p><strong>Mobile:</strong> ${data.mobile_number}</p>
                    </div>

                    <div class="documents">
                        <h2>Documents</h2>
                        <div class="document-item">
                            <span>Certificate of Registration</span>
                            <a href="../RECRUITER/${data.certificate_of_registration}" target="_blank" class="view-btn">View</a>
                        </div>
                        <div class="document-item">
                            <span>BIR Registration</span>
                            <a href="../RECRUITER/${data.bir_registration}" target="_blank" class="view-btn">View</a>
                        </div>
                        <div class="document-item">
                            <span>Business Permit</span>
                            <a href="../RECRUITER/${data.business_permit}" target="_blank" class="view-btn">View</a>
                        </div>
                    </div>
                </div>

            `;
            
            document.getElementById("modalContent").innerHTML = content;
            document.getElementById("companyModal").style.display = "block";
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error fetching company details');
        });
}


// Function to edit company (redirect to edit page)
function editCompany(id) {
    window.location.href = `edit_company.php?id=${id}`;
}
</script>
</body>
</html>
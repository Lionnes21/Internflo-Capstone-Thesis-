<?php
    // Add this code at the beginning of your file (before any HTML output)
    session_start();
    include 'config.php';
    
    // Include PHPMailer classes at the top level
    require 'PHPMailer/src/Exception.php';
    require 'PHPMailer/src/PHPMailer.php';
    require 'PHPMailer/src/SMTP.php';
    
    // Use statements must be at the top level
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    // Get the internship ID from the URL
    $internship_id = isset($_GET['internship_id']) ? (int)$_GET['internship_id'] : 0;

    if ($internship_id === 0) {
        // No valid internship ID provided
        header("Location: companyloginpage.php");
        exit();
    }

    // Handle the removal of an applicant
    if (isset($_POST['action']) && $_POST['action'] === 'remove_applicant' && isset($_POST['student_id'])) {
        $student_id = (int)$_POST['student_id'];
        
        // Verify that the internship belongs to the logged-in company
        $verifyStmt = $conn->prepare("
            SELECT user_id FROM internshipad 
            WHERE internship_id = ?
        ");
        $verifyStmt->bind_param("i", $internship_id);
        $verifyStmt->execute();
        $verifyResult = $verifyStmt->get_result();
        
        if ($verifyResult->num_rows > 0) {
            $internshipData = $verifyResult->fetch_assoc();
            if ($internshipData['user_id'] == $_SESSION['user_id']) {
                // First, get the applicant's email and application_id before deleting
                $getEmailStmt = $conn->prepare("
                    SELECT sa.application_id, sa.email, sa.first_name, sa.last_name, i.internship_title, c.company_name  
                    FROM studentapplication sa
                    JOIN internshipad i ON sa.internshipad_id = i.internship_id
                    LEFT JOIN recruiters r ON i.user_id = r.id
                    LEFT JOIN approvedrecruiters ar ON i.user_id = ar.id
                    LEFT JOIN (
                        SELECT id, company_name FROM recruiters
                        UNION
                        SELECT id, company_name FROM approvedrecruiters
                    ) c ON i.user_id = c.id
                    WHERE sa.student_id = ? AND sa.internshipad_id = ?
                ");
                $getEmailStmt->bind_param("ii", $student_id, $internship_id);
                $getEmailStmt->execute();
                $emailResult = $getEmailStmt->get_result();
                
                if ($emailResult->num_rows > 0) {
                    $applicantData = $emailResult->fetch_assoc();
                    $application_id = $applicantData['application_id'];
                    
                    // Send decline email to the applicant
                    $mail = new PHPMailer(true);
                    
                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'rogermalabananbusi@gmail.com';
                        $mail->Password = 'fhnt amet zziu tlow';
                        $mail->SMTPSecure = 'tls';
                        $mail->Port = 587;
                        
                        $mail->setFrom('rogermalabananbusi@gmail.com', 'Internflo');
                        $mail->addAddress($applicantData['email']);
                        $mail->isHTML(true);
                        $mail->Subject = 'Internship Application Status Update';
                        
                        // Email body with design
                        $mail->Body = '
                        <div style="font-family: Arial, sans-serif; text-align: center; color: #333; max-width: 600px; margin: auto;">
                            <h1 style="background-color: #478831; color: white; padding: 10px 0; font-size: 24px;">
                                APPLICATION STATUS UPDATE
                            </h1>
                            <p style="font-size: 18px; color: #333;">Dear ' . htmlspecialchars($applicantData['first_name'] . ' ' . $applicantData['last_name']) . ',</p>
                            <p style="font-size: 16px; color: #555; line-height: 1.5;">
                                Thank you for your interest in the <strong>' . htmlspecialchars($applicantData['internship_title']) . '</strong> 
                                position at <strong>' . htmlspecialchars($applicantData['company_name']) . '</strong>.
                            </p>
                            <p style="font-size: 16px; color: #555; line-height: 1.5;">
                                After careful consideration of your application and our current requirements, 
                                we regret to inform you that we will not be moving forward with your candidacy 
                                at this time. This decision was based on various factors including company 
                                qualifications, assessment results, and the specific skills required for this role.
                            </p>
                            <p style="font-size: 16px; color: #555; line-height: 1.5;">
                                We encourage you to continue exploring other internship opportunities that 
                                may better align with your skills and career goals.
                            </p>
                            <p style="font-size: 16px; color: #555; line-height: 1.5;">
                                We wish you success in your future endeavors.
                            </p>
                            <p style="margin-top: 30px; font-size: 14px; color: #777;">
                                This is an automated message. Please do not reply to this email.
                            </p>
                        </div>';
                        
                        // Send email
                        $mail->send();
                        
                    } catch (Exception $e) {
                        // Log email sending error (optional)
                        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
                    }
                    
                    // First, delete any related interview records
                    if (!empty($application_id)) {
                        $deleteInterviewStmt = $conn->prepare("
                            DELETE FROM student_interview 
                            WHERE application_id = ?
                        ");
                        $deleteInterviewStmt->bind_param("i", $application_id);
                        $deleteInterviewStmt->execute();
                        $deleteInterviewStmt->close();
                    }
                    
                    // Then delete the application
                    $deleteStmt = $conn->prepare("
                        DELETE FROM studentapplication 
                        WHERE student_id = ? AND internshipad_id = ?
                    ");
                    $deleteStmt->bind_param("ii", $student_id, $internship_id);
                    $deleteStmt->execute();
                    $deleteStmt->close();
                }
                $getEmailStmt->close();
            }
        }
        $verifyStmt->close();
        
        // Redirect to the same page to refresh the applicants list
        header("Location: " . $_SERVER['PHP_SELF'] . "?internship_id=" . $internship_id);
        exit();
    }

    // Rest of your existing code...
    function getFullName() {
        if (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
            return htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']);
        }
        return 'Your Name'; // Fallback if names are not set
    }

    // Function to get assessment category based on score
    function getAssessmentCategory($score_string) {
        // Check if score is empty or blank
        if (empty($score_string) || trim($score_string) == '') {
            return array('text' => 'N/A', 'class' => 'badge badge-secondary');
        }
        
        // Split the score string into correct answers and total questions
        list($correct, $total) = explode('/', $score_string);
        $correct = (int)$correct;
        $total = (int)$total;
        
        // Handle division by zero
        if ($total == 0) {
            return array('text' => 'N/A', 'class' => 'badge badge-secondary');
        }
        
        // Calculate percentage
        $percentage = ($correct / $total) * 100;

        if ($percentage >= 60) {  // 60% or higher
            return array('text' => 'Excellent Score', 'class' => 'badge badge-success');
        } elseif ($percentage >= 40) {  // 40-59%
            return array('text' => 'Average Score', 'class' => 'badge badge-warning');
        } else {
            return array('text' => 'Failed Score', 'class' => 'badge badge-danger');
        }
    }

    // Fetch internship details including company information
    $stmt = $conn->prepare("
    SELECT i.*, 
        CASE 
            WHEN r.company_name IS NOT NULL THEN r.company_name 
            WHEN ar.company_name IS NOT NULL THEN ar.company_name 
        END as company_name,
        CASE 
            WHEN r.company_logo IS NOT NULL THEN r.company_logo 
            WHEN ar.company_logo IS NOT NULL THEN ar.company_logo 
        END as company_logo,
        CASE 
            WHEN r.email IS NOT NULL THEN r.email 
            WHEN ar.email IS NOT NULL THEN ar.email 
        END as company_email,
        CASE 
            WHEN r.mobile_number IS NOT NULL THEN r.mobile_number 
            WHEN ar.mobile_number IS NOT NULL THEN ar.mobile_number 
        END as company_mobile
    FROM internshipad i
    LEFT JOIN recruiters r ON i.user_id = r.id
    LEFT JOIN approvedrecruiters ar ON i.user_id = ar.id
    WHERE i.internship_id = ?
    ");

    $stmt->bind_param("i", $internship_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Internship not found
        header("Location: companyloginpage.php");
        exit();
    }

    $internship = $result->fetch_assoc();

    // Fetch student applications for this internship
    $stmt = $conn->prepare("
        SELECT sa.student_id, sa.first_name, sa.last_name, sa.assessment_score, sa.status
        FROM studentapplication sa
        WHERE sa.internshipad_id = ?
    ");

    // Check if assessment form already exists for this internship
    $checkForm = $conn->prepare("SELECT form_id FROM assessment_forms WHERE internship_id = ?");
    $checkForm->bind_param("i", $internship_id);
    $checkForm->execute();
    $formResult = $checkForm->get_result();
    $formExists = $formResult->num_rows > 0;
    $checkForm->close();

    $stmt->bind_param("i", $internship_id);
    $stmt->execute();
    $applications = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="pics/ucc.png">
    <title>UCC - Applicants</title>
    <link rel="stylesheet" href="applicants.css">
    <link rel="stylesheet" href="NAV-login.css">
    <link rel="stylesheet" href="FOOTER.css">
    
</head>
<body>

    <!-- NAVIGATION -->
    <div class="navbar">
        <div class="logo-container">
            <button class="menu-toggle" aria-label="Toggle Navigation">☰</button>
            <img src="pics/ucc-logo.png" alt="Logo" class="logo-img">
        </div>
        <div class="nav-links">
            <a href="companyloginpage.php">HOME</a>
            <a href="#about">ABOUT US</a>
            <a href="#contact">CONTACT US</a>
            <?php if(!isset($_SESSION['email'])): ?>
                <a href="../STUDENTCOORLOG/login.php" class="login-btn">LOGIN</a>
                <a href="../MAIN/MAIN.php" class="employer-btn">APPLICANT SITE</a>
            <?php endif; ?>
        </div>
        <?php if(isset($_SESSION['email'])): ?>
        <div class="auth-buttons">
            <div class="dropdown-container">
                <div class="border">
                    <span class="greeting-text"><?php echo htmlspecialchars($_SESSION['email']); ?></span>
                    <div class="dropdown-btn" onclick="toggleDropdown(event)">
                        <img src="pics/profile.png" alt="Profile" onerror="this.onerror=null;this.src='pics/default_profile.jpg';">
                    </div>
                </div>
                <div id="dropdown-content" class="dropdown-content">
                    <div class="user-fullname"><?php echo getFullName(); ?></div>
                    <hr style="margin: 0 auto">
                    <a href="company-profile.php">Profile</a>
                    <a href="company-overview.php">Interns</a>
                    <a href="chat-inbox.php">Emails</a>
                    <a href="company-account.php">Settings</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Elements
            const navbar = document.querySelector('.navbar');
            const menuToggle = document.querySelector('.menu-toggle');
            const dropdownContent = document.getElementById("dropdown-content");
            let timeout;

            // Navbar visibility functions
            const hideNavbar = () => {
                if (window.scrollY > 0) {
                    navbar.style.opacity = '0';
                    navbar.style.pointerEvents = 'none';
                }
            };

            const showNavbar = () => {
                navbar.style.opacity = '1';
                navbar.style.pointerEvents = 'auto';
            };

            const resetNavbarTimeout = () => {
                showNavbar();
                clearTimeout(timeout);
                if (window.scrollY > 0) {
                    timeout = setTimeout(hideNavbar, 1000);
                }
            };

            // Scroll event listeners
            window.addEventListener('scroll', () => {
                if (window.scrollY === 0) {
                    showNavbar();
                    clearTimeout(timeout);
                } else {
                    resetNavbarTimeout();
                }
            });

            // User interaction listeners
            window.addEventListener('mousemove', resetNavbarTimeout);
            window.addEventListener('click', resetNavbarTimeout);
            window.addEventListener('keydown', resetNavbarTimeout);

            // Initial check
            if (window.scrollY > 0) {
                timeout = setTimeout(hideNavbar, 1000);
            }

            // Mobile menu toggle functionality
            menuToggle.addEventListener('click', function(event) {
                event.stopPropagation(); // Prevent event from bubbling
                navbar.classList.toggle('active');
                
                if (navbar.classList.contains('active')) {
                    menuToggle.innerHTML = '☰';
                    menuToggle.style.color = '#e77d33';
                } else {
                    menuToggle.innerHTML = '☰';
                    menuToggle.style.color = '#fd6f41';
                }
            });

            // Dropdown toggle function
            window.toggleDropdown = function(event) {
                if (event) {
                    event.stopPropagation();
                }
                
                const isDisplayed = dropdownContent.style.display === "block";
                
                // Close dropdown if it's open
                if (isDisplayed) {
                    dropdownContent.style.display = "none";
                } else {
                    // Close any other open dropdowns first
                    const allDropdowns = document.querySelectorAll('.dropdown-content');
                    allDropdowns.forEach(dropdown => {
                        dropdown.style.display = "none";
                    });
                    
                    // Open this dropdown
                    dropdownContent.style.display = "block";
                }
            };

            // Close menu and dropdown when clicking outside
            document.addEventListener('click', function(event) {
                // Handle mobile menu
                const isClickInsideNavbar = navbar.contains(event.target);
                if (!isClickInsideNavbar && navbar.classList.contains('active')) {
                    navbar.classList.remove('active');
                    menuToggle.innerHTML = '☰';
                    menuToggle.style.color = '#fd6f41';
                }

                // Handle dropdown
                const isClickInsideDropdown = event.target.closest('.dropdown-container');
                if (!isClickInsideDropdown && dropdownContent) {
                    dropdownContent.style.display = "none";
                }
            });

            // Window resize handler
            window.addEventListener('resize', function() {
                if (window.innerWidth > 1300) {
                    navbar.classList.remove('active');
                    menuToggle.innerHTML = '☰';
                    menuToggle.style.color = '#fd6f41';
                }
            });
        });
    </script>
    <!-- NAVIGATION -->

    


    <div class="applicantwidth">
        <?php
        // Add this code before the banner in applicants.php

        // Check if assessment form already exists for this internship
        $checkForm = $conn->prepare("SELECT form_id FROM assessment_forms WHERE internship_id = ?");
        $checkForm->bind_param("i", $internship_id);
        $checkForm->execute();
        $formResult = $checkForm->get_result();
        $formExists = $formResult->num_rows > 0;
        $checkForm->close();

        // Only show the banner if no form exists
        if (!$formExists): ?>
            <div class="banner">
                <p class="banner-text">
                    Haven't evaluated your applicants yet? Build a comprehensive <span style="color: #ff8c00; font-weight: 600">assessment</span> for internship candidates now!
                </p>
        
                <button class="create-button" onclick="window.location.href='questionaire.php?internship_ad_id=<?php echo $internship_id; ?>'">
                    Publish <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#FFFFFF"><path d="M236.44-152.59q-34.46 0-59.16-24.69-24.69-24.7-24.69-59.16 0-34.47 24.69-59.02Q201.98-320 236.44-320q34.47 0 59.02 24.54Q320-270.91 320-236.44q0 34.46-24.54 59.16-24.55 24.69-59.02 24.69Zm0-243.82q-34.46 0-59.16-24.55-24.69-24.54-24.69-59.01 0-34.46 24.69-59.04 24.7-24.58 59.16-24.58 34.47 0 59.02 24.55Q320-514.5 320-480.03q0 34.46-24.54 59.04-24.55 24.58-59.02 24.58Zm0-243.59q-34.46 0-59.16-24.54-24.69-24.55-24.69-59.02 0-34.46 24.69-59.16 24.7-24.69 59.16-24.69 34.47 0 59.02 24.69Q320-758.02 320-723.56q0 34.47-24.54 59.02Q270.91-640 236.44-640Zm243.59 0q-34.46 0-59.04-24.54-24.58-24.55-24.58-59.02 0-34.46 24.55-59.16 24.54-24.69 59.01-24.69 34.46 0 59.04 24.69 24.58 24.7 24.58 59.16 0 34.47-24.55 59.02Q514.5-640 480.03-640Zm243.53 0q-34.47 0-59.02-24.54Q640-689.09 640-723.56q0-34.46 24.54-59.16 24.55-24.69 59.02-24.69 34.46 0 59.16 24.69 24.69 24.7 24.69 59.16 0 34.47-24.69 59.02Q758.02-640 723.56-640ZM480.03-396.41q-34.46 0-59.04-24.55-24.58-24.54-24.58-59.01 0-34.46 24.55-59.04 24.54-24.58 59.01-24.58 34.46 0 59.04 24.55 24.58 24.54 24.58 59.01 0 34.46-24.55 59.04-24.54 24.58-59.01 24.58Zm38.54 198.32v-65.04q0-9.2 3.47-17.53 3.48-8.34 10.2-15.06l208.76-208q9.72-9.76 21.59-14.09 11.88-4.34 23.76-4.34 12.95 0 24.8 4.86 11.85 4.86 21.55 14.57l37 37q8.67 9.72 13.55 21.6 4.88 11.87 4.88 23.75 0 12.2-4.36 24.41-4.36 12.22-14.07 21.94l-208 208q-6.69 6.72-15.04 10.07-8.36 3.36-17.55 3.36h-65.04q-19.16 0-32.33-13.17-13.17-13.17-13.17-32.33Zm270.17-185.67 34.61-36.61-37-37-35.61 35.61 38 38Z"/></svg>
                </button>
            </div>
        
            <script>
                function updateBannerText() {
                    const bannerText = document.querySelector('.banner-text');
                    if (window.innerWidth <= 870) {
                        // Tablet version - shorter text
                        bannerText.innerHTML = 'Build an <span style="color: #ff8c00; font-weight: 600">assessment</span> for your candidates!';
                    } else {
                        // Desktop version - original full text
                        bannerText.innerHTML = 'Haven\'t evaluated your applicants yet? Build a comprehensive <span style="color: #ff8c00; font-weight: 600">assessment</span> for internship candidates now!';
                    }
                }
        
                // Run on page load
                document.addEventListener('DOMContentLoaded', updateBannerText);
        
                // Run whenever the window is resized
                window.addEventListener('resize', updateBannerText);
            </script>
        <?php endif; ?>
        
        <div class="header">
            <img src="../RECRUITER/<?php echo htmlspecialchars($internship['company_logo']); ?>" 
                alt="<?php echo htmlspecialchars($internship['company_name']); ?> Logo" 
                class="company-logo">
            <div class="job-info">
                <span class="applying-for">Applicants for</span>
                <h1><?php echo htmlspecialchars($internship['internship_title']); ?></h1>
                <h2><?php echo htmlspecialchars($internship['company_name']); ?></h2>
            </div>
        </div>
        
        <?php
        // Check if there are any applications
        if ($applications->num_rows > 0): ?>
        
        <div class="list-container">
            <!-- Header Row -->
            <div class="list-header">
                <div class="list-header-cell">Status</div>
                <div class="list-header-cell">Applicant</div>
                <div class="list-header-cell">Assessment</div>
                <div class="list-header-cell">Actions</div>
            </div>
            
            <!-- Data Rows -->
            <?php while($application = $applications->fetch_assoc()): 
                $assessment_score = $application['assessment_score'] ?? '';
                $assessment = getAssessmentCategory($assessment_score);
            ?>
        
            <div class="list-row" id="applicant-<?php echo $application['student_id']; ?>">
                <div class="list-cell">
                    <span class="badge badge-status">
                        <?php echo htmlspecialchars($application['status']); ?>
                    </span>
                </div>
                <div class="list-cell">
                    <?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?>
                </div>
                <div class="list-cell">
                    <span class="<?php echo $assessment['class']; ?>">
                        <?php echo $assessment['text']; ?>
                    </span>
                </div>
                <div class="list-cell">
                    <div style="display: flex; gap: 10px;">
                        <button class="btn-view-details" onclick="window.location.href='viewapplicant.php?student_id=<?php echo $application['student_id']; ?>&internship_id=<?php echo $internship['internship_id']; ?>&company_name=<?php echo urlencode($internship['company_name']); ?>&email=<?php echo urlencode($internship['company_email']); ?>&mobile_number=<?php echo urlencode($internship['company_mobile']); ?>&internship_title=<?php echo urlencode($internship['internship_title']); ?>'">
                            View Details
                        </button>
                        
                        <form method="post" style="margin: 0;">
                            <input type="hidden" name="action" value="remove_applicant">
                            <input type="hidden" name="student_id" value="<?php echo $application['student_id']; ?>">
                            <button type="submit" class="btn-remove">Decline</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        
        <?php else: ?>
        <!-- No applications message -->
        <div class="no-applicants">
            No applications for this internship advertisement
        </div>
        <?php endif; ?>
    </div>


    <script>
                (function(){if(!window.chatbase||window.chatbase("getState")!=="initialized"){window.chatbase=(...arguments)=>{if(!window.chatbase.q){window.chatbase.q=[]}window.chatbase.q.push(arguments)};window.chatbase=new Proxy(window.chatbase,{get(target,prop){if(prop==="q"){return target.q}return(...args)=>target(prop,...args)}})}const onLoad=function(){const script=document.createElement("script");script.src="https://www.chatbase.co/embed.min.js";script.id="sqZ5VD70WA_0wO97JZLEZ";script.domain="www.chatbase.co";document.body.appendChild(script)};if(document.readyState==="complete"){onLoad()}else{window.addEventListener("load",onLoad)}})();
    </script>
    <!-- FOOTER -->
        <div class="footbg">
            <div class="properdiv">
                <footer>
        
                    <!-- Logo Section -->
                    <div class="rightside">
                        <h2 class="university-name">UNIVERSITY OF CALOOCAN CITY</h2>
                        <h4 class="program-name">COMPUTER SCIENCE DEPARTMENT</h4>
                        <p>
                            Biglang Awa Street <br> Cor 11th Ave Catleya,<br> Caloocan 1400 Metro Manila, Philippines
                        </p>
                        <br>
                        <p style="margin: 0">
                            <strong>Phone:</strong>&nbsp;(02) 5310 6855
                        </p>
                        <p style="margin: 0">
                            <strong>Email:</strong>&nbsp;support@uccinternshipportal.ph
                        </p>
    
                    </div>
                
                    <!-- Internship Seekers Section -->
                    <div class="centerside">
                        <h4>INTERNSHIP SEEKERS</h4>
                        <ul>
                            <li><a href="companyloginpage.php#advertise">Internship by Company</a></li>
                            <li><a href="companyloginpage.php#advertise">Internship by City</a></li>
                            <li><a href="companyloginpage.php#advertise">Search Nearby Internship</a></li>
                        </ul>
                    </div>
                
                    <!-- Employers Section -->
                    <div class="centerside">
                        <h4>EMPLOYERS</h4>
                        <ul>
                            <li><a href="companyloginpage.php">Post Internships</a></li>
                        </ul>
                    </div>
                
                    <!-- About Interflo Section -->
                    <div class="centerside">
                        <h4>ABOUT INTERNFLO</h4>
                        <ul>
                            <li><a href="companyloginpage.php#about">About Us</a></li>
                            <li><a href="companyloginpage.php#chatbot">How It Works</a></li>
                            <li><a href="companyloginpage.php#contact">Contact Us</a></li>
                        </ul>
                    </div>
                
                </footer>
            </div>
        </div>
        <div class="underfooter-bg">
            <div class="underfooter">
                <div class="uf-content">
                    <p>Copyright <strong>University of Caloocan City</strong> Internflo©2025. All Rights Reserved</p>
                </div>
            </div>
        </div>
    <!-- FOOTER -->	
</body>
</html>
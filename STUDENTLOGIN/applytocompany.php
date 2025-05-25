<?php
    session_start();
    include 'config.php';

    // Enable error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    $isLoggedIn = true;
    $internship_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    // Initialize database connection for assessment forms
	$db_host = "localhost";
	$db_user = "u798912504_root";
	$db_pass = "Internfloucc2025*";
	$db_name = "u798912504_internflo";

    try {
        // Create new connection for assessment forms
        $assessment_conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        
        if ($assessment_conn->connect_error) {
            throw new Exception("Assessment DB Connection failed: " . $assessment_conn->connect_error);
        }

        // Modified query to only fetch forms matching the internship_id
        $forms_query = "SELECT * FROM assessment_forms WHERE internship_id = ? ORDER BY created_at DESC";
        $stmt = $assessment_conn->prepare($forms_query);
        $stmt->bind_param("i", $internship_id);
        $stmt->execute();
        $forms_result = $stmt->get_result();

        if (!$forms_result) {
            throw new Exception("Error fetching forms: " . $assessment_conn->error);
        }
    } catch (Exception $e) {
        error_log("Assessment Error: " . $e->getMessage());
        // Don't die here, allow the rest of the page to load
        $forms_result = null;
    }
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $student_id = $_SESSION['user_id'];
        
        // Get form data
        $fname = mysqli_real_escape_string($conn, $_POST['fname']);
        $lname = mysqli_real_escape_string($conn, $_POST['lname']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $pnumber = mysqli_real_escape_string($conn, $_POST['pnumber']);
        $portfolio_link = mysqli_real_escape_string($conn, $_POST['portfolio-link']);
        $assessment_score = mysqli_real_escape_string($conn, $_POST['assessment_score']);

        // Initialize file variables
        $cv_file = '';
        $endorsement_file = '';
        $demo_video = '';

        // Define the file upload handler function first
        function handleFileUpload($file, $directory) {
            // Check if directory exists, if not create it
            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }

            if (!empty($file['name'])) {
                $filename = time() . '_' . basename($file['name']);
                $target_path = $directory . $filename;
        
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    return $filename;
                } else {
                    error_log("Upload failed for file: " . $file['name'] . " to " . $target_path);
                    return '';
                }
            }
            return '';
        }

        // Process file uploads
        $cv_file = handleFileUpload($_FILES['cv'], 'cv/');
        $endorsement_file = handleFileUpload($_FILES['endorse'], 'endorse/');
        
        // Debug video upload
        if (isset($_FILES['demo'])) {
            error_log("Video file information: " . print_r($_FILES['demo'], true));
            $demo_video = handleFileUpload($_FILES['demo'], 'demovids/');
        }

        // If we get here, all files were uploaded successfully
        // Prepare and execute SQL query
        $sql = "INSERT INTO studentapplication (
            internshipad_id, student_id, first_name, last_name, address, assessment_score, 
            email, phone_number, cv_file, endorsement_file, demo_video, portfolio_link
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            echo "Database preparation failed";
            exit;
        }

        $stmt->bind_param(
            "iissssssssss", // Added an extra 's' for assessment_score
            $internship_id, 
            $student_id, 
            $fname, 
            $lname, 
            $address,
            $assessment_score, // Add this line
            $email, 
            $pnumber, 
            $cv_file, 
            $endorsement_file, 
            $demo_video, 
            $portfolio_link
        );

        if ($stmt->execute()) {
            // Redirect to success page
            header('Location: studentfrontpage.php');
            exit;
        } else {
            error_log("Execute failed: " . $stmt->error);
            echo "Error submitting application: " . $stmt->error;
            exit;
        }
    }

    // Get internship details
    $query = "
        SELECT 
            i.internship_id,
            i.internship_title,
            i.department,
            i.internship_type,
            i.number_of_openings,
            i.duration,
            i.internship_description,
            i.internship_summary,
            i.requirements,
            i.qualifications,
            i.skills_required,
            i.application_deadline,
            i.additional_info,
            i.created_at,
            r.company_name,
            r.industry,
            r.company_overview,
            r.company_address,
            r.company_logo,
            r.company_phone,
            r.company_email
        FROM internshipad i
        JOIN approvedrecruiters r ON i.user_id = r.id
        WHERE i.internship_id = ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $internship_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $job = $result->fetch_assoc();

    // If no job found or invalid ID, redirect back
    if (!$job) {
        header('Location: jobs.php');
        exit;
    }

    $userId = $_SESSION['user_id'];
    $sql = 'SELECT 
        first_name, 
        middle_name, 
        last_name, 
        suffix, 
        email, 
        home_address, 
        city, 
        region, 
        mobile_number, 
        profile_pic,
        login_method
    FROM students 
    WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Set default values for form fields
    if ($user) {
        $firstName = htmlspecialchars($user['first_name']);
        $lastName = htmlspecialchars($user['last_name']);
        $homeLocation = htmlspecialchars("{$user['home_address']} {$user['city']}, {$user['region']}");
        $email = htmlspecialchars($user['email']);
        $mobileNumber = htmlspecialchars($user['mobile_number']);
        
        $initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
        $fullName2 =             $fullName2 = trim($user['first_name'] . 
                         
        ' ' . $user['last_name']);
        
        $profile_pic = !empty($user['profile_pic']) ? $user['profile_pic'] : 'path/to/default/profile.jpg';
        $loginMethod = $user['login_method'];
    }
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="applycompany.css">
    <link rel="stylesheet" href="assessment.css">
    <link rel="stylesheet" href="NAVX.css">
    <link rel="stylesheet" href="FOOTER.css">
    <script src="assessment.js"></script>
    <link rel="icon" href="pics/ucc.png">
    <title>UCC - Application</title>
</head>
<body>

    <!-- NAVIGATION -->
    <div class="navbar">
            <div class="logo-container">
            <button class="menu-toggle" aria-label="Toggle Navigation">☰</button>
                    <img src="pics/ucc-logo.png" alt="Logo">

                </div>
                <div class="nav-links">
                    <a href="studentfrontpage.php">HOME</a>
                    <a href="#">ABOUT US</a>
                    <a href="#">CONTACT US</a>
                </div>
                <div class="auth-buttons">
                    <?php if ($isLoggedIn): ?>
                        <div class="dropdown-container">
                            <div class="border">
                                <span class="greeting-text"><?php echo htmlspecialchars($user['email']); ?></span>
                                <div class="dropdown-btn" onclick="toggleDropdown()">
                                    <img src="<?php echo htmlspecialchars($profile_pic, ENT_QUOTES); ?>" alt="Profile Picture" onerror="this.onerror=null;this.src='pics/default_profile.jpg';" />
                                </div>
                            </div>
                            <div id="dropdown-content" class="dropdown-content">
                                <div class="user-fullname"><?php echo $fullName2; ?></div>
                                <hr style="margin: 0 auto">
                                <a href="student-profile.php">Profile</a>
                                <a href="../monitoring/std_dashboard.php">Internship</a>
                                <a href="form.php">Emails</a>
                                <a href="settings.php">Settings</a>
                                <a href="logout.php">Logout</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- <button id="signUpBtn" class="sign-up">Sign Up</button>
                        <button class="login" onclick="window.location.href='../STUDENTCOORLOG/login.php';">Login</button> -->
                    <?php endif; ?>
            </div>
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

            // Enhanced dropdown toggle function
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


    <!-- APPLICATION FORM -->
    <?php if ($loginMethod === 'email'): ?>
        <div class="verified-alert">
            <div class="verified-alert__wrapper">
                <span class="verified-alert__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2d6a2d"><path d="m438-452-58-57q-11-11-27.5-11T324-508q-11 11-11 28t11 28l86 86q12 12 28 12t28-12l170-170q12-12 11.5-28T636-592q-12-12-28.5-12.5T579-593L438-452ZM326-90l-58-98-110-24q-15-3-24-15.5t-7-27.5l11-113-75-86q-10-11-10-26t10-26l75-86-11-113q-2-15 7-27.5t24-15.5l110-24 58-98q8-13 22-17.5t28 1.5l104 44 104-44q14-6 28-1.5t22 17.5l58 98 110 24q15 3 24 15.5t7 27.5l-11 113 75 86q10 11 10 26t-10 26l-75 86 11 113q2 15-7 27.5T802-212l-110 24-58 98q-8 13-22 17.5T584-74l-104-44-104 44q-14 6-28 1.5T326-90Z"/></svg>
                </span>
                <p class="verified-alert__message">                 
                    Account has been successfully verified, granting access to apply for internship advertisements
                </p>
            </div>
        </div>
    <?php elseif ($loginMethod === 'google'): ?>
        <div class="alert">
            <div class="alert__wrapper">
                <span class="alert__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#856404"><path d="M403.59-724.3h152.82v-80H403.59v80ZM720-40q-83 0-141.5-58.5T520-240q0-83 58.5-141.5T720-440q83 0 141.5 58.5T920-240q0 83-58.5 141.5T720-40Zm-557.13-71.87q-37.78 0-64.39-26.61t-26.61-64.39V-633.3q0-37.79 26.61-64.4 26.61-26.6 64.39-26.6h149.72v-80q0-37.79 26.61-64.4 26.6-26.6 64.39-26.6h152.82q37.79 0 64.39 26.6 26.61 26.61 26.61 64.4v80h149.72q37.78 0 64.39 26.6 26.61 26.61 26.61 64.4v106.67q0 20.63-17.89 30.59-17.89 9.95-37.04.52-26-12.48-54.74-18.48-28.74-6-58.46-6-116 0-198 82t-82 198q0 18.48 2.5 37.1t7.5 36.86q5.72 19.87-5.86 37.02-11.57 17.15-30.97 17.15h-250.3ZM720-116.65q9.43 0 16.15-6.72 6.72-6.72 6.72-16.15 0-9.44-6.72-16.27-6.72-6.84-16.15-6.84t-16.15 6.84q-6.72 6.83-6.72 16.27 0 9.43 6.72 16.15 6.72 6.72 16.15 6.72Zm0-86.7q8.72 0 15.32-6.6 6.59-6.59 6.59-15.31v-116.17q0-8.72-6.48-15.32-6.47-6.6-15.43-6.6-8.72 0-15.32 6.6-6.59 6.6-6.59 15.32v116.17q0 8.72 6.48 15.31 6.47 6.6 15.43 6.6Z"/></svg>
                </span>
                <p class="alert__message">                 
                    Authorization from the OJT Advisor is required for accounts to pursue internship applications.
                </p>
            </div>
        </div>
    <?php endif; ?>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <img src="../RECRUITER/<?php echo htmlspecialchars($job['company_logo']); ?>" 
                alt="<?php echo htmlspecialchars($job['company_name']); ?> Logo" 
                class="company-logo">
            <div class="job-info">
                <span class="applying-for">Applying for</span>
                <h1><?php echo htmlspecialchars($job['internship_title']); ?></h1>
                <h2><?php echo htmlspecialchars($job['company_name']); ?></h2>
                <a href="#" class="view-job" onclick="history.back(); return false;">View job description</a>
            </div>
        </div>
    
        <!-- Stepper -->
        <div class="stepper">
            <div class="step-indicator" id="step1">
                <div class="step-circle">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ddd">
                        <path d="M480-280q83 0 141.5-58.5T680-480q0-83-58.5-141.5T480-680q-83 0-141.5 58.5T280-480q0 83 58.5 141.5T480-280Zm0 200q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                    </svg>
                </div>
                <div class="step-text">Applicant Information</div>
            </div>
    
            <div class="step-indicator" id="step2">
                <div class="step-circle">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ddd">
                        <path d="M480-280q83 0 141.5-58.5T680-480q0-83-58.5-141.5T480-680q-83 0-141.5 58.5T280-480q0 83 58.5 141.5T480-280Zm0 200q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                    </svg>
                </div>
                <div class="step-text">Internship Assessment</div>
            </div>
    
            <div class="step-indicator" id="step3">
                <div class="step-circle">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ddd">
                        <path d="M480-280q83 0 141.5-58.5T680-480q0-83-58.5-141.5T480-680q-83 0-141.5 58.5T280-480q0 83 58.5 141.5T480-280Zm0 200q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                    </svg>
                </div>
                <div class="step-text">Final Review</div>
            </div>
        </div>
        <form action="<?php echo $_SERVER['PHP_SELF'] . '?id=' . $internship_id; ?>" method="POST" enctype="multipart/form-data" id="applicationForm">
            <!-- Step 1: Upload Documents -->
            <div class="content-section" id="step1-content">
                <div class="pd-title">Applicant Information</div>
                <div class="pd-form">
                <h1>Personal details</h1>
                    <div class="name-row">
                        <div class="input-container">
                            <label>First name</label>
                            <input name="fname" type="text" id="firstName" value="<?php echo $firstName; ?>" placeholder="First name" onkeypress="allowOnlyLetters(event)" oninput="capitalizeFirstLetter(this)">
                            <span class="error-message" style="display: none;">This field is required</span>
                        </div>
                        <div class="input-container">
                            <label>Last name</label>
                            <input name="lname" type="text" id="lastName" value="<?php echo $lastName; ?>" placeholder="Last name" onkeypress="allowOnlyLetters(event)" oninput="capitalizeFirstLetter(this)">
                            <span class="error-message" style="display: none;">This field is required</span>
                        </div>
                    </div>

                    <label>Home location</label>
                    <div class="input-container">
                        <input name="address" type="text" id="homeLocation" value="<?php echo $homeLocation; ?>" placeholder="Address">
                        <span class="error-message" style="display: none;">This field is required</span>
                    </div>

                    <div class="name-row">
                        <div class="input-container">
                            <label>Email</label>
                            <input name="email" type="email" id="email" value="<?php echo $email; ?>" placeholder="Email">
                            <span class="error-message" style="display: none;">This field is required</span>
                        </div>
                        <div class="input-container">
                            <label>Phone Number</label>
                            <input name="pnumber" type="text" id="phoneNumber" value="<?php echo $mobileNumber; ?>" placeholder="Phone number" maxlength="11" onkeypress="allowOnlyNumbers(event)">
                            <span class="error-message" style="display: none;">This field is required</span>
                        </div>
                    </div>                
                </div>
                    <!-- CV Section -->
                    <div class="document-section">
                        <h2 class="section-title">Curriculum Vitae</h2>
                        <div class="upload-option" id="cvUploadSection">
                            <div>
                                <div class="upload-text">Upload a Curriculum Vitae</div>
                                <div class="file-preview" id="cvPreview">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2E3849"><path d="M202.87-111.87q-37.78 0-64.39-26.61t-26.61-64.39v-554.26q0-37.78 26.61-64.39t64.39-26.61h554.26q37.78 0 64.39 26.61t26.61 64.39v554.26q0 37.78-26.61 64.39t-64.39 26.61H202.87Zm0-91h554.26v-554.26H202.87v554.26Zm0-554.26v554.26-554.26Zm118.56 475.7h200q17 0 28.5-11.5t11.5-28.5q0-17-11.5-28.5t-28.5-11.5h-200q-17 0-28.5 11.5t-11.5 28.5q0 17 11.5 28.5t28.5 11.5Zm0-158.57h317.14q17 0 28.5-11.5t11.5-28.5q0-17-11.5-28.5t-28.5-11.5H321.43q-17 0-28.5 11.5t-11.5 28.5q0 17 11.5 28.5t28.5 11.5Zm0-158.57h317.14q17 0 28.5-11.5t11.5-28.5q0-17-11.5-28.5t-28.5-11.5H321.43q-17 0-28.5 11.5t-11.5 28.5q0 17 11.5 28.5t28.5 11.5Z"/></svg>
                                    <span class="file-name" id="cvFileName"></span>
                                    <span class="remove-file" onclick="removeFile('cv')">Remove</span>
                                </div>
                                <label for="cvUpload" class="upload-button">Upload</label>
                                <input name="cv" type="file" id="cvUpload" class="file-input" accept=".pdf">
                                <div class="pdf-banner">
                                    <p class="pdf-banner-text">
                                        <span class="pdf-banner-content">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="22px" viewBox="0 -960 960 960" width="22px" fill="#666666"><path d="M408-528h24q20.4 0 34.2-13.8Q480-555.6 480-576v-24q0-20.4-13.8-34.2Q452.4-648 432-648h-48q-9.6 0-16.8 7.2-7.2 7.2-7.2 16.8v144q0 9.6 7.2 16.8 7.2 7.2 16.8 7.2 9.6 0 16.8-7.2 7.2-7.2 7.2-16.8v-48Zm0-48v-24h24v24h-24Zm168 120q20.4 0 34.2-13.8Q624-483.6 624-504v-96q0-20.4-13.8-34.2Q596.4-648 576-648h-48q-9.6 0-16.8 7.2-7.2 7.2-7.2 16.8v144q0 9.6 7.2 16.8 7.2 7.2 16.8 7.2h48Zm-24-48v-96h24v96h-24Zm144-24h24.29q9.71 0 16.71-7.2t7-16.8q0-9.6-7.2-16.8-7.2-7.2-16.8-7.2h-24v-24h24.29q9.71 0 16.71-7.2t7-16.8q0-9.6-7.24-16.8-7.23-7.2-16.88-7.2h-48.23q-9.65 0-16.65 7.2-7 7.2-7 16.8v144q0 9.6 7.2 16.8 7.2 7.2 16.8 7.2 9.6 0 16.8-7.2 7.2-7.2 7.2-16.8v-48ZM312-240q-29.7 0-50.85-21.15Q240-282.3 240-312v-480q0-29.7 21.15-50.85Q282.3-864 312-864h480q29.7 0 50.85 21.15Q864-821.7 864-792v480q0 29.7-21.15 50.85Q821.7-240 792-240H312ZM168-96q-29.7 0-50.85-21.15Q96-138.3 96-168v-516q0-15.3 10.29-25.65Q116.58-720 131.79-720t25.71 10.35Q168-699.3 168-684v516h516q15.3 0 25.65 10.29Q720-147.42 720-132.21t-10.35 25.71Q699.3-96 684-96H168Z"/></svg>
                                            <span class="pdf-text">Only accept file of pdf with limit of 5mb</span>
                                        </span>
                                    </p>
                                </div>
                                <span class="error-message" style="display: none;">This field is required</span>
                            </div>
                        </div>
                    </div>

                    <!-- Endorsement Letter Section -->
                    <div class="document-section">
                        <h2 class="section-title">Endorsement Letter</h2>
                        <div class="upload-option" id="endorsementUploadSection">
                            <div>
                                <div class="upload-text">Upload an Endorsement Letter</div>
                                <div class="file-preview" id="endorsementPreview">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2E3849"><path d="M202.87-111.87q-37.78 0-64.39-26.61t-26.61-64.39v-554.26q0-37.78 26.61-64.39t64.39-26.61h554.26q37.78 0 64.39 26.61t26.61 64.39v554.26q0 37.78-26.61 64.39t-64.39 26.61H202.87Zm0-91h554.26v-554.26H202.87v554.26Zm0-554.26v554.26-554.26Zm118.56 475.7h200q17 0 28.5-11.5t11.5-28.5q0-17-11.5-28.5t-28.5-11.5h-200q-17 0-28.5 11.5t-11.5 28.5q0 17 11.5 28.5t28.5 11.5Zm0-158.57h317.14q17 0 28.5-11.5t11.5-28.5q0-17-11.5-28.5t-28.5-11.5H321.43q-17 0-28.5 11.5t-11.5 28.5q0 17 11.5 28.5t28.5 11.5Zm0-158.57h317.14q17 0 28.5-11.5t11.5-28.5q0-17-11.5-28.5t-28.5-11.5H321.43q-17 0-28.5 11.5t-11.5 28.5q0 17 11.5 28.5t28.5 11.5Z"/></svg>
                                    <span class="file-name" id="endorsementFileName"></span>
                                    <span class="remove-file" onclick="removeFile('endorsement')">Remove</span>
                                </div>
                                <label for="endorsementUpload" class="upload-button">Upload</label>
                                <input name="endorse" type="file" id="endorsementUpload" class="file-input" accept=".pdf">
                                <div class="pdf-banner">
                                    <p class="pdf-banner-text">
                                        <span class="pdf-banner-content">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="22px" viewBox="0 -960 960 960" width="22px" fill="#666666"><path d="M408-528h24q20.4 0 34.2-13.8Q480-555.6 480-576v-24q0-20.4-13.8-34.2Q452.4-648 432-648h-48q-9.6 0-16.8 7.2-7.2 7.2-7.2 16.8v144q0 9.6 7.2 16.8 7.2 7.2 16.8 7.2 9.6 0 16.8-7.2 7.2-7.2 7.2-16.8v-48Zm0-48v-24h24v24h-24Zm168 120q20.4 0 34.2-13.8Q624-483.6 624-504v-96q0-20.4-13.8-34.2Q596.4-648 576-648h-48q-9.6 0-16.8 7.2-7.2 7.2-7.2 16.8v144q0 9.6 7.2 16.8 7.2 7.2 16.8 7.2h48Zm-24-48v-96h24v96h-24Zm144-24h24.29q9.71 0 16.71-7.2t7-16.8q0-9.6-7.2-16.8-7.2-7.2-16.8-7.2h-24v-24h24.29q9.71 0 16.71-7.2t7-16.8q0-9.6-7.24-16.8-7.23-7.2-16.88-7.2h-48.23q-9.65 0-16.65 7.2-7 7.2-7 16.8v144q0 9.6 7.2 16.8 7.2 7.2 16.8 7.2 9.6 0 16.8-7.2 7.2-7.2 7.2-16.8v-48ZM312-240q-29.7 0-50.85-21.15Q240-282.3 240-312v-480q0-29.7 21.15-50.85Q282.3-864 312-864h480q29.7 0 50.85 21.15Q864-821.7 864-792v480q0 29.7-21.15 50.85Q821.7-240 792-240H312ZM168-96q-29.7 0-50.85-21.15Q96-138.3 96-168v-516q0-15.3 10.29-25.65Q116.58-720 131.79-720t25.71 10.35Q168-699.3 168-684v516h516q15.3 0 25.65 10.29Q720-147.42 720-132.21t-10.35 25.71Q699.3-96 684-96H168Z"/></svg>
                                            <span class="pdf-text">Only accept file of pdf with limit of 5mb</span>
                                        </span>
                                    </p>
                                </div>
                                <span class="error-message" style="display: none;">This field is required</span>
                            </div>
                        </div>
                    </div>

            </div>

            
    
            <!-- Step 2: Internship Assessment -->
            <div class="step-content" id="step2-content" style="display: none;">

                <div class="assessment-container">
                    <div class="assessment-title">Applicant Assessment</div>

                    
                    <?php
                    // Condition for the tip section
                    if ($forms_result && $forms_result->num_rows > 0): ?>
                        <div class="tip">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#002b7f">
                                <path d="M478-240q21 0 35.5-14.5T528-290q0-21-14.5-35.5T478-340q-21 0-35.5 14.5T428-290q0 21 14.5 35.5T478-240Zm2 160q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm4-572q25 0 43.5 16t18.5 40q0 22-13.5 39T502-525q-23 20-40.5 44T444-427q0 14 10.5 23.5T479-394q15 0 25.5-10t13.5-25q4-21 18-37.5t30-31.5q23-22 39.5-48t16.5-58q0-51-41.5-83.5T484-720q-38 0-72.5 16T359-655q-7 12-4.5 25.5T368-609q14 8 29 5t25-17q11-15 27.5-23t34.5-8Z"/>
                            </svg>
                            Applicant will be given an assessment examination to test your capabilities.
                        </div>
                        <div class="heading">Assessment Questionaire</div>
                        <div class="assessment-completed-message"id="assessment-completed-message">
                            <h2>
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2d6a2d"><path d="m508-512-58-57q-11-11-27.5-11T394-568q-11 11-11 28t11 28l86 86q12 12 28 12t28-12l170-170q12-12 11.5-28T706-652q-12-12-28.5-12.5T649-653L508-512ZM320-240q-33 0-56.5-23.5T240-320v-480q0-33 23.5-56.5T320-880h480q33 0 56.5 23.5T880-800v480q0 33-23.5 56.5T800-240H320ZM160-80q-33 0-56.5-23.5T80-160v-520q0-17 11.5-28.5T120-720q17 0 28.5 11.5T160-680v520h520q17 0 28.5 11.5T720-120q0 17-11.5 28.5T680-80H160Z"/></svg>
                                APPLICANT ASSESSMENT COMPLETED
                            </h2>
                        </div>
                    <?php endif; ?>





                    
                    <?php if ($forms_result && $forms_result->num_rows > 0): ?>
                        <div class="student-assessment-container">
                            <?php while ($form = $forms_result->fetch_assoc()): ?>
                                <!-- Assessment Header -->
                                <div class="student-assessment-header">
                                    <div class="student-assessment-title">
                                        <h3><?php echo htmlspecialchars($form['title']); ?></h3>
                                        <div class="score-container">
                                            <input type="text" class="input-hide" name="assessment_score" id="assessment-score-<?php echo $form['form_id']; ?>" value="0/0">
                                            <input type="text" class="input-hide" name="all_questions" id="all-questions-<?php echo $form['form_id']; ?>" value="">
                                            <input type="text" class="input-hide" name="paragraph_answers" id="paragraph-answers-<?php echo $form['form_id']; ?>" value="">
                                            <div id="score-display-<?php echo $form['form_id']; ?>" class="score-display"></div>
                                        </div>
                                    </div>
                                    <button type="button" class="student-start-button" onclick="startAssessment(<?php echo $form['form_id']; ?>)">
                                        START ASSESSMENT<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M160-520q-33 0-56.5-23.5T80-600v-120q0-33 23.5-56.5T160-800h320q17 0 28.5 11.5T520-760v200q0 17-11.5 28.5T480-520H160Zm0 360q-33 0-56.5-23.5T80-240v-120q0-33 23.5-56.5T160-440h400q17 0 28.5 11.5T600-400v200q0 17-11.5 28.5T560-160H160Zm520-360h-40q-17 0-28.5-11.5T600-560v-200q0-17 11.5-28.5T640-800h181q21 0 33 17.5t4 37.5l-58 145h18q22 0 33.5 18.5T854-543L718-173q-3 8-9.5 10.5t-12.5.5q-6-2-11-6.5t-5-12.5v-339ZM250-660q0-13-8.5-21.5T220-690q-13 0-21.5 8.5T190-660q0 13 8.5 21.5T220-630q13 0 21.5-8.5T250-660Zm-30 390q13 0 21.5-8.5T250-300q0-13-8.5-21.5T220-330q-13 0-21.5 8.5T190-300q0 13 8.5 21.5T220-270Z"/></svg>
                                    </button>
                                </div>
                                <!-- Modal Structure -->
                                <div id="student-questionModal-<?php echo $form['form_id']; ?>" class="student-modal">
                                    <div class="student-modal-content">
                                        <div class="student-form-header">
                                            <h2><?php echo htmlspecialchars($form['title']); ?></h2>
                                            <p class="student-form-description"><?php echo htmlspecialchars($form['description']); ?></p>
                                        </div>
                                        <div class="student-progress-bar">
                                            <div id="student-progress-<?php echo $form['form_id']; ?>" class="student-progress"></div>
                                        </div>
                                        <form id="student-assessment-form-<?php echo $form['form_id']; ?>">
                                            <div id="student-question-container-<?php echo $form['form_id']; ?>">
                                                <!-- Questions will be dynamically inserted here -->
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <?php
                                    // Fetch questions and store them in a data attribute
                                    $questions_query = "SELECT * FROM assessment_questions WHERE form_id = ? ORDER BY question_order";
                                    $stmt = $conn->prepare($questions_query);
                                    $stmt->bind_param("i", $form['form_id']);
                                    $stmt->execute();
                                    $questions_result = $stmt->get_result();
                                    $questions = array();

                                    while ($question = $questions_result->fetch_assoc()) {
                                        // Fetch options for this question
                                        $options_query = "SELECT * FROM assessment_options WHERE question_id = ? ORDER BY option_order";
                                        $stmt2 = $conn->prepare($options_query);
                                        $stmt2->bind_param("i", $question['question_id']);
                                        $stmt2->execute();
                                        $options_result = $stmt2->get_result();
                                        $options = array();

                                        while ($option = $options_result->fetch_assoc()) {
                                            $options[] = $option;
                                        }

                                        $question['options'] = $options;
                                        $questions[] = $question;
                                    }
                                ?>
                                <script>
                                    // Store questions data for this form
                                    window.questionData = window.questionData || {};
                                    window.questionData[<?php echo $form['form_id']; ?>] = <?php echo json_encode($questions); ?>;
                                </script>
                            <?php endwhile; ?>
                        </div>

                    <?php endif; ?>

                    <?php 
                    // Condition for the exam banner
                    if ($forms_result && $forms_result->num_rows > 0): ?>
                        <div class="exam-banner">
                            <p class="exam-banner-text">
                                <span class="exam-banner-content">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#666666">
                                        <path d="M479.79-288q15.21 0 25.71-10.29t10.5-25.5q0-15.21-10.29-25.71t-25.5-10.5q-15.21 0-25.71 10.29t-10.5 25.5q0 15.21 10.29 25.71t25.5 10.5ZM444-432h72v-240h-72v240Zm36.28 336Q401-96 331-126t-122.5-82.5Q156-261 126-330.96t-30-149.5Q96-560 126-629.5q30-69.5 82.5-122T330.96-834q69.96-30 149.5-30t149.04 30q69.5 30 122 82.5T834-629.28q30 69.73 30 149Q864-401 834-331t-82.5 122.5Q699-156 629.28-126q-69.73 30-149 30Z"/>
                                    </svg>
                                    <span class="exam-text">Upon clicking the exam, each questions have countdown for each difficulty.</span>
                                </span>
                            </p>
                        </div>
                        <div class="assessment-validate-message"id="assessment-validate-message">
                            <h2>
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#EA3323"><path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm0-160q17 0 28.5-11.5T520-480v-160q0-17-11.5-28.5T480-680q-17 0-28.5 11.5T440-640v160q0 17 11.5 28.5T480-440ZM363-120q-16 0-30.5-6T307-143L143-307q-11-11-17-25.5t-6-30.5v-234q0-16 6-30.5t17-25.5l164-164q11-11 25.5-17t30.5-6h234q16 0 30.5 6t25.5 17l164 164q11 11 17 25.5t6 30.5v234q0 16-6 30.5T817-307L653-143q-11 11-25.5 17t-30.5 6H363Z"/></svg>
                                COMPLETE APPLICANT ASSESSMENT TO PROCEED
                            </h2>
                        </div>
                    <?php endif; ?>



                    <?php
                    // Condition for the tip section
                    if ($forms_result && $forms_result->num_rows > 0): ?>

                    <?php endif; ?>

                </div>
                <script>
                    let currentQuestionIndex = 0;
                    let activeTimer = null;
                    let currentFormId = null;
                    let answers = {};
                    let randomizedQuestions = [];

                    function startAssessment(formId) {      
                            currentFormId = formId;
                            currentQuestionIndex = 0;
                            answers = {};
                            
                            const modal = document.getElementById(`student-questionModal-${formId}`);
                            if (!modal) {
                                console.error(`Modal not found: student-questionModal-${formId}`);
                                return;
                            }
                            
                            // Get all questions for this form
                            const allQuestions = window.questionData[formId];
                            
                            // Separate questions by type and difficulty
                            const basicQuestions = allQuestions.filter(q => q.difficulty === 'basic' && q.question_type !== 'paragraph');
                            const intermediateQuestions = allQuestions.filter(q => q.difficulty === 'intermediate' && q.question_type !== 'paragraph');
                            const difficultQuestions = allQuestions.filter(q => q.difficulty === 'difficult' && q.question_type !== 'paragraph');
                            const paragraphQuestions = allQuestions.filter(q => q.question_type === 'paragraph');

                            // Function to shuffle array
                            const shuffleArray = array => {
                                const shuffled = [...array];
                                for (let i = shuffled.length - 1; i > 0; i--) {
                                    const j = Math.floor(Math.random() * (i + 1));
                                    [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
                                }
                                return shuffled;
                            };

                            // Random selection of questions while maintaining difficulty sequence
                            const selectedQuestions = {
                                basic: shuffleArray(basicQuestions).slice(0, 5),
                                intermediate: shuffleArray(intermediateQuestions).slice(0, 5),
                                difficult: shuffleArray(difficultQuestions).slice(0, 4)
                            };

                            // Calculate total scorable questions (excluding paragraph questions)
                            window.totalScorableQuestions = selectedQuestions.basic.length + 
                                                        selectedQuestions.intermediate.length + 
                                                        selectedQuestions.difficult.length;

                            // Combine questions in order: basic -> intermediate -> difficult -> paragraph
                            randomizedQuestions = [
                                ...selectedQuestions.basic,           
                                ...selectedQuestions.intermediate,    
                                ...selectedQuestions.difficult,
                                ...paragraphQuestions     // Include paragraph questions at the end
                            ];
                            
                            modal.style.display = 'block';
                            updateProgressBar(formId, 0);
                            displayQuestion(formId, 0);
                            window.onclick = null;
                        }

                        function getTimerDuration(difficulty) {
                            switch(difficulty.toLowerCase()) {
                                case 'basic': return 20;
                                case 'intermediate': return 30;
                                case 'difficult': return 60;
                                default: return 20;
                            }
                        }

                        function updateProgressBar(formId, questionIndex) {
                            const totalQuestions = randomizedQuestions.length; // Count all questions including paragraphs
                            const progressElement = document.getElementById(`student-progress-${formId}`);
                            
                            if (!progressElement) {
                                console.error(`Progress element not found: student-progress-${formId}`);
                                return;
                            }
                            
                            // Calculate progress based on current question index, including paragraph questions
                            const progressPercentage = ((questionIndex + 1) / totalQuestions) * 100;
                            progressElement.style.width = `${progressPercentage}%`;
                        }

                        function displayQuestion(formId, questionIndex) {
                            if (!randomizedQuestions || !randomizedQuestions[questionIndex]) {
                                console.error('Questions data not found or invalid index');
                                return;
                            }

                            const question = randomizedQuestions[questionIndex];
                            const container = document.getElementById(`student-question-container-${formId}`);
                            
                            if (!container) {
                                console.error(`Question container not found: student-question-container-${formId}`);
                                return;
                            }

                            updateProgressBar(formId, questionIndex);

                            let html = `
                                <div class="student-question-container">
                                    <div class="student-difficulty-badge student-difficulty-${question.difficulty.toLowerCase()}">
                                        ${question.difficulty.toUpperCase()}
                                    </div>
                                    <div class="student-assessment-timer" id="student-timer-${formId}">Time remaining: <span id="student-time-left-${formId}"></span>s</div>
                                    <div class="student-question-text">
                                        ${question.title}
                                    </div>
                                </div>
                            `;

                            if (question.question_type === 'paragraph') {
                                html += `
                                    <textarea 
                                        class="student-paragraph-answer" 
                                        name="question[${question.question_id}]"
                                        placeholder="Type your answer here..."
                                        ${question.is_required ? 'required' : ''}
                                    ></textarea>
                                `;
                            } else {
                                html += '<div class="student-option-list">';
                                question.options.forEach(option => {
                                    const inputType = question.question_type === 'multiple-choice' ? 'radio' : 'checkbox';
                                    html += `
                                        <div class="student-option-item">
                                            <label for="student-option-${option.option_id}">
                                                <input 
                                                    type="${inputType}" 
                                                    id="student-option-${option.option_id}"
                                                    name="question[${question.question_id}]${inputType === 'checkbox' ? '[]' : ''}"
                                                    value="${option.option_id}"
                                                    ${question.is_required && inputType === 'radio' ? 'required' : ''}
                                                >
                                                ${option.option_text}
                                            </label>
                                        </div>
                                    `;
                                });
                                html += '</div>';
                            }

                            html += `
                                <div>
                                    ${questionIndex < randomizedQuestions.length - 1 ? 
                                        `<button type="button" onclick="nextQuestion(${formId})" class="student-submit-button">Next Question</button>` :
                                        `<button type="button" onclick="submitAssessment(event, ${formId})" class="student-submit-button">Submit Assessment</button>`
                                    }
                                </div>
                            `;

                            container.innerHTML = html;

                            const duration = getTimerDuration(question.difficulty);
                            startTimer(duration, formId, questionIndex);
                        }

                        function startTimer(duration, formId, questionIndex) {
                            if (activeTimer) {
                                clearInterval(activeTimer);
                            }

                        const display = document.getElementById(`student-time-left-${formId}`);
                        const timerElement = document.getElementById(`student-timer-${formId}`);
                        
                        if (!display || !timerElement) {
                            console.error('Timer elements not found');
                            return;
                        }

                        let timer = duration;
                        display.textContent = timer;
                        
                        // Set initial color
                        updateTimerColor(timerElement, timer, duration);

                        activeTimer = setInterval(() => {
                            timer--;
                            display.textContent = timer;
                            
                            // Update color based on remaining time
                            updateTimerColor(timerElement, timer, duration);
                            
                            if (timer < 0) {
                                clearInterval(activeTimer);
                                if (questionIndex < randomizedQuestions.length - 1) {
                                    nextQuestion(formId);
                                } else {
                                    // If it's the last question, automatically submit
                                    submitAssessment(new Event('autosubmit'), formId);
                                }
                            }
                        }, 1000);
                    }

                    function updateTimerColor(timerElement, currentTime, totalTime) {
                        timerElement.classList.remove('student-timer-normal', 'student-timer-warning', 'student-timer-danger');
                        
                        const percentageLeft = (currentTime / totalTime) * 100;
                        
                        if (percentageLeft > 50) {
                            timerElement.classList.add('student-timer-normal');
                        } else if (percentageLeft > 25) {
                            timerElement.classList.add('student-timer-warning');
                        } else {
                            timerElement.classList.add('student-timer-danger');
                        }
                    }

                    function nextQuestion(formId) {
                        const container = document.getElementById(`student-question-container-${formId}`);
                        const inputs = container.querySelectorAll('input, textarea');
                        
                        inputs.forEach(input => {
                            if (input.type === 'radio' || input.type === 'checkbox') {
                                if (input.checked) {
                                    if (input.type === 'checkbox') {
                                        if (!answers[input.name]) {
                                            answers[input.name] = [];
                                        }
                                        answers[input.name].push(input.value);
                                    } else {
                                        answers[input.name] = input.value;
                                    }
                                }
                            } else {
                                answers[input.name] = input.value;
                            }
                        });

                        currentQuestionIndex++;
                        updateProgressBar(formId, currentQuestionIndex);
                        displayQuestion(formId, currentQuestionIndex);
                    }

                    function submitAssessment(event, formId) {
                        event.preventDefault();

                        const container = document.getElementById(`student-question-container-${formId}`);
                        const inputs = container.querySelectorAll('input, textarea');

                        inputs.forEach(input => {
                            if (input.type === 'radio' || input.type === 'checkbox') {
                                if (input.checked) {
                                    if (input.type === 'checkbox') {
                                        if (!answers[input.name]) {
                                            answers[input.name] = [];
                                        }
                                        answers[input.name].push(input.value);
                                    } else {
                                        answers[input.name] = input.value;
                                    }
                                }
                            } else {
                                answers[input.name] = input.value;
                            }
                        });

                        const finalFormData = new FormData();
                        Object.entries(answers).forEach(([key, value]) => {
                            if (Array.isArray(value)) {
                                value.forEach(val => finalFormData.append(key, val));
                            } else {
                                finalFormData.append(key, value);
                            }
                        });

                        fetch('questionaire_submit_answers.php', {
                            method: 'POST',
                            body: finalFormData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const score = data.score;
                                const scoreFormat = `${score.correct_answers}/${score.total_questions}`;

                                // Update the hidden input and display the score
                                const scoreInput = document.getElementById(`assessment-score-${formId}`);
                                const scoreDisplay = document.getElementById(`score-display-${formId}`);
                                if (scoreInput && scoreDisplay) {
                                    scoreInput.value = scoreFormat;
                                    scoreDisplay.innerHTML = `Last Score: ${scoreFormat}`;
                                    scoreDisplay.style.display = 'block';
                                }

                                const modal = document.getElementById(`student-questionModal-${formId}`);
                                modal.style.display = 'none';

                                // Show the "Assessment Completed" message and hide the validation message
                                const completedMessage = document.getElementById('assessment-completed-message');
                                const validateMessage = document.getElementById('assessment-validate-message');
                                if (completedMessage) {
                                    completedMessage.classList.add('visible');
                                    // Hide the validation message when showing completion message
                                    if (validateMessage) {
                                        validateMessage.style.display = 'none';
                                    }
                                }

                                // Hide the student-assessment-container but keep the score-container visible
                                const assessmentContainer = document.querySelector('.student-assessment-container');
                                if (assessmentContainer) {
                                    assessmentContainer.classList.add('hidden');
                                }

                                // Reset answers and container
                                answers = {};
                                container.innerHTML = '';

                                // Clear any active timer
                                if (activeTimer) {
                                    clearInterval(activeTimer);
                                }
                            } else {
                                console.error('Error submitting assessment:', data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                    }

                </script>

                <div class="video-section">
                            <div class="heading">Demo video of your portfolio</div>
                            <div class="text-description">Add a short video of your portfolio</div>
                            
                            <div class="drop-zone" id="dropZone">
                                <input name="demo" type="file" id="fileInput" accept="video/*" style="display: none">
                                <svg id="dropZoneIcon" xmlns="http://www.w3.org/2000/svg" height="200px" viewBox="0 -960 960 960" width="200px" fill="#e0e0e0"><path d="m418-332 202-129q11-7 11-19t-11-19L418-628q-11-8-23-1.5T383-609v258q0 14 12 20.5t23-1.5ZM112-450q14 0 23.5 8.5T148-419q6 30 17 59t28 55q8 12 7 25t-11 23q-10 9-22.5 8.5T146-260q-24-35-39.5-75T85-417q-2-13 6-23t21-10Zm77-274q10 10 11.5 23.5T194-675q-17 26-28.5 55T148-561q-3 13-12.5 22t-23.5 9q-13 0-21.5-9.5T84-562q6-42 21.5-82.5T146-720q8-11 20.5-12t22.5 8Zm59 528q10-11 24-12.5t27 6.5q26 15 54.5 27t57.5 20q13 3 21.5 13t8.5 23q0 12-10 19.5T409-94q-42-8-81-22.5T254-153q-11-7-13-20t7-23Zm196-665q0 13-8.5 23T414-825q-30 7-58 18.5T302-779q-13 8-27.5 7T249-784q-10-10-8-23t13-21q36-23 76-36.5t83-21.5q12-2 21.5 5.5T444-861Zm376 371q0-122-76.5-216T549-826q-11-2-18-12.5t-7-22.5q0-12 9-20t21-6q143 25 234.5 137T880-490q0 148-91.5 259.5T554-93q-12 2-21-6t-9-20q0-12 7-22.5t18-12.5q118-26 194.5-120T820-490Z"/></svg>
                            </div>
                            

                            <div class="note-banner">
                                <p class="note-banner-text">
                                    <span class="note-banner-content">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="22px" viewBox="0 -960 960 960" width="22px" fill="#666666"><path d="M168-192q-29 0-50.5-21.5T96-264v-432q0-29.7 21.5-50.85Q139-768 168-768h216l96 96h312q29.7 0 50.85 21.15Q864-629.7 864-600v336q0 29-21.15 50.5T792-192H168Zm312-96q60 0 102-42t42-102q0-60-42-102t-102-42q-60 0-102 42t-42 102q0 60 42 102t102 42Zm-48-227q11-6 22.63-9.5Q466.26-528 480-528q40.32 0 68.16 27.84Q576-472.32 576-432q0 13.74-3.5 25.37T563-384L432-515Zm48 179q-40.32 0-68.16-27.84Q384-391.68 384-432q0-13.74 3.5-25.37T397-480l131 131q-11 6-22.63 9.5Q493.74-336 480-336Z"/></svg>
                                        <span class="text">Maximum size of uploaded video is 25mb.</span>
                                    </span>
                                </p>
                            </div>


                                    
                            <div class="input-section">
                            <div class="input-label">
                                Portfolio Link<span class="tag-optional">(Recommended)</span>
                            </div>
                            <div class="text-description">Add the website link of your portfolio</div>
                            <input name="portfolio-link" type="text" id="portfolioLink" class="link-input" placeholder="e.g. https://portfolio.com/username">
                            </div>
                </div> 

            </div>
            
            <!-- VIDEO SCRIPT -->
            <script>
                document.getElementById("dropZone").addEventListener("click", function() {
                    document.getElementById("fileInput").click();
                });

                document.getElementById("fileInput").addEventListener("change", function(event) {
                    const file = event.target.files[0];
                    const dropZone = document.getElementById("dropZone");
                    const noteBanner = document.querySelector(".note-banner");
                    const maxSize = 25 * 1024 * 1024; // 25MB in bytes
                    
                    // Remove any existing error message
                    const existingError = document.querySelector(".video-error");
                    if (existingError) {
                        existingError.remove();
                    }
                    
                    // Function to show error message
                    const showError = (message) => {
                        const errorDiv = document.createElement("div");
                        errorDiv.className = "video-error";
                        errorDiv.style.cssText = `
                            color: red;
                            margin: 5px 0 0 0;
                            font-size: 15px;

                        `;
                        errorDiv.textContent = message;
                        // Insert after the note banner
                        noteBanner.parentNode.insertBefore(errorDiv, noteBanner.nextSibling);
                    };
                    
                    if (file) {
                        // Check file type
                        if (!file.type.startsWith("video/mp4")) {
                            showError("Only accept file of mp4");
                            this.value = ""; // Clear the input
                            return;
                        }
                        
                        // Check file size
                        if (file.size > maxSize) {
                            showError("Video uploaded exceeded the 25mb limit");
                            this.value = ""; // Clear the input
                            return;
                        }
                        
                        // If file passes validation, create video preview
                        const videoUrl = URL.createObjectURL(file);
                        dropZone.style.border = "3px solid #5A6478";
                        dropZone.classList.add('has-video');
                        
                        document.getElementById("dropZoneIcon").style.display = "none";

                        const existingVideoWrapper = document.querySelector(".video-wrapper");
                        if (existingVideoWrapper) {
                            existingVideoWrapper.remove();
                        }

                        const videoElement = document.createElement("video");
                        videoElement.src = videoUrl;
                        videoElement.controls = true;
                        videoElement.autoplay = true;
                        videoElement.loop = true;
                        videoElement.style.maxWidth = "100%";
                        videoElement.style.maxHeight = "100%";
                        videoElement.style.objectFit = "contain";

                        const videoWrapper = document.createElement("div");
                        videoWrapper.className = "video-wrapper";
                        
                        videoElement.addEventListener('loadedmetadata', function() {
                            const videoAspectRatio = videoElement.videoWidth / videoElement.videoHeight;
                            dropZone.style.aspectRatio = `${videoElement.videoWidth}/${videoElement.videoHeight}`;
                            
                            const containerWidth = dropZone.offsetWidth;
                            const calculatedHeight = containerWidth / videoAspectRatio;
                            
                            const maxHeight = 800;
                            const minHeight = 300;
                            
                            dropZone.style.height = `${Math.min(Math.max(calculatedHeight, minHeight), maxHeight)}px`;
                        });

                        videoWrapper.appendChild(videoElement);
                        dropZone.appendChild(videoWrapper);
                    }
                });

            </script>
            <!-- VIDEO SCRIPT -->

            <!-- Step 3: Final Review -->
            <div class="step-content" id="step3-content" style="display: none;">
                <div class="step3">
                    <div class="final-review-title">Final Review</div>
                
                    <div class="personal-info-card">
                        <div class="personal-details">
                            <p class="fullname"></p>
                            <p class="address">
                            <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#2E3849"><path d="M480-197.46q117.33-105.08 177.23-192.09 59.9-87.02 59.9-160.41 0-103.31-67.67-171.24t-169.47-67.93q-101.79 0-169.46 67.93-67.66 67.93-67.66 171.24 0 73.39 59.9 160.29 59.9 86.89 177.23 192.21Zm-.06 85.16q-13.9 0-26.25-4.74-12.36-4.74-24.04-14.22-41.43-35.72-88.89-82.96-47.46-47.24-88.05-101.71-40.6-54.48-66.72-114.06-26.12-59.58-26.12-119.97 0-137.28 91.45-229.72 91.45-92.45 228.68-92.45 136.23 0 228.18 92.45 91.95 92.44 91.95 229.72 0 60.39-26.62 120.47t-66.72 114.56q-40.09 54.47-87.55 101.21-47.46 46.74-88.89 82.46-11.71 9.48-24.11 14.22-12.4 4.74-26.3 4.74ZM480-552Zm0 74.39q31.2 0 52.79-21.6 21.6-21.59 21.6-52.79t-21.6-52.79q-21.59-21.6-52.79-21.6t-52.79 21.6q-21.6 21.59-21.6 52.79t21.6 52.79q21.59 21.6 52.79 21.6Z"/></svg>
                            </p>
                            <p class="contact">
                            <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#2E3849"><path d="M763.48-136.87q-122.44-9-232.37-60.1-109.94-51.1-197.37-138.29-87.44-87.44-138.03-197.49-50.6-110.05-59.6-232.49-2-24.35 14.65-42.12 16.65-17.77 41-17.77h135.76q22.5 0 37.87 12.53 15.37 12.53 20.81 33.56l23.76 101.97q2.95 16.59-1.38 31.22-4.34 14.63-15.21 24.78l-98.44 96.09q19.05 35.37 43.61 68.46 24.57 33.09 55.61 63.13 28.81 28.8 60.42 52.63 31.6 23.83 66.26 41.91L621.5-395.8q10.63-10.4 25.02-14.37 14.39-3.98 30.98-1.03l100.54 22.29q22.03 6.43 34.06 21.44 12.03 15.01 12.03 37.04v137.67q0 24.35-18.27 41.12-18.27 16.77-42.38 14.77Z"/></svg>
                            </p>
                            <p class="email">
                            <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#2E3849"><path d="M479.76-87.87q-80.93 0-152.12-30.6-71.18-30.6-124.88-84.29-53.69-53.7-84.29-125.11-30.6-71.41-30.6-152.61 0-81.19 30.6-152.13 30.6-70.93 84.29-124.63 53.7-53.69 125.11-84.29 71.41-30.6 152.61-30.6 81.19 0 152.13 30.6 70.93 30.6 124.63 84.29 53.69 53.7 84.29 124.57 30.6 70.87 30.6 152.43v60q0 56.44-40.41 96.13-40.42 39.7-97.57 39.7-34.77 0-63.96-17.24-29.19-17.24-49.35-46.2-26.88 29.96-63.62 46.7-36.74 16.74-77.22 16.74-81.35 0-138.47-57.19-57.12-57.18-57.12-138.63 0-81.44 57.19-138.4 57.18-56.96 138.63-56.96 81.44 0 138.4 57.12 56.96 57.12 56.96 138.47v57.85q0 24.56 17.26 41.56 17.26 17 41.54 17 24.27 0 41.3-17 17.03-17 17.03-41.56v-58.09q0-130-91.36-221.24Q610-792.72 480-792.72t-221.36 91.36Q167.28-610 167.28-480t91.24 221.36q91.24 91.36 221.59 91.36h152.3q16.83 0 28.21 11.32Q672-144.64 672-127.91q0 16.65-11.38 28.34-11.38 11.7-28.21 11.7H479.76Zm.28-275.72q48.53 0 82.45-33.96 33.92-33.97 33.92-82.49 0-48.53-33.96-82.45-33.97-33.92-82.49-33.92-48.53 0-82.45 33.96-33.92 33.97-33.92 82.49 0 48.53 33.96 82.45 33.97 33.92 82.49 33.92Z"/></svg>
                            </p>

                        </div>
                    </div>
                
                    <div class="review-section-title">Documents Uploaded</div>
                    <div class="uploaded-document">
                        <div class="docu-text">Resume</div>
                        <p class="cv"></p>
                    </div>
                    
                    <div class="uploaded-document">
                        <div class="docu-text">Endorsement Letter</div>
                        <p class="endorse"></p>
                    </div>

                    <hr>



                    <?php
                    // Condition for the tip section
                    if ($forms_result && $forms_result->num_rows > 0): ?>

                    <div class="uploaded-document">
                    <div class="review-section-title">Applicant Assessment</div>
                    <div class="docu-text">Result</div>
                    <div class="assessment-status">ASSESSMENT EXAMINATION COMPLETED <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#1e7e34"><path d="m423.28-416.37-79.78-79.78q-12.43-12.44-31.35-12.44-18.91 0-31.35 12.44-12.43 12.43-12.31 31.35.12 18.91 12.55 31.34l110.18 110.18q13.76 13.67 32.11 13.67 18.34 0 32.02-13.67L677.76-545.7q12.44-12.43 12.44-31.22 0-18.8-12.44-31.23-12.43-12.44-31.35-12.44-18.91 0-31.34 12.44L423.28-416.37ZM480-71.87q-84.91 0-159.34-32.12-74.44-32.12-129.5-87.17-55.05-55.06-87.17-129.5Q71.87-395.09 71.87-480t32.12-159.34q32.12-74.44 87.17-129.5 55.06-55.05 129.5-87.17 74.43-32.12 159.34-32.12t159.34 32.12q74.44 32.12 129.5 87.17 55.05 55.06 87.17 129.5 32.12 74.43 32.12 159.34t-32.12 159.34q-32.12 74.44-87.17 129.5-55.06 55.05-129.5 87.17Q564.91-71.87 480-71.87Zm0-91q133.04 0 225.09-92.04 92.04-92.05 92.04-225.09 0-133.04-92.04-225.09-92.05-92.04-225.09-92.04-133.04 0-225.09 92.04-92.04 92.05-92.04 225.09 0 133.04 92.04 225.09 92.05 92.04 225.09 92.04ZM480-480Z"></path></svg></div>
                    </div>

                    <hr>
                    <?php endif; ?>

                    <div class="review-section-title">Portfolio</div>
                    <div class="uploaded-document">
                        <div class="docu-text">Link</div>
                        <a href="#" class="portfolio-url"></a>
                    </div>

                    <div class="uploaded-document">
                        <div class="docu-text">Demo Video</div>
                        <p class="mp4"></p>
                    </div>

                    <hr>

                    </div>
                    
                
                    <div class="terms-policies-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 -960 960 960" width="20" fill="#1A73E8">
                            <path d="M480-280q83 0 141.5-58.5T680-480q0-83-58.5-141.5T480-680q-83 0-141.5 58.5T280-480q0 83 58.5 141.5T480-280Zm0 200q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                        </svg>
                        <label for="terms-acceptance" class="terms-policies-text">
                            By applyng, you agree to abide by our
                            <a href="#" class="terms-link">Terms</a> and
                            <a href="#" class="privacy-link">Conditions</a>.
                        </label>
                    </div>
            </div> 

            <div class="navigation-buttons">
                <button type="button" id="previousButton" class="nav-button previous-button" disabled><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="m318.98-480 151.41 152.17q12.68 12.68 13.06 31.45.38 18.77-13.06 32.21-12.67 12.67-31.82 12.67-19.16 0-31.83-12.67L222.98-447.93q-6.72-6.72-9.82-14.92-3.09-8.19-3.09-17.15 0-8.96 3.09-17.15 3.1-8.2 9.82-14.92l183.76-183.76q12.67-12.67 31.44-13.05 18.78-.38 32.21 13.05 12.68 12.68 12.68 31.83t-12.68 31.83L318.98-480Zm267.11 0 151.17 152.17q12.67 12.68 13.06 31.45.38 18.77-13.06 32.21-12.67 12.67-31.83 12.67-19.15 0-31.82-12.67L489.85-447.93q-6.72-6.72-9.82-14.92-3.1-8.19-3.1-17.15 0-8.96 3.1-17.15 3.1-8.2 9.82-14.92l183.76-183.76q12.67-12.67 31.44-13.05 18.78-.38 32.21 13.05 12.67 12.68 12.67 31.83t-12.67 31.83L586.09-480Z"/></svg>Previous</button>
                <button type="button" id="continueButton" class="nav-button continue-button">Continue <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M373.91-480 222.74-632.17q-12.67-12.68-13.06-31.45-.38-18.77 13.06-32.21 12.67-12.67 31.83-12.67 19.15 0 31.82 12.67l183.76 183.76q6.72 6.72 9.82 14.92 3.1 8.19 3.1 17.15 0 8.96-3.1 17.15-3.1 8.2-9.82 14.92L286.39-264.17q-12.67 12.67-31.44 13.05-18.78.38-32.21-13.05-12.67-12.68-12.67-31.83t12.67-31.83L373.91-480Zm267.11 0L489.61-632.17q-12.68-12.68-13.06-31.45-.38-18.77 13.06-32.21 12.67-12.67 31.82-12.67 19.16 0 31.83 12.67l183.76 183.76q6.72 6.72 9.82 14.92 3.09 8.19 3.09 17.15 0 8.96-3.09 17.15-3.1 8.2-9.82 14.92L553.26-264.17q-12.67 12.67-31.44 13.05-18.78.38-32.21-13.05-12.68-12.68-12.68-31.83t12.68-31.83L641.02-480Z"/></svg></button>
            </div>
        </form>
        <script>
            // Function to update the review section with form data
            function updateReviewSection(event) {
                // Get input values
                const firstName = document.getElementById('firstName').value;
                const lastName = document.getElementById('lastName').value;
                const homeLocation = document.getElementById('homeLocation').value;
                const phone = document.getElementById('phoneNumber').value;
                const email = document.getElementById('email').value;
                

                // Portfolio link input
                const portfolioLink = document.querySelector('.link-input').value;

                // Demo video input
                const demoVideoInput = document.getElementById('fileInput');

                // Update the review section
                // Full name
                const fullNameElement = document.querySelector('.personal-info-card .fullname');
                fullNameElement.textContent = `${firstName} ${lastName}`;

                // Address
                const addressElement = document.querySelector('.personal-info-card .address');
                addressElement.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#2E3849"><path d="M480-197.46q117.33-105.08 177.23-192.09 59.9-87.02 59.9-160.41 0-103.31-67.67-171.24t-169.47-67.93q-101.79 0-169.46 67.93-67.66 67.93-67.66 171.24 0 73.39 59.9 160.29 59.9 86.89 177.23 192.21Zm-.06 85.16q-13.9 0-26.25-4.74-12.36-4.74-24.04-14.22-41.43-35.72-88.89-82.96-47.46-47.24-88.05-101.71-40.6-54.48-66.72-114.06-26.12-59.58-26.12-119.97 0-137.28 91.45-229.72 91.45-92.45 228.68-92.45 136.23 0 228.18 92.45 91.95 92.44 91.95 229.72 0 60.39-26.62 120.47t-66.72 114.56q-40.09 54.47-87.55 101.21-47.46 46.74-88.89 82.46-11.71 9.48-24.11 14.22-12.4 4.74-26.3 4.74ZM480-552Zm0 74.39q31.2 0 52.79-21.6 21.6-21.59 21.6-52.79t-21.6-52.79q-21.59-21.6-52.79-21.6t-52.79 21.6q-21.6 21.59-21.6 52.79t21.6 52.79q21.59 21.6 52.79 21.6Z"/></svg>
                    ${homeLocation}`;

                // Phone
                const contactElement = document.querySelector('.personal-info-card .contact');
                contactElement.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#2E3849"><path d="M763.48-136.87q-122.44-9-232.37-60.1-109.94-51.1-197.37-138.29-87.44-87.44-138.03-197.49-50.6-110.05-59.6-232.49-2-24.35 14.65-42.12 16.65-17.77 41-17.77h135.76q22.5 0 37.87 12.53 15.37 12.53 20.81 33.56l23.76 101.97q2.95 16.59-1.38 31.22-4.34 14.63-15.21 24.78l-98.44 96.09q19.05 35.37 43.61 68.46 24.57 33.09 55.61 63.13 28.81 28.8 60.42 52.63 31.6 23.83 66.26 41.91L621.5-395.8q10.63-10.4 25.02-14.37 14.39-3.98 30.98-1.03l100.54 22.29q22.03 6.43 34.06 21.44 12.03 15.01 12.03 37.04v137.67q0 24.35-18.27 41.12-18.27 16.77-42.38 14.77Z"/></svg>
                    ${phone}`;

                // Email
                const emailElement = document.querySelector('.personal-info-card .email');
                emailElement.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#2E3849"><path d="M479.76-87.87q-80.93 0-152.12-30.6-71.18-30.6-124.88-84.29-53.69-53.7-84.29-125.11-30.6-71.41-30.6-152.61 0-81.19 30.6-152.13 30.6-70.93 84.29-124.63 53.7-53.69 125.11-84.29 71.41-30.6 152.61-30.6 81.19 0 152.13 30.6 70.93 30.6 124.63 84.29 53.69 53.7 84.29 124.57 30.6 70.87 30.6 152.43v60q0 56.44-40.41 96.13-40.42 39.7-97.57 39.7-34.77 0-63.96-17.24-29.19-17.24-49.35-46.2-26.88 29.96-63.62 46.7-36.74 16.74-77.22 16.74-81.35 0-138.47-57.19-57.12-57.18-57.12-138.63 0-81.44 57.19-138.4 57.18-56.96 138.63-56.96 81.44 0 138.4 57.12 56.96 57.12 56.96 138.47v57.85q0 24.56 17.26 41.56 17.26 17 41.54 17 24.27 0 41.3-17 17.03-17 17.03-41.56v-58.09q0-130-91.36-221.24Q610-792.72 480-792.72t-221.36 91.36Q167.28-610 167.28-480t91.24 221.36q91.24 91.36 221.59 91.36h152.3q16.83 0 28.21 11.32Q672-144.64 672-127.91q0 16.65-11.38 28.34-11.38 11.7-28.21 11.7H479.76Zm.28-275.72q48.53 0 82.45-33.96 33.92-33.97 33.92-82.49 0-48.53-33.96-82.45-33.97-33.92-82.49-33.92-48.53 0-82.45 33.96-33.92 33.97-33.92 82.49 0 48.53 33.96 82.45 33.97 33.92 82.49 33.92Z"/></svg>
                    ${email}`;

                // Update uploaded documents section
                const cvInput = document.getElementById('cvUpload');
                const endorsementInput = document.getElementById('endorsementUpload');

                // Update Resume/CV filename with icon
                const cvContainer = document.querySelector('.uploaded-document .cv');
                if (cvInput.files && cvInput.files[0]) {
                    cvContainer.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2E3849"><path d="M202.87-111.87q-37.78 0-64.39-26.61t-26.61-64.39v-554.26q0-37.78 26.61-64.39t64.39-26.61h554.26q37.78 0 64.39 26.61t26.61 64.39v554.26q0 37.78-26.61 64.39t-64.39 26.61H202.87Zm0-91h554.26v-554.26H202.87v554.26Zm0-554.26v554.26-554.26Zm118.56 475.7h200q17 0 28.5-11.5t11.5-28.5q0-17-11.5-28.5t-28.5-11.5h-200q-17 0-28.5 11.5t-11.5 28.5q0 17 11.5 28.5t28.5 11.5Zm0-158.57h317.14q17 0 28.5-11.5t11.5-28.5q0-17-11.5-28.5t-28.5-11.5H321.43q-17 0-28.5 11.5t-11.5 28.5q0 17 11.5 28.5t28.5 11.5Zm0-158.57h317.14q17 0 28.5-11.5t11.5-28.5q0-17-11.5-28.5t-28.5-11.5H321.43q-17 0-28.5 11.5t-11.5 28.5q0 17 11.5 28.5t28.5 11.5Z"/></svg>
                        ${cvInput.files[0].name}`;
                } else {
                    cvContainer.innerHTML = 'No file uploaded';
                }

                // Update Endorsement Letter filename with icon
                const endorsementContainer = document.querySelector('.uploaded-document .endorse');
                if (endorsementInput.files && endorsementInput.files[0]) {
                    endorsementContainer.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2E3849"><path d="M202.87-111.87q-37.78 0-64.39-26.61t-26.61-64.39v-554.26q0-37.78 26.61-64.39t64.39-26.61h554.26q37.78 0 64.39 26.61t26.61 64.39v554.26q0 37.78-26.61 64.39t-64.39 26.61H202.87Zm0-91h554.26v-554.26H202.87v554.26Zm0-554.26v554.26-554.26Zm118.56 475.7h200q17 0 28.5-11.5t11.5-28.5q0-17-11.5-28.5t-28.5-11.5h-200q-17 0-28.5 11.5t-11.5 28.5q0 17 11.5 28.5t28.5 11.5Zm0-158.57h317.14q17 0 28.5-11.5t11.5-28.5q0-17-11.5-28.5t-28.5-11.5H321.43q-17 0-28.5 11.5t-11.5 28.5q0 17 11.5 28.5t28.5 11.5Zm0-158.57h317.14q17 0 28.5-11.5t11.5-28.5q0-17-11.5-28.5t-28.5-11.5H321.43q-17 0-28.5 11.5t-11.5 28.5q0 17 11.5 28.5t28.5 11.5Z"/></svg>
                        ${endorsementInput.files[0].name}`;
                } else {
                    endorsementContainer.innerHTML = 'No file uploaded';
                }

                // Update Portfolio Link
                const portfolioContainer = document.querySelector('.portfolio-url');
                if (portfolioLink) {
                    portfolioContainer.href = portfolioLink;
                    portfolioContainer.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#0000ee"><path d="M480.51-87.87q-80.92 0-152.37-30.6-71.44-30.6-125.14-84.29-53.7-53.7-84.29-125.11-30.6-71.41-30.6-152.61 0-81.19 30.6-152.13Q149.3-703.54 203-757.24q53.7-53.69 125.11-84.29 71.41-30.6 152.61-30.6 81.19 0 152.01 30.6 70.81 30.6 124.51 84.29 53.69 53.7 84.29 124.88 30.6 71.17 30.6 152.1 0 80.93-30.6 152.37-30.6 71.43-84.29 125.13-53.7 53.69-124.79 84.29-71.1 30.6-151.94 30.6Zm-48.03-87v-67.76q-19.76 0-33.53-13.87-13.76-13.86-13.76-33.33v-47.6L178.11-544.52q-4 18.76-5.5 34.52-1.5 15.76-1.5 29.63 0 114.1 73.78 201.42t187.59 104.08Zm285.61-108.04q35.52-41.52 53.28-92.17 17.76-50.65 17.76-105.4 0-93.61-51.28-170.99-51.28-77.38-138.57-113.9v23.52q0 29.41-21.15 50.35-21.15 20.93-50.5 20.93h-95.15v47.22q0 20.23-13.8 34.03t-34.03 13.8h-47.22v95.04h237.99q20.26 0 34.06 13.8t13.8 34.02v94.75h40.29q23 0 38.79 16 15.8 16 15.73 39Z"/></svg>
                        ${portfolioLink}`;
                } else {
                    portfolioContainer.href = '#';
                    portfolioContainer.textContent = 'No portfolio link provided';
                }

                // Update video section only if event is defined and has a file
                // Get video input
                const videoInput = document.getElementById('fileInput');
                const videoContainer = document.querySelector('.uploaded-document .mp4');
                if (videoInput.files && videoInput.files[0]) {
                    videoContainer.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#2E3849"><path d="m218.87-776.13l60 120q5 11 16 17.5t23 6.5q24 0 36.5-20t1.5-42l-41-82h72l60 120q5 11 16 17.5t23 6.5q24 0 36.5-20t1.5-42l-41-82h72l60 120q5 11 16 17.5t23 6.5q24 0 36.5-20t1.5-42l-41-82h138.26q34.78 0 58.89 24.61t24.11 58.39v426.26q0 33.78-24.11 58.39t-58.89 24.61H170.87q-33.78 0-58.39-24.37t-24.61-57.91v-426.98q0-33.78 23.61-58.39t59.39-24.61h48Z"/></svg>
                        ${videoInput.files[0].name}`;
                } else {
                    videoContainer.innerHTML = 'No video uploaded';
                }
            }

            // Add event listeners to all input fields
            document.getElementById('firstName').addEventListener('input', updateReviewSection);
            document.getElementById('lastName').addEventListener('input', updateReviewSection);
            document.getElementById('homeLocation').addEventListener('input', updateReviewSection);
            document.getElementById('phoneNumber').addEventListener('input', updateReviewSection);
            document.getElementById('email').addEventListener('input', updateReviewSection);
            document.getElementById('cvUpload').addEventListener('change', updateReviewSection);
            document.getElementById('endorsementUpload').addEventListener('change', updateReviewSection);
            document.getElementById('fileInput').addEventListener('change', updateReviewSection);
            document.querySelector('.link-input').addEventListener('input', updateReviewSection);

            // Update when files are removed
            window.removeFile = function(type) {
                const input = document.getElementById(`${type}Upload`);
                const preview = document.getElementById(`${type}Preview`);
                const section = document.getElementById(`${type}UploadSection`);
                const fileName = document.getElementById(`${type}FileName`);
                const errorMessage = section.querySelector('.error-message');

                input.value = '';
                preview.classList.remove('show');
                section.classList.remove('has-file');
                fileName.textContent = '';

                // Call updateReviewSection without an event
                updateReviewSection();
            };

            // Initial update
            updateReviewSection();
        </script>
    </div>

    
    <script>
            (function(){if(!window.chatbase||window.chatbase("getState")!=="initialized"){window.chatbase=(...arguments)=>{if(!window.chatbase.q){window.chatbase.q=[]}window.chatbase.q.push(arguments)};window.chatbase=new Proxy(window.chatbase,{get(target,prop){if(prop==="q"){return target.q}return(...args)=>target(prop,...args)}})}const onLoad=function(){const script=document.createElement("script");script.src="https://www.chatbase.co/embed.min.js";script.id="qEhc2yKw7YIylj99unQ0q";script.domain="www.chatbase.co";document.body.appendChild(script)};if(document.readyState==="complete"){onLoad()}else{window.addEventListener("load",onLoad)}})();
    </script>

    <script>
        (function(){if(!window.chatbase||window.chatbase("getState")!=="initialized"){window.chatbase=(...arguments)=>{if(!window.chatbase.q){window.chatbase.q=[]}window.chatbase.q.push(arguments)};window.chatbase=new Proxy(window.chatbase,{get(target,prop){if(prop==="q"){return target.q}return(...args)=>target(prop,...args)}})}const onLoad=function(){const script=document.createElement("script");script.src="https://www.chatbase.co/embed.min.js";script.id="qEhc2yKw7YIylj99unQ0q";script.domain="www.chatbase.co";document.body.appendChild(script)};if(document.readyState==="complete"){onLoad()}else{window.addEventListener("load",onLoad)}})();
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
                            <li><a href="studentmain.php">Internship by Company</a></li>
                            <li><a href="studentmain.php">Internship by City</a></li>
                            <li><a href="studentmain.php">Search Nearby Internship</a></li>
                        </ul>
                    </div>
                
                    <!-- Employers Section -->
                    <div class="centerside">
                        <h4>EMPLOYERS</h4>
                        <ul>
                            <li><a href="../RECRUITER/companymainpage.html">Post Internships</a></li>
                        </ul>
                    </div>
                
                    <!-- About Interflo Section -->
                    <div class="centerside">
                        <h4>ABOUT INTERNFLO</h4>
                        <ul>
                            <li><a href="studentfrontpage.php#about">About Us</a></li>
                            <li><a href="studentfrontpage.php#aichat">How It Works</a></li>
                            <li><a href="studentfrontpage.php#contact">Contact Us</a></li>
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



    <script src="applycompany.js"></script>
</body>
</html>







                    



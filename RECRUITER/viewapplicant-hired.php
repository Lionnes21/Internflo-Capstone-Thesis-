<?php
    session_start();
    include 'config.php';
    // Define company_name and internship_title from URL parameters

    // Get the internship ID from the URL
    if (isset($_GET['logout'])) {
        session_destroy();
        header("Location: companymainpage.html");
        exit();
    }

    function getFullName() {
        if (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
            return htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']);
        }
        return 'Your Name'; // Fallback if names are not set
    }

    // Current user (recruiter) verification
    $current_user_id = null;
    if (isset($_SESSION['email']) && isset($_SESSION['source_table']) && $_SESSION['source_table'] === 'approvedrecruiters') {
        $recruiter_email = $_SESSION['email'];
        $query = "SELECT id FROM approvedrecruiters WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $recruiter_email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $current_user_id = $row['id'];
        } else {
            echo "Recruiter not found.";
            exit();
        }
    } else {
        echo "Unauthorized access. Please log in as an approved recruiter.";
        exit();
    }



    // Assuming you want to pass the student's ID from the previous page
    $student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

    if ($student_id === 0) {
        // No valid student ID provided
        header("Location: applicant.php");
        exit();
    }
    // Add this before sending the email


    // Fetch student details including course from students table
    $stmt = $conn->prepare("
        SELECT s.*, 
                sa.first_name, 
                sa.last_name, 
                sa.address, 
                sa.email, 
                sa.phone_number, 
                sa.portfolio_link, 
                sa.cv_file, 
                sa.endorsement_file, 
                sa.assessment_score,
                sa.demo_video 
        FROM students s 
        JOIN hired_applicants sa ON s.id = sa.student_id 
        WHERE s.id = ?
        ");

    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Student not found
        header("Location: applicant.php");
        exit();
    }
    

    $student = $result->fetch_assoc();

    function getAssessmentCategory($score_string) {
        // Split the score string into correct answers and total questions
        list($correct, $total) = explode('/', $score_string);
        $correct = (int)$correct;
        $total = (int)$total;
        
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
    
    function getAssessmentDetails($score_string) {
        // Check if score is empty or blank
        if (empty($score_string) || trim($score_string) == '') {
            return array(
                'text' => 'N/A', 
                'text_color' => '#6c757d',
                'progress_class' => 'progress-bar-secondary',
                'progress' => 0,
                'badge_class' => 'badge-secondary'
            );
        }
        
        // Split the score string into correct answers and total questions
        list($correct, $total) = explode('/', $score_string);
        $correct = (int)$correct;
        $total = (int)$total;
        
        // Handle division by zero
        if ($total == 0) {
            return array(
                'text' => 'N/A', 
                'text_color' => '#6c757d',
                'progress_class' => 'progress-bar-secondary',
                'progress' => 0,
                'badge_class' => 'badge-secondary'
            );
        }
        
        // Calculate percentage
        $percentage = ($correct / $total) * 100;
    
        if ($percentage >= 60) {
            return array(
                'text' => 'Excellent performance', 
                'text_color' => '#1e7e34',
                'progress_class' => 'progress-bar-success',
                'progress' => $percentage,
                'badge_class' => 'badge-success'
            );
        } elseif ($percentage >= 40) {
            return array(
                'text' => 'Average performance', 
                'text_color' => '#856404',
                'progress_class' => 'progress-bar-warning',
                'progress' => $percentage,
                'badge_class' => 'badge-warning'
            );
        } else {
            return array(
                'text' => 'Poor performance', 
                'text_color' => '#b91e1e',
                'progress_class' => 'progress-bar-danger',
                'progress' => $percentage,
                'badge_class' => 'badge-danger'
            );
        }
    }
    
    function getScorePercentage($conn, $score_string) {
        // Check if score is empty or blank
        if (empty($score_string) || trim($score_string) == '') {
            return array(
                'percentage' => 'N/A',
                'color' => '#6c757d'
            );
        }
        
        // Split the score string into correct answers and total questions
        list($correct, $total) = explode('/', $score_string);
        $correct = (int)$correct;
        $total = (int)$total;
        
        // Handle division by zero
        if ($total == 0) {
            return array(
                'percentage' => 'N/A',
                'color' => '#6c757d'
            );
        }
        
        // Rest of your existing code...
        // Fetch all assessment scores
        $query = "SELECT assessment_score FROM hired_applicants";
        $result = $conn->query($query);
        
        if (!$result) {
            return array(
                'percentage' => '70.0%',
                'color' => '#b91e1e'
            );
        }
    
        // Collect all scores and their percentages
        $all_scores = [];
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['assessment_score']) && strpos($row['assessment_score'], '/') !== false) {
                list($c, $t) = explode('/', $row['assessment_score']);
                if ((int)$t > 0) {
                    $all_scores[] = ((int)$c / (int)$t) * 100;
                }
            }
        }
    
        // If no scores found
        if (empty($all_scores)) {
            return array(
                'percentage' => '70.0%',
                'color' => '#b91e1e'
            );
        }
    
        // Calculate the number of scores lower than current score
        $current_percentage = ($correct / $total) * 100;
        $scores_below_current = array_filter($all_scores, function($score) use ($current_percentage) {
            return $score < $current_percentage;
        });
    
        // Calculate percentile
        $total_scores = count($all_scores);
        $percentile = (count($scores_below_current) / $total_scores) * 100;
    
        // Logic for low scores (below 40%)
        if ($current_percentage < 40) {
            return array(
                'percentage' => '70.0%',
                'color' => '#b91e1e'
            );
        }
    
        // Logic for average scores (40-59%)
        if ($current_percentage >= 40 && $current_percentage < 60) {
            $additional_decimal = round($percentile / 10, 1);
            $final_percentage = number_format(70.0 + $additional_decimal, 1, '.', '');
            
            return array(
                'percentage' => $final_percentage . '%',
                'color' => '#856404'  // Brown for average scores
            );
        }
    
        // Logic for excellent scores (60% and above)
        if ($current_percentage >= 60) {
            $additional_decimal = round($percentile / 5, 1);
            $final_percentage = number_format(80.0 + $additional_decimal, 1, '.', '');
            
            return array(
                'percentage' => $final_percentage . '%',
                'color' => '#1e7e34'  // Green for excellent scores
            );
        }
    
        // Fallback
        return array(
            'percentage' => '70.0%',
            'color' => '#b91e1e'
        );
    }
    
    // Populate assessment details
    $assessment_score = $student['assessment_score'] ?? '';
    // Check if assessment score is empty
    if (empty($assessment_score) || trim($assessment_score) == '') {
        $assessment = array('text' => 'N/A', 'class' => 'badge badge-secondary');
        $scoreDetails = array(
            'text' => 'N/A', 
            'text_color' => '#6c757d',
            'progress_class' => 'progress-bar-secondary',
            'progress' => 0,
            'badge_class' => 'badge-secondary'
        );
        $scorePercentage = array(
            'percentage' => 'N/A',
            'color' => '#6c757d'
        );
    } else {
        $scorePercentage = getScorePercentage($conn, $assessment_score);
        $assessment = getAssessmentCategory($assessment_score);
        $scoreDetails = getAssessmentDetails($assessment_score);
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="pics/ucc.png">
    <title>UCC - Applicants</title>
    <link rel="stylesheet" href="viewapplicant.css">
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
        <div class="profile-card">
            <div class="profile-image-container">
                <div class="profile-image">
                <img src="../STUDENTLOGIN/<?php echo htmlspecialchars($student['profile_pic']); ?>" alt="<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>">
                </div>
            </div>
            <div class="profile-content">
                <span class="applicantnum">Hired Applicant</span>
                <h1><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h1>
                <h2><?php echo htmlspecialchars($student['course']); ?></h2>
                <div class="profile-details">
                <span class="items">
                    <svg xmlns="http://www.w3.org/2000/svg" height="22px" viewBox="0 -960 960 960" width="22px" fill="#2E3849"><path d="M480-197.46q117.33-105.08 177.23-192.09 59.9-87.02 59.9-160.41 0-103.31-67.67-171.24t-169.47-67.93q-101.79 0-169.46 67.93-67.66 67.93-67.66 171.24 0 73.39 59.9 160.29 59.9 86.89 177.23 192.21Zm-.06 85.16q-13.9 0-26.25-4.74-12.36-4.74-24.04-14.22-41.43-35.72-88.89-82.96-47.46-47.24-88.05-101.71-40.6-54.48-66.72-114.06-26.12-59.58-26.12-119.97 0-137.28 91.45-229.72 91.45-92.45 228.68-92.45 136.23 0 228.18 92.45 91.95 92.44 91.95 229.72 0 60.39-26.62 120.47t-66.72 114.56q-40.09 54.47-87.55 101.21-47.46 46.74-88.89 82.46-11.71 9.48-24.11 14.22-12.4 4.74-26.3 4.74ZM480-552Zm0 74.39q31.2 0 52.79-21.6 21.6-21.59 21.6-52.79t-21.6-52.79q-21.59-21.6-52.79-21.6t-52.79 21.6q-21.6 21.59-21.6 52.79t21.6 52.79q21.59 21.6 52.79 21.6Z"/></svg>
                    <?php echo htmlspecialchars($student['address']); ?>
                </span>
                <span class="items">
                    <svg xmlns="http://www.w3.org/2000/svg" height="22px" viewBox="0 -960 960 960" width="22px" fill="#2E3849"><path d="M763.48-136.87q-122.44-9-232.37-60.1-109.94-51.1-197.37-138.29-87.44-87.44-138.03-197.49-50.6-110.05-59.6-232.49-2-24.35 14.65-42.12 16.65-17.77 41-17.77h135.76q22.5 0 37.87 12.53 15.37 12.53 20.81 33.56l23.76 101.97q2.95 16.59-1.38 31.22-4.34 14.63-15.21 24.78l-98.44 96.09q19.05 35.37 43.61 68.46 24.57 33.09 55.61 63.13 28.81 28.8 60.42 52.63 31.6 23.83 66.26 41.91L621.5-395.8q10.63-10.4 25.02-14.37 14.39-3.98 30.98-1.03l100.54 22.29q22.03 6.43 34.06 21.44 12.03 15.01 12.03 37.04v137.67q0 24.35-18.27 41.12-18.27 16.77-42.38 14.77Z"/></svg>
                    <?php echo htmlspecialchars($student['phone_number']); ?>
                </span>
                <span class="items">
                    <svg xmlns="http://www.w3.org/2000/svg" height="22px" viewBox="0 -960 960 960" width="22px" fill="#2E3849"><path d="M479.76-87.87q-80.93 0-152.12-30.6-71.18-30.6-124.88-84.29-53.69-53.7-84.29-125.11-30.6-71.41-30.6-152.61 0-81.19 30.6-152.13 30.6-70.93 84.29-124.63 53.7-53.69 125.11-84.29 71.41-30.6 152.61-30.6 81.19 0 152.13 30.6 70.93 30.6 124.63 84.29 53.69 53.7 84.29 124.57 30.6 70.87 30.6 152.43v60q0 56.44-40.41 96.13-40.42 39.7-97.57 39.7-34.77 0-63.96-17.24-29.19-17.24-49.35-46.2-26.88 29.96-63.62 46.7-36.74 16.74-77.22 16.74-81.35 0-138.47-57.19-57.12-57.18-57.12-138.63 0-81.44 57.19-138.4 57.18-56.96 138.63-56.96 81.44 0 138.4 57.12 56.96 57.12 56.96 138.47v57.85q0 24.56 17.26 41.56 17.26 17 41.54 17 24.27 0 41.3-17 17.03-17 17.03-41.56v-58.09q0-130-91.36-221.24Q610-792.72 480-792.72t-221.36 91.36Q167.28-610 167.28-480t91.24 221.36q91.24 91.36 221.59 91.36h152.3q16.83 0 28.21 11.32Q672-144.64 672-127.91q0 16.65-11.38 28.34-11.38 11.7-28.21 11.7H479.76Zm.28-275.72q48.53 0 82.45-33.96 33.92-33.97 33.92-82.49 0-48.53-33.96-82.45-33.97-33.92-82.49-33.92-48.53 0-82.45 33.96-33.92 33.97-33.92 82.49 0 48.53 33.96 82.45 33.97 33.92 82.49 33.92Z"/></svg>

                    <?php echo htmlspecialchars($student['email']); ?>
                </span>
                <span class="link">
                    <?php echo htmlspecialchars($student['portfolio_link']); ?>
                    <?php if (!empty($student['portfolio_link'])): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#0000ee">
                            <path d="M202.87-111.87q-37.78 0-64.39-26.61t-26.61-64.39v-554.26q0-37.78 26.61-64.39t64.39-26.61H434.5q19.15 0 32.33 13.17Q480-821.78 480-802.63t-13.17 32.33q-13.18 13.17-32.33 13.17H202.87v554.26h554.26V-434.5q0-19.15 13.17-32.33Q783.48-480 802.63-480t32.33 13.17q13.17 13.18 13.17 32.33v231.63q0 37.78-26.61 64.39t-64.39 26.61H202.87Zm554.26-581.85L427-363.59q-12.67 12.68-31.59 12.56-18.91-.12-31.58-12.8-12.68-12.67-12.68-31.7 0-19.04 12.68-31.71l329.89-329.89H605.5q-19.15 0-32.33-13.17Q560-783.48 560-802.63t13.17-32.33q13.18-13.17 32.33-13.17h197.13q19.15 0 32.33 13.17 13.17 13.18 13.17 32.33v197.13q0 19.15-13.17 32.33Q821.78-560 802.63-560t-32.33-13.17q-13.17-13.18-13.17-32.33v-88.22Z"/>
                        </svg>
                    <?php endif; ?>
                </span>
                </div>
            </div>

        </div>
        <table class="list-container">
                <thead>
                <tr class="list-header">
                    <th class="list-header-cell left">Applicant Assessment</th>
                    <th class="list-header-cell centered">
                    <div style="display: flex; align-items: center; justify-content: center;">
                        <span>Outcome</span>
                        <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#666666" style="margin-left: 4px;">
                        <path d="M478-240q21 0 35.5-14.5T528-290q0-21-14.5-35.5T478-340q-21 0-35.5 14.5T428-290q0 21 14.5 35.5T478-240Zm2 160q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Zm4-172q25 0 43.5 16t18.5 40q0 22-13.5 39T502-525q-23 20-40.5 44T444-427q0 14 10.5 23.5T479-394q15 0 25.5-10t13.5-25q4-21 18-37.5t30-31.5q23-22 39.5-48t16.5-58q0-51-41.5-83.5T484-720q-38 0-72.5 16T359-655q-7 12-4.5 25.5T368-609q14 8 29 5t25-17q11-15 27.5-23t34.5-8Z"/>
                        </svg>
                    </div>
                    </th>

                    <th class="list-header-cell right">
                    <div style="display: inline-flex; align-items: center; justify-content: flex-end; width: 100%;">
                        <span>Average</span>
                        <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#666666" style="margin-left: 4px;">
                        <path d="M478-240q21 0 35.5-14.5T528-290q0-21-14.5-35.5T478-340q-21 0-35.5 14.5T428-290q0 21 14.5 35.5T478-240Zm2 160q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Zm4-172q25 0 43.5 16t18.5 40q0 22-13.5 39T502-525q-23 20-40.5 44T444-427q0 14 10.5 23.5T479-394q15 0 25.5-10t13.5-25q4-21 18-37.5t30-31.5q23-22 39.5-48t16.5-58q0-51-41.5-83.5T484-720q-38 0-72.5 16T359-655q-7 12-4.5 25.5T368-609q14 8 29 5t25-17q11-15 27.5-23t34.5-8Z"/>
                        </svg>
                    </div>
                    </th>

                </tr>
                </thead>
                <tbody>
                <tr class="list-row">
                    <td class="list-cell">
                    <span class="<?php echo $assessment['class']; ?>">
                        <?php echo $assessment['text']; ?>
                    </span>
                    </td>
                    <td class="list-cell centered">
                    <div class="score-value" style="color: <?php echo $scoreDetails['text_color']; ?>">
                        <div class="progress-bar <?php echo $scoreDetails['progress_class']; ?>">
                            <div class="progress-bar-fill" style="width: <?php echo $scoreDetails['progress']; ?>%;"></div>
                        </div>
                        <?php echo $scoreDetails['text']; ?><span style="color: #666666;"> in the assessment</span>
                    </div>
                    </td>
                    <td class="list-cell right">
                    <div class="score-percentage" style="color: <?php echo $scorePercentage['color']; ?>">
                        <?php echo $scorePercentage['percentage']; ?>
                    </div>
                    </td>
                </tr>
                </tbody>
        </table>

        <div class="demo-video">
            <?php if (!empty($student['demo_video'])): ?>
                <p class="docu-text">Demo Video</p>
                <iframe src="../STUDENTLOGIN/demovids/<?php echo htmlspecialchars($student['demo_video']); ?>" width="100%" height="400px" frameborder="0"></iframe>
            <?php else: ?>
                <p class="docu-text">Demo Video</p>
                <div class="document demo-video">
                    No Demo Video uploaded
                </div>
            <?php endif; ?>
        </div>
        <br>
        <br>

        <div class="documents-container">
            <?php if (!empty($student['cv_file'])): ?>
                <div class="document cv">
                    <p class="docu-text">Curriculum Vitae</p>
                    <iframe src="../STUDENTLOGIN/cv/<?php echo htmlspecialchars($student['cv_file']); ?>" width="100%" height="600px" frameborder="0"></iframe>
                </div>
            <?php else: ?>
                <div class="document cv">
                    No CV uploaded
                </div>
                <?php endif; ?>

            <?php if (!empty($student['endorsement_file'])): ?>
                <div class="document endorsement">
                    <p class="docu-text">Endorsement Letter</p>
                    <iframe src="../STUDENTLOGIN/endorse/<?php echo htmlspecialchars($student['endorsement_file']); ?>" width="100%" height="600px" frameborder="0"></iframe>
                </div>
            <?php else: ?>
                <div class="document endorsement">
                    No Endorsement uploaded
                </div>
            <?php endif; ?>
        </div>

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
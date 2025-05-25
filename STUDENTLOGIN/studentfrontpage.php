<?php
    session_start();
    $isLoggedIn = isset($_SESSION['user_id']);

    // Initialize variables
    $initials = '';
    $fullName = '';
    $email = '';
    $fullName2 = '';
    $profile_pic = ''; // Default profile picture

    

    // If logged in, fetch user details from the database
    if ($isLoggedIn) {
        // Include database connection
        include 'config.php';

        // Get the user ID from the session
        $userId = $_SESSION['user_id'];

        $sql = '
        SELECT 
            first_name, 
            middle_name, 
            last_name, 
            suffix, 
            email, 
            name, 
            profile_pic,
            login_method,
            course 
        FROM students 
        WHERE id = ?
    ';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // If user information is found, extract details
    if ($user) {
        $profile_pic = !empty($user['profile_pic']) ? $user['profile_pic'] : 'pics/profile.png';

        // Construct full name (including middle name and suffix if available)
        $fullName2 = trim($user['first_name'] . 
                         
                        ' ' . $user['last_name']);

        // Store the student's course
        $student_course = $user['course'];
        
        // Set login method
        $loginMethod = $user['login_method'];
    }

        // If user information is found, extract details
        if ($user) {
            $profile_pic = !empty($user['profile_pic']) ? $user['profile_pic'] : 'pics/profile.png'; // Add your default profile picture path

            // Construct full name (including middle name and suffix if available)
            $fullName2 = trim($user['first_name'] . 
                         
            ' ' . $user['last_name']);

            // If 'name' column exists and is not empty, you can use that alternatively
            if (empty($fullName2) && !empty($user['name'])) {
                $fullName2 = $user['name'];
            }

            // Store the student's course
            $student_course = $user['course'];
        }

        $conn = new mysqli("localhost", "u798912504_root", "Internfloucc2025*", "u798912504_internflo");

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Calculate the total number of openings
        $total_openings_query = "SELECT SUM(number_of_openings) as total_openings FROM internshipad";
        $total_openings_result = $conn->query($total_openings_query);
        
        // Initialize a default value in case the query fails
        $total_openings = 0;
    
        if ($total_openings_result && $row = $total_openings_result->fetch_assoc()) {
            $total_openings = $row['total_openings'] ?? 0;
        }
    
        // Close the result set
        $total_openings_result->free();
    
        // Count the total number of rows in internshipad
        $total_rows_query = "SELECT COUNT(*) as total_rows FROM internshipad";
        $total_rows_result = $conn->query($total_rows_query);
    
        // Initialize a default value in case the query fails
        $total_rows = 0;
    
        if ($total_rows_result && $row = $total_rows_result->fetch_assoc()) {
            $total_rows = $row['total_rows'] ?? 0;
        }
    
        // Close the result set
        $total_rows_result->free();

        // Function to calculate distance between two points
        function calculateDistance($lat1, $lon1, $lat2, $lon2) {
            $earthRadius = 6371; // in kilometers
            $dLat = deg2rad($lat2 - $lat1);
            $dLon = deg2rad($lon2 - $lon1);
            $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
            $c = 2 * atan2(sqrt($a), sqrt(1-$a));
            $distance = $earthRadius * $c;
            return round($distance, 2);
        }

        // Function to truncate text
        function truncate($text, $length = 150) {
            if (strlen($text) > $length) {
                return substr($text, 0, $length) . '...';
            }
            return $text;
        }

        // Function to format 'created_at' as "time ago"
        function timeAgo($datetime) {
            $timestamp = strtotime($datetime);
            $diff = time() - $timestamp;

            if ($diff < 60) return $diff . ' s ago';
            $diff = round($diff / 60);
            if ($diff < 60) return $diff . ' m ago';
            $diff = round($diff / 60);
            if ($diff < 24) return $diff . ' h ago';
            $diff = round($diff / 24);
            if ($diff < 7) return $diff . ' d ago';
            $diff = round($diff / 7);
            return $diff . ' weeks ago';
        }

        // Query for company grid
        $company_grid_sql = "SELECT ar.id, 
                        ar.company_name, 
                        ar.company_logo,
                        COUNT(ia.internship_id) as job_count 
                    FROM approvedrecruiters ar
                    LEFT JOIN internshipad ia ON ar.id = ia.user_id 
                    GROUP BY ar.id, ar.company_name, ar.company_logo";
                    
        $company_grid_result = $conn->query($company_grid_sql);

        // Query for job listings with course-based filtering
        $search_keyword = $_GET['search_keyword'] ?? '';
        $search_location = $_GET['search_location'] ?? '';
        $industry = $_GET['classification'] ?? '';

        $params = array();
        $types = ''; // Initialize $types string
        
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
            r.id as company_id,
            r.company_name,
            r.industry,
            r.company_overview,
            r.company_address,
            r.Keywords,
            CAST(r.latitude AS DECIMAL(10,6)) as latitude,
            CAST(r.longitude AS DECIMAL(10,6)) as longitude,
            r.company_logo,
            r.company_phone,
            r.company_email,
            COALESCE((
                SELECT ROUND(AVG(rating), 1)
                FROM company_reviews
                WHERE company_id = r.id
            ), 0) as total_rating,
            COALESCE((
                SELECT COUNT(*)
                FROM company_reviews
                WHERE company_id = r.id
            ), 0) as total_reviews,
            CASE WHEN EXISTS (
                SELECT 1 
                FROM studentapplication sa 
                WHERE sa.internshipad_id = i.internship_id 
                AND sa.student_id = ?
            ) THEN 1 ELSE 0 END as has_applied,
            CASE WHEN EXISTS (
                SELECT 1 
                FROM hired_applicants ha
                WHERE ha.student_id = ?
                AND ha.Status = 'Hired'
            ) THEN 1 ELSE 0 END as is_hired,
            CASE WHEN EXISTS (
                SELECT 1 
                FROM hired_applicants ha
                WHERE ha.student_id = ?
                AND ha.internshipad_id = i.internship_id
                AND ha.Status = 'Hired'
            ) THEN 1 ELSE 0 END as is_hired_for_this_ad
        FROM internshipad i
        JOIN approvedrecruiters r ON i.user_id = r.id
        WHERE 1=1";
        
        // Add the user ID to your parameters array
        $params[] = $_SESSION['user_id']; // for has_applied
        $params[] = $_SESSION['user_id']; // for is_hired
        $params[] = $_SESSION['user_id']; // for is_hired_for_this_ad
        $types .= 'iii';

        // Define education courses
        $education_courses = [
            'Bachelor of Early Childhood Education',
            'Bachelor in Secondary Education Major in English',
            'Bachelor in Secondary Education Major in English - Chinese',
            'Bachelor in Secondary Education Major in Science',
            'Bachelor in Secondary Education Major in Technology and Livelihood Education',
            'BECED',
            'BSED-English',
            'BSED-English-Chinese',
            'BSED-Science',
            'BSED-TLE'
        ];
        
        // Define tech-related courses
        $tech_courses = [
            'Bachelor of Science in Computer Science',
            'Bachelor of Science in Information System',
            'Bachelor of Science in Entertainment and Multimedia',
            'Bachelor of Science in Information Technology',
            'BSCS',
            'BSIS',
            'BSEM',
            'BSIT'
        ];
        
        // Communication courses
        $communication_courses = [
            'Bachelor of Arts in Communication',
            'BA Communication'
        ];
        
        // Political science courses
        $political_science_courses = [
            'Bachelor of Arts in Political Science',
            'BA Political Science'
        ];
        
        // Health and human services courses
        $health_services_courses = [
            'Bachelor of Science in Psychology',
            'Bachelor of Science in Office Administration',
            'BS Psychology',
            'BSOA'
        ];
        
        // Finance courses
        $finance_courses = [
            'Bachelor of Science in Mathematics',
            'Bachelor of Science in Accountancy',
            'Bachelor of Science in Accounting Information System',
            'Bachelor of Science in Entrepreneurship',
            'Bachelor of Science in Business Administration, Major in Financial Management',
            'Bachelor of Science in Business Administration, Major in Marketing Management',
            'BS Math',
            'BSA',
            'BS AIS',
            'BS Entrep',
            'BSBA-FM',
            'BSBA-MM'
        ];
        
        // Civil society courses
        $civil_society_courses = [
            'Bachelor of Public Administration',
            'BPA'
        ];
        
        // Healthcare courses
        $healthcare_courses = [
            'Bachelor of Science in Entrepreneurship',
            'BS Entrep'
        ];
        
        // Entertainment courses
        $entertainment_courses = [
            'Bachelor of Science in Hospitality Management',
            'BSHM'
        ];
        
        // Marketing courses
        $marketing_courses = [
            'Bachelor of Science in Business Administration, Major in Human Resource Management',
            'BSBA-HRM'
        ];
        
        // Law enforcement courses
        $law_enforcement_courses = [
            'Bachelor of Science in Criminology',
            'BS Crim'
        ];
        
        // Then modify your filtering condition to include new mappings
        if (isset($student_course)) {
            if (in_array($student_course, $tech_courses)) {
                $query .= " AND i.industry = 'Technology'";
            } elseif (in_array($student_course, $education_courses)) {
                $query .= " AND i.industry = 'Institutions'";
            } elseif (in_array($student_course, $communication_courses)) {
                $query .= " AND i.industry = 'Advertisement'";
            } elseif (in_array($student_course, $political_science_courses)) {
                $query .= " AND i.industry = 'Media'";
            } elseif (in_array($student_course, $health_services_courses)) {
                $query .= " AND i.industry = 'Health and human services'";
            } elseif (in_array($student_course, $finance_courses)) {
                $query .= " AND i.industry = 'Finance'";
            } elseif (in_array($student_course, $civil_society_courses)) {
                $query .= " AND i.industry = 'Civil society'";
            } elseif (in_array($student_course, $healthcare_courses)) {
                $query .= " AND i.industry = 'Healthcare'";
            } elseif (in_array($student_course, $entertainment_courses)) {
                $query .= " AND i.industry = 'Entertainment'";
            } elseif (in_array($student_course, $marketing_courses)) {
                $query .= " AND i.industry = 'Marketing'";
            } elseif (in_array($student_course, $law_enforcement_courses)) {
                $query .= " AND i.industry = 'Law Enforcement'";
            }
        }
        
                
        // Add search conditions if keywords or location are provided
        if (!empty($search_keyword)) {
            $query .= " AND (i.internship_title LIKE ? OR i.internship_description LIKE ? OR r.company_name LIKE ? 
                        OR i.duration LIKE ? OR i.department LIKE ? OR i.internship_type LIKE ? OR r.Keywords LIKE ?)";
            $keyword = "%$search_keyword%";
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
            $types .= 'sssssss';
        }
        
        if (!empty($search_location)) {
            $query .= " AND r.company_address LIKE ?";
            $location = "%$search_location%";
            $params[] = $location;
            $types .= 's';
        }
        
        // Add industry filter
        if (!empty($industry)) {
            $query .= " AND i.industry = ?";
            $params[] = $industry;
            $types .= 's';
        }

        // Prepare and execute the query for job listings
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        // Store results in job_listings array
        $job_listings = $result->fetch_all(MYSQLI_ASSOC);

        // Get the total count of jobs
        $job_number = count($job_listings);

        // Close the statement
        $stmt->close();

        // Close the connection after all queries are done
        $conn->close();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="pics/ucc.png">
    <title>UCC - Student</title>
    <link rel="stylesheet" href="NAVX.css">
    <link rel="stylesheet" href="studentfrontpage.css">
    <link rel="stylesheet" href="FOOTER.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body>
    <div class="navbar">
            <div class="logo-container">
            <button class="menu-toggle" aria-label="Toggle Navigation">☰</button>
                    <img src="pics/ucc-logo.png" alt="Logo">

                </div>
                <div class="nav-links">
                    <a href="studentfrontpage.php">HOME</a>
                    <a href="#about">ABOUT US</a>
                    <a href="#contact">CONTACT US</a>
                </div>
                <div class="auth-buttons">
                    <?php if ($isLoggedIn): ?>
                        <div class="dropdown-container">
                            <div class="border">
                                <span class="greeting-text"><?php echo htmlspecialchars($user['email']); ?></span>
                                <div class="dropdown-btn" onclick="toggleDropdown()">
                                    <img src="<?php echo htmlspecialchars($profile_pic, ENT_QUOTES); ?>" alt="Profile Picture" onerror="this.onerror=null;this.src='pics/default_profile.png';" />
                                </div>
                            </div>
                            <div id="dropdown-content" class="dropdown-content">
                                <div class="user-fullname"><?php echo $fullName2; ?></div>
                                <hr style="margin: 0 auto">
                                <a href="student-profile.php">Profile</a>
                                <a href="../monitoring/std_dashboard.php">Internship</a>
                                <a href="chat-inbox.php">Emails</a>
                                <a href="student-account.php">Settings</a>
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


    <!-- BANNER HEADER -->
    <div class="banner">
                <p class="banner-text">
                    Transform your skills into opportunities. Build your <span style="color: #ff8c00; font-weight: 700" >Curriculim Vitae </span> with us today!
                </p>
                <button class="create-button" onclick="window.location.href='resume/resume.php'">
                    CREATE CV
                </button>
    </div>
    <script>
        function updateText() {
            const bannerText = document.querySelector('.banner-text');
            if (window.innerWidth <= 1100) {
                // Mobile version - only show the specified text
                bannerText.innerHTML = `Build your <span style="color: #ff8c00; font-weight: 700">Curriculum Vitae</span> with us!`;
            } else {
                // Desktop version - show full text
                bannerText.innerHTML = `Transform your skills into opportunities. Build your <span style="color: #ff8c00; font-weight: 700">Curriculum Vitae</span> with us today!`;
            }
        }

        // Run on page load
        updateText();

        // Run whenever the window is resized
        window.addEventListener('resize', updateText);
    </script>
    
    <div class="title-container">
        <h1>Helping you land your dream company<br> <span class="highlight">internship</span> with Internflo!</h1>
            <p>
            Now recruiting for
            <span style="color: #ff8c00; font-weight: 700"><?php echo $total_openings; ?>+</span> positions, over 
            <span style="color: #ff8c00; font-weight: 700"><?php echo $total_rows; ?>+</span> internship opportunities posted!
            </p>
            <button class="map" onclick="window.location.href='studentmain.php';">
            Find internships in the map!
            </button>
    </div>
    <div class="extra-details">
        <?php if ($isLoggedIn): ?>
            <?php if ($loginMethod === 'email'): ?>
                <div class="promo-banner">
                    <svg xmlns="http://www.w3.org/2000/svg" height="28px" viewBox="0 -960 960 960" width="28px" fill="#ff8c00"><path d="M769.33-436.41H685.5q-18.43 0-31.01-12.58-12.58-12.58-12.58-31.01t12.58-31.01q12.58-12.58 31.01-12.58h83.83q18.43 0 31.01 12.58 12.57 12.58 12.57 31.01t-12.57 31.01q-12.58 12.58-31.01 12.58Zm-182.7 146.26q10.96-14.96 28.51-17.32 17.56-2.36 32.51 8.6l67.11 50.15q15.2 10.96 17.44 28.39 2.24 17.44-8.72 32.63-10.96 15.2-28.63 17.44-17.68 2.24-32.87-8.72l-66.63-50.15q-14.96-10.96-17.32-28.39-2.36-17.44 8.6-32.63Zm127.89-421.13-66.87 50.15q-14.95 10.96-32.51 8.72-17.55-2.24-28.51-17.44-10.96-14.95-8.6-32.51 2.36-17.55 17.32-28.51l66.87-50.39q14.95-10.96 32.51-8.6 17.55 2.36 28.51 17.32 10.96 15.19 8.72 32.75-2.24 17.55-17.44 28.51ZM271.87-351.87H152.59q-19.16 0-32.33-13.17-13.17-13.18-13.17-32.33v-165.26q0-19.15 13.17-32.33 13.17-13.17 32.33-13.17h119.28L405.54-741.8q21.63-21.64 49.6-9.82 27.97 11.82 27.97 42.4v458.44q0 30.58-27.97 42.4t-49.6-9.82L271.87-351.87Z"/></svg>
                    <span class="promo-text">Career starts here, apply for internships!</span>
                </div>
            <?php elseif ($loginMethod === 'google'): ?>
                <div class="alert-promo-banner">
                <svg xmlns="http://www.w3.org/2000/svg" height="28px" viewBox="0 -960 960 960" width="28px" fill="#856404"><path d="M320-160h320v-120q0-66-47-113t-113-47q-66 0-113 47t-47 113v120ZM200-80q-17 0-28.5-11.5T160-120q0-17 11.5-28.5T200-160h40v-120q0-61 28.5-114.5T348-480q-51-32-79.5-85.5T240-680v-120h-40q-17 0-28.5-11.5T160-840q0-17 11.5-28.5T200-880h560q17 0 28.5 11.5T800-840q0 17-11.5 28.5T760-800h-40v120q0 61-28.5 114.5T612-480q51 32 79.5 85.5T720-280v120h40q17 0 28.5 11.5T800-120q0 17-11.5 28.5T760-80H200Z"/></svg>
                    <span class="alert-promo-text">Career starts here, be verified to apply!</span>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <p class="help-text">
            Have questions? Talk with our innovative AI <a href="#chatbot" class="help-link">Roger</a>
        </p>
    </div>
    <div class="divbg">
        <div class="container">
            <div class="job-listings-container">
                <div class="job-count"><?php echo $job_number; ?> Internships Recommended</div>
                <div class="job-listings">
                <?php if (!empty($job_listings)): ?>
                                <?php foreach ($job_listings as $job): ?>
                                    <div class="job-card"
                                        data-login-method="<?php echo htmlspecialchars($user['login_method']); ?>"
                                        data-id="<?php echo htmlspecialchars($job['internship_id']); ?>" 
                                        data-has-applied="<?php echo htmlspecialchars($job['has_applied']); ?>"
                                        data-is-hired="<?php echo htmlspecialchars($job['is_hired']); ?>"
                                        data-is-hired-for-this-ad="<?php echo htmlspecialchars($job['is_hired_for_this_ad']); ?>"
                                        data-company-id="<?php echo htmlspecialchars($job['company_id']); ?>"
                                        data-total-rating="<?php echo htmlspecialchars($job['total_rating']); ?>"
                                        data-total-reviews="<?php echo htmlspecialchars($job['total_reviews']); ?>"
                                        data-company-logo="<?php echo htmlspecialchars($job['company_logo']); ?>"
                                        data-company-name="<?php echo htmlspecialchars($job['company_name']); ?>"
                                        data-company-overview="<?php echo htmlspecialchars($job['company_overview']); ?>"
                                        data-company-address="<?php echo htmlspecialchars($job['company_address']); ?>"
                                        data-company-phone="<?php echo htmlspecialchars($job['company_phone']); ?>"
                                        data-company-email="<?php echo htmlspecialchars($job['company_email']); ?>"
                                        data-internship-title="<?php echo htmlspecialchars($job['internship_title']); ?>"
                                        data-internship-type="<?php echo htmlspecialchars($job['internship_type']); ?>"
                                        data-internship-description="<?php echo htmlspecialchars($job['internship_description']); ?>"
                                        data-internship-summary="<?php echo htmlspecialchars($job['internship_summary']); ?>"
                                        data-number-of-openings="<?php echo htmlspecialchars($job['number_of_openings']); ?>"
                                        data-duration="<?php echo htmlspecialchars($job['duration']); ?>"
                                        data-department="<?php echo htmlspecialchars($job['department']); ?>"
                                        data-requirements="<?php echo htmlspecialchars($job['requirements']); ?>"
                                        data-qualifications="<?php echo htmlspecialchars($job['qualifications']); ?>"
                                        data-skills-required="<?php echo htmlspecialchars($job['skills_required']); ?>"
                                        data-latitude="<?php echo htmlspecialchars($job['latitude'] ?? ''); ?>"
                                        data-longitude="<?php echo htmlspecialchars($job['longitude'] ?? ''); ?>"
                                        data-application-deadline="<?php echo htmlspecialchars($job['application_deadline']); ?>"
                                        data-additional-info="<?php echo htmlspecialchars($job['additional_info']); ?>"
                                        
                                        data-posted-time="<?php echo htmlspecialchars(timeAgo($job['created_at'])); ?>"> 
                                         
                                        
                                        <div class="job-titles"><?php echo htmlspecialchars($job['internship_title']); ?></div>
                                        <div class="company-info">
                                            <img src="../RECRUITER/<?php echo htmlspecialchars($job['company_logo']); ?>" alt="Company Logo" class="company-logo">
                                            <div class="company-details">
                                                <div class="company-name"><?php echo htmlspecialchars($job['company_name']); ?></div>
                                                <div class="company-location"><?php echo htmlspecialchars($job['company_address']); ?></div>
                                                <div class="company-distance" 
                                                    data-lat="<?php echo htmlspecialchars($job['latitude'] ?? ''); ?>" 
                                                    data-lon="<?php echo htmlspecialchars($job['longitude'] ?? ''); ?>">
                                                    Calculating distance...
                                                </div>
                                            </div>
                                        </div>
                                        <div class="internship-description">
                                            <?php echo htmlspecialchars(truncate($job['internship_description'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <div class="no-results">No internships found matching your criteria.</div>
                            <?php endif; ?>
        </div>


        </div>
        <div class="job-offers">
        </div>
        </div>
    </div>


    <!-- ABOUT SECTION -->
    <div class="scrolling" id="about">
    <div class="about-hero" >
            <div class="about-heading">
                <h1>University of Caloocan City - Intern<span>flo.</span></h1>
                <p>Explore our <span style="color: #ff8c00; font-weight: 600">dedication</span> to supporting University of Caloocan City students. Learn how we create opportunities <br> and build connections that drive their future success.</p>
            </div>
            <div class="about-container">
                <div class="about-hero-content">
                    <h2>About us</h2>
                    <p>
                        The <span style="color: #ff8c00; font-weight: 600">University of Caloocan City</span> (abbreviated as UCC) is a public-type local university established in 1971 and formerly called Caloocan City Community College and Caloocan City Polytechnic College. Its south campus is located at Biglang Awa St., Grace Park East, 12th Avenue, Caloocan, Metro Manila, Philippines (also known as EDSA/Biglang Awa Campus) and the north campuses are Camarin Business Campus, Congressional Campus, and Engineering Campus (Barangay 176, Bagong Silang).
                    </p>

                    <p>
                        At <span style="color: #ff8c00; font-weight: 600">Internflo</span>, we aim to connect University of Caloocan City students with meaningful internships, helping them develop skills and prepare for their careers. Our platform fosters collaboration between students and industries, ensuring opportunities align with their aspirations and create a lasting impact on their growth.
                    </p>
                </div>
                <div class="about-hero-image">
                    <img id="dynamic-image" src="pics/pic.jpg" alt="Dynamic Image">
                </div>
            </div>
    </div>

    <div class="about-hero" >
            <div class="about-heading">
                <h1>Find out what our guiding <span>principles</span> are.</h1>
                <p><span style="color: #ff8c00; font-weight: 600">Learn </span>about who we are, our mission, and our vision. Find out what <br> motivates us to make a significant difference.</p>
            </div>
            <div class="about-container">
                <div class="about-hero-content">
                    <h2>Mission</h2>
                    <p>
                        A local government university with global quality of <span style="color: #ff8c00; font-weight: 600">education</span> imbued with desired knowledge, skills, and values for academic excellence, professional development, civic consciousness, resilient citizenry, technological advancement, ecological sustainability and continual improvement.
                    </p>
                </div>
                <div class="about-hero-content">
                    <h2>Vision</h2>
                    <p>
                        To <span style="color: #ff8c00; font-weight: 600">develop</span> academically excellent, professionally progressive, industry sensitive, environmentally and technologically conscious, globally competitive and resilient graduates through quality instruction, functional co-curricular activities, responsive community immersion programs, intensive research and development and continually improved quality management system molding them to become effective social and cultural agents of change.
                    </p>
                </div>
            </div>
    </div>
    </div>
    <script>
            const imageSources = [
                "pics/pic.jpg",
                "pics/south.jpg"
                
            ];

            let currentIndex = 0;
            const imageElement = document.getElementById('dynamic-image');

            function changeImage() {
                imageElement.style.opacity = 0; // Fade out

                setTimeout(() => {
                    currentIndex = (currentIndex + 1) % imageSources.length;
                    imageElement.src = imageSources[currentIndex]; 
                    imageElement.style.opacity = 1; // Fade in
                }, 1000); // Matches transition duration
            }

            setInterval(changeImage, 2000);
    </script>
    <!-- ABOUT SECTION -->
     
    <!-- FEATURES -->
    <section>
        <div class="about-heading">
                <h1><span>Services</span> we provide</h1>
                <p>Find out how our services help transform challenges into <span style="color: #ff8c00; font-weight: 600"> opportunities</span> <br> for success and lasting growth.</p>
        </div>
        <div class="features-row">
                <!-- Column One -->
                <div class="features-column">
                <div class="features-card">
                    <div class="features-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" height="50px" viewBox="0 -960 960 960" width="50px" fill="#FFFFFF"><path d="M472.59-72.35q-83.24-1.43-156.36-34.01-73.12-32.57-127.24-87.53-54.12-54.96-85.5-128.64Q72.11-396.22 72.11-480q0-84.65 32.1-159.1 32.09-74.44 87.29-129.64 55.2-55.19 129.64-87.29 74.45-32.1 159.1-32.1 149.35 0 260.98 93.75 111.62 93.75 139.15 235.14h-93q-18.81-72.03-67.81-128.77-49-56.74-120.28-85.36v15.52q0 32.71-23.5 56-23.5 23.28-56.15 23.28h-79.15v79.22q0 16.83-11.5 28.33t-28.33 11.5h-79.22v79.04H400v118.81h-39.04L171.11-551.52q-3 17.76-5.5 35.52t-2.5 35.52q0 128.13 88.89 220.82 88.89 92.68 220.59 96.31v91Zm372.84-18.08L717.2-218.91q-20.77 11.28-44.17 18.8-23.4 7.52-49.44 7.52-76.44 0-130.13-53.69-53.7-53.7-53.7-130.18 0-76.47 53.7-130.01Q547.15-560 623.63-560q76.48 0 130.01 53.55 53.53 53.54 53.53 130.04 0 26.04-7.52 49.56-7.52 23.52-18.8 44.28l128.48 128.24-63.9 63.9ZM623.59-283.59q38.88 0 65.73-26.92t26.85-65.9q0-38.89-26.85-65.74Q662.47-469 623.58-469q-38.88 0-65.85 26.85-26.97 26.85-26.97 65.74 0 38.89 26.92 65.86 26.92 26.96 65.91 26.96Z"/></svg>
                    </div>
                    <h3>Google Map</h3>
                    <p>
                    <span style="color: #ff8c00; font-weight: 600">Interactive </span>map interface for students to discover and explore nearby internship opportunities, making location-based company searches quick and convenient.
                    </p>
                </div>
                </div>
                <!-- Column Two -->
                <div class="features-column">
                <div class="features-card">
                    <div class="features-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="#FFFFFF"><path d="M890-365.93H70q-14.42 0-24.24-9.88-9.83-9.87-9.83-24.37 0-14.49 9.83-24.19 9.82-9.7 24.24-9.7h820q14.42 0 24.24 9.88 9.83 9.87 9.83 24.37 0 14.49-9.83 24.19-9.82 9.7-24.24 9.7ZM582.91-631.85h154.94l-189-186v151.94q0 14.42 9.82 24.24 9.82 9.82 24.24 9.82ZM222.15-74.02q-28.1 0-48.11-20.02-20.02-20.01-20.02-48.11v-129.72q0-14.42 9.82-24.24 9.82-9.82 24.25-9.82h583.82q14.43 0 24.37 9.82 9.94 9.82 9.94 24.24v129.72q0 28.1-20.14 48.11-20.13 20.02-48.23 20.02h-515.7Zm-34.06-420.05q-14.43 0-24.25-9.82t-9.82-24.48v-289.48q0-28.1 20.02-48.23 20.01-20.14 48.11-20.14h332.89q14.12 0 26.95 5.72 12.84 5.72 22.03 14.91l181.57 181.57q9.19 9.19 14.91 22.03 5.72 12.83 5.72 26.95v107.39q0 14.42-9.7 24t-23.89 9.58H188.09Z"/></svg>
                    </div>
                    <h3>NLP AI Technology</h3>
                    <p>
                    <span style="color: #ff8c00; font-weight: 600">Smart</span> matching system that connects students with relevant internships by analyzing their skills and qualifications against company requirements.
                    </p>
                </div>
                </div>
                <!-- Column Three -->
                <div class="features-column">
                <div class="features-card">
                    <div class="features-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="#FFFFFF"><path d="M856.22-117.98q-5.96 0-12.3-2.36-6.33-2.36-11.81-7.83l-102.5-102.5H305.02q-28.59 0-48.36-19.9-19.77-19.89-19.77-48.47v-80h436.89q28.35 0 48.24-19.78 19.89-19.77 19.89-48.35v-276.66h80q28.59 0 48.48 19.9 19.89 19.89 19.89 48.47v503.42q0 15.91-10.81 24.99-10.82 9.07-23.25 9.07Zm-752.2-205.93q-12.67 0-23.49-9.08-10.81-9.08-10.81-24.99v-464.65q0-28.59 19.89-48.48Q109.5-891 138.09-891h475.69q28.35 0 48.24 19.89t19.89 48.48v315.46q0 28.58-19.89 48.35-19.89 19.78-48.24 19.78H232.59l-104.7 104.69q-5.72 5.72-11.93 8.08-6.22 2.36-11.94 2.36Z"/></svg>
                    </div>
                    <h3>Chatbot Support</h3>
                    <p>
                    <span style="color: #ff8c00; font-weight: 600">24/7</span> AI assistant that helps users navigate the platform, answers common questions, and guides them through the application process.
                    </p>
                </div>
                </div>
                <div class="features-column">
                <div class="features-card">
                    <div class="features-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="#FFFFFF"><path d="M825-303.57q-23.48 0-39.96-16.47-16.47-16.48-16.47-39.96v-100q0-23.48 16.47-39.96 16.48-16.47 39.96-16.47t39.96 16.47q16.47 16.48 16.47 39.96v100q0 23.48-16.47 39.96-16.48 16.47-39.96 16.47ZM583.7-503.11q-65.4 0-110.69-45.29-45.29-45.3-45.29-110.69t45.29-110.68q45.29-45.3 110.69-45.3 65.39 0 110.8 45.3 45.41 45.29 45.41 110.68 0 65.39-45.41 110.69-45.41 45.29-110.8 45.29Zm-252.85 352.2q-28.35 0-48.24-19.89-19.89-19.9-19.89-48.24v-32.33q0-36.67 19.45-66.61 19.46-29.93 52.89-44.65 47-21 109.22-40.74 62.22-19.74 139.42-19.74H585.85q12.19.24 18.65 10.18 6.46 9.93 2.98 22.6-7.76 29.48-6.26 60.08 1.5 30.6 10.5 59.08 6.76 19.52 17.02 38.28t23.54 35.28q11.96 14.63 5 30.66-6.95 16.04-22.87 16.04H330.85ZM825-153.3q-6.48 0-11.46-4.98-4.97-4.98-4.97-11.46v-49.56q-46.92-7.48-81.23-38.8-34.32-31.31-42.04-77.99-1.23-7.71 3.36-13.31 4.6-5.6 12.08-5.6 6.72 0 11.58 3.86 4.85 3.86 5.85 10.1 5 39.67 35.7 64.89 30.7 25.22 71.13 25.22 39.67 0 69.75-25.34t37.32-64.77q1.23-6.48 6.59-10.22 5.36-3.74 11.84-3.74 6.48 0 11.34 4.24 4.86 4.24 3.86 10.72-5.48 48.15-40.8 80.7-35.31 32.56-83.47 40.04v49.56q0 6.48-4.97 11.46-4.98 4.98-11.46 4.98ZM238.72-659.09q0-30.32 11.68-57.51 11.69-27.18 32.53-48.18 20.61-20.52 47.36-31.84 26.75-11.31 56.36-12.42 10.72-1.4 15.77 8.18 5.06 9.58-1.66 19.25-16.28 25.48-24.66 56.74-8.38 31.26-8.38 65.78 0 35 8.26 66.38 8.26 31.38 24.54 56.14 5.72 9.68 1.28 19.14-4.43 9.45-14.39 9.3-29.61-1.11-56.86-11.92-27.25-10.82-47.85-32.82-20.61-21.76-32.3-48.95-11.68-27.18-11.68-57.27ZM26.85-216.89v-34.48q0-36.04 16.42-61.56 16.43-25.53 48.75-45.48 27.65-15.81 67.07-28.47 39.41-12.66 89.34-21.19 11.2-3.67 17.09 7.65 5.89 11.31-3.59 20.9-32.84 27.09-46.03 59.93-13.18 32.85-13.18 68.22v32.33q0 10.15 1.98 20.56 1.97 10.41 5.41 20.33 3.48 10-1.78 18.62-5.27 8.62-15.5 8.62H93.07q-27.64 0-46.93-19.3-19.29-19.29-19.29-46.68Z"/></svg>
                    </div>
                    <h3>Video Conference</h3>
                    <p>
                    <span style="color: #ff8c00; font-weight: 600">Built-in</span> video call system for virtual interviews, meetings, progress monitoring, weekly consultations between students, advisors, and company recruiters.
                    </p>
                </div>
                </div>
        </div>
    </section>
    <!-- FEATURES -->


    <!-- AI CHATBOT -->
    <div class="scrolling" id="aichat">
        <div class="about-heading">
            <h1>Talk to<span> Roger</span></h1>
            <p><span style="color: #ff8c00; font-weight: 600">Roger</span> is an AI assistant providing quick, accurate answers and support for <br> your queries, ensuring a seamless user experience.</p>
        </div>
        <div class="aidiv">
            <iframe
                src="https://www.chatbase.co/chatbot-iframe/tvT2yOZfZHWitE4rTdXNG"
                width="100%"
                style="height: 100%; min-height: 500px; border: 3px solid #2e3849; border-radius: 8px; margin: 0 0 50px 0;"
                frameborder="0"
            ></iframe>
        </div>
    </div>
    <!-- AI CHATBOT -->

    <!-- CHAT WIDGET -->
    <script>
        (function(){if(!window.chatbase||window.chatbase("getState")!=="initialized"){window.chatbase=(...arguments)=>{if(!window.chatbase.q){window.chatbase.q=[]}window.chatbase.q.push(arguments)};window.chatbase=new Proxy(window.chatbase,{get(target,prop){if(prop==="q"){return target.q}return(...args)=>target(prop,...args)}})}const onLoad=function(){const script=document.createElement("script");script.src="https://www.chatbase.co/embed.min.js";script.id="qEhc2yKw7YIylj99unQ0q";script.domain="www.chatbase.co";document.body.appendChild(script)};if(document.readyState==="complete"){onLoad()}else{window.addEventListener("load",onLoad)}})();
    </script>
    <!-- CHAT WIDGET -->

    <!-- CONTACT SECTION -->
    <div class="scrolling" id="contact">
            <div class="about-hero">
                <div class="about-heading">
                    <h1>Send us your <span>feedback</span></h1>
                    <p>We value your <span style="color: #ff8c00; font-weight: 600">thoughts</span> and <span style="color: #ff8c00; font-weight: 600">opinions</span>  your feedback helps us <br> grow, improve, and better serve your needs.</p>
                </div>
                <div class="contact-container">
                    <div class="about-hero-map">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3858.256292214547!2d121.02833497457567!3d14.754585573289205!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b1cc9c9c83e9%3A0x303a03298da24ddb!2sUniversity%20of%20Caloocan%20City%20-%20Congressional%20Campus!5e0!3m2!1sen!2sph!4v1734309290308!5m2!1sen!2sph" width="100%" height="520" style="border: 3px solid #2e3849; border-radius: 8px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>

                    <div class="about-hero-form">
                        <h2>University of Caloocan City</h2>
                        <p>Congressional Campus</p> 
                        <form id="feedbackForm" action="submit_feedback.php" method="POST">
                            <div class="form-group">
                                <input type="text" id="name" name="name" placeholder="Name (Optional)" oninput="capitalizeFirstLetter(this)">
                            </div>
                            <div class="form-group">
                                <input type="text" id="email" name="email" placeholder="Email">
                            </div>
                            <div class="form-group">
                                <input type="text" id="title" name="title" placeholder="Title (Subject of your concern)" oninput="capitalizeFirstLetter(this)">
                            </div>
                            <div class="form-group">
                                <textarea id="message" name="message" placeholder="Type your message here..." oninput="capitalizeFirstLetter(this)"></textarea>
                            </div>
                            <button type="submit" class="about-cta-button">Send 
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
                                    <path d="M176.24-178.7q-22.87 9.44-43.26-3.85-20.39-13.3-20.39-38.17V-393.3l331-86.7-331-86.7v-172.58q0-24.87 20.39-38.17 20.39-13.29 43.26-3.85l613.61 259.28q28.11 12.43 28.11 42.02 0 29.59-28.11 42.02L176.24-178.7Z"/>
                                </svg>
                            </button>
                        </form>
                        <script>
                            function capitalizeFirstLetter(input) {
                                if (input.value.length === 1) {
                                    input.value = input.value.charAt(0).toUpperCase() + input.value.slice(1).toLowerCase();
                                }
                            }
                            document.getElementById('feedbackForm').addEventListener('submit', function(event) {
                                event.preventDefault(); // Prevent default form submission

                                // Get the submit button and its contents
                                const submitButton = this.querySelector('button[type="submit"]');
                                const originalButtonContent = submitButton.innerHTML;
                                const successSvg = `<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q48 0 93.5 11t87.5 32q15 8 19.5 24t-5.5 30q-10 14-26.5 18t-32.5-4q-32-15-66.5-23t-69.5-8q-133 0-226.5 93.5T160-480q0 133 93.5 226.5T480-160q133 0 226.5-93.5T800-480q0-8-.5-15.5T798-511q-2-17 6.5-32.5T830-564q16-5 30 3t16 24q2 14 3 28t1 29q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm-56-328 372-373q11-11 27.5-11.5T852-781q11 11 11 28t-11 28L452-324q-12 12-28 12t-28-12L282-438q-11-11-11-28t11-28q11-11 28-11t28 11l86 86Z"/></svg>`;
                                
                                const fields = this.querySelectorAll('[name="email"], [name="title"], [name="message"]');
                                let isFormValid = true;

                                fields.forEach((field) => {
                                    // Remove any previous error styling and messages
                                    const existingErrorMessage = field.parentNode.querySelector('.error-message');
                                    if (existingErrorMessage) {
                                        existingErrorMessage.remove();
                                    }
                                    field.style.border = '';
                                    field.style.boxShadow = '';

                                    // Check if required fields are empty
                                    if (!field.value.trim()) {
                                        field.style.border = '2px solid red';
                                        field.style.boxShadow = '0 0 0 0.3rem rgba(255, 0, 0, 0.25)';

                                        const errorMsg = document.createElement('span');
                                        errorMsg.classList.add('error-message');
                                        errorMsg.style.color = 'red';
                                        errorMsg.textContent = 'This field is required';
                                        field.parentNode.appendChild(errorMsg);

                                        isFormValid = false;
                                    }

                                    // Email validation
                                    if (field.name === 'email' && field.value.trim() !== '') {
                                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                                        if (!emailRegex.test(field.value.trim())) {
                                            field.style.border = '2px solid red';
                                            field.style.boxShadow = '0 0 0 0.3rem rgba(255, 0, 0, 0.25)';

                                            const errorMsg = document.createElement('span');
                                            errorMsg.classList.add('error-message');
                                            errorMsg.style.color = 'red';
                                            errorMsg.textContent = 'Please enter a valid email address';
                                            field.parentNode.appendChild(errorMsg);

                                            isFormValid = false;
                                        }
                                    }
                                });

                                // Dynamic input validation
                                fields.forEach((field) => {
                                    field.addEventListener('input', function() {
                                        // Remove existing error message
                                        const existingErrorMessage = this.parentNode.querySelector('.error-message');
                                        if (existingErrorMessage) {
                                            existingErrorMessage.remove();
                                        }

                                        // Check if the field is now empty
                                        if (!this.value.trim()) {
                                            this.style.border = '2px solid red';
                                            this.style.boxShadow = '0 0 0 0.3rem rgba(255, 0, 0, 0.25)';

                                            const errorMsg = document.createElement('span');
                                            errorMsg.classList.add('error-message');
                                            errorMsg.style.color = 'red';
                                            errorMsg.textContent = 'This field is required';
                                            this.parentNode.appendChild(errorMsg);
                                        } else {
                                            // Check email validation if it's an email field
                                            if (this.name === 'email') {
                                                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                                                if (!emailRegex.test(this.value.trim())) {
                                                    this.style.border = '2px solid red';
                                                    this.style.boxShadow = '0 0 0 0.3rem rgba(255, 0, 0, 0.25)';

                                                    const errorMsg = document.createElement('span');
                                                    errorMsg.classList.add('error-message');
                                                    errorMsg.style.color = 'red';
                                                    errorMsg.textContent = 'Please enter a valid email address';
                                                    this.parentNode.appendChild(errorMsg);
                                                } else {
                                                    // Valid email
                                                    this.style.border = '2px solid green';
                                                    this.style.boxShadow = 'none';
                                                }
                                            } else {
                                                // Non-email fields turn green when not empty
                                                this.style.border = '2px solid green';
                                                this.style.boxShadow = 'none';
                                            }
                                        }
                                    });

                                    // Add blur event listener to reset styling
                                    field.addEventListener('blur', function() {
                                        // If field is empty
                                        if (!this.value.trim()) {
                                            this.style.border = '2px solid red';
                                            this.style.boxShadow = '0 0 0 0.3rem rgba(255, 0, 0, 0.25)';

                                            // Add error message if not already present
                                            if (!this.parentNode.querySelector('.error-message')) {
                                                const errorMsg = document.createElement('span');
                                                errorMsg.classList.add('error-message');
                                                errorMsg.style.color = 'red';
                                                errorMsg.textContent = 'This field is required';
                                                this.parentNode.appendChild(errorMsg);
                                            }
                                        } else if (this.name === 'email') {
                                            // For email field, check email validity
                                            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                                            if (!emailRegex.test(this.value.trim())) {
                                                // Keep red border and error message for invalid email
                                                this.style.border = '2px solid red';
                                                this.style.boxShadow = '0 0 0 0.3rem rgba(255, 0, 0, 0.25)';

                                                // Ensure error message is present
                                                if (!this.parentNode.querySelector('.error-message')) {
                                                    const errorMsg = document.createElement('span');
                                                    errorMsg.classList.add('error-message');
                                                    errorMsg.style.color = 'red';
                                                    errorMsg.textContent = 'Please enter a valid email address';
                                                    this.parentNode.appendChild(errorMsg);
                                                }
                                            } else {
                                                // Reset to default for valid email
                                                this.style.border = '';
                                                this.style.boxShadow = '';
                                                const existingErrorMessage = this.parentNode.querySelector('.error-message');
                                                if (existingErrorMessage) {
                                                    existingErrorMessage.remove();
                                                }
                                            }
                                        } else {
                                            // Reset to default for non-email fields
                                            this.style.border = '';
                                            this.style.boxShadow = '';

                                            // Remove any existing error messages
                                            const existingErrorMessage = this.parentNode.querySelector('.error-message');
                                            if (existingErrorMessage) {
                                                existingErrorMessage.remove();
                                            }
                                        }
                                    });
                                });


                                    if (isFormValid) {
                                    submitButton.disabled = true;
                                    submitButton.innerHTML = 'Sending... <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M176.24-178.7q-22.87 9.44-43.26-3.85-20.39-13.3-20.39-38.17V-393.3l331-86.7-331-86.7v-172.58q0-24.87 20.39-38.17 20.39-13.29 43.26-3.85l613.61 259.28q28.11 12.43 28.11 42.02 0 29.59-28.11 42.02L176.24-178.7Z"/></svg>';

                                    const formData = new FormData(this);
                                    
                                    fetch('submit_feedback.php', {
                                        method: 'POST',
                                        body: formData
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            // Completely replace button content while preserving classes
                                            submitButton.innerHTML = 'Feedback sent successfully ' + successSvg;
                                            
                                            // Revert button after a few seconds
                                            setTimeout(() => {
                                                submitButton.innerHTML = originalButtonContent;
                                                submitButton.disabled = false;
                                            }, 3000);
                                        } else {
                                            // Error handling remains the same
                                            submitButton.innerHTML = 'Feedback failed to send <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm-40-160 40-40 40 40 56-56-40-40 40-40-56-56-40 40-40-40-56 56 40 40-40 40 56 56Zm40-280Z"/></svg>';
                                            submitButton.style.backgroundColor = '#ff0000'; // Red for error

                                            // Revert button after a few seconds
                                            setTimeout(() => {
                                                submitButton.innerHTML = originalButtonContent;
                                                submitButton.disabled = false;
                                                submitButton.style.backgroundColor = '#4aa629'; // Return to original color
                                            }, 3000);
                                        }
                                    })
                                    .catch(error => {
                                        // Error handling remains the same
                                        console.error('Error:', error);
                                        submitButton.innerHTML = 'Feedback failed to send <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm-40-160 40-40 40 40 56-56-40-40 40-40-56-56-40 40-40-40-56 56 40 40-40 40 56 56Zm40-280Z"/></svg>';
                                        submitButton.style.backgroundColor = '#ff0000'; // Red for error

                                        // Revert button after a few seconds
                                        setTimeout(() => {
                                            submitButton.innerHTML = originalButtonContent;
                                            submitButton.disabled = false;
                                            submitButton.style.backgroundColor = '#4aa629'; // Return to original color
                                        }, 3000);
                                    });
                                    }
                                });
                        </script>
                    </div>
                </div>
            </div>
    </div>
    <!-- CONTACT SECTION -->


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
<script src="studentfrontpage.js"></script>

</body>
</html>





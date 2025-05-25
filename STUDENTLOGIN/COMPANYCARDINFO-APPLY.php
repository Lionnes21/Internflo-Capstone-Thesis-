<?php
    session_start();

    $isLoggedIn = isset($_SESSION['user_id']);

    // Initialize variables
    $initials = '';
    $fullName = '';
    $email = '';
    $fullName2 = '';
    $profile_pic = 'pics/default_profile.jpg';

    // Database connection
    $conn = new mysqli("localhost", "u798912504_root", "Internfloucc2025*", "u798912504_internflo");

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // If logged in, fetch user details from the database
    if ($isLoggedIn) {
        $userId = $_SESSION['user_id'];
        $sql = 'SELECT first_name, middle_name, last_name, suffix, email, name, profile_pic, login_method FROM students WHERE id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            $loginMethod = $user['login_method'];
            $fullName2 = trim($user['first_name'] . 
                            (!empty($user['middle_name']) ? ' ' . $user['middle_name'] : '') . 
                            ' ' . $user['last_name'] . 
                            (!empty($user['suffix']) ? ' ' . $user['suffix'] : ''));

            if (empty($fullName2) && !empty($user['name'])) {
                $fullName2 = $user['name'];
            }

            if (!empty($user['profile_pic'])) {
                $profile_pic = $user['profile_pic'];
            }
        }
    }

    // Get all parameters from URL
    $company_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $internship_id = isset($_GET['internship_id']) ? intval($_GET['internship_id']) : 0;
    $industry = isset($_GET['industry']) ? $_GET['industry'] : '';
    $matched_texts = isset($_GET['texts']) ? $_GET['texts'] : '';

    // Store the original searched keywords for display purposes
    $original_keywords = $matched_texts;

    if ($company_id > 0) {
        // Fetch company information
        $sql = "SELECT company_name, company_logo, company_email, industry, 
                    company_address, company_overview, total_rating, total_reviews 
                FROM approvedrecruiters 
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $company = $result->fetch_assoc();

        if (!$company) {
            die("Company not found");
        }

        // Fetch company reviews
        $sql_reviews = "SELECT cr.*, 
            s.first_name as reviewer_first_name,
            s.last_name as reviewer_last_name,
            cr.created_at
        FROM company_reviews cr
        JOIN students s ON cr.student_id = s.id
        WHERE cr.company_id = ?
        ORDER BY cr.created_at DESC";
            
        $stmt_reviews = $conn->prepare($sql_reviews);
        $stmt_reviews->bind_param("i", $company_id);
        $stmt_reviews->execute();
        $reviews_result = $stmt_reviews->get_result();

        // Update company rating and review count
        $sql_rating = "SELECT 
                        COUNT(*) as total_reviews,
                        ROUND(AVG(rating), 1) as average_rating
                    FROM company_reviews 
                    WHERE company_id = ?";
        
        $stmt_rating = $conn->prepare($sql_rating);
        $stmt_rating->bind_param("i", $company_id);
        $stmt_rating->execute();
        $rating_result = $stmt_rating->get_result();
        $rating_info = $rating_result->fetch_assoc();

        // Update the company array with latest rating info
        $company['total_rating'] = $rating_info['average_rating'] ?? 0;
        $company['total_reviews'] = $rating_info['total_reviews'] ?? 0;

        // Internship query logic with priority for internship_id
        if ($internship_id > 0) {
            // Filter by both company ID and internship ID
            $sql_internships = "SELECT ia.internship_id, 
                                ia.internship_title,
                                ia.internship_description,
                                ia.created_at,
                                ar.company_address,
                                ia.industry,
                                ia.keywords
                        FROM internshipad ia
                        JOIN approvedrecruiters ar ON ia.user_id = ar.id 
                        WHERE ia.user_id = ? AND ia.internship_id = ?";
                        
            $stmt_internships = $conn->prepare($sql_internships);
            $stmt_internships->bind_param("ii", $company_id, $internship_id);
            
        } else if (!empty($matched_texts)) {
            // Split the comma-separated keywords
            $keywords_array = explode(',', $matched_texts);
            
            // Build the SQL condition for keywords
            $keyword_conditions = [];
            $param_types = "i";
            $params = [$company_id];
            
            foreach ($keywords_array as $keyword) {
                $keyword = trim($keyword);
                // For each individual keyword, we create conditions to match in different positions
                $keyword_conditions[] = "(ia.keywords LIKE ? OR ia.keywords LIKE ? OR ia.keywords LIKE ?)";
                $param_types .= "sss";
                $params[] = "$keyword,%";  // Match at beginning of list
                $params[] = "%, $keyword,%"; // Match in middle of list
                $params[] = "%, $keyword";  // Match at end of list
            }
            
            // Combine keyword conditions with OR - this matches ANY of the keywords
            $keyword_condition_sql = implode(" OR ", $keyword_conditions);
            
            $sql_internships = "SELECT ia.internship_id, 
                                ia.internship_title,
                                ia.internship_description,
                                ia.created_at,
                                ar.company_address,
                                ia.industry,
                                ia.keywords
                        FROM internshipad ia
                        JOIN approvedrecruiters ar ON ia.user_id = ar.id 
                        WHERE ia.user_id = ? AND ($keyword_condition_sql)";
                        
            $stmt_internships = $conn->prepare($sql_internships);
            
            // Bind parameters dynamically
            $bind_params = array($param_types);
            foreach ($params as $key => $value) {
                $bind_params[] = &$params[$key];
            }
            call_user_func_array(array($stmt_internships, 'bind_param'), $bind_params);
            
        } else if (!empty($industry)) {
            $sql_internships = "SELECT ia.internship_id, 
                                ia.internship_title,
                                ia.internship_description,
                                ia.created_at,
                                ar.company_address,
                                ia.industry,
                                ia.keywords
                        FROM internshipad ia
                        JOIN approvedrecruiters ar ON ia.user_id = ar.id 
                        WHERE ia.user_id = ? AND ia.industry = ?";
                        
            $stmt_internships = $conn->prepare($sql_internships);
            $stmt_internships->bind_param("is", $company_id, $industry);
            
        } else {
            $sql_internships = "SELECT ia.internship_id, 
                                ia.internship_title,
                                ia.internship_description,
                                ia.created_at,
                                ar.company_address,
                                ia.industry,
                                ia.keywords
                        FROM internshipad ia
                        JOIN approvedrecruiters ar ON ia.user_id = ar.id 
                        WHERE ia.user_id = ?";
                        
            $stmt_internships = $conn->prepare($sql_internships);
            $stmt_internships->bind_param("i", $company_id);
        }
        
    } else {
        // Handle case with no company ID
        if ($internship_id > 0) {
            $sql_internships = "SELECT ia.internship_id, 
                                ia.internship_title,
                                ia.internship_description,
                                ia.created_at,
                                ar.company_address,
                                ia.industry,
                                ar.company_name,
                                ia.keywords
                        FROM internshipad ia
                        JOIN approvedrecruiters ar ON ia.user_id = ar.id 
                        WHERE ia.internship_id = ?";
                        
            $stmt_internships = $conn->prepare($sql_internships);
            $stmt_internships->bind_param("i", $internship_id);
            
        } else if (!empty($matched_texts)) {
            // Split the comma-separated keywords
            $keywords_array = explode(',', $matched_texts);
            
            // Build the SQL condition for keywords
            $keyword_conditions = [];
            $param_types = "";
            $params = [];
            
            foreach ($keywords_array as $keyword) {
                $keyword = trim($keyword);
                // More precise matching accounting for format with spaces after commas
                $keyword_conditions[] = "(ia.keywords LIKE ? OR ia.keywords LIKE ? OR ia.keywords LIKE ?)";
                $param_types .= "sss";
                $params[] = "$keyword,%";  // Match at beginning of list
                $params[] = "%, $keyword,%"; // Match in middle of list
                $params[] = "%, $keyword";  // Match at end of list
            }
            
            // Combine keyword conditions with OR
            $keyword_condition_sql = implode(" OR ", $keyword_conditions);
            
            $sql_internships = "SELECT ia.internship_id, 
                                ia.internship_title,
                                ia.internship_description,
                                ia.created_at,
                                ar.company_address,
                                ia.industry,
                                ar.company_name,
                                ia.keywords
                        FROM internshipad ia
                        JOIN approvedrecruiters ar ON ia.user_id = ar.id 
                        WHERE $keyword_condition_sql";
                        
            $stmt_internships = $conn->prepare($sql_internships);
            
            // Bind parameters dynamically
            $bind_params = array($param_types);
            foreach ($params as $key => $value) {
                $bind_params[] = &$params[$key];
            }
            call_user_func_array(array($stmt_internships, 'bind_param'), $bind_params);
            
        } else if (!empty($industry)) {
            $sql_internships = "SELECT ia.internship_id, 
                                ia.internship_title,
                                ia.internship_description,
                                ia.created_at,
                                ar.company_address,
                                ia.industry,
                                ar.company_name,
                                ia.keywords
                        FROM internshipad ia
                        JOIN approvedrecruiters ar ON ia.user_id = ar.id 
                        WHERE ia.industry = ?";
                        
            $stmt_internships = $conn->prepare($sql_internships);
            $stmt_internships->bind_param("s", $industry);
        }
    }

    // Execute internship query and get results
    $stmt_internships->execute();
    $internships_result = $stmt_internships->get_result();
    $total_jobs = $internships_result->num_rows;

    // Helper functions
    function truncateText($text, $limit = 220) {
        if (strlen($text) > $limit) {
            return substr($text, 0, $limit) . '....';
        }
        return $text;
    }

    function getStudentDisplayName($firstName, $lastName, $fullName) {
        if (!empty($fullName)) {
            return htmlspecialchars($fullName);
        }
        return htmlspecialchars(trim($firstName . ' ' . $lastName));
    }

    function maskName($name) {
        if (empty($name)) return '';
        return substr($name, 0, 1) . str_repeat('*', strlen($name) - 1);
    }

    // Find exact matches in a comma-separated list
    function findExactMatches($keywordsList, $searchTerms) {
        $matches = [];
        $keywordsArray = explode(',', $keywordsList);
        $searchTermsArray = explode(',', $searchTerms);
        
        foreach ($keywordsArray as $keyword) {
            $keyword = trim($keyword);
            foreach ($searchTermsArray as $searchTerm) {
                $searchTerm = trim($searchTerm);
                if (strcasecmp($keyword, $searchTerm) === 0) {
                    $matches[] = $keyword;
                }
            }
        }
        
        return $matches;
    }
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="NAVX.css">
    <link rel="stylesheet" href="../css/FOOTER.css">
    <link rel="stylesheet" href="../css/COMPANYCARDINFO.css">
    <link rel="icon" href="pics/ucclogo2.png">
    <title>UCC - Company</title>
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
    <!-- NAVIGATION -->


    <!-- MAIN CONTENT -->
    <div class="width">
        
        <div class="company-card">
            <div class="company-header-section">
                <!-- Company Header -->
                <div class="header">
                    <img src="../RECRUITER/<?php echo htmlspecialchars($company['company_logo']); ?>" 
                        alt="<?php echo htmlspecialchars($company['company_name']); ?>" 
                        class="logo">
                    <h1 class="name"><?php echo htmlspecialchars($company['company_name']); ?></h1>
                </div>

                <!-- Rating Section -->
                <div class="rating">
                    <div class="stars">
                        <?php
                        $rating = floatval($company['total_rating']);
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $rating) {
                                // Full star
                                echo '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ff9800"><path d="M480-259.91 313.28-159.43q-12.67 7.95-26.35 6.83-13.67-1.12-23.86-9.07-10.2-7.96-15.8-20.01-5.6-12.06-2.12-26.73l44.24-189.72-147.72-127.72q-11.43-10.19-14.29-23.25-2.86-13.05 1.38-25.49 4.24-12.43 13.79-20.63 9.56-8.19 25.23-10.19l194.72-17 75.48-178.96q5.72-13.91 17.53-20.63 11.82-6.72 24.49-6.72 12.67 0 24.49 6.72 11.81 6.72 17.53 20.63l75.48 178.96 194.72 17q15.67 2 25.23 10.19 9.55 8.2 13.79 20.63 4.24 12.44 1.38 25.49-2.86 13.06-14.29 23.25L670.61-398.13l44.24 189.72q3.48 14.67-2.12 26.73-5.6 12.05-15.8 20.01-10.19 7.95-23.86 9.07-13.68 1.12-26.35-6.83L480-259.91Z"/></svg>';
                            } else {
                                // Empty star
                                echo '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ccc"><path d="M480-259.91 313.28-159.43q-12.67 7.95-26.35 6.83-13.67-1.12-23.86-9.07-10.2-7.96-15.8-20.01-5.6-12.06-2.12-26.73l44.24-189.72-147.72-127.72q-11.43-10.19-14.29-23.25-2.86-13.05 1.38-25.49 4.24-12.43 13.79-20.63 9.56-8.19 25.23-10.19l194.72-17 75.48-178.96q5.72-13.91 17.53-20.63 11.82-6.72 24.49-6.72 12.67 0 24.49 6.72 11.81 6.72 17.53 20.63l75.48 178.96 194.72 17q15.67 2 25.23 10.19 9.55 8.2 13.79 20.63 4.24 12.44 1.38 25.49-2.86 13.06-14.29 23.25L670.61-398.13l44.24 189.72q3.48 14.67-2.12 26.73-5.6 12.05-15.8 20.01-10.19 7.95-23.86 9.07-13.68 1.12-26.35-6.83L480-259.91Z"/></svg>';
                            }
                        }
                        ?>
                    </div>
                    <span class="rating-text"><?php echo number_format($company['total_rating'], 1); ?> total rating from</span>
                    <a href="#" class="review-link"><?php echo $company['total_reviews']; ?> reviews</a>
                    </div>
                    <div class="review-container">
                        <a href="#" class="write-review">
                            Write Review
                            <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#ffffff"><path d="M168-121q-21 5-36.5-10.5T121-168l35-170 182 182-170 35Zm235-84L205-403l413-413q23-23 57-23t57 23l84 84q23 23 23 57t-23 57L403-205Z"/></svg>
                        </a>
                    </div>
                </div>
            
                <!-- Navigation -->
                <div class="nav-wrapper">
                    <nav class="navigation">
                        <a href="#" class="nav-item active">Internships</a>
                        <a href="#" class="nav-item">About</a>
                        <a href="#" class="nav-item">Reviews</a>
                    </nav>
                </div>

                <!-- Company Information -->
                <section class="info" style="display: none;">
                    <br>
                    <h2>Overview</h2>
                    
                        <div class="info-grid">
                            <div class="info-label">Website</div>
                            <div class="info-value">
                                <a href="mailto:<?php echo htmlspecialchars($company['company_email']); ?>">
                                    <?php echo htmlspecialchars($company['company_email']); ?>
                                </a>
                                <svg xmlns="http://www.w3.org/2000/svg" height="22px" viewBox="0 -960 960 960" width="22px" fill="#0000ee">
                                    <path d="M202.87-111.87q-37.78 0-64.39-26.61t-26.61-64.39v-554.26q0-37.78 26.61-64.39t64.39-26.61H480v91H202.87v554.26h554.26V-480h91v277.13q0 37.78-26.61 64.39t-64.39 26.61H202.87ZM395.41-332 332-395.41l361.72-361.72H560v-91h288.13V-560h-91v-133.72L395.41-332Z"/>
                                </svg>
                            </div>
                            
                            <div class="info-label">Industry</div>
                            <div class="info-value"><?php echo htmlspecialchars($company['industry']); ?></div>
                            
                            <div class="info-label">Primary location</div>
                            <div class="info-value"><?php echo htmlspecialchars($company['company_address']); ?></div>
                        </div>

                    <p class="description">
                        <?php echo nl2br(htmlspecialchars($company['company_overview'])); ?>
                    </p>
                </section>

                <!-- Internship Information -->
                <section class="internships-section" >
                    <div class="search-section">
                        <br>
                        <h2>Refine by Internship type</h2>
                        <div class="search-container">
                            <input type="text" placeholder="eg: Software Engineer" class="search-input">
                            <button class="search-button">Search</button>
                        </div>
                        <p class="results-count"><span><?php echo $total_jobs; ?></span> internships in <?php echo htmlspecialchars($company['company_name']); ?></p>
                    </div>

                    <div class="jobs-grid">
                        <?php
                        if ($internships_result->num_rows > 0) {
                            while($job = $internships_result->fetch_assoc()) {
                                // Calculate time difference
                                $created_date = new DateTime($job['created_at']);
                                $now = new DateTime();
                                $interval = $created_date->diff($now);
                                
                                if ($interval->d > 0) {
                                    $posted_time = $interval->d . "d ago";
                                } else if ($interval->h > 0) {
                                    $posted_time = $interval->h . "h ago";
                                } else {
                                    $posted_time = $interval->i . "m ago";
                                }
                                
                                // Truncate the description
                                $truncated_description = truncateText($job['internship_description']);
                                
                                echo '<div class="job-card" onclick="window.location.href=\'studentfrontpageview.php?id=' . $job['internship_id'] . '\'" style="cursor: pointer;">';
                                echo '<h3>' . htmlspecialchars($job['internship_title']) . '</h3>';
                                echo '<p class="location">' . htmlspecialchars($job['company_address']) . '</p>';
                                echo '<p class="internship-description">' . htmlspecialchars($truncated_description) . '</p>';
                                echo '<p class="posted-time">' . $posted_time . '</p>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p class="no-jobs">No internships available at this time.</p>';       
                        }
                        ?>
                    </div>
                </section>

                <!-- Reviews -->
                <section class="reviews-section" style="display: none;">
                    <br>
                    <h2>Reviews</h2>
                    
                    <!-- Stats Overview -->
                    <div class="reviews-overview">
                        <div class="overall-rating">
                            <h3>Overall Rating</h3>
                            <div class="big-rating">
                                <span class="rating-number"><?php echo number_format($company['total_rating'], 1); ?></span>
                                <div class="stars">
                                    <?php
                                    $rating = floatval($company['total_rating']);
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $rating) {
                                            echo '<svg xmlns="http://www.w3.org/2000/svg" height="35px" viewBox="0 -960 960 960" width="35px" fill="#ff9800"><path d="M480-259.91 313.28-159.43q-12.67 7.95-26.35 6.83-13.67-1.12-23.86-9.07-10.2-7.96-15.8-20.01-5.6-12.06-2.12-26.73l44.24-189.72-147.72-127.72q-11.43-10.19-14.29-23.25-2.86-13.05 1.38-25.49 4.24-12.43 13.79-20.63 9.56-8.19 25.23-10.19l194.72-17 75.48-178.96q5.72-13.91 17.53-20.63 11.82-6.72 24.49-6.72 12.67 0 24.49 6.72 11.81 6.72 17.53 20.63l75.48 178.96 194.72 17q15.67 2 25.23 10.19 9.55 8.2 13.79 20.63 4.24 12.44 1.38 25.49-2.86 13.06-14.29 23.25L670.61-398.13l44.24 189.72q3.48 14.67-2.12 26.73-5.6 12.05-15.8 20.01-10.19 7.95-23.86 9.07-13.68 1.12-26.35-6.83L480-259.91Z"/></svg>';
                                        } else {
                                            echo '<svg xmlns="http://www.w3.org/2000/svg" height="35px" viewBox="0 -960 960 960" width="35px" fill="#ccc"><path d="M480-259.91 313.28-159.43q-12.67 7.95-26.35 6.83-13.67-1.12-23.86-9.07-10.2-7.96-15.8-20.01-5.6-12.06-2.12-26.73l44.24-189.72-147.72-127.72q-11.43-10.19-14.29-23.25-2.86-13.05 1.38-25.49 4.24-12.43 13.79-20.63 9.56-8.19 25.23-10.19l194.72-17 75.48-178.96q5.72-13.91 17.53-20.63 11.82-6.72 24.49-6.72 12.67 0 24.49 6.72 11.81 6.72 17.53 20.63l75.48 178.96 194.72 17q15.67 2 25.23 10.19 9.55 8.2 13.79 20.63 4.24 12.44 1.38 25.49-2.86 13.06-14.29 23.25L670.61-398.13l44.24 189.72q3.48 14.67-2.12 26.73-5.6 12.05-15.8 20.01-10.19 7.95-23.86 9.07-13.68 1.12-26.35-6.83L480-259.91Z"/></svg>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                            <span class="total-reviews"><?php echo $company['total_reviews']; ?> reviews</span>
                        </div>
                    </div>


                        <div class="add-review-section">
                            <div class="review-form">
                                <h3>Write Review</h3>
                                <form id="reviewForm" onsubmit="submitReview(event)">
                                    <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">
                                    
                                    <div class="rating-input">
                                        <div class="star-rating">
                                            <?php for($i = 5; $i >= 1; $i--): ?>
                                                <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                                <label for="star<?php echo $i; ?>">
                                                    <svg xmlns="http://www.w3.org/2000/svg" height="28px" viewBox="0 -960 960 960" width="28px">
                                                        <path d="M480-259.91 313.28-159.43q-12.67 7.95-26.35 6.83-13.67-1.12-23.86-9.07-10.2-7.96-15.8-20.01-5.6-12.06-2.12-26.73l44.24-189.72-147.72-127.72q-11.43-10.19-14.29-23.25-2.86-13.05 1.38-25.49 4.24-12.43 13.79-20.63 9.56-8.19 25.23-10.19l194.72-17 75.48-178.96q5.72-13.91 17.53-20.63 11.82-6.72 24.49-6.72 12.67 0 24.49 6.72 11.81 6.72 17.53 20.63l75.48 178.96 194.72 17q15.67 2 25.23 10.19 9.55 8.2 13.79 20.63 4.24 12.44 1.38 25.49-2.86 13.06-14.29 23.25L670.61-398.13l44.24 189.72q3.48 14.67-2.12 26.73-5.6 12.05-15.8 20.01-10.19 7.95-23.86 9.07-13.68 1.12-26.35-6.83L480-259.91Z"/>
                                                    </svg>
                                                </label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>

                                    <div class="review-input">
                                        <textarea id="review_text" name="review_text" rows="4" placeholder="Type here your reviews..."></textarea>
                                    </div>

                                    <button type="submit" class="submit-review">Submit Review <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M383-480 228-636q-11-11-11.5-27.5T228-692q11-11 28-11t28 11l184 184q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13L284-268q-11 11-27.5 11.5T228-268q-11-11-11-28t11-28l155-156Zm264 0L492-636q-11-11-11.5-27.5T492-692q11-11 28-11t28 11l184 184q6 6 8.5 13t2.5 15q0 8-2.5 15t-8.5 13L548-268q-11 11-27.5 11.5T492-268q-11-11-11-28t11-28l155-156Z"/></svg></button>
                                </form>
                            </div>


                    <!-- Reviews List -->
                    <div class="reviews-list">
                        <?php
                        if ($reviews_result->num_rows > 0) {
                            while($review = $reviews_result->fetch_assoc()) {
                                ?>
                                <div class="review-card">
                                    <div class="review-header">
                                        <div class="reviewer-info">
                                            <h4><?php 
                                                $maskedFirstName = maskName($review['reviewer_first_name']);
                                                $maskedLastName = maskName($review['reviewer_last_name']);
                                                echo htmlspecialchars($maskedFirstName . ' ' . $maskedLastName); 
                                            ?></h4>
                                            <span class="review-date"><?php echo date('F j, Y', strtotime($review['created_at'])); ?></span>
                                        </div>
                                        <div class="review-rating">
                                            <?php
                                            $rating = floatval($review['rating']);
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $rating) {
                                                    echo '<svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#ff9800"><path d="M480-259.91 313.28-159.43q-12.67 7.95-26.35 6.83-13.67-1.12-23.86-9.07-10.2-7.96-15.8-20.01-5.6-12.06-2.12-26.73l44.24-189.72-147.72-127.72q-11.43-10.19-14.29-23.25-2.86-13.05 1.38-25.49 4.24-12.43 13.79-20.63 9.56-8.19 25.23-10.19l194.72-17 75.48-178.96q5.72-13.91 17.53-20.63 11.82-6.72 24.49-6.72 12.67 0 24.49 6.72 11.81 6.72 17.53 20.63l75.48 178.96 194.72 17q15.67 2 25.23 10.19 9.55 8.2 13.79 20.63 4.24 12.44 1.38 25.49-2.86 13.06-14.29 23.25L670.61-398.13l44.24 189.72q3.48 14.67-2.12 26.73-5.6 12.05-15.8 20.01-10.19 7.95-23.86 9.07-13.68 1.12-26.35-6.83L480-259.91Z"/></svg>';
                                                } else {
                                                    echo '<svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#ccc"><path d="M480-259.91 313.28-159.43q-12.67 7.95-26.35 6.83-13.67-1.12-23.86-9.07-10.2-7.96-15.8-20.01-5.6-12.06-2.12-26.73l44.24-189.72-147.72-127.72q-11.43-10.19-14.29-23.25-2.86-13.05 1.38-25.49 4.24-12.43 13.79-20.63 9.56-8.19 25.23-10.19l194.72-17 75.48-178.96q5.72-13.91 17.53-20.63 11.82-6.72 24.49-6.72 12.67 0 24.49 6.72 11.81 6.72 17.53 20.63l75.48 178.96 194.72 17q15.67 2 25.23 10.19 9.55 8.2 13.79 20.63 4.24 12.44 1.38 25.49-2.86 13.06-14.29 23.25L670.61-398.13l44.24 189.72q3.48 14.67-2.12 26.73-5.6 12.05-15.8 20.01-10.19 7.95-23.86 9.07-13.68 1.12-26.35-6.83L480-259.91Z"/></svg>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <p class="review-text"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<p class="no-reviews">No reviews available yet.</p>';
                        }
                        ?>
                    </div>
                </section>

        </div>

    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const navItems = document.querySelectorAll('.nav-item');
            const infoSection = document.querySelector('.info');
            const internshipsSection = document.querySelector('.internships-section');
            const reviewsSection = document.querySelector('.reviews-section');

            function showSection(sectionName) {
                // Remove active class from all nav items
                navItems.forEach(nav => nav.classList.remove('active'));
                
                // Add active class to corresponding nav item
                navItems.forEach(item => {
                    if (item.textContent === sectionName) {
                        item.classList.add('active');
                    }
                });
                
                // Hide all sections first
                infoSection.style.display = 'none';
                internshipsSection.style.display = 'none';
                reviewsSection.style.display = 'none';
                
                // Show the appropriate section
                switch(sectionName) {
                    case 'About':
                        infoSection.style.display = 'block';
                        break;
                    case 'Internships':
                        internshipsSection.style.display = 'block';
                        break;
                    case 'Reviews':
                        reviewsSection.style.display = 'block';
                        break;
                }
            }

            // Handle URL hash on page load
            if (window.location.hash === '#reviews') {
                showSection('Reviews');
            }

            navItems.forEach(item => {
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    showSection(item.textContent);
                    
                    // Update URL hash based on section
                    if (item.textContent === 'Reviews') {
                        window.location.hash = 'reviews';
                    } else {
                        // Remove hash if not reviews
                        history.pushState("", document.title, window.location.pathname + window.location.search);
                    }
                });
            });

            function updateRatingDisplay(ratingInfo) {
                // Update overall rating
                const ratingNumber = document.querySelector('.rating-number');
                const totalReviews = document.querySelector('.total-reviews');
                const ratingText = document.querySelector('.rating-text');
                
                if (ratingNumber) ratingNumber.textContent = ratingInfo.average_rating;
                if (totalReviews) totalReviews.textContent = `${ratingInfo.total_reviews} reviews`;
                if (ratingText) ratingText.textContent = `${ratingInfo.average_rating} total rating from`;
                
                // Update all star displays
                updateStars(ratingInfo.average_rating);
            }

            function updateStars(rating) {
                const starContainers = document.querySelectorAll('.stars');
                starContainers.forEach(container => {
                    container.innerHTML = '';
                    for (let i = 1; i <= 5; i++) {
                        const star = document.createElement('svg');
                        star.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
                        star.setAttribute('height', '24px');
                        star.setAttribute('viewBox', '0 -960 960 960');
                        star.setAttribute('width', '24px');
                        star.setAttribute('fill', i <= rating ? '#ff9800' : '#ccc');
                        star.innerHTML = '<path d="M480-259.91 313.28-159.43q-12.67 7.95-26.35 6.83-13.67-1.12-23.86-9.07-10.2-7.96-15.8-20.01-5.6-12.06-2.12-26.73l44.24-189.72-147.72-127.72q-11.43-10.19-14.29-23.25-2.86-13.05 1.38-25.49 4.24-12.43 13.79-20.63 9.56-8.19 25.23-10.19l194.72-17 75.48-178.96q5.72-13.91 17.53-20.63 11.82-6.72 24.49-6.72 12.67 0 24.49 6.72 11.81 6.72 17.53 20.63l75.48 178.96 194.72 17q15.67 2 25.23 10.19 9.55 8.2 13.79 20.63 4.24 12.44 1.38 25.49-2.86 13.06-14.29 23.25L670.61-398.13l44.24 189.72q3.48 14.67-2.12 26.73-5.6 12.05-15.8 20.01-10.19 7.95-23.86 9.07-13.68 1.12-26.35-6.83L480-259.91Z"/>';
                        container.appendChild(star);
                    }
                });
            }

            function maskName(name) {
                if (!name) return '';
                return name.charAt(0) + '*'.repeat(name.length - 1);
            }

            function addNewReview(review) {
                const reviewsList = document.querySelector('.reviews-list');
                const reviewCard = document.createElement('div');
                reviewCard.className = 'review-card';
                
                const maskedFirstName = maskName(review.reviewer_first_name);
                const maskedLastName = maskName(review.reviewer_last_name);
                
                reviewCard.innerHTML = `
                    <div class="review-header">
                        <div class="reviewer-info">
                            <h4>${escapeHtml(maskedFirstName + ' ' + maskedLastName)}</h4>
                            <span class="review-date">${formatDate(review.created_at)}</span>
                        </div>
                        <div class="review-rating">
                            ${generateStarRating(review.rating)}
                        </div>
                    </div>
                    <p class="review-text">${escapeHtml(review.review_text)}</p>
                `;
                
                reviewsList.insertBefore(reviewCard, reviewsList.firstChild);
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            }

            function generateStarRating(rating) {
                let stars = '';
                for (let i = 1; i <= 5; i++) {
                    stars += `<svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="${i <= rating ? '#ff9800' : '#ccc'}">
                                <path d="M480-259.91 313.28-159.43q-12.67 7.95-26.35 6.83-13.67-1.12-23.86-9.07-10.2-7.96-15.8-20.01-5.6-12.06-2.12-26.73l44.24-189.72-147.72-127.72q-11.43-10.19-14.29-23.25-2.86-13.05 1.38-25.49 4.24-12.43 13.79-20.63 9.56-8.19 25.23-10.19l194.72-17 75.48-178.96q5.72-13.91 17.53-20.63 11.82-6.72 24.49-6.72 12.67 0 24.49 6.72 11.81 6.72 17.53 20.63l75.48 178.96 194.72 17q15.67 2 25.23 10.19 9.55 8.2 13.79 20.63 4.24 12.44 1.38 25.49-2.86 13.06-14.29 23.25L670.61-398.13l44.24 189.72q3.48 14.67-2.12 26.73-5.6 12.05-15.8 20.01-10.19 7.95-23.86 9.07-13.68 1.12-26.35-6.83L480-259.91Z"/>
                            </svg>`;
                }
                return stars;
            }

            function submitReview(event) {
                event.preventDefault();
                
                const form = event.target;
                const formData = new FormData(form);
                
                fetch('submit_review.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Add the reviews hash before reloading
                        window.location.hash = 'reviews';
                        window.location.reload();
                    } else {
                        throw new Error(data.message || 'Failed to submit review');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }

            // Attach the submit handler to the form
            const reviewForm = document.getElementById('reviewForm');
            if (reviewForm) {
                reviewForm.addEventListener('submit', submitReview);
            }
        });
    </script>

    <!-- MAIN CONTENT -->

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
</body>
</html>

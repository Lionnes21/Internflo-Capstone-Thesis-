<?php
    $conn = new mysqli("localhost", "u798912504_root", "Internfloucc2025*", "u798912504_internflo");
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
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
                                ia.Keywords
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
                $keyword_conditions[] = "(ia.Keywords LIKE ? OR ia.Keywords LIKE ? OR ia.Keywords LIKE ?)";
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
                                ia.Keywords
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
                                 ia.Keywords
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
                                 ia.Keywords
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
                                ia.Keywords
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
                $keyword_conditions[] = "(ia.Keywords LIKE ? OR ia.Keywords LIKE ? OR ia.Keywords LIKE ?)";
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
                                ia.Keywords
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
                                 ia.Keywords
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
    <link rel="stylesheet" href="../css/NAV.css">
    <link rel="stylesheet" href="../css/FOOTER.css">
    <link rel="stylesheet" href="../css/COMPANYCARDINFO.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="pics/ucc.png">
    <title>UCC - Company Profile</title>
</head>
<body>


    <!-- NAVIGATION -->
    <div class="navbar">
        <div class="logo-container">
            <button class="menu-toggle" aria-label="Toggle Navigation">☰</button>
            <img src="pics/ucc-logo.png" alt="Logo" class="logo-img">
        </div>
        <div class="nav-links">
            <a href="MAIN.php">HOME</a>
            <a href="#about">ABOUT US</a>
            <a href="#contact">CONTACT US</a>
            <a href="../STUDENTCOORLOG/login.php" class="login-btn">LOGIN</a>
            <a href="../RECRUITER/companysignin.php" class="employer-btn">EMPLOYER SITE</a>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Existing scroll behavior code
            const navbar = document.querySelector('.navbar');
            let timeout;

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

            window.addEventListener('scroll', () => {
                if (window.scrollY === 0) {
                    showNavbar();
                    clearTimeout(timeout);
                } else {
                    resetNavbarTimeout();
                }
            });

            window.addEventListener('mousemove', resetNavbarTimeout);
            window.addEventListener('click', resetNavbarTimeout);
            window.addEventListener('keydown', resetNavbarTimeout);

            if (window.scrollY > 0) {
                timeout = setTimeout(hideNavbar, 1000);
            }

            // New mobile menu toggle functionality
            const menuToggle = document.querySelector('.menu-toggle');
            
            menuToggle.addEventListener('click', function() {
                // Toggle the 'active' class on the navbar
                navbar.classList.toggle('active');
                
                // Change the burger menu icon to 'X' when menu is open
                if (navbar.classList.contains('active')) {
                    menuToggle.innerHTML = '✕';
                    menuToggle.style.color = '#e77d33'; // Match the hover color of nav links
                } else {
                    menuToggle.innerHTML = '☰';
                    menuToggle.style.color = '#fd6f41'; // Reset to original color
                }
            });

            // Close menu when clicking outside
            document.addEventListener('click', function(event) {
                const isClickInside = navbar.contains(event.target);
                
                if (!isClickInside && navbar.classList.contains('active')) {
                    navbar.classList.remove('active');
                    menuToggle.innerHTML = '☰';
                    menuToggle.style.color = '#fd6f41';
                }
            });

            // Close menu when window is resized above mobile breakpoint
            window.addEventListener('resize', function() {
                if (window.innerWidth > 1300) { // Match your media query breakpoint
                    navbar.classList.remove('active');
                    menuToggle.innerHTML = '☰';
                    menuToggle.style.color = '#fd6f41';
                }
            });
        });
    </script>
    <!-- NAVIGATION -->


    <!-- ALERT -->
    <div class="alert" >
                <div class="alert__wrapper">
                        <span class="alert__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#856404"><path d="M800-514.5q-19.15 0-32.33-13.17Q754.5-540.85 754.5-560t13.17-32.33Q780.85-605.5 800-605.5t32.33 13.17Q845.5-579.15 845.5-560t-13.17 32.33Q819.15-514.5 800-514.5Zm0-131q-19.15 0-32.33-13.17Q754.5-671.85 754.5-691v-120q0-19.15 13.17-32.33Q780.85-856.5 800-856.5t32.33 13.17Q845.5-830.15 845.5-811v120q0 19.15-13.17 32.33Q819.15-645.5 800-645.5ZM360.72-484.07q-69.59 0-118.86-49.27-49.27-49.27-49.27-118.86 0-69.58 49.27-118.74 49.27-49.15 118.86-49.15 69.58 0 118.86 49.15 49.27 49.16 49.27 118.74 0 69.59-49.27 118.86-49.28 49.27-118.86 49.27ZM32.59-238.8v-29.61q0-36.16 18.69-66.57 18.7-30.41 49.85-46.37 62.72-31.24 127.67-46.98 64.96-15.74 131.92-15.74 67.43 0 132.39 15.62 64.96 15.62 127.19 46.86 31.16 15.96 49.85 46.25 18.7 30.3 18.7 66.93v29.61q0 37.78-26.61 64.39t-64.39 26.61H123.59q-37.79 0-64.39-26.61-26.61-26.61-26.61-64.39Z"/></svg>
                        </span>
                                    
                        <p class="alert__message">
                            Writing Reviews and Internship application requires logging in. Please 
                            <a href="../STUDENTCOORLOG/login.php" class="alert-sign-in">sign in</a> to continue.
                        </p>
                </div>
    </div>
    <!-- ALERT -->


    <!-- MAIN -->
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
                        <a href="#" class="write-review-disabled">
                            Write Review
                            <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#808080"><path d="M168-121q-21 5-36.5-10.5T121-168l35-170 182 182-170 35Zm235-84L205-403l413-413q23-23 57-23t57 23l84 84q23 23 23 57t-23 57L403-205Z"/></svg>
                        </a>
                    </div>
                </div>
            
                <!-- Navigation -->
                <div class="nav-wrapper">
                    <nav class="navigation">
                        <a href="#" class="nav-item active">About</a>
                        <a href="#" class="nav-item">Internships</a>
                        <a href="#" class="nav-item">Reviews</a>
                    </nav>
                </div>

                <!-- Company Information -->
                <section class="info">
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
                <section class="internships-section" style="display: none;">
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
                                
                                // Find exact matches
                                $exact_matches = findExactMatches($job['Keywords'], $original_keywords);
                                
                                echo '<div class="job-card" onclick="window.location.href=\'studentfrontpageview.php?id=' . $job['internship_id'] . '\'" style="cursor: pointer;">';
                                echo '<h3>' . htmlspecialchars($job['internship_title']) . '</h3>';
                                echo '<p class="location">' . htmlspecialchars($job['company_address']) . '</p>';
                                echo '<p class="internship-description">' . htmlspecialchars($truncated_description) . '</p>';
                                

                                echo '<p class="posted-time">' . $posted_time . '</p>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p class="no-jobs">No internships available at this time.</p>';
                            
                            // Debug information for troubleshooting
                            if (!empty($original_keywords)) {
                                echo '<div class="debug-info" style="margin-top: 20px; padding: 10px; background: #f8f9fa; border: 1px solid #ddd;">';
                                echo '<p><strong>Debug Info:</strong></p>';
                                echo '<p>Searched for keyword: ' . htmlspecialchars($original_keywords) . '</p>';
                                echo '</div>';
                            }
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
        });
    </script>
    <!-- MAIN -->
    
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
                            <li><a href="MAIN.php#searchinternship">Internship by Company</a></li>
                            <li><a href="MAIN.php#searchinternship">Internship by City</a></li>
                            <li><a href="MAIN.php#searchinternship">Search Nearby Internship</a></li>
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
                            <li><a href="MAIN.php#about">About Us</a></li>
                            <li><a href="MAIN.php#aichat">How It Works</a></li>
                            <li><a href="MAIN.php#contact">Contact Us</a></li>
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

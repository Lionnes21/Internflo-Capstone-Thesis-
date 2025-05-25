<?php
    session_start();
    include 'config.php';  // Database connection config


    // Function to get full name
    function getFullName() {
        if (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
            return htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']);
        }
        return 'Your Name'; // Fallback if names are not set
    }

    // Get the table and user_id from session
    $user_id = $_SESSION['user_id'];
    $source_table = $_SESSION['source_table'] ?? '';

    // Initialize variables for company data and internship info array
    $company_logo = '';
    $company_name = '';
    $internships = [];
    $show_internship_ads = ($source_table !== 'recruiters');

    // Check which table the user is from and fetch company data
    if ($source_table === 'approvedrecruiters') {
        // Fetch from approvedrecruiters table
        $stmt = $conn->prepare("SELECT company_logo, company_name FROM approvedrecruiters WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $company_logo = $row['company_logo'];
            $company_name = $row['company_name'];
        }
    } elseif ($source_table === 'recruiters') {
        // Fetch from recruiters table
        $stmt = $conn->prepare("SELECT company_logo, company_name FROM recruiters WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $company_logo = $row['company_logo'];
            $company_name = $row['company_name'];
        }
    }

    // Fetch all internship titles and their IDs from internshipad table based on user_id
    $stmt = $conn->prepare("SELECT internship_id, internship_title FROM internshipad WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $internship_id = $row['internship_id'];
        $internship_title = $row['internship_title'];

        // Count number of applications for this internship
        $applicant_stmt = $conn->prepare("SELECT COUNT(*) as applicant_count FROM studentapplication WHERE internshipad_id = ?");
        $applicant_stmt->bind_param("i", $internship_id);
        $applicant_stmt->execute();
        $applicant_result = $applicant_stmt->get_result();
        $applicant_count = 0;

        if ($applicant_row = $applicant_result->fetch_assoc()) {
            $applicant_count = $applicant_row['applicant_count'];
        }

        // Add internship data including applicant count to internships array
        // Add internship data including applicant count and internship_id to internships array
        $internships[] = [
            'internship_id' => $internship_id,  // Add this line
            'internship_title' => $internship_title,
            'applicant_count' => $applicant_count
        ];

    }

    // Store company data in session if needed elsewhere
    $_SESSION['company_logo'] = $company_logo;
    $_SESSION['company_name'] = $company_name;
    $_SESSION['internships'] = $internships;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="pics/ucc.png">
    <title>UCC - Company</title>
    <link rel="stylesheet" href="NAV-login.css">
    <link rel="stylesheet" href="companyloginpage.css">
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

    <!-- BANNER HEADER -->
    <div class="banner">
        <p class="banner-text">
            Begin by assessing your candidates. <span class="highlight">Post</span> your Internship with us now!
        </p>
        <button 
            class="create-button <?= ($source_table == 'recruiters' || empty($internships)) ? 'disabled' : '' ?>"
            <?= ($source_table == 'recruiters' || empty($internships)) ? 'disabled' : '' ?>>
            VIEW CANDIDATES
        </button>
        <script>
            document.querySelector('.create-button').addEventListener('click', function() {
                // Only execute if button is not disabled
                if (!this.classList.contains('disabled')) {
                    const targetElement = document.querySelector('.job-width');
                    if (targetElement) {
                        targetElement.scrollIntoView({ behavior: 'smooth' });
                    }
                }
            });
        </script>
    </div>

    <script>
        function updateText() {
            const bannerText = document.querySelector('.banner-text');
            if (window.innerWidth <= 870) {
                // Mobile version - only show "Create your Internship Ad with us today!"
                bannerText.innerHTML = `<span class="highlight">Post</span> your Internship with us now!`;
            } else {
                // Desktop version - show full text
                bannerText.innerHTML = `Begin by assessing your candidates. <span class="highlight">Post</span> your Internship with us now!`;
            }
        }

        // Run on page load
        updateText();
        // Run whenever the window is resized
        window.addEventListener('resize', updateText);
    </script>
    <div class="title-container">
            <h1>Helping you find the best<span class="highlight"> interns</span> for <br> your company with Internflo!</h1>
            <p class="title-p">
                <span style="color: #ff8c00; font-weight: 700">Bridging</span> the gap between potential and success
            </p>
            <!-- Success Alert -->
            <div class="alert-container success-alert <?= ($source_table == 'approvedrecruiters') ? '' : 'hidden' ?>">
                <div class="alert-wrapper">
                    <span class="alert-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M242.87-71.87q-37.78 0-64.39-26.61t-26.61-64.39v-149q0-37.78 26.61-64.39t64.39-26.61h474.26q37.78 0 64.39 26.61t26.61 64.39v149q0 37.78-26.61 64.39t-64.39 26.61H242.87Zm37.61-164.78h399.04q16.05 0 26.83-10.78 10.78-10.79 10.78-26.83t-10.78-26.83q-10.78-10.78-26.83-10.78H280.48q-16.05 0-26.83 10.78-10.78 10.79-10.78 26.83t10.78 26.83q10.78 10.78 26.83 10.78Zm163.17-213.89L296.13-647.5q-11.39-14.91-15.97-33.04-4.57-18.13-1.86-37 12-74.2 68.99-122.39 56.99-48.2 132.71-48.2t132.71 48.2q56.99 48.19 68.99 122.39 2.71 18.87-1.86 37-4.58 18.13-15.97 33.04L516.35-450.54q-13.68 17.95-36.35 17.95t-36.35-17.95Z"/></svg>
                    </span>
                    <span class="alert-message">
                    Verified account, start creating your ads NOW!
                    </span>
                </div>
            </div>

            <!-- Warning Alert -->
            <div class="alert-container warning-alert <?= ($source_table == 'recruiters') ? '' : 'hidden' ?>">
                <div class="alert-wrapper">
                    <span class="alert-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M607.13-204.74q12 0 21-9t9-21q0-12-9-21t-21-9q-12 0-21 9t-9 21q0 12 9 21t21 9Zm110 0q12 0 21-9t9-21q0-12-9-21t-21-9q-12 0-21 9t-9 21q0 12 9 21t21 9Zm110 0q12 0 21-9t9-21q0-12-9-21t-21-9q-12 0-21 9t-9 21q0 12 9 21t21 9Zm-110 170q-83 0-141.5-58.5t-58.5-141.5q0-83 58.5-141.5t141.5-58.5q83 0 141.5 58.5t58.5 141.5q0 83-58.5 141.5t-141.5 58.5ZM322.87-596.41h314.26q17 0 28.5-11.5t11.5-28.5q0-17-11.5-28.5t-28.5-11.5H322.87q-17 0-28.5 11.5t-11.5 28.5q0 17 11.5 28.5t28.5 11.5Zm-120 485.26q-37.54 0-64.27-26.73-26.73-26.73-26.73-64.27v-554.26q0-37.54 26.73-64.27 26.73-26.73 64.27-26.73h554.26q37.54 0 64.27 26.73 26.73 26.73 26.73 64.27v217.28q0 20.63-17.27 31.71-17.27 11.07-36.9 3.88-17.89-5.48-37.36-8.22t-39.47-2.74q-11 0-20.5.46t-19.5 2.3q-8.28-3.91-19.28-5.73-11-1.81-20.72-1.81H322.87q-17 0-28.5 11.5t-11.5 28.5q0 17 11.5 28.5t28.5 11.5h204.52q-18 16.28-32.74 35.56-14.74 19.29-26.22 41.57H322.87q-17 0-28.5 11.5t-11.5 28.5q0 17 11.5 28.5t28.5 11.5h118.7q-2.72 11.67-3.58 23.2-.86 11.54-.86 24.45 0 18.8 1.84 35.99t6.44 33.42q5.24 19.87-6.07 36.91-11.32 17.03-30.95 17.03H202.87Z"/></svg>
                    </span>
                    <span class="alert-message">
                        Unverified account, unable to create ads at this time.
                    </span>
                </div>
            </div>

            <!-- Default Promo Alert (shown when no condition matches) -->
            <div class="alert-container promo-alert <?= ($source_table != 'approvedrecruiters' && $source_table != 'recruiters') ? '' : 'hidden' ?>">
                <div class="alert-wrapper">
                    <span class="alert-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" height="28px" viewBox="0 -960 960 960" width="28px">
                            <path d="M769.33-436.41H685.5q-18.43 0-31.01-12.58-12.58-12.58-12.58-31.01t12.58-31.01q12.58-12.58 31.01-12.58h83.83q18.43 0 31.01 12.58 12.57 12.58 12.57 31.01t-12.57 31.01q-12.58 12.58-31.01 12.58Z"/>
                        </svg>
                    </span>
                    <span class="alert-message">
                        Post any internship ad, anytime for FREE!
                    </span>
                </div>
            </div>
            
            <div class="form-group-email">
                
                <div class="input-container">
                    <input type="text" id="interns-ad" placeholder="Enter Internship Title">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#4a5a73"><path d="M418.63-421.04h122.74l21.85 60.45q3.28 8.79 10.39 13.73 7.12 4.95 15.87 4.95 15.32 0 23.66-12.2 8.34-12.19 2.86-26.39l-98.65-259.15q-3.3-8.68-10.43-13.56-7.14-4.88-15.92-4.88h-21.96q-8.79 0-15.94 4.95-7.15 4.94-10.45 13.73L345-379.02q-5.48 13.19 2.48 25.15 7.95 11.96 22.91 11.96 8.96 0 16.06-5.1 7.09-5.1 10.33-13.82l21.85-60.21Zm16-48.16 43.58-127.21H482l43.37 127.21h-90.74Zm45.35 397.33q-84.65 0-159.09-31.98-74.43-31.98-129.63-87.05-55.19-55.08-87.29-129.45-32.1-74.37-32.1-158.93 0-30.74 4.52-61.47 4.52-30.74 13.57-60.51 5.24-18.15 22.77-24.73 17.53-6.58 33.68 2.38 17.39 9.2 24.73 27.01 7.34 17.82 2.34 37.36-5.05 19.61-7.83 39.58-2.78 19.97-2.78 40.38 0 132.5 92.28 224.45 92.28 91.96 224.85 91.96t224.85-92.28q92.28-92.28 92.28-224.85t-91.82-224.85q-91.82-92.28-224.11-92.28-21.58 0-42.7 2.9-21.13 2.9-41.98 8.71-19.39 5-36.9-1.84-17.51-6.84-25.47-24.11-7.95-17.27-.5-34.92 7.46-17.65 25.85-22.89 29.04-9.81 59.53-14.33 30.48-4.52 60.97-4.52 84.65 0 159.1 32.1 74.44 32.1 129.64 87.29 55.19 55.2 87.29 129.65 32.1 74.46 32.1 159.11 0 84.65-32.1 159.09-32.1 74.43-87.29 129.63-55.2 55.19-129.65 87.29-74.46 32.1-159.11 32.1Zm-260.5-601.91q-27.67 0-47.04-19.54-19.37-19.53-19.37-47.2t19.37-47.04q19.37-19.37 47.04-19.37 27.67 0 47.2 19.37 19.54 19.37 19.54 47.04 0 27.67-19.54 47.2-19.53 19.54-47.2 19.54ZM242.87-480q0-98.8 69.16-167.97Q381.2-717.13 480-717.13t167.97 69.16Q717.13-578.8 717.13-480t-69.16 167.97Q578.8-242.87 480-242.87t-167.97-69.16Q242.87-381.2 242.87-480Z"/></svg>
                </div>
                <button 
                    class="register-btn <?= ($source_table == 'recruiters') ? 'disabled' : '' ?>" 
                    onclick="window.location.href='createinternship.php'" 
                    <?= ($source_table == 'recruiters') ? 'disabled' : '' ?>>
                    Create
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor" class="button-svg">
                        <path d="m783.67-94.5-131.76-131-27.37 83.11q-5.71 15.91-21.87 15.41-16.15-.5-21.63-16.41l-88.61-295.83q-4.23-12.67 5.72-22.63 9.96-9.95 22.63-5.72l295.83 88.61q15.91 5.48 16.41 21.63.5 16.16-15.41 21.87l-83.11 27.37 132 131.76q17.72 17.72 17.72 40.92 0 23.19-17.72 40.91-17.72 17.72-41.41 17.72-23.7 0-41.42-17.72ZM229-480q0-104.54 73.23-177.77T480-731q81.07 0 144.43 44.89 63.37 44.89 90.33 116.48 6.48 17.43-.74 33.75-7.22 16.31-24.65 22.79-17.44 6.48-33.49-.74-16.05-7.21-22.53-24.65-17.24-46.43-59.08-75.77-41.84-29.34-94.27-29.34-67.43 0-115.51 48.08T316.41-480q0 52.43 29.22 94.15 29.22 41.72 75.65 59.2 17.44 6.48 24.54 22.91 7.09 16.44.61 33.87-6.47 17.44-22.91 24.15-16.43 6.72-33.87.24-72.35-27.19-116.5-91.44Q229-401.17 229-480Z"/>
                    </svg>
                </button>
            </div> 
            <p class="help-text">
                Get started by creating your first <span style="color: #ff8c00; font-weight: 600">internship</span> ad.
            </p>         
    </div>
    <!-- BANNER HEADER -->

    <!-- Success -->
    <!-- <div class="success-alert <?= ($source_table == 'approvedrecruiters') ? '' : 'hidden' ?>">
            <div class="success-alert__wrapper">
                <span class="success-alert__icon">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2d6a2d"><path d="m708.93-600.89 130-130.76q13.68-13.68 32.33-13.56 18.65.12 32.33 13.56 13.67 13.67 13.67 32.32 0 18.66-13.67 32.33L741-504.17q-13.67 13.67-32.07 13.67-18.39 0-32.06-13.67l-80.22-80.22q-13.67-13.68-13.55-32.33.12-18.65 13.79-32.32 13.68-13.44 31.95-13.56 18.27-.12 31.94 13.56l48.15 48.15ZM358.57-484.07q-69.59 0-118.86-49.27-49.28-49.27-49.28-118.86 0-69.58 49.28-118.74 49.27-49.15 118.86-49.15 69.58 0 118.85 49.15 49.28 49.16 49.28 118.74 0 69.59-49.28 118.86-49.27 49.27-118.85 49.27ZM30.43-238.8v-29.61q0-36.16 18.7-66.57 18.7-30.41 49.85-46.37 62.72-31.24 127.67-46.98 64.96-15.74 131.92-15.74 67.43 0 132.39 15.62 64.95 15.62 127.19 46.86 31.15 15.96 49.85 46.25 18.7 30.3 18.7 66.93v29.61q0 37.78-26.61 64.39T595.7-147.8H121.43q-37.78 0-64.39-26.61T30.43-238.8Z"/></svg>
                </span>
                <p class="success-alert__message">
                    Your account has been successfully verified, and you can now create an internship advertisement.
                </p>
            </div>
    </div> -->

    <!-- Alert -->
    <!-- <div class="alert <?= ($source_table == 'recruiters') ? '' : 'hidden' ?>">
            <div class="alert__wrapper">
                <span class="alert__icon">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#856404"><path d="M800-514.5q-19.15 0-32.33-13.17Q754.5-540.85 754.5-560t13.17-32.33Q780.85-605.5 800-605.5t32.33 13.17Q845.5-579.15 845.5-560t-13.17 32.33Q819.15-514.5 800-514.5Zm0-131q-19.15 0-32.33-13.17Q754.5-671.85 754.5-691v-120q0-19.15 13.17-32.33Q780.85-856.5 800-856.5t32.33 13.17Q845.5-830.15 845.5-811v120q0 19.15-13.17 32.33Q819.15-645.5 800-645.5ZM360.72-484.07q-69.59 0-118.86-49.27-49.27-49.27-49.27-118.86 0-69.58 49.27-118.74 49.27-49.15 118.86-49.15 69.58 0 118.86 49.15 49.27 49.16 49.27 118.74 0 69.59-49.27 118.86-49.28 49.27-118.86 49.27ZM32.59-238.8v-29.61q0-36.16 18.69-66.57 18.7-30.41 49.85-46.37 62.72-31.24 127.67-46.98 64.96-15.74 131.92-15.74 67.43 0 132.39 15.62 64.96 15.62 127.19 46.86 31.16 15.96 49.85 46.25 18.7 30.3 18.7 66.93v29.61q0 37.78-26.61 64.39t-64.39 26.61H123.59q-37.79 0-64.39-26.61-26.61-26.61-26.61-64.39Z"/></svg>
                </span>                  
                <p class="alert__message">
                    Your account will undergo verification before you can create an internship ad.
                </p>
            </div>
    </div> -->

    <!-- HEADER -->
    <!-- <div class="header-container">
        <div class="header-left">
            <h1>Create Internship</h1>
            <p>You're in the right place to find your next interns. Get started by creating your first internship ad.</p>
        </div>
        <button 
            class="create-button <?= ($source_table == 'recruiters') ? 'disabled' : '' ?>" 
            onclick="window.location.href='createinternship.php'" 
            <?= ($source_table == 'recruiters') ? 'disabled' : '' ?>>
            Create Internship Ad
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor" class="button-svg">
                <path d="M202.87-111.87q-37.78 0-64.39-26.61t-26.61-64.39v-554.26q0-37.78 26.61-64.39t64.39-26.61h247.37q22.75 0 34.12 14.18 11.38 14.19 11.38 31.21 0 17.02-11.7 31.32-11.69 14.29-34.56 14.29H202.87v554.26h554.26V-450q0-22.75 14.24-34.12 14.24-11.38 31.33-11.38 17.08 0 31.26 11.38 14.17 11.37 14.17 34.12v247.13q0 37.78-26.61 64.39t-64.39 26.61H202.87ZM480-480Zm-123.83 78.33v-92.46q0-18.21 6.84-34.71 6.84-16.51 19.51-29.18l337.07-336.83q13.67-13.67 30.58-20.39 16.92-6.72 34.07-6.72 17.91 0 34.44 6.72 16.54 6.72 30.21 20.39l45.96 46.72q12.91 13.67 19.63 30.23 6.72 16.56 6.72 33.69 0 17.12-6.3 33.74-6.31 16.61-20.05 30.36L558.02-383.28q-12.67 12.67-29.18 19.89-16.5 7.22-34.71 7.22h-92.46q-19.15 0-32.32-13.18-13.18-13.17-13.18-32.32Zm481.72-382.57L785-836.89l52.89 52.65Zm-395.5 341.85h52.65l229.85-229.85-26.24-26.33-27.17-26.32-229.09 228.85v53.65Zm256.26-256.18-27.17-26.32 27.17 26.32 26.24 26.33-26.24-26.33Z"/>
            </svg>
        </button>
    </div> -->
    <!-- HEADER -->

    <!-- INTERNSHIP AD -->
    <br>
    <br>
    <?php if ($show_internship_ads): ?>
        <div class="job-width">
            <?php if (!empty($internships)): ?>
                <div class="header-job">
                    <h1>Internship <span style="color: #ff8c00;">Advertisements</span></h1>
                    <p>Explore <span style="color: #ff8c00; font-weight: 600">Applicants</span> for your Posted Internship Ad</p>
                </div>
            <?php endif; ?>
            <div class="job-align">
                <?php foreach ($internships as $internship): ?>
                    <a href="applicants.php?internship_id=<?php echo htmlspecialchars($internship['internship_id']); ?>" class="job-card-link">
                        <div class="job-card">
                            <div class="job-card__logo-wrapper">
                                <?php if ($company_logo): ?>
                                    <img class="job-card__logo-image" src="../RECRUITER/<?php echo htmlspecialchars($company_logo); ?>" alt="<?php echo htmlspecialchars($company_name); ?> Logo">
                                <?php else: ?>
                                    <img class="job-card__logo-image" src="default-logo.png" alt="Default Logo">
                                <?php endif; ?>
                            </div>
                            <h1 class="job-card__company-name"><?php echo htmlspecialchars($company_name); ?></h1>
                            <div class="job-card__role-title"><?php echo htmlspecialchars($internship['internship_title']); ?></div>
                            <div class="job-card__stats-badge"><p><?php echo htmlspecialchars($internship['applicant_count']); ?> Applicants</p></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>


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
    <div class="about-heading" id="chatbot">
            <h1>Talk to<span> Roger</span></h1>
            <p><span style="color: #ff8c00; font-weight: 600">Roger</span> is an AI assistant providing quick, accurate answers and support for <br> your queries, ensuring a seamless user experience.</p>
    </div>
    <div class="aidiv">
            <iframe
                src="https://www.chatbase.co/chatbot-iframe/INTGzufIgCnpSAWtWNJB5"
                width="100%"
                style="height: 100%; min-height: 500px; border: 3px solid #2e3849; border-radius: 8px; margin: 0 0 50px 0;"
                frameborder="0"
            ></iframe>
    </div>
    <!-- AI CHATBOT -->

    <!-- CONTACT SECTION -->
    <div class="scrolling" id="contact">
        <div class="about-hero">
                        <div class="about-heading">
                            <h1>Send us your <span>feedback</span></h1>
                            <p>We value your <span style="color: #ff8c00; font-weight: 600">thoughts</span> and <span style="color: #ff8c00; font-weight: 600">opinions</span>  your feedback helps us grow, improve, and better serve your needs.</p>
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
    <!-- CONTACT SECTION -->

    <!-- CHAT WIDGET -->
    <script>
                (function(){if(!window.chatbase||window.chatbase("getState")!=="initialized"){window.chatbase=(...arguments)=>{if(!window.chatbase.q){window.chatbase.q=[]}window.chatbase.q.push(arguments)};window.chatbase=new Proxy(window.chatbase,{get(target,prop){if(prop==="q"){return target.q}return(...args)=>target(prop,...args)}})}const onLoad=function(){const script=document.createElement("script");script.src="https://www.chatbase.co/embed.min.js";script.id="sqZ5VD70WA_0wO97JZLEZ";script.domain="www.chatbase.co";document.body.appendChild(script)};if(document.readyState==="complete"){onLoad()}else{window.addEventListener("load",onLoad)}})();
    </script>
    <!-- CHAT WIDGET -->

    
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
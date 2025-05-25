<?php
    session_start();
    include 'config.php';

    // Check login status and initialize variables
    $isLoggedIn = isset($_SESSION['user_id']);
    $initials = '';
    $fullName = '';
    $email = '';
    $fullName2 = '';
    $profile_pic = '';
    $showVerifiedAlert = false;
    $loginMethod = '';

    // If logged in, fetch user details
    if ($isLoggedIn) {
        $userId = $_SESSION['user_id'];
        
        // Fetch user details with login method
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
                creation_method
            FROM students 
            WHERE id = ?
        ';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            $fullName2 = trim($user['first_name'] . 
                         
            ' ' . $user['last_name']);
        
            $email = $user['email'];
            $profile_pic = !empty($user['profile_pic']) ? $user['profile_pic'] : 'path/to/default/profile.jpg';
            
            $loginMethod = $user['login_method'];


        }
    }

    // Get internship ID from URL
    $internshipId = isset($_GET['id']) ? $_GET['id'] : null;

    if (!$internshipId) {
        header('Location: index.php');
        exit;
    }

    // Fetch internship details
    // Fetch internship details with company rating information
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
    ) THEN 1 ELSE 0 END as has_applied
    FROM internshipad i
    JOIN approvedrecruiters r ON i.user_id = r.id
    WHERE i.internship_id = ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $_SESSION['user_id'], $internshipId);
    $stmt->execute();
    $result = $stmt->get_result();
    $job = $result->fetch_assoc();

    if (!$job) {
        header('Location: index.php');
        exit;
    }

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

    $hiredCheckQuery = "SELECT 1 FROM hired_applicants WHERE student_id = ? AND Status = 'Hired' LIMIT 1";
    $hiredStmt = $conn->prepare($hiredCheckQuery);
    $hiredStmt->bind_param('i', $_SESSION['user_id']);
    $hiredStmt->execute();
    $isHired = $hiredStmt->get_result()->num_rows > 0;

    // Also check if student is hired for this specific internship
    $hiredForThisAdQuery = "SELECT 1 FROM hired_applicants WHERE student_id = ? AND internshipad_id = ? AND Status = 'Hired' LIMIT 1";
    $hiredForThisStmt = $conn->prepare($hiredForThisAdQuery);
    $hiredForThisStmt->bind_param('ii', $_SESSION['user_id'], $internshipId);
    $hiredForThisStmt->execute();
    $isHiredForThisAd = $hiredForThisStmt->get_result()->num_rows > 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="studentfrontpageview.css">
    <link rel="stylesheet" href="NAVX.css">
    <link rel="stylesheet" href="FOOTER.css">
    <link rel="icon" href="pics/ucc.png">
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


    <!-- Alert -->
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
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#856404"><path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm0-160q17 0 28.5-11.5T520-480v-160q0-17-11.5-28.5T480-680q-17 0-28.5 11.5T440-640v160q0 17 11.5 28.5T480-440Zm0 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/></svg>
                <p class="alert__message">                 
                    Only verified accounts are permitted to apply for internship advertisements.
                </p>
            </div>
        </div>
    <?php endif; ?>

   

    <div class="bgwidth">
        <div class="bgcolor">
            <div class="logo">
                <img src="../RECRUITER/<?php echo htmlspecialchars($job['company_logo']); ?>" alt="Company Logo">
            </div>

            <h1 class="tit"><?php echo htmlspecialchars($job['internship_title']); ?></h1>

            <div class="company">
                <span><?php echo htmlspecialchars($job['company_name']); ?></span>
                <div class="rating">
                    <div class="stars">
                        <?php
                        $rating = floatval($job['total_rating']);
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
                    <a href="COMPANYCARDINFO-APPLY.php?id=<?php echo htmlspecialchars($job['company_id']); ?>#reviews" class="review-link">
                        <?php echo htmlspecialchars($job['total_reviews']); ?> reviews
                    </a>
                    <span class="dot">•</span>
                    <a href="company.php?id=<?php echo htmlspecialchars($job['company_id']); ?>" class="review-link">
                        View all internships
                    </a>
                </div>
            </div>

            <div class="details">
                <div class="detail-item">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2E3849">
                        <path d="M480-186q122-112 181-203.5T720-552q0-109-69.5-178.5T480-800q-101 0-170.5 69.5T240-552q0 71 59 162.5T480-186Zm0 79q-14 0-28-5t-25-15q-65-60-115-117t-83.5-110.5q-33.5-53.5-51-103T160-552q0-150 96.5-239T480-880q127 0 223.5 89T800-552q0 45-17.5 94.5t-51 103Q698-301 648-244T533-127q-11 10-25 15t-28 5Zm0-453Zm0 80q33 0 56.5-23.5T560-560q0-33-23.5-56.5T480-640q-33 0-56.5 23.5T400-560q0 33 23.5 56.5T480-480Z"/>
                    </svg>
                    <span><?php echo htmlspecialchars($job['company_address']); ?></span>
                </div>

                <div class="detail-item">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2E3849"><path d="M80-200v-560q0-33 23.5-56.5T160-840h240q33 0 56.5 23.5T480-760v80h320q33 0 56.5 23.5T880-600v400q0 33-23.5 56.5T800-120H160q-33 0-56.5-23.5T80-200Zm80 0h80v-80h-80v80Zm0-160h80v-80h-80v80Zm0-160h80v-80h-80v80Zm0-160h80v-80h-80v80Zm160 480h80v-80h-80v80Zm0-160h80v-80h-80v80Zm0-160h80v-80h-80v80Zm0-160h80v-80h-80v80Zm160 480h320v-400H480v80h80v80h-80v80h80v80h-80v80Zm160-240v-80h80v80h-80Zm0 160v-80h80v80h-80Z"/></svg>
                    <span><?php echo htmlspecialchars($job['internship_title']); ?> (<?php echo htmlspecialchars($job['department']); ?>)</span>
                </div>

                <div class="detail-item">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2E3849">
                        <path d="M520-496v-144q0-17-11.5-28.5T480-680q-17 0-28.5 11.5T440-640v159q0 8 3 15.5t9 13.5l132 132q11 11 28 11t28-11q11-11 11-28t-11-28L520-496ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-400Zm0 320q133 0 226.5-93.5T800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 133 93.5 226.5T480-160Z"/>
                    </svg>
                    <span><?php echo htmlspecialchars($job['internship_type']); ?></span>
                    <span class="dot">•</span>
                    <span><?php echo htmlspecialchars($job['duration']); ?> hours</span>
                </div>

                <div class="detail-item">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2E3849"><path d="M500-482q29-32 44.5-73t15.5-85q0-44-15.5-85T500-798q60 8 100 53t40 105q0 60-40 105t-100 53Zm198 322q11-18 16.5-38.5T720-240v-40q0-36-16-68.5T662-406q51 18 94.5 46.5T800-280v40q0 33-23.5 56.5T720-160h-22Zm102-360h-40q-17 0-28.5-11.5T720-560q0-17 11.5-28.5T760-600h40v-40q0-17 11.5-28.5T840-680q17 0 28.5 11.5T880-640v40h40q17 0 28.5 11.5T960-560q0 17-11.5 28.5T920-520h-40v40q0 17-11.5 28.5T840-440q-17 0-28.5-11.5T800-480v-40Zm-480 40q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM0-240v-32q0-34 17.5-62.5T64-378q62-31 126-46.5T320-440q66 0 130 15.5T576-378q29 15 46.5 43.5T640-272v32q0 33-23.5 56.5T560-160H80q-33 0-56.5-23.5T0-240Zm320-320q33 0 56.5-23.5T400-640q0-33-23.5-56.5T320-720q-33 0-56.5 23.5T240-640q0 33 23.5 56.5T320-560ZM80-240h480v-32q0-11-5.5-20T540-306q-54-27-109-40.5T320-360q-56 0-111 13.5T100-306q-9 5-14.5 14T80-272v32Zm240-400Zm0 400Z"/></svg>
                    <span><?php echo htmlspecialchars($job['number_of_openings']); ?> available internship spots</span>
                </div>
                
                <div class="detail-item">
                    <span style="color: #666666;">Posted <?php echo timeAgo($job['created_at']); ?></span>
                </div>
            </div>

            <div class="buttons">
            <button 
                class="quick-apply <?php echo ($loginMethod === 'google' || $job['has_applied'] || $isHired) ? 'disabled' : ''; ?>"
                <?php if ($loginMethod !== 'google' && !$job['has_applied'] && !$isHired): ?>
                    onclick="window.location.href='applytocompany.php?id=<?php echo $internshipId; ?>'"
                <?php endif; ?>
                title="<?php 
                    if ($loginMethod === 'google') {
                        echo 'Google login users cannot apply';
                    } elseif ($isHiredForThisAd) {
                        echo 'You have been hired for this internship';
                    } elseif ($isHired) {
                        echo 'You are already hired for another internship';
                    } elseif ($job['has_applied']) {
                        echo 'You have already applied';
                    }
                ?>"
            >
                <?php 
                    if ($isHiredForThisAd) {
                        echo 'Hired for this Internship';
                    } elseif ($isHired) {
                        echo 'Already Hired Elsewhere';
                    } elseif ($job['has_applied']) {
                        echo 'Already Applied';
                    } else {
                        echo 'Apply Now';
                    }
                ?>
                <svg xmlns="http://www.w3.org/2000/svg" height="22px" viewBox="0 -960 960 960" width="22px">
                    <path d="M211-480q0 100.5 64.93 176.03 64.94 75.53 164.2 90.25 17.67 2.72 28.77 16.51 11.1 13.8 11.1 31.71 0 19.63-16.29 31.95-16.3 12.31-36.69 9.59-133.09-19.15-220.05-120.05Q120-344.91 120-480q0-134.33 86.59-235.23 86.58-100.9 219.43-120.81 21.15-2.96 37.57 9.09Q480-814.89 480-794.5q0 17.91-11.1 31.71-11.1 13.79-28.77 16.51-99.26 14.72-164.2 90.25Q211-580.5 211-480Zm462.61 45.5H400q-19.15 0-32.33-13.17Q354.5-460.85 354.5-480t13.17-32.33Q380.85-525.5 400-525.5h273.61l-65.68-65.67q-13.43-13.68-13.43-32.33t13.67-32.33Q621.85-669.5 640-669.5t31.83 13.67l144 143.76Q829.5-498.39 829.5-480t-13.67 32.07L672.07-304.17Q658.39-290.5 640-290.88q-18.39-.38-32.07-14.05-13.43-13.68-13.43-31.95t13.67-31.95l65.44-65.67Z"/>
                </svg>
            </button>

                <button class="save" onclick="window.history.back()">View Internships</button>
            </div>
        </div>

        <div class="job-content">
            <div class="margin">
                <h2><?php echo htmlspecialchars($job['internship_title']); ?></h2>
                <p><?php echo nl2br(htmlspecialchars($job['internship_description'])); ?></p>
            </div>

            <div class="margin">
                <h2><?php echo htmlspecialchars($job['internship_title']); ?> Skills</h2>
                <ul>
                    <?php 
                    $skills = explode("\n", $job['skills_required']);
                    foreach ($skills as $skill):
                        if (trim($skill)):
                    ?>
                        <li><?php echo htmlspecialchars(trim($skill)); ?></li>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </ul>
            </div>

            <!-- Add all other sections (Requirements, Qualifications, etc.) following the same pattern -->

            <div class="margin">
                <h2>Application Deadline</h2>
                <div class="calendar">
                    <div class="calendar-header">Deadline</div>
                    <div class="calendar-body">
                        <div class="calendar-day"><?php echo date('d', strtotime($job['application_deadline'])); ?></div>
                        <div class="calendar-month-year">
                            <?php echo date('F Y', strtotime($job['application_deadline'])); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="margin">
                <h2><?php echo htmlspecialchars($job['internship_title']); ?> Summary</h2>
                <p><?php echo nl2br(htmlspecialchars($job['internship_summary'])); ?></p>
            </div>

            <div class="margin">
                <h2>Additional Information</h2>
                <ul>
                    <?php 
                    $additionalInfo = explode("\n", $job['additional_info']);
                    foreach ($additionalInfo as $info):
                        if (trim($info)):
                    ?>
                        <li><?php echo htmlspecialchars(trim($info)); ?></li>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </ul>
            </div>
        </div>
    </div>


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
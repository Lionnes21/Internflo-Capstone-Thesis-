<?php
    session_start();
    include 'config.php';

    // Get internship ID from URL
    $internshipId = isset($_GET['id']) ? $_GET['id'] : null;

    if (!$internshipId) {
        header('Location: index.php');
        exit;
    }

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
        ), 0) as total_reviews
    FROM internshipad i
    JOIN approvedrecruiters r ON i.user_id = r.id
    WHERE i.internship_id = ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $internshipId);
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="studentfrontpageview.css">
    <link rel="stylesheet" href="../css/NAV.css">
    <link rel="stylesheet" href="../css/FOOTER.css">
    <link rel="icon" href="pics/ucclogo2.png">
    <title>UCC - Company</title>
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
            <a href="MAIN.php#about">ABOUT US</a>
            <a href="MAIN.php#contact">CONTACT US</a>
            <a href="../STUDENTCOORLOG/login.php" class="login-btn">LOGIN</a>
            <a href="../RECRUITER/companysignin.php" class="employer-btn">EMPLOYER SITE</a>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
        const navbar = document.querySelector('.navbar');
        let timeout;

        const hideNavbar = () => {
            // Only hide the navbar if we've scrolled down
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

            // Only set timeout to hide navbar if we're not at the top
            clearTimeout(timeout);
            if (window.scrollY > 0) {
                timeout = setTimeout(hideNavbar, 1000);
            }
        };

        // Add scroll event listener
        window.addEventListener('scroll', () => {
            if (window.scrollY === 0) {
                // At the top - always show navbar
                showNavbar();
                clearTimeout(timeout);
            } else {
                // Not at the top - reset the timeout
                resetNavbarTimeout();
            }
        });

        window.addEventListener('mousemove', resetNavbarTimeout);
        window.addEventListener('click', resetNavbarTimeout);
        window.addEventListener('keydown', resetNavbarTimeout);

        // Don't set initial timeout if we're at the top of the page
        if (window.scrollY > 0) {
            timeout = setTimeout(hideNavbar, 1000);
        }
        });

    </script>
    <!-- NAVIGATION -->


    <!-- Alert -->
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
    <!-- Alert -->


    <!-- MAIN -->
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
            <button class="quick-apply" onclick="window.location.href='../STUDENTCOORLOG/login.php'">Apply Now<svg xmlns="http://www.w3.org/2000/svg" height="22px" viewBox="0 -960 960 960" width="22px" fill="#808080"><path d="M211-480q0 100.5 64.93 176.03 64.94 75.53 164.2 90.25 17.67 2.72 28.77 16.51 11.1 13.8 11.1 31.71 0 19.63-16.29 31.95-16.3 12.31-36.69 9.59-133.09-19.15-220.05-120.05Q120-344.91 120-480q0-134.33 86.59-235.23 86.58-100.9 219.43-120.81 21.15-2.96 37.57 9.09Q480-814.89 480-794.5q0 17.91-11.1 31.71-11.1 13.79-28.77 16.51-99.26 14.72-164.2 90.25Q211-580.5 211-480Zm462.61 45.5H400q-19.15 0-32.33-13.17Q354.5-460.85 354.5-480t13.17-32.33Q380.85-525.5 400-525.5h273.61l-65.68-65.67q-13.43-13.68-13.43-32.33t13.67-32.33Q621.85-669.5 640-669.5t31.83 13.67l144 143.76Q829.5-498.39 829.5-480t-13.67 32.07L672.07-304.17Q658.39-290.5 640-290.88q-18.39-.38-32.07-14.05-13.43-13.68-13.43-31.95t13.67-31.95l65.44-65.67Z"/></svg></button>
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
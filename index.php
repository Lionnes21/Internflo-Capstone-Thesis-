<?php
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

    // Query for company grid (original code)
    $company_grid_sql = "SELECT ar.id, 
                    ar.company_name, 
                    ar.company_logo,
                    COUNT(ia.internship_id) as job_count 
                FROM approvedrecruiters ar
                LEFT JOIN internshipad ia ON ar.id = ia.user_id 
                GROUP BY ar.id, ar.company_name, ar.company_logo";
                
    $company_grid_result = $conn->query($company_grid_sql);

    // Query for job listings (new code)
    $search_keyword = $_GET['search_keyword'] ?? '';
    $search_location = $_GET['search_location'] ?? '';
    $industry = $_GET['classification'] ?? ''; // Add this line to get the industry value
    
    $query = "
    SELECT 
        i.internship_id,
        r.id as company_id,
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
        i.industry,
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
        ), 0) as total_reviews
    FROM internshipad i
    JOIN approvedrecruiters r ON i.user_id = r.id
    WHERE 1=1";
    
    $params = array();
    $types = '';
    
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/NAV.css">
    <link rel="stylesheet" href="./css/FOOTER.css">
    <link rel="stylesheet" href="./css/MAIN.css">

    <link rel="icon" href="MAIN/pics/ucc.png">
    <title>UCC - Internflo</title>
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body>


    <!-- NAVIGATION -->
    <div class="navbar">
            <div class="logo-container">
                <button class="menu-toggle" aria-label="Toggle Navigation">☰</button>
                <img src="MAIN/pics/ucc-logo.png" alt="Logo" class="logo-img">
            </div>
            <div class="nav-links">
                <a href="MAIN/MAIN.php">HOME</a>
                <a href="MAIN/MAIN.php#about">ABOUT US</a>
                <a href="MAIN/MAIN.php#contact">CONTACT US</a>
                <a href="STUDENTCOORLOG/login.php" class="login-btn">LOGIN</a>
                <a href="RECRUITER/companymainpage.html" class="employer-btn">EMPLOYER SITE</a>
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
                        menuToggle.innerHTML = '☰';
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
    

    <!-- BANNER HEADER -->
    <div class="scrolling" id="searchinternship">
        <div class="banner">
                    <p class="banner-text">
                        Transform your skills into opportunities. Build your <span style="color: #ff8c00; font-weight: 700" >Curriculim Vitae </span> with us today!
                    </p>
                    <button class="create-button" onclick="window.location.href='MAIN/resume/resume.php'">
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
                <button class="map" onclick="window.location.href='MAIN/map.php';">
                Find internships in the map!
                </button>
        </div>
    </div>
    <!-- BANNER HEADER -->


    <!-- SEARCH INPUTS -->
    <div class="search-wrapper">
                <div class="web-inputs-wrapper">
                    <div class="web-search-container">
                    <input type="text" class="web-search-input" placeholder="Enter Keyword">
                    <svg class="web-search-icon" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2E3849">
                        <path d="M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z"/>
                    </svg>
                    </div>
    
                    <div class="web-search-container">
                    <select class="web-search-select">
                        <option value="" disabled selected>Select Classification</option>
                        <option value="Finance">Finance</option>
                        <option value="Technology">Technology</option>
                        <option value="Healthcare">Healthcare</option>
                        <option value="Institutions">Institutions</option>
                        <option value="Media">Media</option>
                        <option value="Civil Society">Civil Society</option>
                        <option value="Advertisement">Advertisement</option>
                        <option value="Health and human services">Health and human services</option>
                        <option value="Entertainment">Entertainment</option>
                        <option value="Marketing">Marketing</option>
                        <option value="Law Enforcement">Law Enforcement</option>
                    </select>
                    <svg class="web-search-icon" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2E3849"><path d="M480-360 280-560h400L480-360Z"/></svg>
                    </div>
    
                    <div class="web-search-container">
                    <input type="text" class="web-search-input" placeholder="Enter Places">
                    <svg class="web-search-icon" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2E3849">
                        <path d="M480-480q33 0 56.5-23.5T560-560q0-33-23.5-56.5T480-640q-33 0-56.5 23.5T400-560q0 33 23.5 56.5T480-480Zm0 294q122-112 181-203.5T720-552q0-109-69.5-178.5T480-800q-101 0-170.5 69.5T240-552q0 71 59 162.5T480-186Zm0 106Q319-217 239.5-334.5T160-552q0-150 96.5-239T480-880q127 0 223.5 89T800-552q0 100-79.5 217.5T480-80Zm0-480Z"/>
                    </svg>
                    </div>
    
                    <div class="web-search-container">
                    <button class="web-search-button">Search</button>
                    </div>
                </div>
                <button class="filter-button">
                    <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#2E3849">
                        <path d="M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z"/>
                    </svg>
                    <div class="filter-content">
                        <span>All Internships</span>
                        <span class="dot">•</span>
                        <span>Philippines</span>
                    </div>
                </button>
        </div>
    <!-- SEARCH INPUTS -->


    <!-- Start Animation Container -->
    <div class="start-animation-container" id="startAnimation">
            <div class="start-animation-circles-container">
                <div class="start-animation-circle start-animation-small-circle"></div>
                <div class="start-animation-circle start-animation-large-circle"></div>
            </div>
            <div class="start-animation-text-container">
                <h1>
                    Connecting 
                    <span class="start-animation-green-highlight start-animation-opportunities">Opportunities</span>
                    <span class="start-animation-everywhere">Everywhere</span>
                </h1>
            </div>
    </div>
    <script>
           document.addEventListener('DOMContentLoaded', function() {
                // Get the current page name from the URL or set a default
                const currentPage = window.location.pathname.split('/').pop() || 'main';
                const storageKey = `hasVisited_${currentPage}`;
                
                // Check if this specific page has been visited
                if (!sessionStorage.getItem(storageKey)) {
                    const startAnimation = document.getElementById('startAnimation');
                    startAnimation.style.display = 'flex'; // Make sure it's visible initially
                    
                    // Hide start animation after 3 seconds
                    setTimeout(() => {
                        startAnimation.classList.add('hide');
                        
                        // Remove from DOM after transition completes
                        setTimeout(() => {
                            startAnimation.style.display = 'none';
                        }, 500);
                    }, 3000);
                    
                    // Set the flag in session storage for this specific page
                    sessionStorage.setItem(storageKey, 'true');
                } else {
                    // If not first visit, hide the start animation immediately
                    const startAnimation = document.getElementById('startAnimation');
                    startAnimation.style.display = 'none';
                }
            });
    </script>


    <!-- COMPANY SEARCH -->
    <div class="divbg">
                <div class="container">
                        <div class="job-listings-container">
                            <div class="job-count"><?php echo $job_number; ?> Internships </div>
                            <div class="job-listings">
                            <?php if (!empty($job_listings)): ?>
                                <?php foreach ($job_listings as $job): ?>
                                    <div class="job-card"
                                        data-id="<?php echo htmlspecialchars($job['internship_id']); ?>" 
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
    <script>
            document.addEventListener("DOMContentLoaded", function () {
                // Initial setup of DOM elements
                const urlParams = new URLSearchParams(window.location.search);
                const divbg = document.querySelector('.divbg');
                const loadingOverlay = document.createElement('div');
                loadingOverlay.classList.add('loading-overlay');
                
                const spinner = document.createElement('div');
                spinner.classList.add('spinner');
                
                const loadingText = document.createElement('div');
                loadingText.classList.add('loading-text');
                loadingText.textContent = 'Finding companies...';
                
                loadingOverlay.appendChild(spinner);
                loadingOverlay.appendChild(loadingText);
                document.body.appendChild(loadingOverlay);

                // Initial visibility setup
                if (!urlParams.has('search_keyword') && 
                    !urlParams.has('classification') && 
                    !urlParams.has('search_location') && 
                    !urlParams.has('keepDivbg')) {
                    if (divbg) {
                        divbg.style.display = 'none';
                    }
                }

                if (loadingOverlay) {
                    loadingOverlay.classList.add('show');
                }

                setTimeout(() => {
                    if (loadingOverlay) {
                        loadingOverlay.classList.remove('show');
                    }
                    if (divbg && (urlParams.toString() || urlParams.has('keepDivbg'))) {
                        divbg.classList.add('show');
                    }
                }, 2000);

                loadGoogleMapsAPI();
                initializeSearchFromUrl();

                // Add click event listeners to job cards
                document.querySelectorAll(".job-card").forEach((card) => {
                    card.addEventListener("click", () => updateJobOffers(card));
                });

                // Define all the functions within DOMContentLoaded scope
                function scrollToDivbg() {
                    const divbg = document.querySelector('.divbg');
                    if (divbg) {
                        window.scrollTo(0, 0);
                        setTimeout(() => {
                            divbg.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }, 100);
                    }
                }

                function addFilterLoadingAnimation() {
                    const keywordInput = document.querySelector('.web-search-input[placeholder="Enter Keyword"]');
                    const classificationSelect = document.querySelector('.web-search-select');
                    const locationInput = document.querySelector('.web-search-input[placeholder="Enter Places"]');
                    
                    // Clear inputs
                    if (keywordInput) keywordInput.value = '';
                    if (classificationSelect) classificationSelect.value = '';
                    if (locationInput) locationInput.value = '';

                    if (loadingOverlay) {
                        loadingOverlay.classList.add('show');
                        const loadingText = loadingOverlay.querySelector('.loading-text');
                        if (loadingText) {
                            loadingText.textContent = 'Searching for all companies...';
                        }
                    }
                    
                    if (divbg) {
                        divbg.classList.add('show');
                    }
                    
                    setTimeout(() => {
                        sessionStorage.setItem('scrollToDivbg', 'true');
                        window.location.href = window.location.pathname + '?keepDivbg=true';
                    }, 2000);
                }

                function handleSearch(event) {
                event.preventDefault();
                
                const keyword = document.querySelector('.web-search-input[placeholder="Enter Keyword"]')?.value.trim() || '';
                const classification = document.querySelector('.web-search-select')?.value || '';
                const location = document.querySelector('.web-search-input[placeholder="Enter Places"]')?.value.trim() || '';
                
                if (loadingOverlay) {
                    loadingOverlay.classList.add('show');
                    const loadingText = loadingOverlay.querySelector('.loading-text');
                    if (loadingText) {
                        loadingText.textContent = 'Searching...';
                    }
                }
                
                let queryParams = new URLSearchParams();
                
                if (keyword) {
                    queryParams.append('search_keyword', keyword);
                }
                
                if (classification && classification !== "") {
                    queryParams.append('classification', classification);
                }
                
                if (location) {
                    queryParams.append('search_location', location);
                }
                
                queryParams.append('keepDivbg', 'true');
                sessionStorage.setItem('scrollToDivbg', 'true');
                
                setTimeout(() => {
                    window.location.href = `${window.location.pathname}?${queryParams.toString()}`;
                }, 3000);
            }

                // Add event listeners after all functions are defined
                const searchButton = document.querySelector('.web-search-button');
                const keywordInput = document.querySelector('.web-search-input[placeholder="Enter Keyword"]');
                const locationInput = document.querySelector('.web-search-input[placeholder="Enter Places"]');
                const filterButton = document.querySelector('.filter-button');

                // Add event listeners with null checks
                if (searchButton) {
                    searchButton.addEventListener('click', handleSearch);
                }

                if (filterButton) {
                    filterButton.addEventListener('click', addFilterLoadingAnimation);
                }

                // Add Enter key listeners
                [keywordInput, locationInput].forEach(input => {
                    if (input) {
                        input.addEventListener('keypress', (event) => {
                            if (event.key === 'Enter') {
                                event.preventDefault();
                                handleSearch(event);
                            }
                        });
                    }
                });

                // Check and handle scroll after page load
                if (sessionStorage.getItem('scrollToDivbg')) {
                    setTimeout(() => {
                        if (divbg) {
                            divbg.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            sessionStorage.removeItem('scrollToDivbg');
                        }
                    }, 500);
                }
            });
            // Add scroll behavior after page load
            if (sessionStorage.getItem('scrollToDivbg')) {
                const divbg = document.querySelector('.divbg');
                if (divbg) {
                    // Scroll to divbg with smooth behavior
                    setTimeout(() => {
                        divbg.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        sessionStorage.removeItem('scrollToDivbg'); // Clear the flag
                    }, 500); // Small delay to ensure elements are loaded
                }
            }

            // Handle search button click
            searchButton.addEventListener('click', handleSearch);
            filterButton.addEventListener('click', addFilterLoadingAnimation);

            // Handle Enter key in search inputs
            [keywordInput, locationInput].forEach(input => {
                input.addEventListener('keypress', (event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        handleSearch(event);
                    }
                });
            });

            function initializeSearchFromUrl() {
                const urlParams = new URLSearchParams(window.location.search);
                
                const keywordInput = document.querySelector('.web-search-input[placeholder="Enter Keyword"]');
                const classificationSelect = document.querySelector('.web-search-select');
                const locationInput = document.querySelector('.web-search-input[placeholder="Enter Places"]');
                const loadingOverlay = document.querySelector('.loading-overlay');
                const divbg = document.querySelector('.divbg');
                
                // Remove loading overlay if it exists
                if (loadingOverlay) {
                    loadingOverlay.classList.remove('show');
                }
                
                // Show divbg if there are search parameters or keepDivbg is true
                if (urlParams.has('search_keyword') || 
                    urlParams.has('classification') || 
                    urlParams.has('search_location') ||
                    urlParams.has('keepDivbg')) {
                    if (divbg) {
                        divbg.classList.add('show');
                    }
                }
                
                if (urlParams.has('search_keyword')) {
                    keywordInput.value = urlParams.get('search_keyword');
                }
                
                if (urlParams.has('classification')) {
                    classificationSelect.value = urlParams.get('classification');
                }
                
                if (urlParams.has('search_location')) {
                    locationInput.value = urlParams.get('search_location');
                }
            }
            function loadGoogleMapsAPI() {
                if (typeof google === "undefined") {
                    const script = document.createElement("script");
                    script.src = `https://maps.googleapis.com/maps/api/js?key=AIzaSyDtbgRcgki0qgsq4Kt6c0JlhhUhEUH7PXQ&libraries=geometry&callback=initMap&loading=async`;
                    script.async = true;
                    script.defer = true;
                    script.onerror = function () {
                        handleLocationError("Failed to load Google Maps API");
                    };
                    document.head.appendChild(script);
                } else {
                    initMap();
                }
            }

            function initMap() {
                getUserLocation();
            }

            function getUserLocation() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(successCallback, errorCallback, {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0,
                    });
                } else {
                    handleLocationError("Geolocation is not supported by this browser.");
                }
            }

            function successCallback(position) {
                const userLat = position.coords.latitude;
                const userLon = position.coords.longitude;

                const jobCards = document.querySelectorAll(".job-card");
                const jobCardsArray = Array.from(jobCards);

                let validDistanceFound = false;

                jobCardsArray.forEach((card) => {
                    const distanceElement = card.querySelector(".company-distance");
                    const companyLat = parseFloat(distanceElement.getAttribute("data-lat"));
                    const companyLon = parseFloat(distanceElement.getAttribute("data-lon"));

                    if (!companyLat || !companyLon || isNaN(companyLat) || isNaN(companyLon)) {
                        card.distance = Infinity;
                        distanceElement.textContent = "Distance unavailable";
                        return;
                    }

                    try {
                        const distance = google.maps.geometry.spherical.computeDistanceBetween(
                            new google.maps.LatLng(userLat, userLon),
                            new google.maps.LatLng(companyLat, companyLon)
                        );

                        const distanceKm = (distance / 1000).toFixed(2);
                        card.distance = parseFloat(distanceKm);
                        distanceElement.textContent = `${distanceKm} km away`;
                        validDistanceFound = true;
                    } catch (error) {
                        console.error("Error calculating distance:", error);
                        card.distance = Infinity;
                        distanceElement.textContent = "Error calculating distance";
                    }
                });

                if (validDistanceFound) {
                    jobCardsArray.sort((a, b) => a.distance - b.distance);
                    const jobListings = document.querySelector(".job-listings");
                    const noResults = jobListings.querySelector(".no-results");
                    jobListings.innerHTML = "";
                    
                    jobCardsArray.forEach((card) => {
                        jobListings.appendChild(card);
                        card.addEventListener("click", () => updateJobOffers(card));
                    });

                    if (noResults && !validDistanceFound) {
                        jobListings.appendChild(noResults);
                    }

                    if (jobCardsArray.length > 0) {
                        updateJobOffers(jobCardsArray[0]);
                    }
                }
            }

            function errorCallback(error) {
                handleLocationError(`Error getting location: ${error.message}`);
            }

            function handleLocationError(message) {
                console.error(message);
                const distanceElements = document.getElementsByClassName("company-distance");
                Array.from(distanceElements).forEach((element) => {
                    element.textContent = "Distance unavailable";
                });
            }

            function updateJobOffers(card) {
                const jobOffers = document.querySelector(".job-offers");
                document.querySelectorAll(".job-card").forEach((c) => c.classList.remove("active"));
                card.classList.add("active");


                const internshipId = card.getAttribute("data-id");
                // Extract necessary data attributes
                
                const companyLogo = card.getAttribute("data-company-logo");
                const companyName = card.getAttribute("data-company-name");
                const companyAddress = card.getAttribute("data-company-address");
                const internshipTitle = card.getAttribute("data-internship-title");
                const internshipType = card.getAttribute("data-internship-type");
                const internshipDescription = card.getAttribute(
                    "data-internship-description"
                );
                const numberOfOpenings = card.getAttribute("data-number-of-openings");
                const duration = card.getAttribute("data-duration");
                const department = card.getAttribute("data-department");
                const requirements = card.getAttribute("data-requirements");
                const qualifications = card.getAttribute("data-qualifications");
                const skillsRequired = card.getAttribute("data-skills-required");
                const applicationDeadline = card.getAttribute("data-application-deadline");
                const additionalInfo = card.getAttribute("data-additional-info");
                const postedTime = card.getAttribute("data-posted-time");
                const companyId = card.getAttribute("data-company-id");
                const internshipSummary = card.getAttribute("data-internship-summary");
                const companyOverview = card.getAttribute("data-company-overview");
                const latitude = card.getAttribute("data-latitude");
                const longitude = card.getAttribute("data-longitude");
                const companyWebsite = card.getAttribute("data-company-email");
                const totalRating = parseFloat(card.getAttribute("data-total-rating")) || 0;
                const totalReviews = parseInt(card.getAttribute("data-total-reviews")) || 0;
                    let starsHtml = '';
                    for (let i = 1; i <= 5; i++) {
                        if (i <= totalRating) {
                            starsHtml += '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ff9800"><path d="M480-259.91 313.28-159.43q-12.67 7.95-26.35 6.83-13.67-1.12-23.86-9.07-10.2-7.96-15.8-20.01-5.6-12.06-2.12-26.73l44.24-189.72-147.72-127.72q-11.43-10.19-14.29-23.25-2.86-13.05 1.38-25.49 4.24-12.43 13.79-20.63 9.56-8.19 25.23-10.19l194.72-17 75.48-178.96q5.72-13.91 17.53-20.63 11.82-6.72 24.49-6.72 12.67 0 24.49 6.72 11.81 6.72 17.53 20.63l75.48 178.96 194.72 17q15.67 2 25.23 10.19 9.55 8.2 13.79 20.63 4.24 12.44 1.38 25.49-2.86 13.06-14.29 23.25L670.61-398.13l44.24 189.72q3.48 14.67-2.12 26.73-5.6 12.05-15.8 20.01-10.19 7.95-23.86 9.07-13.68 1.12-26.35-6.83L480-259.91Z"/></svg>';
                        } else {
                            starsHtml += '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ccc"><path d="M480-259.91 313.28-159.43q-12.67 7.95-26.35 6.83-13.67-1.12-23.86-9.07-10.2-7.96-15.8-20.01-5.6-12.06-2.12-26.73l44.24-189.72-147.72-127.72q-11.43-10.19-14.29-23.25-2.86-13.05 1.38-25.49 4.24-12.43 13.79-20.63 9.56-8.19 25.23-10.19l194.72-17 75.48-178.96q5.72-13.91 17.53-20.63 11.82-6.72 24.49-6.72 12.67 0 24.49 6.72 11.81 6.72 17.53 20.63l75.48 178.96 194.72 17q15.67 2 25.23 10.19 9.55 8.2 13.79 20.63 4.24 12.44 1.38 25.49-2.86 13.06-14.29 23.25L670.61-398.13l44.24 189.72q3.48 14.67-2.12 26.73-5.6 12.05-15.8 20.01-10.19 7.95-23.86 9.07-13.68 1.12-26.35-6.83L480-259.91Z"/></svg>';
                        }
                    }

                jobOffers.innerHTML = `
                    <div class="bgwidth">
                    <div class="bgcolor">
                    <div class="logo">
                        <img src="../RECRUITER/${companyLogo}" alt="Company Logo">
                    </div>

                    <h1 class="tit">${internshipTitle}</h1>

                    <div class="company">
                        <span>${companyName}</span>
                        <div class="rating">
                            <div class="stars">
                                ${starsHtml}
                            </div>
                            <a href="#" class="review-link">${totalReviews} reviews</a>
                            <span class="dot">•</span>
                            <a href="#" class="review-link">View all internships</a>
                        </div>
                    </div>

                    <div class="details">
                        <div class="detail-item">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2E3849">
                            <path d="M480-186q122-112 181-203.5T720-552q0-109-69.5-178.5T480-800q-101 0-170.5 69.5T240-552q0 71 59 162.5T480-186Zm0 79q-14 0-28-5t-25-15q-65-60-115-117t-83.5-110.5q-33.5-53.5-51-103T160-552q0-150 96.5-239T480-880q127 0 223.5 89T800-552q0 45-17.5 94.5t-51 103Q698-301 648-244T533-127q-11 10-25 15t-28 5Zm0-453Zm0 80q33 0 56.5-23.5T560-560q0-33-23.5-56.5T480-640q-33 0-56.5 23.5T400-560q0 33 23.5 56.5T480-480Z"/>
                        </svg>
                        <span>${companyAddress}</span>
                        <button class="direction-button" onclick="window.location.href='MAIN/DirectionMap.php?lat=${latitude}&lng=${longitude}&logo=${encodeURIComponent(companyLogo)}&name=${encodeURIComponent(companyName)}&address=${encodeURIComponent(companyAddress)}&contact=${encodeURIComponent(card.getAttribute('data-company-phone'))}&website=${encodeURIComponent(card.getAttribute('data-company-email'))}&id=${companyId}&internship_id=${internshipId}'">Direction<svg xmlns="http://www.w3.org/2000/svg" height="22px" viewBox="0 -960 960 960" width="22px" fill="white"><path d="m300-300 280-80 80-280-280 80-80 280Zm180-120q-25 0-42.5-17.5T420-480q0-25 17.5-42.5T480-540q25 0 42.5 17.5T540-480q0 25-17.5 42.5T480-420Zm0 340q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q133 0 226.5-93.5T800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 133 93.5 226.5T480-160Zm0-320Z"/></svg></button>
                        </div>
                        
                        <div class="detail-item">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2E3849">
                            <path d="M520-496v-144q0-17-11.5-28.5T480-680q-17 0-28.5 11.5T440-640v159q0 8 3 15.5t9 13.5l132 132q11 11 28 11t28-11q11-11 11-28t-11-28L520-496ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-400Zm0 320q133 0 226.5-93.5T800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 133 93.5 226.5T480-160Z"/>
                        </svg>
                        <span>${internshipType}</span>
                        <span class="dot">•</span>
                        <span>${duration} hours</span>
                        </div>

                        <div class="detail-item">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2E3849">
                            <path d="M80-200v-560q0-33 23.5-56.5T160-840h240q33 0 56.5 23.5T480-760v80h320q33 0 56.5 23.5T880-600v400q0 33-23.5 56.5T800-120H160q-33 0-56.5-23.5T80-200Zm80 0h80v-80h-80v80Zm0-160h80v-80h-80v80Zm0-160h80v-80h-80v80Zm0-160h80v-80h-80v80Zm160 480h80v-80h-80v80Zm0-160h80v-80h-80v80Zm0-160h80v-80h-80v80Zm0-160h80v-80h-80v80Zm160 480h320v-400H480v80h80v80h-80v80h80v80h-80v80Zm160-240v-80h80v80h-80Zm0 160v-80h80v80h-80Z"/>
                        </svg>
                        <span>${internshipTitle} (${department})</span>
                        </div>

                        <div class="detail-item">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2E3849">
                            <path d="M500-482q29-32 44.5-73t15.5-85q0-44-15.5-85T500-798q60 8 100 53t40 105q0 60-40 105t-100 53Zm198 322q11-18 16.5-38.5T720-240v-40q0-36-16-68.5T662-406q51 18 94.5 46.5T800-280v40q0 33-23.5 56.5T720-160h-22Zm102-360h-40q-17 0-28.5-11.5T720-560q0-17 11.5-28.5T760-600h40v-40q0-17 11.5-28.5T840-680q17 0 28.5 11.5T880-640v40h40q17 0 28.5 11.5T960-560q0 17-11.5 28.5T920-520h-40v40q0 17-11.5 28.5T840-440q-17 0-28.5-11.5T800-480v-40Zm-480 40q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM0-240v-32q0-34 17.5-62.5T64-378q62-31 126-46.5T320-440q66 0 130 15.5T576-378q29 15 46.5 43.5T640-272v32q0 33-23.5 56.5T560-160H80q-33 0-56.5-23.5T0-240Zm320-320q33 0 56.5-23.5T400-640q0-33-23.5-56.5T320-720q-33 0-56.5 23.5T240-640q0 33 23.5 56.5T320-560ZM80-240h480v-32q0-11-5.5-20T540-306q-54-27-109-40.5T320-360q-56 0-111 13.5T100-306q-9 5-14.5 14T80-272v32Zm240-400Zm0 400Z"/>
                        </svg>
                        <span>${numberOfOpenings} available internship spots</span>
                        </div>

                        <div class="detail-item">
                        <span style= "color: #666666;"">Posted ${postedTime}</span> 
                        </div>
                    </div>
                    <div class="buttons">
                        <button class="quick-apply" onclick="window.location.href='../STUDENTCOORLOG/login.php'">Apply Now<svg xmlns="http://www.w3.org/2000/svg" height="22px" viewBox="0 -960 960 960" width="22px" fill="#FFFFFF"><path d="M211-480q0 100.5 64.93 176.03 64.94 75.53 164.2 90.25 17.67 2.72 28.77 16.51 11.1 13.8 11.1 31.71 0 19.63-16.29 31.95-16.3 12.31-36.69 9.59-133.09-19.15-220.05-120.05Q120-344.91 120-480q0-134.33 86.59-235.23 86.58-100.9 219.43-120.81 21.15-2.96 37.57 9.09Q480-814.89 480-794.5q0 17.91-11.1 31.71-11.1 13.79-28.77 16.51-99.26 14.72-164.2 90.25Q211-580.5 211-480Zm462.61 45.5H400q-19.15 0-32.33-13.17Q354.5-460.85 354.5-480t13.17-32.33Q380.85-525.5 400-525.5h273.61l-65.68-65.67q-13.43-13.68-13.43-32.33t13.67-32.33Q621.85-669.5 640-669.5t31.83 13.67l144 143.76Q829.5-498.39 829.5-480t-13.67 32.07L672.07-304.17Q658.39-290.5 640-290.88q-18.39-.38-32.07-14.05-13.43-13.68-13.43-31.95t13.67-31.95l65.44-65.67Z"/></svg></button>
                        <button class="save" onclick="window.location.href='MAIN/studentfrontpageview.php?id=${internshipId}'">View Details</button>
                    </div>
                    </div>


                    <div class="job-content">
                    <h2>Company</h2>
                    <p>${companyOverview}</p>
                    </div>
                    </div>
                    
                `;
            }
    </script>
    <!-- COMPANY SEARCH -->


    <!-- COMPANY CARDS -->
    <div class="com-container">
            <h1 class="com-main-heading">Find your <span class="highlight">ideal</Span> internship.</h1>
            <p class="com-subtitle"><span style="color: #ff8c00; font-weight: 600">Explore</span> company profiles to find your ideal internship. Learn about roles, reviews, culture, and<br>benefits to find the perfect match for your career.</p>

            <div class="com-company-grid-container">
                <div class="com-company-grid" id="companyGrid">
                <?php
                    if ($company_grid_result->num_rows > 0) {
                        while($row = $company_grid_result->fetch_assoc()) {
                            $jobCount = $row['job_count'];
                            $jobText = $jobCount == 1 ? 'Internship' : 'Internships';
                            
                            echo '<a href="MAIN/COMPANYCARDINFO-VIEW.php?id=' . htmlspecialchars($row['id']) . '" class="com-company-card">';
                            echo '<img src="RECRUITER/' . htmlspecialchars($row['company_logo']) . '" alt="' . htmlspecialchars($row['company_name']) . ' Logo" class="com-company-logo">';
                            echo '<h2 class="com-company-name">' . htmlspecialchars($row['company_name']) . '</h2>';
                            echo '<p class="com-job-count">' . $jobCount . ' ' . $jobText . '</p>';
                            echo '</a>';
                        }
                    }
                    ?>
                </div>
            </div>
    </div>
    <script>
          document.addEventListener('DOMContentLoaded', function() {
            const grid = document.querySelector('.com-company-grid');
            let scrollInterval;
            let isDragging = false;
            let startPosition = 0;
            let scrollLeft = 0;

            // Variables for drag momentum
            let dragVelocity = 0;
            let lastDragPosition = 0;
            let lastDragTime = 0;
            
            const cardWidth = 300 + 24; // card width + gap
            
            function startAutoScroll() {
                scrollInterval = setInterval(() => {
                    if (!isDragging) {
                        // Use smooth scroll behavior
                        if (grid.scrollLeft >= (grid.scrollWidth - grid.clientWidth)) {
                            grid.scrollTo({
                                left: 0,
                                behavior: 'smooth'
                            });
                        } else {
                            grid.scrollTo({
                                left: grid.scrollLeft + cardWidth,
                                behavior: 'smooth'
                            });
                        }
                    }
                }, 3000); // Increased interval for smoother experience
            }

            // Mouse Events
            function handleDragStart(e) {
                isDragging = true;
                startPosition = e.type === 'mousedown' ? e.pageX : e.touches[0].pageX;
                scrollLeft = grid.scrollLeft;
                lastDragPosition = startPosition;
                lastDragTime = Date.now();
                
                clearInterval(scrollInterval);
                grid.style.cursor = 'grabbing';
                grid.style.userSelect = 'none';
            }

            function handleDragMove(e) {
                if (!isDragging) return;

                e.preventDefault();
                const currentPosition = e.type === 'mousemove' ? e.pageX : e.touches[0].pageX;
                const walk = (currentPosition - startPosition) * 2; // Multiply by 2 for faster drag
                grid.scrollLeft = scrollLeft - walk;

                // Calculate velocity
                const currentTime = Date.now();
                const timeElapsed = currentTime - lastDragTime;
                const dragDistance = currentPosition - lastDragPosition;
                dragVelocity = dragDistance / timeElapsed;

                lastDragPosition = currentPosition;
                lastDragTime = currentTime;
            }

            function handleDragEnd() {
                if (!isDragging) return;
                isDragging = false;
                grid.style.cursor = 'grab';
                grid.style.userSelect = '';

                // Apply momentum with smooth scroll
                if (Math.abs(dragVelocity) > 0.5) {
                    const momentum = dragVelocity * 100;
                    grid.scrollTo({
                        left: grid.scrollLeft - momentum,
                        behavior: 'smooth'
                    });
                }

                startAutoScroll();
            }

            // Touch and Mouse Events
            grid.addEventListener('touchstart', handleDragStart);
            grid.addEventListener('touchmove', handleDragMove);
            grid.addEventListener('touchend', handleDragEnd);
            grid.addEventListener('mousedown', handleDragStart);
            grid.addEventListener('mousemove', handleDragMove);
            grid.addEventListener('mouseup', handleDragEnd);
            grid.addEventListener('mouseleave', handleDragEnd);

            // Prevent click events during drag
            grid.addEventListener('click', (e) => {
                if (Math.abs(dragVelocity) > 0.5) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }, true);

            // Start auto-scroll
            startAutoScroll();
            });
    </script>
    <!-- COMPANY CARDS -->
     


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
                "MAIN/pics/pic.jpg",
                "MAIN/pics/south.jpg"
                
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
                <h1><span>Services</span> we provide.</h1>
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
                    <h1>Talk to<span> Roger.</span></h1>
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


    <!-- APP DOWNLOAD -->
    <div class="app-download">
            <div class="about-heading">
                <h1>Start your <span>internship</span> journey.</h1>
                <p>Gain <span style="color: #ff8c00; font-weight: 600">real-world</span> experience, develop your skills, and take the first step toward your career. <br> Internflo helps you connect with opportunities that match your goals!</p>
            </div>
            <div class="app-container">
                <!-- Left column - Contains text section -->
                <div class="app-left-column">
                    <!-- Text and button section -->
                    <div class="app-text-section">
                        <h1 class="app-heading">Apply with Intern<span class="highlight">flo.</span></h1>
                        <p class="app-subheading">Get our <span style="color: #ff8c00; font-weight: 600">mobile app</span> now to explore exciting opportunities on the go. Simply scan the QR code or click the download button to start your journey today and take the first step toward success with ease and confidence through Internflo’s smart career platform.</p>
                        <div class="app-download-section">
                            <a href="https://internflo-ucc.com/Internflo.apk" download class="app-download-btn">
                                Download APK
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M6-221q9.81-115.2 71.41-212.1Q139-530 239-587l-80-137q-6-10-2.5-20.5T170-761q10-6 20.38-2.6 10.39 3.4 16.62 13.6l79 138q92-39 194-39t194 39l78.63-138.28Q759-760 769.5-763.5T790-761q10 6 13.5 16.5T801-724l-80 137q100 57 161.59 153.9Q944.19-336.2 954-221H6Zm259.23-118Q288-339 303-354.73q15-15.72 15-38.5 0-22.77-15-38.27-15-15.5-37.77-15.5-22.78 0-38.5 15.73Q211-415.55 211-392.77q0 22.77 15.73 38.27 15.72 15.5 38.5 15.5Zm430 0q22.77 0 38.27-15.73 15.5-15.72 15.5-38.5 0-22.77-15.73-38.27-15.72-15.5-38.5-15.5-22.77 0-38.27 15.73-15.5 15.72-15.5 38.5 0 22.77 15.73 38.27 15.72 15.5 38.5 15.5Z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Right column - QR code section (moved to be a proper column) -->
                <div class="app-right-column">
                    <div class="app-qr-section">
                        <p class="app-subheading">Scan to download <span style="color: #ff8c00; font-weight: 600">instantly</span> now.</p>
                        <div class="app-qr-code">
                            <img src="Internflo.png" alt="QR Code for app download">
                            <p class="app-qr-text">Scan to download</p>
                        </div>
                    </div>
                </div>
            </div>
    </div>
    <!-- APP DOWNLOAD -->

    
    <!-- CONTACT SECTION -->
    <div class="scrolling" id="contact">
            <div class="about-hero">
                <div class="about-heading">
                    <h1>Send us your <span>feedback.</span></h1>
                    <p>We value your <span style="color: #ff8c00; font-weight: 600">thoughts</span> and <span style="color: #ff8c00; font-weight: 600">opinions</span>  your feedback helps us <br> grow, improve, and better serve your needs.</p>
                </div>
                <div class="contact-container">
                    <div class="about-hero-map">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3858.256292214547!2d121.02833497457567!3d14.754585573289205!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b1cc9c9c83e9%3A0x303a03298da24ddb!2sUniversity%20of%20Caloocan%20City%20-%20Congressional%20Campus!5e0!3m2!1sen!2sph!4v1734309290308!5m2!1sen!2sph" width="100%" height="520" style="border: 3px solid #2e3849; border-radius: 8px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>

                    <div class="about-hero-form">
                        <h2>University of Caloocan City</h2>
                        <p>Congressional Campus</p> 
                        <form id="feedbackForm" action="MAIN/submit_feedback.php" method="POST">
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
                                    
                                    fetch('MAIN/submit_feedback.php', {
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


    <!-- CHAT WIDGET -->
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
                        <li><a href="MAIN/MAIN.php#searchinternship">Internship by Company</a></li>
                        <li><a href="MAIN/MAIN.php#searchinternship">Internship by City</a></li>
                        <li><a href="MAIN/MAIN.php#searchinternship">Search Nearby Internship</a></li>
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
                        <li><a href="MAIN/MAIN.php#about">About Us</a></li>
                        <li><a href="MAIN/MAIN.php#aichat">How It Works</a></li>
                        <li><a href="MAIN/MAIN.php#contact">Contact Us</a></li>
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

    <script src="MAIN.js"></script>
</body>
</html>

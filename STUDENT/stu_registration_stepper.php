<?php
    // Database configuration
	$servername = "localhost";
	$username = "u798912504_root";
	$password = "Internfloucc2025*"; // Update with your database password
	$dbname = "u798912504_internflo";

    // Create a new database connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check the connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get student details
    $student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';
    $student_data = null;

    if ($student_id) {
        // Define tables to check
        $tables = ['students_ce', 'students_clas', 'students_crim', 'student_ba'];
        
        foreach ($tables as $table) {
            $stmt = $conn->prepare("SELECT student_id, firstname, lastname, middlename, suffix, course, year, section FROM $table WHERE student_id = ?");
            $stmt->bind_param("s", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $student_data = $result->fetch_assoc();
                // Combine year and section
                $student_data['yearsection'] = $student_data['year'] . '-' . $student_data['section'];
                break; // Exit the loop once found
            }
        }
    }

    // Function to mask string (show first letter, rest as asterisks)
    function maskString($str) {
        if (empty($str)) return '';
        return substr($str, 0, 1) . str_repeat('*', strlen($str) - 1);
    }

    // Add endpoint for verifying last name
    if (isset($_POST['action']) && $_POST['action'] === 'verify_lastname') {
        $entered_lastname = $_POST['entered_lastname'];
        $student_id = $_POST['student_id'];
        
        // Define tables to check
        $tables = ['students_ce', 'students_clas', 'students_crim', 'student_ba'];
        $found = false;
        $matches = false;
        
        foreach ($tables as $table) {
            $stmt = $conn->prepare("SELECT lastname FROM $table WHERE student_id = ?");
            $stmt->bind_param("s", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $found = true;
                $row = $result->fetch_assoc();
                $actual_lastname = $row['lastname'];
                
                // Compare case-insensitive
                $matches = (strtolower($entered_lastname) === strtolower($actual_lastname));
                break; // Exit the loop once found
            }
        }
        
        if ($found) {
            echo json_encode(['success' => true, 'matches' => $matches]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Student not found']);
        }
        exit;
    }

    // Add endpoint for checking email uniqueness
    if (isset($_POST['email'])) {
        $email = $_POST['email'];

        // Prepare the SQL statement to check if the email exists
        $stmt = $conn->prepare("SELECT email FROM students WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // Return JSON response
        if ($result->num_rows > 0) {
            echo json_encode(['exists' => true]);
        } else {
            echo json_encode(['exists' => false]);
        }
        $stmt->close();
        exit;
    }

    $conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UCC - Student Registration</title>
    <link rel="stylesheet" href="stu_registration_stepper.css">
    <link rel="stylesheet" href="NAV.css">
    <link rel="icon" href="pics/ucc.png">
    <link rel="stylesheet" href="../css/FOOTER.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Font Awesome -->
</head>
<body>


    <!-- NAVIGATION -->
    <div class="navbar">
            <div class="logo-container">
                <button class="menu-toggle" aria-label="Toggle Navigation">☰</button>
                <img src="pics/ucc-logo.png" alt="Logo" class="logo-img">
            </div>
            <div class="nav-links">
                <a href="../MAIN/MAIN.php#">HOME</a>
                <a href="../MAIN/MAIN.php#about">ABOUT US</a>
                <a href="../MAIN/MAIN.php#contact">CONTACT US</a>
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


    <!-- FORM -->
        <div class="container90">
            <div class="container">
                <!-- Sign Up Section -->       
                <div class="image-container">
                    <img src="pics/pic1.jpg" alt="Image Description">
                </div>
            
                <fieldset>
                        <div class="signup-header">
                            <h1>Student <span class="highlight">Sign up</span></h1>
                            <p>It's free and takes less than 60 seconds</p>                  
                        </div>

                        <div class="stepper">
                            <div class="step-indicator active" data-step="1">
                                <div class="step-circle">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ddd">
                                        <path d="M480-280q83 0 141.5-58.5T680-480q0-83-58.5-141.5T480-680q-83 0-141.5 58.5T280-480q0 83 58.5 141.5T480-280Zm0 200q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                                    </svg>
                                </div>
                                <div class="step-text">Personal Info</div>
                            </div>
                            <div class="step-indicator" data-step="2">
                                <div class="step-circle">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ddd">
                                        <path d="M480-280q83 0 141.5-58.5T680-480q0-83-58.5-141.5T480-680q-83 0-141.5 58.5T280-480q0 83 58.5 141.5T480-280Zm0 200q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                                    </svg>
                                </div>
                                <div class="step-text">Address</div>
                            </div>
                            <div class="step-indicator" data-step="3">
                                <div class="step-circle">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ddd">
                                        <path d="M480-280q83 0 141.5-58.5T680-480q0-83-58.5-141.5T480-680q-83 0-141.5 58.5T280-480q0 83 58.5 141.5T480-280Zm0 200q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                                    </svg>
                                </div>
                                <div class="step-text">Contact Info</div>
                            </div>
                            <div class="step-indicator" data-step="4">
                                <div class="step-circle">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ddd">
                                        <path d="M480-280q83 0 141.5-58.5T680-480q0-83-58.5-141.5T480-680q-83 0-141.5 58.5T280-480q0 83 58.5 141.5T480-280Zm0 200q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                                    </svg>
                                </div>
                                <div class="step-text">Account</div>
                            </div>
                        </div>


                        <form action="stu_registration_submit.php" method="POST">

                            <div class="step-content" data-step="1" id="step1">
                                <!-- Hidden field for student ID -->
                                <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_data['student_id'] ?? ''); ?>">
                                
                                <h2>Personal Information</h2>
                                <div class="row1">
                                    <div class="input-group1">
                                        <input type="text" placeholder="First Name" 
                                            value="<?php echo htmlspecialchars(maskString($student_data['firstname'] ?? '')); ?>" readonly>
                                        <!-- Hidden field for the unmasked first name -->
                                        <input type="hidden" name="first_name" value="<?php echo htmlspecialchars($student_data['firstname'] ?? ''); ?>">
                                    </div>
                                    <div class="input-group1">
                                        <input type="text" placeholder="Middle Name" 
                                            value="<?php echo htmlspecialchars(maskString($student_data['middlename'] ?? '')); ?>" readonly>
                                        <!-- Hidden field for the unmasked middle name -->
                                        <input type="hidden" name="middle_name" value="<?php echo htmlspecialchars($student_data['middlename'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="row1">
                                    <div class="input-group1">
                                        <input type="text" placeholder="Last Name" 
                                            value="<?php echo htmlspecialchars(maskString($student_data['lastname'] ?? '')); ?>" readonly>
                                        <!-- Hidden field for the unmasked last name -->
                                        <input type="hidden" name="last_name" value="<?php echo htmlspecialchars($student_data['lastname'] ?? ''); ?>">
                                    </div>
                                    <div class="input-group1">
                                        <input type="text" placeholder="Suffix" 
                                            value="<?php echo htmlspecialchars(maskString($student_data['suffix'] ?? '')); ?>" readonly>
                                        <!-- Hidden field for the unmasked suffix -->
                                        <input type="hidden" name="suffix" value="<?php echo htmlspecialchars($student_data['suffix'] ?? ''); ?>">
                                    </div>
                                </div>

                                <h2>Course and Section</h2>
                                <div class="row1">
                                    <div class="input-group1">
                                        <input type="text" placeholder="Course" 
                                            value="<?php echo htmlspecialchars(maskString($student_data['course'] ?? '')); ?>" readonly>
                                        <!-- Hidden field for the unmasked course -->
                                        <input type="hidden" name="course" value="<?php echo htmlspecialchars($student_data['course'] ?? ''); ?>">
                                        
                                    </div>
                                    <div class="input-group1">
                                        <input type="text" placeholder="Year and Section" 
                                            value="<?php echo htmlspecialchars(maskString($student_data['yearsection'] ?? '')); ?>" readonly>
                                        <!-- Hidden field for the unmasked school year -->
                                        <input type="hidden" name="school_year" value="<?php echo htmlspecialchars($student_data['yearsection'] ?? ''); ?>">
                                    </div>
                                </div>

                                <h3>To proceed, please enter your full last name</h3>
                                <div class="rows">
                                    <div class="input-group">
                                        <input type="text" name="verify_lastname" id="verify_lastname" placeholder='Enter Correct Last Name' onkeypress="allowOnlyLetters(event)" oninput="capitalizeFirstLetter(this)">
                                        <div id="lastname_error" class="form-error" style="display: none">
                                            Incorrect last name. Please try again.
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="nextbtn">Next <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M373.91-480 222.74-632.17q-12.67-12.68-13.06-31.45-.38-18.77 13.06-32.21 12.67-12.67 31.83-12.67 19.15 0 31.82 12.67l183.76 183.76q6.72 6.72 9.82 14.92 3.1 8.19 3.1 17.15 0 8.96-3.1 17.15-3.1 8.2-9.82 14.92L286.39-264.17q-12.67 12.67-31.44 13.05-18.78.38-32.21-13.05-12.67-12.68-12.67-31.83t12.67-31.83L373.91-480Zm267.11 0L489.61-632.17q-12.68-12.68-13.06-31.45-.38-18.77 13.06-32.21 12.67-12.67 31.82-12.67 19.16 0 31.83 12.67l183.76 183.76q6.72 6.72 9.82 14.92 3.09 8.19 3.09 17.15 0 8.96-3.09 17.15-3.1 8.2-9.82 14.92L553.26-264.17q-12.67 12.67-31.44 13.05-18.78.38-32.21-13.05-12.68-12.68-12.68-31.83t12.68-31.83L641.02-480Z"/></svg></button>

                            </div>
                            <!-- Step 2: Address Information -->
                            <div class="step-content" id="step2" data-step="2" style="display: none;">
                                <div class="tip">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#002b7f"><path d="M480-280q17 0 28.5-11.5T520-320v-160q0-17-11.5-28.5T480-520q-17 0-28.5 11.5T440-480v160q0 17 11.5 28.5T480-280Zm0-320q17 0 28.5-11.5T520-640q0-17-11.5-28.5T480-680q-17 0-28.5 11.5T440-640q0 17 11.5 28.5T480-600Zm0 520q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/></svg>  
                                    Click 'Use your location' to automatically find your address
                                </div>
                                <h2>Address Information</h2>
                                <div class="row">
                                    <div class="input-group">
                                        <input type="text" name="city" id="city" placeholder="City" onkeypress="allowOnlyLetters(event)" oninput="capitalizeFirstLetter(this)">
                                        <div id="cityError" class="form-error" style="display: none;"></div>
                                    </div>
                                    <div class="input-group">
                                        <input type="text" name="region" id="region" placeholder="Region" onkeypress="allowOnlyLetters(event)" oninput="capitalizeFirstLetter(this)">
                                        <div id="regionError" class="form-error" style="display: none;"></div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-group">
                                        <input type="number" name="postal_code" id="postal_code" placeholder="Postal Code" oninput="validateNumber(this)">
                                        <div id="postal_codeError" class="form-error" style="display: none;"></div>
                                    </div>
                                    <div class="input-group">
                                        <input type="number" name="barangay" id="baranggay" placeholder="Baranggay" oninput="validateNumber(this)">
                                        <div id="baranggayError" class="form-error" style="display: none;"></div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-group">
                                        <input type="text" name="home_address" id="home_address" placeholder="Home Address" onkeypress="allowOnlyLettersAndSpace(event)" oninput="capitalizeFirstLetter(this)">
                                        <div id="home_addressError" class="form-error" style="display: none;"></div>
                                    </div>
                                </div>
                                <div class="locationbtn">
                                    <button type="button" class="btnNavloc" onclick="getLocation()">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="22px" viewBox="0 -960 960 960" width="22px" fill="#FFFFFF"><path d="M480-192.46q120.33-110.08 178.73-198.59 58.4-88.52 58.4-161.91 0-107.31-68.67-175.74t-168.47-68.43q-99.79 0-168.46 68.43-68.66 68.43-68.66 175.74 0 73.39 58.4 161.79 58.4 88.39 178.73 198.71Zm-.06 92.16q-14.9 0-29.75-5.24-14.86-5.24-26.54-15.72-66.43-60.72-117.39-117.96-50.96-57.24-85.05-111.21-34.1-53.98-51.72-104.56-17.62-50.58-17.62-97.97 0-153.28 98.95-244.22 98.95-90.95 229.18-90.95 130.23 0 229.18 90.95 98.95 90.94 98.95 244.22 0 47.39-17.62 97.97t-51.72 104.56q-34.09 53.97-85.05 111.21-50.96 57.24-117.39 117.96-11.71 10.48-26.61 15.72-14.9 5.24-29.8 5.24ZM480-560Zm0 82.39q34.2 0 58.29-24.1 24.1-24.09 24.1-58.29t-24.1-58.29q-24.09-24.1-58.29-24.1t-58.29 24.1q-24.1 24.09-24.1 58.29t24.1 58.29q24.09 24.1 58.29 24.1Z"/></svg>
                                        Use your location
                                    </button>
                                </div>
                                <div class="button-container">
                                    <button type="button" class="previousbtn"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="m318.98-480 151.41 152.17q12.68 12.68 13.06 31.45.38 18.77-13.06 32.21-12.67 12.67-31.82 12.67-19.16 0-31.83-12.67L222.98-447.93q-6.72-6.72-9.82-14.92-3.09-8.19-3.09-17.15 0-8.96 3.09-17.15 3.1-8.2 9.82-14.92l183.76-183.76q12.67-12.67 31.44-13.05 18.78-.38 32.21 13.05 12.68 12.68 12.68 31.83t-12.68 31.83L318.98-480Zm267.11 0 151.17 152.17q12.67 12.68 13.06 31.45.38 18.77-13.06 32.21-12.67 12.67-31.83 12.67-19.15 0-31.82-12.67L489.85-447.93q-6.72-6.72-9.82-14.92-3.1-8.19-3.1-17.15 0-8.96 3.1-17.15 3.1-8.2 9.82-14.92l183.76-183.76q12.67-12.67 31.44-13.05 18.78-.38 32.21 13.05 12.67 12.68 12.67 31.83t-12.67 31.83L586.09-480Z"/></svg> Previous</button>
                                    <button type="button" class="nextbtn">Next <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M373.91-480 222.74-632.17q-12.67-12.68-13.06-31.45-.38-18.77 13.06-32.21 12.67-12.67 31.83-12.67 19.15 0 31.82 12.67l183.76 183.76q6.72 6.72 9.82 14.92 3.1 8.19 3.1 17.15 0 8.96-3.1 17.15-3.1 8.2-9.82 14.92L286.39-264.17q-12.67 12.67-31.44 13.05-18.78.38-32.21-13.05-12.67-12.68-12.67-31.83t12.67-31.83L373.91-480Zm267.11 0L489.61-632.17q-12.68-12.68-13.06-31.45-.38-18.77 13.06-32.21 12.67-12.67 31.82-12.67 19.16 0 31.83 12.67l183.76 183.76q6.72 6.72 9.82 14.92 3.09 8.19 3.09 17.15 0 8.96-3.09 17.15-3.1 8.2-9.82 14.92L553.26-264.17q-12.67 12.67-31.44 13.05-18.78.38-32.21-13.05-12.68-12.68-12.68-31.83t12.68-31.83L641.02-480Z"/></svg></button>
                                </div>
                            </div>
                            <!-- Step 3: Contact Information -->
                            <div class="step-content" id="step3" data-step="3" style="display: none;">
                                <div class="note">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ff8c00"><path d="M478-240q21 0 35.5-14.5T528-290q0-21-14.5-35.5T478-340q-21 0-35.5 14.5T428-290q0 21 14.5 35.5T478-240Zm2 160q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm4-572q25 0 43.5 16t18.5 40q0 22-13.5 39T502-525q-23 20-40.5 44T444-427q0 14 10.5 23.5T479-394q15 0 25.5-10t13.5-25q4-21 18-37.5t30-31.5q23-22 39.5-48t16.5-58q0-51-41.5-83.5T484-720q-38 0-72.5 16T359-655q-7 12-4.5 25.5T368-609q14 8 29 5t25-17q11-15 27.5-23t34.5-8Z"/></svg>
                                    Ensure your contact information is active for account verification.
                                </div>
                                <h2>Contact Information</h2>
                                <div class="row">
    <div class="input-group">
        <div class="input-group-prepend">
            <span class="input-group-text">+63</span>
            <input type="tel" name="mobile_number" id="mobile_number" placeholder="Mobile Number (09*********)" maxlength="11" onkeypress="allowOnlyNumbers(event)">
        </div>
        <div id="mobile_numberError" class="form-error" style="display: none;"></div>
    </div>
</div>
                                <div class="row">
                                    <div class="input-group">
                                        <input type="email" name="email" id="email" placeholder="Email">
                                        <div id="emailError" class="form-error" style="display: none;"></div>
                                    </div>
                                </div>
                                <div class="button-container">
                                    <button type="button" class="previousbtn"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="m318.98-480 151.41 152.17q12.68 12.68 13.06 31.45.38 18.77-13.06 32.21-12.67 12.67-31.82 12.67-19.16 0-31.83-12.67L222.98-447.93q-6.72-6.72-9.82-14.92-3.09-8.19-3.09-17.15 0-8.96 3.09-17.15 3.1-8.2 9.82-14.92l183.76-183.76q12.67-12.67 31.44-13.05 18.78-.38 32.21 13.05 12.68 12.68 12.68 31.83t-12.68 31.83L318.98-480Zm267.11 0 151.17 152.17q12.67 12.68 13.06 31.45.38 18.77-13.06 32.21-12.67 12.67-31.83 12.67-19.15 0-31.82-12.67L489.85-447.93q-6.72-6.72-9.82-14.92-3.1-8.19-3.1-17.15 0-8.96 3.1-17.15 3.1-8.2 9.82-14.92l183.76-183.76q12.67-12.67 31.44-13.05 18.78-.38 32.21 13.05 12.67 12.68 12.67 31.83t-12.67 31.83L586.09-480Z"/></svg> Previous </button>
                                    <button type="button" class="nextbtn">Next <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M373.91-480 222.74-632.17q-12.67-12.68-13.06-31.45-.38-18.77 13.06-32.21 12.67-12.67 31.83-12.67 19.15 0 31.82 12.67l183.76 183.76q6.72 6.72 9.82 14.92 3.1 8.19 3.1 17.15 0 8.96-3.1 17.15-3.1 8.2-9.82 14.92L286.39-264.17q-12.67 12.67-31.44 13.05-18.78.38-32.21-13.05-12.67-12.68-12.67-31.83t12.67-31.83L373.91-480Zm267.11 0L489.61-632.17q-12.68-12.68-13.06-31.45-.38-18.77 13.06-32.21 12.67-12.67 31.82-12.67 19.16 0 31.83 12.67l183.76 183.76q6.72 6.72 9.82 14.92 3.09 8.19 3.09 17.15 0 8.96-3.09 17.15-3.1 8.2-9.82 14.92L553.26-264.17q-12.67 12.67-31.44 13.05-18.78.38-32.21-13.05-12.68-12.68-12.68-31.83t12.68-31.83L641.02-480Z"/></svg></button>
                                </div>
                            </div>

                            <!-- Step 4: Account Creation -->
                            <div class="step-content" id="step4" data-step="4" style="display: none;">
                                <h2>Account Creation</h2>
                                <div class="row">
                                    <div class="input-group-password">
                                        <div class="password-container">
                                            <input type="password" name="password" id="password" placeholder="Password">
                                            <i class="toggle-password fas fa-eye-slash" data-target="#password"></i>
                                        </div>
                                        <div id="passwordError" class="form-error" style="display: none;"></div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-group-password">
                                        <div class="password-container">
                                            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password">
                                            <i class="toggle-password fas fa-eye-slash" data-target="#confirm_password"></i>
                                        </div>
                                        <div id="confirm_passwordError" class="form-error" style="display: none;"></div>
                                    </div>
                                </div>
                                <!-- Terms acceptance wrapper -->
                                <div class="terms-policies-fold">
                                    <div class="terms-policies-wrapper">
                                        <input type="radio" id="terms-acceptance" name="terms" class="terms-policies-radio">
                                        <label for="terms-acceptance" class="terms-policies-text">
                                            By creating an account, you agree to abide by our 
                                            <a href="#" class="terms-link">Terms and Conditions</a>
                                            
                                        </label>
                                    </div>
                                    <div id="radioError" class="form-error" style="display: none;"></div>
                                </div>

                                <div class="button-container">
                                    <button type="button" class="previousbtn"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="m318.98-480 151.41 152.17q12.68 12.68 13.06 31.45.38 18.77-13.06 32.21-12.67 12.67-31.82 12.67-19.16 0-31.83-12.67L222.98-447.93q-6.72-6.72-9.82-14.92-3.09-8.19-3.09-17.15 0-8.96 3.09-17.15 3.1-8.2 9.82-14.92l183.76-183.76q12.67-12.67 31.44-13.05 18.78-.38 32.21 13.05 12.68 12.68 12.68 31.83t-12.68 31.83L318.98-480Zm267.11 0 151.17 152.17q12.67 12.68 13.06 31.45.38 18.77-13.06 32.21-12.67 12.67-31.83 12.67-19.15 0-31.82-12.67L489.85-447.93q-6.72-6.72-9.82-14.92-3.1-8.19-3.1-17.15 0-8.96 3.1-17.15 3.1-8.2 9.82-14.92l183.76-183.76q12.67-12.67 31.44-13.05 18.78-.38 32.21 13.05 12.67 12.68 12.67 31.83t-12.67 31.83L586.09-480Z"/></svg> Previous</button>
                                    <button type="button" class="nextbtn" id="registerBtn">Register <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M236.44-152.59q-34.46 0-59.16-24.69-24.69-24.7-24.69-59.16 0-34.47 24.69-59.02Q201.98-320 236.44-320q34.47 0 59.02 24.54Q320-270.91 320-236.44q0 34.46-24.54 59.16-24.55 24.69-59.02 24.69Zm0-243.82q-34.46 0-59.16-24.55-24.69-24.54-24.69-59.01 0-34.46 24.69-59.04 24.7-24.58 59.16-24.58 34.47 0 59.02 24.55Q320-514.5 320-480.03q0 34.46-24.54 59.04-24.55 24.58-59.02 24.58Zm0-243.59q-34.46 0-59.16-24.54-24.69-24.55-24.69-59.02 0-34.46 24.69-59.16 24.7-24.69 59.16-24.69 34.47 0 59.02 24.69Q320-758.02 320-723.56q0 34.47-24.54 59.02Q270.91-640 236.44-640Zm243.59 0q-34.46 0-59.04-24.54-24.58-24.55-24.58-59.02 0-34.46 24.55-59.16 24.54-24.69 59.01-24.69 34.46 0 59.04 24.69 24.58 24.7 24.58 59.16 0 34.47-24.55 59.02Q514.5-640 480.03-640Zm243.53 0q-34.47 0-59.02-24.54Q640-689.09 640-723.56q0-34.46 24.54-59.16 24.55-24.69 59.02-24.69 34.46 0 59.16 24.69 24.69 24.7 24.69 59.16 0 34.47-24.69 59.02Q758.02-640 723.56-640ZM480.03-396.41q-34.46 0-59.04-24.55-24.58-24.54-24.58-59.01 0-34.46 24.55-59.04 24.54-24.58 59.01-24.58 34.46 0 59.04 24.55 24.58 24.54 24.58 59.01 0 34.46-24.55 59.04-24.54 24.58-59.01 24.58Zm38.54 198.32v-65.04q0-9.2 3.47-17.53 3.48-8.34 10.2-15.06l208.76-208q9.72-9.76 21.59-14.09 11.88-4.34 23.76-4.34 12.95 0 24.8 4.86 11.85 4.86 21.55 14.57l37 37q8.67 9.72 13.55 21.6 4.88 11.87 4.88 23.75 0 12.2-4.36 24.41-4.36 12.22-14.07 21.94l-208 208q-6.69 6.72-15.04 10.07-8.36 3.36-17.55 3.36h-65.04q-19.16 0-32.33-13.17-13.17-13.17-13.17-32.33Zm270.17-185.67 34.61-36.61-37-37-35.61 35.61 38 38Z"/></svg></button>
                                </div>
                            </div>

                        </form>
                </fieldset>
            </div>
        </div>
    <!-- FORM -->


    <script src="stu_registration_stepper.js"></script>
    <!-- TERMS AND CONDITIONS -->
    <div id="termsModal" class="terms-modal">
                    <div class="terms-modal-content">
                        <div class="terms-modal-width">
                            <div class="terms-header">
                                <h1>Intern<span class="highlight">flo.</span></h1>
                                <h2>University of Caloocan City</h2>

                            </div>
                            <div class="terms-body">
                                <p class="intro">Welcome to University of Caloocan City InternFlo. These Terms and Conditions ("Terms") govern your use of the Portal and the services provided through it. By accessing or using the Portal, you agree to abide by these Terms. If you do not agree, please do not use the Portal.</p>
                                <h3>Terms and Conditions</h3>
                                <div class="terms-section">
                                    <h3>Definitions</h3>
                                    <ul class="terms-list">
                                        <li><span class="strong">"Portal"</span> refers to the internship portal operated by Arcane.</li>
                                        <li><span class="strong">"User"</span> refers to any individual or entity accessing the Portal, including interns, employers, and educational institutions.</li>
                                        <li><span class="strong">"Content"</span> includes all information, materials, and data available on the Portal.</li>
                                    </ul>
                                </div>

                                <div class="terms-section">
                                    <h3>User Eligibility</h3>
                                    <p class="details">Users must meet the following criteria to use the Portal:</p>
                                    <ul class="terms-list">
                                        <li>Be a bonafide student of University of Caloocan City.</li>
                                        <li>Provide specific requirements for educational institutions.</li>
                                        <li>Provide accurate and complete information during registration.</li>
                                        <li>Comply with all applicable laws and regulations.</li>
                                    </ul>
                                </div>

                                <div class="terms-section">
                                    <h3>User Accounts</h3>
                                    <ul class="terms-list">
                                        <li>Users must create an account to access certain features of the Portal.</li>
                                        <li>Users are responsible for maintaining the confidentiality of their login credentials.</li>
                                        <li>Users must notify the Portal immediately in case of unauthorized account access.</li>
                                    </ul>
                                </div>

                                <div class="terms-section">
                                    <h3>Use of the Portal</h3>
                                    <ul class="terms-list">
                                        <li>The Portal is designed to facilitate connections between interns and employers.</li>
                                        <li>Users agree to use the Portal solely for lawful purposes.</li>
                                        <li>Users may not:
                                            <ul class="terms-sub-list">
                                                <li>Post false or misleading information.</li>
                                                <li>Share content that is offensive, defamatory, or violates any laws.</li>
                                                <li>Attempt to disrupt the Portal's functionality.</li>
                                            </ul>
                                        </li>
                                    </ul>
                                </div>

                                <div class="terms-section">
                                    <h3>Internships</h3>
                                    <ul class="terms-list">
                                        <li>The Portal acts as a medium for connecting interns and employers and does not guarantee internship placements.</li>
                                        <li>Employers are solely responsible for the internship opportunities they post.</li>
                                        <li>Interns are responsible for verifying the legitimacy of opportunities before applying.</li>
                                    </ul>
                                </div>

                                <div class="terms-section">
                                    <h3>Content Ownership and Use</h3>
                                    <ul class="terms-list">
                                        <li>Users retain ownership of the content they upload.</li>
                                        <li>By uploading content, Users grant the Portal a non-exclusive, worldwide, royalty-free license to use, reproduce, and display the content as necessary for Portal operations.</li>
                                    </ul>
                                </div>

                                <div class="terms-section">
                                    <h3>Privacy Policy</h3>
                                    <p class="details">The Portal's Privacy Policy governs the collection and use of Users' personal information. By using the Portal, you consent to the practices described in the Privacy Policy.</p>
                                </div>

                                <div class="terms-section">
                                    <h3>Limitation of Liability</h3>
                                    <ul class="terms-list">
                                        <li>The Portal is provided "as is" and "as available."</li>
                                        <li>The Portal is not responsible for any damages resulting from:
                                            <ul class="terms-sub-list">
                                                <li>Use or inability to use the Portal.</li>
                                                <li>Misconduct by other Users or third parties.</li>
                                                <li>Errors or inaccuracies in the content provided.</li>
                                            </ul>
                                        </li>
                                    </ul>
                                </div>

                                <div class="terms-section">
                                    <h3>Termination</h3>
                                    <p class="details">The Portal reserves the right to terminate or suspend access to any User for any reason, including breach of these Terms.</p>
                                </div>

                                <div class="terms-section">
                                    <h3>Changes to Terms</h3>
                                    <p class="details">The Portal may update these Terms at any time. Users will be notified of significant changes, and continued use of the Portal constitutes acceptance of the updated Terms.</p>
                                </div>

                                <button class="acceptbtn">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="22px" viewBox="0 -960 960 960" width="22px" fill="#FFFFFF"><path d="m423.28-416.37-79.78-79.78q-12.43-12.44-31.35-12.44-18.91 0-31.35 12.44-12.43 12.43-12.31 31.35.12 18.91 12.55 31.34l110.18 110.18q13.76 13.67 32.11 13.67 18.34 0 32.02-13.67L677.76-545.7q12.44-12.43 12.44-31.22 0-18.8-12.44-31.23-12.43-12.44-31.35-12.44-18.91 0-31.34 12.44L423.28-416.37ZM480-71.87q-84.91 0-159.34-32.12-74.44-32.12-129.5-87.17-55.05-55.06-87.17-129.5Q71.87-395.09 71.87-480t32.12-159.34q32.12-74.44 87.17-129.5 55.06-55.05 129.5-87.17 74.43-32.12 159.34-32.12t159.34 32.12q74.44 32.12 129.5 87.17 55.05 55.06 87.17 129.5 32.12 74.43 32.12 159.34t-32.12 159.34q-32.12 74.44-87.17 129.5-55.06 55.05-129.5 87.17Q564.91-71.87 480-71.87Z"/></svg>
                                    <span>Accept terms and conditions</span>
                                </button>
                            </div>
                        </div>
                    </div>
    </div>
    <!-- TERMS AND CONDITIONS -->

    <script>
                    const apiKey = '2bf039cb7d194b1ab3e9e5c02fe660fa'; // Replace with your OpenCage API key
                    function getLocation() {
                        // Call the geolocation API
                        if (navigator.geolocation) {
                            navigator.geolocation.getCurrentPosition(showPosition, showError);
                        } else {
                            alert("Geolocation is not supported by this browser.");
                        }
                    }

                    function showPosition(position) {
                        const latitude = position.coords.latitude;
                        const longitude = position.coords.longitude;

                        // Call the OpenCage API to get city, region, and postal code
                        fetch(`https://api.opencagedata.com/geocode/v1/json?q=${latitude}+${longitude}&key=${apiKey}`)
                            .then(response => response.json())
                            .then(data => {
                                console.log("API response:", data);

                                if (data.results.length > 0) {
                                    const location = data.results[0].components;
                                    const city = location.city || location.town || location.village || "";
                                    const region = location.state || location.province || location.region || "";
                                    const postalCode = location.postcode || "";

                                    // Set the values in the form
                                    document.getElementById('city').value = city;
                                    document.getElementById('region').value = region;
                                    document.getElementById('postal_code').value = postalCode;
                                } else {
                                    alert("Could not fetch location details.");
                                }
                            })
                            .catch(error => console.log("Error fetching the location data: ", error));
                    }

                    function showError(error) {
                        switch (error.code) {
                            case error.PERMISSION_DENIED:
                                alert("User denied the request for Geolocation.");
                                break;
                            case error.POSITION_UNAVAILABLE:
                                alert("Location information is unavailable.");
                                break;
                            case error.TIMEOUT:
                                alert("The request to get user location timed out.");
                                break;
                            case error.UNKNOWN_ERROR:
                                alert("An unknown error occurred.");
                                break;
                        }
                    }
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
                            <li><a href="../MAIN/MAIN.php#searchinternship">Internship by Company</a></li>
                            <li><a href="../MAIN/MAIN.php#searchinternship">Internship by City</a></li>
                            <li><a href="../MAIN/MAIN.php#searchinternship">Search Nearby Internship</a></li>
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
                            <li><a href="../MAIN/MAIN.php#about">About Us</a></li>
                            <li><a href="../MAIN/MAIN.php#aichat">How It Works</a></li>
                            <li><a href="../MAIN/MAIN.php#contact">Contact Us</a></li>
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
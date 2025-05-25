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

    // Handle AJAX verification request
   // Handle AJAX verification request
if (isset($_POST['check_student'])) {
    $student_id = $_POST['student_id'];
    
    // First validate the format using regex
    // First validate the format using regex
    if (!preg_match('/^20\d{6}-[NS]$/', $student_id)){
    echo json_encode(['status' => 'error', 'message' => 'Invalid format. Please use format: 20******-N/S']);
    exit;
}
    // If format is valid, continue with database checks
    // Prepare and execute the query to check for existing student
    $stmt = $conn->prepare("SELECT student_id FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Send JSON response
    if ($result->num_rows > 0) {
        echo json_encode(['status' => 'duplicate', 'message' => 'Student number already registered to an account']);
    } else {
        // Define tables to check
        $tables = ['students_ce', 'students_clas', 'students_crim', 'student_ba'];
        $found = false;
        
        // Check each table for the student ID
        foreach ($tables as $table) {
            $query = "SELECT student_id FROM $table WHERE student_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $found = true;
                break;
            }
        }
        
        if ($found) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Student ID not found in any department']);
        }
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UCC - Student Registration</title>
    <link rel="stylesheet" href="stu_registration.css">
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
                    <!-- ALERT -->
                    <div class="alert" >
                        <div class="alert__wrapper">
                        <span class="alert__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#856404"><path d="M800-514.5q-19.15 0-32.33-13.17Q754.5-540.85 754.5-560t13.17-32.33Q780.85-605.5 800-605.5t32.33 13.17Q845.5-579.15 845.5-560t-13.17 32.33Q819.15-514.5 800-514.5Zm0-131q-19.15 0-32.33-13.17Q754.5-671.85 754.5-691v-120q0-19.15 13.17-32.33Q780.85-856.5 800-856.5t32.33 13.17Q845.5-830.15 845.5-811v120q0 19.15-13.17 32.33Q819.15-645.5 800-645.5ZM360.72-484.07q-69.59 0-118.86-49.27-49.27-49.27-49.27-118.86 0-69.58 49.27-118.74 49.27-49.15 118.86-49.15 69.58 0 118.86 49.15 49.27 49.16 49.27 118.74 0 69.59-49.27 118.86-49.28 49.27-118.86 49.27ZM32.59-238.8v-29.61q0-36.16 18.69-66.57 18.7-30.41 49.85-46.37 62.72-31.24 127.67-46.98 64.96-15.74 131.92-15.74 67.43 0 132.39 15.62 64.96 15.62 127.19 46.86 31.16 15.96 49.85 46.25 18.7 30.3 18.7 66.93v29.61q0 37.78-26.61 64.39t-64.39 26.61H123.59q-37.79 0-64.39-26.61-26.61-26.61-26.61-64.39Z"/></svg>
                        </span>
                                                    
                        <p class="alert__message">
                            In case student number is not found, send a feedback
                            <a href="stu_feedback.php" class="alert-sign-in">here.</a>
                        </p>
                        </div>
                    </div>
                    <!-- ALERT -->

                    <div class="signup-header">
                        <h1>Student <span class="highlight">Sign up</span></h1>
                        <p>It's free and takes less than 60 seconds</p>                  
                    </div>


                    <form action="">
                        <h2>Student Number</h2>
                        <div class="row">
                            <div class="input-group">
                                <input type="text" name="student_number" id="student_number" 
                                    placeholder="Enter student number (20**0***-N/S)" oninput="capitalizeAll(event)">
                                <div id="student_number_error" class="form-error" style="display: none">
                                    This field is required
                                </div>
                            </div>
                        </div>
                        <button type="button" class="verifybtn">
                            Verify Student Number 
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
                                <path d="M682.63-202.87q31.61 0 52.67-19.16 21.07-19.17 21.83-55.34.76-31.61-21.06-53.05-21.83-21.45-53.44-21.45t-53.05 21.45q-21.45 21.44-21.45 53.05 0 31.61 21.45 53.05 21.44 21.45 53.05 21.45ZM834.8-63.22l-70.17-69.93q-18.24 10.52-38.74 15.9-20.5 5.38-43.26 5.38-68.39 0-116.95-48.55-48.55-48.56-48.55-116.95t48.55-116.95q48.56-48.55 116.95-48.55t116.95 48.55q48.55 48.56 48.55 116.95 0 22.28-5.02 42.07-5.02 19.78-15.31 37.54l70.66 70.65q12.91 12.91 12.91 31.95 0 19.03-12.91 31.94-12.68 12.68-31.71 12.68T834.8-63.22ZM123.59-147.8q-37.79 0-64.39-26.61-26.61-26.61-26.61-64.39v-29.61q0-36.16 18.69-66.57 18.7-30.41 49.85-46.37 62.72-31.24 127.67-46.98 64.96-15.74 131.92-15.74 16.08 0 32.17 1.15 16.09 1.14 32.18 2.9 22.63 2.76 32.32 23.89 9.7 21.13-1.5 43.52-9.76 23-14.26 46.62-4.5 23.62-4.5 48.62 0 17.72 2.62 35.08t7.62 34.83q6.72 22.63-5.36 41.14-12.08 18.52-33.47 18.52H123.59Zm648.85-500.33q0 67.67-48.2 115.87-48.2 48.19-115.87 48.19-11.24 0-28-2.61-16.76-2.62-28.72-6.1 27.48-32.48 42.1-72.44 14.62-39.95 14.62-82.91 0-42.96-14.62-82.91-14.62-39.96-42.1-72.44 14.24-5.24 28.24-6.98 14-1.74 28.48-1.74 67.67 0 115.87 48.2t48.2 115.87ZM360.72-484.07q-67.92 0-115.99-48.07-48.08-48.08-48.08-115.99t48.08-115.99q48.07-48.08 115.99-48.08 67.91 0 115.99 48.08 48.07 48.08 48.07 115.99t-48.07 115.99q-48.08 48.07-115.99 48.07Z"/>
                            </svg>
                        </button>
                    </form>
                    <div id="additionalSignInContent" class="additionalcontent" >
                        <p class="textbelow">Already have an account? <a href="../STUDENTCOORLOG/login.php" >Sign in</a></p>
                    </div>
            </fieldset>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const studentInput = document.getElementById('student_number');
        const errorDiv = document.getElementById('student_number_error');
        let hasInteracted = false;
        let typingTimer;
        const doneTypingInterval = 500; // Wait for 500ms after user stops typing
    
    function validateStudentNumber(value) {
        const regex = /^20\d{6}-[NS]$/;
        return regex.test(value);
    }

    function showError(message) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        studentInput.classList.add('error');
        studentInput.classList.remove('valid');
    }

    function clearError() {
        errorDiv.style.display = 'none';
        studentInput.classList.remove('error');
        studentInput.classList.remove('valid');
    }

    function showValid() {
        clearError();
        studentInput.classList.add('valid');
    }

    function checkStudentNumber() {
        if (!validateStudentNumber(studentInput.value)) {
            return;
        }

        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'check_student=1&student_id=' + encodeURIComponent(studentInput.value)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'duplicate') {
                showError(data.message);
            } else if (data.status === 'error') {
                showError(data.message);
            } else {
                showValid();
            }
        })
        .catch(error => {
            showError('An error occurred while checking the student ID');
        });
    }

    function capitalizeAll(event) {
        const inputField = event.target;
        inputField.value = inputField.value.toUpperCase();
    }

    studentInput.addEventListener('input', function(event) {
        capitalizeAll(event);
        hasInteracted = true;
        clearTimeout(typingTimer);

        if (this.value === '') {
            showError('This field is required');
        } else if (!validateStudentNumber(this.value)) {
            showError('Invalid format. Please use format: 20******-N/S');
        } else {
            // Start timer to check student number after user stops typing
            typingTimer = setTimeout(checkStudentNumber, doneTypingInterval);
        }
    });

    studentInput.addEventListener('blur', function() {
        if (this.value === '') {
            if (hasInteracted) {
                showError('This field is required');
            }
        } else if (!validateStudentNumber(this.value)) {
            showError('Invalid format. Please use format: 20******-N/S/C');
        } else {
            // Remove the valid class and just keep default styling
            studentInput.classList.remove('valid');
            // Hide any error messages
            errorDiv.style.display = 'none';
        }
    });

    document.querySelector('.verifybtn').addEventListener('click', function() {
        hasInteracted = true;

        if (studentInput.value === '') {
            showError('This field is required');
            return;
        } 
        
        if (!validateStudentNumber(studentInput.value)) {
            showError('Invalid format. Please use format: 20******-N/S/C');
            return;
        }

        // Send AJAX request to verify student ID
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'check_student=1&student_id=' + encodeURIComponent(studentInput.value)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'duplicate') {
                showError(data.message);
            } else if (data.status === 'success') {
                window.location.href = 'stu_registration_stepper.php?student_id=' + encodeURIComponent(studentInput.value);
            } else {
                showError(data.message);
            }
        })
        .catch(error => {
            showError('An error occurred while verifying the student ID');
        });
    });
});
    </script>
    <!-- FORM -->


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
    

    <script src="registration.js"></script>
</body>
</html>



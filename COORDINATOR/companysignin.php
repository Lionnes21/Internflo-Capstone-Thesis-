<?php
    // Include the database configuration file
    include 'config.php';

    session_start();

    // Variable to store login error message
    $loginError = "";

    // Check if form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Modified function to check login from m_advisors table
        function checkLogin($conn, $email, $password) {
            $stmt = $conn->prepare("SELECT advisor_id, email, password, first_name, last_name FROM m_advisors WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    return $user;
                }
            }
            return false;
        }

        // Check login against m_advisors table
        $user = checkLogin($conn, $email, $password);

        // If a user was found
        if ($user) {
            $_SESSION['advisor_id'] = $user['advisor_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['source_table'] = 'm_advisors';

            header("Location: ../monitoring/ADVISER/InsDashboard.php");
            exit();
        } else {
            $loginError = "Invalid email or password";
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practicum Coordinator Log In</title>
    <link rel="stylesheet" href="companysignin.css">
    <link rel="stylesheet" href="NAV.css">
    <link rel="icon" href="pics/ucc.png">
    <link rel="stylesheet" href="FOOTER.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
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
                <a href="../MAIN/MAIN.php">HOME</a>
                <a href="../MAIN/MAIN.php#about">ABOUT US</a>
                <a href="../MAIN/MAIN.php#contact">CONTACT US</a>
                <a href="../STUDENTCOORLOG/login.php" class="login-btn">LOGIN</a>
                <a href="../RECRUITER/companymainpage.html" class="employer-btn">EMPLOYER SITE</a>
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
        <div class="page-wrapper">
            <div class="container">
                <div class="image-section">

                </div>
                <div class="form-section">
                    <div class="form-container">
                        <h1>Practicum Coordinator</h1>
                        <p>Please enter your credentials to log in</p>
                        
                        <form action="" method="POST">
    <div class="password-container">
        <div class="input-norm">
            <input type="email" name="email" placeholder="Email">
            <i class="toggle-email fas fa-envelope"></i>
        </div>
    </div>
    
    <div class="password-container">
        <div class="input-norm">
            <input type="password" name="password" placeholder="Password">
            <i class="toggle-password fas fa-eye-slash"></i>
        </div>
    </div>
    
    <div class="remember-forgot">
        <label class="remember-me custom-checkbox">
            <input type="checkbox" name="remember">
            <span class="checkmark"></span>
            Remember Me
        </label>
        <a href="#" class="forgot-password">Forgot Password?</a>
    </div>
    
    <div id="login-error-container" class="login-error-container <?php echo !empty($loginError) ? 'show' : ''; ?>">
        <?php echo $loginError; ?>
    </div>
    
    <button type="submit">Login</button>
</form>
                </div>
            </div>




        </div>
    </div>

    <script>
        // Toggle password visibility
        document.querySelector('.toggle-password').addEventListener('click', function() {
            const passwordInput = this.previousElementSibling;
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
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
                        <li><a href="#">Internship by Company</a></li>
                        <li><a href="#">Internship by City</a></li>
                        <li><a href="#">Search Nearby Internship</a></li>
                    </ul>
                </div>
            
                <!-- Employers Section -->
                <div class="centerside">
                    <h4>EMPLOYERS</h4>
                    <ul>
                        <li><a href="#">Post Internships</a></li>
                    </ul>
                </div>
            
                <!-- About Interflo Section -->
                <div class="centerside">
                    <h4>ABOUT INTERNFLO</h4>
                    <ul>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">How It Works</a></li>
                        <li><a href="#">Contact Us</a></li>
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

        <script src="companysignin.js"></script>
</body>
</html>

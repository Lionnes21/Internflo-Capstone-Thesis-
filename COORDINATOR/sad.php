<?php
    // Include the database configuration file
    include 'config.php';

    session_start();

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
            $_SESSION['advisor_id'] = $user['advisor_id'];  // Changed from user_id to advisor_id
            $_SESSION['email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['source_table'] = 'm_advisors';  // Store table name for reference

            header("Location: ../monitoring/adviser/insdashboard.php");
            exit();
        } else {
            echo "<script>alert('Invalid email or password');</script>";
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recruiter Log In</title>
    <link rel="stylesheet" href="companysignin.css">
    <link rel="stylesheet" href="NAV.css">
    <link rel="icon" href="pics/ucclogo2.png">
    <link rel="stylesheet" href="FOOTER.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Font Awesome -->
</head>
<body>
    <!-- NAVIGATION -->
    <div class="navbar">
        <div class="logo-container1">
            <button class="menu-toggle" aria-label="Toggle Navigation">☰</button>
            <img src="pics/ucc-logo.png" alt="Logo" class="logo-img">
        </div>
        <div class="nav-links">
            <a href="../MAIN/MAIN.php">HOME</a>
            <a href="../MAIN/MAIN.php#about">ABOUT US</a>
            <a href="../MAIN/MAIN.php#contact">CONTACT US</a>
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
                <p>Copyright <strong>University of Caloocan City</strong> Internflo©2024. All Rights Reserved</p>
            </div>
        </div>
    </div>
    <!-- FOOTER -->

        <script src="companysignin.js"></script>
</body>
</html>

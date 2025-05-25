<?php
include 'config.php';
require_once 'vendor/autoload.php';

session_start();

// Initialize error message variables
$formError = '';

// Handle cookie-based login
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    $sql = 'SELECT * FROM students WHERE remember_token = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        header('Location: ../STUDENTLOGIN/studentfrontpage.php');
        exit;
    }
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailOrStudentNumber = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($emailOrStudentNumber) || empty($password)) {
        $formError = 'Please fill in all required fields';
    } else {
        $sql = 'SELECT * FROM students WHERE email = ? OR student_id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $emailOrStudentNumber, $emailOrStudentNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];

            // Handle "Remember Me" functionality
            if (isset($_POST['remember_me']) && $_POST['remember_me'] == 'on') {
                // Generate a secure random token
                $token = bin2hex(random_bytes(32));
                
                // Store token in database
                $updateStmt = $conn->prepare('UPDATE students SET remember_token = ? WHERE id = ?');
                $updateStmt->bind_param('si', $token, $user['id']);
                $updateStmt->execute();
                
                // Set cookies (30 days expiration)
                setcookie('remember_me', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
                // Store email in a cookie (base64 encoded for basic obscurity)
                $encryptedEmail = base64_encode($emailOrStudentNumber);
                setcookie('remember_email', $encryptedEmail, time() + (30 * 24 * 60 * 60), '/', '', true, true);
            }

            // Update login method
            $updateStmt = $conn->prepare('UPDATE students SET login_method = ? WHERE id = ?');
            $loginMethod = 'email';
            $updateStmt->bind_param('si', $loginMethod, $user['id']);
            $updateStmt->execute();

            header('Location: ../STUDENTLOGIN/studentfrontpage.php');
            exit;
        } else {
            $formError = 'Invalid email/student number or password';
        }
    }
}

// Google Client Configuration
$client = new Google_Client();
$client->setClientId('41598131166-2ra5ia34n04fk054m433hfchim94tej0.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('https://internflo-ucc.com/STUDENTCOORLOG/callback.php');
$client->addScope('email');
$client->addScope('profile');

$googleLoginUrl = $client->createAuthUrl();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UCC - Student Login</title>
    <link rel="stylesheet" href="login.css">
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
                <a href="login.php" class="login-btn">LOGIN</a>
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


    <!-- LOGIN FORM -->
    <div class="container90">
                <div class="container">
                    <!-- Sign Up Section -->
                    <!-- Left side with image -->
                    <div class="image-container">
                        <img src="pics/ucc1.jpg" alt="Image Description">
                    </div>

                    <fieldset>
                        <div class="signup-header">
                            <h1>Student <span class="highlight">Login </span></h1>
                            <p>Please enter your credentials to log in</p>
                        </div>
<form name="loginForm" action="login.php" method="POST">
        <!-- Email input -->
        <div class="row">
            <div class="input-group">
                <div class="password-container">
                    <input type="text" 
                           name="email" 
                           id="email" 
                           placeholder="Email or Student number (20**0***-N/S)"
                           value="<?php echo isset($_COOKIE['remember_email']) ? htmlspecialchars(base64_decode($_COOKIE['remember_email'])) : ''; ?>">
                    <i class="toggle-email fas fa-envelope"></i>
                </div>
                <div id="emailError" class="form-error" style="display: none; color: red;"></div>
            </div>
        </div>
        
        <!-- Password input with show password icon -->
        <div class="row">
            <div class="input-group">
                <div class="password-container">
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="Password">
                    <i class="toggle-password fas fa-eye-slash"></i>
                </div>
                <div id="passwordError" class="form-error" style="display: none; color: red;"></div>
            </div>
        </div>
        
        <!-- Remember Me and Forgot Password in one line -->
        <div class="row">
            <label style="color: #2E3849;" class="custom-checkbox">
                <input type="checkbox" name="remember_me"> 
                <span class="checkmark"></span> Remember me
            </label>
            <a href="forgotpassword.php" style="margin-left: auto;">Forgot Password?</a>
        </div>

        <div id="formError" class="form-error" style="display: <?php echo $formError ? 'block' : 'none'; ?>; color: red; font-size: 15px;">
            <?php echo htmlspecialchars($formError, ENT_QUOTES); ?>
        </div>
        
        <button class="btnSend">Login <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M200-160q-33 0-56.5-23.5T120-240v-32q0-34 17.5-62.5T184-378q62-31 126-46.5T440-440q14 0 28 .5t28 2.5q11 1 17.5 8.5T520-410q2 47 23 88.5t56 70.5q7 5 11 12.5t4 16.5v22q0 17-11.5 28.5T574-160H200Zm240-320q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47Zm300 80q17 0 28.5-11.5T780-440q0-17-11.5-28.5T740-480q-17 0-28.5 11.5T700-440q0 17 11.5 28.5T740-400Zm6 346-40-40q-2-2-6-14v-178q-44-13-72-49.5T600-420q0-58 41-99t99-41q58 0 99 41t41 99q0 45-25.5 80T790-290l36 36q6 6 6 14t-6 14l-32 32q-6 6-6 14t6 14l32 32q6 6 6 14t-6 14l-52 52q-6 6-14 6t-14-6Z"/></svg></button>
    </form>
                    

                        <!-- Divider with "or" -->
                        <div class="divider">
                            <span>Or</span>
                        </div>

                        <!-- Google Login Button -->
                        <div class="center-container">
                            <div class="gborder">
                                <div class="google-login">
                                    <a href="<?php echo htmlspecialchars($googleLoginUrl); ?>">
                                        <img src="pics/google.png" alt="Google Sign In">
                                        <span>Continue with Google</span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div id="additionalSignInContent" class="additionalcontent" >
                            <p class="textbelow" >Don't have an account? <a href="../STUDENT/stu_registration.php" >Register here</a></p>
                        </div>



                    </fieldset>   
                    
                </div>

    </div>
    <!-- LOGIN FORM -->

 
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
    

    <script src="logins.js"></script>
</body>
</html>


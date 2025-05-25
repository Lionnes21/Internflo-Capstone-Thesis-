<?php
    // Include the database configuration file
    include 'config.php';

    session_start();
    $formError = ''; // Initialize form error variable

    // Check if form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Modified function to check login and return more user data
        function checkLogin($conn, $table, $email, $password) {
            $stmt = $conn->prepare("SELECT id, email, password, first_name, last_name FROM $table WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    // Add table name to user data to track which table the user is from
                    $user['source_table'] = $table;
                    return $user;
                }
            }
            return false;
        }

        // Try recruiters table first
        $user = checkLogin($conn, 'recruiters', $email, $password);

        // If not found, try approvedrecruiters table
        if (!$user) {
            $user = checkLogin($conn, 'approvedrecruiters', $email, $password);
        }

        // If a user was found in either table
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['source_table'] = $user['source_table'];  // Store which table the user is from

            header("Location: companyloginpage.php");
            exit();
        } else {
            // Set error message instead of showing alert
            $formError = 'Invalid email or password. Please try again.';
        }
    }
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UCC - Company Login</title>
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
                <a href="companymainpage.html">HOME</a>
                <a href="companymainpage.html#about">ABOUT US</a>
                <a href="companymainpage.html#contact">CONTACT US</a>
                <a href="companysignin.php" class="login-btn">LOGIN</a>     
                <a href="../MAIN/MAIN.php" class="employer-btn">APPLICANT SITE</a>
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
                    <h1>Company <span class="highlight">Login </span></h1>
                    <p>Please enter your credentials to log in</p>
                    
                    <form action="" method="POST">
                        <div class="password-container">
                            <div class="input-norm">
                                <input type="email" name="email" placeholder="Email">
                                <i class="toggle-email fas fa-envelope"></i>
                            </div>
                            <div id="emailError" class="form-error" style="display: none; color: red;"></div>
                        </div>
                        
                        <!-- Password container -->
                        <div class="password-container">
                            <div class="input-norm">
                                <input type="password" name="password" placeholder="Password">
                                <i class="toggle-password fas fa-eye-slash"></i>
                            </div>
                            <div id="passwordError" class="form-error" style="display: none; color: red;"></div>
                        </div>
                        
                        <div class="remember-forgot">
                            <label class="remember-me custom-checkbox">
                                <input type="checkbox" name="remember">
                                <span class="checkmark"></span>
                                Remember Me
                            </label>
                            <a href="forgotpassword.php" class="forgot-password">Forgot Password?</a>
                        </div>
                        
                        <!-- Form error message -->
                        <div id="formError" class="form-error" style="display: <?php echo !empty($formError) ? 'block' : 'none'; ?>; color: red; font-size: 15px;">
                            <?php echo htmlspecialchars($formError, ENT_QUOTES); ?>
                        </div>
                        
                        <button class="lgn" type="submit">Login <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M200-160q-33 0-56.5-23.5T120-240v-32q0-34 17.5-62.5T184-378q62-31 126-46.5T440-440q14 0 28 .5t28 2.5q11 1 17.5 8.5T520-410q2 47 23 88.5t56 70.5q7 5 11 12.5t4 16.5v22q0 17-11.5 28.5T574-160H200Zm240-320q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47Zm300 80q17 0 28.5-11.5T780-440q0-17-11.5-28.5T740-480q-17 0-28.5 11.5T700-440q0 17 11.5 28.5T740-400Zm6 346-40-40q-2-2-6-14v-178q-44-13-72-49.5T600-420q0-58 41-99t99-41q58 0 99 41t41 99q0 45-25.5 80T790-290l36 36q6 6 6 14t-6 14l-32 32q-6 6-6 14t6 14l32 32q6 6 6 14t-6 14l-52 52q-6 6-14 6t-14-6Z"/></svg></button>
                    </form>
                    
                    <div class="register">
                        Don't have an account? <a href="companycreate.php">Register here</a>
                    </div>
                </div>
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
                            <li><a href="../MAIN/MAIN.php#searchinternship">Internship by Company</a></li>
                            <li><a href="../MAIN/MAIN.php#searchinternship">Internship by City</a></li>
                            <li><a href="../MAIN/MAIN.php#searchinternship">Search Nearby Internship</a></li>
                        </ul>
                    </div>
                
                    <!-- Employers Section -->
                    <div class="centerside">
                        <h4>EMPLOYERS</h4>
                        <ul>
                            <li><a href="companymainpage.html">Post Internships</a></li>
                        </ul>
                    </div>
                
                    <!-- About Interflo Section -->
                    <div class="centerside">
                        <h4>ABOUT INTERNFLO</h4>
                        <ul>
                            <li><a href="companymainpage.html#about">About Us</a></li>
                            <li><a href="companymainpage.html#chatbot">How It Works</a></li>
                            <li><a href="companymainpage.html#contact">Contact Us</a></li>
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

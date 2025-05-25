<?php
session_start();
require_once 'config.php'; // Include the configuration file

$formError = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {


    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password']; // Password input from the form

    $sql = "SELECT advisor_id, email, password FROM ojt_advisors WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        
        // Direct comparison of passwords
        if ($password === $row['password']) {
            $_SESSION['advisor_id'] = $row['advisor_id'];
            $_SESSION['email'] = $row['email'];
            
            if (isset($_POST['remember_me'])) {
                // Set a cookie that expires in 30 days
                setcookie('remember_user', $row['advisor_id'], time() + (86400 * 30), "/");
            }

            header("Location: ../STUDENTLOGIN/ADVISER/InsDashboard.html");
            exit();
        } else {
            $formError = "Invalid email or password";
        }
    } else {
        $formError = "Invalid email or password";
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UCC - Practicum Coordinator Login</title>
    <link rel="icon" href="pics/ucclogo2.png">
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="../css/NAV.css">
    <link rel="stylesheet" href="../css/FOOTER.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Font Awesome -->
</head>
<body>
    <div class="navbar">
        <div class="logo">
            <img src="pics/logo1.png" alt="Logo">
            <button class="menu-toggle" aria-label="Toggle Navigation">☰</button>
        </div>
        <div class="nav-links">
            <a href="../MAIN/MAIN.html">Home</a>
            <a href="#">About us</a>
            <a href="#">Contact us</a>
        </div>
        <div class="auth-buttons">
            <button class="login" onclick="window.location.href='../STUDENTCOORLOG/login.html';" >Login</button>
            <button class="company">Company</button>
        </div>
    </div>

    <div id="myModal-SignUp" class="modal-custom">
        <div class="modal-dialog-custom">
            <div class="modal-content-custom">
                <!-- Modal Header -->
                <div class="modal-header-custom">
                    <img src="pics/account.png" alt="Role Icon">
                    <h5 class="modal-title-custom">Create an Account As</h5>
                    <span class="close close-myModal-SignUp">&times;</span> <!-- Unique class -->
                </div>
                <!-- Modal Body -->
                <div class="modal-body-custom">
                    <button class="role-btn-custom" onclick="window.location.href='../STUDENT/registration.php';">
                        Student
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-arrow-right-custom">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M5 12l14 0" />
                            <path d="M13 18l6 -6" />
                            <path d="M13 6l6 6" />
                        </svg>
                    </button>                
                    <button class="role-btn-custom" onclick="window.location.href='../COORDINATOR/reg-coor.html';">
                        Coordinator
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-arrow-right-custom">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M5 12l14 0" />
                            <path d="M13 18l6 -6" />
                            <path d="M13 6l6 6" />
                        </svg>
                    </button>
                </div>
                <!-- Modal Footer -->
                <div class="modal-footer-custom">
                    <p>Already have an Account? <a href="../STUDENTCOORLOG/login.html">Click here</a></p>
                </div>
            </div>
        </div>
    </div>


    <div class="container90">
        <div class="container">
            <!-- Left side with image -->
            <div class="image-container">
                <img src="pics/login.png" alt="Image Description" style="max-width: 100%; height: auto;">
            </div>
            <fieldset>
                <div class="signup-header">
                    <h1>OJT Advisor Login</h1>
                    <p>Please enter your credentials to log in</p>
                </div>
                <form name="loginForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                    <!-- Email input -->
                    <div class="row">
                        <div class="input-group">
                            <input type="text" name="email" id="email" placeholder="Email" required>
                        </div>
                    </div>
                    
                    <!-- Password input with show password icon -->
                    <div class="row">
                        <div class="input-group">
                            <div class="password-container">
                                <input type="password" id="password" name="password" placeholder="Password" required>
                                <i class="toggle-password fas fa-eye-slash"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Remember Me and Forgot Password in one line -->
                    <div class="row">
                        <label>
                            <input type="checkbox" name="remember_me"> Remember Me
                        </label>
                        <a href="forgotpassword.php" style="margin-left: auto;">Forgot Password?</a>
                    </div>
            
                    <?php if ($formError): ?>
                        <div class="form-error" style="color: red;">
                            <?php echo htmlspecialchars($formError); ?>
                        </div>
                    <?php endif; ?>
            
                    <button type="submit" class="btnSend">Login</button>
                </form>
            </fieldset>   
        </div>
    </div>
    
 
    
    

    

    <div class="footbg">
        <div class="properdiv">
            <footer>
    
                <!-- Logo Section -->
                <div class="rightside">
                    <img src="pics/logo2.png" alt="Company Logo">
                    <p>
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="currentColor"  class="icon icon-tabler icons-tabler-filled icon-tabler-map-pin"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18.364 4.636a9 9 0 0 1 .203 12.519l-.203 .21l-4.243 4.242a3 3 0 0 1 -4.097 .135l-.144 -.135l-4.244 -4.243a9 9 0 0 1 12.728 -12.728zm-6.364 3.364a3 3 0 1 0 0 6a3 3 0 0 0 0 -6z" /></svg>
                        Biglang Awa Street Cor 11th Ave Catleya,<br> Caloocan 1400 Metro Manila, Philippines
                    </p>
                    <p>
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="currentColor"  class="icon icon-tabler icons-tabler-filled icon-tabler-phone"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 3a1 1 0 0 1 .877 .519l.051 .11l2 5a1 1 0 0 1 -.313 1.16l-.1 .068l-1.674 1.004l.063 .103a10 10 0 0 0 3.132 3.132l.102 .062l1.005 -1.672a1 1 0 0 1 1.113 -.453l.115 .039l5 2a1 1 0 0 1 .622 .807l.007 .121v4c0 1.657 -1.343 3 -3.06 2.998c-8.579 -.521 -15.418 -7.36 -15.94 -15.998a3 3 0 0 1 2.824 -2.995l.176 -.005h4z" /></svg>
                        Phone: (02) 5310 6855
                    </p>
                    <p>
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="currentColor"  class="icon icon-tabler icons-tabler-filled icon-tabler-mail"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M22 7.535v9.465a3 3 0 0 1 -2.824 2.995l-.176 .005h-14a3 3 0 0 1 -2.995 -2.824l-.005 -.176v-9.465l9.445 6.297l.116 .066a1 1 0 0 0 .878 0l.116 -.066l9.445 -6.297z" /><path d="M19 4c1.08 0 2.027 .57 2.555 1.427l-9.555 6.37l-9.555 -6.37a2.999 2.999 0 0 1 2.354 -1.42l.201 -.007h14z" /></svg>
                        Email: support@uccinternshipportal.ph
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
                <p>Copyright Internflo©2024. All Rights Reserved</p>
            </div>
        </div>
    </div>

    <script src="login.js"></script>
</body>
</html>


<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);

// Initialize variables
$initials = '';
$fullName = '';    // Store the full name
$email = '';       // Store the email
$fullName2 = '';   // Store the full name without middle name
$course = '';      // Store the course
$schoolYear = '';  // Store the school year
$studentID = '';   // Store the student
$city = '';        // Store the city
$region = '';      // Store the region
$postalCode = '';  // Store the postal code
$barangay = '';    // Store the barangay
$homeAddress = ''; // Store the home address
$profile_pic = 'pics/default_profile.jpg'; // Default profile picture

// If logged in, fetch user details from the database
if ($isLoggedIn) {
    // Include database connection
    include 'config.php';

    // Get the user ID from the session
    $userId = $_SESSION['user_id'];

    // Prepare the SQL query to retrieve user information including profile picture
    $sql = 'SELECT first_name, middle_name, last_name, suffix, email, course, school_year, city, region, postal_code, barangay, home_address, student_id, name, profile_picture FROM students WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $userId);  // Bind the user ID to the SQL query
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // If user information is found, extract details
    if ($user) {
        // Check if first and last names are available
        if (!empty($user['first_name']) && !empty($user['last_name'])) {
            $firstNameInitial = strtoupper(substr($user['first_name'], 0, 1));
            $lastNameInitial = strtoupper(substr($user['last_name'], 0, 1));
            $initials = $firstNameInitial . $lastNameInitial;

            // Store full name (with middle name and suffix if available)
            $fullName = $user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name'] . ' ' . $user['suffix'];

            // Store full name (without middle name)
            $fullName2 = $user['first_name'] . ' ' . $user['last_name'];
        } else {
            // Use 'name' column if first_name or last_name is missing
            $nameParts = explode(' ', $user['name']);
            $firstNameInitial = isset($nameParts[0][0]) ? strtoupper($nameParts[0][0]) : '';
            $secondNameInitial = isset($nameParts[1][0]) ? strtoupper($nameParts[1][0]) : '';
            $initials = $firstNameInitial . $secondNameInitial;

            // Store the full name from 'name' column
            $fullName = $user['name'];
            $fullName2 = $user['name'];  // Set the same for fullName2
        }

        // Store email, course, school year
        $email = $user['email'];
        $course = $user['course'];
        $schoolYear = $user['school_year'];
        $studentID = $user['student_id'];  // Store student ID for reference

        // Store address information
        $city = $user['city'];
        $region = $user['region'];
        $postalCode = $user['postal_code'];
        $barangay = $user['barangay'];
        $homeAddress = $user['home_address'];

        // Store profile picture
        $profile_pic = $user['profile_picture'] ?? 'pics/default_profile.png'; // Fallback to default if null
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student</title>
    <link rel="stylesheet" href="studentprofile.css">
    <link rel="stylesheet" href="NAVx.css">
    <link rel="stylesheet" href="../css/FOOTER.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Font Awesome -->
</head>
<body>
    <div class="navbar">
    <div class="logo">
            <img src="pics/ucclogonav-t.png" alt="Logo">
            <button class="menu-toggle" aria-label="Toggle Navigation">☰</button>
        </div>
        <div class="nav-links">
            <a href="studentmain.php">HOME</a>
            <a href="#">ABOUT US</a>
            <a href="#">CONTACT US</a>
        </div>
        <div class="auth-buttons">

            <?php if ($isLoggedIn): ?>
                <div class="dropdown-container">
                    <div class="border">
                    <svg xmlns="http://www.w3.org/2000/svg" height="28px" viewBox="0 -960 960 960" width="28px" fill="#464349"><path d="M702-494.23 574.23-622 617-663.77l85 85 170-170L914.15-706 702-494.23Zm-342 1.92q-57.75 0-98.87-41.12Q220-574.56 220-632.31q0-57.75 41.13-98.87 41.12-41.13 98.87-41.13 57.75 0 98.87 41.13Q500-690.06 500-632.31q0 57.75-41.13 98.88-41.12 41.12-98.87 41.12ZM60-187.69v-88.93q0-29.38 15.96-54.42 15.96-25.04 42.66-38.5 59.3-29.07 119.65-43.61 60.35-14.54 121.73-14.54t121.73 14.54q60.35 14.54 119.65 43.61 26.7 13.46 42.66 38.5Q660-306 660-276.62v88.93H60Zm60-60h480v-28.93q0-12.15-7.04-22.5-7.04-10.34-19.11-16.88-51.7-25.46-105.42-38.58Q414.7-367.69 360-367.69q-54.7 0-108.43 13.11-53.72 13.12-105.42 38.58-12.07 6.54-19.11 16.88-7.04 10.35-7.04 22.5v28.93Zm240-304.62q33 0 56.5-23.5t23.5-56.5q0-33-23.5-56.5t-56.5-23.5q-33 0-56.5 23.5t-23.5 56.5q0 33 23.5 56.5t56.5 23.5Zm0 244.62Zm0-324.62Z"/></svg>                    <span class="greeting-text">Student</span>
                        <div class="dropdown-btn" onclick="toggleDropdown()">
            <img src="<?php echo htmlspecialchars($profile_pic, ENT_QUOTES); ?>" alt="Profile Picture" onerror="this.onerror=null;this.src='pics/default_profile.jpg';" />
                        </div>
                    </div>
                    <div id="dropdown-content" class="dropdown-content">
                        <div class="user-fullname"><?php echo $fullName2; ?></div> <!-- Display the full name here -->
                        <hr style="width: 80%; margin: 0 auto">
                        <a href="profile.php">Profile</a>
                        <a href="settings.php">Settings</a>
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
        function toggleDropdown() {
            var dropdownContent = document.getElementById("dropdown-content");
            if (dropdownContent.style.display === "none" || dropdownContent.style.display === "") {
                dropdownContent.style.display = "block";
            } else {
                dropdownContent.style.display = "none";
            }
        }
    </script>

    <div class="whitespace">
        
        <div class="grid-container">
        <div class="user-profile">
        <div class="initials-circle">
            <img src="<?php echo htmlspecialchars($profile_pic, ENT_QUOTES); ?>" alt="Profile Picture" onerror="this.onerror=null;this.src='pics/default_profile.jpg';" />
        </div>
                    <div class="user-text">
                        User Profile
                    </div>
                    <div class="maxwidth">
                    <div class="user-links">
                        <div class="link-item">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed"><path d="M480-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM160-160v-112q0-34 17.5-62.5T224-378q62-31 126-46.5T480-440q66 0 130 15.5T736-378q29 15 46.5 43.5T800-272v112H160Z"/></svg>
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 2a5 5 0 1 1 -5 5l.005 -.217a5 5 0 0 1 4.995 -4.783z"/>
                                    <path d="M14 14a5 5 0 0 1 5 5v1a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-1a5 5 0 0 1 5 -5h4z"/>
                                </svg>
                                <a href="studentprofile.php">Personal Information</a>
                            </div>
                            
                            <div class="link-item active">
                            <svg xmlns="http://www.w3.org/2000/svg" height="22px" viewBox="0 -960 960 960" width="22px" fill="#e8eaed"><path d="M40-160v-112q0-34 17.5-62.5T104-378q62-31 126-46.5T360-440q66 0 130 15.5T616-378q29 15 46.5 43.5T680-272v112H40Zm720 0v-120q0-44-24.5-84.5T666-434q51 6 96 20.5t84 35.5q36 20 55 44.5t19 53.5v120H760ZM360-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47Zm400-160q0 66-47 113t-113 47q-11 0-28-2.5t-28-5.5q27-32 41.5-71t14.5-81q0-42-14.5-81T544-792q14-5 28-6.5t28-1.5q66 0 113 47t47 113Z"/></svg>
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 2a5 5 0 1 1 -5 5l.005 -.217a5 5 0 0 1 4.995 -4.783z"/>
                                    <path d="M14 14a5 5 0 0 1 5 5v1a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-1a5 5 0 0 1 5 -5h4z"/>
                                </svg>
                                <a href="internship.php">Internship</a>
                            </div>
                            <div class="link-item">
                            <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#e8eaed"><path d="M240-80q-33 0-56.5-23.5T160-160v-400q0-33 23.5-56.5T240-640h40v-80q0-83 58.5-141.5T480-920q83 0 141.5 58.5T680-720v80h40q33 0 56.5 23.5T800-560v400q0 33-23.5 56.5T720-80H240Zm240-200q33 0 56.5-23.5T560-360q0-33-23.5-56.5T480-440q-33 0-56.5 23.5T400-360q0 33 23.5 56.5T480-280ZM360-640h240v-80q0-50-35-85t-85-35q-50 0-85 35t-35 85v80Z"/></svg>
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 2a5 5 0 1 1 -5 5l.005 -.217a5 5 0 0 1 4.995 -4.783z"/>
                                    <path d="M14 14a5 5 0 0 1 5 5v1a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-1a5 5 0 0 1 5 -5h4z"/>
                                </svg>
                                <a href="manage_password.php">Manage Password</a>
                            </div>
                            <div class="link-item">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed"><path d="m640-120-12-60q-12-5-22.5-10.5T584-204l-58 18-40-68 46-40q-2-14-2-26t2-26l-46-40 40-68 58 18q11-8 21.5-13.5T628-460l12-60h80l12 60q12 5 22.5 11t21.5 15l58-20 40 70-46 40q2 12 2 25t-2 25l46 40-40 68-58-18q-11 8-21.5 13.5T732-180l-12 60h-80ZM80-160v-112q0-33 17-62t47-44q51-26 115-44t141-18h14q6 0 12 2-29 72-24 143t48 135H80Zm600-80q33 0 56.5-23.5T760-320q0-33-23.5-56.5T680-400q-33 0-56.5 23.5T600-320q0 33 23.5 56.5T680-240ZM400-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47Z"/></svg>
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 2a5 5 0 1 1 -5 5l.005 -.217a5 5 0 0 1 4.995 -4.783z"/>
                                    <path d="M14 14a5 5 0 0 1 5 5v1a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-1a5 5 0 0 1 5 -5h4z"/>
                                </svg>
                                <a href="account_settings.php">Account Settings</a>
                            </div>
                        </div>
                    </div>
                </div>


    </div>








</body>
</html>

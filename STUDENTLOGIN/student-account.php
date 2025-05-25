<?php 
    session_start();

    $isLoggedIn = isset($_SESSION['user_id']);

    // Initialize variables
    $initials = '';
    $fullName = '';
    $email = '';
    $fullName2 = '';
    $profile_pic = 'pics/default_profile.jpg';
    $course = '';

    // Database connection
    $conn = new mysqli("localhost", "u798912504_root", "Internfloucc2025*", "u798912504_internflo");

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Function to convert to proper case
    function toProperCase($str) {
        return ucfirst(strtolower($str));
    }

    // If logged in, fetch user details from the database
    if ($isLoggedIn) {
        $userId = $_SESSION['user_id'];
        // Add password_length column to the query
        $sql = 'SELECT first_name, last_name, profile_pic, course, email, mobile_number, student_id, password, LENGTH(password) as password_length FROM students WHERE id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
    
        if ($user) {
            // Convert names to proper case and combine
            $firstName = toProperCase($user['first_name']);
            $lastName = toProperCase($user['last_name']);
            $fullName2 = trim($firstName . ' ' . $lastName);
            
            $profile_pic = !empty($user['profile_pic']) ? $user['profile_pic'] : 'pics/default_profile.jpg';
            $course = $user['course'];
            $email = $user['email'];
            $phone = $user['mobile_number'];
            
            // Add these lines for student_id and masked password
            $studentId = $user['student_id'];
            $maskedPassword = str_repeat('*', $user['password_length']);
        }
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
        if ($isLoggedIn) {
            $userId = $_SESSION['user_id'];
            
            // First, get all data from the student
            $fetch_sql = "SELECT * FROM students WHERE id = ?";
            $fetch_stmt = $conn->prepare($fetch_sql);
            $fetch_stmt->bind_param('i', $userId);
            $fetch_stmt->execute();
            $result = $fetch_stmt->get_result();
            $student_data = $result->fetch_assoc();
            
            if ($student_data) {
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // First delete from studentapplication table
                    $delete_application_sql = "DELETE FROM studentapplication WHERE student_id = ?";
                    $delete_application_stmt = $conn->prepare($delete_application_sql);
                    $delete_application_stmt->bind_param('i', $userId);
                    $delete_application_stmt->execute();
                    
                    // Insert into deleted_students including the original ID
                    $insert_sql = "INSERT INTO deleted_students (
                        original_student_id, first_name, middle_name, last_name, suffix, course, 
                        school_year, city, region, postal_code, barangay,
                        home_address, email, mobile_number, password,
                        student_id_attachment, created_at, otp_code, name,
                        student_id, profile_picture, profile_pic,
                        student_id_file, registration_form_file,
                        practicum_coordinator, login_method, creation_method,
                        status, approved_by, approved_at
                    ) SELECT 
                        id, first_name, middle_name, last_name, suffix, course,
                        school_year, city, region, postal_code, barangay,
                        home_address, email, mobile_number, password,
                        student_id_attachment, created_at, otp_code, name,
                        student_id, profile_picture, profile_pic,
                        student_id_file, registration_form_file,
                        practicum_coordinator, login_method, creation_method,
                        status, approved_by, approved_at
                    FROM students WHERE id = ?";
                    
                    $insert_stmt = $conn->prepare($insert_sql);
                    $insert_stmt->bind_param('i', $userId);
                    $insert_stmt->execute();
                    
                    // If insertion successful, delete from students
                    $delete_sql = "DELETE FROM students WHERE id = ?";
                    $delete_stmt = $conn->prepare($delete_sql);
                    $delete_stmt->bind_param('i', $userId);
                    $delete_stmt->execute();
                    
                    // Commit transaction
                    $conn->commit();
                    
                    // Clear the session
                    session_destroy();
                    
                    // Redirect to login page
                    header("Location: ../STUDENTCOORLOG/login.php");
                    exit();
                    
                } catch (Exception $e) {
                    // If there's an error, rollback changes
                    $conn->rollback();
                    $_SESSION['error'] = "Failed to delete account: " . $e->getMessage();
                }
            }
        }
    }

    $totalApplications = 0;

    if ($isLoggedIn) {
        // Fetch total applications for current user
        $appSql = "SELECT COUNT(*) as total_applications 
                  FROM studentapplication 
                  WHERE student_id = ?";
        $appStmt = $conn->prepare($appSql);
        $appStmt->bind_param('i', $userId);
        $appStmt->execute();
        $appResult = $appStmt->get_result();
        
        if ($appRow = $appResult->fetch_assoc()) {
            $totalApplications = $appRow['total_applications'];
        }
    }

    $totalEmails = 0;

    if ($isLoggedIn && isset($email)) {
        // Fetch total emails where user is recipient
        $emailSql = "SELECT COUNT(*) as total_emails 
                    FROM messaging 
                    WHERE recipient_email = ?";
        $emailStmt = $conn->prepare($emailSql);
        $emailStmt->bind_param('s', $email);
        $emailStmt->execute();
        $emailResult = $emailStmt->get_result();
        
        if ($emailRow = $emailResult->fetch_assoc()) {
            $totalEmails = $emailRow['total_emails'];
        }
    }

    function getStudentStatus($conn, $userId) {
        // Check if student is hired
        $hiredQuery = "SELECT Status FROM hired_applicants WHERE student_id = ? AND Status = 'Hired'";
        $stmt = $conn->prepare($hiredQuery);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return 'Hired';
        }
    
        // Check application status - using application_id for ORDER BY
        $applicationQuery = "SELECT Status FROM studentapplication WHERE student_id = ? ORDER BY application_id DESC LIMIT 1";
        $stmt = $conn->prepare($applicationQuery);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if ($row['Status'] === 'Applying') {
                return 'Applying';
            } elseif ($row['Status'] === 'For Interview') {
                return 'For Interview';
            }
        }
    
        // Check verification status
        $verificationQuery = "SELECT login_method FROM students WHERE id = ?";
        $stmt = $conn->prepare($verificationQuery);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if ($row['login_method'] === 'google') {
                return 'Not Verified';
            }
            return 'Verified';
        }
    
        return 'Unknown';
    }
    
    // Get the status
    $studentStatus = getStudentStatus($conn, $userId);

    // If status is 'Hired', delete all applications for this student
    if ($studentStatus === 'Hired') {
        $deleteQuery = "DELETE FROM studentapplication WHERE student_id = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param('i', $userId);
        $deleteStmt->execute();
        
        // You might want to check if the deletion was successful
        if ($deleteStmt->affected_rows > 0) {
            // Optional: Set a session message to inform the user their applications were cleared
            $_SESSION['status_message'] = "Your previous applications have been cleared as you are now hired.";
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
  <head>

    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="NAVX.css">
    <link rel="icon" href="pics/ucc.png">
    <link rel="stylesheet" href="FOOTER.css">
    <title>UCC - Account</title>
    <link rel="stylesheet" href="student-account.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  </head>
  <body>
    <div class="navbar">
            <div class="logo-container">
            <button class="menu-toggle" aria-label="Toggle Navigation">☰</button>
                    <img src="pics/ucc-logo.png" alt="Logo">

                </div>
                <div class="nav-links">
                    <a href="studentfrontpage.php">HOME</a>
                    <a href="#about">ABOUT US</a>
                    <a href="#contact">CONTACT US</a>
                </div>
                <div class="auth-buttons">
                    <?php if ($isLoggedIn): ?>
                        <div class="dropdown-container">
                            <div class="border">
                                <span class="greeting-text"><?php echo htmlspecialchars($user['email']); ?></span>
                                <div class="dropdown-btn" onclick="toggleDropdown()">
                                    <img src="<?php echo htmlspecialchars($profile_pic, ENT_QUOTES); ?>" alt="Profile Picture" onerror="this.onerror=null;this.src='pics/default_profile.png';" />
                                </div>
                            </div>
                            <div id="dropdown-content" class="dropdown-content">
                                <div class="user-fullname"><?php echo $fullName2; ?></div>
                                <hr style="margin: 0 auto">
                                <a href="student-profile.php">Profile</a>
                                <a href="../monitoring/std_dashboard.php">Internship</a>
                                <a href="chat-inbox.php">Emails</a>
                                <a href="student-account.php">Settings</a>
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
        document.addEventListener('DOMContentLoaded', function () {
            // Elements
            const navbar = document.querySelector('.navbar');
            const menuToggle = document.querySelector('.menu-toggle');
            const dropdownContent = document.getElementById("dropdown-content");
            let timeout;

            // Navbar visibility functions
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

            // Scroll event listeners
            window.addEventListener('scroll', () => {
                if (window.scrollY === 0) {
                    showNavbar();
                    clearTimeout(timeout);
                } else {
                    resetNavbarTimeout();
                }
            });

            // User interaction listeners
            window.addEventListener('mousemove', resetNavbarTimeout);
            window.addEventListener('click', resetNavbarTimeout);
            window.addEventListener('keydown', resetNavbarTimeout);

            // Initial check
            if (window.scrollY > 0) {
                timeout = setTimeout(hideNavbar, 1000);
            }

            // Mobile menu toggle functionality
            menuToggle.addEventListener('click', function(event) {
                event.stopPropagation(); // Prevent event from bubbling
                navbar.classList.toggle('active');
                
                if (navbar.classList.contains('active')) {
                    menuToggle.innerHTML = '☰';
                    menuToggle.style.color = '#e77d33';
                } else {
                    menuToggle.innerHTML = '☰';
                    menuToggle.style.color = '#fd6f41';
                }
            });

            // Enhanced dropdown toggle function
            window.toggleDropdown = function(event) {
                if (event) {
                    event.stopPropagation();
                }
                
                const isDisplayed = dropdownContent.style.display === "block";
                
                // Close dropdown if it's open
                if (isDisplayed) {
                    dropdownContent.style.display = "none";
                } else {
                    // Close any other open dropdowns first
                    const allDropdowns = document.querySelectorAll('.dropdown-content');
                    allDropdowns.forEach(dropdown => {
                        dropdown.style.display = "none";
                    });
                    
                    // Open this dropdown
                    dropdownContent.style.display = "block";
                }
            };

            // Close menu and dropdown when clicking outside
            document.addEventListener('click', function(event) {
                // Handle mobile menu
                const isClickInsideNavbar = navbar.contains(event.target);
                if (!isClickInsideNavbar && navbar.classList.contains('active')) {
                    navbar.classList.remove('active');
                    menuToggle.innerHTML = '☰';
                    menuToggle.style.color = '#fd6f41';
                }

                // Handle dropdown
                const isClickInsideDropdown = event.target.closest('.dropdown-container');
                if (!isClickInsideDropdown && dropdownContent) {
                    dropdownContent.style.display = "none";
                }
            });

            // Window resize handler
            window.addEventListener('resize', function() {
                if (window.innerWidth > 1300) {
                    navbar.classList.remove('active');
                    menuToggle.innerHTML = '☰';
                    menuToggle.style.color = '#fd6f41';
                }
            });
        });
    </script>
    <div class="profile-container">
        <header class="profile-header"></header>
        <div class="profile-grid">
            <div class="profile-sidebar">
                <div class="avatar-wrapper" onclick="document.getElementById('profileInput').click();">
                    <input type="file" id="profileInput" accept="image/*" style="display: none;" />
                    <img id="profileImage" 
                        src="<?php echo htmlspecialchars($profile_pic); ?>" 
                        alt="<?php echo htmlspecialchars($fullName2); ?>" 
                        onerror="this.onerror=null;this.src='pics/default_profile.jpg';" />
                    <div class="change-overlay">
                        <span>Change</span>
                    </div>
                </div>
                <div class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $studentStatus)); ?>">
                    <?php echo $studentStatus; ?>
                </div>
                <h2><?php echo htmlspecialchars($fullName2); ?></h2>
                <p><?php echo htmlspecialchars($course); ?></p>

                <ul class="profile-stats">
                    <li><span><?php echo htmlspecialchars($totalApplications); ?></span>Applications</li>
                    <li><span><?php echo htmlspecialchars($totalEmails); ?></span>Emails</li>
                </ul>
            
                <hr>

                <div class="profile-bio">
                    <p><svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#2e3849"><path d="M840-288v-276L480-384 48-600l432-216 432 216v312h-72ZM480-144 216-276v-159l264 132 264-132v159L480-144Z"/></svg>University of Caloocan City</p>
                    <p><svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#2e3849"><path d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480v58q0 59-40.5 100.5T740-280q-35 0-66-15t-52-43q-29 29-65.5 43.5T480-280q-83 0-141.5-58.5T280-480q0-83 58.5-141.5T480-680q83 0 141.5 58.5T680-480v58q0 26 17 44t43 18q26 0 43-18t17-44v-58q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93h160q17 0 28.5 11.5T680-120q0 17-11.5 28.5T640-80H480Zm0-280q50 0 85-35t35-85q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 50 35 85t85 35Z"/></svg> <?php echo htmlspecialchars($email); ?></p>
                    <p><svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#2e3849"><path d="M798-120q-125 0-247-54.5T329-329Q229-429 174.5-551T120-798q0-18 12-30t30-12h162q14 0 25 9.5t13 22.5l26 140q2 16-1 27t-11 19l-97 98q20 37 47.5 71.5T387-386q31 31 65 57.5t72 48.5l94-94q9-9 23.5-13.5T670-390l138 28q14 4 23 14.5t9 23.5v162q0 18-12 30t-30 12Z"/></svg> <?php echo htmlspecialchars($phone); ?></p>
                </div>
        </div>
        <script>
          document.addEventListener('DOMContentLoaded', function() {
            const profileInput = document.getElementById('profileInput');
            const profileImage = document.getElementById('profileImage');

            // Store the original image source when the page loads
            profileImage.setAttribute('data-original-src', profileImage.src);

            // Handle file input change event
            profileInput.addEventListener('change', function(event) {
                const file = event.target.files[0];

                if (file) {
                    // Show a preview of the selected file
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        profileImage.src = e.target.result; // Update the preview
                    };
                    reader.readAsDataURL(file);

                    // Create a FormData object to send the file to the server
                    const formData = new FormData();
                    formData.append('profile_pic', file);

                    // Send the file to the server via fetch API
                    fetch('upload_profile_pic.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Simply reload the page on success
                            window.location.reload();
                        } else {
                            // Restore the original image if upload fails
                            profileImage.src = profileImage.getAttribute('data-original-src');
                            alert(data.message); // Keep error alert for failed uploads
                        }
                    })
                    .catch(error => {
                        console.error('Error uploading file:', error);
                        alert('An error occurred while uploading the file.'); // Keep error alert for network issues
                        profileImage.src = profileImage.getAttribute('data-original-src');
                    });
                }
            });
        });
        </script>
        <div class="profile-main">
          <nav class="profile-nav">
            <ul>
              <li><a href="student-overview.php">internships</a></li>
              <li><a href="student-profile.php">personal info</a></li>
              <li><a href="student-account.php">account</a></li>
            </ul>
          </nav>
          <h4 class="label">Manage Account</h4>
          <h5 class="sub-label">Change your password and update security settings.</h5>
            <form class="form-container" id="passwordUpdateForm">
                <div class="input-container full-width">
                    <label class="input-label" for="oldPassword">Old Password</label>
                    <div class="input-group">
                        <input class="input-field" type="password" id="oldPassword" name="oldPassword" placeholder="Old Password here">
                        <i class="toggle-password fas fa-eye-slash"></i>
                        <span class="error-message" id="oldPasswordError">Incorrect old password</span>
                    </div>
                </div>
                <div class="input-container full-width">
                    <label class="input-label" for="newPassword">New Password</label>
                    <div class="input-group">
                        <input class="input-field" type="password" id="newPassword" name="newPassword" placeholder="New Password here">
                        <i class="toggle-password fas fa-eye-slash" ></i>
                        <div class="error-message" id="newPasswordError">This field is required</div>
                    </div>
                </div>
                <button type="submit" class="save-button">Update</button>
            </form>
            <script>
                $(document).ready(function() {
                    let oldPasswordTimer;
                    let oldPasswordValidated = false;
                    let newPasswordValidated = false;
                    let newPasswordInteracted = false;
                    
                    // Add password toggle functionality
                    $('.toggle-password').on('click', function() {
                        const $input = $(this).siblings('input');
                        
                        if ($input.attr('type') === 'password') {
                            $input.attr('type', 'text');
                            $(this).removeClass('fa-eye-slash').addClass('fa-eye');
                        } else {
                            $input.attr('type', 'password');
                            $(this).removeClass('fa-eye').addClass('fa-eye-slash');
                        }
                    });
                    
                    function validatePassword(password) {
                        const hasUpperCase = /[A-Z]/.test(password);
                        const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);
                        const isLengthValid = password.length >= 8;
                        
                        if (!hasUpperCase) {
                            return {
                                valid: false,
                                message: "Password must include at least one uppercase letter"
                            };
                        } else if (!hasSpecialChar) {
                            return {
                                valid: false,
                                message: "Password must include at least one special character (e.g., @, #, $)"
                            };
                        } else if (!isLengthValid) {
                            return {
                                valid: false,
                                message: "Password must be at least 8 characters long"
                            };
                        }
                        
                        return {
                            valid: true,
                            message: ""
                        };
                    }

                    $('#oldPassword').on('input', function() {
                        clearTimeout(oldPasswordTimer);
                        const oldPassword = $(this).val();
                        const $error = $('#oldPasswordError');
                        const $input = $(this);
                        
                        if (oldPassword.length === 0) {
                            if (oldPasswordValidated) {
                                $error.show();
                                $input.addClass('error').removeClass('valid');
                            }
                            return;
                        }

                        oldPasswordTimer = setTimeout(function() {
                            $.ajax({
                                url: 'update_password.php',
                                method: 'POST',
                                data: { oldPassword: oldPassword },
                                success: function(response) {
                                    oldPasswordValidated = true;
                                    if (!response.valid) {
                                        $error.show();
                                        $input.addClass('error').removeClass('valid');
                                    } else {
                                        $error.hide();
                                        $input.removeClass('error').addClass('valid');
                                    }
                                }
                            });
                        }, 500);
                    });

                    $('#oldPassword').on('blur', function() {
                        const $input = $(this);
                        const $error = $('#oldPasswordError');
                        
                        if (oldPasswordValidated && $input.val().length === 0) {
                            $error.show();
                            $input.addClass('error').removeClass('valid');
                        } else if ($input.val().length > 0) {
                            $input.removeClass('valid');
                        }
                    });

                    $('#newPassword').on('input', function() {
                        const $input = $(this);
                        const $error = $('#newPasswordError');
                        const password = $input.val();
                        newPasswordInteracted = true;
                        
                        if (password.length === 0) {
                            if (newPasswordValidated) {
                                $error.text("This field is required");
                                $error.show();
                                $input.addClass('error').removeClass('valid');
                            }
                        } else {
                            const validation = validatePassword(password);
                            if (validation.valid) {
                                $error.hide();
                                $input.removeClass('error').addClass('valid');
                            } else {
                                $error.text(validation.message);
                                $error.show();
                                $input.addClass('error').removeClass('valid');
                            }
                        }
                    });

                    $('#newPassword').on('blur', function() {
                        const $input = $(this);
                        const $error = $('#newPasswordError');
                        const password = $input.val();
                        
                        if (newPasswordInteracted) {
                            if (password.length === 0) {
                                newPasswordValidated = true;
                                $error.text("This field is required");
                                $error.show();
                                $input.addClass('error').removeClass('valid');
                            } else {
                                const validation = validatePassword(password);
                                if (validation.valid) {
                                    $input.removeClass('valid error');
                                    $error.hide();
                                } else {
                                    $error.text(validation.message);
                                    $error.show();
                                    $input.addClass('error').removeClass('valid');
                                }
                            }
                        }
                    });

                    $('#passwordUpdateForm').on('submit', function(e) {
                        e.preventDefault();
                        
                        const $oldPassword = $('#oldPassword');
                        const $newPassword = $('#newPassword');
                        const $oldPasswordError = $('#oldPasswordError');
                        const $newPasswordError = $('#newPasswordError');
                        
                        oldPasswordValidated = true;
                        newPasswordValidated = true;
                        newPasswordInteracted = true;
                        
                        let hasError = false;
                        
                        if (!$oldPassword.val()) {
                            $oldPassword.addClass('error').removeClass('valid');
                            $oldPasswordError.show();
                            hasError = true;
                        }
                        
                        const newPasswordValidation = validatePassword($newPassword.val());
                        if (!$newPassword.val()) {
                            $newPassword.addClass('error').removeClass('valid');
                            $newPasswordError.text("This field is required");
                            $newPasswordError.show();
                            hasError = true;
                        } else if (!newPasswordValidation.valid) {
                            $newPassword.addClass('error').removeClass('valid');
                            $newPasswordError.text(newPasswordValidation.message);
                            $newPasswordError.show();
                            hasError = true;
                        }
                        
                        if (hasError) {
                            return;
                        }

                        $.ajax({
                            url: 'update_password.php',
                            method: 'POST',
                            data: {
                                oldPassword: $oldPassword.val(),
                                newPassword: $newPassword.val()
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Instead of alert, just reload the page
                                    window.location.reload();
                                } else {
                                    // Show error message in form instead of alert
                                    $oldPasswordError.text(response.message || 'Failed to update password');
                                    $oldPasswordError.show();
                                    $oldPassword.addClass('error').removeClass('valid');
                                }
                            },
                            error: function() {
                                // Show error message in form instead of alert
                                $oldPasswordError.text('An error occurred while updating the password');
                                $oldPasswordError.show();
                                $oldPassword.addClass('error').removeClass('valid');
                            }
                        });
                    });
                });
            </script>
          <h4 class="label">Account Deletion</h4>
          <h5 class="sub-label">Manage and permanently remove your account.</h5>
          <form class="form-container" method="POST" onsubmit="return confirmDelete(event)">
                <div class="input-container full-width">
                    <label class="input-label" for="studentNumber">Student Number</label>
                    <input class="input-field readonly" type="text" id="studentNumber" name="studentNumber" 
                        value="<?php echo htmlspecialchars($studentId); ?>" placeholder="Student Number here" readonly>
                </div>
                <div class="input-container full-width">
                    <label class="input-label" for="userEmail">Email</label>
                    <input class="input-field readonly" type="text" id="userEmail" name="userEmail" 
                        value="<?php echo htmlspecialchars($email); ?>" placeholder="Email here" readonly>
                </div>
                <div class="input-container full-width">
                    <label class="input-label" for="deletePassword">Password</label>
                    <input class="input-field readonly" type="text" id="deletePassword" name="deletePassword" 
                        value="<?php echo htmlspecialchars($maskedPassword); ?>" placeholder="Password here" readonly>
                </div>
                <input type="hidden" name="delete_account" value="1">
                <button type="submit" class="save-button">Delete Account</button>
            </form>

            <div id="deleteModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Delete your Account?</h2>
                    </div>
                    <div class="modal-body">
                        <p class="action">This action is final and you will be unable to recover any data</p>
                        <div class="student-info">
                            <p class="stu-email">Email: <span id="modalUserEmail"></span></p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button id="confirmDelete" class="btn-delete">YES</button>
                        <button id="cancelDelete" class="btn-cancel">NO</button>
                    </div>
                </div>
            </div>

            <script>
                function confirmDelete(event) {
                    event.preventDefault();
                    
                    // Get the email directly from the input field
                    const userEmail = document.getElementById('userEmail').value;
                    
                    // Update the modal with the user's email
                    document.getElementById('modalUserEmail').textContent = userEmail;
                    
                    // Show the modal
                    const modal = document.getElementById('deleteModal');
                    modal.style.display = 'flex';
                    
                    // Handle the confirmation
                    document.getElementById('confirmDelete').onclick = function() {
                        modal.style.display = 'none';
                        event.target.submit(); // Submit the form
                    };
                    
                    // Handle the cancellation
                    document.getElementById('cancelDelete').onclick = function() {
                        modal.style.display = 'none';
                    };
                    
                    // Close the modal if clicking outside
                    window.onclick = function(event) {
                        if (event.target === modal) {
                            modal.style.display = 'none';
                        }
                    };
                    
                    return false;
                }
            </script>
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
                            <li><a href="studentmain.php">Internship by Company</a></li>
                            <li><a href="studentmain.php">Internship by City</a></li>
                            <li><a href="studentmain.php">Search Nearby Internship</a></li>
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
                            <li><a href="studentfrontpage.php#about">About Us</a></li>
                            <li><a href="studentfrontpage.php#aichat">How It Works</a></li>
                            <li><a href="studentfrontpage.php#contact">Contact Us</a></li>
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

          

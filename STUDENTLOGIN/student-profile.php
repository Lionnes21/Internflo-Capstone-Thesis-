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
      $sql = 'SELECT first_name, last_name, profile_pic, course, email, mobile_number, school_year, student_id, city, region, postal_code, barangay, home_address FROM students WHERE id = ?';
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
  
          // Assign fetched data to variables
          $profile_pic = !empty($user['profile_pic']) ? $user['profile_pic'] : 'pics/default_profile.jpg';
          $course = $user['course'];
          $email = $user['email'];
          $phone = $user['mobile_number'];
          $yearSection = $user['school_year'];
          $studentNumber = $user['student_id'];
  
          // Fetch address details
          $city = $user['city'];
          $region = $user['region'];
          $postalCode = $user['postal_code'];
          $barangay = $user['barangay'];
          $address = $user['home_address'];
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
    <title>UCC - Profile</title>
    <link rel="stylesheet" href="student-profile.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
          <h4 class="label">Personal Information</h4>
          <h5 class="sub-label">Edit your name, contact details, and other personal info.</h5>
          <div class="form-container">
            <form action="update_profile.php" method="POST">
                <div class="input-container">
                    <label class="input-label" for="first-name">First Name</label>
                    <input class="input-field" type="text" id="first-name" name="first_name" value="<?php echo htmlspecialchars($firstName ?? ''); ?>" placeholder="First Name here" onkeypress="allowOnlyLetters(event)" oninput="capitalizeFirstLetter(this)">
                </div>

                <div class="input-container">
                    <label class="input-label" for="last-name">Last Name</label>
                    <input class="input-field" type="text" id="last-name" name="last_name" value="<?php echo htmlspecialchars($lastName ?? ''); ?>" placeholder="Last Name here" onkeypress="allowOnlyLetters(event)" oninput="capitalizeFirstLetter(this)">
                </div>

                <div class="input-container">
                    <label class="input-label" for="email">Email</label>
                    <input class="input-field" type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" placeholder="Email here">
                </div>

                <div class="input-container">
                    <label class="input-label" for="phone">Phone</label>
                    <input class="input-field" type="tel" id="phone" name="mobile_number" value="<?php echo htmlspecialchars($phone ?? ''); ?>" placeholder="Phone here" maxlength="11" onkeypress="allowOnlyNumbers(event)">
                </div>

                <div class="input-container">
                    <label class="input-label" for="YearSection">Year & Section</label>
                    <input class="input-field readonly" type="text" id="YearSection" name="school_year" value="<?php echo htmlspecialchars($yearSection ?? ''); ?>" placeholder="Year & Section here" readonly>
                </div>

                <div class="input-container">
                    <label class="input-label" for="student-number">Student Number</label>
                    <input class="input-field readonly" type="text" id="student-number" name="student_id" value="<?php echo htmlspecialchars($studentNumber ?? ''); ?>" placeholder="Student Number here" readonly>
                </div>

                <div class="input-container full-width">
                    <label class="input-label" for="Course">Course</label>
                    <input class="input-field readonly" type="text" id="Course" name="course" value="<?php echo htmlspecialchars($course ?? ''); ?>" placeholder="Course here" readonly>
                </div>

                <button type="submit" class="save-button">Update</button>
            </form>
            <script>
                $(document).ready(function() {
                    // Add error message elements after each input container
                    $('.input-container:has(#first-name)').append('<span class="error-message" id="firstNameError">This field is required</span>');
                    $('.input-container:has(#last-name)').append('<span class="error-message" id="lastNameError">This field is required</span>');
                    $('.input-container:has(#email)').append('<span class="error-message" id="emailError">This field is required</span>');
                    $('.input-container:has(#phone)').append('<span class="error-message" id="phoneError">This field is required</span>');
                    
                    // Track if fields have been modified (had content and then were emptied)
                    let firstNameModified = false;
                    let lastNameModified = false;
                    let emailModified = false;
                    let phoneModified = false;
                    let emailCheckTimeout;
                    
                    // Hide all error messages initially
                    $('.error-message').hide();
                    
                    // Input restrictions
                    function allowOnlyLetters(event) {
                        if (!/[a-zA-Z\s]/.test(event.key)) {
                            event.preventDefault();
                        }
                    }

                    function allowOnlyNumbers(event) {
                        if (!/[0-9]/.test(event.key)) {
                            event.preventDefault();
                        }
                    }

                    function capitalizeFirstLetter(input) {
                        input.value = input.value.charAt(0).toUpperCase() + input.value.slice(1);
                    }
                    
                    // Validation functions
                    function validateFirstName(showValid = true) {
                        const $input = $('#first-name');
                        const $error = $('#firstNameError');
                        const value = $input.val();
                        
                        if (value.length === 0 && (firstNameModified || $('form').data('submitted'))) {
                            $error.show();
                            $input.addClass('error').removeClass('valid');
                            return false;
                        } else {
                            $error.hide();
                            $input.removeClass('error');
                            if (showValid) $input.addClass('valid');
                            return true;
                        }
                    }
                    
                    function validateLastName(showValid = true) {
                        const $input = $('#last-name');
                        const $error = $('#lastNameError');
                        const value = $input.val();
                        
                        if (value.length === 0 && (lastNameModified || $('form').data('submitted'))) {
                            $error.show();
                            $input.addClass('error').removeClass('valid');
                            return false;
                        } else {
                            $error.hide();
                            $input.removeClass('error');
                            if (showValid) $input.addClass('valid');
                            return true;
                        }
                    }
                    
                    function validateEmail(showValid = true) {
                        const $input = $('#email');
                        const $error = $('#emailError');
                        const value = $input.val();
                        
                        if (value.length === 0 && (emailModified || $('form').data('submitted'))) {
                            $error.text("This field is required");
                            $error.show();
                            $input.addClass('error').removeClass('valid');
                            return false;
                        } else if (value.length > 0 && !isValidEmail(value)) {
                            $error.text("Please enter a valid email address");
                            $error.show();
                            $input.addClass('error').removeClass('valid');
                            return false;
                        } else if (value.length > 0) {
                            clearTimeout(emailCheckTimeout);
                            emailCheckTimeout = setTimeout(() => {
                                $.ajax({
                                    url: 'update_email.php',
                                    method: 'POST',
                                    data: { email: value },
                                    dataType: 'json',
                                    success: function(response) {
                                        if (!response.success) {
                                            $error.text("Error: " + response.error);
                                            $error.show();
                                            $input.addClass('error').removeClass('valid');
                                        } else if (response.exists) {
                                            $error.text("Email already taken");
                                            $error.show();
                                            $input.addClass('error').removeClass('valid');
                                        } else {
                                            $error.hide();
                                            if (showValid) $input.addClass('valid').removeClass('error');
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        $error.text("Error checking email availability: " + error);
                                        $error.show();
                                        $input.addClass('error').removeClass('valid');
                                    }
                                });
                            }, 500);
                            return true;
                        }
                        
                        $error.hide();
                        $input.removeClass('error');
                        if (showValid) $input.addClass('valid');
                        return true;
                    }
                    
                    function validatePhone(showValid = true) {
                        const $input = $('#phone');
                        const $error = $('#phoneError');
                        const value = $input.val();
                        
                        if (value.length === 0 && (phoneModified || $('form').data('submitted'))) {
                            $error.text("This field is required");
                            $error.show();
                            $input.addClass('error').removeClass('valid');
                            return false;
                        } else if (value.length > 0) {
                            if (value.length !== 11) {
                                $error.text("Phone number must be 11 digits");
                                $error.show();
                                $input.addClass('error').removeClass('valid');
                                return false;
                            } else if (!value.startsWith('09')) {
                                $error.text("Phone number must start with '09'");
                                $error.show();
                                $input.addClass('error').removeClass('valid');
                                return false;
                            }
                        }
                        
                        $error.hide();
                        $input.removeClass('error');
                        if (showValid) $input.addClass('valid');
                        return true;
                    }
                    
                    function isValidEmail(email) {
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        return emailRegex.test(email);
                    }
                    
                    $('#first-name').on('input', function() {
                        firstNameModified = true;  // Always set to true on any input change
                        validateFirstName(true);
                    });

                    $('#last-name').on('input', function() {
                        lastNameModified = true;  // Always set to true on any input change
                        validateLastName(true);
                    });

                    $('#email').on('input', function() {
                        emailModified = true;  // Always set to true on any input change
                        validateEmail(true);
                    });

                    $('#phone').on('input', function() {
                        phoneModified = true;  // Always set to true on any input change
                        validatePhone(true);
                    });
                    $('#first-name, #last-name, #email, #phone').on('cut paste', function() {
                        const field = $(this).attr('id');
                        setTimeout(() => {
                            switch(field) {
                                case 'first-name':
                                    firstNameModified = true;
                                    validateFirstName(true);
                                    break;
                                case 'last-name':
                                    lastNameModified = true;
                                    validateLastName(true);
                                    break;
                                case 'email':
                                    emailModified = true;
                                    validateEmail(true);
                                    break;
                                case 'phone':
                                    phoneModified = true;
                                    validatePhone(true);
                                    break;
                            }
                        }, 0);
                    });
                    
                    // Add keypress event listeners for input restrictions
                    $('#first-name, #last-name').on('keypress', allowOnlyLetters);
                    $('#phone').on('keypress', allowOnlyNumbers);
                    
                    // Add input event listeners for capitalization
                    $('#first-name, #last-name').on('input', function() {
                        capitalizeFirstLetter(this);
                    });
                    
                    // Add blur event listeners
                    $('#first-name').on('blur', function() {
                        if (firstNameModified) {
                            validateFirstName(false);
                        }
                        $(this).removeClass('valid');
                    });
                    
                    $('#last-name').on('blur', function() {
                        if (lastNameModified) {
                            validateLastName(false);
                        }
                        $(this).removeClass('valid');
                    });
                    
                    $('#email').on('blur', function() {
                        if (emailModified) {
                            validateEmail(false);
                        }
                        $(this).removeClass('valid');
                    });
                    
                    $('#phone').on('blur', function() {
                        if (phoneModified) {
                            validatePhone(false);
                        }
                        $(this).removeClass('valid');
                    });
                    
                    // Form submission handler
                    $('form[action="update_profile.php"]').on('submit', function(e) {
                        e.preventDefault();
                        
                        // Mark form as submitted to show all validation errors
                        $(this).data('submitted', true);
                        
                        // Validate all non-email fields
                        const isFirstNameValid = validateFirstName(true);
                        const isLastNameValid = validateLastName(true);
                        const isPhoneValid = validatePhone(true);
                        
                        // Check email with server before submitting
                        $.ajax({
                            url: 'update_email.php',
                            method: 'POST',
                            data: { email: $('#email').val() },
                            dataType: 'json',
                            success: function(response) {
                                const isEmailValid = !response.exists && isValidEmail($('#email').val());
                                
                                if (isFirstNameValid && isLastNameValid && isEmailValid && isPhoneValid) {
                                    // If all validations pass, submit the form
                                    $(e.target).off('submit').submit();
                                }
                            },
                            error: function() {
                                $('#emailError').text("Error checking email availability").show();
                            }
                        });
                    });
                });
            </script>
          </div>
          <h4 class="label">Address Information</h4>
          <h5 class="sub-label">View and update your saved addresses..</h5>
          <div class="form-container">
            <form action="update_address.php" method="POST">
                <div class="input-container">
                    <label class="input-label" for="city">City</label>
                    <input class="input-field" type="text" id="city" name="city" value="<?php echo htmlspecialchars($city ?? ''); ?>" placeholder="City here" onkeypress="allowOnlyLetters(event)" oninput="capitalizeFirstLetter(this)">
                </div>

                <div class="input-container">
                    <label class="input-label" for="region">Region</label>
                    <input class="input-field" type="text" id="region" name="region" value="<?php echo htmlspecialchars($region ?? ''); ?>" placeholder="Region here" onkeypress="allowOnlyLetters(event)" oninput="capitalizeFirstLetter(this)">
                </div>

                <div class="input-container">
                    <label class="input-label" for="postal-code">Postal Code</label>
                    <input class="input-field" type="text" id="postal-code" name="postal_code" value="<?php echo htmlspecialchars($postalCode ?? ''); ?>" placeholder="Postal Code here"  onkeypress="allowOnlyNumbers(event)">
                </div>

                <div class="input-container">
                    <label class="input-label" for="barangay">Barangay</label>
                    <input class="input-field" type="text" id="barangay" name="barangay" value="<?php echo htmlspecialchars($barangay ?? ''); ?>" placeholder="Barangay here"  onkeypress="allowOnlyNumbers(event)">
                </div>

                <div class="input-container full-width">
                    <label class="input-label" for="home_address">Home Address</label>
                    <input class="input-field" type="text" id="home_address" name="home_address" value="<?php echo htmlspecialchars($address ?? ''); ?>" placeholder="Home Address here" onkeypress="allowOnlyLettersAndSpace(event)" oninput="capitalizeFirstLetter(this)">
                </div>

                <button type="submit" class="save-button">Update</button>
            </form>
        </div>
        <script>
            $(document).ready(function() {
            // Add error message elements after each input container
            $('.input-container:has(#city)').append('<span class="error-message" id="cityError">This field is required</span>');
            $('.input-container:has(#region)').append('<span class="error-message" id="regionError">This field is required</span>');
            $('.input-container:has(#postal-code)').append('<span class="error-message" id="postalCodeError">This field is required</span>');
            $('.input-container:has(#barangay)').append('<span class="error-message" id="barangayError">This field is required</span>');
            $('.input-container:has(#home_address)').append('<span class="error-message" id="homeAddressError">This field is required</span>');
            
            // Track if fields have been modified
            let cityModified = false;
            let regionModified = false;
            let postalCodeModified = false;
            let barangayModified = false;
            let homeAddressModified = false;
            
            // Hide all error messages initially
            $('.error-message').hide();
            
            // Generic validation function for required fields
            function validateRequiredField(inputId, errorId, showValid = true) {
                const $input = $('#' + inputId);
                const $error = $('#' + errorId);
                const value = $input.val();
                
                // Get the modified state based on input ID
                let isModified = false;
                switch(inputId) {
                    case 'city': isModified = cityModified; break;
                    case 'region': isModified = regionModified; break;
                    case 'postal-code': isModified = postalCodeModified; break;
                    case 'barangay': isModified = barangayModified; break;
                    case 'home_address': isModified = homeAddressModified; break;
                }
                
                if (value.length === 0 && (isModified || $('form').data('submitted'))) {
                    $error.show();
                    $input.addClass('error').removeClass('valid');
                    return false;
                } else {
                    $error.hide();
                    $input.removeClass('error');
                    if (showValid) $input.addClass('valid');
                    return true;
                }
            }
            
            // Add input event listeners
            $('#city').on('input', function() {
                if ($(this).val().length > 0) {
                    cityModified = true;
                }
                validateRequiredField('city', 'cityError', true);
            });
            
            $('#region').on('input', function() {
                if ($(this).val().length > 0) {
                    regionModified = true;
                }
                validateRequiredField('region', 'regionError', true);
            });
            
            $('#postal-code').on('input', function() {
                if ($(this).val().length > 0) {
                    postalCodeModified = true;
                }
                validateRequiredField('postal-code', 'postalCodeError', true);
            });
            
            $('#barangay').on('input', function() {
                if ($(this).val().length > 0) {
                    barangayModified = true;
                }
                validateRequiredField('barangay', 'barangayError', true);
            });
            
            $('#home_address').on('input', function() {
                if ($(this).val().length > 0) {
                    homeAddressModified = true;
                }
                validateRequiredField('home_address', 'homeAddressError', true);
            });
            
            // Add blur event listeners
            $('#city').on('blur', function() {
                if (cityModified) {
                    validateRequiredField('city', 'cityError', false);
                }
                $(this).removeClass('valid');
            });
            
            $('#region').on('blur', function() {
                if (regionModified) {
                    validateRequiredField('region', 'regionError', false);
                }
                $(this).removeClass('valid');
            });
            
            $('#postal-code').on('blur', function() {
                if (postalCodeModified) {
                    validateRequiredField('postal-code', 'postalCodeError', false);
                }
                $(this).removeClass('valid');
            });
            
            $('#barangay').on('blur', function() {
                if (barangayModified) {
                    validateRequiredField('barangay', 'barangayError', false);
                }
                $(this).removeClass('valid');
            });
            
            $('#home_address').on('blur', function() {
                if (homeAddressModified) {
                    validateRequiredField('home_address', 'homeAddressError', false);
                }
                $(this).removeClass('valid');
            });
            
            // Form submission handler
            $('form[action="update_address.php"]').on('submit', function(e) {
                e.preventDefault();
                
                // Mark form as submitted to show all validation errors
                $(this).data('submitted', true);
                
                // Validate all fields
                const isCityValid = validateRequiredField('city', 'cityError', true);
                const isRegionValid = validateRequiredField('region', 'regionError', true);
                const isPostalCodeValid = validateRequiredField('postal-code', 'postalCodeError', true);
                const isBarangayValid = validateRequiredField('barangay', 'barangayError', true);
                const isHomeAddressValid = validateRequiredField('home_address', 'homeAddressError', true);
                
                // If all validations pass, submit the form
                if (isCityValid && isRegionValid && isPostalCodeValid && isBarangayValid && isHomeAddressValid) {
                    $(this).off('submit').submit();
                }
            });
            });
        </script>


            
      </div>

    </div>
    </div>
    <script>
        function capitalizeFirstLetter(input) {
        input.value = input.value.replace(/(?:^|\s)\S/g, function (a) {
            return a.toUpperCase();
        });
        }
        function allowOnlyLetters(event) {
        if (!/[a-zA-Z ]/.test(event.key)) {
            event.preventDefault();
        }
        }
        function allowOnlyNumbers(event) {
        // Get the character code from the event
        const charCode = (event.which) ? event.which : event.keyCode;
        
        // Allow only numbers (0-9)
        if (charCode < 48 || charCode > 57) {
            // Prevent all non-numeric keys
            event.preventDefault();
            return false;
        }
        return true;
        }
        function allowOnlyLettersAndSpace(event) {
        if (!/[a-zA-Z0-9 ]/.test(event.key)) {
            event.preventDefault();
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

          

<?php
    session_start();
    include 'config.php';  // Database connection config

    // Handle logout
    if (isset($_GET['logout'])) {
        session_destroy();
        header("Location: companymainpage.html");
        exit();
    }

    // Function to get full name
    function getFullName() {
        if (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
            return htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']);
        }
        return 'Your Name'; // Fallback if names are not set
    }

    // Get the table and user_id from session
    $user_id = $_SESSION['user_id'];
    $source_table = $_SESSION['source_table'] ?? '';

    // Initialize userData array
    $userData = null;

    // Try to fetch from approvedrecruiters first
    $stmt = $conn->prepare("SELECT first_name, last_name, email, company_name, company_address, company_email, mobile_number, company_phone 
                           FROM approvedrecruiters 
                           WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $userData = $result->fetch_assoc();
        
        // Get number of regular applicants
        $applicants_query = "SELECT COUNT(*) as applicant_count 
                            FROM studentapplication sa 
                            INNER JOIN internshipad ia ON sa.internshipad_id = ia.internship_id 
                            WHERE ia.user_id = ?";
        
        $stmt = $conn->prepare($applicants_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $applicants_result = $stmt->get_result();
        $applicants_data = $applicants_result->fetch_assoc();
        $applicant_count = $applicants_data['applicant_count'];

        // Get number of hired applicants
        $hired_query = "SELECT COUNT(*) as hired_count 
                       FROM hired_applicants ha 
                       INNER JOIN internshipad ia ON ha.internshipad_id = ia.internship_id 
                       WHERE ia.user_id = ?";
        
        $stmt = $conn->prepare($hired_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $hired_result = $stmt->get_result();
        $hired_data = $hired_result->fetch_assoc();
        $hired_count = $hired_data['hired_count'];
        
    } else {
        // If not found in approvedrecruiters, try recruiters table
        $stmt = $conn->prepare("SELECT first_name, last_name, email, company_name, company_address, company_email, mobile_number, company_phone 
                               FROM recruiters 
                               WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $userData = $result->fetch_assoc();
            
            // Get number of regular applicants
            $applicants_query = "SELECT COUNT(*) as applicant_count 
                                FROM studentapplication sa 
                                INNER JOIN internshipad ia ON sa.internshipad_id = ia.internship_id 
                                WHERE ia.user_id = ?";
            
            $stmt = $conn->prepare($applicants_query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $applicants_result = $stmt->get_result();
            $applicants_data = $applicants_result->fetch_assoc();
            $applicant_count = $applicants_data['applicant_count'];

            // Get number of hired applicants
            $hired_query = "SELECT COUNT(*) as hired_count 
                           FROM hired_applicants ha 
                           INNER JOIN internshipad ia ON ha.internshipad_id = ia.internship_id 
                           WHERE ia.user_id = ?";
            
            $stmt = $conn->prepare($hired_query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $hired_result = $stmt->get_result();
            $hired_data = $hired_result->fetch_assoc();
            $hired_count = $hired_data['hired_count'];
        }
    }

    // If no data found in either table
    if (!$userData) {
        die("User data not found in either table.");
    }
    
    // Get email count
    $totalEmails = 0;
    if (isset($userData['email'])) {
        $email = $userData['email'];
        
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
    
    // Initialize variables if they don't exist yet
    if (!isset($applicant_count)) {
        $applicant_count = 0;
    }
    
    if (!isset($hired_count)) {
        $hired_count = 0;
    }

    function getRecruiterStatus($conn, $userId) {
        // Check approvedrecruiters table first
        $stmt = $conn->prepare("SELECT id FROM approvedrecruiters WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return 'Verified';
        }
        
        // Check recruiters table
        $stmt = $conn->prepare("SELECT id FROM recruiters WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return 'Not Verified';
        }
        
        return 'Unknown';
    }
    
    // Get the recruiter's status
    $recruiterStatus = getRecruiterStatus($conn, $user_id);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="NAV-login.css">
    <link rel="icon" href="pics/ucc.png">
    <link rel="stylesheet" href="FOOTER.css">
    <title>UCC - Profile</title>
    <link rel="stylesheet" href="company-org-profile.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  </head>
  <body>

    <!-- NAVIGATION -->
    <div class="navbar">
        <div class="logo-container">
            <button class="menu-toggle" aria-label="Toggle Navigation">☰</button>
            <img src="pics/ucc-logo.png" alt="Logo" class="logo-img">
        </div>
        <div class="nav-links">
            <a href="companyloginpage.php">HOME</a>
            <a href="#about">ABOUT US</a>
            <a href="#contact">CONTACT US</a>
            <?php if(!isset($_SESSION['email'])): ?>
                <a href="../STUDENTCOORLOG/login.php" class="login-btn">LOGIN</a>
                <a href="../MAIN/MAIN.php" class="employer-btn">APPLICANT SITE</a>
            <?php endif; ?>
        </div>
        <?php if(isset($_SESSION['email'])): ?>
        <div class="auth-buttons">
            <div class="dropdown-container">
                <div class="border">
                    <span class="greeting-text"><?php echo htmlspecialchars($_SESSION['email']); ?></span>
                    <div class="dropdown-btn" onclick="toggleDropdown(event)">
                        <img src="pics/profile.png" alt="Profile" onerror="this.onerror=null;this.src='pics/default_profile.jpg';">
                    </div>
                </div>
                <div id="dropdown-content" class="dropdown-content">
                    <div class="user-fullname"><?php echo getFullName(); ?></div>
                    <hr style="margin: 0 auto">
                    <a href="company-profile.php">Profile</a>
                    <a href="company-overview.php">Interns</a>
                    <a href="chat-inbox.php">Emails</a>
                    <a href="company-account.php">Settings</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
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

            // Dropdown toggle function
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
    <!-- NAVIGATION -->


   <div class="profile-container">
    <header class="profile-header"></header>
        <div class="profile-grid">
          <div class="profile-sidebar">
            <div class="avatar-wrapper" onclick="document.getElementById('profileInput').click();">
                <input type="file" id="profileInput" accept="image/*" style="display: none;" />
                <img id="profileImage" 
                    src="pics/default_profile.png';" 
                    alt="pics/default_profile.png';" 
                    onerror="this.onerror=null;this.src='pics/default_profile.png';" />
                <div class="change-overlay">
                    <span>Change</span>
                </div>
            </div>
            <div class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $recruiterStatus)); ?>">
                <?php echo $recruiterStatus; ?>
            </div>
            <h2><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></h2>
            <p><?php echo htmlspecialchars($userData['company_name']); ?> - Employer</p>

            <ul class="profile-stats">
                <li><span><?php echo htmlspecialchars($hired_count ?? 0); ?></span>Interns</li>
                <li><span><?php echo htmlspecialchars($applicant_count ?? 0); ?></span>Applicants</li>
                <li><span><?php echo htmlspecialchars($totalEmails ?? 0); ?></span>Emails</li>
            </ul>
        
            <hr>

            <div class="profile-bio">
                <p><svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#2e3849"><path d="M840-288v-276L480-384 48-600l432-216 432 216v312h-72ZM480-144 216-276v-159l264 132 264-132v159L480-144Z"/></svg><?php echo htmlspecialchars($userData['company_name']); ?></p>
                <p><svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#2e3849"><path d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480v58q0 59-40.5 100.5T740-280q-35 0-66-15t-52-43q-29 29-65.5 43.5T480-280q-83 0-141.5-58.5T280-480q0-83 58.5-141.5T480-680q83 0 141.5 58.5T680-480v58q0 26 17 44t43 18q26 0 43-18t17-44v-58q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93h160q17 0 28.5 11.5T680-120q0 17-11.5 28.5T640-80H480Zm0-280q50 0 85-35t35-85q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 50 35 85t85 35Z"/></svg><?php echo htmlspecialchars($userData['email']); ?></p>
                <p><svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#2e3849"><path d="M798-120q-125 0-247-54.5T329-329Q229-429 174.5-551T120-798q0-18 12-30t30-12h162q14 0 25 9.5t13 22.5l26 140q2 16-1 27t-11 19l-97 98q20 37 47.5 71.5T387-386q31 31 65 57.5t72 48.5l94-94q9-9 23.5-13.5T670-390l138 28q14 4 23 14.5t9 23.5v162q0 18-12 30t-30 12Z"/></svg><?php echo htmlspecialchars($userData['company_phone']); ?></p>
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
              <li><a href="company-org-profile.php">company</a></li>
              <li><a href="company-overview.php">interns</a></li>
              <li><a href="company-profile.php">personal info</a></li>
              <li><a href="company-account.php">account</a></li>
            </ul>
          </nav>
          <h4 class="label">Company Information</h4>
          <h5 class="sub-label">Edit your name, address, and other company info.</h5>
          <div class="form-container">
            <form action="update_org_profile.php" method="POST">

            <div class="input-container full-width">
                <label class="input-label" for="company_name">Company Name</label>
                <input class="input-field" type="text" id="company_name" name="company_name" 
                    value="<?php echo htmlspecialchars($userData['company_name']); ?>" 
                    placeholder="Company Name here" 
                    oninput="capitalizeFirstLetter(this)">
            </div>

            <div class="input-container full-width">
                <label class="input-label" for="company_address">Address</label>
                <input class="input-field" type="text" id="company_address" name="company_address" 
                    value="<?php echo htmlspecialchars($userData['company_address']); ?>" 
                    placeholder="Address here" 
                    onkeypress="allowOnlyLettersAndSpace(event)" 
                    oninput="capitalizeFirstLetter(this)">
            </div>

            <div class="input-container">
                <label class="input-label" for="company_phone">Contact</label>
                <input class="input-field" type="tel" id="company_phone" name="company_phone" 
                    value="<?php echo htmlspecialchars($userData['company_phone']); ?>" 
                    placeholder="Contact Number here" 
                    maxlength="15" 
                    onkeypress="allowOnlyNumbers(event)">
            </div>

            <div class="input-container">
            <label class="input-label" for="company_email">Company Website</label>
                <input class="input-field" type="text" id="company_email" name="company_email" 
                    value="<?php echo htmlspecialchars($userData['company_email']); ?>" 
                    placeholder="Company Website here">
            </div>

            <div class="input-container full-width">

            </div>

                <button type="submit" class="save-button">Update</button>
            </form>
            <script>
                  $(document).ready(function() {
    // Add error message elements after each input container
    $('.input-container:has(#company_name)').append('<span class="error-message" id="companyNameError">This field is required</span>');
    $('.input-container:has(#company_address)').append('<span class="error-message" id="companyAddressError">This field is required</span>');
    $('.input-container:has(#company_phone)').append('<span class="error-message" id="companyPhoneError">This field is required</span>');
    $('.input-container:has(#company_email)').append('<span class="error-message" id="companyWebsiteError">This field is required</span>');
    
    // Track if fields have been modified
    let companyNameModified = false;
    let companyAddressModified = false;
    let companyPhoneModified = false;
    let companyWebsiteModified = false;
    
    // Hide all error messages initially
    $('.error-message').hide();
    
    // Input restrictions
    window.allowOnlyLettersAndSpace = function(event) {
        if (!/[a-zA-Z0-9\s]/.test(event.key)) {
            event.preventDefault();
        }
    }

    window.allowOnlyLetters = function(event) {
        if (!/[a-zA-Z0-9]/.test(event.key)) {
            event.preventDefault();
        }
    }

    window.capitalizeFirstLetter = function(input) {
        input.value = input.value.charAt(0).toUpperCase() + input.value.slice(1);
    }
    
    // Validation functions
    function validateCompanyName(showValid = true) {
        const $input = $('#company_name');
        const $error = $('#companyNameError');
        const value = $input.val();
        
        if (value.length === 0 && (companyNameModified || $('form').data('submitted'))) {
            $error.text("Company name is required");
            $error.show();
            $input.addClass('error').removeClass('valid');
            return false;
        } else if (value.length < 2 && value.length > 0) {
            $error.text("Company name must be at least 2 characters");
            $error.show();
            $input.addClass('error').removeClass('valid');
            return false;
        }
        
        $error.hide();
        $input.removeClass('error');
        if (showValid) $input.addClass('valid');
        return true;
    }
    
    function validateCompanyAddress(showValid = true) {
        const $input = $('#company_address');
        const $error = $('#companyAddressError');
        const value = $input.val();
        
        if (value.length === 0 && (companyAddressModified || $('form').data('submitted'))) {
            $error.text("Company address is required");
            $error.show();
            $input.addClass('error').removeClass('valid');
            return false;
        } else if (value.length < 5 && value.length > 0) {
            $error.text("Please enter a complete address");
            $error.show();
            $input.addClass('error').removeClass('valid');
            return false;
        }
        
        $error.hide();
        $input.removeClass('error');
        if (showValid) $input.addClass('valid');
        return true;
    }
    
    function validateCompanyPhone(showValid = true) {
        const $input = $('#company_phone');
        const $error = $('#companyPhoneError');
        const value = $input.val();
        
        if (value.length === 0 && (companyPhoneModified || $('form').data('submitted'))) {
            $error.text("Company phone is required");
            $error.show();
            $input.addClass('error').removeClass('valid');
            return false;
        } else if (value.length > 0 && value.length < 7) {
            $error.text("Please enter a valid phone number");
            $error.show();
            $input.addClass('error').removeClass('valid');
            return false;
        }
        
        $error.hide();
        $input.removeClass('error');
        if (showValid) $input.addClass('valid');
        return true;
    }
    
    function validateCompanyWebsite(showValid = true) {
        const $input = $('#company_email');
        const $error = $('#companyWebsiteError');
        const value = $input.val().trim();
        
        if (value.length === 0 && (companyWebsiteModified || $('form').data('submitted'))) {
            $error.text("Company website is required");
            $error.show();
            $input.addClass('error').removeClass('valid');
            return false;
        } else if (value.length > 0 && !isValidWebsite(value)) {
            $error.text("Please enter a valid website URL");
            $error.show();
            $input.addClass('error').removeClass('valid');
            return false;
        }
        
        $error.hide();
        $input.removeClass('error');
        if (showValid) $input.addClass('valid');
        return true;
    }
    
    function isValidWebsite(url) {
        const urlRegex = /^(https?:\/\/)?([\da-z.-]+)\.([a-z.]{2,6})([/\w .-]*)*\/?$/;
        return urlRegex.test(url);
    }
    
    // Add input event listeners
    $('#company_name').on('input', function() {
        companyNameModified = true;
        validateCompanyName(true);
    });
    
    $('#company_address').on('input', function() {
        companyAddressModified = true;
        validateCompanyAddress(true);
    });
    
    $('#company_phone').on('input', function() {
        companyPhoneModified = true;
        validateCompanyPhone(true);
    });
    
    $('#company_email').on('input', function() {
        companyWebsiteModified = true;
        validateCompanyWebsite(true);
    });
    
    // Add cut/paste event listeners
    $('#company_name, #company_address, #company_phone, #company_email').on('cut paste', function() {
        const field = $(this).attr('id');
        setTimeout(() => {
            switch(field) {
                case 'company_name':
                    companyNameModified = true;
                    validateCompanyName(true);
                    break;
                case 'company_address':
                    companyAddressModified = true;
                    validateCompanyAddress(true);
                    break;
                case 'company_phone':
                    companyPhoneModified = true;
                    validateCompanyPhone(true);
                    break;
                case 'company_email':
                    companyWebsiteModified = true;
                    validateCompanyWebsite(true);
                    break;
            }
        }, 0);
    });
    
    // Add blur event listeners
    $('#company_name').on('blur', function() {
        companyNameModified = true;
        validateCompanyName(false);
        $(this).removeClass('valid');
    });
    
    $('#company_address').on('blur', function() {
        companyAddressModified = true;
        validateCompanyAddress(false);
        $(this).removeClass('valid');
    });
    
    $('#company_phone').on('blur', function() {
        companyPhoneModified = true;
        validateCompanyPhone(false);
        $(this).removeClass('valid');
    });
    
    $('#company_email').on('blur', function() {
        companyWebsiteModified = true;
        validateCompanyWebsite(false);
        $(this).removeClass('valid');
    });
    
    // Form submission handler
    $('form[action="update_org_profile.php"]').on('submit', function(e) {
        e.preventDefault();
        
        // Mark form as submitted to show all validation errors
        $(this).data('submitted', true);
        
        // Validate all fields
        const isCompanyNameValid = validateCompanyName(true);
        const isCompanyAddressValid = validateCompanyAddress(true);
        const isCompanyPhoneValid = validateCompanyPhone(true);
        const isCompanyWebsiteValid = validateCompanyWebsite(true);
        
        if (isCompanyNameValid && isCompanyAddressValid && 
            isCompanyPhoneValid && isCompanyWebsiteValid) {
            // If all validations pass, submit the form
            $(e.target).off('submit').submit();
        }
    });
});
            </script>
        </div>
   


            
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
                (function(){if(!window.chatbase||window.chatbase("getState")!=="initialized"){window.chatbase=(...arguments)=>{if(!window.chatbase.q){window.chatbase.q=[]}window.chatbase.q.push(arguments)};window.chatbase=new Proxy(window.chatbase,{get(target,prop){if(prop==="q"){return target.q}return(...args)=>target(prop,...args)}})}const onLoad=function(){const script=document.createElement("script");script.src="https://www.chatbase.co/embed.min.js";script.id="sqZ5VD70WA_0wO97JZLEZ";script.domain="www.chatbase.co";document.body.appendChild(script)};if(document.readyState==="complete"){onLoad()}else{window.addEventListener("load",onLoad)}})();
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
                            <li><a href="companyloginpage.php#advertise">Internship by Company</a></li>
                            <li><a href="companyloginpage.php#advertise">Internship by City</a></li>
                            <li><a href="companyloginpage.php#advertise">Search Nearby Internship</a></li>
                        </ul>
                    </div>
                
                    <!-- Employers Section -->
                    <div class="centerside">
                        <h4>EMPLOYERS</h4>
                        <ul>
                            <li><a href="companyloginpage.php">Post Internships</a></li>
                        </ul>
                    </div>
                
                    <!-- About Interflo Section -->
                    <div class="centerside">
                        <h4>ABOUT INTERNFLO</h4>
                        <ul>
                            <li><a href="companyloginpage.php#about">About Us</a></li>
                            <li><a href="companyloginpage.php#chatbot">How It Works</a></li>
                            <li><a href="companyloginpage.php#contact">Contact Us</a></li>
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

          

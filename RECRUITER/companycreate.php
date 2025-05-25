<?php
    session_start();

    // Initialize database connection
    $host = 'localhost';
    $dbname = 'u798912504_internflo';
    $username = 'u798912504_root';
    $password = 'Internfloucc2025*';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
        try {
            // Get form data
            $firstName = $_POST['firstName'];
            $middleName = $_POST['middleName'];
            $lastName = $_POST['lastName'];
            $suffix = $_POST['suffix'];
            $companyName = $_POST['companyName'];
            $industry = $_POST['industry'];
            $companyNumber = $_POST['companyNumber']; // Added company phone number
            $companyEmail = $_POST['companyEmail']; // Added company email
            $companyOverview = $_POST['companyOverview'];
            $companyAddress = $_POST['company-address'];
            $latitude = $_POST['latitude'];
            $longitude = $_POST['longitude'];
            $email = $_POST['email'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $mobileNumber = $_POST['mobileNumber'];

            // Handle Company Logo upload
            $profilePhotoPath = "";
            if (isset($_FILES['profilePhoto']) && $_FILES['profilePhoto']['error'] == 0) {
                $targetDir = "companyprofile/";
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                $profilePhotoPath = $targetDir . basename($_FILES["profilePhoto"]["name"]);
                move_uploaded_file($_FILES["profilePhoto"]["tmp_name"], $profilePhotoPath);
            }

            // Handle other file uploads
            $certRegPath = "";
            if (isset($_FILES['certReg']) && $_FILES['certReg']['error'] == 0) {
                $targetDir = "companycert/";
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                $certRegPath = $targetDir . basename($_FILES["certReg"]["name"]);
                move_uploaded_file($_FILES["certReg"]["tmp_name"], $certRegPath);
            }

            $birRegPath = "";
            if (isset($_FILES['birReg']) && $_FILES['birReg']['error'] == 0) {
                $targetDir = "bir/";
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                $birRegPath = $targetDir . basename($_FILES["birReg"]["name"]);
                move_uploaded_file($_FILES["birReg"]["tmp_name"], $birRegPath);
            }

            $permitPath = "";
            if (isset($_FILES['permit']) && $_FILES['permit']['error'] == 0) {
                $targetDir = "companypermit/";
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                $permitPath = $targetDir . basename($_FILES["permit"]["name"]);
                move_uploaded_file($_FILES["permit"]["tmp_name"], $permitPath);
            }

            // Generate a verification token
            $verification_token = bin2hex(random_bytes(32));

            // Insert user data into the unverified_recruiters table with the verification token
            $sql = "INSERT INTO unverified_recruiters (
                first_name, middle_name, last_name, suffix, 
                company_name, industry, company_phone, company_email, 
                company_overview, company_address, 
                latitude, longitude, company_logo, 
                certificate_of_registration, bir_registration, business_permit,
                email, password, mobile_number, verification_token, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $firstName, $middleName, $lastName, $suffix,
                $companyName, $industry, $companyNumber, $companyEmail,
                $companyOverview, $companyAddress,
                $latitude, $longitude, $profilePhotoPath,
                $certRegPath, $birRegPath, $permitPath,
                $email, $password, $mobileNumber, $verification_token
            ]);

            // Set session variables
            $_SESSION['verification_token'] = $verification_token;
            $_SESSION['user_email'] = $email;

            // Redirect to the email verification page
            header("Location: companyemailverification.php");
            exit();

        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UCC - Company Registration</title>
    <link rel="stylesheet" href="NAV.css">
    <link rel="stylesheet" href="FOOTER.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500&display=swap" rel="stylesheet">
    <link rel="icon" href="pics/ucc.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="companycreate.css">
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
                <!-- Alert -->
                <div class="alert" >
                        <div class="alert__wrapper">
                            <span class="alert__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#856404"><path d="M800-514.5q-19.15 0-32.33-13.17Q754.5-540.85 754.5-560t13.17-32.33Q780.85-605.5 800-605.5t32.33 13.17Q845.5-579.15 845.5-560t-13.17 32.33Q819.15-514.5 800-514.5Zm0-131q-19.15 0-32.33-13.17Q754.5-671.85 754.5-691v-120q0-19.15 13.17-32.33Q780.85-856.5 800-856.5t32.33 13.17Q845.5-830.15 845.5-811v120q0 19.15-13.17 32.33Q819.15-645.5 800-645.5ZM360.72-484.07q-69.59 0-118.86-49.27-49.27-49.27-49.27-118.86 0-69.58 49.27-118.74 49.27-49.15 118.86-49.15 69.58 0 118.86 49.15 49.27 49.16 49.27 118.74 0 69.59-49.27 118.86-49.28 49.27-118.86 49.27ZM32.59-238.8v-29.61q0-36.16 18.69-66.57 18.7-30.41 49.85-46.37 62.72-31.24 127.67-46.98 64.96-15.74 131.92-15.74 67.43 0 132.39 15.62 64.96 15.62 127.19 46.86 31.16 15.96 49.85 46.25 18.7 30.3 18.7 66.93v29.61q0 37.78-26.61 64.39t-64.39 26.61H123.59q-37.79 0-64.39-26.61-26.61-26.61-26.61-64.39Z"/></svg>
                            </span>
                            <p class="alert__message">
                                Registered account will undergo verification before you can create an internship ad.
                            </p>
                        </div>
                </div>

            <div class="form-section">
                <div class="form-container">
                    <div class="bgwidth">
                        <h1>Create <span class="highlight"> Organization</span> Account</h1>
                        <p>Fill in the following to create a Organization account</p>
                    </div>
                    <!-- Stepper -->
                    <div class="stepper">
                        <div class="step-indicator active" data-step="1">
                            <div class="step-circle">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ddd">
                                    <path d="M480-280q83 0 141.5-58.5T680-480q0-83-58.5-141.5T480-680q-83 0-141.5 58.5T280-480q0 83 58.5 141.5T480-280Zm0 200q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                                </svg>
                            </div>
                            <div class="step-text">Contact Info</div>
                        </div>
                        <div class="step-indicator" data-step="2">
                            <div class="step-circle">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ddd">
                                    <path d="M480-280q83 0 141.5-58.5T680-480q0-83-58.5-141.5T480-680q-83 0-141.5 58.5T280-480q0 83 58.5 141.5T480-280Zm0 200q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                                </svg>
                            </div>
                            <div class="step-text">Organization Info</div>
                        </div>
                        <div class="step-indicator" data-step="3">
                            <div class="step-circle">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ddd">
                                    <path d="M480-280q83 0 141.5-58.5T680-480q0-83-58.5-141.5T480-680q-83 0-141.5 58.5T280-480q0 83 58.5 141.5T480-280Zm0 200q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                                </svg>
                            </div>
                            <div class="step-text">Verification</div>
                        </div>
                        <div class="step-indicator" data-step="4">
                            <div class="step-circle">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ddd">
                                    <path d="M480-280q83 0 141.5-58.5T680-480q0-83-58.5-141.5T480-680q-83 0-141.5 58.5T280-480q0 83 58.5 141.5T480-280Zm0 200q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                                </svg>
                            </div>
                            <div class="step-text">Account</div>
                        </div>
                    </div>

                    <form id="multi-step-form" method="POST" enctype="multipart/form-data" novalidate>
                        <!-- Step 1: Personal Info -->
                        <div class="form-step active" id="step-1">
                            <h2>Primary Contact Person</h2>

                            <div class="form-fields">
                                <div class="input-container">
                                    <input type="text" name="firstName" placeholder="First Name" onkeypress="allowOnlyLetters(event)" oninput="capitalizeFirstLetter(this)">
                                </div>
                                <div class="input-container">
                                    <input type="text" name="lastName" placeholder="Last Name" onkeypress="allowOnlyLetters(event)" oninput="capitalizeFirstLetter(this)">
                                </div>
                                <div class="input-container">
                                    <input type="text" name="middleName" placeholder="Middle Name (Optional)" onkeypress="allowOnlyLetters(event)" oninput="capitalizeFirstLetter(this)">
                                </div>
                                <div class="input-container">
                                    <input type="text" name="suffix" placeholder="Suffix (Optional)" onkeypress="allowOnlyLetters(event)" oninput="capitalizeFirstLetter(this)">
                                </div>
                            </div>


                            
                            <div class="button-group">
                                <button type="button" class="prev-btn" style="display: none;"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="m318.98-480 151.41 152.17q12.68 12.68 13.06 31.45.38 18.77-13.06 32.21-12.67 12.67-31.82 12.67-19.16 0-31.83-12.67L222.98-447.93q-6.72-6.72-9.82-14.92-3.09-8.19-3.09-17.15 0-8.96 3.09-17.15 3.1-8.2 9.82-14.92l183.76-183.76q12.67-12.67 31.44-13.05 18.78-.38 32.21 13.05 12.68 12.68 12.68 31.83t-12.68 31.83L318.98-480Zm267.11 0 151.17 152.17q12.67 12.68 13.06 31.45.38 18.77-13.06 32.21-12.67 12.67-31.83 12.67-19.15 0-31.82-12.67L489.85-447.93q-6.72-6.72-9.82-14.92-3.1-8.19-3.1-17.15 0-8.96 3.1-17.15 3.1-8.2 9.82-14.92l183.76-183.76q12.67-12.67 31.44-13.05 18.78-.38 32.21 13.05 12.67 12.68 12.67 31.83t-12.67 31.83L586.09-480Z"/></svg> Previous</button>
                                <button type="button" class="next-btn">Next <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M373.91-480 222.74-632.17q-12.67-12.68-13.06-31.45-.38-18.77 13.06-32.21 12.67-12.67 31.83-12.67 19.15 0 31.82 12.67l183.76 183.76q6.72 6.72 9.82 14.92 3.1 8.19 3.1 17.15 0 8.96-3.1 17.15-3.1 8.2-9.82 14.92L286.39-264.17q-12.67 12.67-31.44 13.05-18.78.38-32.21-13.05-12.67-12.68-12.67-31.83t12.67-31.83L373.91-480Zm267.11 0L489.61-632.17q-12.68-12.68-13.06-31.45-.38-18.77 13.06-32.21 12.67-12.67 31.82-12.67 19.16 0 31.83 12.67l183.76 183.76q6.72 6.72 9.82 14.92 3.09 8.19 3.09 17.15 0 8.96-3.09 17.15-3.1 8.2-9.82 14.92L553.26-264.17q-12.67 12.67-31.44 13.05-18.78.38-32.21-13.05-12.68-12.68-12.68-31.83t12.68-31.83L641.02-480Z"/></svg></button>
                            </div>
                        </div>

                        <!-- Step 2: Company Info -->
                        <div class="form-step" id="step-2">
                            <h2>Organization Information</h2>
                            
                            <!-- Company Logo Upload - Centered -->
                            <div class="input-norm">
                                <label class="section-label-logo">Organization Logo</label>
                                <div class="logo-container-org">
                                    <div class="file-input-wrapper">
                                        <input type="file" name="profilePhoto" id="profilePhoto" accept="image/*" style="display: none;">
                                        <label for="profilePhoto" class="custom-file-upload">
                                            <div class="upload-content">
                                                <svg xmlns="http://www.w3.org/2000/svg" height="100px" viewBox="0 -960 960 960" width="100px" fill="currentColor" class="upload-icon">
                                                    <path d="M480-480ZM200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h320v80H200v560h560v-320h80v320q0 33-23.5 56.5T760-120H200Zm40-160h480L570-480 450-320l-90-120-120 160Zm440-320v-80h-80v-80h80v-80h80v80h80v80h-80v80h-80Z"/>
                                                </svg>
                                            </div>
                                            <img id="preview-image" class="preview-image" src="" alt="Preview">
                                        </label>
                                        <button type="button" class="remove-image" style="display: none;">×</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Company Name and Industry in one line -->
                            <div class="input-row">
                                <div class="input-container">
                                    <label class="section-label">Organization Name</label>
                                    <input type="text" name="companyName" placeholder="Enter Company Name" oninput="capitalizeFirstLetter(this)">
                                </div>
                                <div class="input-container">
                                    <label class="section-label">General Industry</label>
                                    <select name="industry">
                                        <option value="" disabled selected>Select Organization Industry</option>
                                        <option value="Institution">Institution</option>
                                        <option value="Technology">Technology</option>
                                        <option value="Healthcare">Healthcare</option>
                                        <option value="Finance">Finance</option>
                                        <option value="Entertainment">Entertainment</option>
                                        <option value="Marketing">Marketing</option>
                                        <option value="Law Enforcement">Law Enforcement</option>
                                        <option value="Civil Society">Civil Society</option>
                                        <option value="Health and Human Services">Health and Human Services</option>
                                        <option value="Advertisement">Advertisement</option>
                                        <option value="Media">Media</option>
                                    </select>

                                </div>
                            </div>


                            <div class="input-norm">
                                <label class="section-label">Contact Information</label>
                                <div class="input-row">
                                    <div class="input-container">
                                        <input type="text" name="companyNumber" placeholder="Official Telephone Number" maxlength="10" onkeypress="allowOnlyNumbers(event)">
                                    </div>
                                    <div class="input-container">
                                        <input type="text" name="companyEmail" placeholder="Official Website">
                                    </div>
                                </div>
                            </div>


                            <div class="input-norm">
                                <label class="section-label">Organization Address</label>
                                <div class="input-row">
                                    <div class="input-container">
                                        <input type="text" id="company-address" name="company-address" placeholder="Enter Address">
                                    </div>
                                    <div class="input-container">
                                        <button type="button" class="map-button" id="mapButton">
                                            <span class="button-content">
                                                Mark Location on Map
                                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
                                                    <path d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q146 0 255.5 91.5T872-559h-82q-19-73-68.5-130.5T600-776v16q0 33-23.5 56.5T520-680h-80v80q0 17-11.5 28.5T400-560h-80v80h80v120h-40L168-552q-3 18-5.5 36t-2.5 36q0 131 92 225t228 95v80Zm364-20L716-228q-21 12-45 20t-51 8q-75 0-127.5-52.5T440-380q0-75 52.5-127.5T620-560q75 0 127.5 52.5T800-380q0 27-8 51t-20 45l128 128-56 56ZM620-280q42 0 71-29t29-71q0-42-29-71t-71-29q-42 0-71 29t-29 71q0 42 29 71t71 29Z"/>
                                                </svg>
                                            </span>
                                        </button>
                                        <!-- Hidden Latitude and Longitude Inputs -->
                                        <input type="text" id="latitude" name="latitude" style="display: none;">
                                        <input type="text" id="longitude" name="longitude" style="display: none;">
                                    </div>
                                </div>
                                <div class="tip">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#002b7f"><path d="M480-280q17 0 28.5-11.5T520-320v-160q0-17-11.5-28.5T480-520q-17 0-28.5 11.5T440-480v160q0 17 11.5 28.5T480-280Zm0-320q17 0 28.5-11.5T520-640q0-17-11.5-28.5T480-680q-17 0-28.5 11.5T440-640q0 17 11.5 28.5T480-600Zm0 520q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/></svg>  
                                    Easily set your organization's address by clicking 'Mark Location on Map.'
                                    </div>
                                </div>


                            <!-- Company Overview -->
                            <div class="input-norm">
                                <label class="section-label">Organization Overview</label>
                                <div class="input-container">
                                    <div class="editor-container">
                                        <div class="toolbar">
                                            <button type="button" onclick="execCommand('undo')" title="Undo"><i>↩</i></button>
                                            <button type="button" onclick="execCommand('redo')" title="Redo"><i>↪</i></button>

                                            <button type="button" onclick="execCommand('bold')" title="Bold"><i>B</i></button>
                                            <button type="button" onclick="execCommand('italic')" title="Italic"><i>I</i></button>
                                            <button type="button" onclick="execCommand('underline')" title="Underline"><i>U</i></button>

                                            <button type="button" onclick="execCommand('insertUnorderedList')" title="Bullet List"><i>•</i></button>
                                            <button type="button" onclick="execCommand('insertOrderedList')" title="Numbered List"><i>#</i></button>
                                        </div>
                                        <div class="editor" id="company-overview-editor" contenteditable="true" data-placeholder="Responsibilities and tasks"></div>
                                        <input type="hidden" id="companyOverview" name="companyOverview">
                                    </div>
                                </div>
                            </div>

                            <div class="date-banner">
                                <p class="date-banner-text">
                                    <span class="date-banner-content">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#666666"><path d="M479.89-240Q500-240 514-253.89q14-13.88 14-34Q528-308 514.11-322q-13.88-14-34-14Q460-336 446-322.11q-14 13.88-14 34Q432-268 445.89-254q13.88 14 34 14Zm.39 144Q401-96 331-126t-122.5-82.5Q156-261 126-330.96t-30-149.5Q96-560 126-629.5q30-69.5 82.5-122T330.96-834q69.96-30 149.5-30t149.04 30q69.5 30 122 82.5T834-629.28q30 69.73 30 149Q864-401 834-331t-82.5 122.5Q699-156 629.28-126q-69.73 30-149 30Zm2.93-554q24.33 0 43.56 15.18Q546-619.64 546-596.87 546-576 533.31-559q-12.7 17-29.31 31-23 20-42 44t-19 54q0 15 10.68 25t24.92 10q16.07 0 27.23-10.5Q517-416 520-432q4-21 18-37.14 14-16.13 30-30.8 23-21.06 39-47.75T623-605q0-51-41.5-83.5T484.91-721q-38.06 0-71.98 17-33.93 17-56.09 49.27-7.84 10.81-4.34 23.77Q356-618 367-609q14 11 30 6.5t27-18.5q11-14 26.35-21.5 15.35-7.5 32.86-7.5Z"/></svg>
                                        <span class="text">Applicants can view your organization's location in real time on the map.</span>
                                    </span>
                                </p>
                            </div>

                            <!-- Navigation Buttons -->
                            <div class="button-group">
                                <button type="button" class="prev-btn"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="m318.98-480 151.41 152.17q12.68 12.68 13.06 31.45.38 18.77-13.06 32.21-12.67 12.67-31.82 12.67-19.16 0-31.83-12.67L222.98-447.93q-6.72-6.72-9.82-14.92-3.09-8.19-3.09-17.15 0-8.96 3.09-17.15 3.1-8.2 9.82-14.92l183.76-183.76q12.67-12.67 31.44-13.05 18.78-.38 32.21 13.05 12.68 12.68 12.68 31.83t-12.68 31.83L318.98-480Zm267.11 0 151.17 152.17q12.67 12.68 13.06 31.45.38 18.77-13.06 32.21-12.67 12.67-31.83 12.67-19.15 0-31.82-12.67L489.85-447.93q-6.72-6.72-9.82-14.92-3.1-8.19-3.1-17.15 0-8.96 3.1-17.15 3.1-8.2 9.82-14.92l183.76-183.76q12.67-12.67 31.44-13.05 18.78-.38 32.21 13.05 12.67 12.68 12.67 31.83t-12.67 31.83L586.09-480Z"/></svg> Previous</button>
                                <button type="button" class="next-btn">Next <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M373.91-480 222.74-632.17q-12.67-12.68-13.06-31.45-.38-18.77 13.06-32.21 12.67-12.67 31.83-12.67 19.15 0 31.82 12.67l183.76 183.76q6.72 6.72 9.82 14.92 3.1 8.19 3.1 17.15 0 8.96-3.1 17.15-3.1 8.2-9.82 14.92L286.39-264.17q-12.67 12.67-31.44 13.05-18.78.38-32.21-13.05-12.67-12.68-12.67-31.83t12.67-31.83L373.91-480Zm267.11 0L489.61-632.17q-12.68-12.68-13.06-31.45-.38-18.77 13.06-32.21 12.67-12.67 31.82-12.67 19.16 0 31.83 12.67l183.76 183.76q6.72 6.72 9.82 14.92 3.09 8.19 3.09 17.15 0 8.96-3.09 17.15-3.1 8.2-9.82 14.92L553.26-264.17q-12.67 12.67-31.44 13.05-18.78.38-32.21-13.05-12.68-12.68-12.68-31.83t12.68-31.83L641.02-480Z"/></svg></button>
                            </div>
                        </div>




                        <!-- Step 3: Company Verification -->
                        <div class="form-step" id="step-3">
                            <h2>Organization Verification</h2>
                            <div class="input-norm-verification">
                                <div class="input-container">
                                    <div class="file-input">
                                        <label>Certificate of Registration</label>
                                        <input type="file" name="certReg" accept=".pdf">
                                    </div>
                                </div>
                            </div>
                            <div class="input-norm-verification">
                                <div class="input-container">
                                    <div class="file-input">
                                        <label>BIR Registration</label>
                                        <input type="file" name="birReg" accept=".pdf">
                                    </div>
                                </div>
                            </div>
                            <div class="input-norm-verification">
                                <div class="input-container">
                                    <div class="file-input">
                                        <label>Business Permit</label>
                                        <input type="file" name="permit" accept=".pdf">
                                    </div>
                                </div>
                            </div>
                            <div class="tip">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#002b7f"><path d="M480-280q17 0 28.5-11.5T520-320v-160q0-17-11.5-28.5T480-520q-17 0-28.5 11.5T440-480v160q0 17 11.5 28.5T480-280Zm0-320q17 0 28.5-11.5T520-640q0-17-11.5-28.5T480-680q-17 0-28.5 11.5T440-640q0 17 11.5 28.5T480-600Zm0 520q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/></svg>  
                                    Please ensure you provide the relevant documents for your application.
                                </div>
                            <div class="button-group">
                                <button type="button" class="prev-btn"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="m318.98-480 151.41 152.17q12.68 12.68 13.06 31.45.38 18.77-13.06 32.21-12.67 12.67-31.82 12.67-19.16 0-31.83-12.67L222.98-447.93q-6.72-6.72-9.82-14.92-3.09-8.19-3.09-17.15 0-8.96 3.09-17.15 3.1-8.2 9.82-14.92l183.76-183.76q12.67-12.67 31.44-13.05 18.78-.38 32.21 13.05 12.68 12.68 12.68 31.83t-12.68 31.83L318.98-480Zm267.11 0 151.17 152.17q12.67 12.68 13.06 31.45.38 18.77-13.06 32.21-12.67 12.67-31.83 12.67-19.15 0-31.82-12.67L489.85-447.93q-6.72-6.72-9.82-14.92-3.1-8.19-3.1-17.15 0-8.96 3.1-17.15 3.1-8.2 9.82-14.92l183.76-183.76q12.67-12.67 31.44-13.05 18.78-.38 32.21 13.05 12.67 12.68 12.67 31.83t-12.67 31.83L586.09-480Z"/></svg> Previous</button>
                                <button type="button" class="next-btn">Next <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M373.91-480 222.74-632.17q-12.67-12.68-13.06-31.45-.38-18.77 13.06-32.21 12.67-12.67 31.83-12.67 19.15 0 31.82 12.67l183.76 183.76q6.72 6.72 9.82 14.92 3.1 8.19 3.1 17.15 0 8.96-3.1 17.15-3.1 8.2-9.82 14.92L286.39-264.17q-12.67 12.67-31.44 13.05-18.78.38-32.21-13.05-12.67-12.68-12.67-31.83t12.67-31.83L373.91-480Zm267.11 0L489.61-632.17q-12.68-12.68-13.06-31.45-.38-18.77 13.06-32.21 12.67-12.67 31.82-12.67 19.16 0 31.83 12.67l183.76 183.76q6.72 6.72 9.82 14.92 3.09 8.19 3.09 17.15 0 8.96-3.09 17.15-3.1 8.2-9.82 14.92L553.26-264.17q-12.67 12.67-31.44 13.05-18.78.38-32.21-13.05-12.68-12.68-12.68-31.83t12.68-31.83L641.02-480Z"/></svg></button>
                            </div>
                        </div>



                        <!-- Step 4: Account -->
                        <div class="form-step" id="step-4">

                            <h2>Account Creation</h2>
                            <div class="note">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ff8c00"><path d="M478-240q21 0 35.5-14.5T528-290q0-21-14.5-35.5T478-340q-21 0-35.5 14.5T428-290q0 21 14.5 35.5T478-240Zm2 160q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm4-572q25 0 43.5 16t18.5 40q0 22-13.5 39T502-525q-23 20-40.5 44T444-427q0 14 10.5 23.5T479-394q15 0 25.5-10t13.5-25q4-21 18-37.5t30-31.5q23-22 39.5-48t16.5-58q0-51-41.5-83.5T484-720q-38 0-72.5 16T359-655q-7 12-4.5 25.5T368-609q14 8 29 5t25-17q11-15 27.5-23t34.5-8Z"/></svg>
                                Ensure your contact information is active for account verification.
                            </div>
                            <div class="input-norm">
                                <div class="input-container">
                                    <input type="email" name="email" placeholder="Email">
                                </div>
                            </div>
                            <div class="input-norm">
                                <div class="input-container">
                                    <input type="tel" name="mobileNumber" placeholder="Mobile Number" maxlength="11" onkeypress="allowOnlyNumbers(event)">
                                </div>
                            </div>
   
                            <div class="input-norm-password">
                                <div class="password-container">
                                    <input type="password" name="password" placeholder="Create Password">
                                    <i class="toggle-password fas fa-eye-slash"></i>
                                    <span class="error-message"></span>
                                </div>
                            </div>
                            
                            <div class="input-norm-password">
                                <div class="password-container">
                                    <input type="password" name="confirmPassword" placeholder="Confirm Password">
                                    <i class="toggle-password fas fa-eye-slash"></i>
                                    <span class="error-message"></span>
                                </div>
                            </div>
                            <div class="button-group">
                                <button type="button" class="prev-btn"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="m318.98-480 151.41 152.17q12.68 12.68 13.06 31.45.38 18.77-13.06 32.21-12.67 12.67-31.82 12.67-19.16 0-31.83-12.67L222.98-447.93q-6.72-6.72-9.82-14.92-3.09-8.19-3.09-17.15 0-8.96 3.09-17.15 3.1-8.2 9.82-14.92l183.76-183.76q12.67-12.67 31.44-13.05 18.78-.38 32.21 13.05 12.68 12.68 12.68 31.83t-12.68 31.83L318.98-480Zm267.11 0 151.17 152.17q12.67 12.68 13.06 31.45.38 18.77-13.06 32.21-12.67 12.67-31.83 12.67-19.15 0-31.82-12.67L489.85-447.93q-6.72-6.72-9.82-14.92-3.1-8.19-3.1-17.15 0-8.96 3.1-17.15 3.1-8.2 9.82-14.92l183.76-183.76q12.67-12.67 31.44-13.05 18.78-.38 32.21 13.05 12.67 12.68 12.67 31.83t-12.67 31.83L586.09-480Z"/></svg> Previous</button>
                                <button type="submit" name="submit" class="next-btn">Register <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M236.44-152.59q-34.46 0-59.16-24.69-24.69-24.7-24.69-59.16 0-34.47 24.69-59.02Q201.98-320 236.44-320q34.47 0 59.02 24.54Q320-270.91 320-236.44q0 34.46-24.54 59.16-24.55 24.69-59.02 24.69Zm0-243.82q-34.46 0-59.16-24.55-24.69-24.54-24.69-59.01 0-34.46 24.69-59.04 24.7-24.58 59.16-24.58 34.47 0 59.02 24.55Q320-514.5 320-480.03q0 34.46-24.54 59.04-24.55 24.58-59.02 24.58Zm0-243.59q-34.46 0-59.16-24.54-24.69-24.55-24.69-59.02 0-34.46 24.69-59.16 24.7-24.69 59.16-24.69 34.47 0 59.02 24.69Q320-758.02 320-723.56q0 34.47-24.54 59.02Q270.91-640 236.44-640Zm243.59 0q-34.46 0-59.04-24.54-24.58-24.55-24.58-59.02 0-34.46 24.55-59.16 24.54-24.69 59.01-24.69 34.46 0 59.04 24.69 24.58 24.7 24.58 59.16 0 34.47-24.55 59.02Q514.5-640 480.03-640Zm243.53 0q-34.47 0-59.02-24.54Q640-689.09 640-723.56q0-34.46 24.54-59.16 24.55-24.69 59.02-24.69 34.46 0 59.16 24.69 24.69 24.7 24.69 59.16 0 34.47-24.69 59.02Q758.02-640 723.56-640ZM480.03-396.41q-34.46 0-59.04-24.55-24.58-24.54-24.58-59.01 0-34.46 24.55-59.04 24.54-24.58 59.01-24.58 34.46 0 59.04 24.55 24.58 24.54 24.58 59.01 0 34.46-24.55 59.04-24.54 24.58-59.01 24.58Zm38.54 198.32v-65.04q0-9.2 3.47-17.53 3.48-8.34 10.2-15.06l208.76-208q9.72-9.76 21.59-14.09 11.88-4.34 23.76-4.34 12.95 0 24.8 4.86 11.85 4.86 21.55 14.57l37 37q8.67 9.72 13.55 21.6 4.88 11.87 4.88 23.75 0 12.2-4.36 24.41-4.36 12.22-14.07 21.94l-208 208q-6.69 6.72-15.04 10.07-8.36 3.36-17.55 3.36h-65.04q-19.16 0-32.33-13.17-13.17-13.17-13.17-32.33Zm270.17-185.67 34.61-36.61-37-37-35.61 35.61 38 38Z"/></svg></button>
                            </div>
                            <!-- Terms acceptance wrapper -->
                            <div class="terms-policies-fold">
                                    <div class="terms-policies-wrapper">
                                        <input type="radio" id="terms-acceptance" name="terms" class="terms-policies-radio">
                                        <label for="terms-acceptance" class="terms-policies-text">
                                            By creating an account, you agree to abide by our 
                                            <a href="#" class="terms-link">Terms and Conditions</a> 
                                        </label>
                                    </div>
                                    <div id="radioError" class="form-error" style="display: none;"></div>
                            </div>
                        </div>
                    </form>

                    <div id="sign-in-section">
                        <div class="sign-in">
                            Already have an account? <a href="companysignin.php">Sign in</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const mapButton = document.getElementById('mapButton');
                                mapButton.addEventListener('click', function() {
                                    const width = 800;
                                    const height = 800;
                                    const left = (screen.width - width) / 2;
                                    const top = (screen.height - height) / 2;

                                    const address = document.getElementById('company-address').value;
                                    const encodedAddress = encodeURIComponent(address);

                                    window.open(
                                        `mapscompany.html?address=${encodedAddress}`, 
                                        'MapWindow', 
                                        `width=${width},height=${height},left=${left},top=${top}`
                                    );
                                });

                                // Example: Populate latitude and longitude dynamically (adjust to actual map integration)
                                function updateLatLong(lat, long) {
                                    document.getElementById('latitude').value = lat;
                                    document.getElementById('longitude').value = long;
                                }
                            });
                        </script>
    <script>
            document.addEventListener('DOMContentLoaded', function() {
            // Get references to the elements
            const industrySelect = document.querySelector('select[name="industry"]');
            const fileInputs = document.querySelectorAll('.input-norm-verification .file-input label');

            function updateVerificationLabels(selectedIndustry) {
                console.log('Industry selected:', selectedIndustry); // Debug line

                if (selectedIndustry === 'Institution') {
                    const newLabels = [
                        'CHED Recognition',
                        'School License',
                        'Department Authorization Letter'
                    ];
                    
                    fileInputs.forEach((label, index) => {
                        label.textContent = newLabels[index];
                    });
                } else {
                    const defaultLabels = [
                        'Certificate of Registration',
                        'BIR Registration',
                        'Business Permit'
                    ];
                    
                    fileInputs.forEach((label, index) => {
                        label.textContent = defaultLabels[index];
                    });
                }
            }

            // Add the change event listener
            if (industrySelect) {
                industrySelect.addEventListener('change', function(event) {
                    console.log('Change detected'); // Debug line
                    updateVerificationLabels(event.target.value);
                });
            }
});
    </script>

    <script>
    // Function to show the terms modal when radio button is clicked

    </script>
    <!-- TERMS AND CONDITIONS -->
    <div id="termsModal" class="terms-modal">
                    <div class="terms-modal-content">
                        <div class="terms-modal-width">
                            <div class="terms-header">
                                <h1>Intern<span class="highlight">flo.</span></h1>
                                <h2>University of Caloocan City</h2>

                            </div>
                            <div class="terms-body">
                                <p class="intro">Welcome to University of Caloocan City InternFlo. These Terms and Conditions ("Terms") govern your use of the Portal and the services provided through it. By accessing or using the Portal, you agree to abide by these Terms. If you do not agree, please do not use the Portal.</p>
                                <h3>Terms and Conditions</h3>
                                <div class="terms-section">
                                    <h3>Definitions</h3>
                                    <ul class="terms-list">
                                        <li><span class="strong">"Portal"</span> refers to the internship portal operated by Arcane.</li>
                                        <li><span class="strong">"User"</span> refers to any individual or entity accessing the Portal, including interns, employers, and educational institutions.</li>
                                        <li><span class="strong">"Content"</span> includes all information, materials, and data available on the Portal.</li>
                                    </ul>
                                </div>

                                <div class="terms-section">
                                    <h3>User Eligibility</h3>
                                    <p class="details">Users must meet the following criteria to use the Portal:</p>
                                    <ul class="terms-list">
                                        <li>Be a bonafide student of University of Caloocan City.</li>
                                        <li>Provide specific requirements for educational institutions.</li>
                                        <li>Provide accurate and complete information during registration.</li>
                                        <li>Comply with all applicable laws and regulations.</li>
                                    </ul>
                                </div>

                                <div class="terms-section">
                                    <h3>User Accounts</h3>
                                    <ul class="terms-list">
                                        <li>Users must create an account to access certain features of the Portal.</li>
                                        <li>Users are responsible for maintaining the confidentiality of their login credentials.</li>
                                        <li>Users must notify the Portal immediately in case of unauthorized account access.</li>
                                    </ul>
                                </div>

                                <div class="terms-section">
                                    <h3>Use of the Portal</h3>
                                    <ul class="terms-list">
                                        <li>The Portal is designed to facilitate connections between interns and employers.</li>
                                        <li>Users agree to use the Portal solely for lawful purposes.</li>
                                        <li>Users may not:
                                            <ul class="terms-sub-list">
                                                <li>Post false or misleading information.</li>
                                                <li>Share content that is offensive, defamatory, or violates any laws.</li>
                                                <li>Attempt to disrupt the Portal's functionality.</li>
                                            </ul>
                                        </li>
                                    </ul>
                                </div>

                                <div class="terms-section">
                                    <h3>Internships</h3>
                                    <ul class="terms-list">
                                        <li>The Portal acts as a medium for connecting interns and employers and does not guarantee internship placements.</li>
                                        <li>Employers are solely responsible for the internship opportunities they post.</li>
                                        <li>Interns are responsible for verifying the legitimacy of opportunities before applying.</li>
                                    </ul>
                                </div>

                                <div class="terms-section">
                                    <h3>Content Ownership and Use</h3>
                                    <ul class="terms-list">
                                        <li>Users retain ownership of the content they upload.</li>
                                        <li>By uploading content, Users grant the Portal a non-exclusive, worldwide, royalty-free license to use, reproduce, and display the content as necessary for Portal operations.</li>
                                    </ul>
                                </div>

                                <div class="terms-section">
                                    <h3>Privacy Policy</h3>
                                    <p class="details">The Portal's Privacy Policy governs the collection and use of Users' personal information. By using the Portal, you consent to the practices described in the Privacy Policy.</p>
                                </div>

                                <div class="terms-section">
                                    <h3>Limitation of Liability</h3>
                                    <ul class="terms-list">
                                        <li>The Portal is provided "as is" and "as available."</li>
                                        <li>The Portal is not responsible for any damages resulting from:
                                            <ul class="terms-sub-list">
                                                <li>Use or inability to use the Portal.</li>
                                                <li>Misconduct by other Users or third parties.</li>
                                                <li>Errors or inaccuracies in the content provided.</li>
                                            </ul>
                                        </li>
                                    </ul>
                                </div>

                                <div class="terms-section">
                                    <h3>Termination</h3>
                                    <p class="details">The Portal reserves the right to terminate or suspend access to any User for any reason, including breach of these Terms.</p>
                                </div>

                                <div class="terms-section">
                                    <h3>Changes to Terms</h3>
                                    <p class="details">The Portal may update these Terms at any time. Users will be notified of significant changes, and continued use of the Portal constitutes acceptance of the updated Terms.</p>
                                </div>

                                <button class="acceptbtn">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="22px" viewBox="0 -960 960 960" width="22px" fill="#FFFFFF"><path d="m423.28-416.37-79.78-79.78q-12.43-12.44-31.35-12.44-18.91 0-31.35 12.44-12.43 12.43-12.31 31.35.12 18.91 12.55 31.34l110.18 110.18q13.76 13.67 32.11 13.67 18.34 0 32.02-13.67L677.76-545.7q12.44-12.43 12.44-31.22 0-18.8-12.44-31.23-12.43-12.44-31.35-12.44-18.91 0-31.34 12.44L423.28-416.37ZM480-71.87q-84.91 0-159.34-32.12-74.44-32.12-129.5-87.17-55.05-55.06-87.17-129.5Q71.87-395.09 71.87-480t32.12-159.34q32.12-74.44 87.17-129.5 55.06-55.05 129.5-87.17 74.43-32.12 159.34-32.12t159.34 32.12q74.44 32.12 129.5 87.17 55.05 55.06 87.17 129.5 32.12 74.43 32.12 159.34t-32.12 159.34q-32.12 74.44-87.17 129.5-55.06 55.05-129.5 87.17Q564.91-71.87 480-71.87Z"/></svg>
                                    <span>Accept terms and conditions</span>
                                </button>
                            </div>
                        </div>
                    </div>
    </div>
    <!-- TERMS AND CONDITIONS -->

    
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

    <script src="companycreates.js"></script>
</body>
</html>
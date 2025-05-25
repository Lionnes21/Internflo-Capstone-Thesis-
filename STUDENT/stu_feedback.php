<?php
    // Database configuration
    $servername = "localhost";
    $username = "u798912504_root";
    $password = "Internfloucc2025*"; // Update with your database password
    $dbname = "u798912504_internflo";

    // Create a new database connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check the connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Sanitize and validate inputs
        $name = $conn->real_escape_string($_POST['name']);
        $student_number = $conn->real_escape_string($_POST['student_number']);
        $course_and_year = $conn->real_escape_string($_POST['course_and_year']);
        $advisor_id = $conn->real_escape_string($_POST['coordinator']);
        $title = $conn->real_escape_string($_POST['title']);
        $additional_info = $conn->real_escape_string($_POST['additional_info']);

        // File upload handling
        $attachment = null;
        if(isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
            $upload_dir = 'studentreg/';
            // Create uploads directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = uniqid() . '_' . basename($_FILES['attachment']['name']);
            $upload_path = $upload_dir . $file_name;
            
            if(move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_path)) {
                $attachment = $conn->real_escape_string($upload_path);
            }
        }

        // Prepare SQL to insert data
        $sql = "INSERT INTO student_concerns (
            name, 
            student_number, 
            course_and_year, 
            advisor_id, 
            title, 
            additional_info, 
            attachment
        ) VALUES (
            '$name', 
            '$student_number', 
            '$course_and_year', 
            '$advisor_id', 
            '$title', 
            '$additional_info', 
            " . ($attachment ? "'$attachment'" : "NULL") . "
        )";

        // Execute query
        if ($conn->query($sql) === TRUE) {
            // Redirect or show success message
            header("Location: stu_registration.php");
            exit();
        } else {
            // Handle error
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }

    // Query to fetch coordinators
    $query = "SELECT id, first_name, last_name FROM m_advisors ORDER BY last_name, first_name";
    $result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UCC - Student Feedback</title>
    <link rel="stylesheet" href="stu_feedback.css">
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
                <a href="../STUDENTCOORLOG/login.php" class="login-btn">LOGIN</a>
                <a href="../RECRUITER/companysignin.php" class="employer-btn">EMPLOYER SITE</a>
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
                    menuToggle.innerHTML = '✕';
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
    

    <!-- FORM -->
    <div class="container90">
        <div class="container">
            <!-- Sign Up Section -->       
            <div class="image-container">
                <img src="pics/pic1.jpg" alt="Image Description">
            </div>
           
            <fieldset>

                    <div class="signup-header">
                        <h1>Student <span class="highlight">Concerns</span></h1>
                        <p>Send us a message about your student number concerns</p>                  
                    </div>

                        <form action="" method="POST" enctype="multipart/form-data">
                            
                            <div class="tip">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#002b7f"><path d="M480-280q17 0 28.5-11.5T520-320v-160q0-17-11.5-28.5T480-520q-17 0-28.5 11.5T440-480v160q0 17 11.5 28.5T480-280Zm0-320q17 0 28.5-11.5T520-640q0-17-11.5-28.5T480-680q-17 0-28.5 11.5T440-640q0 17 11.5 28.5T480-600Zm0 520q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/></svg>  
                                For validation purposes, student registration uploads are compulsory.
                            </div>
                            <h2>Student Information</h2>
                            <div class="row">
                                <div class="input-group">
                                <input type="text" name="name" id="name" placeholder="Name" onkeypress="allowOnlyLetters(event)" oninput="capitalizeFirstLetter(this)">
                                <div id="name_error" class="form-error" style="display: none">This field is required</div>
                                </div>
                                <div class="input-group">
                                <input type="text" name="student_number" id="student_number" placeholder="Student Number (20**0***-N/S)" oninput="capitalizeAll(event)">
                                <div id="student_number_error" class="form-error" style="display: none">This field is required</div>
                                </div>
                            </div>


                            <div class="row">
                                <div class="input-group">
                                <input type="text" name="course_and_year" id="course_and_year" placeholder="Course & Year (BS**-*A)" oninput="capitalizeAll(event)">
                                <div id="course_and_year_error" class="form-error" style="display: none">This field is required</div>
                                </div>
                                <div class="input-group">
                                <select name="coordinator" id="coordinator">
                                    <option value="">Select Practicum Coordinator</option>
                                    <?php
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            $full_name = $row['last_name'] . ', ' . $row['first_name'];
                                            echo "<option value='" . htmlspecialchars($row['id']) . "'>" . htmlspecialchars($full_name) . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                                <div id="coordinator_error" class="form-error" style="display: none; margin: 8px 0 0 0; color: red; font-size: 15px;">This field is required</div>
                            </div>

                            <?php
                            // Close the database connection
                            $conn->close();
                            ?>
                            </div>

                            <h3>Upload here your latest student registration form</h3>
                            <div class="row">
                                <div class="input-group">
                                <input type="file" name="attachment" id="attachment" accept=".pdf">
                                <div id="attachment_error" class="form-error" style="display: none">This field is required</div>
                                </div>
                            </div>

                            <h2>Student Concerns</h2>
                                <div class="row">
                                    <div class="input-group">
                                    <input type="text" name="title" id="name" placeholder="Title"  oninput="capitalizeFirstLetter(this)">
                                    <div id="name_error" class="form-error" style="display: none">This field is required</div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="input-group">
                                    <textarea name="additional_info" id="additional_info" placeholder="Provide additional information about your concern here.."  oninput="capitalizeFirstLetter(this)"></textarea>
                                    <div id="additional_info_error" class="form-error" style="display: none">This field is required</div>
                                    </div>
                                </div>


                                <button type="submit" class="verifybtn">Send Concerns <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ffffff"><path d="M176-183q-20 8-38-3.5T120-220v-180l320-80-320-80v-180q0-22 18-33.5t38-3.5l616 260q25 11 25 37t-25 37L176-183Z"/></svg></button>
                        </form>

            </fieldset>
            <SCript>
                
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
                function capitalizeAll(event) {
                    const inputField = event.target;
                    inputField.value = inputField.value.toUpperCase();
                }
                document.addEventListener('DOMContentLoaded', function() {
                    const form = document.querySelector('form');
                    const sendConcernsBtn = document.querySelector('.verifybtn');
                    const inputFields = document.querySelectorAll('.input-group input, .input-group textarea, .input-group select#coordinator');

                    function validateStudentNumber(input) {
                const errorElement = input.nextElementSibling;
                const value = input.value.trim();
                const studentNumberRegex = /^20\d{2}0\d{3}-[NS]$/;

                if (value === '') {
                    showError(errorElement, input, 'This field is required');
                    input.style.borderColor = 'red';
                    return false;
                }

                if (studentNumberRegex.test(value)) {
                    clearError(errorElement, input);
                    input.classList.add('valid');
                    input.style.borderColor = 'green';
                    input.style.boxShadow = 'none';
                    return true;
                } else {
                    showError(errorElement, input, 'Please use format: 20**0***-N/S');
                    input.style.borderColor = 'red';
                    return false;
                }
            }

            function validateInput(input) {
                // Special validation for student number
                if (input.id === 'student_number') {
                    return validateStudentNumber(input);
                }

                const errorElement = input.nextElementSibling;
                const value = input.value.trim();
                let isValid = false;

                if (value === '') {
                    showError(errorElement, input, 'This field is required');
                    isValid = false;
                } else {
                    clearError(errorElement, input);
                    isValid = true;
                }

                return isValid;
            }

            function showError(errorElement, input, message) {
                errorElement.textContent = message;
                errorElement.style.display = 'block';
                input.classList.add('error');
                input.classList.remove('valid');
            }

            function clearError(errorElement, input) {
                errorElement.style.display = 'none';
                input.classList.remove('error');
                input.classList.remove('valid');
            }

            // Add individual input event listeners
            inputFields.forEach(input => {
                // Add specific event listeners based on input type
                if (input.id === 'name' || input.id === 'coordinator') {
                    input.addEventListener('keypress', function(event) {
                        allowOnlyLetters(event);
                    });
                    input.addEventListener('input', function() {
                        capitalizeFirstLetter(this);
                    });
                }

                if (input.id === 'student_number' || input.id === 'course_and_year') {
                    input.addEventListener('input', function(event) {
                        capitalizeAll(event);
                        
                        // Special handling for student number
                        if (input.id === 'student_number') {
                            validateStudentNumber(input);
                        }
                    });
                }

                input.addEventListener('focus', function() {
                    if (!this.classList.contains('error') && !this.classList.contains('valid')) {
                        this.style.borderColor = 'blue';
                        this.style.boxShadow = '0 0 0 0.3rem rgba(0, 123, 255, 0.25)';
                    }
                });

                input.addEventListener('blur', function() {
                            // For student number, reset border to default if valid
                            input.addEventListener('blur', function() {
                    if (!this.classList.contains('error')) {
                        this.style.borderColor = '';
                        this.style.boxShadow = '';
                        
                        if (this.classList.contains('valid')) {
                            this.classList.remove('valid');
                        }
                    }
                });

                            if (!this.classList.contains('error')) {
                                this.style.borderColor = '';
                                this.style.boxShadow = '';
                                
                                if (this.classList.contains('valid')) {
                                    this.classList.remove('valid');
                                }
                            }
                        });
                        // Add file validation for attachment
                        const attachmentInput = document.getElementById('attachment');
                        attachmentInput.addEventListener('change', function(event) {
                            const allowedExtensions = ['.pdf'];
                            const fileName = this.value;
                            const fileExtension = fileName.substring(fileName.lastIndexOf('.')).toLowerCase();
                            
                            if (!allowedExtensions.includes(fileExtension)) {
                                // Clear the file input
                                this.value = '';
                                
                                // Show error message
                                const errorElement = document.getElementById('attachment_error');
                                errorElement.textContent = 'Only PDF files are allowed';
                                errorElement.style.display = 'block';
                                
                                // Add red border and box-shadow
                                this.style.borderColor = 'red';
                                this.style.boxShadow = '0 0 0 0.3rem rgba(255, 0, 0, 0.25)';
                                this.classList.remove('valid');
                            } else {
                                // Clear any previous error
                                const errorElement = document.getElementById('attachment_error');
                                errorElement.style.display = 'none';
                                this.style.borderColor = '';
                                this.style.boxShadow = '';
                                this.classList.add('valid');
                            }
                        });
                        attachmentInput.addEventListener('blur', function() {
                            if (this.classList.contains('error') || this.value === '') {
                                // Clear the error message
                                const errorElement = document.getElementById('attachment_error');
                                errorElement.style.display = 'none';
                                
                                // Reset border and box-shadow
                                this.style.borderColor = '';
                                this.style.boxShadow = '';
                                
                                // Clear the file input
                                this.value = '';
                            }
                        });

                        // Default input validation for other fields
                        input.addEventListener('input', function() {
                            validateInput(this);
                            
                            if (this.value.trim() !== '' && this.id !== 'student_number') {
                                this.classList.add('valid');
                                this.style.borderColor = 'green';
                                this.style.boxShadow = 'none';
                            }
                            });
                        });

                        form.addEventListener('submit', function(event) {
                    let isFormValid = true;

                    // Validate all inputs when form is submitted
                    inputFields.forEach(input => {
                        if (!validateInput(input)) {
                            isFormValid = false;
                        }
                    });

                    // Additional validation for file upload
                    const attachmentInput = document.getElementById('attachment');
                    if (attachmentInput.files.length === 0) {
                        const errorElement = document.getElementById('attachment_error');
                        errorElement.textContent = 'This field is required';
                        errorElement.style.display = 'block';
                        isFormValid = false;
                    } else {
                        const fileName = attachmentInput.value;
                        const fileExtension = fileName.substring(fileName.lastIndexOf('.')).toLowerCase();
                        if (fileExtension !== '.pdf') {
                            const errorElement = document.getElementById('attachment_error');
                            errorElement.textContent = 'Only PDF files are allowed';
                            errorElement.style.display = 'block';
                            isFormValid = false;
                        }
                    }

                    // Prevent form submission if not valid
                    if (!isFormValid) {
                        event.preventDefault();
                        return false;
                    }
                });
                });


            </SCript>
        </div>
    </div>
    <!-- FORM -->


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
    <script src="registration.js"></script>
</body>
</html>



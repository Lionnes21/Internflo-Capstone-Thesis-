<?php
    // Database connection
    $servername = "localhost";
    $username = "u798912504_root";
    $password = "Internfloucc2025*"; // Update with your database password
    $dbname = "u798912504_internflo";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Collect and sanitize input data
        $advisor_id = $conn->real_escape_string($_POST['advisor_id']);
        $lastName = $conn->real_escape_string($_POST['lastName']);
        $firstName = $conn->real_escape_string($_POST['firstName']);
        $middleInitial = $conn->real_escape_string($_POST['middleInitial']);
        $suffix = $conn->real_escape_string($_POST['suffix']);
        $email = $conn->real_escape_string($_POST['email']);
        $contact_no = $conn->real_escape_string($_POST['contact_no']);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirmPassword'];

        // Validate input
        if ($password !== $confirmPassword) {
            die("Error: Passwords do not match");
        }

        // Check if the email already exists
        $checkEmailStmt = $conn->prepare("SELECT * FROM m_advisors WHERE email = ?");
        $checkEmailStmt->bind_param("s", $email);
        $checkEmailStmt->execute();
        $result = $checkEmailStmt->get_result();

        if ($result->num_rows > 0) {
            // Email already exists
            die("Error: Email is already registered.");
        }

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Prepare and execute the SQL statement
        $stmt = $conn->prepare("INSERT INTO m_advisors (advisor_id, last_name, first_name, middle_initial, suffix, email, contact_no, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $advisor_id, $lastName, $firstName, $middleInitial, $suffix, $email, $contact_no, $hashedPassword);

        if ($stmt->execute()) {
            echo "New advisor registered successfully";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
        $checkEmailStmt->close();
    }

    $conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
    <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300&display=swap" rel="stylesheet">
        <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel="stylesheet">
        <link rel="stylesheet" href="css/admin_styles.css">
        <link rel="stylesheet" href="css/create_account.css">
        <title>Internflo - Administrator</title>
        <link rel="icon" href="ucc-logo1.png">

        
<body>
<section id="sidebar">
    <div class="logo">
        <img src="ucc.png" class="logo-full" alt="UCC Full Logo">
        <img src="ucc-logo1.png" class="logo-icon" alt="UCC Icon">
    </div>
    <ul class="side-menu">
        <li><a href="admin_index.php" ><i class='bx bxs-dashboard icon'></i>Dashboard</a></li>
        <li><a href="interns.php" ><i class='bx bxs-graduation icon'></i>Interns</a></li>
        <li>
            <a href="#" ><i class='bx bxs-analyse icon' ></i>Affiliates<i class='bx bx-chevron-right icon-right'></i></a>
            <ul class="side-dropdown">
                <li><a href="partnership.php"><i class='bx bxs-briefcase icon'></i>Partnership</a></li>
                <li><a href="requests.php"><i class='bx bxs-envelope icon'></i>Requests</a></li>
            </ul>
        </li>
        <li>
            <a href="#" ><i class='bx bxs-message-rounded-detail icon'></i>Feedbacks<i class='bx bx-chevron-right icon-right'></i></a>
            <ul class="side-dropdown">
                <li><a href="studentnumberfeedback.php"><i class='bx bxs-id-card icon'></i>Student Number</a></li>
                <li><a href="websitefeedback.php"><i class='bx bx-globe icon'></i>Website</a></li>

            </ul>
        </li>
        <li>
            <a href="#" ><i class='bx bxs-analyse icon'></i>Accounts<i class='bx bx-chevron-right icon-right'></i></a>
            <ul class="side-dropdown">
                <li><a href="studentList.php"><i class='bx bxs-user-detail icon'></i>Students</a></li>
                <li><a href="companyList.php"><i class='bx bx-run icon'></i> Companies</a></li>
            </ul>
        </li>
        <li>
            <a href="#" class="active"><i class='bx bxs-user-account icon'></i>Adviser Account<i class='bx bx-chevron-right icon-right'></i></a>
                <ul class="side-dropdown">
                        <li><a href="create_account.php"><i class='bx bxs-user-detail icon'></i>Create Advisor</a></li> 
                        <li><a href="assignAdvisor.php"><i class='bx bxs-book-add icon'></i>Assign Adviser</a></li>
                        <li><a href="advisorList.php"><i class='bx bxs-user-detail icon'></i>List of Adviser</a></li>  
                </ul>
        </li>
        <li>
        <a href="#"><i class='bx bxs-file-archive icon'></i>All Student Records<i class='bx bx-chevron-right icon-right'></i></a>
            <ul class="side-dropdown">
                <li><a href="student_clas.php"><i class='bx bxs-graduation icon'></i> CLAS</a></li> 
                <li><a href="student_cba.php"><i class='bx bxs-briefcase-alt-2 icon'></i> CBA</a></li>
                <li><a href="student_ce.php"><i class='bx bxs-building-house icon'></i> CE</a></li>
                <li><a href="student_crim.php"><i class='bx bxs-shield icon'></i> CCJE</a></li>  
            </ul>

        </li>
    </ul>
</section>

<main>

    <div class="form-container">
        <h2>Create Adviser Account</h2>
        <form action="#" method="POST">
            <!-- Row 1: Last Name, First Name, Middle Initial, Suffix -->
            <div class="form-group">
                <label>Advisor ID</label>
                <input type="text" name="advisor_id" required>
            </div>
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="lastName" required>
            </div>
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="firstName" required>
            </div>
            <div class="form-group">
                <label>Middle Initial</label>
                <input type="text" name="middleInitial" maxlength="1">
            </div>
            <div class="form-group">
                <label>Suffix</label>
                <input type="text" name="suffix">
            </div>

            <!-- Full-width fields -->
            <div class="full-width">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>

            <div class="full-width">
                <label>Contact No.</label>
                <input type="number" name="contact_no" required>
            </div>
            <div class="full-width password-container">
                <label>Password</label>
                <div class="input-container">
                    <input type="password" name="password" required>
                    <i class='bx bx-show toggle-password' id="togglePassword1"></i>
                </div>
            </div>

            <div class="full-width password-container">
                <label>Confirm Password</label>
                <div class="input-container">
                    <input type="password" name="confirmPassword" required>
                    <i class='bx bx-show toggle-password' id="togglePassword2"></i>
                </div>
            </div>


            <div class="full-width">
                <button type="submit">Create Account</button>
            </div>
        </form>
    </div>

    <script>


        // Toggle password visibility
        const togglePassword1 = document.getElementById('togglePassword1');
        const togglePassword2 = document.getElementById('togglePassword2');
        const passwordInput = document.querySelector('input[name="password"]');
        const confirmPasswordInput = document.querySelector('input[name="confirmPassword"]');

        togglePassword1.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('bx-show');
            this.classList.toggle('bx-hide');
        });

        togglePassword2.addEventListener('click', function () {
            const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordInput.setAttribute('type', type);
            this.classList.toggle('bx-show');
            this.classList.toggle('bx-hide');
        });

    </script>

<script src="js/script.js"></script>
</body>
</html> 
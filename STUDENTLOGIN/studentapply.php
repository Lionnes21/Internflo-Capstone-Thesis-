<?php
    session_start();
    $isLoggedIn = isset($_SESSION['user_id']);

    // Initialize variables
    $initials = '';
    $fullName = '';
    $email = '';
    $fullName2 = '';
    $profile_pic = 'pics/default_profile.jpg';
    $internshipDetails = null;

    // If logged in, fetch user details from the database
    if ($isLoggedIn) {
        // Include database connection
        include 'config.php';

        // Get the user ID from the session
        $userId = $_SESSION['user_id'];

        // Fetch user details
        $sql = 'SELECT first_name, middle_name, last_name, suffix, email, name, profile_picture FROM students WHERE id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

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

            $profile_pic = $user['profile_picture'] ?? 'pics/default_profile.png';
        }

        // Check if company_id is passed in the URL
        if (isset($_GET['company_id']) && is_numeric($_GET['company_id'])) {
            $companyId = $_GET['company_id'];

            // Prepare the SQL query to fetch the internship details for the selected company
            $sql = 'SELECT * FROM approvedinternship WHERE id = ?';
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $companyId);
            $stmt->execute();
            $result = $stmt->get_result();

            // Fetch the internship details if found
            if ($result->num_rows > 0) {
                $internshipDetails = $result->fetch_assoc();
            } else {
                $error = "No internship details found for the selected company.";
            }
        } else {
            $error = "Invalid company ID or no ID provided.";
        }
    } else {
        $error = "You are not logged in.";
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="pics/ucclogo2.png">
    <title>UCC - Application</title>
    <link rel="stylesheet" href="NAVX.css">
    <link rel="stylesheet" href="studentapply.css">
</head>
<body>
    <div class="navbar">
        <div class="logo-container">
            <img src="pics/ucclogonav-t.png" alt="Logo">
            <button class="menu-toggle" aria-label="Toggle Navigation">☰</button>
        </div>
        <div class="nav-links">
            <a href="studentfrontpage.php">HOME</a>
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
                        <div class="user-fullname"><?php echo htmlspecialchars($fullName2); ?></div>
                        <hr style="width: 80%; margin: 0 auto">
                        <a href="../studentprofile.php">Profile</a>
                        <a href="StudentMonitoring/std_dashboard.html">Internship</a>
                        <a href="form.php">Resume</a>
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
<div class="content-container">
    <?php if (isset($internshipDetails)): ?>
        <div class="internship-post">
            <div class="header">
                <img src="../RECRUITER/<?php echo htmlspecialchars($internshipDetails['company_logo']); ?>" alt="Company Logo" class="logo">
                <h1><?php echo htmlspecialchars($internshipDetails['company_name']); ?></h1>
                <h3><?php echo htmlspecialchars($internshipDetails['internship_title']); ?></h3>
            </div>
            <div class="company-info">
                <p><?php echo htmlspecialchars($internshipDetails['company_address']); ?></p>
                <p><?php echo htmlspecialchars($internshipDetails['contact_details']); ?></p>
            </div>

            <article>
                <aside>
                    <p><?php echo htmlspecialchars($internshipDetails['number_of_openings']); ?></p>
                    <span>Openings</span>
                </aside>
                <aside>
                    <p><?php echo htmlspecialchars($internshipDetails['internship_type']); ?></p>
                    <span>Internship Type</span>
                </aside>
                <aside>
                    <p><?php echo htmlspecialchars($internshipDetails['duration']); ?></p>
                    <span>Duration</span>
                </aside>
                <aside>
                    <p><?php echo htmlspecialchars($internshipDetails['department']); ?></p>
                    <span>Department</span>
                </aside>
            </article>

            <p><?php echo htmlspecialchars($internshipDetails['company_overview']); ?></p>
            <h2>Internship</h2>
            <div class="internship-details">
                <p><?php echo nl2br(htmlspecialchars($internshipDetails['internship_description'])); ?></p>
            </div>

            <h2>Application Requirements</h2>
            <ul>
                <?php
                $requirements = explode("\n", htmlspecialchars($internshipDetails['requirements']));
                foreach ($requirements as $requirement) {
                    echo "<li>" . $requirement . "</li>";
                }
                ?>
            </ul>

            <h2>Qualifications</h2>
            <ul>
                <?php
                $qualifications = explode("\n", htmlspecialchars($internshipDetails['qualifications']));
                foreach ($qualifications as $qualification) {
                    echo "<li>" . $qualification . "</li>";
                }
                ?>
            </ul>

            <h2>Skills Required</h2>
            <ul class="skills-list">
                <?php
                $skills = explode("\n", htmlspecialchars($internshipDetails['skills_required']));
                foreach ($skills as $skill) {
                    $skill = trim($skill);
                    if (!empty($skill)) {
                        echo "<li>" . htmlspecialchars($skill) . "</li>";
                    }
                }
                ?>
            </ul>

            <h2>Application Deadline</h2>
            <div class="calendar">
                <div class="calendar-header">Deadline</div>
                <div class="calendar-body">
                    <div class="calendar-day" id="day"></div>
                    <div class="calendar-month-year" id="month-year"></div>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const deadline = "<?php echo htmlspecialchars($internshipDetails['application_deadline']); ?>";
                    const date = new Date(deadline);

                    const day = date.getDate();
                    const month = date.toLocaleString('default', { month: 'long' });
                    const year = date.getFullYear();

                    document.getElementById('day').textContent = day;
                    document.getElementById('month-year').textContent = `${month} ${year}`;
                });
            </script>

            <h2>Additional Information</h2>
            <ul>
                <?php
                $additionalInfo = explode("\n", htmlspecialchars($internshipDetails['additional_info']));
                foreach ($additionalInfo as $info) {
                    echo "<li>" . $info . "</li>";
                }
                ?>
            </ul>

            <h2>How to Apply</h2>
            <p>Click the "APPLY NOW" button on this page to start your application process and upload the required documents through our platform.</p>
            <p>For any questions, please use the platform's messaging system to contact the internship coordinator.</p>

            <button class="apply-button">APPLY NOW</button>
        </div>
    <?php else: ?>
        <p><?php echo isset($error) ? htmlspecialchars($error) : "No internship details available."; ?></p>
    <?php endif; ?>
</div>

</body>
</html>



    
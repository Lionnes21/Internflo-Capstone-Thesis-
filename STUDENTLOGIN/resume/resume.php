<?php
    session_start();
    require_once 'config.php'; // Include your database configuration file

    if (!isset($_SESSION['user_id'])) {
        // Redirect to login page or show an error
        header("Location: login.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $resume_exists = false;
    $resume_data = null;

    // Fetch user data from users table
    $sql = 'SELECT first_name, middle_name, last_name, suffix, name, course, email, home_address, region, mobile_number, profile_pic FROM students WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $default_profile_pic = !empty($user['profile_pic']) ? $user['profile_pic'] : '../pics/profile.png';

    if (!$user) {
        // Handle the case where user data is not found
        echo "User data not found.";
        exit();
    }

    // Construct full name
    if (!empty($user['first_name']) && !empty($user['last_name'])) {
        $middleInitial = !empty($user['middle_name']) ? ' ' . strtoupper(substr($user['middle_name'], 0, 1)) . '.' : '';
        $full_name = trim($user['first_name'] . $middleInitial . ' ' . $user['last_name'] . ' ' . $user['suffix']);
    } else {
        $full_name = $user['name'];
    }

    $course = $user['course'];
    $email = $user['email'];
    $location = $user['home_address'] . ', ' . $user['region'];

    // Check if the user already has a resume
    $resumeCheckQuery = 'SELECT * FROM resumes WHERE user_id = ?';
    $resumeCheckStmt = $conn->prepare($resumeCheckQuery);
    $resumeCheckStmt->bind_param('i', $user_id);
    $resumeCheckStmt->execute();
    $resumeCheckResult = $resumeCheckStmt->get_result();

    if ($resumeCheckResult->num_rows > 0) {
        $resume_exists = true;
        $resume_data = $resumeCheckResult->fetch_assoc();
        
        // Update variables with resume data if it exists
        $full_name = $resume_data['full_name'];
        $course = $resume_data['course'];
        $phone_number = $resume_data['mobile_number']; // Changed from 'phone_number' to 'mobile_number'
        $email = $resume_data['email'];
        $location = $resume_data['location'];
        $career_objective = $resume_data['career_objective'];
        $profile_picture = $resume_data['profile_picture'];
        $signature = $resume_data['signature'];
    }

    $experiences = array();
    if ($resume_exists) {
        $expQuery = 'SELECT * FROM resume_experiences WHERE resume_id = ? ORDER BY start_date DESC';
        $expStmt = $conn->prepare($expQuery);
        $expStmt->bind_param('i', $resume_data['id']);
        $expStmt->execute();
        $expResult = $expStmt->get_result();
        while ($row = $expResult->fetch_assoc()) {
            $experiences[] = $row;
        }
    }

    $educations = array();
    if ($resume_exists) {
        $eduQuery = 'SELECT * FROM resume_education WHERE resume_id = ? ORDER BY start_year DESC';
        $eduStmt = $conn->prepare($eduQuery);
        $eduStmt->bind_param('i', $resume_data['id']);
        $eduStmt->execute();
        $eduResult = $eduStmt->get_result();
        while ($row = $eduResult->fetch_assoc()) {
            $educations[] = $row;
        }
    }

    $skills = array();
    if ($resume_exists) {
        $skillsQuery = 'SELECT * FROM resume_skills WHERE resume_id = ?';
        $skillsStmt = $conn->prepare($skillsQuery);
        $skillsStmt->bind_param('i', $resume_data['id']);
        $skillsStmt->execute();
        $skillsResult = $skillsStmt->get_result();
        while ($row = $skillsResult->fetch_assoc()) {
            $skills[] = $row;
        }
    }

    $certificates = array();
    if ($resume_exists) {
        $certQuery = 'SELECT * FROM resume_certificates WHERE resume_id = ? ORDER BY end_date DESC';
        $certStmt = $conn->prepare($certQuery);
        $certStmt->bind_param('i', $resume_data['id']);
        $certStmt->execute();
        $certResult = $certStmt->get_result();
        while ($row = $certResult->fetch_assoc()) {
            $certificates[] = $row;
        }
    }

    // Function to get recommended skills based on course
    function getRecommendedSkills($course) {
        $skills = array();
        
        // Define skills for each course
        switch ($course) {
            case "Bachelor of Science in Accountancy":
                $skills = ["Financial Accounting", "Auditing", "Taxation", "Financial Analysis"];
                break;
            case "Bachelor of Science in Accounting Information System":
                $skills = ["Financial Accounting", "Database Management", "ERP Systems", "Data Analytics"];
                break;
            case "Bachelor of Science in Business Administration, Major in Financial Management":
                $skills = ["Financial Planning", "Investment Analysis", "Risk Management", "Corporate Finance"];
                break;
            case "Bachelor of Science in Business Administration, Major in Human Resource Management":
                $skills = ["Recruitment & Selection", "Performance Management", "Labor Relations", "Training & Development"];
                break;
            case "Bachelor of Science in Business Administration, Major in Marketing Management":
                $skills = ["Market Research", "Digital Marketing", "Brand Management", "Consumer Behavior"];
                break;
            case "Bachelor of Science in Entrepreneurship":
                $skills = ["Business Planning", "Market Analysis", "Financial Management", "Product Development"];
                break;
            case "Bachelor of Science in Hospitality Management":
                $skills = ["Customer Service", "Event Planning", "Hotel Operations", "Food & Beverage Management"];
                break;
            case "Bachelor of Science in Office Administration":
                $skills = ["Document Management", "Office Software", "Administrative Support", "Communication"];
                break;
            case "Bachelor of Science in Tourism Management":
                $skills = ["Tour Planning", "Destination Management", "Customer Service", "Cultural Awareness"];
                break;
            case "Bachelor of Science in Criminology":
                $skills = ["Criminal Investigation", "Forensic Science", "Law Enforcement", "Security Management"];
                break;
            case "Bachelor in Secondary Education Major in English":
            case "Bachelor in Secondary Education Major in English - Chinese":
                $skills = ["Curriculum Development", "Instructional Design", "Language Assessment", "Teaching Methodologies"];
                break;
            case "Bachelor in Secondary Education Major in Science":
                $skills = ["Laboratory Management", "Scientific Research", "Curriculum Development", "STEM Education"];
                break;
            case "Bachelor in Secondary Education Major in Technology and Livelihood Education":
                $skills = ["Vocational Training", "Technical Drawing", "Workshop Management", "Instructional Design"];
                break;
            case "Bachelor of Early Childhood Education":
                $skills = ["Child Development", "Curriculum Planning", "Classroom Management", "Assessment Methods"];
                break;
            case "Bachelor of Science in Computer Engineering":
                $skills = ["Computer Architecture", "Digital Systems", "Microcontrollers", "Embedded Systems"];
                break;
            case "Bachelor of Science in Electrical Engineering":
                $skills = ["Power Systems", "Circuit Design", "Control Systems", "Electrical Installation"];
                break;
            case "Bachelor of Science in Electronics Engineering":
                $skills = ["Circuit Design", "Signal Processing", "Electronic Devices", "Telecommunications"];
                break;
            case "Bachelor of Science in Industrial Engineering":
                $skills = ["Process Optimization", "Quality Control", "Operations Research", "Facility Planning"];
                break;
            case "Bachelor of Laws":
                $skills = ["Legal Research", "Legal Writing", "Case Analysis", "Advocacy"];
                break;
            case "Bachelor of Arts in Political Science":
                $skills = ["Policy Analysis", "Political Research", "Public Administration", "International Relations"];
                break;
            case "Bachelor of Arts in Communication":
                $skills = ["Media Production", "Content Writing", "Public Speaking", "Social Media Management"];
                break;
            case "Bachelor of Public Administration":
                $skills = ["Policy Implementation", "Public Service", "Organizational Management", "Governance"];
                break;
            case "Bachelor of Science in Mathematics":
                $skills = ["Statistical Analysis", "Mathematical Modeling", "Problem Solving", "Data Interpretation"];
                break;
            case "Bachelor of Science in Psychology":
                $skills = ["Psychological Assessment", "Research Methods", "Counseling Techniques", "Behavioral Analysis"];
                break;
            case "Bachelor of Science in Computer Science":
                $skills = ["Algorithms", "Data Structures", "Software Development", "Database Design"];
                break;
            case "Bachelor of Science in Information System":
                $skills = ["Systems Analysis", "Database Management", "IT Project Management", "Business Process Modeling"];
                break;
            case "Bachelor of Science in Entertainment and Multimedia":
                $skills = ["Digital Content Creation", "Animation", "Multimedia Design", "Video Production"];
                break;
            case "Bachelor of Science in Information Technology":
                $skills = ["Network Administration", "Web Development", "Cybersecurity", "IT Support"];
                break;
            default:
                $skills = ["Communication", "Teamwork", "Problem Solving", "Critical Thinking"];
        }
        
        return $skills;
    }

    // Get skills based on current course
    $recommended_skills = getRecommendedSkills($course);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../pics/ucc.png">
    <title>UCC - Internflo Curricul Vitae</title>
    <link rel="stylesheet" href="resume.css">
</head>
<body>

    <header>
        <a href="../studentfrontpage.php" class="back-to-home">
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#434649"><path d="m313-440 224 224-57 56-320-320 320-320 57 56-224 224h487v80H313Z"/></svg>
            BACK TO HOME
        </a>
        <h1>Create Curriculum Vitae with Intern<span class="highlight">flo.</span></h1>
        <p>Create your professional curriculum vitae in just a few minutes!</p>
    </header>
    <div class="container">
        <div class="form-section">
            <div class="tabs">
                <button class="tab active" onclick="showTab('personal')">Personal</button>
                <button class="tab" onclick="showTab('experience')">Experience</button>
                <button class="tab" onclick="showTab('education')">Education</button>
                <div class="dropdown">
                    <button class="tab dropdown-toggle" onclick="toggleDropdown(event)">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#434649"><path d="M240-400q-33 0-56.5-23.5T160-480q0-33 23.5-56.5T240-560q33 0 56.5 23.5T320-480q0 33-23.5 56.5T240-400Zm240 0q-33 0-56.5-23.5T400-480q0-33 23.5-56.5T480-560q33 0 56.5 23.5T560-480q0 33-23.5 56.5T480-400Zm240 0q-33 0-56.5-23.5T640-480q0-33 23.5-56.5T720-560q33 0 56.5 23.5T800-480q0 33-23.5 56.5T720-400Z"/></svg>
                    </button>
                    <div class="dropdown-content">
                        <button class="tab" onclick="showTab('skills')">Skills</button>
                        <button class="tab" onclick="showTab('certificates')">Certificates</button>
                    </div>
            </div>
        </div>
            <form id="resume-form" action="<?php echo $resume_exists ? 'update_resume.php' : 'submit_resume.php'; ?>" method="POST" enctype="multipart/form-data">
                    <div class="form-content">

                            <!-- Personal Section -->
                            <div id="personal" class="active">
                                    <h3 class="section-title">Personal Information</h3>

                                
                                        <label for="name">Full Name</label>
                                        <input class="personal-input" type="text" id="name" name="full_name" placeholder="Enter Full Name" 
                                        value="<?php echo strtoupper(htmlspecialchars($full_name)); ?>"
                                        oninput="this.value = this.value.toUpperCase().replace(/[^A-Z\s.]/g, '');">

                                        <label for="course">Course</label>
                                        <input class="personal-input" type="text" id="course" name="course" placeholder="Enter Course"
                                        value="<?php echo strtoupper(htmlspecialchars($course)); ?>"
                                        oninput="this.value = this.value.toUpperCase().replace(/[^A-Z\s.]/g, '');">


                                        <label for="mobile">Phone Number</label>
                                        <input class="personal-input" type="text" id="mobile" name="mobile_number" 
                                        value="<?php echo isset($phone_number) ? htmlspecialchars($phone_number) : (isset($user['mobile_number']) ? htmlspecialchars($user['mobile_number']) : ''); ?>"
                                        pattern="[0-9]*" inputmode="numeric" 
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);">

                                        <label for="email">Email</label>
                                        <input class="personal-input" type="text" id="email" name="email" placeholder="Enter Email"
                                        value="<?php echo htmlspecialchars($email); ?>">

                                        <label for="address">Location</label>
                                        <input class="personal-input" type="text" id="address" name="location" placeholder="Enter Address"
                                        value="<?php echo htmlspecialchars($location); ?>"
                                        oninput="this.value = this.value.replace(/^./, this.value.charAt(0).toUpperCase());">

                                        <!-- Profile Picture Upload -->
                                        <div class="file-upload">
                                            <label for="profile-pic-input">Profile Picture</label>
                                            <div class="file-upload-btn">Choose Files</div>
                                            <input type="file" id="profile-pic-input" name="profile_picture" accept="image/*" 
                                                onchange="updateProfilePic(event); updateFileName(this, 'profile-pic-name')">
                                            <span id="profile-pic-name" class="file-name">
                                                <?php echo isset($profile_picture) ? basename($profile_picture) : 'No file chosen'; ?>
                                            </span>
                                        </div>

                                        <!-- Signature Upload -->
                                        <div class="file-upload">
                                            <label for="signature-pic-input">Signature</label>
                                            <div class="file-upload-btn">Choose Files</div>
                                            <input type="file" id="signature-pic-input" name="signature" accept="image/*" 
                                                onchange="updateSignaturePic(event); updateFileName(this, 'signature-pic-name')">
                                            <span id="signature-pic-name" class="file-name">
                                                <?php echo isset($signature) ? basename($signature) : 'No file chosen'; ?>
                                            </span>
                                        </div>


                                        <label for="career-objective">Career Objective</label>
                                        <textarea class="personal-input personal-textarea" id="career-objective" name="career_objective" placeholder="Enter Career Objective" 
                                            oninput="this.value = this.value.replace(/[^A-Za-z,\s]/g, '').replace(/^./, this.value.charAt(0).toUpperCase());"
                                        ><?php echo isset($career_objective) ? htmlspecialchars($career_objective) : ''; ?></textarea>

                            </div>
                            <!-- Experience Section -->
                            <div id="experience">
                                <h3 class="section-title">Experience</h3>
                                <div class="experience-container">
                                    <?php if (empty($experiences)): ?>
                                    <!-- Default empty form for new entries -->
                                    <div class="originalform">
                                        <input type="text" class="experience-input" name="experience[0][company]" id="experience-company-0" 
                                            placeholder="Company" oninput="this.value = this.value.replace(/[^A-Za-z,\s]/g, '').replace(/^./, this.value.charAt(0).toUpperCase());" />
                                        <input type="text" class="experience-input" name="experience[0][position]" id="experience-position-0" 
                                            placeholder="Position" oninput="this.value = this.value.replace(/[^A-Za-z,\s]/g, '').replace(/^./, this.value.charAt(0).toUpperCase());" />
                                        <div class="experience-date-container">
                                            <div class="experience-date-field">
                                                <label for="experience-start-date-0">Start Date</label>
                                                <input class="experience-input" type="date" name="experience[0][start_date]" id="experience-start-date-0" />
                                            </div>
                                            <div class="experience-date-field">
                                                <label for="experience-end-date-0">End Date</label>
                                                <input class="experience-input" type="date" name="experience[0][end_date]" id="experience-end-date-0" />
                                            </div>
                                        </div>
                                        <div class="experience-checkbox-container">
                                            <input type="checkbox" name="experience[0][currently_working]" id="currently-working-0" />
                                            <label for="currently-working-0" class="experience-checkboxlabel">Currently working here</label>
                                        </div>
                                        <textarea class="experience-input experience-textarea" name="experience[0][description]" id="experience-description-0" 
                                            placeholder="Job Description" oninput="this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1);"></textarea>
                                    </div>
                                    <?php else: ?>
                                        <?php foreach ($experiences as $index => $exp): ?>
                                        <div class="experience-form <?php echo $index === 0 ? 'originalform' : ''; ?>">
                                            <input type="hidden" name="experience[<?php echo $index; ?>][id]" value="<?php echo htmlspecialchars($exp['id']); ?>" />
                                            <input type="text" class="experience-input" name="experience[<?php echo $index; ?>][company]" 
                                                id="experience-company-<?php echo $index; ?>" value="<?php echo htmlspecialchars($exp['company']); ?>"
                                                placeholder="Company" oninput="this.value = this.value.replace(/[^A-Za-z,\s]/g, '').replace(/^./, this.value.charAt(0).toUpperCase());" />
                                            <input type="text" class="experience-input" name="experience[<?php echo $index; ?>][position]" 
                                                id="experience-position-<?php echo $index; ?>" value="<?php echo htmlspecialchars($exp['position']); ?>"
                                                placeholder="Position" oninput="this.value = this.value.replace(/[^A-Za-z,\s]/g, '').replace(/^./, this.value.charAt(0).toUpperCase());" />
                                            <div class="experience-date-container">
                                                <div class="experience-date-field">
                                                    <label for="experience-start-date-<?php echo $index; ?>">Start Date</label>
                                                    <input class="experience-input" type="date" name="experience[<?php echo $index; ?>][start_date]" 
                                                        id="experience-start-date-<?php echo $index; ?>" value="<?php echo htmlspecialchars($exp['start_date']); ?>" />
                                                </div>
                                                <div class="experience-date-field">
                                                    <label for="experience-end-date-<?php echo $index; ?>">End Date</label>
                                                    <input class="experience-input" type="date" name="experience[<?php echo $index; ?>][end_date]" 
                                                        id="experience-end-date-<?php echo $index; ?>" 
                                                        value="<?php echo $exp['currently_working'] ? '' : htmlspecialchars($exp['end_date']); ?>" 
                                                        <?php echo $exp['currently_working'] ? 'disabled' : ''; ?> />
                                                </div>
                                            </div>
                                            <div class="experience-checkbox-container">
                                                <input type="checkbox" name="experience[<?php echo $index; ?>][currently_working]" 
                                                    id="currently-working-<?php echo $index; ?>" 
                                                    <?php echo $exp['currently_working'] ? 'checked' : ''; ?> />
                                                <label for="currently-working-<?php echo $index; ?>" class="experience-checkboxlabel">Currently working here</label>
                                            </div>
                                            <textarea class="experience-input experience-textarea" name="experience[<?php echo $index; ?>][description]" 
                                                id="experience-description-<?php echo $index; ?>" 
                                                placeholder="Job Description" oninput="this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1);"
                                                ><?php echo htmlspecialchars($exp['description']); ?></textarea>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="experience-add-button">+ Add Experience</button>
                            </div>


                            <!-- Certificates Section -->
                            <div id="certificates">
                                <h3 class="section-title">Certifications</h3>
                                
                                <div class="cert-container">
                                    <?php if (empty($certificates)): ?>
                                        <!-- Default empty form for new entries -->
                                        <div class="certform">
                                            <input type="text" class="cert-input" name="certificate-name[0]" 
                                                id="certificate-name-0"
                                                placeholder="Certificate Name" 
                                                oninput="this.value = this.value.replace(/^./, this.value.charAt(0).toUpperCase());" />
                                            <input type="text" class="cert-input" name="issuing-organization[0]"
                                                id="issuing-organization-0"
                                                placeholder="Issuing Organization" 
                                                oninput="this.value = this.value.replace(/^./, this.value.charAt(0).toUpperCase());" />
                                            <div class="cert-date-container">
                                                <div class="cert-date-field">
                                                    <label for="start-date-0">Start Date</label>
                                                    <input type="text" class="cert-input" name="start-date[0]" 
                                                        id="start-date-0" 
                                                        placeholder="Enter start date as text" />
                                                </div>
                                                <div class="cert-date-field">
                                                    <label for="end-date-0">End Date</label>
                                                    <input type="number" class="cert-input" name="end-date[0]" 
                                                        id="end-date-0" 
                                                        placeholder="Enter year as number" />
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($certificates as $index => $cert): ?>
                                        <div class="certform">
                                            <input type="hidden" name="cert-id[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($cert['id']); ?>" />
                                            <input type="text" class="cert-input" name="certificate-name[<?php echo $index; ?>]" 
                                                id="certificate-name-<?php echo $index; ?>"
                                                value="<?php echo htmlspecialchars($cert['certificate_name']); ?>" 
                                                placeholder="Certificate Name" 
                                                oninput="this.value = this.value.replace(/^./, this.value.charAt(0).toUpperCase());" />
                                            <input type="text" class="cert-input" name="issuing-organization[<?php echo $index; ?>]" 
                                                id="issuing-organization-<?php echo $index; ?>"
                                                value="<?php echo htmlspecialchars($cert['issuing_organization']); ?>" 
                                                placeholder="Issuing Organization" 
                                                oninput="this.value = this.value.replace(/^./, this.value.charAt(0).toUpperCase());" />
                                            <div class="cert-date-container">
                                                <div class="cert-date-field">
                                                    <label for="start-date-<?php echo $index; ?>">Start Date</label>
                                                    <input type="text" class="cert-input" name="start-date[<?php echo $index; ?>]" 
                                                        id="start-date-<?php echo $index; ?>"
                                                        value="<?php echo htmlspecialchars($cert['start_date']); ?>" 
                                                        placeholder="Enter start date as text" />
                                                </div>
                                                <div class="cert-date-field">
                                                    <label for="end-date-<?php echo $index; ?>">End Date</label>
                                                    <input type="number" class="cert-input" name="end-date[<?php echo $index; ?>]" 
                                                        id="end-date-<?php echo $index; ?>"
                                                        value="<?php echo htmlspecialchars($cert['end_date']); ?>" 
                                                        placeholder="Enter year as number" />
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <!-- Button to Add More Certificates -->
                                    <button type="button" class="add-certificate-button">+ Add Certification</button>
                                </div>

                            </div>

                            

                            <!-- Skills Section -->
                            <div id="skills">
                                <h3 class="section-title">Skills & Capabilities</h3>
                                <div id="skills-list">
                                    <?php if ($resume_exists && count($skills) > 0): ?>
                                        <?php foreach ($skills as $skill): ?>
                                            <span class="skill-badge"><?php echo htmlspecialchars($skill['skill_name']); ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <?php foreach ($recommended_skills as $skill): ?>
                                            <span class="skill-badge"><?php echo htmlspecialchars($skill); ?></span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <!-- Add New Skill -->
                                <div id="add-skill-section">
                                    <?php if ($resume_exists && !empty($skills)): ?>
                                        <?php foreach ($skills as $skill): ?>
                                            <div class="skill-input-wrapper">
                                                <input class="skill-input" 
                                                    type="text" 
                                                    name="skills[<?php echo $skill['id']; ?>][name]" 
                                                    value="<?php echo htmlspecialchars($skill['skill_name']); ?>"
                                                    oninput="this.value = this.value.replace(/[^A-Za-z,\s]/g, '').replace(/^./, this.value.charAt(0).toUpperCase());">
                                                <input type="hidden" name="skills[<?php echo $skill['id']; ?>][id]" value="<?php echo $skill['id']; ?>">
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <!-- New skill input -->
                                    <div class="skill-input-wrapper">
                                        <input class="skill-input" 
                                            type="text" 
                                            name="new_skill" 
                                            id="new-skill" 
                                            placeholder="Skill" 
                                            oninput="this.value = this.value.replace(/[^A-Za-z,\s]/g, '').replace(/^./, this.value.charAt(0).toUpperCase());">
                                    </div>
                                    <button type="button" id="add-skill-btn">+ Add Skill</button>
                                </div>
                            </div>

            



                            <!-- Education Section -->
                            <div id="education">
                                <h3 class="section-title">Education</h3>
                                <div id="education-entries">
                                    <?php if (empty($educations)): ?>
                                    <!-- Default empty form for new entries -->
                                    <div class="education-entry">
                                        <input class="education-input" type="text" name="education[0][school]" 
                                            id="school-0" placeholder="School/University" 
                                            oninput="this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1);">
                                        
                                        <div class="date-container">
                                            <div class="date-field">
                                                <label for="start-year-0">Start Year</label>
                                                <input class="education-input" type="number" name="education[0][start_year]" 
                                                    id="start-year-0" placeholder="YYYY" min="1900" max="2099" step="1" 
                                                    oninput="this.value = this.value.slice(0, 4);">
                                            </div>
                                            <div class="date-field">
                                                <label for="end-year-0">End Year</label>
                                                <input class="education-input" type="number" name="education[0][end_year]" 
                                                    id="end-year-0" placeholder="YYYY" min="1900" max="2099" step="1" 
                                                    oninput="this.value = this.value.slice(0, 4);">
                                            </div>
                                        </div>

                                        <div class="education-checkbox-container">
                                            <input type="checkbox" name="education[0][current_study]" id="current-study-0">
                                            <label class="checkboxlabel" for="current-study-0">Currently studying here</label>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                        <?php foreach ($educations as $index => $edu): ?>
                                        <div class="education-entry">
                                            <input type="hidden" name="education[<?php echo $index; ?>][id]" 
                                                value="<?php echo htmlspecialchars($edu['id']); ?>" />
                                            <input class="education-input" type="text" 
                                                name="education[<?php echo $index; ?>][school]" 
                                                id="school-<?php echo $index; ?>" 
                                                value="<?php echo htmlspecialchars($edu['school']); ?>" 
                                                placeholder="School/University" 
                                                oninput="this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1);">
                                            
                                            <div class="date-container">
                                                <div class="date-field">
                                                    <label for="start-year-<?php echo $index; ?>">Start Year</label>
                                                    <input class="education-input" type="number" 
                                                        name="education[<?php echo $index; ?>][start_year]" 
                                                        id="start-year-<?php echo $index; ?>" 
                                                        value="<?php echo htmlspecialchars($edu['start_year']); ?>" 
                                                        placeholder="YYYY" min="1900" max="2099" step="1" 
                                                        oninput="this.value = this.value.slice(0, 4);">
                                                </div>
                                                <div class="date-field">
                                                    <label for="end-year-<?php echo $index; ?>">End Year</label>
                                                    <input class="education-input" type="number" 
                                                        name="education[<?php echo $index; ?>][end_year]" 
                                                        id="end-year-<?php echo $index; ?>" 
                                                        value="<?php echo $edu['current_study'] ? '' : htmlspecialchars($edu['end_year']); ?>" 
                                                        placeholder="YYYY" min="1900" max="2099" step="1" 
                                                        oninput="this.value = this.value.slice(0, 4);" 
                                                        <?php echo $edu['current_study'] ? 'disabled' : ''; ?>>
                                                </div>
                                            </div>

                                            <div class="education-checkbox-container">
                                                <input type="checkbox" name="education[<?php echo $index; ?>][current_study]" 
                                                    id="current-study-<?php echo $index; ?>" 
                                                    <?php echo $edu['current_study'] ? 'checked' : ''; ?>>
                                                <label class="checkboxlabel" for="current-study-<?php echo $index; ?>">
                                                    Currently studying here
                                                </label>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="education-buttons">
                                    <button type="button" class="add-education">+ Add Education</button>
                                </div>
                            </div>








                    </div>

            </form>

    </div>



                









    <div class="resume-section">
        <div class="resume-header">
            <div>
                <h1 class="name" id="resume-name">NAME</h1>
                <h2 class="course" id="resume-course">Course</h2>
                <p class="details" id="resume-mobile">Mobile Number</p>
                <p class="details" id="resume-email">Email Address</p>
                <p class="details" id="resume-address">Location</p>
            </div>






            <!-- Profile Picture Preview -->
            <img src="<?php echo empty($profile_picture) ? htmlspecialchars('../' . $default_profile_pic) : htmlspecialchars($profile_picture); ?>" 
            alt="Profile Picture" class="profile-pic" id="resume-profile-pic">
        </div>
            
            <div class="career">
                <h2>CAREER OBJECTIVE</h2>
                <hr>
                <p id="resume-career-objective">Career objective will appear here.</p>
            </div>
            <div class="experience">
                <h2>PROFESSIONAL EXPERIENCE</h2>
                <hr>
                <p id="resume-professional-experience">Professional experience will appear here.</p>
            </div>
            
            <div class="education">
                <h2>EDUCATION</h2>
                <hr>
                <p id="resume-education">Professional experience will appear here.</p>
            </div>

            <div class="capabilities">
                <h2>CAPABILITIES</h2>
                <hr>
                <ul id="resume-capabilities" class="capabilities-grid">
                    <li>Capabilities will appear here</li>
                </ul>
            </div>

            <div class="certification">
                <h2>CERTIFICATIONS</h2>
                <hr>
                <ul id="resume-certifications">
                    <li>Your certifications will appear here</li>
                </ul>
            </div>        
            

            
            <div class="resume-signature">
                <!-- Signature Preview -->
                <img src="<?php echo isset($signature) ? htmlspecialchars($signature) : ''; ?>" 
                    class="signature-pic" id="resume-signature-pic" 
                    style="display: <?php echo isset($signature) ? 'block' : 'none'; ?>">
                <p id="resume-signature-text">I hereby certify that the above information is true and correct<br>to the best of my knowledge and belief.</p>
            </div>
    </div>

    </div>
        <!-- New wrapper for the button, separate from the flex container -->
        <div class="button-wrapper">
        <div class="button-container">
            <button form="resume-form" type="submit" class="submit-button">Submit Resume</button>
            <button onclick="printResume()">Generate PDF</button>
        </div>






<script src="resume.js"></script>
</body>
</html>



               








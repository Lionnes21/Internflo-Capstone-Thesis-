<?php
// Handle form submission and save data to the database
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database connection settings
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "student_registration";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Retrieve form data for multiple education levels
    $tertiary_school = $_POST['tertiary_school'][0];
    $tertiary_start_year = $_POST['tertiary_start'][0];
    $secondary_school = $_POST['secondary_school'][0];
    $secondary_start_year = $_POST['secondary_start'][0];
    $secondary_end_year = $_POST['secondary_end'][0];
    $elementary_school = $_POST['elementary_school'][0];
    $elementary_start_year = $_POST['elementary_start'][0];
    $elementary_end_year = $_POST['elementary_end'][0];

    // Other form data
    $education = $_POST['education'];
    $certifications = $_POST['certifications'];
    $experience = $_POST['experience'];
    $capabilities = $_POST['capabilities'];
    $objective = $_POST['objective'];
    $contact_number = $_POST['contact_number'];
    $email = $_POST['email']; // Retrieve email from the form

    // Handle profile picture upload
    $profilePicturePath = "";
    if (isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] == 0) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $profilePicturePath = $targetDir . basename($_FILES["profilePicture"]["name"]);
        move_uploaded_file($_FILES["profilePicture"]["tmp_name"], $profilePicturePath);
    }

    // Handle e-signature upload
    $eSignaturePath = "";
    if (isset($_FILES['eSignature']) && $_FILES['eSignature']['error'] == 0) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $eSignaturePath = $targetDir . basename($_FILES["eSignature"]["name"]);
        move_uploaded_file($_FILES["eSignature"]["tmp_name"], $eSignaturePath);
    }

    // Insert data into the database
    $stmt = $conn->prepare("INSERT INTO resumes (
        tertiary_school, tertiary_start_year, secondary_school, secondary_start_year, secondary_end_year,
        elementary_school, elementary_start_year, elementary_end_year, education, certifications, experience,
        capabilities, objective, contact_number, email, profile_picture, e_signature) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "sssssssssssssssss", 
        $tertiary_school, $tertiary_start_year, $secondary_school, $secondary_start_year, $secondary_end_year, 
        $elementary_school, $elementary_start_year, $elementary_end_year, $education, $certifications, $experience, 
        $capabilities, $objective, $contact_number, $email, $profilePicturePath, $eSignaturePath
    );

    if ($stmt->execute()) {
        header("Location: resume.php");
        exit();
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');</script>";
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
    <link rel="stylesheet" href="forms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <title>Resume Input Form</title>

</head>
<body>
    <div class="form-container">
        <h2>Resume Creation</h2>
        <p>Customise and create your own resume with Internflo.</p>
        <div class="progress-bar">
            <div class="step active">
                1
                <span class="step-label">Education</span>
            </div>
            <div class="step">
                2
                <span class="step-label">Capabilities</span>
            </div>
            <div class="step">
                3
                <span class="step-label">Additional</span>
            </div>
        </div>
        <form id="resumeForm" method="POST" enctype="multipart/form-data">
        <div class="form-step active">
            <!-- Tertiary Education -->
            <div class="education-level">
                <div class="level-title">TERTIARY</div>
                <div class="education-form-group">
                <input type="text" placeholder="University/College Name" name="tertiary_school[]" required oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '').replace(/\b\w/g, char => char.toUpperCase());">
                    <div class="date-range">
                    <input type="number" placeholder="Present Year" name="tertiary_start[]" required oninput="this.value = this.value.slice(0, 4);">
                    </div>
                </div>
                <div class="button-container">
                    <button type="button" class="add-more-btn" onclick="addMoreEducation(this, 'tertiary')">Add More</button>
                    <button type="button" class="remove-btn" style="display: none;" onclick="removeEducation(this)">Remove</button>
                </div>
            </div>

            <!-- Secondary Education -->
            <div class="education-level">
                <div class="level-title">SECONDARY SCHOOL</div>
                <div class="education-form-group">
                    <input type="text" placeholder="High School Name" name="secondary_school[]" required oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '').replace(/\b\w/g, char => char.toUpperCase());">
                    <div class="date-range">
                        <input type="number" placeholder="Start Year" name="secondary_start[]" required oninput="this.value = this.value.slice(0, 4);">
                        <input type="number" placeholder="End Year" name="secondary_end[]" required oninput="this.value = this.value.slice(0, 4);">
                    </div>
                </div>
                <div class="button-container">
                    <button type="button" class="add-more-btn" onclick="addMoreEducation(this, 'secondary')">Add More</button>
                    <button type="button" class="remove-btn" style="display: none;" onclick="removeEducation(this)">Remove</button>
                </div>
            </div>

            <!-- Elementary Education -->
            <div class="education-level">
                <div class="level-title">ELEMENTARY</div>
                <div class="education-form-group">
                    <input type="text" placeholder="Elementary School Name" name="elementary_school[]" required oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '').replace(/\b\w/g, char => char.toUpperCase());">
                    <div class="date-range">
                        <input type="number" placeholder="Start Year" name="elementary_start[]" required oninput="this.value = this.value.slice(0, 4);"> 
                        <input type="number" placeholder="End Year" name="elementary_end[]" required oninput="this.value = this.value.slice(0, 4);">
                    </div>
                </div>
                <div class="button-container">
                    <button type="button" class="add-more-btn" onclick="addMoreEducation(this, 'elementary')">Add More</button>
                    <button type="button" class="remove-btn" style="display: none;" onclick="removeEducation(this)">Remove</button>
                </div>
            </div>
        </div>

        <div class="form-step">
                <div class="level-title">CAREER OBJECTIVE</div>
                <textarea name="objective" placeholder="Enter Career objective" required oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '').replace(/\b\w/g, char => char.toUpperCase());"></textarea>
                <div class="level-title">EXPERIENCE</div>
                <textarea name="experience" placeholder="Enter Professional experience" required oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '').replace(/\b\w/g, char => char.toUpperCase());"></textarea>
                <div class="level-title">SKILLS</div>
                <textarea name="capabilities" placeholder="Enter Skills" required></textarea>
                <div class="level-title">CERTIFICATION</div>
                <textarea name="certifications" placeholder="Enter Certificates" required oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '').replace(/\b\w/g, char => char.toUpperCase());"></textarea>
            </div>


            <div class="form-step">
                <!-- Contact Number -->
                <div class="level-title">CONTACT INFORMATION</div>
                <input class="additional-text" type="email" name="email" placeholder="Enter Email Address" required>
                <input class="additional-text" type="text" name="contact_number" placeholder="Enter Phone Number" required>

                <div class="level-title">ADDITIONAL UPLOADS</div>

                <!-- Resume Picture Upload -->
                <div id="resume-photo-upload-container">
                    <label for="resume-picture" class="upload-label">Upload Resume Profile Picture</label>
                    <div class="picture" onclick="triggerFileInput('resume-photo-input')">
                        <svg xmlns="http://www.w3.org/2000/svg" height="30px" viewBox="0 -960 960 960" width="30px" fill="#ddd">
                            <path d="M760-680v-80h-80v-80h80v-80h80v80h80v80h-80v80h-80ZM440-260q75 0 127.5-52.5T620-440q0-75-52.5-127.5T440-620q-75 0-127.5 52.5T260-440q0 75 52.5 127.5T440-260Zm0-80q-42 0-71-29t-29-71q0-42 29-71t71-29q42 0 71 29t29 71q0 42-29 71t-71 29ZM120-120q-33 0-56.5-23.5T40-200v-480q0-33 23.5-56.5T120-760h126l74-80h280v160h80v80h160v400q0 33-23.5 56.5T760-120H120Z"/>
                        </svg>
                        <span>Personal Picture</span>
                    </div>
                </div>
                <div id="resume-photo-upload-container-warning" style="display: none; color: red;">Photo size exceeds the maximum allowed.</div>
                <input type="file" name="profilePicture" id="resume-photo-input" style="display: none;" accept="image/*" onchange="handleFileSelect(this, 'resume-photo-upload-container')">

                <!-- E-Signature Upload -->
                <div id="esign-photo-upload-container">
                    <label for="esign-picture" class="upload-label">Upload E-Signature</label>
                    <div class="picture" onclick="triggerFileInput( 'esign-photo-input')">
                        <svg xmlns="http://www.w3.org/2000/svg" height="30px" viewBox="0 -960 960 960" width="30px" fill="#ddd">
                            <path d="M760-680v-80h-80v-80h80v-80h80v80h80v80h-80v80h-80ZM440-260q75 0 127.5-52.5T620-440q0-75-52.5-127.5T440-620q-75 0-127.5 52.5T260-440q0 75 52.5 127.5T440-260Zm0-80q-42 0-71-29t-29-71q0-42 29-71t71-29q42 0 71 29t29 71q0 42-29 71t-71 29ZM120-120q-33 0-56.5-23.5T40-200v-480q0-33 23.5-56.5T120-760h126l74-80h280v160h80v80h160v400q0 33-23.5 56.5T760-120H120Z"/>
                        </svg>
                        <span>E-Signature</span>
                    </div>
                </div>
                <div id="esign-photo-upload-container-warning" style="display: none; color: red;">Photo size exceeds the maximum allowed.</div>
                <input type="file" name="eSignature" id="esign-photo-input" style="display: none;" accept="image/*" onchange="handleFileSelect(this, 'esign-photo-upload-container')">
            </div>




            <div class="button-group first-step">
                <button type="button" id="prevBtn" onclick="nextPrev(-1)">Previous</button>
                <button type="button" id="nextBtn" onclick="nextPrev(1)">Next</button>
            </div>
        </form>
    </div>

    <script src="form.js">
    </script>
</body>
</html>
<?php
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

    // Fetch the latest resume entry from the database
    $sql = "SELECT * FROM resumes ORDER BY submission_date DESC LIMIT 1";
    $result = $conn->query($sql);

    // Initialize variables to store fetched data
    $name = $degree = $phone = $email = $address = $objective = $capabilities = $experience = $education = $certifications = $profilePicturePath = "";

    if ($result->num_rows > 0) {
        // Get the data
        $row = $result->fetch_assoc();
        $name = $row['name'];
        $degree = $row['degree'];
        $phone = $row['phone'];
        $email = $row['email'];
        $address = $row['address'];
        $objective = $row['objective'];
        $capabilities = $row['capabilities'];
        $experience = $row['experience'];
        $education = $row['education'];
        $certifications = $row['certifications'];
        $profilePicturePath = $row['profile_picture'];
    }

    $conn->close();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">
    <title></title>
</head>
<body>
   

    <button id="downloadPdf" onclick="printResume()">Download PDF</button>

    <div class="resume" id="resume">
        <div class="header">
            <div>
                <h1><?php echo htmlspecialchars($name); ?></h1>
                <h2><?php echo htmlspecialchars($degree); ?></h2>
                <div><?php echo htmlspecialchars($phone); ?></div>
                <div><?php echo htmlspecialchars($email); ?></div>
                <div><?php echo htmlspecialchars($address); ?></div>
            </div>
            <img src="<?php echo htmlspecialchars($profilePicturePath); ?>" alt="Profile Picture">
        </div>

        <div class="section">
            <div class="section-title">CAREER OBJECTIVE</div>
            <div><?php echo nl2br(htmlspecialchars($objective)); ?></div>
        </div>

        <div class="section">
            <div class="section-title">CAPABILITIES</div>
            <div>
                <?php 
                    $capabilitiesList = explode(',', $capabilities);
                    foreach ($capabilitiesList as $capability) {
                        echo '• ' . htmlspecialchars(trim($capability)) . '<br>';
                    }
                ?>
            </div>
        </div>

        <div class="section">
            <div class="section-title">PROFESSIONAL EXPERIENCE</div>
            <div><?php echo nl2br(htmlspecialchars($experience)); ?></div>
        </div>

        <div class="section">
            <div class="section-title">EDUCATION</div>
            <div>
                <?php 
                    $educationList = explode("\n", $education);
                    foreach ($educationList as $edu) {
                        echo htmlspecialchars(trim($edu)) . '<br>';
                    }
                ?>
            </div>
        </div>

        <div class="section">
            <div class="section-title">CERTIFICATIONS</div>
            <div>
                <?php 
                    $certificationsList = explode("\n", $certifications);
                    foreach ($certificationsList as $cert) {
                        echo htmlspecialchars(trim($cert)) . '<br>';
                    }
                ?>
            </div>
        </div>  
    </div>

    <script>
        function printResume() {
            window.print();
        }
    </script>

</body>
</html>

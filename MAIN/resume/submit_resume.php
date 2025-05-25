<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Error: User not logged in");
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $course = $_POST['course'];
    $phone_number = $_POST['mobile_number'];
    $email = $_POST['email'];
    $location = $_POST['location'];
    $career_objective = $_POST['career_objective'];

    // Handle profile picture upload
    $profile_picture = "";
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $targetDir = "profileresume/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $profile_picture = $targetDir . basename($_FILES["profile_picture"]["name"]);
        move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $profile_picture);
    }

    // Handle signature upload
    $signature = "";
    if (isset($_FILES['signature']) && $_FILES['signature']['error'] == 0) {
        $targetDir = "signitureresume/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $signature = $targetDir . basename($_FILES["signature"]["name"]);
        move_uploaded_file($_FILES["signature"]["tmp_name"], $signature);
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Insert basic resume data into the database
        $sql = "INSERT INTO resumes (user_id, full_name, course, mobile_number, email, location, profile_picture, signature, career_objective) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        
        $stmt->bind_param("issssssss", 
            $user_id,
            $full_name,
            $course,
            $phone_number,
            $email,
            $location,
            $profile_picture,
            $signature,
            $career_objective
        );

        if ($stmt->execute()) {
            $resume_id = $stmt->insert_id;

            // Process and insert experience data
            if (isset($_POST['experience']) && is_array($_POST['experience'])) {
                $expSql = "INSERT INTO resume_experiences (resume_id, company, position, start_date, end_date, currently_working, description) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $expStmt = $conn->prepare($expSql);

                foreach ($_POST['experience'] as $exp) {
                    // Skip if both company and position are empty
                    if (empty($exp['company']) && empty($exp['position'])) {
                        continue;
                    }

                    $currently_working = isset($exp['currently_working']) ? 1 : 0;
                    
                    // Handle dates
                    $start_date = !empty($exp['start_date']) ? $exp['start_date'] : null;
                    $end_date = $currently_working ? null : 
                               (!empty($exp['end_date']) ? $exp['end_date'] : null);

                    $expStmt->bind_param("issssis", 
                        $resume_id, 
                        $exp['company'], 
                        $exp['position'], 
                        $start_date, 
                        $end_date, 
                        $currently_working, 
                        $exp['description']
                    );
                    $expStmt->execute();
                }
                $expStmt->close();
            }

            // Process and insert education data
            if (isset($_POST['education']) && is_array($_POST['education'])) {
                $eduSql = "INSERT INTO resume_education (resume_id, school, start_year, end_year, current_study) 
                        VALUES (?, ?, ?, ?, ?)";
                $eduStmt = $conn->prepare($eduSql);

                foreach ($_POST['education'] as $edu) {
                    // Skip if school is empty
                    if (empty($edu['school'])) {
                        continue;
                    }

                    $current_study = isset($edu['current_study']) ? 1 : 0;
                    
                    // Handle years
                    $start_year = !empty($edu['start_year']) ? $edu['start_year'] : null;
                    $end_year = $current_study ? null : 
                               (!empty($edu['end_year']) ? $edu['end_year'] : null);

                    $eduStmt->bind_param("issis", 
                        $resume_id, 
                        $edu['school'], 
                        $start_year, 
                        $end_year, 
                        $current_study
                    );
                    $eduStmt->execute();
                }
                $eduStmt->close();
            }

            // Process and insert skills data
            if (isset($_POST['skills']) && is_array($_POST['skills'])) {
                $skillsSql = "INSERT INTO resume_skills (resume_id, skill_name) VALUES (?, ?)";
                $skillsStmt = $conn->prepare($skillsSql);

                foreach ($_POST['skills'] as $skill) {
                    $skill_name = trim($skill);

                    if (!empty($skill_name)) {
                        $skillsStmt->bind_param("is", $resume_id, $skill_name);
                        $skillsStmt->execute();
                    }
                }
                $skillsStmt->close();
            }

            // Process and insert certificates data
            if (isset($_POST['certificate-name']) && is_array($_POST['certificate-name'])) {
                $certSql = "INSERT INTO resume_certificates (resume_id, certificate_name, issuing_organization, start_date, end_date) 
                           VALUES (?, ?, ?, ?, ?)";
                $certStmt = $conn->prepare($certSql);

                $certCount = count($_POST['certificate-name']);
                for ($i = 0; $i < $certCount; $i++) {
                    $certName = trim($_POST['certificate-name'][$i]);
                    $issuingOrg = trim($_POST['issuing-organization'][$i]);
                    $startDate = !empty($_POST['start-date'][$i]) ? $_POST['start-date'][$i] : null;
                    $endDate = !empty($_POST['end-date'][$i]) ? $_POST['end-date'][$i] : null;

                    if (!empty($certName) || !empty($issuingOrg)) {
                        $certStmt->bind_param("issss", 
                            $resume_id, 
                            $certName, 
                            $issuingOrg, 
                            $startDate, 
                            $endDate
                        );
                        $certStmt->execute();
                    }
                }
                $certStmt->close();
            }

            // Commit transaction
            $conn->commit();

            // Redirect to success page
            header("Location: success.php");
            exit();

        } else {
            throw new Exception("Error inserting resume: " . $stmt->error);
        }

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }

    // Close main statement and connection
    $stmt->close();
    $conn->close();
}
?>

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

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update resume
        $sql = "UPDATE resumes SET 
                full_name = ?, 
                course = ?, 
                mobile_number = ?, 
                email = ?, 
                location = ?, 
                career_objective = ?";
        
        $params = [$full_name, $course, $phone_number, $email, $location, $career_objective];
        $types = "ssssss";

        if ($profile_picture !== "") {
            $sql .= ", profile_picture = ?";
            $params[] = $profile_picture;
            $types .= "s";
        }
        if ($signature !== "") {
            $sql .= ", signature = ?";
            $params[] = $signature;
            $types .= "s";
        }

        $sql .= " WHERE user_id = ?";
        $params[] = $user_id;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        // Get resume_id
        $resume_id_query = "SELECT id FROM resumes WHERE user_id = ?";
        $resume_id_stmt = $conn->prepare($resume_id_query);
        $resume_id_stmt->bind_param("i", $user_id);
        $resume_id_stmt->execute();
        $resume_id_result = $resume_id_stmt->get_result();
        $resume_id_row = $resume_id_result->fetch_assoc();
        $resume_id = $resume_id_row['id'];

        // Get existing experience IDs
        $existing_exp_query = "SELECT id FROM resume_experiences WHERE resume_id = ?";
        $existing_exp_stmt = $conn->prepare($existing_exp_query);
        $existing_exp_stmt->bind_param("i", $resume_id);
        $existing_exp_stmt->execute();
        $existing_exp_result = $existing_exp_stmt->get_result();
        $existing_exp_ids = [];
        while ($row = $existing_exp_result->fetch_assoc()) {
            $existing_exp_ids[] = $row['id'];
        }

        // Handle experiences
        if (isset($_POST['experience']) && is_array($_POST['experience'])) {
            $update_exp_query = "UPDATE resume_experiences SET 
                               company = ?, position = ?, start_date = ?, 
                               end_date = ?, currently_working = ?, description = ? 
                               WHERE id = ? AND resume_id = ?";
            $update_exp_stmt = $conn->prepare($update_exp_query);

            $insert_exp_query = "INSERT INTO resume_experiences 
                               (resume_id, company, position, start_date, end_date, currently_working, description) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insert_exp_stmt = $conn->prepare($insert_exp_query);

            $updated_exp_ids = [];

            foreach ($_POST['experience'] as $exp) {
                $currently_working = isset($exp['currently_working']) ? 1 : 0;
                $end_date = $currently_working ? NULL : $exp['end_date'];

                if (isset($exp['id']) && !empty($exp['id'])) {
                    // Update existing experience
                    $update_exp_stmt->bind_param("ssssisis", 
                        $exp['company'], 
                        $exp['position'], 
                        $exp['start_date'], 
                        $end_date,
                        $currently_working, 
                        $exp['description'],
                        $exp['id'],
                        $resume_id
                    );
                    $update_exp_stmt->execute();
                    $updated_exp_ids[] = $exp['id'];
                } else {
                    // Insert new experience
                    $insert_exp_stmt->bind_param("issssis", 
                        $resume_id,
                        $exp['company'], 
                        $exp['position'], 
                        $exp['start_date'], 
                        $end_date,
                        $currently_working, 
                        $exp['description']
                    );
                    $insert_exp_stmt->execute();
                }
            }
        }
        $existing_edu_query = "SELECT id FROM resume_education WHERE resume_id = ?";
        $existing_edu_stmt = $conn->prepare($existing_edu_query);
        $existing_edu_stmt->bind_param("i", $resume_id);
        $existing_edu_stmt->execute();
        $existing_edu_result = $existing_edu_stmt->get_result();
        $existing_edu_ids = [];
        while ($row = $existing_edu_result->fetch_assoc()) {
            $existing_edu_ids[] = $row['id'];
        }

        // Handle education entries
        if (isset($_POST['education']) && is_array($_POST['education'])) {
            $update_edu_query = "UPDATE resume_education SET 
                                school = ?, start_year = ?, end_year = ?, 
                                current_study = ? WHERE id = ? AND resume_id = ?";
            $update_edu_stmt = $conn->prepare($update_edu_query);

            $insert_edu_query = "INSERT INTO resume_education 
                                (resume_id, school, start_year, end_year, current_study) 
                                VALUES (?, ?, ?, ?, ?)";
            $insert_edu_stmt = $conn->prepare($insert_edu_query);

            $updated_edu_ids = [];

            foreach ($_POST['education'] as $edu) {
                $current_study = isset($edu['current_study']) ? 1 : 0;
                $end_year = $current_study ? NULL : $edu['end_year'];

                if (isset($edu['id']) && !empty($edu['id'])) {
                    // Update existing education
                    $update_edu_stmt->bind_param("siisii", 
                        $edu['school'], 
                        $edu['start_year'], 
                        $end_year,
                        $current_study,
                        $edu['id'],
                        $resume_id
                    );
                    $update_edu_stmt->execute();
                    $updated_edu_ids[] = $edu['id'];
                } else {
                    // Insert new education
                    $insert_edu_stmt->bind_param("isiis", 
                        $resume_id,
                        $edu['school'], 
                        $edu['start_year'], 
                        $end_year,
                        $current_study
                    );
                    $insert_edu_stmt->execute();
                }
            }

        }

// Handle skills
if (isset($_POST['skills']) && is_array($_POST['skills'])) {
    $update_skill_query = "UPDATE resume_skills SET skill_name = ? WHERE id = ? AND resume_id = ?";
    $update_skill_stmt = $conn->prepare($update_skill_query);

    $insert_skill_query = "INSERT INTO resume_skills (resume_id, skill_name) VALUES (?, ?)";
    $insert_skill_stmt = $conn->prepare($insert_skill_query);

    $updated_skill_ids = [];

    foreach ($_POST['skills'] as $skill) {
        if (isset($skill['id']) && !empty($skill['id'])) {
            // Update existing skill
            $update_skill_stmt->bind_param("sii", 
                $skill['name'], // skill_name
                $skill['id'],   // skill id
                $resume_id      // resume id
            );
            $update_skill_stmt->execute();
            $updated_skill_ids[] = $skill['id'];
        }
    }
}

// Handle new skill input (if any)
if (isset($_POST['new_skill']) && !empty(trim($_POST['new_skill']))) {
    $new_skill = trim($_POST['new_skill']);
    $insert_skill_query = "INSERT INTO resume_skills (resume_id, skill_name) VALUES (?, ?)";
    $insert_skill_stmt = $conn->prepare($insert_skill_query);
    $insert_skill_stmt->bind_param("is", $resume_id, $new_skill);
    $insert_skill_stmt->execute();
}




// Check if we have any certificate data
if (isset($_POST['certificate-name']) && is_array($_POST['certificate-name'])) {
    // Get existing certificates for this resume
    $existing_cert_query = "SELECT id FROM resume_certificates WHERE resume_id = ?";
    $existing_cert_stmt = $conn->prepare($existing_cert_query);
    $existing_cert_stmt->bind_param("i", $resume_id);
    $existing_cert_stmt->execute();
    $existing_cert_result = $existing_cert_stmt->get_result();
    $existing_cert_ids = [];
    while ($row = $existing_cert_result->fetch_assoc()) {
        $existing_cert_ids[] = $row['id'];
    }

    $updated_cert_ids = [];
    
    // Get the number of certificates submitted
    $cert_count = count($_POST['certificate-name']);
    
    // Process each certificate
    for ($i = 0; $i < $cert_count; $i++) {
        $cert_name = $_POST['certificate-name'][$i] ?? '';
        $issuing_org = $_POST['issuing-organization'][$i] ?? '';
        $start_date = $_POST['start-date'][$i] ?? '';
        $end_date = $_POST['end-date'][$i] ?? '';
        $cert_id = $_POST['cert-id'][$i] ?? null;

        // Only process if we have at least a name or organization
        if (!empty($cert_name) || !empty($issuing_org)) {
            if ($cert_id !== null) {
                // Update existing certificate
                $update_cert_query = "UPDATE resume_certificates SET 
                                    certificate_name = ?, 
                                    issuing_organization = ?, 
                                    start_date = ?, 
                                    end_date = ? 
                                    WHERE id = ? AND resume_id = ?";
                $update_cert_stmt = $conn->prepare($update_cert_query);
                $update_cert_stmt->bind_param("ssssii", 
                    $cert_name,
                    $issuing_org,
                    $start_date,
                    $end_date,
                    $cert_id,
                    $resume_id
                );
                $update_cert_stmt->execute();
                $updated_cert_ids[] = $cert_id;
            } else {
                // Insert new certificate
                $insert_cert_query = "INSERT INTO resume_certificates 
                                    (resume_id, certificate_name, issuing_organization, start_date, end_date) 
                                    VALUES (?, ?, ?, ?, ?)";
                $insert_cert_stmt = $conn->prepare($insert_cert_query);
                $insert_cert_stmt->bind_param("issss",
                    $resume_id,
                    $cert_name,
                    $issuing_org,
                    $start_date,
                    $end_date
                );
                $insert_cert_stmt->execute();
            }
        }
    }

    // Delete certificates that were removed
    foreach ($existing_cert_ids as $cert_id) {
        if (!in_array($cert_id, $updated_cert_ids)) {
            $delete_cert_query = "DELETE FROM resume_certificates WHERE id = ? AND resume_id = ?";
            $delete_cert_stmt = $conn->prepare($delete_cert_query);
            $delete_cert_stmt->bind_param("ii", $cert_id, $resume_id);
            $delete_cert_stmt->execute();
        }
    }
}





        // Commit transaction
        $conn->commit();

        echo "Resume updated successfully";
        header("Location: resume.php");
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }

    $conn->close();
}
?>
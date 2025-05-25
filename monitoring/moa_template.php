<?php
session_start();

// Database connection configuration
$servername = "localhost";
$username = "u798912504_root";
$password = "Internfloucc2025*"; // Update with your database password
$dbname = "u798912504_internflo";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => "Connection failed: " . $e->getMessage()]);
    exit();
}

// Function to get latest MOA record for the user
function getLatestMOA($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT m.*, GROUP_CONCAT(s.student_name) as student_names, 
                   GROUP_CONCAT(s.student_course) as student_courses
            FROM moa_records m
            LEFT JOIN moa_students s ON m.id = s.moa_id
            WHERE m.user_id = ?
            GROUP BY m.id
            ORDER BY m.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        $moa = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($moa) {
            // Get individual student records
            $stmt = $pdo->prepare("
                SELECT student_name, student_course
                FROM moa_students
                WHERE moa_id = ?
            ");
            $stmt->execute([$moa['id']]);
            $moa['students'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $moa;
    } catch (Exception $e) {
        return null;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        $user_id = $_SESSION['user_id'] ?? null;
        if (!$user_id) {
            throw new Exception("User not logged in");
        }
        
        // Insert main MOA record
        $stmt = $pdo->prepare("INSERT INTO moa_records (
            user_id, ojt_company, company_description, company_address,
            company_representative, representative_position, training_hours,
            city
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $user_id,
            $_POST['ojt_company'],
            $_POST['company_description'],
            $_POST['company_address'],
            $_POST['company_representative'],
            $_POST['representative_position'],
            $_POST['training_hours'],
            $_POST['city']
        ]);
        
        $moa_id = $pdo->lastInsertId();
        
        // Insert student records
        $stmt = $pdo->prepare("INSERT INTO moa_students (
            moa_id, student_name, student_course
        ) VALUES (?, ?, ?)");
        
        $student_names = $_POST['student_names'] ?? [];
        $student_course = $_POST['student_course'];
        
        foreach ($student_names as $student_name) {
            if (!empty($student_name)) {
                $stmt->execute([$moa_id, $student_name, $student_course]);
            }
        }
        
        $pdo->commit();
        echo json_encode(['success' => true]);
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit();
    }
}

// Get the latest MOA data for the current user
$user_id = $_SESSION['user_id'] ?? null;
$latestMOA = $user_id ? getLatestMOA($pdo, $user_id) : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/MOAtemplates.css">
    <link rel="icon" href="css/ucclogo2.png">
    <title>MOA</title>
</head>
<body>
    
    <div class="form-container">
    <a href="std_documents.php" class="back-link" style="display: inline-block; padding: 7px 13px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; font-family: Arial, sans-serif; font-size: 15px; transition: background-color 0.3s; margin-top: -10px;">← Back</a>
        <h2>MOA Information Form</h2>
        <form id="moaForm">

            <div class="form-group">
                <label>Student Name</label>
                <div id="students">
                    <?php if ($latestMOA && !empty($latestMOA['students'])): ?>
                    <?php foreach ($latestMOA['students'] as $index => $student): ?>
                        <div class="student-entry">
                            <input type="text" name="student_names[]" value="<?php echo htmlspecialchars($student['student_name']); ?>" required>
                            <?php if ($index > 0): ?> <!-- Show remove button for all except first entry -->
                                <button type="button" onclick="removeStudent(this)" class="remove-btn">Remove</button>
                            <?php endif; ?>
                        </div>
                            <?php endforeach; ?>
                             <?php else: ?>
                                <div class="student-entry">
                                    <input type="text" name="student_names[]" placeholder="Student Name" required>
                                </div>
                            <?php endif; ?>
                </div>
                    <button type="button" onclick="addStudent()" style="background: #4CAF50; width: 20%;">Add</button>
            </div>
            
            <div class="form-group">
                <label>Course:</label>
                <select name="student_course" class="live-input" required>
                    <option value="">Select a course</option>
                    <?php
                    $courses = [
                        "AB Political Science",
                        "BA Communication",
                        "Bachelor of Public Administration",
                        "Bachelor of Science in Computer Science",
                        "Bachelor of Science in Entertainment and Multimedia Computing",
                        "Bachelor of Science in Information System",
                        "Bachelor of Science in Information Technology",
                        "Bachelor of Science in Mathematics",
                        "Bachelor of Science in Psychology"
                    ];
                    foreach ($courses as $course):
                        $selected = ($latestMOA && $latestMOA['students'][0]['student_course'] === $course) ? 'selected' : '';
                    ?>
                        <option value="<?php echo htmlspecialchars($course); ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($course); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>OJT Company</label>
                <input type="text" name="ojt_company" value="<?php echo $latestMOA ? htmlspecialchars($latestMOA['ojt_company']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label>Company Description</label>
                <textarea name="company_description" rows="3" style="width: 90%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;" required><?php echo $latestMOA ? htmlspecialchars($latestMOA['company_description']) : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Company Address</label>
                <input type="text" name="company_address" value="<?php echo $latestMOA ? htmlspecialchars($latestMOA['company_address']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Company Representative</label>
                <input type="text" name="company_representative" value="<?php echo $latestMOA ? htmlspecialchars($latestMOA['company_representative']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Representative Position</label>
                <input type="text" name="representative_position" value="<?php echo $latestMOA ? htmlspecialchars($latestMOA['representative_position']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Training Hours</label>
                <input type="number" name="training_hours" value="<?php echo $latestMOA ? htmlspecialchars($latestMOA['training_hours']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="city">City:</label>
                <input type="text" id="city" name="city" value="<?php echo $latestMOA ? htmlspecialchars($latestMOA['city']) : ''; ?>" required>
            </div>
            
            <!--<div id="witnesses">
                <h3>Witnesses</h3>
                <div class="witness-entry">
                    <input type="text" name="witness_names[]" placeholder="Witness Name" required>
                    <input type="text" name="witness_positions[]" placeholder="Position" required>
                </div>
            </div>-->
        

        </form>
    </div>
    
    <div class="preview-container">
    <div class="preview-content" id="moaPreview">
        <!-- MOA content will be dynamically inserted here -->
    </div>
    
    <div class="button-container">
        <button type="button" onclick="saveForm()">Save</button>
        <button type="button" onclick="generatePDF()">Generate PDF</button>
    </div>
</div>

      <script src="templateScript/MOAtemplates.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</body>
</html>
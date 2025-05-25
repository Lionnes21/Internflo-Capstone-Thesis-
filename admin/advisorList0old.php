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
    $advisorId = $conn->real_escape_string($_POST['advisorId']);
    $programId = $conn->real_escape_string($_POST['programId']);
    $courseId = $conn->real_escape_string($_POST['courseId']);
    $year = $conn->real_escape_string($_POST['year']);
    $section = $conn->real_escape_string($_POST['section']);

    // Prepare and execute the SQL statement
    $stmt = $conn->prepare("INSERT INTO m_advisor_assignments (advisor_id, program_id, course_id, year, section) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiss", $advisorId, $programId, $courseId, $year, $section);

    if ($stmt->execute()) {
        echo "Advisor assigned successfully";

        // Fetch the assigned advisor information
        $assignedAdvisorQuery = "
            SELECT 
                a.full_name,
                c.name AS course_name,
                aa.year,
                aa.section
            FROM m_advisor_assignments aa
            JOIN m_advisors a ON aa.advisor_id = a.id
            JOIN m_courses c ON aa.course_id = c.id
            WHERE aa.advisor_id = ? AND aa.course_id = ? AND aa.year = ? AND aa.section = ?
        ";
        $assignedAdvisorStmt = $conn->prepare($assignedAdvisorQuery);
        $assignedAdvisorStmt->bind_param("iiss", $advisorId, $courseId, $year, $section);
        $assignedAdvisorStmt->execute();
        $result = $assignedAdvisorStmt->get_result();
        $assignedAdvisor = $result->fetch_assoc();

        if ($assignedAdvisor) {
            echo "<h3>Assigned Advisor:</h3>";
            echo "Name: " . $assignedAdvisor['full_name'] . "<br>";
            echo "Course: " . $assignedAdvisor['course_name'] . "<br>";
            echo "Year: " . $assignedAdvisor['year'] . "<br>";
            echo "Section: " . $assignedAdvisor['section'] . "<br>";
        } else {
            echo "No assigned advisor found.";
        }

        $assignedAdvisorStmt->close();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Fetch advisors
$advisorQuery = "SELECT id, CONCAT(last_name, ', ', first_name, ' ', middle_initial, ' ', suffix) AS full_name FROM m_advisors";
$advisorResult = $conn->query($advisorQuery);

// Fetch programs
$programQuery = "SELECT id, name FROM m_programs";
$programResult = $conn->query($programQuery);

// Fetch courses
$courseQuery = "SELECT id, name, program_id FROM m_courses";
$courseResult = $conn->query($courseQuery);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- HTML head section -->
</head>
<body>
<section id="sidebar">
    <!-- Sidebar content -->
</section>

    <main>
        <div class="form-container">
            <h2>Assign Advisor</h2>
            <form action="assign_advisor.php" method="POST">
                <!-- Form fields -->
            </form>

            <!-- Display the assigned advisor information -->
            <div id="assigned-advisor"></div>
        </div>
    </main>

    <script>
        // JavaScript code for filtering courses based on selected program
    </script>
</body>
</html>
<?php
session_start();

// Check if user is logged in
if (isset($_SESSION['advisor_id'])) {
    $_SESSION['user_id'] = $_SESSION['advisor_id'];
}

// Check if user is logged in
if (!isset($_SESSION['advisor_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

// Database connection
    $servername = "localhost";
    $username = "u798912504_root";
    $password = "Internfloucc2025*"; // Update with your database password
    $dbname = "u798912504_internflo";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get advisor information
    $stmt = $conn->prepare("SELECT id, advisor_id, first_name, last_name FROM m_advisors WHERE advisor_id = ?");
    $stmt->execute([$_SESSION['advisor_id']]);
    $advisor = $stmt->fetch(PDO::FETCH_ASSOC);

    // Set the variables that were undefined
    $fullName = $advisor['first_name'] . ' ' . $advisor['last_name'];
    $advisorId = $advisor['advisor_id'];
    
    // Get assigned courses for this advisor
    $stmt = $conn->prepare("
        SELECT DISTINCT 
            aa.program_id,
            aa.course_id,
            aa.year,
            aa.section,
            p.name as program_name,
            c.name as course_name
        FROM m_advisor_assignments aa
        JOIN m_programs p ON p.id = aa.program_id
        JOIN m_courses c ON c.id = aa.course_id
        WHERE aa.advisor_id = ?
    ");
    $stmt->execute([$advisor['id']]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle announcement submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $stmt = $conn->prepare("
                INSERT INTO m_announcements 
                (advisor_id, program_id, course_id, year, section, title, content) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $advisor['id'],
                $_POST['program_id'],
                $_POST['course_id'],
                $_POST['year'],
                $_POST['section'],
                $_POST['title'],
                $_POST['content']
            ]);
            
            // Set success message
            $_SESSION['announcement_message'] = 'Announcement created successfully!';
            $_SESSION['announcement_message_type'] = 'success';
            
            // Redirect to prevent form resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();

        } elseif ($_POST['action'] === 'delete' && isset($_POST['announcement_id'])) {
            $stmt = $conn->prepare("DELETE FROM m_announcements WHERE id = ? AND advisor_id = ?");
            $stmt->execute([$_POST['announcement_id'], $advisor['id']]);
            
            // Set success message
            $_SESSION['announcement_message'] = 'Announcement deleted successfully!';
            $_SESSION['announcement_message_type'] = 'success';
            
            // Redirect to prevent form resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }

    // Get announcements for this advisor's assigned courses
    $stmt = $conn->prepare("
        SELECT 
            a.*,
            p.name as program_name,
            c.name as course_name
        FROM m_announcements a
        JOIN m_programs p ON p.id = a.program_id
        JOIN m_courses c ON c.id = a.course_id
        WHERE a.advisor_id = ?
        ORDER BY a.date_posted DESC
    ");
    $stmt->execute([$advisor['id']]);
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>

<html lang="en" dir="ltr">
  <head>
    <meta charset="UTF-8">
    <!--Drop Down Sidebar Menu -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="Ins_css/announcement.css">
    <link rel="icon" href="Ins_css/ucclogo2.png">

    <!-- Boxiocns CDN Link -->
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
     <meta name="viewport" content="width=device-width, initial-scale=1.0">

     <!-- SweetAlert popup message for logout -->
     <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
     </head>

     <body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <a href="#" class="brand">
            <img src="Ins_css/ucc1.png" alt="Logo" class="logo-img">
        </a>
        <ul class="side-menu">
            <li><a href="InsDashboard.php"><i class='bx bxs-dashboard icon'></i> Dashboard</a></li>
            <li><a href="Ins_Student.php"><i class='bx bxs-contact icon'></i> Student List</a></li>
            <!--<li><a href="Ins_timelog.php"><i class='bx bx-time icon'></i> Timelogs</a></li>-->
            <li><a href="Ins_Report.php"><i class='bx bxs-report icon'></i> Reports</a></li>
            <li><a href="documents.php"><i class='bx bxs-file-doc icon'></i> Documents</a></li>
            <li><a href="#" class="active"><i class='bx bxs-chat icon'></i> Announcement</a></li>
            <li><a href="chat-inbox.php"><i class='bx bxs-chat icon'></i> Emails</a></li>

        </ul>
        <br><br><br>
        <div class="ads">
            <div class="wrapper" style="margin-top: -80px;">
                <a href="../../COORDINATOR/companysignin.php" class="btn-upgrade">
                    <i class='bx bx-log-out' style="float: left; margin-left: 15px; margin-right: -20px; font-size: 20px;"></i>
                    <span style="display: inline-block; width: 100%; text-align: center;">Log out</span>
                </a>
            </div>
        </div>
    </section>

        <!-- CONTENT -->
        <section id="content">
        <!-- NAVBAR -->
        <nav>
    <i class='bx bx-menu toggle-sidebar'></i>
    <div class="profile">
        <div class="profile-info">
        <span class="user-name"><?php echo htmlspecialchars($fullName); ?></span>
        <span class="user-id"><?php echo htmlspecialchars($advisorId); ?></span>
        </div>
    </div>
</nav>
 

<main>

        <div class="announcement-container">
            <!-- Add Announcement Section -->
            <div class="add-announcement">
                <h3>Add New Announcement</h3>
                <form class="announcement-form" method="POST">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="form-group">
                        <select name="assignment" required>
                            <option value="">Select Course and Year</option>
                            <?php foreach ($assignments as $assignment): ?>
                                <option value="<?php echo htmlspecialchars(json_encode([
                                    'program_id' => $assignment['program_id'],
                                    'course_id' => $assignment['course_id'],
                                    'year' => $assignment['year'],
                                    'section' => $assignment['section']
                                ])); ?>">
                                    <?php echo htmlspecialchars("{$assignment['program_name']} - {$assignment['course_name']} Year {$assignment['year']}"); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <input type="text" name="title" placeholder="Title" required>
                    </div>

                    <div class="form-group">
                        <textarea name="content" placeholder="Details" required></textarea>
                    </div>

                    <button type="submit">Post</button>
                </form>
            </div>
        
            <!-- Recent Announcements Section -->
            <div class="recent-announcements">
                <h2>Recent Announcements</h2>
                <?php foreach ($announcements as $announcement): ?>
                <div class="announcement">
                    <div class="announcement-header">
                        <div>
                            <strong>Title:  <?php echo htmlspecialchars($announcement['title']); ?></strong>
                            <p class="announcement-meta">
                                <?php echo htmlspecialchars("{$announcement['program_name']} - {$announcement['course_name']} Year {$announcement['year']}"); ?>
                            </p>
                        </div>
                        <div class="announcement-buttons">
                            <form method="POST" class="delete-form">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                                <button type="submit" class="delete-btn"><i class="fas fa-trash-alt"></i> Delete</button>
                            </form>
                        </div>
                    </div>
                    <p>Content: <?php echo htmlspecialchars($announcement['content']); ?></p>
                    <p class="announcement-date">Posted on: <?php echo date('F j, Y g:i A', strtotime($announcement['date_posted'])); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
</main>
    </section>


  <!--Footer
  <div class="underfooter-bg">
      <div class="underfooter">
          <div class="uf-content">
              <p>Copyright Internflo©2024. All Rights Reserved</p>
          </div>
      </div>
  </div>-->

  <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle announcement creation and deletion messages
            <?php if(isset($_SESSION['announcement_message'])): ?>
                Swal.fire({
                    title: '<?php echo $_SESSION['announcement_message']; ?>',
                    icon: '<?php echo $_SESSION['announcement_message_type']; ?>',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 800,
                    timerProgressBar: true
                });
        // Clear the session messages
        <?php 
        unset($_SESSION['announcement_message']);
        unset($_SESSION['announcement_message_type']);
        ?>
    <?php endif; ?>

            // Add confirmation for announcement deletion
            document.querySelectorAll('.delete-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault(); // Prevent immediate form submission
                    
                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'Do you want to delete this announcement?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.submit(); // Submit form only if confirmed
                        }
                    });
                });
            });

            // Add event listener for form submission
            document.querySelector('.announcement-form').addEventListener('submit', function(e) {
                const assignmentSelect = this.querySelector('select[name="assignment"]');
                if (assignmentSelect.value) {
                    const assignment = JSON.parse(assignmentSelect.value);
                    
                    // Create hidden inputs for the parsed values
                    for (const [key, value] of Object.entries(assignment)) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = value;
                        this.appendChild(input);
                    }
                }
            });
        });
    </script>



  <!-- SweetAlert popup message for logout -->

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 

  <script src="Ins_js/mobileResponsive.js"></script>
  <script src="Ins_css/student_dashboard.js"></script>
</body>
</html>
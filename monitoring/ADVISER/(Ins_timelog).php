<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['advisor_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "student_registration";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get advisor's information
    $stmt = $conn->prepare("SELECT id, first_name, last_name FROM m_advisors WHERE advisor_id = ?");
    $stmt->execute([$_SESSION['advisor_id']]);
    $advisor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Fetch timelogs for students in advisor's assigned courses and years
    $timelogQuery = "
        SELECT 
            CONCAT(s.last_name, ', ', s.first_name, ' ', COALESCE(s.middle_name, '')) as student_name,
            s.student_id,
            t.date,
            t.time_in,
            t.time_out,
            t.activity,
            t.image_path
        FROM m_timelog t
        INNER JOIN students s ON t.student_id = s.student_id
        INNER JOIN m_courses c ON s.course = c.name
        INNER JOIN m_advisor_assignments aa ON c.id = aa.course_id
        WHERE aa.advisor_id = ? 
        AND s.school_year = aa.year
        ORDER BY t.date DESC, t.time_in DESC";
    
    $stmt = $conn->prepare($timelogQuery);
    $stmt->execute([$advisor['id']]);
    $timelogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $fullName = htmlspecialchars($advisor['first_name'] . ' ' . $advisor['last_name']);
    $advisorId = htmlspecialchars($_SESSION['advisor_id']);
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
    <link rel="stylesheet" href="Ins_css/Ins_timelog1.css">
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
            <img src="Ins_css/ucc_logo.png" alt="Logo" class="logo-img">
            <span><span class="intern">Intern</span><span class="flo">flo</span><span class="dot">.</span></span>
        </a>
        <ul class="side-menu">
            <li><a href="InsDashboard.php"><i class='bx bxs-dashboard icon'></i> Dashboard</a></li>
            <li><a href="Ins_Student.php"><i class='bx bxs-contact icon'></i> Student List</a></li>
            <li><a href="#" class="active"><i class='bx bx-time icon'></i> Timelogs</a></li>
            <li><a href="Ins_Report.php"><i class='bx bxs-report icon'></i> Reports</a></li>
            <li><a href="documents.php"><i class='bx bxs-file-doc icon'></i> Documents</a></li>
            <li><a href="announcement.php"><i class='bx bxs-chat icon'></i> Announcement</a></li>

        </ul>
        <div class="ads">
            <div class="wrapper">
                <a href="../STUDENTLOGIN/studentmain.php" class="btn-upgrade">
                    <i class='bx bx-log-out'></i> Back to Home
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


        <br><br><div class="activity-log">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Student ID</th>
                        <th>Date</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Activity Description</th>
                        <th>Image</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($timelogs)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">No timelogs found for your assigned students.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($timelogs as $log): ?>
                        <tr>
                            <td data-label="Name"><?php echo htmlspecialchars($log['student_name']); ?></td>
                            <td data-label="Student ID"><?php echo htmlspecialchars($log['student_id']); ?></td>
                            <td data-label="Date"><?php echo htmlspecialchars($log['date']); ?></td>
                            <td data-label="Time In"><?php echo htmlspecialchars($log['time_in']); ?></td>
                            <td data-label="Time Out"><?php echo htmlspecialchars($log['time_out']); ?></td>
                            <td data-label="Activity Description"><?php echo htmlspecialchars($log['activity']); ?></td>
                            <td data-label="Image">
                                        <?php if (!empty($log['image_path'])): ?>
                                            <i class='bx bx-image view-icon' 
                                               onclick="openModal('timelogImage_upload<?php echo htmlspecialchars($log['image_path']); ?>')"
                                               style="cursor: pointer; font-size: 24px; color:#4CAF50;"></i>
                                        <?php else: ?>
                                            <span>N/A</span>
                                        <?php endif; ?>
                                    </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</section>

    <!-- Modal for displaying images -->
    <div id="imageModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <img id="modalImage" src="" alt="Timecard Image">
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Function to open the modal
        function openModal(imagePath) {
            var modal = document.getElementById("imageModal");
            var modalImg = document.getElementById("modalImage");
            modal.style.display = "block";
            modalImg.src = imagePath;
        }

        // Function to close the modal
        function closeModal() {
            var modal = document.getElementById("imageModal");
            modal.style.display = "none";
        }

        // Close the modal when clicking outside of it
        window.onclick = function(event) {
            var modal = document.getElementById("imageModal");
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>





  <!--Footer
  <div class="underfooter-bg">
      <div class="underfooter">
          <div class="uf-content">
              <p>Copyright Internflo©2024. All Rights Reserved</p>
          </div>
      </div>
  </div>-->

  <!-- SweetAlert popup message for logout -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
  <script src="Ins_js/mobileResponsive.js"></script>
  <script src="Ins_css/student_dashboard.js"></script>
</body>
</html>
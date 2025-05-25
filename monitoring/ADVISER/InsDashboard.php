<?php
    session_start();

    // Check if user is logged in
    // Add user_id session variable with the same value as advisor_id
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

        // Now that we have a connection, we can query for the advisor's information
        $stmt = $conn->prepare("SELECT first_name, last_name FROM m_advisors WHERE advisor_id = ?");
        $stmt->execute([$_SESSION['advisor_id']]);
        $advisor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $fullName = htmlspecialchars($advisor['first_name'] . ' ' . $advisor['last_name']);
        $advisorId = htmlspecialchars($_SESSION['advisor_id']);
    } catch (PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
        $fullName = "Error loading name";
        $advisorId = "Error";
        exit();
    }


    function getTotalAssignedStudents($conn, $advisorId) {
    $query = "SELECT COUNT(DISTINCT s.student_id) as total
                FROM students s
                INNER JOIN m_advisor_assignments aa ON s.course = (
                    SELECT name FROM m_courses WHERE id = aa.course_id
                )
                INNER JOIN m_advisors a ON aa.advisor_id = a.id
                WHERE a.advisor_id = ? 
                AND s.school_year = aa.year";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$advisorId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ?? 0;
    }

    function getTotalSubmittedReports($conn, $advisorId) {
    $query = "SELECT COUNT(DISTINCT wr.id) as total
                FROM m_weekly_reports wr
                INNER JOIN students s ON wr.student_id = s.student_id
                INNER JOIN m_advisor_assignments aa ON s.course = (
                    SELECT name FROM m_courses WHERE id = aa.course_id
                )
                INNER JOIN m_advisors a ON aa.advisor_id = a.id
                WHERE a.advisor_id = ? 
                AND s.school_year = aa.year";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$advisorId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ?? 0;
    }

    function getOJTDocumentsCounts($conn, $advisorId) {
    $query = "SELECT 
                od.document_type,
                COUNT(DISTINCT od.student_id) as submitted_count,
                (SELECT COUNT(DISTINCT s.student_id)
                FROM students s
                INNER JOIN m_advisor_assignments aa ON s.course = (
                    SELECT name FROM m_courses WHERE id = aa.course_id
                )
                INNER JOIN m_advisors a ON aa.advisor_id = a.id
                WHERE a.advisor_id = ? 
                AND s.school_year = aa.year) as total_students
                FROM m_ojt_documents od
                RIGHT JOIN students s ON od.student_id = s.student_id
                INNER JOIN m_advisor_assignments aa ON s.course = (
                    SELECT name FROM m_courses WHERE id = aa.course_id
                )
                INNER JOIN m_advisors a ON aa.advisor_id = a.id
                WHERE a.advisor_id = ?
                AND s.school_year = aa.year
                GROUP BY od.document_type";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$advisorId, $advisorId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function getRecentAnnouncements($conn, $advisorId) {
    $query = "SELECT 
                a.title,
                a.content,
                a.date_posted
                FROM m_announcements a
                INNER JOIN m_advisor_assignments aa ON 
                a.program_id = aa.program_id AND
                a.course_id = aa.course_id AND
                a.year = aa.year
                INNER JOIN m_advisors adv ON aa.advisor_id = adv.id
                WHERE adv.advisor_id = ?
                ORDER BY a.date_posted DESC
                LIMIT 5";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$advisorId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get the data
    $totalStudents = getTotalAssignedStudents($conn, $_SESSION['advisor_id']);
    $totalReports = getTotalSubmittedReports($conn, $_SESSION['advisor_id']);
    $documentCounts = getOJTDocumentsCounts($conn, $_SESSION['advisor_id']);
    $recentAnnouncements = getRecentAnnouncements($conn, $_SESSION['advisor_id']);

    // Prepare data for chart
    $documentTypes = [];
    $submittedCounts = [];
    foreach ($documentCounts as $doc) {
        $documentTypes[] = $doc['document_type'];
        $submittedCounts[] = $doc['submitted_count'];
    }

    // Calculate total documents submitted vs required
    $totalSubmitted = 0;
    $totalRequired = 0;
    foreach ($documentCounts as $doc) {
    $totalSubmitted += $doc['submitted_count'];
    $totalRequired += $doc['total_students'];
    }

?>

<!DOCTYPE html>

<html lang="en" dir="ltr">
  <head>
    <meta charset="UTF-8">
    <!--Drop Down Sidebar Menu -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="Ins_css/dashboard1.css">
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
            <li><a href="#" class="active"><i class='bx bxs-dashboard icon'></i> Dashboard</a></li>
            <li><a href="Ins_Student.php"><i class='bx bxs-contact icon'></i> Student List</a></li>
            <!--<li><a href="Ins_timelog.php"><i class='bx bx-time icon'></i> Timelogs</a></li>-->
            <li><a href="Ins_Report.php"><i class='bx bxs-report icon'></i> Reports</a></li>
            <li><a href="documents.php"><i class='bx bxs-file-doc icon'></i> Documents</a></li>
            <li><a href="announcement.php"><i class='bx bxs-chat icon'></i> Announcement</a></li>
            <li><a href="chat-inbox.php"><i class='bx bxs-chat icon'></i> Emails</a></li>

        </ul>
        <br><br><br>
        <div class="ads">
            <div class="wrapper">
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
    <form action="#">
				<div class="form-group">
					<input type="text" placeholder="Search...">
					<i class='bx bx-search icon' ></i>
				</div>
	</form>
    <div class="profile">
        <div class="profile-info">
        <span class="user-name"><?php echo htmlspecialchars($fullName); ?></span>
        <span class="user-id"><?php echo htmlspecialchars($advisorId); ?></span>
        </div>
    </div>
</nav>
 

<main>


          <!-- FOR TOTAL OF STUDENTS -->
    <div class="dashboard-overview">
      <div class="card">
        <div class="card-details">
            <br>
            <span class="card-title">Total Students</span><br>
            <span class="card-value"><?php echo $totalStudents; ?></span>
        </div>
      </div>

      <!-- FOR TOTAL OF SUBMITTED REPORTS -->
      <div class="card">
        <div class="card-details">
            <br>
            <span class="card-title">Submitted Reports</span><br>
            <span class="card-value"><?php echo $totalReports; ?></span>
        </div>
      </div>
          
      <!-- FOR TOTAL OF OJT SUBMITTED DOCUMENTS -->
      <div class="card">
        <div class="card-details">
            <span class="card-title">OJT Documents</span>
              <select class="document-dropdown">
                  <?php foreach ($documentCounts as $doc): ?>
                      <option>
                          <?php echo htmlspecialchars($doc['document_type']); ?>: 
                          <?php echo $doc['submitted_count']; ?>/<?php echo $doc['total_students']; ?>
                      </option>
                  <?php endforeach; ?>
              </select>
        </div>
      </div>
    </div>

    <!-- New Container for Chart and Announcements -->
    <div class="dashboard-bottom-section">
    <div class="chart-container">
        <h3>Document Submissions</h3>
        <div class="chart-wrapper">
            <canvas id="documentChart"></canvas>
        </div>
    </div>
    
    <div class="announcements-section">
        <h3>Recent Announcements</h3>
        <div class="announcements-list">
            <?php if (empty($recentAnnouncements)): ?>
                <p>No recent announcements</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($recentAnnouncements as $announcement): ?>
                        <li>
                            
                            <strong><?php echo htmlspecialchars($announcement['title']); ?></strong>
                            <span class="announcement-date">
                                <?php echo date('M d, Y', strtotime($announcement['date_posted'])); ?>
                            </span>
                            <p><?php echo htmlspecialchars($announcement['content']); ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
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


  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
    <script src="Ins_css/student_dashboard.js"></script>
    <script src="Ins_js/dashboard.js"></script>
    <script src="Ins_js/mobileResponsive.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var ctx = document.getElementById('documentChart').getContext('2d');
        var documentChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($documentTypes); ?>,
                datasets: [{
                    label: 'Document Submissions',
                    data: <?php echo json_encode($submittedCounts); ?>,
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.6)',
                        'rgba(255, 159, 64, 0.6)',
                        'rgba(54, 162, 235, 0.6)',
                        'rgba(255, 99, 132, 0.6)'
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 99, 132, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        min: 0,
                        max: 50,
                        ticks: {
                            stepSize: 10
                        },
                        title: {
                            display: true,
                            text: 'Number of Documents Submitted'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    });
    </script>

  
</body>
</html>
<?php
session_start();

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

    // Get advisor's information
    $stmt = $conn->prepare("SELECT first_name, last_name, id FROM m_advisors WHERE advisor_id = ?");
    $stmt->execute([$_SESSION['advisor_id']]);
    $advisor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $fullName = htmlspecialchars($advisor['first_name'] . ' ' . $advisor['last_name']);
    $advisorId = htmlspecialchars($_SESSION['advisor_id']);
    
    // Get assigned courses and years for the advisor
    $stmt = $conn->prepare("
        SELECT DISTINCT aa.course_id, aa.year
        FROM m_advisor_assignments aa
        WHERE aa.advisor_id = ?
    ");
    $stmt->execute([$advisor['id']]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    
    // Prepare query to fetch documents from assigned students
    $documentsQuery = "
        SELECT 
            s.first_name,
            s.last_name,
            s.student_id,
            d.document_type,
            d.upload_date,
            d.file_path
        FROM students s
        INNER JOIN m_ojt_documents d ON s.student_id = d.student_id
        INNER JOIN m_courses c ON s.course = c.name
        WHERE (";
    
    $conditions = [];
    $params = [];
    foreach ($assignments as $assignment) {
        $conditions[] = "(c.id = ? AND s.school_year = ?)";
        $params[] = $assignment['course_id'];
        $params[] = $assignment['year'];
    }
    
    if (empty($conditions)) {
        $documents = [];
    } else {
        $documentsQuery .= implode(" OR ", $conditions) . ")
            ORDER BY s.last_name, s.first_name, d.document_type";
        
        $stmt = $conn->prepare($documentsQuery);
        $stmt->execute($params);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit();
}

// Pagination setup
$resultsPerPage = 3; // Number of results per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page, default to 1
$offset = ($page - 1) * $resultsPerPage;

// Count total uploads FOR THIS ADVISOR ONLY
$countQuery = "SELECT COUNT(*) AS total FROM m_instructor_documents WHERE advisor_id = :advisor_id";
$countStmt = $conn->prepare($countQuery);
$countStmt->execute([':advisor_id' => $_SESSION['advisor_id']]);
$totalUploads = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Calculate total pages
$totalPages = ceil($totalUploads / $resultsPerPage);

$query = "
    SELECT
        id.upload_date,
        id.file_name,
        id.file_path,
        p.name as program_name,
        c.name as course_name,
        id.year
    FROM m_instructor_documents id
    LEFT JOIN m_programs p ON p.id = id.program_id
    LEFT JOIN m_courses c ON c.id = id.course_id
    WHERE id.advisor_id = :advisor_id
    ORDER BY id.upload_date DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $conn->prepare($query);
$stmt->bindValue(':advisor_id', $_SESSION['advisor_id'], PDO::PARAM_INT);
$stmt->bindValue(':limit', $resultsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="en" dir="ltr">
  <head>
    <meta charset="UTF-8">
    <!--Drop Down Sidebar Menu -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="Ins_css/document1.css">
    <link rel="icon" href="Ins_css/ucclogo2.png">

    <!-- Boxiocns CDN Link -->
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
     <meta name="viewport" content="width=device-width, initial-scale=1.0">

     <!-- SweetAlert popup message for logout -->
     <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.32/sweetalert2.min.css">
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
            <li><a href="#" class="active"><i class='bx bxs-file-doc icon'></i> Documents</a></li>
            <li><a href="announcement.php"><i class='bx bxs-chat icon'></i> Announcement</a></li>
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

    <br><br>
    <div class="upload-section">
    <form id="uploadForm" action="upload_instructor_documents.php" method="POST" enctype="multipart/form-data">
        <label for="fileUpload">Upload Document:</label>
        <input type="file" id="fileUpload" name="files[]" accept=".pdf,.doc,.docx,.jpg,.png" required multiple>

        <select id="documentType" name="documentType" required>
            <option value="">Select Course and Year</option>
            <?php foreach ($assignments as $assignment): ?>
                <option value="<?php echo htmlspecialchars(json_encode([
                    'program_id' => $assignment['program_id'],
                    'course_id' => $assignment['course_id'],
                    'year' => $assignment['year']     
                ])); ?>">
                    <?php echo htmlspecialchars("{$assignment['course_name']} Year {$assignment['year']}"); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Upload</button>
    </form>
</div>

<div class="recent-uploads"> 
    <h2>Recent Uploads</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>File Name</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php
            foreach ($uploads as $upload) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($upload['upload_date']) . "</td>";
                echo "<td>" . htmlspecialchars($upload['file_name']) . "</td>";
                echo "<td>
                    <a href='../uploads/" . htmlspecialchars($upload['file_path']) . "' target='_blank' class='view-btn'>View</a>
                    /
                    <a href='javascript:void(0)' onclick='confirmDelete(\"" . htmlspecialchars($upload['file_path']) . "\")' style='color: red; text-decoration: none;'>Delete</a>
                </td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>

    <!-- Pagination Links -->
    <div class="pagination">
        <?php
        // Previous page link
        if ($page > 1) {
            echo "<a href='?page=" . ($page - 1) . "' class='pagination-link'>&laquo; Previous</a>";
        }

        // Page numbers
        for ($i = 1; $i <= $totalPages; $i++) {
            $activeClass = ($i == $page) ? 'active' : '';
            echo "<a href='?page=$i' class='pagination-link $activeClass'>$i</a>";
        }

        // Next page link
        if ($page < $totalPages) {
            echo "<a href='?page=" . ($page + 1) . "' class='pagination-link'>Next &raquo;</a>";
        }
        ?>
    </div>
</div>


<br><br><br>
    <div class="document-list">
        <h2>Submitted Documents</h2>

        <div class="search-container">
            <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Search..." />
            <i class="fas fa-search search-icon"></i>
        </div>

        <table id="documentTable">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Student ID</th>
                    <th>Document Type</th>
                    <th>Date Submitted</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php
                    $currentStudent = null;
                    $rowspan = 0;
                    $studentDocs = [];
                    
                    // First, group documents by student
                    foreach ($documents as $doc) {
                        $studentId = $doc['student_id'];
                        if (!isset($studentDocs[$studentId])) {
                            $studentDocs[$studentId] = [
                                'name' => $doc['first_name'] . ' ' . $doc['last_name'],
                                'student_id' => $studentId,
                                'documents' => []
                            ];
                        }
                        $studentDocs[$studentId]['documents'][] = $doc;
                    }
                    
                    // Then output the grouped data
                    foreach ($studentDocs as $studentData) {
                        $first = true;
                        $rowspan = count($studentData['documents']);
                        
                        foreach ($studentData['documents'] as $doc) {
                            echo "<tr>";
                            if ($first) {
                                echo "<td rowspan='" . $rowspan . "'>" . htmlspecialchars($studentData['name']) . "</td>";
                                echo "<td rowspan='" . $rowspan . "'>" . htmlspecialchars($studentData['student_id']) . "</td>";
                                $first = false;
                            }
                            echo "<td>" . htmlspecialchars($doc['document_type']) . "</td>";
                            echo "<td>" . ($doc['upload_date'] ? htmlspecialchars($doc['upload_date']) : '-') . "</td>";
                            
                            // Modified to use a full path to the uploads directory
                            $fullFilePath = '../uploads/' . basename($doc['file_path']);
                            echo "<td><a href='" . htmlspecialchars($fullFilePath) . "' target='_blank'>View</a></td>";
                            echo "</tr>";
                        }
                    }
                    ?>
            </tbody>
        </table>
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

<!--FOR SEARCH BAR FUNCTION -->
<script>
function searchTable() {
    var input = document.getElementById("searchInput");
    var filter = input.value.toLowerCase();
    var table = document.getElementById("documentTable");
    var rows = table.getElementsByTagName("tr");
    
    // Skip header row
    for (var i = 1; i < rows.length; i++) {
        var row = rows[i];
        var cells = row.getElementsByTagName("td");
        var showRow = false;
        
        // Get the parent row if this is a child row
        var parentRow = null;
        var rowspanCell = null;
        if (!cells[0].hasAttribute("rowspan")) {
            // This is a child row, find the parent row
            for (var j = i - 1; j >= 0; j--) {
                if (rows[j].getElementsByTagName("td")[0].hasAttribute("rowspan")) {
                    parentRow = rows[j];
                    break;
                }
            }
        }
        
        if (cells.length > 0) {
            var searchText = "";
            
            if (cells[0].hasAttribute("rowspan")) {
                // This is a parent row
                // Include student name and ID in search
                searchText = cells[0].textContent + " " + // Student Name
                           cells[1].textContent + " " + // Student ID
                           cells[2].textContent + " " + // Document Type
                           cells[3].textContent; // Date
            } else if (parentRow) {
                // This is a child row
                var parentCells = parentRow.getElementsByTagName("td");
                searchText = parentCells[0].textContent + " " + // Student Name from parent
                           parentCells[1].textContent + " " + // Student ID from parent
                           cells[0].textContent + " " + // Document Type
                           cells[1].textContent; // Date
            }
            
            if (searchText.toLowerCase().indexOf(filter) > -1) {
                showRow = true;
                // If this is a child row, also show its parent row
                if (parentRow) {
                    parentRow.style.display = "";
                }
                // Show all sibling rows that share the same parent
                if (cells[0].hasAttribute("rowspan")) {
                    var rowspanValue = parseInt(cells[0].getAttribute("rowspan"));
                    for (var k = i + 1; k < i + rowspanValue; k++) {
                        rows[k].style.display = "";
                    }
                }
            }
        }
        
        row.style.display = showRow ? "" : "none";
    }
    
    // Second pass to ensure parent rows of visible child rows are shown
    for (var i = 1; i < rows.length; i++) {
        var row = rows[i];
        var cells = row.getElementsByTagName("td");
        
        if (!cells[0].hasAttribute("rowspan") && row.style.display !== "none") {
            // This is a visible child row, make sure its parent is visible
            for (var j = i - 1; j >= 0; j--) {
                var parentCells = rows[j].getElementsByTagName("td");
                if (parentCells[0].hasAttribute("rowspan")) {
                    rows[j].style.display = "";
                    break;
                }
            }
        }
    }
}

// upload pop up message
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Show loading state
    Swal.fire({
        title: 'Uploading...',
        text: 'Please wait while your files are being uploaded',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Create FormData object
    const formData = new FormData(this);

    // Send AJAX request
    fetch('upload_instructor_documents.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message,
                confirmButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'documents.php';
                }
            });
        } else {
            let errorMessage = data.message;
            if (data.errors && data.errors.length > 0) {
                errorMessage += '\n\n' + data.errors.join('\n');
            }
            Swal.fire({
                icon: 'error',
                title: 'Upload Failed',
                text: errorMessage,
                confirmButtonText: 'OK'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Upload Failed',
            text: 'An unexpected error occurred. Please try again.',
            confirmButtonText: 'OK'
        });
    });
});

//for delete confirmation
function confirmDelete(filePath) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Send delete request
            fetch('delete_document.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'file_path=' + encodeURIComponent(filePath)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire(
                        'Deleted!',
                        'Your file has been deleted.',
                        'success'
                    ).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire(
                        'Error!',
                        data.message,
                        'error'
                    );
                }
            })
            .catch(error => {
                Swal.fire(
                    'Error!',
                    'An error occurred while deleting the file.',
                    'error'
                );
            });
        }
    });
}
</script>


  <!-- SweetAlert popup message for logout -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.32/sweetalert2.all.min.js"></script> 

  <script src="Ins_css/student_dashboard.js"></script>
  <script src="Ins_js/mobileResponsive.js"></script>
</body>
</html>
<?php
    session_start(); 

    // Database connection for the "monitoring" system
    $servername = "localhost";
    $username = "u798912504_root";
    $password = "Internfloucc2025*"; // Update with your database password
    $dbname = "u798912504_internflo";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }


    // Check if the user is logged in
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];

        // Fetch student information from the "monitoring" database using the logged-in user ID
       // Modify the existing SELECT statement to include profile_pic
        $stmt = $conn->prepare("SELECT first_name, last_name, name, student_id, profile_pic, status FROM students WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();

        if ($student) {
            // Check if first_name or last_name are empty
            if (empty($student['first_name']) && empty($student['last_name'])) {
                // Use the "name" column as a fallback if both first_name and last_name are empty
                $fullName = $student['name'];
            } else {
                // Concatenate first name and last name only
                $fullName = $student['first_name'] . " " . $student['last_name'];
            }
            $studentId = $student['student_id'];  // Use the student_id from the "monitoring" DB
        } else {
            // Fallback values in case no user data is found
            $fullName = "Student Name";
            $studentId = "Student ID";
        }
    } else {
        // Fallback values if no user is logged in
        $fullName = "Student Name";
        $studentId = "Student ID";
    }

    // Function to retrieve the uploaded file for a specific document type
    function getUploadedDocument($studentId, $documentType, $conn) {
    $sql = "SELECT file_name, file_path FROM m_ojt_documents WHERE student_id = ? AND document_type = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $studentId, $documentType);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
    }

    // Function to retrieve all additional documents for a student
    function getAdditionalDocuments($studentId, $conn) {
    $sql = "SELECT document_type, file_name, file_path FROM m_ojt_documents WHERE student_id = ? AND document_type NOT IN ('MOA', 'Acceptance Letter', 'Parent Consent', 'Medical Certificate', 'Registration Form')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Fetch the uploaded documents for each type
    $moaDocument = getUploadedDocument($studentId, 'MOA', $conn);
    $acceptanceLetter = getUploadedDocument($studentId, 'Acceptance Letter', $conn);
    $parentConsent = getUploadedDocument($studentId, 'Parent Consent', $conn);
    $medicalCertificate = getUploadedDocument($studentId, 'Medical Certificate', $conn);
    $registrationForm = getUploadedDocument($studentId, 'Registration Form', $conn);

    $additionalDocuments = getAdditionalDocuments($studentId, $conn);

// Fetch instructor documents
$stmt = $conn->prepare("SELECT course, school_year FROM students WHERE student_id = ?");
$stmt->bind_param("s", $studentId);
$stmt->execute();
$result = $stmt->get_result();
$studentInfo = $result->fetch_assoc();

if ($studentInfo) {
    // Get course ID from course name
    $stmt = $conn->prepare("SELECT id FROM m_courses WHERE name = ?");
    $stmt->bind_param("s", $studentInfo['course']);
    $stmt->execute();
    $result = $stmt->get_result();
    $courseInfo = $result->fetch_assoc();
    
    $resultsPerPage = 3;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $resultsPerPage;

    if ($courseInfo) {
        // Get total count of documents
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total 
            FROM m_instructor_documents d
            JOIN m_advisors a ON d.advisor_id = a.advisor_id
            WHERE d.course_id = ? AND d.year = ?
        ");
        $stmt->bind_param("ii", $courseInfo['id'], $studentInfo['school_year']);
        $stmt->execute();
        $totalResult = $stmt->get_result();
        $totalRow = $totalResult->fetch_assoc();
        $totalDocuments = $totalRow['total'];
        $totalPages = ceil($totalDocuments / $resultsPerPage);

        // Get documents for current page
        $stmt = $conn->prepare("
            SELECT 
                d.file_name,
                d.file_path,
                d.upload_date,
                CONCAT(a.first_name, ' ', a.last_name) as advisor_name
            FROM m_instructor_documents d
            JOIN m_advisors a ON d.advisor_id = a.advisor_id
            WHERE d.course_id = ? 
            AND d.year = ?
            ORDER BY d.upload_date DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("iiii", $courseInfo['id'], $studentInfo['school_year'], $resultsPerPage, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $documents = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        $documents = [];
        $totalPages = 0;
    }
} else {
    $documents = [];
    $totalPages = 0;
}
?>


<!DOCTYPE html>

<html lang="en" dir="ltr">
  <head>
    <meta charset="UTF-8">
    <!--Drop Down Sidebar Menu-->
    
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/std_document2.css">
    <link rel="icon" href="css/ucclogo2.png">
   
    <!-- Boxiocns CDN Link -->
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
     <meta name="viewport" content="width=device-width, initial-scale=1.0">

     <!-- SweetAlert popup message for logout -->
     <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- FOR VIEW AND DELETE BUTTON ICONS -->
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  </head>
  <style>

    </style>
    <body>
    <!-- SIDEBAR -->
    <section id="sidebar">
    <a href="#" class="brand">
            <img src="css/ucc1.png" alt="Logo" class="logo-img">
        </a>
        <ul class="side-menu">
            <li><a href="std_dashboard.php"><i class='bx bxs-dashboard icon'></i> Dashboard</a></li>
            <!--<li><a href="stdTimelog.php"><i class='bx bx-time icon'></i> Timelog</a></li>-->
            <li><a href="std_reports.php"><i class='bx bxs-report icon'></i> Reports</a></li>
            <li><a href="#" class="active"><i class='bx bxs-file-doc icon'></i> Documents</a></li>
            <li>
				<a href="#"><i class='bx bxs-notepad icon'></i> Templates <i class='bx bx-chevron-right icon-right' ></i></a>
				<ul class="side-dropdown">
					<li><a href="ParentConsent_template.php">PARENTS' CONSENT</a></li>
					<li><a href="OjtLetter_template.php">Endorsement Letter</a></li>
                    <li><a href="moa_template.php">Memorandum of Agreement (MOA)</a></li>
					<li><a href="OJTPullOutLetter_template.php">OJT Pull-out Letter</a></li>
				</ul>
			</li>
        </ul>
        <br><br><br><br>
        <div class="ads">
            <div class="wrapper">
                <a href="../STUDENTLOGIN/studentfrontpage.php" class="btn-upgrade">
                    <i class='bx bx-log-out' style="margin-right: 10px; font-size: 24px;"></i> Back to Home
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
            <span class="user-id"><?php echo htmlspecialchars($studentId); ?></span>
        </div>
        <img src="../STUDENTLOGIN/<?php echo $student['profile_pic']; ?>" alt="">
    </div>
</nav>

        <!-- MAIN -->
        <main>
        <!-- Approval Status Banner -->
        <?php if ($student['status'] === 'pending' || !$student['status']): ?>
            <div class="approval-banner pending">
                <i class='bx bx-time-five'></i>
                <div class="approval-content">
                    <h3>Waiting for Account Approval</h3>
                    <p>Your account is pending approval from your Practicum Coordinator: 
                        <?php echo htmlspecialchars($student['coordinator_name'] ?? 'Not yet assigned'); ?></p>
                </div>
            </div>
            <?php elseif ($student['status'] === 'rejected'): ?>
            <div class="approval-banner rejected">
                <i class='bx bx-x-circle'></i>
                <div class="approval-content">
                    <h3>Account Access Denied</h3>
                    <p>Your account registration has been rejected. Please contact your Practicum Coordinator for more information.</p>
                </div>

            </div>
            <?php endif; ?>

            <!-- Wrap dashboard content in a conditional class -->
            <div class="<?php echo ($student['status'] === 'approved') ? '' : 'disabled-content'; ?>">
    <!-- OJT Documents Upload Section -->
     
  <form action="upload.php" method="POST" enctype="multipart/form-data" id="uploadForm">
    <div class="upload-grid">
      <!-- Supervisor/Coordinator Documents -->
    <div class="upload-section">
        <h3>Documents from Practicum Coordinator</h3>
        <?php if (empty($documents)): ?>
            <p>No documents available.</p>
        <?php else: ?>
            <?php foreach ($documents as $doc): ?>
                <div class="file-download">
                    <div class="file-info">
                        <span class="filename"><?php echo htmlspecialchars($doc['file_name']); ?></span>
                        <span class="advisor">Posted by: <?php echo htmlspecialchars($doc['advisor_name']); ?></span>
                        <span class="date">Date: <?php echo date('M d, Y', strtotime($doc['upload_date'])); ?></span>
                    </div>
                    <a href="downloadInstructorFile.php?file=<?php echo urlencode(basename($doc['file_path'])); ?>&filename=<?php echo urlencode($doc['file_name']); ?>" 
                       class="download-btn">
                        Download
                    </a>
                </div>
            <?php endforeach; ?>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo ($page - 1); ?>" class="page-link">&laquo; Previous</a>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $page - 1);
                    $endPage = min($totalPages, $page + 1);
                    
                    if ($startPage > 1) {
                        echo '<a href="?page=1" class="page-link">1</a>';
                        if ($startPage > 2) {
                            echo '<span class="page-link">...</span>';
                        }
                    }
                    
                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" 
                           class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor;
                    
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<span class="page-link">...</span>';
                        }
                        echo '<a href="?page=' . $totalPages . '" class="page-link">' . $totalPages . '</a>';
                    }
                    ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo ($page + 1); ?>" class="page-link">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

              <!-- Updated template table with href links -->
    <div class="template-section">
        <h3>Templates</h3>
        <table class="template-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>

                <tr>
                    <td>
                        <a>PARENTS' CONSENT</a>
                    </td>
                    <td>
                        <a href="ParentConsent_template.php" class="edit-btn">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </td>
                </tr>
                <tr>
                    <td>
                        <a>Endorsement Letter</a>
                    </td>
                    <td>
                        <a href="OjtLetter_template.php" class="edit-btn">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </td>
                </tr>
                <tr>
                    <td>
                        <a>Memorandum of Agreement (MOA)</a>
                    </td>
                    <td>
                        <a href="moa_template.php" class="edit-btn">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </td>
                </tr>
                <tr>
                    <td>
                        <a>OJT Pull-out Letter</a>
                    </td>
                    <td>
                        <a href="OJTPullOutLetter_template.php" class="edit-btn">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>


<!-- OJT Requirements Upload -->

    <div class="ojt-requirements-section">
        <h3>Requirements</h3>

          <!-- MOA -->
        <div class="file-upload">
            <label for="moa-upload">Memorandum of Agreement (MOA):</label>
            <?php if ($moaDocument): ?>
                <span>Already submitted: <?php echo $moaDocument['file_name']; ?></span><br><br>
                <a href="javascript:void(0);" onclick="openModal('<?php echo $moaDocument['file_path']; ?>')" class="view-button" 
                style="background-color: #4CAF50; color: white; padding:7px; cursor:pointer;border-radius: 5px; border: none;">
                    <i class="fas fa-eye"></i> <!-- View Icon -->
                </a>
                <a href="javascript:void(0);" onclick="confirmDelete('MOA')" class="delete-button"
                style="background-color: red; color: white; padding:7px; cursor:pointer;border-radius: 5px; border: none;">
                    <i class="fas fa-trash"></i> <!-- Delete Icon -->
                </a>
            <?php else: ?>
                <input type="file" id="moa-upload" name="moa-upload" accept=".doc,.docx,.pdf">
            <?php endif; ?>
        </div>

      <!-- Acceptance Letter -->
      <div class="file-upload">
          <label for="acceptance-letter-upload">Acceptance Letter:</label>
          <?php if ($acceptanceLetter): ?>
              <span>Already submitted: <?php echo $acceptanceLetter['file_name']; ?></span><br><br>
              <a href="javascript:void(0);" onclick="openModal('<?php echo $acceptanceLetter['file_path']; ?>')" class="view-button"
              style="background-color: #4CAF50; color: white; padding:7px; cursor:pointer;border-radius: 5px; border: none;">
                  <i class="fas fa-eye"></i> <!-- View Icon -->
              </a>
              <a href="javascript:void(0);" onclick="confirmDelete('Acceptance Letter')" class="delete-button"
              style="background-color: red; color: white; padding:7px; cursor:pointer;border-radius: 5px; border: none;">
                  <i class="fas fa-trash"></i> <!-- Delete Icon -->
              </a>
          <?php else: ?>
              <input type="file" id="acceptance-letter-upload" name="acceptance-letter-upload" accept=".doc,.docx,.pdf">
          <?php endif; ?>
      </div>

      <!-- Parent Consent -->
      <div class="file-upload">
          <label for="parent-consent-upload">Parent Consent:</label>
          <?php if ($parentConsent): ?>
              <span>Already submitted: <?php echo $parentConsent['file_name']; ?></span><br><br>
              <a href="javascript:void(0);" onclick="openModal('<?php echo $parentConsent['file_path']; ?>')" class="view-button"
              style="background-color: #4CAF50; color: white; padding:7px; cursor:pointer;border-radius: 5px; border: none;">
                  <i class="fas fa-eye"></i> <!-- View Icon -->
              </a>
              <a href="javascript:void(0);" onclick="confirmDelete('Parent Consent')" class="delete-button"
              style="background-color: red; color: white; padding:7px; cursor:pointer;border-radius: 5px; border: none;">
                  <i class="fas fa-trash"></i> <!-- Delete Icon -->
              </a>
          <?php else: ?>
              <input type="file" id="parent-consent-upload" name="parent-consent-upload" accept=".doc,.docx,.pdf">
          <?php endif; ?>
      </div>

      <!-- Medical Certificate -->
      <div class="file-upload">
          <label for="medical-certificate-upload">Medical Certificate:</label>
          <?php if ($medicalCertificate): ?>
              <span>Already submitted: <?php echo $medicalCertificate['file_name']; ?></span><br><br>
              <a href="javascript:void(0);" onclick="openModal('<?php echo $medicalCertificate['file_path']; ?>')" class="view-button"
              style="background-color: #4CAF50; color: white; padding:7px; cursor:pointer;border-radius: 5px; border: none;">
                  <i class="fas fa-eye"></i> <!-- View Icon -->
              </a>
              <a href="javascript:void(0);" onclick="confirmDelete('Medical Certificate')" class="delete-button"
              style="background-color: red; color: white; padding:7px; cursor:pointer;border-radius: 5px; border: none;">
                  <i class="fas fa-trash"></i> <!-- Delete Icon -->
              </a>
          <?php else: ?>
              <input type="file" id="medical-certificate-upload" name="medical-certificate-upload" accept=".doc,.docx,.pdf">
          <?php endif; ?>
      </div>

      <!-- Registration Form -->
      <div class="file-upload">
          <label for="registration-upload">Registration Form:</label>
          <?php if ($registrationForm): ?>
              <span>Already submitted: <?php echo $registrationForm['file_name']; ?></span><br><br>
              <a href="javascript:void(0);" onclick="openModal('<?php echo $registrationForm['file_path']; ?>')" class="view-button"
              style="background-color: #4CAF50; color: white; padding:7px; cursor:pointer;border-radius: 5px; border: none;">
                  <i class="fas fa-eye"></i> <!-- View Icon -->
              </a>
              <a href="javascript:void(0);" onclick="confirmDelete('Registration Form')" class="delete-button"
              style="background-color: red; color: white; padding:7px; cursor:pointer;border-radius: 5px; border: none;">
                  <i class="fas fa-trash"></i> <!-- Delete Icon -->
              </a>
          <?php else: ?>
              <input type="file" id="registration-upload" name="registration-upload" accept=".doc,.docx,.pdf">
          <?php endif; ?>
      </div><br>

      <!-- New section for additional document upload -->
      <div class="additional-document-section">
            <h3>Additional Document</h3>

            <div class="file-upload">
                <label for="document-type">Document Type:</label>
                <input type="text" id="document-type" name="document-type" placeholder="Enter document type">
            </div>

            <div class="file-upload">
                <label for="additional-document-upload">Upload Additional Document:</label>
                <input type="file" id="additional-document-upload" name="additional-document-upload" accept=".doc,.docx,.pdf">
            </div>
        </div>

          <!-- Submit Button -->
          <div class="submit-section">
              <button type="submit" id="uploadBtn">Upload</button>
          </div><br><br>

          <div class="additional-documents-display">
          <h3>Submitted Files in Additional Documents</h3><br>
            <?php if (!empty($additionalDocuments)): ?>
            <?php foreach ($additionalDocuments as $doc): ?>
          <div class="additional-document">
                <!-- Display Document Type -->
                <span><strong>Document Type:</strong> <?php echo htmlspecialchars($doc['document_type']); ?></span><br>
                
                <!-- Display File Name -->
                <span><strong>File:</strong> <?php echo htmlspecialchars($doc['file_name']); ?></span><br>
                
                <!-- View Button -->
                <a href="javascript:void(0);" onclick="openModal('<?php echo $doc['file_path']; ?>')" class="view-button" style="background-color: #4CAF50; color: white; padding:7px; cursor:pointer; border-radius: 5px; border: none;">
                    <i class="fas fa-eye" style="margin-top: 8px; position: relative;"></i>
                </a>
                
                <!-- Delete Button -->
                <a href="javascript:void(0);" onclick="confirmDelete('<?php echo $doc['document_type']; ?>', 'additional')" class="delete-button" style="background-color: red; color: white; padding:7px; cursor:pointer; border-radius: 5px; border: none;">
                    <i class="fas fa-trash"></i>
                </a>
          </div>
            <br> 
                <?php endforeach; ?>
            <?php else: ?>
                <p>No additional documents submitted.</p>
            <?php endif; ?>
      </div>
    </div>
    </div>
  </form>
  </main>
  </section>

          <!-- Modal for viewing documents -->
          <div id="documentModal" class="modal">
              <div class="modal-content">
                  <span class="close-btn" onclick="closeModal()">&times;</span>
                  <div class="iframe-container">
                      <iframe id="documentIframe" src="" frameborder="0"></iframe>
                  </div>
              </div>
          </div>






  <!--Footer
  <div class="underfooter-bg">
      <div class="underfooter">
          <div class="uf-content">
              <p>Copyright Internflo©2024. All Rights Reserved</p>
          </div>
      </div>
  </div>-->

  <script>
  document.getElementById('uploadForm').addEventListener('submit', function(event) {
      event.preventDefault(); // Prevent the default form submission for testing

      // Simulate successful upload (you can integrate actual validation here)
      var formData = new FormData(this);

      // Send the form data using AJAX
      fetch('upload.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.text())
      .then(result => {
        // Success message after upload
        Swal.fire({
          icon: 'success',
          title: 'File Uploaded',
          text: 'Your file has been successfully uploaded!',
          confirmButtonText: 'OK'
        });
        // Optionally reload the page or update the document list dynamically
      })
      .catch(error => {
        console.error('Error:', error);
      });
  });

  // Function to open the modal and display the document
  function openModal(filePath) {
      const modal = document.getElementById('documentModal');
      const iframe = document.getElementById('documentIframe');
      
      // Set the iframe source to the file path
      iframe.src = filePath;

      // Display the modal
      modal.style.display = 'block';
  }

  // Function to close the modal
  function closeModal() {
      const modal = document.getElementById('documentModal');
      const iframe = document.getElementById('documentIframe');

      // Hide the modal
      modal.style.display = 'none';

      // Clear the iframe source to stop loading the document when modal is closed
      iframe.src = '';
  }

  // Close modal when clicking outside the modal content
  window.onclick = function(event) {
      const modal = document.getElementById('documentModal');
      if (event.target == modal) {
          closeModal();
      }
  }

  // Function to confirm deletion and handle the delete request
  function confirmDelete(documentType, type = 'regular') {
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
            // Modify the URL to include the type parameter for additional documents
            const url = type === 'additional' 
                ? `deleteUpload.php?document_type=${encodeURIComponent(documentType)}&type=additional`
                : `deleteUpload.php?document_type=${encodeURIComponent(documentType)}`;

            // Make AJAX request to delete the file
            fetch(url, {
                method: 'GET'
            })
            .then(response => response.json())  // Parse JSON response
            .then(data => {
                if (data.success) {
                    // Show success message
                    Swal.fire(
                        'Deleted!',
                        'Your file has been deleted.',
                        'success'
                    ).then(() => {
                        // Reload the page to update the document list
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Failed to delete file');
                }
            })
            .catch(error => {
                Swal.fire(
                    'Error!',
                    'Failed to delete the file: ' + error.message,
                    'error'
                );
                console.error('Error:', error);
            });
        }
    });
}

// Add event listener for clicks outside the modal
window.onclick = function(event) {
    const modal = document.getElementById('documentModal');
    if (event.target == modal) {
        closeModal();
    }
}

// Enhanced modal functions
function openModal(filePath) {
    const modal = document.getElementById('documentModal');
    const iframe = document.getElementById('documentIframe');
    iframe.src = filePath;
    modal.style.display = 'block';
}

function closeModal() {
    const modal = document.getElementById('documentModal');
    const iframe = document.getElementById('documentIframe');
    modal.style.display = 'none';
    iframe.src = '';
}

</script>
  <script src="js/std_documents.js"></script>
  <script src="css/student_dashboard.js"></script>
  <script src="js/logMessage.js"></script>
  <script src="js/mobileResponsiveness.js"></script>

</body>
</html>
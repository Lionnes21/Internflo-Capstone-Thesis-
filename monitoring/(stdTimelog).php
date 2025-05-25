<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "student_registration";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in and student ID is set in the session
if (!isset($_SESSION['user_id'])) {
    echo "User not logged in.";
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch student information and set student_id in session
$stmt = $conn->prepare("SELECT first_name, last_name, name, student_id, profile_pic, status FROM students WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if ($student) {
    $fullName = !empty($student['first_name']) && !empty($student['last_name']) 
        ? $student['first_name'] . " " . $student['last_name']
        : $student['name'];
    
    $_SESSION['student_id'] = $student['student_id']; // Set student_id in session
    $studentId = $_SESSION['student_id'];
} else {
    echo "No student data found.";
    exit;
}

// Fetch timelog history
$timelog_history = [];
$stmt = $conn->prepare("SELECT id, date, time_in, time_out, hours_worked, break_time, activity, image_path FROM m_timelog WHERE student_id = ? ORDER BY date DESC, time_in DESC");
$stmt->bind_param("s", $studentId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $timelog_history[] = $row;
}
$stmt->close();
$conn->close();

// Helper function to convert 24-hour time to 12-hour AM/PM format
function formatTime($time) {
    if (empty($time)) return '';
    $datetime = DateTime::createFromFormat('H:i:s', $time);
    return $datetime ? $datetime->format('h:i A') : $time;
}

function formatBreakTime($breakTime) {
    if (empty($breakTime)) return '';
    
    // If the input already looks like a formatted break time, return it
    if (preg_match('/\d+\s*(hour|hours|minute|minutes)/', $breakTime)) {
        return $breakTime;
    }
    
    // Remove any non-numeric characters and trim
    $cleanBreakTime = preg_replace('/[^0-9\s]/', '', $breakTime);
    
    // Split into number and potential unit
    preg_match('/(\d+)\s*(\w*)?/', $cleanBreakTime, $matches);
    
    if (empty($matches[1])) return '';
    
    $value = intval($matches[1]);
    $unit = strtolower($matches[2] ?? '');
    
    // Determine if it's minutes or hours based on input
    if ($unit === 'hour' || $unit === 'hours') {
        return $value . ($value == 1 ? ' hour' : ' hours');
    } elseif ($unit === 'minute' || $unit === 'minutes' || empty($unit)) {
        return $value . ($value == 1 ? ' minute' : ' minutes');
    }
    
    return $breakTime; // Return original input if no match
}

function formatHours($hours) {
    // Round to nearest whole number, preserving only integer part
    return round($hours, 0);
}
?>



<!DOCTYPE html>

<html lang="en" dir="ltr">
  <head>
    <meta charset="UTF-8">
    <!--Drop Down Sidebar Menu -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/stdTimelog1.css">
    <link rel="icon" href="css/ucclogo2.png">


    <!-- Boxiocns CDN Link -->
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
     <meta name="viewport" content="width=device-width, initial-scale=1.0">

     <!-- SweetAlert popup message for logout -->
     <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
     </head>
     <style>
        .approval-banner {
            padding: 15px;
            margin: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .approval-banner.pending {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }

        .approval-banner.approved {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .approval-banner.rejected {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .approval-banner i {
            font-size: 24px;
        }

        .approval-content {
            flex-grow: 1;
        }

        .approval-content h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }

        .approval-content p {
            margin: 5px 0 0;
            font-size: 14px;
        }

        .disabled-content {
            opacity: 0.5;
            pointer-events: none;
        }

        .contact-coordinator {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }

        .contact-coordinator:hover {
            background-color:#3d833f;
        }
    </style>
     <body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <a href="#" class="brand">
            <img src="css/ucc_logo.png" alt="Logo" class="logo-img">
            <span><span class="intern">Intern</span><span class="flo">flo</span><span class="dot">.</span></span>
        </a>
        <ul class="side-menu">
            <li><a href="std_dashboard.php"><i class='bx bxs-dashboard icon'></i> Dashboard</a></li>
            <li><a href="stdTimelog.php" class="active"><i class='bx bx-time icon'></i> Timelog</a></li>
            <li><a href="std_reports.php"><i class='bx bxs-report icon'></i> Reports</a></li>
            <li><a href="std_documents.php"><i class='bx bxs-file-doc icon'></i> Documents</a></li>
            <li>
				<a href="#"><i class='bx bxs-notepad icon' ></i> Templates <i class='bx bx-chevron-right icon-right' ></i></a>
				<ul class="side-dropdown">
					<li><a href="moa_template.php">Memorandum of Agreement (MOA)</a></li>
					<li><a href="ParentConsent_template.php">PARENTS' CONSENT</a></li>
					<li><a href="OjtLetter_template.php">OJT Letter</a></li>
					<li><a href="OJTPullOutLetter_template.php">OJT Pull-out Letter</a></li>
				</ul>
			</li>
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
            
            <!-- Stats Cards -->
            <?php if (isset($success_message)): ?>
        <p style="color: green;"><?php echo $success_message; ?></p>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <p style="color: red;"><?php echo $error_message; ?></p>
    <?php endif; ?>

    <div class="timelog-page">
        <form id="timelog-form" action="#" method="POST" enctype="multipart/form-data">
    <label for="date">Date:</label>
    <input type="date" id="date" name="date" required>
    
    <div class="time-break-group">
        <div class="time-break-item">
            <label for="time-in">Time In:</label>
            <input type="time" id="time-in" name="time-in" required>
        </div>
        
        <div class="time-break-item">
            <label for="break-time">Break Time:</label>
            <input type="text" id="break-time" name="break-time" placeholder="e.g., 30 minutes/1 hour">
        </div>
        
        <div class="time-break-item">
            <label for="time-out">Time Out:</label>
            <input type="time" id="time-out" name="time-out" required>
        </div>
    </div>

        <label for="activity">Activity Description:</label>
        <textarea id="activity" name="activity" rows="4" required></textarea>

        <!-- Image upload section -->
        <label for="image-upload">Upload Timecard/Photos:</label>
        <input type="file" id="image-upload" name="image-upload" accept="image/*">
    
    <button type="submit">Submit</button>
</form>


        
<div class="timelog-history">
        <h2>Previous Timelogs</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time In</th>
                    <th>Break Time</th>
                    <th>Time Out</th>
                    <th>Total Hours</th>
                    <th>Activity</th>
                    <th>Image</th>
                </tr>
            </thead>
            <tbody>
        <?php foreach ($timelog_history as $entry): ?>
        <tr>
            <td><?php echo htmlspecialchars($entry['date']); ?></td>
            <td><?php echo htmlspecialchars($entry['time_in']); ?></td>
            <td><?php echo formatBreakTime($entry['break_time']); ?></td>
            <td><?php echo htmlspecialchars($entry['time_out']); ?></td>
            <td><?php echo formatHours($entry['hours_worked']); ?> hours</td>
            <td><?php echo htmlspecialchars($entry['activity']); ?></td>
            <td>
                <?php if (!empty($entry['image_path'])): ?>
                    <i class='bx bx-image view-icon' onclick="openModal('<?php echo htmlspecialchars($entry['image_path']); ?>')"></i>
                <?php else: ?>
                    N/A
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
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




  <!--Footer
  <div class="underfooter-bg">
      <div class="underfooter">
          <div class="uf-content">
              <p>Copyright Internflo©2024. All Rights Reserved</p>
          </div>
      </div>
  </div>-->

  <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('timelog-form');
        const timelogHistoryTable = document.querySelector('.timelog-history tbody');

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            const formData = new FormData(form);

            fetch('save_timelog.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Timelog entry saved successfully!',
                    });
                // Add the new entry to the table
                const newRow = document.createElement('tr');
                newRow.innerHTML = `
                    <td>${formData.get('date')}</td>
                    <td>${formData.get('time-in')}</td>
                    <td>${formData.get('break-time')}</td>
                    <td>${formData.get('time-out')}</td>
                    <td>${data.hoursWorked.toFixed(2)} hours</td>
                    <td>${formData.get('activity')}</td>

                    `;
                    timelogHistoryTable.insertBefore(newRow, timelogHistoryTable.firstChild);
                    // Reset the form
                    form.reset();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Error: ' + data.error,
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'An error occurred while saving the timelog entry.',
                });
            });
        });
    });

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

  <script src="css/student_dashboard.js"></script>
  <script src="js/logMessage.js"></script>

</body>
</html>
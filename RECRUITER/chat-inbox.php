<?php
   session_start();

   $isLoggedIn = isset($_SESSION['user_id']);

   // Initialize variables
   $initials = '';
   $fullName = '';
   $email = '';
   $fullName2 = '';
   $profile_pic = 'pics/default_profile.jpg';
   $message = ''; // For status messages
   $error = ''; // For error messages
   $sender_email = ''; // Initialize sender_email

   // Database connection
   $conn = new mysqli("localhost", "u798912504_root", "Internfloucc2025*", "u798912504_internflo");

   // Check connection
   if ($conn->connect_error) {
       die("Connection failed: " . $conn->connect_error);
   }

   function getFullName() {
        if (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
            return htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']);
        }
        return 'Your Name'; // Fallback if names are not set
    }

   // If logged in, fetch user details from the database
    if ($isLoggedIn) {
        $userId = $_SESSION['user_id'];
        $sql = 'SELECT first_name, middle_name, last_name, suffix, email FROM approvedrecruiters WHERE id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            $fullName2 = trim($user['first_name'] . 
                        (!empty($user['middle_name']) ? ' ' . $user['middle_name'] : '') . 
                        ' ' . $user['last_name'] . 
                        (!empty($user['suffix']) ? ' ' . $user['suffix'] : ''));

            if (empty($fullName2) && !empty($user['name'])) {
                $fullName2 = $user['name'];
            }

            if (!empty($user['profile_pic'])) {
                $profile_pic = $user['profile_pic'];
            }
            
            $sender_email = $user['email'];  // Set sender_email to logged-in user's email
            $recipient_email = $user['email'];
        }
    }
    
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_message'])) {
        $recipient_email = $_POST['recipient'];
        $subject = $_POST['subject'];
        $content = $_POST['content'];
        
        // Determine recipient type
        $recipient_type = '';
        
        // Check in students table
        $stmt = $conn->prepare("SELECT email FROM students WHERE email = ?");
        $stmt->bind_param("s", $recipient_email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $recipient_type = 'student';
        }
        
        // Check in approvedrecruiters table if not found
        if (empty($recipient_type)) {
            $stmt = $conn->prepare("SELECT email FROM approvedrecruiters WHERE email = ?");
            $stmt->bind_param("s", $recipient_email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $recipient_type = 'recruiter';
            }
        }
        
        // Check in m_advisors table if still not found
        if (empty($recipient_type)) {
            $stmt = $conn->prepare("SELECT email FROM m_advisors WHERE email = ?");
            $stmt->bind_param("s", $recipient_email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $recipient_type = 'advisor';
            }
        }
        
        // Insert message if recipient is valid
        if (!empty($recipient_type)) {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Insert into messaging table first
                $stmt = $conn->prepare("INSERT INTO messaging (sender_email, recipient_email, recipient_type, subject, content) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $sender_email, $recipient_email, $recipient_type, $subject, $content);
                $stmt->execute();
                
                // Get the ID from the messaging table insert
                $message_id = $conn->insert_id;
                
                // Insert into messaging_sent table
                $stmt = $conn->prepare("INSERT INTO messaging_sent (message_id, sender_email, recipient_email, recipient_type, subject, content) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssss", $message_id, $sender_email, $recipient_email, $recipient_type, $subject, $content);
                $stmt->execute();
                
                // Insert into messaging_third table
                $stmt = $conn->prepare("INSERT INTO messaging_third (message_id, sender_email, recipient_email, recipient_type, subject, content) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssss", $message_id, $sender_email, $recipient_email, $recipient_type, $subject, $content);
                $stmt->execute();
                
                // Commit transaction
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Message sent successfully!']);
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                echo json_encode(['success' => false, 'error' => 'Error sending message: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid recipient email address.']);
        }
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reply_message'])) {
        // Log the incoming request
        error_log('Reply message request received');
        error_log('POST data: ' . print_r($_POST, true));
        
        $original_message_id = $_POST['original_message_id'];
        $reply_content = $_POST['reply_content'];
        
        // Log the values
        error_log('Original message ID: ' . $original_message_id);
        error_log('Reply content: ' . $reply_content);
        error_log('Sender email: ' . $sender_email);
        
        // Check if original message exists
        $check_message = $conn->prepare("SELECT recipient_email, sender_email FROM messaging WHERE id = ?");
        $check_message->bind_param("i", $original_message_id);
        $check_message->execute();
        $message_result = $check_message->get_result()->fetch_assoc();
        
        error_log('Original message found: ' . ($message_result ? 'yes' : 'no'));
        
        // Insert reply
        try {
            $stmt = $conn->prepare("INSERT INTO messaging_replies (original_message_id, sender_email, content) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $original_message_id, $sender_email, $reply_content);
            
            $success = $stmt->execute();
            error_log('Insert success: ' . ($success ? 'yes' : 'no'));
            
            if ($success) {
                echo json_encode(['status' => 'success', 'message' => 'Reply sent successfully']);
            } else {
                error_log('MySQL Error: ' . $stmt->error);
                echo json_encode(['status' => 'error', 'message' => 'Error sending reply: ' . $stmt->error]);
            }
        } catch (Exception $e) {
            error_log('Exception: ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Exception: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // Function to get replies for a message
    function getMessageReplies($conn, $message_id) {
        $stmt = $conn->prepare("
            SELECT r.*, 
                   CASE 
                       WHEN s.email IS NOT NULL THEN CONCAT(s.first_name, ' ', s.last_name)
                       WHEN rc.email IS NOT NULL THEN CONCAT(rc.first_name, ' ', rc.last_name)
                       WHEN a.email IS NOT NULL THEN CONCAT(a.first_name, ' ', a.last_name)
                       ELSE r.sender_email
                   END as sender_name
            FROM messaging_replies r
            INNER JOIN messaging m ON r.original_message_id = m.id
            LEFT JOIN students s ON r.sender_email = s.email
            LEFT JOIN approvedrecruiters rc ON r.sender_email = rc.email
            LEFT JOIN m_advisors a ON r.sender_email = a.email
            WHERE r.original_message_id = ? 
            AND EXISTS (SELECT 1 FROM messaging WHERE id = r.original_message_id)
            ORDER BY r.timestamp ASC
        ");
        $stmt->bind_param("i", $message_id);
        $stmt->execute();
        return $stmt->get_result();
    }
 
    function getSenderName($conn, $email) {
        // Try students table first
        $query = "SELECT 'student' as type, CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name, ' ', COALESCE(suffix, '')) as full_name 
                 FROM students WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            // Try approvedrecruiters table
            $query = "SELECT 'recruiter' as type, CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name, ' ', COALESCE(suffix, '')) as full_name 
                     FROM approvedrecruiters WHERE email = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
        }
        
        if ($result->num_rows == 0) {
            // Try m_advisors table
            $query = "SELECT 'advisor' as type, CONCAT(first_name, ' ', COALESCE(middle_initial, ''), ' ', last_name, ' ', COALESCE(suffix, '')) as full_name 
                     FROM m_advisors WHERE email = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
        }
        
        $row = $result->fetch_assoc();
        $full_name = $row ? trim($row['full_name']) : $email;
        
        return [
            'name' => $full_name,
            'email' => $email,
            'type' => $row ? $row['type'] : 'unknown'
        ];
    }
    
    $messages_query = "
    SELECT 
        m.id,
        m.sender_email,
        m.recipient_email,
        m.recipient_type,
        m.subject,
        m.content,
        m.timestamp,
        'received' as message_type 
    FROM messaging m 
    WHERE m.recipient_email = ?

    UNION

    SELECT 
        m.id,
        mt.sender_email,
        mt.recipient_email,
        mt.recipient_type,
        mt.subject,
        mt.content,
        mt.timestamp,
        'sent' as message_type
    FROM messaging_third mt 
    INNER JOIN messaging m ON mt.message_id = m.id
    WHERE mt.sender_email = ?
    AND EXISTS (SELECT 1 FROM messaging WHERE id = mt.message_id)
    ORDER BY timestamp DESC";

    $stmt = $conn->prepare($messages_query);
    $stmt->bind_param('ss', $recipient_email, $sender_email);
    $stmt->execute();
    $messages = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="pics/ucc.png">
    <title>UCC - Company Emails</title>

    <link rel="stylesheet" href="chat.css">
    <link rel="stylesheet" href="NAV-login.css">
    <link rel="stylesheet" href="FOOTER.css">
</head>
<body>
    <!-- NAVIGATION -->
    <div class="navbar">
        <div class="logo-container">
            <button class="menu-toggle" aria-label="Toggle Navigation">☰</button>
            <img src="pics/ucc-logo.png" alt="Logo" class="logo-img">
        </div>
        <div class="nav-links">
            <a href="companyloginpage.php">HOME</a>
            <a href="#about">ABOUT US</a>
            <a href="#contact">CONTACT US</a>
            <?php if(!isset($_SESSION['email'])): ?>
                <a href="../STUDENTCOORLOG/login.php" class="login-btn">LOGIN</a>
                <a href="../MAIN/MAIN.php" class="employer-btn">APPLICANT SITE</a>
            <?php endif; ?>
        </div>
        <?php if(isset($_SESSION['email'])): ?>
        <div class="auth-buttons">
            <div class="dropdown-container">
                <div class="border">
                    <span class="greeting-text"><?php echo htmlspecialchars($_SESSION['email']); ?></span>
                    <div class="dropdown-btn" onclick="toggleDropdown(event)">
                        <img src="pics/profile.png" alt="Profile" onerror="this.onerror=null;this.src='pics/default_profile.jpg';">
                    </div>
                </div>
                <div id="dropdown-content" class="dropdown-content">
                    <div class="user-fullname"><?php echo getFullName(); ?></div>
                    <hr style="margin: 0 auto">
                    <a href="company-profile.php">Profile</a>
                    <a href="company-overview.php">Interns</a>
                    <a href="chat-inbox.php">Emails</a>
                    <a href="company-account.php">Settings</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Elements
            const navbar = document.querySelector('.navbar');
            const menuToggle = document.querySelector('.menu-toggle');
            const dropdownContent = document.getElementById("dropdown-content");
            let timeout;

            // Navbar visibility functions
            const hideNavbar = () => {
                if (window.scrollY > 0) {
                    navbar.style.opacity = '0';
                    navbar.style.pointerEvents = 'none';
                }
            };

            const showNavbar = () => {
                navbar.style.opacity = '1';
                navbar.style.pointerEvents = 'auto';
            };

            const resetNavbarTimeout = () => {
                showNavbar();
                clearTimeout(timeout);
                if (window.scrollY > 0) {
                    timeout = setTimeout(hideNavbar, 1000);
                }
            };

            // Scroll event listeners
            window.addEventListener('scroll', () => {
                if (window.scrollY === 0) {
                    showNavbar();
                    clearTimeout(timeout);
                } else {
                    resetNavbarTimeout();
                }
            });

            // User interaction listeners
            window.addEventListener('mousemove', resetNavbarTimeout);
            window.addEventListener('click', resetNavbarTimeout);
            window.addEventListener('keydown', resetNavbarTimeout);

            // Initial check
            if (window.scrollY > 0) {
                timeout = setTimeout(hideNavbar, 1000);
            }

            // Mobile menu toggle functionality
            menuToggle.addEventListener('click', function(event) {
                event.stopPropagation(); // Prevent event from bubbling
                navbar.classList.toggle('active');
                
                if (navbar.classList.contains('active')) {
                    menuToggle.innerHTML = '☰';
                    menuToggle.style.color = '#e77d33';
                } else {
                    menuToggle.innerHTML = '☰';
                    menuToggle.style.color = '#fd6f41';
                }
            });

            // Dropdown toggle function
            window.toggleDropdown = function(event) {
                if (event) {
                    event.stopPropagation();
                }
                
                const isDisplayed = dropdownContent.style.display === "block";
                
                // Close dropdown if it's open
                if (isDisplayed) {
                    dropdownContent.style.display = "none";
                } else {
                    // Close any other open dropdowns first
                    const allDropdowns = document.querySelectorAll('.dropdown-content');
                    allDropdowns.forEach(dropdown => {
                        dropdown.style.display = "none";
                    });
                    
                    // Open this dropdown
                    dropdownContent.style.display = "block";
                }
            };

            // Close menu and dropdown when clicking outside
            document.addEventListener('click', function(event) {
                // Handle mobile menu
                const isClickInsideNavbar = navbar.contains(event.target);
                if (!isClickInsideNavbar && navbar.classList.contains('active')) {
                    navbar.classList.remove('active');
                    menuToggle.innerHTML = '☰';
                    menuToggle.style.color = '#fd6f41';
                }

                // Handle dropdown
                const isClickInsideDropdown = event.target.closest('.dropdown-container');
                if (!isClickInsideDropdown && dropdownContent) {
                    dropdownContent.style.display = "none";
                }
            });

            // Window resize handler
            window.addEventListener('resize', function() {
                if (window.innerWidth > 1300) {
                    navbar.classList.remove('active');
                    menuToggle.innerHTML = '☰';
                    menuToggle.style.color = '#fd6f41';
                }
            });
        });
    </script>
    <!-- NAVIGATION -->

    <header class="header">
        <div class="header-content">
            <div class="search-bar">
                <input type="text" placeholder="Search messages" class="search-input">
                <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2e3849"><path d="M378.09-314.5q-111.16 0-188.33-77.17-77.17-77.18-77.17-188.33t77.17-188.33q77.17-77.17 188.33-77.17 111.15 0 188.32 77.17 77.18 77.18 77.18 188.33 0 44.48-13.52 83.12-13.53 38.64-36.57 68.16l222.09 222.33q12.67 12.91 12.67 31.94 0 19.04-12.91 31.71-12.68 12.67-31.83 12.67t-31.82-12.67L529.85-364.59q-29.76 23.05-68.64 36.57-38.88 13.52-83.12 13.52Zm0-91q72.84 0 123.67-50.83 50.83-50.82 50.83-123.67t-50.83-123.67q-50.83-50.83-123.67-50.83-72.85 0-123.68 50.83-50.82 50.82-50.82 123.67t50.82 123.67q50.83 50.83 123.68 50.83Z"/></svg>
            </div>
            <button class="compose-btn">
                Compose
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
                    <path d="M202.87-111.87q-37.78 0-64.39-26.61t-26.61-64.39v-487.52q0-16.15 5.34-30.95 5.33-14.79 15.53-27.23l56.93-68.89q12.44-15.43 30.61-23.17 18.18-7.74 37.85-7.74h443.74q19.67 0 37.85 7.74 18.17 7.74 30.61 23.17l56.93 68.89q10.2 12.44 15.53 27.23 5.34 14.8 5.34 30.95v95.93q0 13.68-9.58 22.99-9.57 9.32-23.48 10.56-25.96 2.24-48.42 11.48-22.45 9.23-41.89 29.43l-80.46 80.7v-189.46H315.7v246.63q0 25.87 21.51 39.04 21.51 13.18 44.38 1.74L480-390.43l76.3 38.89-50.63 49.87Q493-289 485.78-272.21q-7.21 16.8-7.21 35.19v79.65q0 19.15-13.18 32.33-13.17 13.17-32.32 13.17h-230.2Zm355.7-45.5v-65.04q0-9.2 3.47-17.54 3.48-8.33 10.2-15.05L781-463q9.72-9.72 21.55-14.08 11.84-4.35 23.8-4.35 12.95 0 24.79 4.85 11.84 4.86 21.56 14.58l37 37q8.71 9.72 13.57 21.55 4.86 11.84 4.86 23.8 0 12.19-4.36 24.41T909.7-333.3l-208 208q-6.72 6.71-15.06 10.07-8.34 3.36-17.53 3.36h-65.04q-19.16 0-32.33-13.17-13.17-13.18-13.17-32.33Zm270.17-185.67 34.61-36.61-37-37-35.61 35.61 38 38ZM224.37-717.37h511.26l-34-40H258.37l-34 40Z"/>
                </svg>

            </button>
            
        </div>
    </header>
    <div class="container">
        <nav class="nav">
            <a href="chat-inbox.php" class="nav-item active">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#002b7f"><path d="M202.87-111.87q-37.78 0-64.39-26.61t-26.61-64.39v-554.26q0-37.78 26.61-64.39t64.39-26.61h554.26q37.78 0 64.39 26.61t26.61 64.39v554.26q0 37.78-26.61 64.39t-64.39 26.61H202.87ZM480-321.43q31.28 0 58.04-15.79 26.76-15.78 43.29-42.3 6.53-9.86 16.33-15.89 9.8-6.02 21.77-6.02h137.7v-355.7H202.87v355.7h137.7q11.97 0 21.77 6.02 9.8 6.03 16.33 15.89 16.53 26.52 43.29 42.3 26.76 15.79 58.04 15.79Z"/></svg>
                Messages
            </a>
            <a href="chat-sent.php" class="nav-item">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#666666"><path d="M792-443 176-183q-20 8-38-3.5T120-220v-520q0-22 18-33.5t38-3.5l616 260q25 11 25 37t-25 37ZM200-280l474-200-474-200v140l240 60-240 60v140Zm0 0v-400 400Z"/></svg>
                Sent
            </a>
            <a href="chat-trash.php" class="nav-item">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#666666"><path d="M280-120q-33 0-56.5-23.5T200-200v-520q-17 0-28.5-11.5T160-760q0-17 11.5-28.5T200-800h160q0-17 11.5-28.5T400-840h160q17 0 28.5 11.5T600-800h160q17 0 28.5 11.5T800-760q0 17-11.5 28.5T760-720v520q0 33-23.5 56.5T680-120H280Zm400-600H280v520h400v-520ZM400-280q17 0 28.5-11.5T440-320v-280q0-17-11.5-28.5T400-640q-17 0-28.5 11.5T360-600v280q0 17 11.5 28.5T400-280Zm160 0q17 0 28.5-11.5T600-320v-280q0-17-11.5-28.5T560-640q-17 0-28.5 11.5T520-600v280q0 17 11.5 28.5T560-280ZM280-720v520-520Z"/></svg>
                Trash
            </a>
            <a href="chat-agreement.php" class="nav-item">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#666666"><path d="M151.87-191.87v-91H229v-271.87q0-86.11 51.32-153.6 51.31-67.49 134.18-88.2v-26.09q0-27.29 19.1-46.4 19.11-19.1 46.4-19.1t46.4 19.1q19.1 19.11 19.1 46.4v26.09q83.11 20.71 134.3 88.08Q731-641.09 731-554.74v271.87h77.13v91H151.87ZM480-498.8Zm.24 429.56q-34.18 0-58.53-24.27-24.34-24.27-24.34-58.36h165.5q0 34.2-24.27 58.41-24.28 24.22-58.36 24.22ZM320-282.87h320v-271.87q0-66-47-113t-113-47q-66 0-113 47t-47 113v271.87Z"/></svg>
                Notifications
            </a>
        </nav>
        

        <div class="messages">
            <?php 
                $messagesDisplayed = false;
                    if ($messages->num_rows > 0): 
                            while($message = $messages->fetch_assoc()): 
                                $message_id = $message['id'];
                                $replies = getMessageReplies($conn, $message_id);
                                $messageType = $message['message_type'];
                                
                                // Show if it's a received message OR if it's a sent message with replies
                                if ($messageType === 'received' || ($messageType === 'sent' && $replies && $replies->num_rows > 0)):
                                    $messagesDisplayed = true;
                                    $sender = getSenderName($conn, $message['sender_email']);
                                    $recipient = getSenderName($conn, $message['recipient_email']);
                                    $timestamp = date('F j, Y g:i A', strtotime($message['timestamp']));
                        ?>
                        <div class="message <?php echo $messageType; ?>">
                            <div class="message-wrapper">
                                <div class="message-content">
                                    <!-- Show "From" section only if there are no replies -->
                                    <?php if ($message['sender_email'] !== $sender_email): ?>
                                        <div class="recipient-info">
                                            <span>From:</span>
                                            <div class="recipient-info">
                                                <h3><?php echo htmlspecialchars($sender['name']); ?></h3>
                                                <span class="recipient-email">&lt;<?php echo htmlspecialchars($sender['email']); ?>&gt;</span>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="message-actions">
                                        <button class="btn reply-btn">
                                            <svg xmlns="http://www.w3.org/2000/svg" height="22px" viewBox="0 -960 960 960" width="22px" fill="#666666">
                                                <path d="m184.92-520 162.93 162.92q8.92 8.93 8.8 20.89-.11 11.96-9.42 21.27-9.31 8.69-21.08 9-11.76.3-21.07-9L125.31-494.69q-5.62-5.62-7.92-11.85-2.31-6.23-2.31-13.46t2.31-13.46q2.3-6.23 7.92-11.85l179.77-179.77q8.31-8.3 20.57-8.5 12.27-.19 21.58 8.5 9.31 9.31 9.31 21.39 0 12.07-9.31 21.38L184.92-520Zm212.31 30 132.92 132.92q8.93 8.93 8.81 20.89-.12 11.96-9.42 21.27-9.31 8.69-21.08 9-11.77.3-21.08-9L307.62-494.69q-5.62-5.62-7.93-11.85-2.31-6.23-2.31-13.46t2.31-13.46q2.31-6.23 7.93-11.85l179.76-179.77q8.31-8.3 20.58-8.5 12.27-.19 21.58 8.5 9.3 9.31 9.3 21.39 0 12.07-9.3 21.38L397.23-550H670q78.77 0 134.38 55.62Q860-438.77 860-360v110q0 12.77-8.62 21.38Q842.77-220 830-220t-21.38-8.62Q800-237.23 800-250v-110q0-53.85-38.08-91.92Q723.85-490 670-490H397.23Z"/>
                                            </svg>
                                            Reply
                                        </button>
                                        <button class="btn delete-btn" data-message-id="<?php echo $message['id']; ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#666666">
                                                <path d="M280-720v520-520Zm12.31 580Q262-140 241-161q-21-21-21-51.31V-720h-12.54q-12.38 0-20.69-8.62-8.31-8.63-8.31-21.38 0-12.38 8.63-21.19 8.62-8.81 21.37-8.81H360q0-14.69 10.35-25.04 10.34-10.34 25.03-10.34h169.24q14.69 0 25.03 10.34Q600-794.69 600-780h151.54q12.75 0 21.37 8.63 8.63 8.63 8.63 21.38 0 12.76-8.63 21.37-8.62 8.62-21.37 8.62H740v149.23q0 12.75-8.63 21.38-8.63 8.62-21.38 8.62-12.76 0-21.37-8.62-8.62-8.63-8.62-21.38V-720H280v507.69q0 4.62 3.85 8.46 3.84 3.85 8.46 3.85h103.54q12.75 0 21.37 8.58 8.63 8.57 8.63 21.25 0 11.94-8.63 21.05-8.62 9.12-21.37 9.12H292.31Zm110.75-500q-12.75 0-21.37 8.62-8.61 8.63-8.61 21.38v300q0 12.75 8.63 21.38 8.63 8.62 21.38 8.62 12.76 0 21.37-8.62 8.62-8.63 8.62-21.38v-300q0-12.75-8.63-21.38-8.63-8.62-21.39-8.62Zm153.85 0q-12.76 0-21.37 8.62-8.62 8.63-8.62 21.38v80.39q0 12.58 8.63 21.1 8.63 8.51 21.39 8.51 12.75 0 21.37-8.62 8.61-8.63 8.61-21.38v-80q0-12.75-8.63-21.38-8.63-8.62-21.38-8.62Zm123 556.92q-81.76 0-139.29-57.62-57.54-57.63-57.54-139.39 0-81.76 57.6-139.29 57.6-57.54 139.32-57.54 40.45 0 76.45 15.61 36.01 15.62 62.63 42.23 26.61 26.62 42.23 62.62 15.61 36 15.61 76.44 0 81.87-57.62 139.4-57.63 57.54-139.39 57.54Zm17.78-203.99V-380q0-7.08-5.31-12.38-5.3-5.31-12.38-5.31-7.08 0-12.38 5.31-5.31 5.3-5.31 12.38v92.15q0 7.07 2.61 13.69 2.62 6.62 8.23 12.24l60.39 60.38q5.23 5.23 12.27 5.42 7.04.2 12.65-5.42 5.62-5.61 5.62-12.46 0-6.85-5.62-12.46l-60.77-60.61Z"/>
                                            </svg>
                                            Move to trash
                                        </button>
                                    </div>
                                    <!-- Show "To" section only if the message is sent (not received) -->
                                    <?php if ($messageType === 'sent'): ?>
                                        <div class="recipient-info">
                                            <span>To:</span>
                                            <div class="send-to">
                                                <h3><?php echo htmlspecialchars($recipient['name']); ?></h3>
                                                <span class="recipient-email">&lt;<?php echo htmlspecialchars($recipient['email']); ?>&gt;</span>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <p class="send-to-timestamp"><?php echo $timestamp; ?></p>
                                    <h4 class="title"><?php echo htmlspecialchars($message['subject']); ?></h4>
                                    <p class="content"><?php echo nl2br(htmlspecialchars($message['content'])); ?></p>
                                    
                                </div>

                                
                                <!-- Replies section -->
                                <?php if ($replies && $replies->num_rows > 0): ?>
                                    <div class="replies">
                                        <?php while ($reply = $replies->fetch_assoc()): ?>
                                            <?php $reply_timestamp = date('F j, Y g:i A', strtotime($reply['timestamp'])); ?>
                                            <div class="reply">
                                                <div class="reply-header">
                                                    <span class="reply-sender">Replied by: <?php echo htmlspecialchars($reply['sender_name']); ?></span>
                                                    <span class="reply-timestamp"><?php echo $reply_timestamp; ?></span>
                                                </div>
                                                <div class="reply-content">
                                                    <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <hr>
                    <?php endif; ?>
                <?php endwhile; ?>
                <?php if (!$messagesDisplayed): ?>
            <p class="no-messages">No messages found.</p>
            <?php endif; ?>
        <?php else: ?>
            <p class="no-messages">No messages found.</p>
        <?php endif; ?>
        </div>

    </div>

    <div class="date-banner">
        <p class="date-banner-text">
            <span class="date-banner-content">
            <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#666666"><path d="M123.59-87.87q-33.79 0-58.39-24.61-24.61-24.61-24.61-58.39v-500.17h83v500.17H725.5v83H123.59Zm151.41-155q-32.06 0-55.74-23.53-23.67-23.53-23.67-55.64v-340.48q0-25.55 10.69-43.56 10.7-18.01 30.81-29.44L557.5-914.87l81.57 45.83-365.5 206.65 283.93 160.3 278.43-157.19q13.92-7.72 28.69-6.98 14.77.74 27.21 7.69 12.43 6.96 20.01 19.8 7.57 12.83 7.57 29.14v287.59q0 32.11-23.67 55.64-23.68 23.53-55.74 23.53H275Zm281.7-336.96-102-102 51-51 51 51 143-142 51 51-194 193Z"/></svg>
                <span class="text"><span style="font-weight: 700;">Internflo</span> Secure and Reliable Email Infrastructure for Professionals - <span style="font-weight: 700;">University of Caloocan City</span> Internflo©2025 </span>
            </span>
        </p>
    </div>

    <div class="modal" id="composeModal">
        <div class="modal-header">
            <h2 class="modal-title">New Message</h2>
            <div>
                <button class="modal-minimize">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2E3849"><path d="M237.37-434.5q-19.15 0-32.33-13.17-13.17-13.18-13.17-32.33t13.17-32.33q13.18-13.17 32.33-13.17h485.26q19.15 0 32.33 13.17 13.17 13.18 13.17 32.33t-13.17 32.33q-13.18 13.17-32.33 13.17H237.37Z"/></svg>
                </button>
                <button class="modal-close">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2E3849"><path d="M480-416.35 287.83-224.17Q275.15-211.5 256-211.5t-31.83-12.67Q211.5-236.85 211.5-256t12.67-31.83L416.35-480 224.17-672.17Q211.5-684.85 211.5-704t12.67-31.83Q236.85-748.5 256-748.5t31.83 12.67L480-543.65l192.17-192.18Q684.85-748.5 704-748.5t31.83 12.67Q748.5-723.15 748.5-704t-12.67 31.83L543.65-480l192.18 192.17Q748.5-275.15 748.5-256t-12.67 31.83Q723.15-211.5 704-211.5t-31.83-12.67L480-416.35Z"/></svg>
                </button>
            </div>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <div class="input-wrapper">
                    <input type="text" class="form-control" placeholder="Recipients" id="recipients">
                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2E3849"><path d="M91-116.41q-37.78 0-64.39-26.61T0-207.41v-545.18q0-37.78 26.61-64.39T91-843.59h778q37.78 0 64.39 26.61T960-752.59v545.18q0 37.78-26.61 64.39T869-116.41H91Zm275.7-285.5q50.95 0 86.31-35.36t35.36-86.32q0-50.95-35.36-86.43-35.36-35.48-86.31-35.48-50.96 0-86.44 35.48t-35.48 86.43q0 50.96 35.48 86.32 35.48 35.36 86.44 35.36ZM91.41-203.59h552q-42-75-115.76-117.5t-160.24-42.5q-86 0-160 42.5t-116 117.5Zm513.61-317.13H789q19.15 0 32.33-13.17 13.17-13.18 13.17-32.33v-106.37q0-19.15-13.17-32.32-13.18-13.18-32.33-13.18H605.02q-19.15 0-32.32 13.18-13.18 13.17-13.18 32.32v106.37q0 19.15 13.18 32.33 13.17 13.17 32.32 13.17Zm92.11-89.76L770.41-662q8-6 17-1.5t9 14.5q0 1-7 14l-66.17 46.37q-12.44 8.72-26.11 8.72t-26.11-8.72L604.85-635q-1-1-7-14 0-10 9-14.5t17 1.5l73.28 51.52Z"/></svg>
                </div>
            </div>
            <div class="form-group">
                <div class="input-wrapper">
                    <input type="text" class="form-control" placeholder="Subject" id="subject">
                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2E3849"><path d="M414.5-677.37H257.13q-27.39 0-46.33-19.1-18.93-19.11-18.93-46.4 0-27.39 18.93-46.33 18.94-18.93 46.33-18.93h445.74q27.39 0 46.33 18.93 18.93 18.94 18.93 46.33 0 27.39-18.93 46.45-18.94 19.05-46.33 19.05H545.74v460q0 27.29-19.1 46.4-19.11 19.1-46.4 19.1-27.39 0-46.57-19.17-19.17-19.18-19.17-46.57v-459.76Z"/></svg>
                </div>
            </div>
            <div class="form-group">
                <div class="input-wrapper">
                    <textarea class="form-control content-area" placeholder="Content" id="content"></textarea>
                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2E3849"><path d="M196.41-182.3q-18.67 0-31.61-12.94-12.93-12.93-12.93-31.61 0-18.67 12.93-31.49 12.94-12.81 31.61-12.81h326.94q18.43.24 31.25 12.93 12.81 12.7 12.81 31.61 0 18.68-12.81 31.49-12.82 12.82-31.49 12.82h-326.7Zm-.24-168.37q-18.67 0-31.49-12.82-12.81-12.81-12.81-31.49 0-18.67 12.81-31.49 12.82-12.81 31.49-12.81h567.66q18.67 0 31.49 12.81 12.81 12.82 12.81 31.49 0 18.68-12.81 31.49-12.82 12.82-31.49 12.82H196.17Zm0-167.9q-18.67 0-31.49-12.81-12.81-12.82-12.81-31.49 0-18.67 12.81-31.49 12.82-12.81 31.49-12.81h567.66q18.67 0 31.49 12.81 12.81 12.82 12.81 31.49 0 18.67-12.81 31.49-12.82 12.81-31.49 12.81H196.17Zm1.2-168.13q-19.15 0-32.33-13.17-13.17-13.17-13.17-32.33 0-19.15 13.17-32.32 13.18-13.18 32.33-13.18h565.26q19.15 0 32.33 13.18 13.17 13.17 13.17 32.32 0 19.16-13.17 32.33-13.18 13.17-32.33 13.17H197.37Z"/></svg>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="sent-btn">
                Send
                <svg xmlns="http://www.w3.org/2000/svg" height="22px" viewBox="0 -960 960 960" width="22px" fill="#FFFFFF"><path d="M176.24-178.7q-22.87 9.44-43.26-3.85-20.39-13.3-20.39-38.17V-393.3l331-86.7-331-86.7v-172.58q0-24.87 20.39-38.17 20.39-13.29 43.26-3.85l613.61 259.28q28.11 12.43 28.11 42.02 0 29.59-28.11 42.02L176.24-178.7Z"/></svg>
            </button>
            <button class="trash-btn">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2E3849"><path d="M277.37-111.87q-37.78 0-64.39-26.61t-26.61-64.39v-514.5q-19.15 0-32.33-13.17-13.17-13.18-13.17-32.33t13.17-32.33q13.18-13.17 32.33-13.17H354.5q0-19.15 13.17-32.33 13.18-13.17 32.33-13.17h159.52q19.15 0 32.33 13.17 13.17 13.18 13.17 32.33h168.61q19.15 0 32.33 13.17 13.17 13.18 13.17 32.33t-13.17 32.33q-13.18 13.17-32.33 13.17v514.5q0 37.78-26.61 64.39t-64.39 26.61H277.37Zm405.26-605.5H277.37v514.5h405.26v-514.5ZM398.57-280.24q17.95 0 30.29-12.34 12.34-12.33 12.34-30.29v-274.74q0-17.96-12.34-30.29-12.34-12.34-30.29-12.34-17.96 0-30.42 12.34-12.45 12.33-12.45 30.29v274.74q0 17.96 12.45 30.29 12.46 12.34 30.42 12.34Zm163.1 0q17.96 0 30.3-12.34 12.33-12.33 12.33-30.29v-274.74q0-17.96-12.33-30.29-12.34-12.34-30.3-12.34-17.95 0-30.41 12.34-12.46 12.33-12.46 30.29v274.74q0 17.96 12.46 30.29 12.46 12.34 30.41 12.34Zm-284.3-437.13v514.5-514.5Z"/></svg>
            </button>
        </div>
    </div>
    <!-- FILTERING SEARCH INPUTS -->
    <script>
        // Get the search input element
        const searchInput = document.querySelector('.search-input');

        // Function to filter messages
        function filterMessages(searchTerm) {
            // Convert search term to lowercase for case-insensitive comparison
            const searchTermLower = searchTerm.toLowerCase();
            
            // Get all message elements
            const messages = document.querySelectorAll('.message');
            
            // Track if we have any matches
            let hasMatches = false;
            
            messages.forEach(message => {
                // Get the name, subject, and content from the message
                // Check both sent and received message structures
                const recipientElement = message.querySelector('.send-to h3') || message.querySelector('.recipient-info h3');
                const recipientName = recipientElement ? recipientElement.textContent : '';
                const subject = message.querySelector('.title')?.textContent || '';
                const content = message.querySelector('.content')?.textContent || '';
                
                // Check if name, subject, or content contains the search term (case-insensitive)
                const matchesName = recipientName.toLowerCase().includes(searchTermLower);
                const matchesSubject = subject.toLowerCase().includes(searchTermLower);
                const matchesContent = content.toLowerCase().includes(searchTermLower);
                
                // Show/hide the message and its following hr element based on the match
                if (matchesName || matchesSubject || matchesContent) {
                    message.style.display = '';
                    const hr = message.nextElementSibling;
                    if (hr && hr.tagName === 'HR') {
                        hr.style.display = '';
                    }
                    hasMatches = true;
                } else {
                    message.style.display = 'none';
                    const hr = message.nextElementSibling;
                    if (hr && hr.tagName === 'HR') {
                        hr.style.display = 'none';
                    }
                }
                
                // If there's a match, highlight the matching text while preserving case
                const elementsToHighlight = [
                    { 
                        element: recipientElement, 
                        text: recipientName, 
                        matches: matchesName 
                    },
                    { 
                        element: message.querySelector('.title'), 
                        text: subject, 
                        matches: matchesSubject 
                    },
                    { 
                        element: message.querySelector('.content'), 
                        text: content, 
                        matches: matchesContent 
                    }
                ];
                
                elementsToHighlight.forEach(({ element, text, matches }) => {
                    if (!element) return;
                    
                    if (searchTerm && matches) {
                        // Create a case-insensitive regex that preserves the original case
                        const regex = new RegExp(searchTerm.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&'), 'gi');
                        element.innerHTML = text.replace(regex, match => `<mark>${match}</mark>`);
                    } else {
                        // Reset the text if it doesn't match or search is empty
                        element.innerHTML = text;
                    }
                });
            });
            
            // Show/hide the "No messages found" text
            let noMessagesElement = document.querySelector('.no-messages');
            if (!hasMatches) {
                if (!noMessagesElement) {
                    noMessagesElement = document.createElement('p');
                    noMessagesElement.className = 'no-messages';
                    noMessagesElement.textContent = 'No messages found.';
                    document.querySelector('.messages').appendChild(noMessagesElement);
                }
                noMessagesElement.style.display = '';
            } else if (noMessagesElement) {
                noMessagesElement.style.display = 'none';
            }
        }

        // Add input event listener for live filtering
        searchInput.addEventListener('input', (e) => {
            filterMessages(e.target.value);
        });

        // Add search clear functionality
        searchInput.addEventListener('search', (e) => {
            if (e.target.value === '') {
                filterMessages('');
            }
        });

        // Add these styles to highlight search matches
        const style = document.createElement('style');
        style.textContent = `
            mark {
                background-color: #ffeb3b;
                padding: 0;
                color: inherit;
            }
        `;
        document.head.appendChild(style);
    </script>

    <!-- SEND, ACTION BUTTONS DELETE -->
    <script>
        const sendBtn = document.querySelector('.sent-btn');
        const recipientInput = document.getElementById('recipients');
        const subjectInput = document.getElementById('subject');
        const contentInput = document.getElementById('content');

        // Add click event listener to send button
        sendBtn?.addEventListener('click', async () => {
            // Create FormData object
            const formData = new FormData();
            formData.append('send_message', '1');
            formData.append('recipient', recipientInput.value);
            formData.append('subject', subjectInput.value);
            formData.append('content', contentInput.value);

            try {
                // Send POST request
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                // Log the response status
                console.log('Response status:', response.status);

                if (response.ok) {
                    try {
                        const result = await response.json();
                        console.log('Server response:', result);
                        
                        // Force reload regardless of success property
                        window.location.reload();
                    } catch (error) {
                        console.error('Error parsing JSON:', error);
                        // Still reload if JSON parsing fails
                        window.location.reload();
                    }
                } else {
                    console.error('Server returned error status:', response.status);
                    // Optionally reload even on error
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error sending message:', error);
                // Optionally reload even on network error
                window.location.reload();
            }
        });

        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', async function() {
                const messageElement = this.closest('.message');
                const hrElement = messageElement.nextElementSibling; // Get the HR element
                const messageId = this.dataset.messageId;

                // Disable the button to prevent double-clicks
                this.disabled = true;

                // Add visual feedback
                messageElement.style.opacity = '0.5';
                if (hrElement && hrElement.tagName === 'HR') {
                    hrElement.style.opacity = '0.5';
                }

                try {
                    const response = await fetch('delete_message_inbox.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ message_id: messageId })
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json();

                    if (data.status === 'success') {
                        // Animate the removal
                        messageElement.style.transition = 'all 0.3s ease-out';
                        messageElement.style.height = `${messageElement.offsetHeight}px`; // Set initial height
                        messageElement.offsetHeight; // Trigger reflow to apply initial height
                        messageElement.style.height = '0';
                        messageElement.style.opacity = '0';
                        messageElement.style.padding = '0';
                        messageElement.style.margin = '0';

                        // Also animate the HR if it exists
                        if (hrElement && hrElement.tagName === 'HR') {
                            hrElement.style.transition = 'all 0.3s ease-out';
                            hrElement.style.opacity = '0';
                            hrElement.style.height = '0';
                            hrElement.style.margin = '0';
                        }

                        // Remove both elements after animation
                        setTimeout(() => {
                            messageElement.remove();
                            if (hrElement && hrElement.tagName === 'HR') {
                                hrElement.remove();
                            }

                            // Check if there are no more messages
                            const remainingMessages = document.querySelectorAll('.message');
                            if (remainingMessages.length === 0) {
                                const messagesContainer = document.querySelector('.messages');
                                messagesContainer.innerHTML = '<p class="no-messages">No messages found.</p>';
                            }
                        }, 300);

                    } else {
                        throw new Error(data.message || 'Unknown error occurred');
                    }
                } catch (error) {
                    console.error('Error:', error);

                    // Restore the message and HR elements' appearance
                    messageElement.style.opacity = '1';
                    if (hrElement && hrElement.tagName === 'HR') {
                        hrElement.style.opacity = '1';
                    }
                    this.disabled = false;

                    // Show error message to user
                    const errorMessage = 'Failed to move message to trash. Please try again.';

                    // Create and show toast notification
                    const toast = document.createElement('div');
                    toast.className = 'error-toast';
                    toast.textContent = errorMessage;
                    document.body.appendChild(toast);

                    // Remove toast after 3 seconds
                    setTimeout(() => toast.remove(), 3000);
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const replyButtons = document.querySelectorAll('.btn.reply-btn');
            
            replyButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const messageDiv = this.closest('.message');
                    const messageWrapper = messageDiv.querySelector('.message-wrapper');
                    
                    // Remove any existing reply forms first
                    const existingForm = messageDiv.querySelector('.reply-form');
                    if (existingForm) {
                        existingForm.remove();
                        return;
                    }
                    
                    // Create new reply form
                    const replyForm = document.createElement('div');
                    replyForm.className = 'reply-form';
                    replyForm.innerHTML = `
                        <textarea class="reply-textarea" placeholder="Write your reply..."></textarea>
                        <div class="reply-form-actions">
                            <button type="button" class="send-reply-btn">Send Reply <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ffffff"><path d="M176.24-178.7q-22.87 9.44-43.26-3.85-20.39-13.3-20.39-38.17V-393.3l331-86.7-331-86.7v-172.58q0-24.87 20.39-38.17 20.39-13.29 43.26-3.85l613.61 259.28q28.11 12.43 28.11 42.02 0 29.59-28.11 42.02L176.24-178.7Z"/></svg></button>
                            <button type="button" class="cancel-reply-btn">Cancel</button>
                        </div>
                    `;
                    
                    // Insert after replies section if it exists, otherwise after message-content
                    const repliesSection = messageWrapper.querySelector('.replies');
                    if (repliesSection) {
                        repliesSection.after(replyForm);
                    } else {
                        messageWrapper.querySelector('.message-content').after(replyForm);
                    }
                    
                    // Focus the textarea
                    replyForm.querySelector('.reply-textarea').focus();
                    
                    // Handle send reply
                    const sendReplyBtn = replyForm.querySelector('.send-reply-btn');
                    sendReplyBtn.addEventListener('click', async function() {
                        const content = replyForm.querySelector('.reply-textarea').value;
                        const messageId = messageDiv.querySelector('.delete-btn').dataset.messageId;
                        
                        if (!content.trim()) {
                            alert('Please write a reply before sending.');
                            return;
                        }
                        
                        const formData = new FormData();
                        formData.append('reply_message', '1');
                        formData.append('original_message_id', messageId);
                        formData.append('reply_content', content);
                        
                        try {
                            const response = await fetch(window.location.href, {
                                method: 'POST',
                                body: formData
                            });
                            
                            const data = await response.json();
                            
                            if (data.status === 'success') {
                                location.reload();
                            } else {
                                alert(data.message || 'Error sending reply');
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            alert('Error sending reply');
                        }
                    });
                    
                    // Handle cancel reply
                    replyForm.querySelector('.cancel-reply-btn').addEventListener('click', function() {
                        replyForm.remove();
                    });
                });
            });
        });

    </script>

    <!-- MODAL -->
    <script>
        // Get modal elements
        // Get modal elements
        const composeBtn = document.querySelector('.compose-btn');
        const modal = document.getElementById('composeModal');
        const closeBtn = document.querySelector('.modal-close');
        const minimizeBtn = document.querySelector('.modal-minimize');
        const modalHeader = document.querySelector('.modal-header');
        const trashBtn = document.querySelector('.trash-btn'); // Get the trash button

        // Show modal when compose button is clicked
        composeBtn?.addEventListener('click', () => {
            modal.classList.add('show');
            modal.classList.remove('minimized');
        });

        // Close modal when close button is clicked
        closeBtn?.addEventListener('click', () => {
            modal.classList.remove('show');
            modal.classList.remove('minimized');
        });

        // Close modal when trash button is clicked
        trashBtn?.addEventListener('click', () => {
            modal.classList.remove('show');
            modal.classList.remove('minimized');
        });

        // Toggle minimize state when minimize button is clicked
        minimizeBtn?.addEventListener('click', () => {
            modal.classList.toggle('minimized');
        });

        // Restore modal when clicking on minimized header
        modalHeader?.addEventListener('click', (e) => {
            // Only restore if the modal is minimized and the click wasn't on the close or minimize buttons
            if (modal.classList.contains('minimized') && 
                !e.target.closest('.modal-close') && 
                !e.target.closest('.modal-minimize')) {
                modal.classList.remove('minimized');
            }
        });

    </script>

    <!-- MODAL SUGGESTIONS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const recipientInput = document.getElementById('recipients');
            let currentFocus = -1;
            let suggestions = [];
            
            
            // Create suggestions container
            const suggestionsContainer = document.createElement('div');
            suggestionsContainer.setAttribute('class', 'suggestions-container');
            recipientInput.parentNode.appendChild(suggestionsContainer);
            
            // Add CSS for suggestions with enhanced styling
            const style = document.createElement('style');
            style.textContent = `
                .suggestions-container {
                    position: absolute;
                    border: 1px solid #ddd;
                    border-top: none;
                    z-index: 99;
                    top: 100%;
                    left: 0;
                    right: 0;
                    background-color: #fff;
                    max-height: 250px;
                    overflow-y: auto;
                    display: none;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    border-radius: 0 0 4px 4px;
                }
                .suggestion-item {
                    padding: 12px;
                    cursor: pointer;
                    border-bottom: 1px solid #f0f0f0;
                    font-size: 14px;
                    line-height: 1.4;
                }
                .suggestion-name {
                    color: #171717;
                    font-weight: 600;
                    font-size: 16px;
                }
                .suggestion-role {
                    color: #2e3849;
                    font-size: 13px;
                    margin: 0 0 8px 0;
                    
                }
                .suggestion-email {
                    color: #666666;
                    font-weight: 600;
                    font-size: 13px;
                    margin: 4px 0 0 0;
                }
                .suggestion-item:last-child {
                    border-bottom: none;
                }
                .suggestion-item:hover {
                    background-color: #f5f5f5;
                }
                .suggestion-item.active {
                    background-color: #f0f0f0;
                }
            `;
            document.head.appendChild(style);
            
            // Handle input changes
            recipientInput.addEventListener('input', debounce(async function() {
                const term = this.value.trim();
                
                if (term.length < 2) {
                    suggestionsContainer.style.display = 'none';
                    return;
                }
                
                try {
                    const response = await fetch(`chat-fetch-recipients.php?term=${encodeURIComponent(term)}`);
                    suggestions = await response.json();
                    
                    if (suggestions.length > 0) {
                        // Parse the label to extract components
                        suggestionsContainer.innerHTML = suggestions.map((item, index) => {
                            const matches = item.label.match(/(.*?) \((.*?)\) - (.*)/);
                            const [_, name, role, email] = matches || [null, item.label, '', item.value];
                            
                            return `
                                <div class="suggestion-item" data-index="${index}" data-email="${item.value}">
                                    <div>
                                        <span class="suggestion-name">${name}</span>
                                        <span class="suggestion-role">${role}</span>
                                    </div>
                                    <div class="suggestion-email">&lt;${email}&gt;</div>
                                </div>
                            `;
                        }).join('');
                        
                        suggestionsContainer.style.display = 'block';
                    } else {
                        suggestionsContainer.style.display = 'none';
                    }
                } catch (error) {
                    console.error('Error fetching suggestions:', error);
                }
            }, 300));
            // Handle suggestion selection
            suggestionsContainer.addEventListener('click', function(e) {
                const item = e.target.closest('.suggestion-item');
                if (item) {
                    recipientInput.value = item.dataset.email;
                    suggestionsContainer.style.display = 'none';
                }
            });
            
            // Handle keyboard navigation
            recipientInput.addEventListener('keydown', function(e) {
                const items = suggestionsContainer.getElementsByClassName('suggestion-item');
                
                if (e.key === 'ArrowDown') {
                    currentFocus++;
                    addActive(items);
                    e.preventDefault();
                } else if (e.key === 'ArrowUp') {
                    currentFocus--;
                    addActive(items);
                    e.preventDefault();
                } else if (e.key === 'Enter' && currentFocus > -1) {
                    if (items[currentFocus]) {
                        items[currentFocus].click();
                        e.preventDefault();
                    }
                }
            });
            
            // Close suggestions on click outside
            document.addEventListener('click', function(e) {
                if (!recipientInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                    suggestionsContainer.style.display = 'none';
                }
            });
            
            function addActive(items) {
                if (!items) return;
                
                removeActive(items);
                
                if (currentFocus >= items.length) currentFocus = 0;
                if (currentFocus < 0) currentFocus = items.length - 1;
                
                items[currentFocus].classList.add('active');
            }
            
            function removeActive(items) {
                Array.from(items).forEach(item => item.classList.remove('active'));
            }
            
            // Debounce function to limit API calls
            function debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func.apply(this, args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }
        });
    </script>

    <script>
                (function(){if(!window.chatbase||window.chatbase("getState")!=="initialized"){window.chatbase=(...arguments)=>{if(!window.chatbase.q){window.chatbase.q=[]}window.chatbase.q.push(arguments)};window.chatbase=new Proxy(window.chatbase,{get(target,prop){if(prop==="q"){return target.q}return(...args)=>target(prop,...args)}})}const onLoad=function(){const script=document.createElement("script");script.src="https://www.chatbase.co/embed.min.js";script.id="sqZ5VD70WA_0wO97JZLEZ";script.domain="www.chatbase.co";document.body.appendChild(script)};if(document.readyState==="complete"){onLoad()}else{window.addEventListener("load",onLoad)}})();
    </script>
    <!-- FOOTER -->
        <div class="footbg">
            <div class="properdiv">
                <footer>
        
                    <!-- Logo Section -->
                    <div class="rightside">
                        <h2 class="university-name">UNIVERSITY OF CALOOCAN CITY</h2>
                        <h4 class="program-name">COMPUTER SCIENCE DEPARTMENT</h4>
                        <p>
                            Biglang Awa Street <br> Cor 11th Ave Catleya,<br> Caloocan 1400 Metro Manila, Philippines
                        </p>
                        <br>
                        <p style="margin: 0">
                            <strong>Phone:</strong>&nbsp;(02) 5310 6855
                        </p>
                        <p style="margin: 0">
                            <strong>Email:</strong>&nbsp;support@uccinternshipportal.ph
                        </p>
    
                    </div>
                
                    <!-- Internship Seekers Section -->
                    <div class="centerside">
                        <h4>INTERNSHIP SEEKERS</h4>
                        <ul>
                            <li><a href="companyloginpage.php#advertise">Internship by Company</a></li>
                            <li><a href="companyloginpage.php#advertise">Internship by City</a></li>
                            <li><a href="companyloginpage.php#advertise">Search Nearby Internship</a></li>
                        </ul>
                    </div>
                
                    <!-- Employers Section -->
                    <div class="centerside">
                        <h4>EMPLOYERS</h4>
                        <ul>
                            <li><a href="companyloginpage.php">Post Internships</a></li>
                        </ul>
                    </div>
                
                    <!-- About Interflo Section -->
                    <div class="centerside">
                        <h4>ABOUT INTERNFLO</h4>
                        <ul>
                            <li><a href="companyloginpage.php#about">About Us</a></li>
                            <li><a href="companyloginpage.php#chatbot">How It Works</a></li>
                            <li><a href="companyloginpage.php#contact">Contact Us</a></li>
                        </ul>
                    </div>
                
                </footer>
            </div>
        </div>
        <div class="underfooter-bg">
            <div class="underfooter">
                <div class="uf-content">
                    <p>Copyright <strong>University of Caloocan City</strong> Internflo©2025. All Rights Reserved</p>
                </div>
            </div>
        </div>
    <!-- FOOTER -->	
</body>
</html>
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

   // Database connection
   $conn = new mysqli("localhost", "u798912504_root", "Internfloucc2025*", "u798912504_internflo");

   // Check connection
   if ($conn->connect_error) {
       die("Connection failed: " . $conn->connect_error);
   }

   // If logged in, fetch user details from the database
   if ($isLoggedIn) {
       $userId = $_SESSION['user_id'];
       $sql = 'SELECT first_name, middle_name, last_name, suffix, email, name, profile_pic FROM students WHERE id = ?';
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
           
           $sender_email = $user['email'];
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
    function determineSenderType($conn, $email) {
        // Check in students table
        $stmt = $conn->prepare("SELECT email FROM students WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return 'student';
        }
        
        // Check in approvedrecruiters table
        $stmt = $conn->prepare("SELECT email FROM approvedrecruiters WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return 'recruiter';
        }
        
        // Check in m_advisors table
        $stmt = $conn->prepare("SELECT email FROM m_advisors WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return 'advisor';
        }
        
        // Default if not found
        return '';
    }

    function getRecipientName($conn, $email, $recipient_type) {
        $table = 'students';
        $name_fields = "CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name, ' ', COALESCE(suffix, '')) as full_name";
        
        if (!empty($recipient_type)) {
            switch($recipient_type) {
                case 'student':
                    $table = 'students';
                    $name_fields = "CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name, ' ', COALESCE(suffix, '')) as full_name";
                    break;
                case 'recruiter':
                    $table = 'approvedrecruiters';
                    $name_fields = "CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name, ' ', COALESCE(suffix, '')) as full_name";
                    break;
                case 'advisor':
                    $table = 'm_advisors';
                    $name_fields = "CONCAT(first_name, ' ', COALESCE(middle_initial, ''), ' ', last_name, ' ', COALESCE(suffix, '')) as full_name";
                    break;
            }
        }
        
        $query = "SELECT $name_fields FROM $table WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return [
            'name' => $row ? trim($row['full_name']) : $email,
            'email' => $email
        ];
    }
    $all_messages_query = "
    (SELECT 
        message_id,
        sender_email,
        recipient_email,
        recipient_type,
        subject,
        content,
        timestamp,
        deleted_timestamp as moved_timestamp,
        'deleted' as message_type
    FROM messaging_deleted 
    WHERE sender_email = ? AND recipient_email != ?)
    UNION ALL
    (SELECT 
        message_id,
        sender_email,
        recipient_email,
        recipient_type,
        subject,
        content,
        timestamp,
        moved_timestamp,
        'inbox' as message_type
    FROM messaging_inbox 
    WHERE sender_email = ? OR recipient_email = ?)
    ORDER BY timestamp DESC";
    
    $stmt = $conn->prepare($all_messages_query);
    $stmt->bind_param('ssss', $sender_email, $sender_email, $sender_email, $sender_email);
    $stmt->execute();
    $all_messages = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="pics/ucc.png">
    <title>UCC - Emails</title>
    <link rel="stylesheet" href="chat.css">
    <link rel="stylesheet" href="NAVX.css">
    <link rel="stylesheet" href="FOOTER.css">

</head>
<body>
    <div class="navbar">
            <div class="logo-container">
            <button class="menu-toggle" aria-label="Toggle Navigation">☰</button>
                    <img src="pics/ucc-logo.png" alt="Logo">

                </div>
                <div class="nav-links">
                    <a href="studentfrontpage.php">HOME</a>
                    <a href="#about">ABOUT US</a>
                    <a href="#contact">CONTACT US</a>
                </div>
                <div class="auth-buttons">
                    <?php if ($isLoggedIn): ?>
                        <div class="dropdown-container">
                            <div class="border">
                                <span class="greeting-text"><?php echo htmlspecialchars($user['email']); ?></span>
                                <div class="dropdown-btn" onclick="toggleDropdown()">
                                    <img src="<?php echo htmlspecialchars($profile_pic, ENT_QUOTES); ?>" alt="Profile Picture" onerror="this.onerror=null;this.src='pics/default_profile.png';" />
                                </div>
                            </div>
                            <div id="dropdown-content" class="dropdown-content">
                                <div class="user-fullname"><?php echo $fullName2; ?></div>
                                <hr style="margin: 0 auto">
                                <a href="student-profile.php">Profile</a>
                                <a href="../monitoring/std_dashboard.php">Internship</a>
                                <a href="chat-inbox.php">Emails</a>
                                <a href="student-account.php">Settings</a>
                                <a href="logout.php">Logout</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- <button id="signUpBtn" class="sign-up">Sign Up</button>
                        <button class="login" onclick="window.location.href='../STUDENTCOORLOG/login.php';">Login</button> -->
                    <?php endif; ?>
            </div>
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

            // Enhanced dropdown toggle function
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
            <a href="chat-inbox.php" class="nav-item">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#666666"><path d="M202.87-111.87q-37.78 0-64.39-26.61t-26.61-64.39v-554.26q0-37.78 26.61-64.39t64.39-26.61h554.26q37.78 0 64.39 26.61t26.61 64.39v554.26q0 37.78-26.61 64.39t-64.39 26.61H202.87Zm0-91h554.26v-118.56H640q-30 38-71.5 59t-88.5 21q-47 0-88.5-21t-71.5-59H202.87v118.56ZM480-321.43q31.28 0 58.04-15.79 26.76-15.78 43.29-42.3 6.53-9.86 16.33-15.89 9.8-6.02 21.77-6.02h137.7v-355.7H202.87v355.7h137.7q11.97 0 21.77 6.02 9.8 6.03 16.33 15.89 16.53 26.52 43.29 42.3 26.76 15.79 58.04 15.79ZM202.87-202.87h554.26-554.26Z"/></svg>
                Messages
            </a>
            <a href="chat-sent.php" class="nav-item">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#666666"><path d="M792-443 176-183q-20 8-38-3.5T120-220v-520q0-22 18-33.5t38-3.5l616 260q25 11 25 37t-25 37ZM200-280l474-200-474-200v140l240 60-240 60v140Zm0 0v-400 400Z"/></svg>
                Sent
            </a>
            <a href="chat-trash.php" class="nav-item active">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#002b7f"><path d="M277.37-111.87q-37.78 0-64.39-26.61t-26.61-64.39v-514.5q-19.15 0-32.33-13.17-13.17-13.18-13.17-32.33t13.17-32.33q13.18-13.17 32.33-13.17H354.5q0-19.15 13.17-32.33 13.18-13.17 32.33-13.17h159.52q19.15 0 32.33 13.17 13.17 13.18 13.17 32.33h168.61q19.15 0 32.33 13.17 13.17 13.18 13.17 32.33t-13.17 32.33q-13.18 13.17-32.33 13.17v514.5q0 37.78-26.61 64.39t-64.39 26.61H277.37Zm121.2-168.37q17.95 0 30.29-12.34 12.34-12.33 12.34-30.29v-274.74q0-17.96-12.34-30.29-12.34-12.34-30.29-12.34-17.96 0-30.42 12.34-12.45 12.33-12.45 30.29v274.74q0 17.96 12.45 30.29 12.46 12.34 30.42 12.34Zm163.1 0q17.96 0 30.3-12.34 12.33-12.33 12.33-30.29v-274.74q0-17.96-12.33-30.29-12.34-12.34-30.3-12.34-17.95 0-30.41 12.34-12.46 12.33-12.46 30.29v274.74q0 17.96 12.46 30.29 12.46 12.34 30.41 12.34Z"/></svg>
                Trash
            </a>
        </nav>
        
        <div class="messages">
            <?php if ($all_messages->num_rows > 0): ?>
                <?php while($message = $all_messages->fetch_assoc()): ?>
                   <?php 
    $is_deleted = $message['message_type'] === 'deleted';
    $is_inbox = $message['message_type'] === 'inbox';
    $is_current_user_sender = ($message['sender_email'] === $sender_email);
    
    if ($is_inbox) {
        if ($is_current_user_sender) {
            // Message sent by current user to someone else
            $recipient = getRecipientName($conn, $message['recipient_email'], $message['recipient_type']);
            $direction_label = "To:";
            $display_name = $recipient['name'];
            $display_email = $recipient['email'];
        } else {
            // Message received from someone else
            $sender_type = determineSenderType($conn, $message['sender_email']);
            $sender = getRecipientName($conn, $message['sender_email'], $sender_type);
            $direction_label = "From:";
            $display_name = $sender['name'];
            $display_email = $sender['email'];
        }
    } else {
        // For deleted messages, always show recipient (which should be someone else, not current user,
        // since your SQL is filtering out messages where current user is the recipient)
        $recipient = getRecipientName($conn, $message['recipient_email'], $message['recipient_type']);
        $direction_label = "To:";
        $display_name = $recipient['name'];
        $display_email = $recipient['email'];
    }
    
    $timestamp = date('F j, Y g:i A', strtotime($message['timestamp']));
    $moved_timestamp = !empty($message['moved_timestamp']) ? 
        date('F j, Y g:i A', strtotime($message['moved_timestamp'])) : '';
?>
                    <div class="message <?php echo $is_inbox ? 'received' : 'sent'; ?>">
                        <div class="message-wrapper">
                            <div class="message-content">
                                <div class="message-actions">
                                    <button class="btn restore-btn" data-message-id="<?php echo $message['message_id']; ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#666666">
                                            <path d="M479.23-140q-120.61 0-212.61-73.62-92-73.61-117.93-188.38-3.23-11.92 3.89-21.92 7.11-10 20.15-11.62 12.27-1.61 22 4.85t13.58 19Q231.15-319 306.73-259.5t172.5 59.5q117 0 198.5-81.5t81.5-198.5q0-117-81.5-198.5T479.23-760q-65.54 0-122.84 29.12-57.31 29.11-98.7 80.11h74.62q12.75 0 21.37 8.63 8.63 8.63 8.63 21.38 0 12.76-8.63 21.37-8.62 8.62-21.37 8.62H195.39q-15.37 0-25.76-10.4-10.4-10.39-10.4-25.76v-136.92q0-12.75 8.63-21.37 8.63-8.63 21.39-8.63 12.75 0 21.37 8.63 8.61 8.62 8.61 21.37v64.77q48.69-57.46 116.62-89.19Q403.77-820 479.23-820q70.8 0 132.63 26.77t107.83 72.77q46 46 72.77 107.82 26.77 61.83 26.77 132.62t-26.77 132.63q-26.77 61.85-72.77 107.85-46 46-107.83 72.77Q550.03-140 479.23-140Zm31.15-352.15 110 110q8.31 8.3 8.5 20.88.2 12.58-8.5 21.27-8.69 8.69-21.07 8.69-12.39 0-21.08-8.69l-117-117q-5.61-5.62-8.23-12.24-2.61-6.62-2.61-13.68V-650q0-12.75 8.62-21.38 8.63-8.62 21.39-8.62 12.75 0 21.37 8.62 8.61 8.63 8.61 21.38v157.85Z"/>
                                        </svg>
                                        Restore
                                    </button>
                                    <button class="btn delete-btn" data-message-id="<?php echo $message['message_id']; ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#666666">
                                            <path d="M292.31-140q-29.83 0-51.07-21.24Q220-182.48 220-212.31V-720h-10q-12.75 0-21.37-8.63-8.63-8.63-8.63-21.38 0-12.76 8.63-21.37Q197.25-780 210-780h150q0-14.69 10.35-25.04 10.34-10.34 25.03-10.34h169.24q14.69 0 25.03 10.34Q600-794.69 600-780h150q12.75 0 21.37 8.63 8.63 8.63 8.63 21.38 0 12.76-8.63 21.37Q762.75-720 750-720h-10v507.69q0 29.83-21.24 51.07Q697.52-140 667.69-140H292.31ZM680-720H280v507.69q0 5.39 3.46 8.85t8.85 3.46h375.38q5.39 0 8.85-3.46t3.46-8.85V-720ZM406.17-280q12.75 0 21.37-8.62 8.61-8.63 8.61-21.38v-300q0-12.75-8.63-21.38-8.62-8.62-21.38-8.62-12.75 0-21.37 8.62-8.61 8.63-8.61 21.38v300q0 12.75 8.62 21.38 8.63 8.62 21.39 8.62Zm147.69 0q12.75 0 21.37-8.62 8.61-8.63 8.61-21.38v-300q0-12.75-8.62-21.38-8.63-8.62-21.39-8.62-12.75 0-21.37 8.62-8.61 8.63-8.61 21.38v300q0 12.75 8.63 21.38 8.62 8.62 21.38 8.62ZM280-720v520-520Z"/>
                                        </svg>
                                        <?php echo $is_deleted ? 'Delete permanently' : 'Delete'; ?>
                                    </button>
                                </div>

                                <div class="recipient-info">
                                    <span><?php echo $direction_label; ?></span>
                                    <div class="send-to">
                                        <h3><?php echo htmlspecialchars($display_name); ?></h3>
                                        <span class="recipient-email">&lt;<?php echo htmlspecialchars($display_email); ?>&gt;</span>
                                    </div>
                                </div>

                                <div class="timestamp-container">
                                    <p class="send-to-timestamp-trash">
                                        <?php echo $is_inbox ? "Received: " : "Sent: "; ?><?php echo $timestamp; ?>
                                    </p>
                                    <?php if (!empty($moved_timestamp)): ?>
                                    <p class="delete-to-timestamp-trash">
                                        Moved to trash: <?php echo $moved_timestamp; ?>
                                    </p>
                                    <?php endif; ?>
                                </div>

                                <h4 class="title"><?php echo htmlspecialchars($message['subject']); ?></h4>
                                <p class="content"><?php echo nl2br(htmlspecialchars($message['content'])); ?></p>
                            </div>
                        </div>
                    </div>
                    <hr>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no-messages">No trash messages found.</p>
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
                const recipientName = message.querySelector('.send-to h3').textContent;
                const subject = message.querySelector('.title').textContent;
                const content = message.querySelector('.content').textContent;
                
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
                    { element: message.querySelector('.send-to h3'), text: recipientName, matches: matchesName },
                    { element: message.querySelector('.title'), text: subject, matches: matchesSubject },
                    { element: message.querySelector('.content'), text: content, matches: matchesContent }
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
                    noMessagesElement.textContent = 'No trash messages found.';
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

    <!-- SEND, ACTION BUTTONS DELETE AND RESTORE -->
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

        document.querySelectorAll('.restore-btn').forEach(button => {
            button.addEventListener('click', async function () {
                const messageId = this.dataset.messageId;
                const messageElement = this.closest('.message');
                const hrElement = messageElement.nextElementSibling;
                const isDeleted = messageElement.querySelector('.delete-btn').textContent.includes('permanently');
                const sourceTable = isDeleted ? 'messaging_deleted' : 'messaging_inbox';

                // Disable the button to prevent double-clicks
                this.disabled = true;

                // Add visual feedback
                messageElement.style.opacity = '0.5';
                if (hrElement && hrElement.tagName === 'HR') {
                    hrElement.style.opacity = '0.5';
                }

                try {
                    const formData = new FormData();
                    formData.append('message_id', messageId);
                    formData.append('source_table', sourceTable);

                    const response = await fetch('delete_restore_message_sent.php', {
                        method: 'POST',
                        body: formData,
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Animate the removal
                        messageElement.style.transition = 'all 0.3s ease-out';
                        messageElement.style.height = `${messageElement.offsetHeight}px`;
                        messageElement.offsetHeight; // Trigger reflow
                        messageElement.style.height = '0';
                        messageElement.style.opacity = '0';
                        messageElement.style.padding = '0';
                        messageElement.style.margin = '0';

                        // Animate HR if it exists
                        if (hrElement && hrElement.tagName === 'HR') {
                            hrElement.style.transition = 'all 0.3s ease-out';
                            hrElement.style.opacity = '0';
                            hrElement.style.height = '0';
                            hrElement.style.margin = '0';
                        }

                        // Remove elements after animation
                        setTimeout(() => {
                            messageElement.remove();
                            if (hrElement && hrElement.tagName === 'HR') {
                                hrElement.remove();
                            }

                            // Check if there are no more messages
                            const remainingMessages = document.querySelectorAll('.message');
                            if (remainingMessages.length === 0) {
                                const messagesContainer = document.querySelector('.messages');
                                messagesContainer.innerHTML = '<p class="no-messages">No trash messages found.</p>';
                            }
                        }, 300);
                    } else {
                        throw new Error(data.message || 'Error restoring message');
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
                    const errorMessage = 'Failed to restore message. Please try again.';

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

        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', async function() {
                const messageId = this.dataset.messageId;
                const messageElement = this.closest('.message');
                const hrElement = messageElement.nextElementSibling;
                const sourceTable = this.textContent.includes('permanently') ? 'messaging_deleted' : 'messaging_inbox';

                // Disable the button to prevent double-clicks
                this.disabled = true;

                // Add visual feedback
                messageElement.style.opacity = '0.5';
                if (hrElement && hrElement.tagName === 'HR') {
                    hrElement.style.opacity = '0.5';
                }

                try {
                    const formData = new FormData();
                    formData.append('message_id', messageId);
                    formData.append('source_table', sourceTable);

                    const response = await fetch('delete_message_trash.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();
                    if (data.success) {
                        // Animate the removal
                        messageElement.style.transition = 'all 0.3s ease-out';
                        messageElement.style.height = `${messageElement.offsetHeight}px`;
                        messageElement.offsetHeight; // Trigger reflow
                        messageElement.style.height = '0';
                        messageElement.style.opacity = '0';
                        messageElement.style.padding = '0';
                        messageElement.style.margin = '0';

                        // Animate HR if it exists
                        if (hrElement && hrElement.tagName === 'HR') {
                            hrElement.style.transition = 'all 0.3s ease-out';
                            hrElement.style.opacity = '0';
                            hrElement.style.height = '0';
                            hrElement.style.margin = '0';
                        }

                        // Remove elements after animation
                        setTimeout(() => {
                            messageElement.remove();
                            if (hrElement && hrElement.tagName === 'HR') {
                                hrElement.remove();
                            }

                            // Check if there are no more messages
                            const remainingMessages = document.querySelectorAll('.message');
                            if (remainingMessages.length === 0) {
                                const messagesContainer = document.querySelector('.messages');
                                messagesContainer.innerHTML = '<p class="no-messages">No trash messages found.</p>';
                            }
                        }, 300);
                    } else {
                        throw new Error(data.message || 'Error deleting message');
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
                    const errorMessage = 'Failed to delete message. Please try again.';

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
        (function(){if(!window.chatbase||window.chatbase("getState")!=="initialized"){window.chatbase=(...arguments)=>{if(!window.chatbase.q){window.chatbase.q=[]}window.chatbase.q.push(arguments)};window.chatbase=new Proxy(window.chatbase,{get(target,prop){if(prop==="q"){return target.q}return(...args)=>target(prop,...args)}})}const onLoad=function(){const script=document.createElement("script");script.src="https://www.chatbase.co/embed.min.js";script.id="qEhc2yKw7YIylj99unQ0q";script.domain="www.chatbase.co";document.body.appendChild(script)};if(document.readyState==="complete"){onLoad()}else{window.addEventListener("load",onLoad)}})();
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
                            <li><a href="studentmain.php">Internship by Company</a></li>
                            <li><a href="studentmain.php">Internship by City</a></li>
                            <li><a href="studentmain.php">Search Nearby Internship</a></li>
                        </ul>
                    </div>
                
                    <!-- Employers Section -->
                    <div class="centerside">
                        <h4>EMPLOYERS</h4>
                        <ul>
                            <li><a href="../RECRUITER/companymainpage.html">Post Internships</a></li>
                        </ul>
                    </div>
                
                    <!-- About Interflo Section -->
                    <div class="centerside">
                        <h4>ABOUT INTERNFLO</h4>
                        <ul>
                            <li><a href="studentfrontpage.php#about">About Us</a></li>
                            <li><a href="studentfrontpage.php#aichat">How It Works</a></li>
                            <li><a href="studentfrontpage.php#contact">Contact Us</a></li>
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
<?php
    session_start();

    // Include database configuration
    require_once 'config.php';  // Make sure this path is correct

    // Handle logout
    if (isset($_GET['logout'])) {
        session_destroy();
        header("Location: companymainpage.html");
        exit();
    }

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    // Validate internship_ad_id
    if (!isset($_GET['internship_ad_id'])) {
        die("Error: Internship AD ID is required");
    }

    $internship_ad_id = intval($_GET['internship_ad_id']);
    if ($internship_ad_id <= 0) {
        die("Error: Invalid Internship AD ID");
    }

    // Function to get full name
    function getFullName() {
        if (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
            return htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']);
        }
        return 'Your Name';
    }

    // Function to check if assessment exists
    function getExistingAssessment($conn, $internship_ad_id) {
        $stmt = $conn->prepare("
            SELECT af.*, aq.*, ao.* 
            FROM assessment_forms af
            LEFT JOIN assessment_questions aq ON af.form_id = aq.form_id
            LEFT JOIN assessment_options ao ON aq.question_id = ao.question_id
            WHERE af.internship_id = ?
        ");
        $stmt->bind_param("i", $internship_ad_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $assessment = array();
            $current_form = null;
            $current_question = null;
            
            while ($row = $result->fetch_assoc()) {
                // Changed 'id' to 'form_id' in these conditionals
                if ($current_form === null || $current_form['form_id'] !== $row['form_id']) {
                    $current_form = array(
                        'form_id' => $row['form_id'],  // Changed 'id' to 'form_id'
                        'title' => $row['title'],
                        'description' => $row['description'],
                        'questions' => array()
                    );
                    $assessment = $current_form;
                }
                
                if ($row['question_id'] !== null) {
                    if ($current_question === null || $current_question['id'] !== $row['question_id']) {
                        $current_question = array(
                            'id' => $row['question_id'],
                            'title' => $row['title'],
                            'type' => $row['question_type'],
                            'difficulty' => $row['difficulty'],
                            'options' => array()
                        );
                        $assessment['questions'][] = $current_question;
                    }
                    
                    if ($row['option_id'] !== null) {
                        $current_question['options'][] = array(
                            'id' => $row['option_id'],
                            'text' => $row['option_text'],
                            'is_correct' => $row['is_correct']
                        );
                    }
                }
            }
            return $assessment;
        }
        return null;
    }

    // Check for existing assessment
    $existingAssessment = null;
    try {
        $conn = new mysqli($servername, $username, $password, $dbname);  // Change $database to $dbname
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        $existingAssessment = getExistingAssessment($conn, $internship_ad_id);
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="pics/ucclogo2.png">
    <title>UCC - Company</title>
    <link rel="stylesheet" href="NAV.css">
    <link rel="stylesheet" href="questionaire.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

</head>
<body>

     <!-- NAVIGATION -->
     <div class="navbar">
        <div class="logo-container1">
            <button class="menu-toggle" aria-label="Toggle Navigation">☰</button>
            <img src="pics/ucclogonav-t.png" alt="Logo" class="logo-img">
        </div>
        <div class="nav-links">
            <a href="companyloginpage.php">HOME</a>
            <a href="#">ABOUT US</a>
            <a href="#">CONTACT US</a>
        </div>
        <div class="auth-buttons">
            <?php if(isset($_SESSION['email'])): ?>
                <div class="dropdown-container">
                    <div class="border">
                        <span class="greeting-text"><?php echo htmlspecialchars($_SESSION['email']); ?></span>
                        <div class="dropdown-btn" onclick="toggleDropdown()">
                            <img src="pics/profile.png" alt="Profile" onerror="this.onerror=null;this.src='pics/default_profile.jpg';">
                        </div>
                    </div>
                    <div id="dropdown-content" class="dropdown-content">
                        <div class="user-fullname"><?php echo getFullName(); ?></div>
                        <hr style="margin: 0 auto">
                        <a href="#">Profile</a>
                        <a href="../monitoring/std_dashboard.php">Internship</a>
                        <a href="form.php">Resume</a>
                        <a href="settings.php">Settings</a>
                        <a href="?logout=true">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="../STUDENTCOORLOG/login.php" class="login-btn">LOGIN</a>
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
                    menuToggle.innerHTML = '✕';
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
    <!-- NAVIGATION -->


    <div class="tip-banner">
        <div class="tip-banner-green-bar"></div>
        <p class="tip-banner-text">
            <span class="tip-banner-content">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#002b7f"><path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm0-160q17 0 28.5-11.5T520-480v-160q0-17-11.5-28.5T480-680q-17 0-28.5 11.5T440-640v160q0 17 11.5 28.5T480-440Zm0 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/></svg>
                <span class="text">
                Consider creating an assessment with at least 25 questions to ensure its overall accuracy and validity.</span>
            </span>
        </p>
    </div>
    <div class="bgwidth">
        <h1 class="header-title">Build <span class="highlight"> Internship Ad </span> Assessment</h1>
        <p class="subheader-title">Creating an assessment helps identify qualified candidates.</p>
    </div>





    <div class="form-header">
        <div class="green-bar"></div>
        <div class="header-content">
            <input type="text" class="form-title" placeholder="Untitled Form">
            <input type="text" class="form-description" placeholder="Form Description">
        </div>
    </div>

    
    <?php if ($existingAssessment): ?>
    <div class="form-header">
        <div class="green-bar"></div>
        <div class="header-content">
            <input type="text" class="form-title" value="<?php echo htmlspecialchars($existingAssessment['title']); ?>">
            <input type="text" class="form-description" value="<?php echo htmlspecialchars($existingAssessment['description']); ?>">
        </div>
    </div>

    <div id="questionsContainer">
        <?php foreach ($existingAssessment['questions'] as $question): ?>
            <div class="question-block" data-question-id="<?php echo $question['id']; ?>">
                <div class="question-header">
                    <input type="text" class="question-text" value="<?php echo htmlspecialchars($question['title']); ?>">
                    <div class="dropdown-container">
                        <select class="question-difficulty">
                            <option value="basic" <?php echo $question['difficulty'] === 'basic' ? 'selected' : ''; ?>>Basic</option>
                            <option value="intermediate" <?php echo $question['difficulty'] === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                            <option value="difficult" <?php echo $question['difficulty'] === 'difficult' ? 'selected' : ''; ?>>Difficult</option>
                        </select>
                        <select class="question-type">
                            <option value="multiple-choice" <?php echo $question['type'] === 'multiple-choice' ? 'selected' : ''; ?>>Multiple Choice</option>
                            <option value="checkbox" <?php echo $question['type'] === 'checkbox' ? 'selected' : ''; ?>>Checkboxes</option>
                            <option value="paragraph" <?php echo $question['type'] === 'paragraph' ? 'selected' : ''; ?>>Paragraph</option>
                        </select>
                    </div>
                </div>
                
                <?php if ($question['type'] === 'paragraph'): ?>
                    <textarea class="answer-input"><?php echo isset($question['paragraphAnswer']) ? htmlspecialchars($question['paragraphAnswer']) : ''; ?></textarea>
                <?php else: ?>
                    <div class="options-container">
                        <?php foreach ($question['options'] as $option): ?>
                            <div class="option-container" data-option-id="<?php echo $option['id']; ?>">
                                <div class="option-wrapper">
                                    <div class="option-icon">
                                        <?php if ($question['type'] === 'multiple-choice'): ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2e3849">
                                                <path d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                                            </svg>
                                        <?php else: ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2e3849">
                                                <path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h560v-560H200v560Z"/>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                    <input type="text" class="option-text" value="<?php echo htmlspecialchars($option['text']); ?>">
                                </div>
                                <div class="correct-answer">
                                    <label class="switch small">
                                        <input type="checkbox" class="correct-toggle" <?php echo $option['is_correct'] ? 'checked' : ''; ?>>
                                        <span class="slider small"></span>
                                    </label>
                                    <span class="correct-label">Correct Answer</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="option-actions">
                    <div class="add-option" onclick="addOption(this)">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#666666" style="margin-right: 8px;">
                            <path d="M440-440H240q-17 0-28.5-11.5T200-480q0-17 11.5-28.5T240-520h200v-200q0-17 11.5-28.5T480-760q17 0 28.5 11.5T520-720v200h200q17 0 28.5 11.5T760-480q0 17-11.5 28.5T720-440H520v200q0 17-11.5 28.5T480-200q-17 0-28.5-11.5T440-240v-200Z"/>
                        </svg>
                        Add option
                    </div>
                    <div class="remove-option" onclick="removeOption(this)">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#d93025" style="margin-right: 8px;">
                            <path d="M280-120q-33 0-56.5-23.5T200-200v-520q-17 0-28.5-11.5T160-760q0-17 11.5-28.5T200-800h160q0-17 11.5-28.5T400-840h160q17 0 28.5 11.5T600-800h160q17 0 28.5 11.5T800-760q0 17-11.5 28.5T760-720v520q0 33-23.5 56.5T680-120H280Zm200-284l76 76q11 11 28 11t28-11q11-11 11-28t-11-28l-76-76 76-76q11-11 11-28t-11-28q-11-11-28-11t-28 11l-76 76-76-76q-11-11-28-11t-28 11q-11 11-11 28t11 28l76 76-76 76q-11 11-11 28t11 28q11 11 28 11t28-11l76-76Z"/>
                        </svg>
                        Remove option
                    </div>
                </div>

                <div class="question-footer">
                    <div class="footer-actions">
                        <button class="action-button" onclick="addQuestion()" title="Add">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2e3849">
                                <path d="M440-440v120q0 17 11.5 28.5T480-280q17 0 28.5-11.5T520-320v-120h120q17 0 28.5-11.5T680-480q0-17-11.5-28.5T640-520H520v-120q0-17-11.5-28.5T480-680q-17 0-28.5 11.5T440-640v120H320q-17 0-28.5 11.5T280-480q0 17 11.5 28.5T320-440h120Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                            </svg>
                        </button>
                        <button class="action-button" onclick="duplicateQuestion()" title="Duplicate">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2e3849">
                                <path d="M360-240q-33 0-56.5-23.5T280-320v-480q0-33 23.5-56.5T360-880h360q33 0 56.5 23.5T800-800v480q0 33-23.5 56.5T720-240H360Zm0-80h360v-480H360v480ZM200-80q-33 0-56.5-23.5T120-160v-520q0-17 11.5-28.5T160-720q17 0 28.5 11.5T200-680v520h400q17 0 28.5 11.5T640-120q0 17-11.5 28.5T600-80H200Zm160-240v-480 480Z"/>
                            </svg>
                        </button>
                        <button class="action-button" onclick="deleteQuestion()" title="Delete">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2e3849">
                                <path d="M280-120q-33 0-56.5-23.5T200-200v-520q-17 0-28.5-11.5T160-760q0-17 11.5-28.5T200-800h160q0-17 11.5-28.5T400-840h160q17 0 28.5 11.5T600-800h160q17 0 28.5 11.5T800-760q0 17-11.5 28.5T760-720v520q0 33-23.5 56.5T680-120H280Zm400-600H280v520h400v-520ZM400-280q17 0 28.5-11.5T440-320v-280q0-17-11.5-28.5T400-640q-17 0-28.5 11.5T360-600v280q0 17 11.5 28.5T400-280Zm160 0q17 0 28.5-11.5T600-320v-280q0-17-11.5-28.5T560-640q-17 0-28.5 11.5T520-600v280q0 17 11.5 28.5T560-280ZM280-720v520-520Z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="banner">
        <p class="banner-text">
            Update your <span style="color: #ff8c00; font-weight: 600">assessment</span> for candidates applying to internship ads. Save changes now!
        </p>
        <button class="create-button">
            Update <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#FFFFFF"><path d="M236.44-152.59q-34.46 0-59.16-24.69-24.69-24.7-24.69-59.16 0-34.47 24.69-59.02Q201.98-320 236.44-320q34.47 0 59.02 24.54Q320-270.91 320-236.44q0 34.46-24.54 59.16-24.55 24.69-59.02 24.69Z"/></svg>
        </button>
    </div>
<?php endif; ?>
    
    <script>    
     document.querySelector('.create-button').addEventListener('click', function(e) {
    e.preventDefault();
    
    // Collect form data
    const formData = {
        form_id: <?php echo $existingAssessment['form_id']; ?>, // Add the form ID for updating
        title: document.querySelector('.form-title').value || 'Untitled Form',
        description: document.querySelector('.form-description').value || '',
        internship_id: <?php echo $internship_ad_id; ?>,
        questions: []
    };
    
    // Collect questions data
    document.querySelectorAll('.question-block').forEach((questionBlock, index) => {
        const question = {
            id: questionBlock.dataset.questionId, // Add the question ID if it exists
            title: questionBlock.querySelector('.question-text').value || 'Untitled Question',
            type: questionBlock.querySelector('.question-type').value,
            difficulty: questionBlock.querySelector('.question-difficulty').value,
            options: []
        };
        
        // Handle different question types
        if (question.type === 'paragraph') {
            const answerInput = questionBlock.querySelector('.answer-input');
            if (answerInput) {
                question.paragraphAnswer = answerInput.value;
            }
        } else {
            // Collect options for multiple choice and checkbox questions
            questionBlock.querySelectorAll('.option-container').forEach((optionContainer) => {
                question.options.push({
                    id: optionContainer.dataset.optionId, // Add the option ID if it exists
                    text: optionContainer.querySelector('.option-text').value || `Option`,
                    isCorrect: optionContainer.querySelector('.correct-toggle').checked
                });
            });
        }
        
        formData.questions.push(question);
    });

    fetch('update_assessment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({formData: formData})
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert(data.message);
            // Redirect back to company login page
            window.location.href = 'companyloginpage.php';
        } else {
            alert(data.message || 'Unknown error occurred');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating assessment: ' + error.message);
    });
});
    </script>

    <script>
        function deleteQuestion() {
            const questionBlock = event.target.closest('.question-block');
            const container = document.getElementById('questionsContainer');
            
            if (container.children.length > 1) {
                questionBlock.remove();
                updateDeleteButtons();
            }
        }

        function disableDeleteButton(button) {
            button.style.opacity = '0.5';
            button.style.cursor = 'not-allowed';
            button.style.pointerEvents = 'none';
        }

        function enableDeleteButton(button) {
            button.style.opacity = '1';
            button.style.cursor = 'pointer';
            button.style.pointerEvents = 'auto';
        }

        function updateDeleteButtons() {
            const container = document.getElementById('questionsContainer');
            const deleteButtons = container.querySelectorAll('.action-button[onclick="deleteQuestion()"]');
            
            if (container.children.length <= 1) {
                deleteButtons.forEach(button => disableDeleteButton(button));
            } else {
                deleteButtons.forEach(button => enableDeleteButton(button));
            }
        }

        function addQuestion() {
            const currentQuestion = event.target.closest('.question-block');
            const newQuestion = currentQuestion.cloneNode(true);
            
            // Reset to default values
            const questionType = newQuestion.querySelector('.question-type');
            const questionDifficulty = newQuestion.querySelector('.question-difficulty');
            questionType.value = 'multiple-choice';
            questionDifficulty.value = 'basic';
            
            // Reset the question text
            newQuestion.querySelector('.question-text').value = '';
            
            // Reset options container to only have one option
            const optionsContainer = newQuestion.querySelector('.options-container');
            const firstOption = optionsContainer.children[0].cloneNode(true);
            firstOption.querySelector('.option-text').value = '';
            firstOption.querySelector('.correct-toggle').checked = false;
            
            // Clear the options container and add only the first option
            optionsContainer.innerHTML = '';
            optionsContainer.appendChild(firstOption);
            
            // Ensure options container and buttons are visible
            optionsContainer.style.display = 'block';
            newQuestion.querySelector('.add-option').style.display = 'flex';
            newQuestion.querySelector('.remove-option').style.display = 'flex';
            
            // Remove any paragraph textarea if it exists
            const existingAnswer = newQuestion.querySelector('.answer-input');
            if (existingAnswer) {
                existingAnswer.remove();
            }
            
            
            // Insert the new question after the current one
            currentQuestion.insertAdjacentElement('afterend', newQuestion);

            // Update the option icons to show multiple choice
            updateOptionIcons(newQuestion.querySelector('.question-type'));
            
            // Disable remove option since it only has one option
            disableRemoveOption(newQuestion.querySelector('.remove-option'));
            
            // Update delete buttons
            updateDeleteButtons();
        }

        function duplicateQuestion() {
            const currentQuestion = event.target.closest('.question-block');
            const duplicatedQuestion = currentQuestion.cloneNode(true);
            
            // Preserve the values from the original question
            const originalTitle = currentQuestion.querySelector('.question-text').value;
            const originalType = currentQuestion.querySelector('.question-type').value;
            const originalDifficulty = currentQuestion.querySelector('.question-difficulty').value;
            
            duplicatedQuestion.querySelector('.question-text').value = originalTitle;
            duplicatedQuestion.querySelector('.question-type').value = originalType;
            duplicatedQuestion.querySelector('.question-difficulty').value = originalDifficulty;
            
            // Handle options based on question type
            if (originalType === 'paragraph') {
                const originalAnswer = currentQuestion.querySelector('.answer-input');
                if (originalAnswer) {
                    const newAnswer = duplicatedQuestion.querySelector('.answer-input');
                    if (newAnswer) {
                        newAnswer.value = originalAnswer.value;
                    }
                }
                // Hide options container and buttons for paragraph type
                duplicatedQuestion.querySelector('.options-container').style.display = 'none';
                duplicatedQuestion.querySelector('.add-option').style.display = 'none';
                duplicatedQuestion.querySelector('.remove-option').style.display = 'none';
            } else {
                // For multiple choice or checkbox questions, duplicate options
                const originalOptions = currentQuestion.querySelectorAll('.option-container');
                const duplicatedOptionsContainer = duplicatedQuestion.querySelector('.options-container');
                duplicatedOptionsContainer.innerHTML = ''; // Clear default options
                
                originalOptions.forEach(option => {
                    const duplicatedOption = option.cloneNode(true);
                    // Preserve option text and correct answer state
                    duplicatedOption.querySelector('.option-text').value = option.querySelector('.option-text').value;
                    duplicatedOption.querySelector('.correct-toggle').checked = option.querySelector('.correct-toggle').checked;
                    duplicatedOptionsContainer.appendChild(duplicatedOption);
                });
                
                // Update remove option button state
                const removeButton = duplicatedQuestion.querySelector('.remove-option');
                if (originalOptions.length <= 1) {
                    disableRemoveOption(removeButton);
                } else {
                    enableRemoveOption(removeButton);
                }
            }
            
            currentQuestion.insertAdjacentElement('afterend', duplicatedQuestion);
    
            // Ensure event listeners are properly set up for the new question
            updateOptionIcons(duplicatedQuestion.querySelector('.question-type'));
            // Enable delete buttons since we now have more than one question
            updateDeleteButtons();
        }

        function addOption(button) {
            const questionBlock = button.closest('.question-block');
            const optionsContainer = questionBlock.querySelector('.options-container');
            const newOption = optionsContainer.children[0].cloneNode(true);
            newOption.querySelector('.option-text').value = '';
            newOption.querySelector('.correct-toggle').checked = false;
            optionsContainer.appendChild(newOption);

            // Enable remove option button since we now have more than one option
            enableRemoveOption(questionBlock.querySelector('.remove-option'));
        }

        function removeOption(button) {
            const questionBlock = button.closest('.question-block');
            const optionsContainer = questionBlock.querySelector('.options-container');
            const removeButton = questionBlock.querySelector('.remove-option');
            
            if (optionsContainer.children.length > 1) {
                optionsContainer.removeChild(optionsContainer.lastChild);
                
                // Disable remove button if we're down to one option
                if (optionsContainer.children.length <= 1) {
                    disableRemoveOption(removeButton);
                }
            }
        }

        function disableRemoveOption(button) {
            button.style.opacity = '0.5';
            button.style.cursor = 'not-allowed';
            button.style.pointerEvents = 'none'; // This prevents clicking
        }

        function enableRemoveOption(button) {
            button.style.opacity = '1';
            button.style.cursor = 'pointer';
            button.style.pointerEvents = 'auto'; // This enables clicking
        }

        function updateOptionIcons(selectElement) {
            const questionBlock = selectElement.closest('.question-block');
            const optionIcons = questionBlock.querySelectorAll('.option-icon');
            
            const multipleChoiceIcon = `<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2e3849">
                <path d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
            </svg>`;
            
            const checkboxIcon = `<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2e3849">
                <path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h560v-560H200v560Z"/>
            </svg>`;

            optionIcons.forEach(iconContainer => {
                iconContainer.innerHTML = selectElement.value === 'multiple-choice' ? multipleChoiceIcon : checkboxIcon;
            });
        }

        // Initialize the page and set up event listeners
            document.addEventListener('DOMContentLoaded', function() {
                // Disable all remove option buttons initially
                document.querySelectorAll('.remove-option').forEach(button => {
                    const questionBlock = button.closest('.question-block');
                    const optionsContainer = questionBlock.querySelector('.options-container');
                    
                    if (optionsContainer.children.length <= 1) {
                        disableRemoveOption(button);
                    }
                });

                // Initialize delete button states
                updateDeleteButtons();

                // Event listener for question type changes
                document.addEventListener('change', function(e) {
                    if (e.target.classList.contains('question-type')) {
                        const questionBlock = e.target.closest('.question-block');
                        const optionsContainer = questionBlock.querySelector('.options-container'); 
                        const addOptionButton = questionBlock.querySelector('.add-option');
                        const removeOptionButton = questionBlock.querySelector('.remove-option');
                        const difficultySelect = questionBlock.querySelector('.question-difficulty');

                        // Set difficulty based on question type
                        if (e.target.value === 'checkbox') {
                            difficultySelect.value = 'intermediate';
                        } else if (e.target.value === 'paragraph') {
                            difficultySelect.value = 'difficult';
                        } else if (e.target.value === 'multiple-choice') {
                            difficultySelect.value = 'basic';  // Reset to basic when switching to multiple choice
                        }

                        if (e.target.value === 'paragraph') {
                            optionsContainer.style.display = 'none';
                            addOptionButton.style.display = 'none';
                            removeOptionButton.style.display = 'none';
                            
                            const answerInput = document.createElement('textarea');
                            answerInput.className = 'answer-input';
                            answerInput.placeholder = 'Enter long answer text';
                            
                            const existingAnswer = questionBlock.querySelector('.answer-input');
                            if (existingAnswer) {
                                existingAnswer.remove();
                            }
                            
                            const questionHeader = questionBlock.querySelector('.question-header');
                            questionHeader.insertAdjacentElement('afterend', answerInput);
                        } else {
                            optionsContainer.style.display = 'block';
                            addOptionButton.style.display = 'flex';
                            removeOptionButton.style.display = 'flex';
                            
                            const answerInput = questionBlock.querySelector('.answer-input');
                            if (answerInput) {
                                answerInput.remove();
                            }
                            
                            updateOptionIcons(e.target);
                        }
                    }
                });
            });

    </script>

</body>
</html>
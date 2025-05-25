<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    $db_host = "localhost";
    $db_user = "u798912504_root";
    $db_pass = "Internfloucc2025*";
    $db_name = "u798912504_internflo";

    try {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        // Fetch all assessment forms
        $forms_query = "SELECT * FROM assessment_forms ORDER BY created_at DESC";
        $forms_result = $conn->query($forms_query);

        if (!$forms_result) {
            throw new Exception("Error fetching forms: " . $conn->error);
        }
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internship Assessment</title>
    <style>
     
    </style>
    <link rel="stylesheet" href="questionaire-exam.css">
</head>
<body>
    <div class="student-assessment-container">
        <?php while ($form = $forms_result->fetch_assoc()): ?>
            <div class="student-assessment-header">
                <div class="student-assessment-title">
                    <h3><?php echo htmlspecialchars($form['title']); ?></h3>
                </div>

                <button class="student-start-button" onclick="startAssessment(<?php echo $form['form_id']; ?>)">
                    Start Assessment
                </button>
            </div>

            <!-- Modal for questions -->
            <div id="student-questionModal-<?php echo $form['form_id']; ?>" class="student-modal">
                <div class="student-modal-content">
                    <div class="student-form-header">
                        <h2><?php echo htmlspecialchars($form['title']); ?></h2>
                        <p class="student-form-description"><?php echo htmlspecialchars($form['description']); ?></p>
                    </div>
                    <div class="student-progress-bar">
                        <div id="student-progress-<?php echo $form['form_id']; ?>" class="student-progress"></div>
                    </div>
                    <form id="student-assessment-form-<?php echo $form['form_id']; ?>" onsubmit="submitAssessment(event, <?php echo $form['form_id']; ?>)">
                        
                    <div id="student-question-container-<?php echo $form['form_id']; ?>">
                            
                            <!-- Questions will be dynamically inserted here -->
                        </div>
                    </form>
                </div>
            </div>

            <?php
                // Fetch questions and store them in a data attribute
                $questions_query = "SELECT * FROM assessment_questions WHERE form_id = ? ORDER BY question_order";
                $stmt = $conn->prepare($questions_query);
                $stmt->bind_param("i", $form['form_id']);
                $stmt->execute();
                $questions_result = $stmt->get_result();
                $questions = array();

                while ($question = $questions_result->fetch_assoc()) {
                    // Fetch options for this question
                    $options_query = "SELECT * FROM assessment_options WHERE question_id = ? ORDER BY option_order";
                    $stmt2 = $conn->prepare($options_query);
                    $stmt2->bind_param("i", $question['question_id']);
                    $stmt2->execute();
                    $options_result = $stmt2->get_result();
                    $options = array();

                    while ($option = $options_result->fetch_assoc()) {
                        $options[] = $option;
                    }

                    $question['options'] = $options;
                    $questions[] = $question;
                }
                ?>
                <script>
                    // Store questions data for this form
                    window.questionData = window.questionData || {};
                    window.questionData[<?php echo $form['form_id']; ?>] = <?php echo json_encode($questions); ?>;
                </script>
            <?php endwhile; ?>
    </div>

    <script>
        let currentQuestionIndex = 0;
        let activeTimer = null;
        let currentFormId = null;
        let answers = {};

        function getTimerDuration(difficulty) {
            switch(difficulty.toLowerCase()) {
                case 'basic': return 20;
                case 'intermediate': return 30;
                case 'difficult': return 40;
                default: return 20;
            }
        }

        function updateProgressBar(formId, questionIndex) {
            const questions = window.questionData[formId];
            const totalQuestions = questions.length;
            const progressElement = document.getElementById(`student-progress-${formId}`);
            
            // Calculate percentage based on current question index (0-based) 
            const progressPercentage = ((questionIndex + 1) / totalQuestions) * 100;
            progressElement.style.width = `${progressPercentage}%`;
        }

        function displayQuestion(formId, questionIndex) {
            const questions = window.questionData[formId];
            const question = questions[questionIndex];
            const container = document.getElementById(`student-question-container-${formId}`);
            
            // Update progress bar
            updateProgressBar(formId, questionIndex);

            // Create question HTML
            let html = `
                <div class="student-question-container">


                    <div class="student-difficulty-badge student-difficulty-${question.difficulty.toLowerCase()}">
                        ${question.difficulty.toUpperCase()}
                    </div>
                    <div class="student-assessment-timer" id="student-timer-${formId}">Time remaining: <span id="student-time-left-${formId}"></span>s</div>
                    <div class="student-question-text">
                        ${question.title}
                    </div>

                </div>

            `;

            if (question.question_type === 'paragraph') {
                html += `
                    <textarea 
                        class="student-paragraph-answer" 
                        name="question[${question.question_id}]"
                        placeholder="Type your answer here..."
                        ${question.is_required ? 'required' : ''}
                    ></textarea>
                `;

            } else {
                html += '<div class="student-option-list">';
                    question.options.forEach(option => {
                        const inputType = question.question_type === 'multiple-choice' ? 'radio' : 'checkbox';
                        html += `
                            <div class="student-option-item">
                                <label for="student-option-${option.option_id}">
                                    <input 
                                        type="${inputType}" 
                                        id="student-option-${option.option_id}"
                                        name="question[${question.question_id}]${inputType === 'checkbox' ? '[]' : ''}"
                                        value="${option.option_id}"
                                        ${question.is_required && inputType === 'radio' ? 'required' : ''}
                                    >
                                    ${option.option_text}
                                </label>
                            </div>
                        `;
                    });
                html += '</div>';
            }

            // Add navigation buttons
            html += `
                <div>
                    ${questionIndex < questions.length - 1 ? 
                        `<button type="button" onclick="nextQuestion(${formId})" class="student-submit-button">Next Question</button>` :
                        `<button type="submit" class="student-submit-button">Submit Assessment</button>`
                    }
                </div>
            `;

            container.innerHTML = html;

            // Start timer for this question
            const duration = getTimerDuration(question.difficulty);
            startTimer(duration, formId, questionIndex);
            }

            function startTimer(duration, formId, questionIndex) {
                if (activeTimer) {
                    clearInterval(activeTimer);
                }

                const display = document.getElementById(`student-time-left-${formId}`);
                const timerElement = document.getElementById(`student-timer-${formId}`);
                let timer = duration;
                display.textContent = timer;
                
                // Set initial color
                updateTimerColor(timerElement, timer, duration);

                activeTimer = setInterval(() => {
                    timer--;
                    display.textContent = timer;
                    
                    // Update color based on remaining time
                    updateTimerColor(timerElement, timer, duration);
                    
                    if (timer < 0) {
                        clearInterval(activeTimer);
                        if (questionIndex < window.questionData[formId].length - 1) {
                            nextQuestion(formId);
                        } else {
                            document.getElementById(`student-assessment-form-${formId}`).submit();
                        }
                    }
                }, 1000);
            }

            

            function updateTimerColor(timerElement, currentTime, totalTime) {
                // Remove all existing timer classes
                timerElement.classList.remove('student-timer-normal', 'student-timer-warning', 'student-timer-danger');
                
                // Calculate percentage of time remaining
                const percentageLeft = (currentTime / totalTime) * 100;
                
                // Add appropriate class based on time remaining
                if (percentageLeft > 50) {
                    timerElement.classList.add('student-timer-normal');
                } else if (percentageLeft > 25) {
                    timerElement.classList.add('student-timer-warning');
                } else {
                    timerElement.classList.add('student-timer-danger');
                }
            }


            function startAssessment(formId) {
                currentFormId = formId;
                currentQuestionIndex = 0;
                answers = {};
                
                const modal = document.getElementById(`student-questionModal-${formId}`);
                modal.style.display = 'block';
                
                // Set initial progress (first question)
                updateProgressBar(formId, 0);
                displayQuestion(formId, currentQuestionIndex);
            }


            function nextQuestion(formId) {
                // Save current question's answers
                const form = document.getElementById(`student-assessment-form-${formId}`);
                const formData = new FormData(form);
                for (let [key, value] of formData.entries()) {
                    answers[key] = value;
                }

                currentQuestionIndex++;
                
                // Update progress before showing next question
                updateProgressBar(formId, currentQuestionIndex);
                displayQuestion(formId, currentQuestionIndex);
            }
            function submitAssessment(event, formId) {
            event.preventDefault();
            
            // Save final question's answers
            const form = document.getElementById(`student-assessment-form-${formId}`);
            const formData = new FormData(form);
            for (let [key, value] of formData.entries()) {
                answers[key] = value;
            }

            // Set progress to 100% on final question
            const questions = window.questionData[formId];
            updateProgressBar(formId, questions.length - 1);

            // Create final FormData with all answers
            const finalFormData = new FormData();
            for (let key in answers) {
                finalFormData.append(key, answers[key]);
            }

            fetch('questionaire_submit_answers.php', {
                method: 'POST',
                body: finalFormData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const score = data.score;
                    const message = `
                        Assessment submitted successfully!\n
                        Score: ${score.correct_answers} out of ${score.total_questions} correct (${score.percentage}%)
                    `;
                    alert(message);
                    
                    // Hide the modal
                    const modal = document.getElementById(`student-questionModal-${formId}`);
                    modal.style.display = 'none';
                    
                    // Optional: Reset answers and progress
                    answers = {};
                    const container = document.getElementById(`student-question-container-${formId}`);
                    container.innerHTML = '';
                } else {
                    alert('Error submitting assessment: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error submitting assessment');
            });
        }

    </script>
</body>
</html>

<?php
$conn->close();
?>

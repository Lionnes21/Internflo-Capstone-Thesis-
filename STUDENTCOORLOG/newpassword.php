<?php
    // Start the session
    session_start();

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']);
        $response = array('success' => false, 'message' => '');

        // Retrieve the email from the session
        if (isset($_SESSION['email'])) {
            $email = $_SESSION['email'];

            // Hash the new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Database connection
            $mysqli = new mysqli("localhost", "u798912504_root", "Internfloucc2025*", "u798912504_internflo");

            // Check connection
            if ($mysqli->connect_error) {
                $response['message'] = "Connection failed: " . $mysqli->connect_error;
            } else {
                // Update the password in the database
                $stmt = $mysqli->prepare("UPDATE students SET password = ? WHERE email = ?");
                $stmt->bind_param("ss", $hashed_password, $email);

                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Password updated successfully';
                } else {
                    $response['message'] = 'Failed to update password. Please try again.';
                }

                $stmt->close();
                $mysqli->close();
            }
        } else {
            $response['message'] = 'No session data found. Please request a new password reset.';
        }

        // Send JSON response
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UCC - Student Forgot Password</title>
    <link rel="icon" href="pics/ucc.png">
    <link rel="stylesheet" href="newpasswords.css">
    <link rel="stylesheet" href="../css/NAV.css">
    <link rel="stylesheet" href="../css/FOOTER.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

<div class="reset-container">
    <div class="reset-content-wrapper">
        <div class="reset-form-section">
            <div class="reset-form-header">
                <h1>RESET PASSWORD</h1>
                <p>Please enter your new password</p>
            </div>

            <form name="resetForm" id="resetForm" method="POST" onsubmit="return handleSubmit(event)">
                <div class="reset-form-row">
                    <div class="reset-form-input-group">
                        <input type="password" id="password" name="password" placeholder="New Password">
                        <p id="passwordError" style="display: none;" class="reset-error-message"></p>
                    </div>
                </div>
                <div class="reset-form-row">
                    <div class="reset-form-input-group">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password">
                        <p id="confirmPasswordError" style="display: none;" class="reset-error-message"></p>
                    </div>
                </div>
                <div style="padding-top: 5px; font-size: 15px; color: #666;">
                    <input type="checkbox" onclick="togglePasswordVisibility()"> Show Password
                </div>
                
                <button class="reset-submit-button" type="submit">Reset Password <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h447q16 0 30.5 6t25.5 17l114 114q11 11 17 25.5t6 30.5v447q0 33-23.5 56.5T760-120H200Zm280-120q50 0 85-35t35-85q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 50 35 85t85 35ZM280-560h280q17 0 28.5-11.5T600-600v-80q0-17-11.5-28.5T560-720H280q-17 0-28.5 11.5T240-680v80q0 17 11.5 28.5T280-560Z"/></svg></button>
                <div id="successMessage" style="display: none; color: green; text-align: center; margin-top: 15px;"></div>
            </form>
        </div>
    </div>
</div>

<script>
    function togglePasswordVisibility() {
        var passwordField = document.getElementById('password');
        var confirmPasswordField = document.getElementById('confirm_password');
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            confirmPasswordField.type = 'text';
        } else {
            passwordField.type = 'password';
            confirmPasswordField.type = 'password';
        }
    }

    function setDefaultBorder(input) {
        input.style.border = '2px solid #8b94a7';
        input.style.boxShadow = 'none';
    }

    function setBlueBorder(input) {
        input.style.border = '2px solid blue';
        input.style.boxShadow = '0 0 0 0.3rem rgba(0, 123, 255, 0.25)';
    }

    function setRedBorder(input) {
        input.style.border = '2px solid red';
        input.style.boxShadow = '0 0 0 0.3rem rgba(255, 0, 0, 0.25)';
    }

    function setGreenBorder(input) {
        input.style.border = '2px solid green';
        input.style.boxShadow = 'none';
    }

    function validatePassword(password) {
        const uppercase = /[A-Z]/.test(password);
        const length = password.length >= 8;
        const symbol = /[!@#$%^&*(),.?":{}|<>]/.test(password);
        return uppercase && length && symbol;
    }

    function isInputValid(input, errorElement) {
        if (!input.value.trim()) {
            return false;
        }
        
        if (input.id === 'password') {
            return validatePassword(input.value);
        } else {
            // For confirm password
            const passwordInput = document.getElementById('password');
            return input.value === passwordInput.value;
        }
    }

    function validateForm() {
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const passwordError = document.getElementById('passwordError');
        const confirmPasswordError = document.getElementById('confirmPasswordError');
        let isValid = true;

        // Mark both fields as touched when form is submitted
        passwordInput.dataset.touched = 'true';
        confirmPasswordInput.dataset.touched = 'true';

        // Check for empty password field
        if (!passwordInput.value.trim()) {
            setRedBorder(passwordInput);
            passwordError.textContent = 'This is a required field.';
            passwordError.style.color = 'red';
            passwordError.style.display = 'block';
            isValid = false;
        }

        // Check for empty confirm password field
        if (!confirmPasswordInput.value.trim()) {
            setRedBorder(confirmPasswordInput);
            confirmPasswordError.textContent = 'This is a required field.';
            confirmPasswordError.style.color = 'red';
            confirmPasswordError.style.display = 'block';
            isValid = false;
        }

        // Only check password requirements if password field is not empty
        if (passwordInput.value.trim()) {
            if (!validatePassword(passwordInput.value)) {
                setRedBorder(passwordInput);
                passwordError.textContent = 'Password must be at least 8 characters, include an uppercase letter, and a symbol.';
                passwordError.style.color = 'red';
                passwordError.style.display = 'block';
                isValid = false;
            }
        }

        // Only check matching if both fields have values
        if (passwordInput.value.trim() && confirmPasswordInput.value.trim()) {
            if (passwordInput.value !== confirmPasswordInput.value) {
                setRedBorder(confirmPasswordInput);
                confirmPasswordError.textContent = 'Passwords do not match.';
                confirmPasswordError.style.color = 'red';
                confirmPasswordError.style.display = 'block';
                isValid = false;
            }
        }

        return isValid;
    }

    async function handleSubmit(event) {
        event.preventDefault();
        
        if (!validateForm()) {
            return false;
        }

        const form = document.getElementById('resetForm');
        const formData = new FormData(form);

        try {
            const response = await fetch('newpassword.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            const successMessage = document.getElementById('successMessage');
            successMessage.style.display = 'block';
            
            if (result.success) {
                successMessage.style.color = 'green';
                successMessage.textContent = result.message + '. Redirecting to login page...';
                
                // Redirect after 2 seconds
                setTimeout(() => {
                    window.location.href = '../STUDENTCOORLOG/login.php';
                }, 2000);
            } else {
                successMessage.style.color = 'red';
                successMessage.textContent = result.message;
            }
        } catch (error) {
            console.error('Error:', error);
            const successMessage = document.getElementById('successMessage');
            successMessage.style.display = 'block';
            successMessage.style.color = 'red';
            successMessage.textContent = 'An error occurred. Please try again.';
        }

        return false;
    }
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const passwordError = document.getElementById('passwordError');
        const confirmPasswordError = document.getElementById('confirmPasswordError');

        function handleInput(input, errorElement) {
            input.dataset.touched = 'true'; // Mark as touched when user types

            if (!input.value.trim()) {
                setRedBorder(input);
                errorElement.textContent = 'This is a required field.';
                errorElement.style.color = 'red';
                errorElement.style.display = 'block';
            } else {
                if (input === passwordInput) {
                    if (validatePassword(input.value)) {
                        setGreenBorder(input);
                        errorElement.style.display = 'none';
                    } else {
                        setRedBorder(input);
                        errorElement.textContent = 'Password must be at least 8 characters, include an uppercase letter, and a symbol.';
                        errorElement.style.color = 'red';
                        errorElement.style.display = 'block';
                    }
                } else {
                    // For confirm password
                    if (input.value === passwordInput.value) {
                        setGreenBorder(input);
                        errorElement.style.display = 'none';
                    } else {
                        setRedBorder(input);
                        errorElement.textContent = 'Passwords do not match.';
                        errorElement.style.color = 'red';
                        errorElement.style.display = 'block';
                    }
                }
            }
        }

        // Handle input events for both fields
        [passwordInput, confirmPasswordInput].forEach(input => {
            const errorElement = input === passwordInput ? passwordError : confirmPasswordError;
            
            // Initialize touched state
            input.dataset.touched = 'false';

            // When focusing on the field
            input.addEventListener('focus', function() {
                if (input.dataset.touched === 'true' && !isInputValid(input, errorElement)) {
                    setRedBorder(input);
                } else {
                    setBlueBorder(input);
                }
            });

            // When focusing out of the field
            input.addEventListener('blur', function() {
                if (input.dataset.touched === 'true') {
                    if (isInputValid(input, errorElement)) {
                        setDefaultBorder(input);
                        errorElement.style.display = 'none';
                    } else {
                        setRedBorder(input);
                        if (!input.value.trim()) {
                            errorElement.textContent = 'This is a required field.';
                        } else if (input === passwordInput) {
                            errorElement.textContent = 'Password must be at least 8 characters, include an uppercase letter, and a symbol.';
                        } else {
                            errorElement.textContent = 'Passwords do not match.';
                        }
                        errorElement.style.color = 'red';
                        errorElement.style.display = 'block';
                    }
                } else {
                    setDefaultBorder(input);
                }
            });

            // While typing
            input.addEventListener('input', function() {
                handleInput(input, errorElement);
            });
        });
    });
</script>
 






    <script src="newpassword.js"></script>

</body>
</html>


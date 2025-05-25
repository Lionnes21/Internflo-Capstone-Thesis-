<?php
    session_start();
    require 'config.php';
    require 'vendor/autoload.php';

    // Helper Functions
    function generateOTP() {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    function updateOTPInDatabase($email, $otp) {
        global $conn;
        
        try {
            // Add debug logging
            error_log("Updating OTP for email: " . $email . " with OTP: " . $otp);
            
            // First check if the user exists
            $check_stmt = $conn->prepare("SELECT email FROM unverified_users WHERE email = ?");
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows === 0) {
                error_log("User not found in unverified_users: " . $email);
                return false;
            }
            
            // Update the OTP
            $stmt = $conn->prepare("UPDATE unverified_users SET otp = ?, otp_timestamp = ? WHERE email = ?");
            if (!$stmt) {
                error_log("Prepare statement failed: " . $conn->error);
                return false;
            }
            
            $timestamp = time();
            $stmt->bind_param("sis", $otp, $timestamp, $email);
            
            $success = $stmt->execute();
            
            if (!$success) {
                error_log("Update failed: " . $stmt->error);
                return false;
            }
            
            // Verify the update
            $verify_stmt = $conn->prepare("SELECT otp FROM unverified_users WHERE email = ?");
            $verify_stmt->bind_param("s", $email);
            $verify_stmt->execute();
            $verify_result = $verify_stmt->get_result();
            $updated_data = $verify_result->fetch_assoc();
            
            if ($updated_data['otp'] !== $otp) {
                error_log("OTP verification failed. Stored OTP doesn't match generated OTP");
                return false;
            }
            
            error_log("OTP successfully updated for email: " . $email);
            return true;
            
        } catch (Exception $e) {
            error_log("Error updating OTP: " . $e->getMessage());
            return false;
        }
    }

    function sendSMS($phoneNumber, $message) {
        $ch = curl_init();
        
        if (substr($phoneNumber, 0, 2) === '09') {
            $phoneNumber = '63' . substr($phoneNumber, 1);
        }
        
        $parameters = array(
            'apikey' => 'c3c8e83cf2c526850b168a57416cde0e',
            'number' => $phoneNumber,
            'message' => $message,
            'sendername' => 'Internflo'
        );
        
        curl_setopt_array($ch, array(
            CURLOPT_URL => 'https://api.semaphore.co/api/v4/priority',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($parameters),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded'
            )
        ));
        
        $response = curl_exec($ch);
        
        if ($response === false) {
            error_log("SMS sending failed: " . curl_error($ch));
        } else {
            error_log("SMS API Response: " . $response);
        }
        
        curl_close($ch);
        
        return json_decode($response, true);
    }

    // Handle POST requests for OTP verification and resending
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        header('Content-Type: application/json');
        
        switch ($_POST['action']) {
            case 'verify_otp':
                if (!isset($_POST['otp']) || !isset($_SESSION['temp_verified_user'])) {
                    echo json_encode(['success' => false, 'message' => 'Invalid request']);
                    exit;
                }
                
                $userOTP = $_POST['otp'];
                $user = $_SESSION['temp_verified_user'];
                
                try {
                    $conn->begin_transaction();
                    
                    // Verify against database OTP
                    $stmt = $conn->prepare("SELECT otp, otp_timestamp, email_verified FROM unverified_users WHERE email = ?");
                    $stmt->bind_param("s", $user['email']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $dbData = $result->fetch_assoc();
                    
                    error_log("Verifying OTP for user: " . $user['email'] . ", Submitted OTP: " . $userOTP . ", Stored OTP: " . ($dbData ? $dbData['otp'] : 'no data found'));
                    
                    if (!$dbData) {
                        error_log("No database record found for email: " . $user['email']);
                        echo json_encode(['success' => false, 'message' => 'User data not found']);
                        $conn->rollback();
                        exit;
                    }
                    
                    if ($dbData['otp'] !== $userOTP) {
                        error_log("OTP mismatch: expected " . $dbData['otp'] . ", got " . $userOTP);
                        echo json_encode(['success' => false, 'message' => 'Invalid OTP']);
                        $conn->rollback();
                        exit;
                    }
                    
                    if (time() - $dbData['otp_timestamp'] > 300) {
                        error_log("OTP expired: timestamp " . $dbData['otp_timestamp'] . ", current time " . time());
                        echo json_encode(['success' => false, 'message' => 'OTP has expired']);
                        $conn->rollback();
                        exit;
                    }
                    
                    // Ensure email is verified before proceeding
                    if ($dbData['email_verified'] != 1) {
                        error_log("Email not verified for user: " . $user['email']);
                        echo json_encode(['success' => false, 'message' => 'Email not verified']);
                        $conn->rollback();
                        exit;
                    }
                    
                    // Get complete user data
                    $userData = $conn->prepare("
                        SELECT * FROM unverified_users 
                        WHERE email = ? 
                        ORDER BY id DESC 
                        LIMIT 1
                    ");
                    $userData->bind_param("s", $user['email']);
                    $userData->execute();
                    $userResult = $userData->get_result();
                    $completeUserData = $userResult->fetch_assoc();
                    
                    if (!$completeUserData) {
                        error_log("Complete user data not found for email: " . $user['email']);
                        echo json_encode(['success' => false, 'message' => 'User data not found']);
                        $conn->rollback();
                        exit;
                    }
                    
                    // Insert into students table
                    $insert_stmt = $conn->prepare(
                        "INSERT INTO students (
                            first_name, middle_name, last_name, suffix, 
                            student_id, course, school_year, email, 
                            mobile_number, city, region, postal_code, 
                            barangay, home_address, password, student_id_file, registration_form_file, 
                            practicum_coordinator, creation_method, email_verified
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)"
                    );
                    
                    if (!$insert_stmt) {
                        error_log("Prepare statement failed: " . $conn->error);
                        echo json_encode(['success' => false, 'message' => 'Database error']);
                        $conn->rollback();
                        exit;
                    }
                    
                    $insert_stmt->bind_param(
                        "sssssssssssssssssss",
                        $completeUserData['first_name'],
                        $completeUserData['middle_name'],
                        $completeUserData['last_name'],
                        $completeUserData['suffix'],
                        $completeUserData['student_id'],
                        $completeUserData['course'],
                        $completeUserData['school_year'],
                        $completeUserData['email'],
                        $completeUserData['mobile_number'],
                        $completeUserData['city'],
                        $completeUserData['region'],
                        $completeUserData['postal_code'],
                        $completeUserData['barangay'],
                        $completeUserData['home_address'],
                        $completeUserData['password'],
                        $completeUserData['student_id_file'],
                        $completeUserData['registration_form_file'],
                        $completeUserData['practicum_coordinator'],
                        $completeUserData['creation_method']
                    );
                    
                    if (!$insert_stmt->execute()) {
                        error_log("Failed to insert into students table: " . $insert_stmt->error);
                        echo json_encode(['success' => false, 'message' => 'Failed to create student account']);
                        $conn->rollback();
                        exit;
                    }
                    
                    // Delete ALL records with this email from unverified_users
                    $delete_stmt = $conn->prepare("DELETE FROM unverified_users WHERE email = ?");
                    $delete_stmt->bind_param("s", $user['email']);
                    
                    if (!$delete_stmt->execute()) {
                        error_log("Failed to delete from unverified_users: " . $delete_stmt->error);
                        echo json_encode(['success' => false, 'message' => 'Cleanup error']);
                        $conn->rollback();
                        exit;
                    }
                    
                    // Set the session flag
                    $_SESSION['phone_verified'] = true;
                    
                    // Commit transaction
                    $conn->commit();
                    
                    // Set redirect URL
                    $redirectUrl = 'verify_otp.php';
                    
                    error_log("Verification successful for user: " . $user['email'] . ". Redirecting to: " . $redirectUrl);
                    echo json_encode(['success' => true, 'redirect' => $redirectUrl]);
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    error_log("Verification Error: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'An error occurred during verification: ' . $e->getMessage()]);
                }
                exit;

            case 'resend_otp':
                try {
                    if (!isset($_SESSION['temp_verified_user'])) {
                        throw new Exception("User information not found");
                    }

                    $user = $_SESSION['temp_verified_user'];
                    $otp = generateOTP();
                    error_log("Resending OTP: " . $otp . " for user: " . $user['email']);
                    
                    if (!updateOTPInDatabase($user['email'], $otp)) {
                        throw new Exception("Failed to update OTP in database during resend");
                    }

                    // Verify OTP was stored correctly
                    $verify_stmt = $conn->prepare("SELECT otp FROM unverified_users WHERE email = ?");
                    $verify_stmt->bind_param("s", $user['email']);
                    $verify_stmt->execute();
                    $result = $verify_stmt->get_result();
                    $stored_data = $result->fetch_assoc();
                    
                    if (!$stored_data || $stored_data['otp'] !== $otp) {
                        throw new Exception("OTP verification failed during resend");
                    }

                    $message = "Your OTP verification code is: $otp. Valid for 5 minutes.";
                    $sms_response = sendSMS($user['mobile_number'], $message);
                    
                    if (!$sms_response) {
                        throw new Exception("Failed to send SMS");
                    }

                    echo json_encode(['success' => true]);
                } catch (Exception $e) {
                    error_log("Resend OTP Error: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'Failed to resend OTP']);
                }
                exit;
        }
    }

    if (isset($_SESSION['temp_verified_user'])) {
        $user = $_SESSION['temp_verified_user'];
        
        // Verify email status in database
        $stmt = $conn->prepare("SELECT email_verified FROM unverified_users WHERE email = ?");
        $stmt->bind_param("s", $user['email']);
        $stmt->execute();
        $result = $stmt->get_result();
        $verification_status = $result->fetch_assoc();
        $stmt->close();
        
        if ($verification_status && $verification_status['email_verified'] == 1) {
            try {
                $phoneNumber = $user['mobile_number'];
                
                // Generate and store OTP
                $otp = generateOTP();
                error_log("Generated OTP: " . $otp . " for user: " . $user['email']);
                
                // Update OTP in database with verification
                if (!updateOTPInDatabase($user['email'], $otp)) {
                    throw new Exception("Failed to store OTP in database for user: " . $user['email']);
                }
    
                // Verify OTP was stored correctly
                $verify_stmt = $conn->prepare("SELECT otp FROM unverified_users WHERE email = ?");
                $verify_stmt->bind_param("s", $user['email']);
                $verify_stmt->execute();
                $result = $verify_stmt->get_result();
                $stored_data = $result->fetch_assoc();
                
                if (!$stored_data || $stored_data['otp'] !== $otp) {
                    throw new Exception("OTP verification failed - stored OTP doesn't match generated OTP");
                }
    
                $message = "Your OTP verification code is: $otp. Valid for 5 minutes.";
                $sms_response = sendSMS($phoneNumber, $message);
                
                if (!$sms_response) {
                    error_log("SMS sending failed for phone number: " . $phoneNumber);
                }
                
            
            // Display the HTML
?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="icon" href="pics/ucclogo2.png">
            <title>UCC - Internflo Verification</title>
            <link rel="stylesheet" href="verify_emails.css">
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <style>
                @import url("https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;800&display=swap");
                body {
                    font-family: "Open Sans", Arial, sans-serif;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    margin: 0;
                    padding: 0;
                    background-color: #f5f5f5;
                }

                fieldset {
                    border: none;
                    background: white;
                    padding: 40px;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                    width: 100%;
                    max-width: 500px;
                }

                h1 {
                    text-align: center;
                    font-size: 24px;
                    color: #449e25;
                    font-weight: 600;
                    margin-bottom: 1rem;
                    font-family: "Open Sans", Arial, sans-serif;
                }

                p {
                    text-align: center;
                    color: #666;
                    margin-bottom: 1.5rem;
                    font-size: 14px;
                }

                .otp-input-wrapper {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 1rem;
                }

                .otp-field {
                    width: 60px;
                    height: 60px;
                    font-size: 20px;
                    text-align: center;
                    border: 2px solid #8b94a7;
                    border-radius: 5px;
                    transition: border-color 0.3s, box-shadow 0.3s;
                }

                .otp-field:focus {
                    border-color: blue;
                    outline: none;
                    box-shadow: 0 0 0 0.3rem rgba(0, 123, 255, 0.25);
                }

                .otp-field-error {
                    border-color: red;
                    box-shadow: 0 0 0 0.3em rgba(255, 0, 0, 0.25);
                }

                .otp-field-success {
                    border-color: green;
                }

                .verify-btn {
                    margin: 20px 0 0 0; /* Match button 2's margin */
                    position: relative; /* Required for the hover effect */
                    display: flex; /* Align items like button 2 */
                    align-items: center; /* Center content vertically */
                    gap: 8px; /* Optional: If you plan to add icons or additional content */
                    justify-content: center; /* Center content horizontally */
                    padding: 15px; /* Match button 2's padding */
                    width: 100%; /* Keep full width */
                    font-size: 16px; /* Match button 2's font size */
                    background-color: #449e29; /* Match button 2's background */
                    color: white; /* Keep text color white */
                    border: none; /* No border */
                    border-radius: 4px; /* Rounded corners */
                    cursor: pointer; /* Pointer cursor */
                    transition: color 0.3s ease; /* Smooth transition for text color */
                    overflow: hidden; /* For the hover effect */
                    font-family: "Open Sans", Arial, sans-serif; /* Keep font consistent */
                    font-weight: 600; /* Bold text */
                    z-index: 1; /* Required for layering */
                    margin: 0 0 10px 0;
                }

                .verify-btn::before {
                    content: ""; /* Create the hover effect background */
                    position: absolute; /* Positioned relative to the button */
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: #4aa625; /* Hover background color */
                    transform: rotateX(0deg); /* Initial state */
                    transform-origin: top; /* Start rotation from the top */
                    transition: transform 0.5s ease; /* Smooth transition */
                    z-index: -1; /* Send the background behind the button text */
                }

                .verify-btn:hover::before {
                    transform: rotateX(90deg); /* Rotate effect */
                }

                .verify-btn:hover {
                    color: white; /* Ensure text stays white on hover */
                }


                .resend-link {
                    position: relative; /* Required for alignment consistency */
                    display: flex; /* Flexbox for alignment */
                    align-items: center; /* Center content vertically */
                    gap: 8px; /* Optional for adding icons or extra content */
                    justify-content: center; /* Center content horizontally */
                    width: 100%; /* Keep full width */
                    padding: 0.8rem; /* Keep original padding */
                    font-size: 1rem; /* Keep original font size */
                    font-weight: 600; /* Keep bold text */
                    font-family: "Open Sans", Arial, sans-serif; /* Consistent font */
                    background-color: #fff; /* Keep original background */
                    color: #4aa625; /* Keep original text color */
                    border: 1px solid #4aa625; /* Keep original border */
                    border-radius: 4px; /* Keep rounded corners */
                    cursor: pointer; /* Pointer cursor */
                    overflow: hidden; /* For future enhancements */
                    transition: background-color 0.3s ease; /* Smooth transition for hover */
                }

                .resend-link:hover {
                    background-color: #f8f8f8; /* Keep original hover background */
                }


                .error-message, .success-message {
                    text-align: center;
                    margin-bottom: 1rem;
                    padding: 0.5rem;
                    border-radius: 4px;
                }

                .error-message {
                    background-color: #ffebee;
                    color: #c62828;
                }

                .success-message {
                    background-color: #e8f5e9;
                    color: #2e7d32;
                }
            </style>
            <script>

$(document).ready(function() {
    const otpFields = $('.otp-field');
    
    // Handle input in OTP fields
    otpFields.each(function(index) {
        $(this).on('input', function(e) {
            // Allow only numbers
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Move to next input field if value is entered
            if (this.value.length === 1 && index < otpFields.length - 1) {
                otpFields[index + 1].focus();
            }
        });

        // Handle backspace
        $(this).on('keydown', function(e) {
            if (e.key === 'Backspace' && !this.value && index > 0) {
                otpFields[index - 1].focus();
            }
        });
    });

    // Form submission
    $('#otpForm').on('submit', function(e) {
        e.preventDefault();
        
        // Combine all OTP values
        const otp = Array.from(otpFields)
            .map(field => field.value)
            .join('');
        
        if (otp.length !== 6) {
            $('.error-message').text('Please enter a complete OTP').show();
            setTimeout(() => {
                $('.error-message').fadeOut();
            }, 3000);
            return;
        }

        // Show loading indicator or disable button here if desired
        $('.verify-btn').prop('disabled', true);
        
        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: {
                action: 'verify_otp',
                otp: otp
            },
            dataType: 'json',
// Replace your current AJAX success callback with this:
success: function(response) {
    console.log("Server response:", response); // Log the full response
    
    if (response.success) {
        $('.success-message').text('Verification successful! Redirecting...').show();
        setTimeout(function() {
            window.location.href = response.redirect;
        }, 1000);
    } else {
        $('.error-message').text(response.message || 'Verification failed').show();
        // Clear fields on error
        otpFields.val('');
        otpFields.first().focus();
        setTimeout(() => {
            $('.error-message').fadeOut();
        }, 3000);
    }
},
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
                console.log("Response:", xhr.responseText);
                $('.error-message').text('Error processing request. Please try again.').show();
                setTimeout(() => {
                    $('.error-message').fadeOut();
                }, 3000);
            },
            complete: function() {
                $('.verify-btn').prop('disabled', false);
            }
        });
    });

    $('#resendOTP').on('click', function() {
        $(this).prop('disabled', true);
        
        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: {
                action: 'resend_otp'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('.success-message').text('New OTP sent successfully').show();
                    // Clear all OTP fields
                    otpFields.val('');
                    otpFields.first().focus();
                } else {
                    $('.error-message').text(response.message || 'Failed to send new OTP').show();
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
                console.log("Response:", xhr.responseText);
                $('.error-message').text('Error resending OTP. Please try again.').show();
            },
            complete: function() {
                setTimeout(() => {
                    $('.success-message, .error-message').fadeOut();
                    $('#resendOTP').prop('disabled', false);
                }, 3000);
            }
        });
    });

    // Optional: Handle paste event
    $('.otp-input-wrapper').on('paste', function(e) {
        e.preventDefault();
        const pastedData = (e.originalEvent.clipboardData || window.clipboardData)
            .getData('text')
            .slice(0, 6)
            .replace(/[^0-9]/g, '');
        
        if (pastedData.length === 6) {
            otpFields.each(function(index) {
                $(this).val(pastedData[index] || '');
            });
            otpFields.last().focus();
        }
    });
});
            </script>
        </head>
        <body>
            <fieldset>
                <h1>MOBILE NUMBER VERIFICATION</h1>
                <p>Verification code sent to phone ending in <?php echo substr($phoneNumber, -4); ?></p>
                <div class="error-message" style="display: none;"></div>
                <div class="success-message" style="display: none;"></div>
                <form id="otpForm">
                    <div class="otp-input-wrapper">
                        <input type="text" class="otp-field" maxlength="1" data-index="1">
                        <input type="text" class="otp-field" maxlength="1" data-index="2">
                        <input type="text" class="otp-field" maxlength="1" data-index="3">
                        <input type="text" class="otp-field" maxlength="1" data-index="4">
                        <input type="text" class="otp-field" maxlength="1" data-index="5">
                        <input type="text" class="otp-field" maxlength="1" data-index="6">
                        <input type="hidden" id="otpValue" name="otp" required>
                    </div>
                    <button type="submit" class="verify-btn">Verify OTP <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M472-120q-42-1-102.5-9.5t-116-27.5Q198-176 159-206.5T120-280v-100q0 36 28.5 63.5t71.5 46q43 18.5 94.5 30T411-224q9 29 24.5 55.5T472-120Zm-71-205q-45-5-94.5-16.5t-91-30.5Q174-391 147-417.5T120-480v-100q0 38 31.5 66t78 47.5q46.5 19.5 101 30T430-422q-12 22-19.5 46.5T401-325Zm79-195q-149 0-254.5-47T120-680q0-66 105.5-113T480-840q150 0 255 47t105 113q0 66-105 113t-255 47Zm180 400q-75 0-127.5-52.5T480-300q0-75 52.5-127.5T660-480q75 0 127.5 52.5T840-300q0 26-7.5 50T812-204l80 80q11 11 11 28t-11 28q-11 11-28 11t-28-11l-80-80q-22 13-46 20.5t-50 7.5Zm0-80q42 0 71-29t29-71q0-42-29-71t-71-29q-42 0-71 29t-29 71q0 42 29 71t71 29Z"/></svg></button>
                </form>
                <button class="resend-link" id="resendOTP">Resend OTP <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#4aa625"><path d="M727-280H600q-17 0-28.5-11.5T560-320q0-17 11.5-28.5T600-360h127l-36-36q-11-11-11-27.5t12-28.5q11-11 28-11t28 11l104 104q5 5 8 12.5t3 15.5q0 8-3 15.5t-8 12.5L748-188q-11 11-27.5 11.5T692-188q-11-11-11-28t11-28l35-36ZM416-520l264-154v-86h-10L416-613 169-760h-9v88l256 152ZM155-280q-31 0-53-22t-22-53v-410q0-31 22-53t53-22h530q31 0 53 22t22 53v173q0 14-11 24t-25 9q-49-2-93.5 16T551-490q-35 35-53.5 80T481-316q1 14-9 25t-24 11H155Z"/></svg></button>
            </fieldset>
        </body>
        </html>
        <?php
        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
            echo "Error sending OTP. Please contact support.";
        }
    } else {
        header("Location: login.php");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>
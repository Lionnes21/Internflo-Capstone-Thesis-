
<?php
session_start();
require 'config.php';

// Helper Functions
function generateOTP() {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function updateOTPInDatabase($email, $otp, $pdo) {
    try {
        $stmt = $pdo->prepare("UPDATE unverified_recruiters SET otp = ?, otp_timestamp = ? WHERE email = ?");
        $timestamp = time();
        return $stmt->execute([$otp, $timestamp, $email]);
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
        'apikey' => 'c3c8e83cf2c526850b168a57416cde0e', // Replace with your actual API key
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
    curl_close($ch);
    return json_decode($response, true);
}

// Handle POST requests for OTP verification and resending
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $pdo = new PDO("mysql:host=localhost;dbname=u798912504_internflo", "u798912504_root", "Internfloucc2025*");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'verify_otp':
                if (!isset($_POST['otp']) || !isset($_SESSION['temp_verified_recruiter'])) {
                    echo json_encode(['success' => false, 'message' => 'Invalid request']);
                    exit;
                }
            
                $userOTP = $_POST['otp'];
                $user = $_SESSION['temp_verified_recruiter'];
            
                try {
                    $pdo->beginTransaction();
            
                    // Verify OTP against the latest record for this email
                    $stmt = $pdo->prepare("
                        SELECT id, otp, otp_timestamp 
                        FROM unverified_recruiters 
                        WHERE email = ? 
                        ORDER BY created_at DESC 
                        LIMIT 1
                    ");
                    $stmt->execute([$user['email']]);
                    $dbData = $stmt->fetch(PDO::FETCH_ASSOC);
            
                    if (!$dbData || $dbData['otp'] !== $userOTP) {
                        echo json_encode(['success' => false, 'message' => 'Invalid OTP']);
                        $pdo->rollback();
                        exit;
                    }
            
                    if (time() - $dbData['otp_timestamp'] > 300) { // 5 minutes expiry
                        echo json_encode(['success' => false, 'message' => 'OTP has expired']);
                        $pdo->rollback();
                        exit;
                    }
            
                    // Get the latest record data for this email
                    $latest_record_stmt = $pdo->prepare("
                        SELECT * 
                        FROM unverified_recruiters 
                        WHERE id = ?
                    ");
                    $latest_record_stmt->execute([$dbData['id']]);
                    $latestUser = $latest_record_stmt->fetch(PDO::FETCH_ASSOC);
            
                    if (!$latestUser) {
                        echo json_encode(['success' => false, 'message' => 'User record not found']);
                        $pdo->rollback();
                        exit;
                    }
            
                    // Move data to recruiters table
                    $insert_stmt = $pdo->prepare(
                        "INSERT INTO recruiters (
                            first_name, middle_name, last_name, suffix, 
                            company_name, industry, company_phone, company_email,
                            company_overview, company_address, 
                            latitude, longitude, company_logo, 
                            certificate_of_registration, bir_registration, business_permit,
                            email, password, mobile_number, email_verified, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );
            
                    $created_at = date('Y-m-d H:i:s');
                    $insert_stmt->execute([
                        $latestUser['first_name'],
                        $latestUser['middle_name'],
                        $latestUser['last_name'],
                        $latestUser['suffix'],
                        $latestUser['company_name'],
                        $latestUser['industry'],
                        $latestUser['company_phone'],
                        $latestUser['company_email'],
                        $latestUser['company_overview'],
                        $latestUser['company_address'],
                        $latestUser['latitude'],
                        $latestUser['longitude'],
                        $latestUser['company_logo'],
                        $latestUser['certificate_of_registration'],
                        $latestUser['bir_registration'],
                        $latestUser['business_permit'],
                        $latestUser['email'],
                        $latestUser['password'],
                        $latestUser['mobile_number'],
                        $latestUser['email_verified'], // Add this line
                        $created_at
                    ]);
            
                    // Delete the verified record from unverified_recruiters
                    $delete_stmt = $pdo->prepare("DELETE FROM unverified_recruiters WHERE id = ?");
                    $delete_stmt->execute([$dbData['id']]);
            
                    $pdo->commit();
                    $_SESSION['phone_verified'] = true;
                    echo json_encode(['success' => true, 'redirect' => 'companysignin.php']);
            
                } catch (Exception $e) {
                    $pdo->rollback();
                    error_log("Verification Error: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'An error occurred during verification: ' . $e->getMessage()]);
                }
                break;

            case 'resend_otp':
                try {
                    if (!isset($_SESSION['temp_verified_recruiter'])) {
                        throw new Exception("User information not found");
                    }

                    $user = $_SESSION['temp_verified_recruiter'];
                    $otp = generateOTP();
                    
                    if (!updateOTPInDatabase($user['email'], $otp, $pdo)) {
                        throw new Exception("Failed to update OTP in database");
                    }

                    $message = "Your OTP verification code is: $otp. Valid for 5 minutes.";
                    $sms_response = sendSMS($user['mobile_number'], $message);

                    if (!$sms_response) {
                        throw new Exception("Failed to send SMS");
                    }

                    echo json_encode(['success' => true]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => 'Failed to resend OTP']);
                }
                break;
        }
        exit;
    }
}

// Main page load
if (isset($_SESSION['email_verified']) && $_SESSION['email_verified'] === true) {
    try {
        $user = $_SESSION['temp_verified_recruiter'];
        $phoneNumber = $user['mobile_number'];
        $otp = generateOTP();
        
        $pdo = new PDO("mysql:host=localhost;dbname=u798912504_internflo", "u798912504_root", "Internfloucc2025*");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if (!updateOTPInDatabase($user['email'], $otp, $pdo)) {
            throw new Exception("Failed to store OTP in database");
        }

        $message = "Your OTP verification code is: $otp. Valid for 5 minutes.";
        sendSMS($phoneNumber, $message);
        
        // Display the HTML form
?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>OTP Verification</title>
            <link rel="icon" href="pics/ucc.png">
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

        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: {
                action: 'verify_otp',
                otp: otp
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = 'verify_otp.php'; // Changed from response.redirect
                } else {
                    $('.error-message').text(response.message).show();
                    // Clear fields on error
                    otpFields.val('');
                    otpFields.first().focus();
                    setTimeout(() => {
                        $('.error-message').fadeOut();
                    }, 3000);
                }
            },
            error: function() {
                $('.error-message').text('Error processing request').show();
                setTimeout(() => {
                    $('.error-message').fadeOut();
                }, 3000);
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
            success: function(response) {
                if (response.success) {
                    $('.success-message').text('New OTP sent successfully').show();
                    // Clear all OTP fields
                    otpFields.val('');
                    otpFields.first().focus();
                } else {
                    $('.error-message').text('Failed to send new OTP').show();
                }
                
                setTimeout(() => {
                    $('.success-message, .error-message').fadeOut();
                    $('#resendOTP').prop('disabled', false);
                }, 3000);
            },
            error: function() {
                $('.error-message').text('Error resending OTP').show();
                setTimeout(() => {
                    $('.error-message').fadeOut();
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
                <h1>Phone Verification</h1>
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
                <button class="resend-link" id="resendOTP">Resend OTP</button>
            </fieldset>
        </body>
        </html>
        <?php
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        echo "Error sending OTP. Please contact support.";
    }
} else {
    header("Location: companysignin.php");
    exit();
}
?>
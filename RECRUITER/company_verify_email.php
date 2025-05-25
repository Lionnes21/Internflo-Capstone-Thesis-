<?php
session_start();
require 'config.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Initialize the PDO connection
    $pdo = new PDO("mysql:host=localhost;dbname=u798912504_internflo", "u798912504_root", "Internfloucc2025*");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if the token exists in the database
    $stmt = $pdo->prepare("SELECT * FROM unverified_recruiters WHERE verification_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Update the email_verified column to 1
        $updateStmt = $pdo->prepare("UPDATE unverified_recruiters SET email_verified = 1 WHERE verification_token = ?");
        $updateResult = $updateStmt->execute([$token]);
        
        if ($updateResult) {
            // Store user data in session for OTP verification
            $_SESSION['temp_verified_recruiter'] = $user;
            $_SESSION['email_verified'] = true;

            // Success message with redirect to OTP verification
            echo '<!DOCTYPE html>
                  <html lang="en">
                  <head>
                      <meta charset="UTF-8">
                      <meta name="viewport" content="width=device-width, initial-scale=1.0">
                      <title>Email Verified</title>
                      <link rel="stylesheet" href="../STUDENT/verify_emails.css">
                      <link rel="icon" href="pics/ucc.png">
                    <script>
                        window.onload = function() {
                            // Close this window after showing verification success
                            setTimeout(function() {
                                window.close();
                            }, 3000);
                        };
                    </script>
                  </head>
                  <body>
                      <fieldset>
                          <div class="svg-container">
                              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icon-tabler-circle-dashed-check">
                                  <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                  <path d="M8.56 3.69a9 9 0 0 0 -2.92 1.95" />
                                  <path d="M3.69 8.56a9 9 0 0 0 -.69 3.44" />
                                  <path d="M3.69 15.44a9 9 0 0 0 1.95 2.92" />
                                  <path d="M8.56 20.31a9 9 0 0 0 3.44 .69" />
                                  <path d="M15.44 20.31a9 9 0 0 0 2.92 -1.95" />
                                  <path d="M20.31 15.44a9 9 0 0 0 .69 -3.44" />
                                  <path d="M20.31 8.56a9 9 0 0 0 -1.95 -2.92" />
                                  <path d="M15.44 3.69a9 9 0 0 0 -3.44 -.69" />
                                  <path d="M9 12l2 2l4 -4" />
                              </svg>
                          </div>
                          <h1 class="gmail-success">YOUR EMAIL HAS BEEN VERIFIED!</h1>
                          <p class="gmail-msg-success">You will be redirected to SMS Verification shortly...</p>
                      </fieldset>
                  </body>
                  </html>';
        } else {
            // Error updating verification status
            echo '<!DOCTYPE html>
                  <html lang="en">
                  <head>
                      <meta charset="UTF-8">
                      <meta name="viewport" content="width=device-width, initial-scale=1.0">
                      <title>Verification Failed</title>
                      <link rel="icon" href="pics/ucc.png">
                      <link rel="stylesheet" href="verify_emails.css">
                  </head>
                  <body>
                      <fieldset>
                          <div class="svg-container-error">
                              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icon-tabler-x">
                                  <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                  <path d="M18 6l-12 12" />
                                  <path d="M6 6l12 12" />
                              </svg>
                          </div>
                          <h1 class="gmail-h1">VERIFICATION FAILED!</h1>
                          <p class="gmail-error">There was an error processing your verification. Please try again or contact support.</p>
                      </fieldset>
                  </body>
                  </html>';
        }
    } else {
        // Invalid token message...
        echo '<!DOCTYPE html>
              <html lang="en">
              <head>
                  <meta charset="UTF-8">
                  <link rel="icon" href="pics/ucc.png">
                  <meta name="viewport" content="width=device-width, initial-scale=1.0">
                  <title>Verification Failed</title>
                  <link rel="stylesheet" href="verify_emails.css">
              </head>
              <body>
                  <fieldset>
                      <div class="svg-container-error">
                          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icon-tabler-x">
                              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                              <path d="M18 6l-12 12" />
                              <path d="M6 6l12 12" />
                          </svg>
                      </div>
                      <h1 class="gmail-h1">INVALID VERIFICATION LINK!</h1>
                      <p class="gmail-error">The verification link you used is not valid. Please check the link and try again.</p>
                  </fieldset>
              </body>
              </html>';
    }
} else {
    // No token provided message...
    echo '<!DOCTYPE html>
          <html lang="en">
          <head>
              <meta charset="UTF-8">
              <meta name="viewport" content="width=device-width, initial-scale=1.0">
              <title>No Token Provided</title>
              <link rel="icon" href="pics/ucc.png">
              <link rel="stylesheet" href="verify_emails.css">
          </head>
          <body>
              <fieldset>
                  <div class="svg-container-error">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icon-tabler-x">
                          <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                          <path d="M18 6l-12 12" />
                          <path d="M6 6l12 12" />
                      </svg>
                  </div>
                  <h1 class="gmail-h1">NO TOKEN PROVIDED!</h1>
                  <p class="gmail-error">No verification token was provided in the request. Please check the link and try again.</p>
              </fieldset>
          </body>
          </html>';
}
?>
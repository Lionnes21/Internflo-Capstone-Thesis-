<?php
session_start();
require 'config.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Check if the token exists in the database
    $stmt = $conn->prepare("SELECT * FROM unverified_users WHERE verification_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user) {
        // Update the database to mark email as verified
        $update_stmt = $conn->prepare("UPDATE unverified_users SET email_verified = 1 WHERE verification_token = ?");
        $update_stmt->bind_param("s", $token);
        $update_stmt->execute();
        $update_stmt->close();
        
        // Store user in session for the next step
        $_SESSION['temp_verified_user'] = $user;

        echo '<!DOCTYPE html>
              <html lang="en">
              <head>
                  <meta charset="UTF-8">
                  <meta name="viewport" content="width=device-width, initial-scale=1.0">
                  <link rel="icon" href="pics/ucclogo2.png">
                  <title>UCC - Internflo Verification</title>
                  <link rel="stylesheet" href="verify_emails.css">
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
                      <p class="gmail-msg-success">You can now return to the registration page to continue.</p>
                  </fieldset>
              </body>
              </html>';
    } else {
        // Invalid token message
        echo '<!DOCTYPE html>
              <html lang="en">
              <head>
                  <meta charset="UTF-8">
                  <meta name="viewport" content="width=device-width, initial-scale=1.0">
                  <link rel="icon" href="pics/ucclogo2.png">
                  <title>UCC - Internflo Verification</title>
                  <link rel="stylesheet" href="verify_emails.css">
              </head>
              <body>
                  <fieldset>
                      <h1 class="gmail-h1">INVALID VERIFICATION LINK!</h1>
                      <p class="gmail-error">The verification link is not valid. Please check and try again.</p>
                  </fieldset>
              </body>
              </html>';
    }
} else {
    echo "No token provided.";
}
?>
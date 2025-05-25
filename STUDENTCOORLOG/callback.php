<?php
// Include the necessary files and start the session
require_once 'vendor/autoload.php';
include 'config.php'; // Include your database connection
session_start();

// Create a new Google client instance
$client = new Google_Client();
$client->setClientId('41598131166-2ra5ia34n04fk054m433hfchim94tej0.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('https://internflo-ucc.com/STUDENTCOORLOG/callback.php');
$client->addScope('email');
$client->addScope('profile');

// Check if the authorization code exists
if (isset($_GET['code'])) {
    try {
        // Authenticate the user with the received code
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        $client->setAccessToken($token);

        // Get user profile information from Google
        $google_oauth = new Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();
        $email = $google_account_info->email;
        $name = $google_account_info->name;
        $profile_picture = $google_account_info->picture;  // Get profile picture URL

        // Check if the user already exists in the database
        $stmt = $conn->prepare('SELECT * FROM students WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            // User already exists, log them in
            $_SESSION['user_id'] = $user['id']; // Use primary key id for session
        
            // Check if the user was created manually via Google
            $loginMethod = ($user['creation_method'] === 'manual') ? 'email' : 'google';
        
            // Update login method
            $updateStmt = $conn->prepare('UPDATE students SET login_method = ? WHERE id = ?');
            $updateStmt->bind_param('si', $loginMethod, $user['id']);
            $updateStmt->execute();
        
            header('Location: ../STUDENTLOGIN/studentfrontpage.php'); // Redirect to dashboard
            exit();
        }
        else {
            // User does not exist, insert their Gmail email, name, and profile picture
            $stmt = $conn->prepare('INSERT INTO students (email, name, profile_picture, login_method) VALUES (?, ?, ?, ?)');
            $loginMethod = 'google';
            $stmt->bind_param('ssss', $email, $name, $profile_picture, $loginMethod);

            // Execute the insert operation
            if ($stmt->execute()) {
                // Get the ID of the newly inserted user and log them in
                $_SESSION['user_id'] = $conn->insert_id; // Set the newly created user's ID in the session
                header('Location: ../STUDENTLOGIN/studentfrontpage.php'); // Redirect to dashboard
                exit();
            } else {
                // Show an error message if the insert fails
                throw new Exception('Failed to insert new user: ' . $stmt->error);
            }
        }
    } catch (Exception $e) {
        // Handle any exceptions and display an error message
        echo 'Google login failed: ' . $e->getMessage();
    }
} else {
    // Handle the case when no authorization code is provided
    echo 'Authorization code missing. Google login failed!';
}
?>

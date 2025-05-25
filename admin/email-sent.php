<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Sent</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="container">
    <h2>Email Sent</h2>
    <p class="success">
        <?php 
        if (isset($_SESSION['message'])) {
            echo $_SESSION['message'];
            unset($_SESSION['message']); // Clear message after displaying
        } else {
            echo 'Please check your email for the verification link.';
        }
        ?>
    </p>
    <a href="https://mail.google.com/">Click here</a>
</div>

</body>
</html>

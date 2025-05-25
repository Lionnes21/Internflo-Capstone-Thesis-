<?php
session_start();
require 'config.php';  // Database configuration

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $cpassword = $_POST['cpassword'];

    $errors = [];
    if(empty($fullname) || empty($username) || empty($email) || empty($password) || empty($cpassword)) {
        $errors[] = 'All fields are required.';
    }
    if($password !== $cpassword) {
        $errors[] = 'Passwords do not match.';
    }

    $query = "SELECT * FROM `admin` WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            if($row['username'] == $username) {
                $errors[] = 'Username already exists.';
            }
            if($row['email'] == $email) {
                $errors[] = 'Email already exists.';
            }
        }
    }

    if(empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        $sql = "INSERT INTO `admin` (fullname, username, email, password) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $fullname, $username, $email, $hashedPassword);
        if($stmt->execute()) {
            $_SESSION['message'] = 'Registration successful. You can now log in.';
            header('Location: signin.php');  // Redirect to login page
            exit;
        } else {
            $errors[] = 'Registration failed. Please try again later.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internflo - Administrator</title>
    <link rel="icon" href="ucc-logo1.png">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

<div class="container">
    <h2>Sign Up</h2>
    <?php
    if(!empty($errors)) {
        foreach($errors as $error) {
            echo '<p style="color: red;">' . $error . '</p>';
        }
    }
    ?>
    <form action="signup.php" method="post">
        <div class="form-group">
            <input id="signup-fullname" name="fullname" type="text" placeholder="Full Name" required>
        </div>
        <div class="form-group">
            <input id="signup-username" name="username" type="text" placeholder="Username" required>
        </div>
        <div class="form-group">
            <input id="signup-email" name="email" type="text" placeholder="Email" required>
        </div>
        <div class="form-group">
            <input id="signup-password" name="password" type="password" placeholder="Password" 
            pattern=".{8,}" title="Password must be at least 8 characters" required>
        </div>
        <div class="form-group">
            <input id="signup-cpassword" name="cpassword" type="password" placeholder="Confirm Password"
            pattern="^.{8,}$" title="Password must contain at least 8 characters." required>
        </div>
        <button type="submit" name="submit">Sign Up</button>
        <h3>Have an account? <a href="signin.php"> Login Now</a></h3>
    </form>
</div>

</body>
</html>
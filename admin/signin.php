<?php
session_start();
include 'config.php';

$errors = [];

if(isset($_POST['submit'])){
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    if(empty($username) || empty($password)){
        $errors[] = 'Username and password are required.';
    }

    if(empty($errors)){
        $query = "SELECT * FROM `admin` WHERE username = '$username' OR email = '$username'";
        $result = mysqli_query($conn, $query);

        if($result){
            if(mysqli_num_rows($result) == 1){
                $row = mysqli_fetch_assoc($result);
                $hashedPassword = $row['password'];

                if(password_verify($password, $hashedPassword)){
                    $_SESSION['username'] = $row['username']; // Set username in session
                    header('Location: admin_index.php'); // Redirect to dashboard or home page
                    exit;
                } else {
                    $errors[] = 'Incorrect password.';
                }
            } else {
                $errors[] = 'User does not exist.';
            }
        } else {
            $errors[] = 'Database query failed.';
        }
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <title>Internflo - Administrator</title>
    <link rel="icon" href="ucc-logo1.png">
</head>
<body>
    <br>
    <br>
    <br>
    <br>

<div class="container"> 
    <h2>Sign In</h2>
    <form action="signin.php" method="post">
        <div class="form-group">
            <input type="text" id="signin-username" name="username" placeholder="Username or Email" required>
        </div>
        <div class="form-group">
            <input type="password" id="signin-password" name="password" placeholder="Password" required>
        </div>
        <button type="submit" name="submit">Sign In</button>
        <h3>Don't have an account? <a href="signup.php"> Register Now</a></h3>
    </form>
    <?php if(!empty($errors)): ?>
        <div class="error-messages">
            <?php foreach($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
<?php
session_start();

// Database configuration
$servername = "localhost";
$username = "u798912504_root";
$password = "Internfloucc2025*"; // Update with your database password
$dbname = "u798912504_internflo";

try {
    // Create database connection using PDO
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Retrieve unmasked user input
        $student_id = trim($_POST['student_id']);
        $first_name = trim($_POST['first_name']);
        $middle_name = trim($_POST['middle_name']);
        $last_name = trim($_POST['last_name']);
        $suffix = trim($_POST['suffix']);
        $course = trim($_POST['course']);
        $school_year = trim($_POST['school_year']);
        $city = trim($_POST['city']);
        $region = trim($_POST['region']);
        $postal_code = trim($_POST['postal_code']);
        $barangay = trim($_POST['barangay']);
        $home_address = trim($_POST['home_address']);
        $mobile_number = trim($_POST['mobile_number']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        // Generate a unique verification token
        $verification_token = bin2hex(random_bytes(32));

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare SQL statement for unverified_users table
        $stmt = $pdo->prepare("INSERT INTO unverified_users (
            student_id, first_name, middle_name, last_name, suffix, course, school_year, 
            city, region, postal_code, barangay, home_address, 
            mobile_number, email, password, verification_token, creation_method
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )");

        // Execute the statement with unmasked form data
        $stmt->execute([
            $student_id,
            $first_name,
            $middle_name,
            $last_name,
            $suffix,
            $course,
            $school_year,
            $city,
            $region,
            $postal_code,
            $barangay,
            $home_address,
            $mobile_number,
            $email,
            $hashed_password,
            $verification_token,
            'manual'
        ]);

        // Store the verification token in session
        $_SESSION['verification_token'] = $verification_token;

        // Redirect to sendVerificationEmail.php
        header('Location: sendVerificationEmail.php');
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['registration_error'] = 'Database error: ' . $e->getMessage();
    header('Location: registration.php');
    exit();
} catch (Exception $e) {
    $_SESSION['registration_error'] = 'Error: ' . $e->getMessage();
    header('Location: registration.php');
    exit();
}
?>
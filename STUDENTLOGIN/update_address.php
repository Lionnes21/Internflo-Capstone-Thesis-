<?php
session_start();
include 'config.php';

if (isset($_SESSION['user_id']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_SESSION['user_id'];

    // Validate and sanitize input
    $city = htmlspecialchars($_POST['city']);
    $region = htmlspecialchars($_POST['region']);
    $postalCode = htmlspecialchars($_POST['postal_code']);
    $barangay = htmlspecialchars($_POST['barangay']);
    $address = htmlspecialchars($_POST['home_address']);

    // Prepare the SQL statement
    $sql = "UPDATE students SET 
            city = ?, 
            region = ?, 
            postal_code = ?, 
            barangay = ?, 
            home_address = ? 
            WHERE id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssssi', 
        $city, 
        $region, 
        $postalCode, 
        $barangay, 
        $address, 
        $userId
    );

    if ($stmt->execute()) {
        $_SESSION['message'] = "Address updated successfully";
    } else {
        $_SESSION['error'] = "Error updating address: " . $stmt->error;
    }

    header("Location: student-profile.php");
    exit();
} else {
    echo "You must be logged in to update your address.";
}
?>
<?php

$host = 'localhost';
$username = 'u798912504_root';
$password = 'Internfloucc2025*';
$database = 'u798912504_internflo';

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die('Connection failed: ' . mysqli_connect_error());
}

?>

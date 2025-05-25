<?php
include 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the counts from the same queries used in your dashboard
$data = array();

// Students count
$select_students = mysqli_query($conn, "SELECT * FROM `students`");
$students_count = mysqli_num_rows($select_students);

// Internships count
$select_internships = mysqli_query($conn, "SELECT * FROM `internshipad`");
$internships_count = mysqli_num_rows($select_internships);

// Companies count
$sql_companies = "SELECT COUNT(*) AS total FROM approvedrecruiters";
$result_companies = mysqli_query($conn, $sql_companies);
$row_companies = mysqli_fetch_assoc($result_companies);
$companies_count = $row_companies['total'];

// Advisors count
$select_advisors = mysqli_query($conn, "SELECT * FROM `m_advisor_assignments`");
$advisors_count = mysqli_num_rows($select_advisors);

// Create mock monthly data for the chart
// We'll use the current total for the current month and distribute the rest
$currentMonth = (int)date('n'); // 1-12 for current month

// Create 12 months of data for each category
$studentsData = array_fill(0, 12, 0);
$internshipsData = array_fill(0, 12, 0);
$companiesData = array_fill(0, 12, 0);
$advisorsData = array_fill(0, 12, 0);

// Set the current month to the exact counts from the dashboard
$studentsData[$currentMonth-1] = $students_count;
$internshipsData[$currentMonth-1] = $internships_count;
$companiesData[$currentMonth-1] = $companies_count;
$advisorsData[$currentMonth-1] = $advisors_count;

// For visualization purposes, distribute some values to previous months
for ($i = 0; $i < $currentMonth-1; $i++) {
    $studentsData[$i] = round($students_count * ($i + 1) / $currentMonth * 0.8);
    $internshipsData[$i] = round($internships_count * ($i + 1) / $currentMonth * 0.8);
    $companiesData[$i] = round($companies_count * ($i + 1) / $currentMonth * 0.8);
    $advisorsData[$i] = round($advisors_count * ($i + 1) / $currentMonth * 0.8);
}

// Package the data for the chart
$data = array(
    'Students' => $studentsData,
    'Internships' => $internshipsData,
    'Company' => $companiesData,
    'Advisors' => $advisorsData
);

// Set proper JSON content type
header('Content-Type: application/json');
echo json_encode($data);

$conn->close();
?>
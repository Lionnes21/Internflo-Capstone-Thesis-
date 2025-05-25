<?php
include 'config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $itineraryId = $_POST['id'];
    
    // Sanitize the input to prevent SQL injection
    $itineraryId = mysqli_real_escape_string($conn, $itineraryId);

    // Delete records from the itineraries_details table
    $sql = "DELETE FROM itineraries_details WHERE itinerary_id = '$itineraryId'";
    if (mysqli_query($conn, $sql)) {
        // If deletion from itineraries_details is successful, proceed to delete from the itineraries table
        $sql = "DELETE FROM itineraries WHERE id = '$itineraryId'";
        if (mysqli_query($conn, $sql)) {
            echo "Itinerary and its details deleted successfully";
        } else {
            echo "Error deleting itinerary: " . mysqli_error($conn);
        }
    } else {
        echo "Error deleting itinerary details: " . mysqli_error($conn);
    }
} else {
    echo "Invalid request";
}
?>

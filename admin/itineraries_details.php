<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Itinerary Details</title>
    <!-- Link to your CSS file -->
    <link rel="stylesheet" href="itineraries_details.css">
</head>
<body>
    <?php
    // get_itinerary_details.php

    include 'config.php';

    // Get the itinerary ID from the AJAX request
    $itineraryId = $_GET['id'];

    // Fetch itinerary details from the database
    $sql = "SELECT * FROM itineraries_details WHERE itinerary_id = '$itineraryId'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Start creating HTML content to display itinerary details
        echo "<table class='itinerary-table'><thead><tr><th>Time</th><th>Departure</th><th>Transportation</th><th>Cost</th></tr></thead><tbody>";
        while ($row = $result->fetch_assoc()) {
            // Append each row of details to the HTML content
            echo "<tr>";
            echo "<td class='time-cell'>" . $row["time"] . "</td>";
            echo "<td class='departure-cell'>" . $row["departure"] . "</td>";
            echo "<td class='transportation-cell'>" . $row["transportation"] . "</td>";
            echo "<td class='cost-cell'>" . $row["cost"] . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "No itinerary details available";
    }

    // Close the database connection
    $conn->close();
    ?>
</body>
</html>

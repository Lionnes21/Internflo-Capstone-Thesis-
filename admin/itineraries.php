<?php
include 'config.php';
session_start();

// Initialize search query variable
$search_query = "";

// Check if the search form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize the input to prevent SQL injection
    $search_query = mysqli_real_escape_string($conn, $_POST['search_query']);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel="stylesheet">
    <link rel="stylesheet" href="css/admin_style.css">
    <title>OnTheGo Admin</title>
	<style>
		.not-found {
			color: #FF0000;
			font-weight: bold;
			align-items: center;
		}
		.error {
			color: #FF0000;
			font-weight: bold;
			align-items: center;
		}
        .action-buttons {
    display: flex;
    gap: 5px; /* Adjust the gap between buttons as needed */
}

	</style>
    <script>
    function deleteItinerary(itineraryId) {
        if (confirm("Are you sure you want to delete this itinerary?")) {
            // AJAX call to delete the itinerary
            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    // Reload the page after deletion
                    location.reload();
                }
            };
            xhttp.open("POST", "delete_itinerary.php", true);
            xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhttp.send("id=" + itineraryId);
        }
    }
</script>

</head>
<body>
<section id="sidebar">
    <div class="logo">
        <img src="logo.png" alt="">
        <span class="nav-item">OnTheGo</span>
    </div>
    <ul class="side-menu">
        <li><a href="admin_index.php"><i class='bx bxs-dashboard icon'></i>Dashboard</a></li>
        <li><a href="accounts.php"><i class='bx bxs-user-detail icon'></i>Accounts</a></li>
		<li><a href="feedbacks.php"><i class='bx bxs-message-rounded-detail icon'></i></i>Feedbacks</a></li>
        <li>
            <a href="#"><i class='bx bxs-analyse icon'></i>Analytics<i class='bx bx-chevron-right icon-right'></i></a>
            <ul class="side-dropdown">
                <li><a href="destinations.php"><i class='bx bxs-map icon'></i>Destinations</a></li>
                <li><a href="itineraries.php"><i class='bx bx-run icon'></i>Itineraries</a></li>
            </ul>
        </li>
    </ul>
</section>

<section id="content">

<nav>
	<i class='bx bx-menu toggle-sidebar' ></i>
    <form id="searchForm" method="post" action="">
        <div class="form-group">
            <input type="text" name="search_query" id="search_query" placeholder="Search">
            <i class='bx bx-search icon' onclick="submitSearchForm()" style="cursor: pointer;"></i>
        </div>
    </form>

    <script>
        function submitSearchForm() {
            document.getElementById("searchForm").submit();
        }
    </script>


	<div class="profile">
		<img src="user.jpg" alt="">
		<ul class="profile-link">
			<p>Username: <span><?php echo $_SESSION['username']; ?></span></p>
			<li><a href="profile.php"><i class='bx bxs-user-circle icon'></i> Profile</a></li>
			<li><a href="logout.php"><i class='bx bxs-log-out-circle icon'></i> Logout</a></li>
		</ul>
	</div>
</nav>

<main>
    <h1 class="title">Itineraries</h1>
    <div class="data">
        <div class="content-data">
            <div class="head">
                <h3>OnTheGo Created Itineraries</h3>
            </div>
            <div class="table-data">
                <div class="order">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Destination</th>
                                <th>Trip Name</th>
                                <th>Budget</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $conn = mysqli_connect('localhost', 'root', '', 'onthego');
                            if ($conn->connect_error) {
                                die("Connection failed: " . $conn->connect_error);
                            }

                            $sql = "SELECT id, name, destination, trip_name, budget, date FROM itineraries";
                            if (!empty($search_query)) {
                                $sql .= " WHERE name LIKE '%$search_query%'";
                            }

                            $result = $conn->query($sql);

                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $row["name"] . "</td>";
                                    echo "<td>" . $row["destination"] . "</td>";
                                    echo "<td>" . $row["trip_name"] . "</td>";
                                    echo "<td>" . $row["budget"] . "</td>";
                                    echo "<td>" . $row["date"] . "</td>";
                                    echo "<td>
                                              <div class='action-buttons'>
                                                  <button onclick='viewFullTrip(" . $row["id"] . ")'>View Full Trip</button>
                                                  <button onclick='deleteItinerary(" . $row["id"] . ")'>Delete</button>
                                              </div>
                                          </td>"; // Added a container div for alignment
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6'>No data available</td></tr>";
                            }
                            $conn->close();
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>


</section>

<!-- Modal to display itinerary details -->
<div id="fullTripModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <div id="fullTripDetails"></div>
    </div>
</div>

<script src="js/script.js"></script>
<script>
    function viewFullTrip(itineraryId) {
        // AJAX call to fetch itinerary details
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                // Display itinerary details in modal
                document.getElementById("fullTripDetails").innerHTML = this.responseText;
                document.getElementById("fullTripModal").style.display = "block";
            }
        };
        xhttp.open("GET", "itineraries_details.php?id=" + itineraryId, true);
        xhttp.send();
    }

    function closeModal() {
        document.getElementById("fullTripModal").style.display = "none";
    }
</script>
</body>
</html>

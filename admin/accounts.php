<?php
include 'config.php';
session_start();
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
	</style>
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
    <h1 class="title">Accounts</h1>
    <div class="data">
        <div class="content-data">
            <div class="head">
                <h3>OnTheGo Accounts</h3>
            </div>
            <div class="table-data">
                <div class="order">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Phone</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                                $search = $_POST['search_query'];
                                $searchQuery = "SELECT *, CONCAT(fname, ' ', lname, ' ', suffix) AS name FROM accounts 
                                    WHERE id LIKE '%$search%' OR CONCAT(fname, ' ', lname, ' ', suffix) LIKE '%$search%'
									OR username LIKE '%$search%'";
                                $searchResult = $conn->query($searchQuery);
                                if ($searchResult) {
                                    if ($searchResult->num_rows > 0) {
                                        while ($searchRow = $searchResult->fetch_assoc()) {
                                            echo '<tr>
                                                <td>' . $searchRow['id'] . '</td>
                                                <td>' . $searchRow['name'] . '</td>
                                                <td>' . $searchRow['username'] . '</td>
                                                <td>' . $searchRow['email'] . '</td>
                                                <td>' . $searchRow['phone'] . '</td>
                                                </tr>';
                                        }
                                    } else {
                                        echo '<tr><td><p class="not-found">Data not found</p></td></tr>';
                                    }
                                } else {
                                    echo '<tr><td><p class="error">Error executing search query</p></td></tr>';
                                }
                            } else {
                                $qry = $conn->query("SELECT *, CONCAT(fname, ' ', lname, ' ', suffix) AS name FROM accounts");
                                while ($row = $qry->fetch_assoc()) :
                            ?>
                                <tr>
                                    <td><?php echo $i++ ?></td>
                                    <td><?php echo $row['name'] ?></td>
                                    <td><?php echo $row['username'] ?></td>
                                    <td><?php echo $row['email'] ?></td>
                                    <td><?php echo $row['phone'] ?></td>
                                </tr>
                            <?php 
                                endwhile;
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>
	</section>

	<script src="js/script.js"></script>
</body>
</html>

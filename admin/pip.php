<?php
include 'config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "UPDATE admin SET username=?, email=?, password=? WHERE id=1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $username, $email, $password);

    if ($stmt->execute()) {
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['password'] = $password;
        echo "<script>";
        echo "Swal.fire({";
        echo "  title: 'Success!',";
        echo "  text: 'Profile updated successfully',";
        echo "  icon: 'success',";
        echo "  confirmButtonText: 'OK'";
        echo "}).then((result) => {";
        echo "  if (result.isConfirmed) {";
        echo "    window.location.href = 'profile.php';";
        echo "  }";
        echo "});";
        echo "</script>";
    } else {
        echo "Error updating profile: " . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel="stylesheet">
    <link rel="stylesheet" href="css/admin_style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@10/dist/sweetalert2.min.css">
    <style>
        .swal2-popup {
            font-family: 'Open Sans', sans-serif;
            border-radius: 10px;
            background: #FFFFFF;
        }
        .swal2-title {
            color: #046C34;
        }
        .swal2-content {
            color: #046C34;
        }
        .swal2-confirm {
            background-color: #FFFFFF;
            color: #046C34;
        }
    </style>
    <title>OnTheGo Admin</title>
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
				<li><a href="transactions.php"><i class='bx bxs-plane-alt icon'></i>Transactions</a></li>
                <li><a href="itineraries.php"><i class='bx bx-run icon'></i>Itineraries</a></li>
            </ul>
        </li>
    </ul>
</section>

<section id="content">
    <nav>
        <i class='bx bx-menu toggle-sidebar' ></i>
        <form action="#">
            <div class="form-group">
                <input type="text" placeholder="Search">
                <i class='bx bx-search icon' ></i>
            </div>
        </form>
        <div class="profile">
            <img src="user.jpg" alt="">
            <ul class="profile-link">
                <p>Username: <span><!?php echo isset($_SESSION['username']) ? $_SESSION['username'] : ''; ?></span></p>
                <li><a href="profile.php"><i class='bx bxs-user-circle icon'></i> Profile</a></li>
                <li><a href="logout.php"><i class='bx bxs-log-out-circle icon'></i> Logout</a></li>
            </ul>
        </div>
    </nav>

    <main>
        <h1 class="title">Profile</h1>
        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3>OnTheGo Admin Profile</h3>
                </div>
                <form method="post" action="profile.php">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" value="<!?php echo isset($_SESSION['username']) ? $_SESSION['username'] : ''; ?>" required>

                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<!?php echo isset($_SESSION['email']) ? $_SESSION['email'] : ''; ?>" required>

                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>

                    <button type="submit">Update Profile</button>
                </form>
            </div>
        </div>
    </main>
</section>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<script src="js/script.js"></script>
</body>
</html>










<?php
include 'config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "UPDATE admin SET username=?, email=?, password=? WHERE id=1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $username, $email, $password);

    if ($stmt->execute()) {
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['password'] = $password;
        echo "<script>";
        echo "let toastBox = document.getElementById('toastBox');";
        echo "let successMsg = '<i class=\"bx bxs-check-circle\"></i> Successfully updated.';";
        echo "showToast(successMsg);";
        echo "</script>";
    } else {
        echo "<script>";
        echo "let toastBox = document.getElementById('toastBox');";
        echo "let errorMsg = '<i class=\"bx bxs-error-circle\"></i> Error in updating admin's profile.' . " . $conn->error . ";";
        echo "showToast(errorMsg);";
        echo "</script>";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel="stylesheet">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        #toastBox {
            position: absolute;
            bottom: 30px;
            right: 30px;
            display: flex;
            align-items: flex-end;
            flex-direction: column;
            overflow: hidden;
            padding: 20px; 
        }
        .toast {
            width: 400px;
            height: 80px;
            background: #046C34;
            font-weight: bold;
            margin: 15px 0;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            position: relative;
            transform: translateX(100%);
            animation: moveleft 0.5s linear forwards;
        }
        @keyframes moveleft{
            100%{
            transform: translateX(0);
            }
        }
        .toast i {
            margin: 0 20px;
            font-size: 35px;
            color: #008000;
        }
        .toast .error i {
            color: #FF0000;
        }
        .toast::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 100%;
            height: 5px;
            background: #008000;
            animation: anim 5s linear forwards;
        }
        @keyframes anim {
            100%{
                width: 0;
            }
        }
        .toast .error::after {
            background: #FF0000;
        }
    </style> 
    <title>OnTheGo Admin</title>
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
				<li><a href="transactions.php"><i class='bx bxs-plane-alt icon'></i>Transactions</a></li>
                <li><a href="itineraries.php"><i class='bx bx-run icon'></i>Itineraries</a></li>
            </ul>
        </li>
    </ul>
</section>

<section id="content">
    <nav>
        <i class='bx bx-menu toggle-sidebar' ></i>
        <form action="#">
            <div class="form-group">
                <input type="text" placeholder="Search">
                <i class='bx bx-search icon' ></i>
            </div>
        </form>
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
        <h1 class="title">Profile</h1>
        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3>OnTheGo Admin Profile</h3>
                </div>
                <form method="post" action="profile.php">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" value="<?php echo isset($_SESSION['username']) ? $_SESSION['username'] : ''; ?>" required>

                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo isset($_SESSION['email']) ? $_SESSION['email'] : ''; ?>" required>

                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>

                    <button type="submit" onclick="showToast(successMsg)">Update Profile</button>
                </form>
                <div id="toastBox"></div>
            </div>
        </div>
    </main>
</section>
<script>
        function showToast(msg) {
            let toast = document.createElement('div');
            toast.classList.add('toast');
            toast.innerHTML = msg;
            toastBox.appendChild(toast);
            if (msg.includes('error')) {
                toast.classList.add('error');
            }
            setTimeout(() => {
                toast.remove();
            }, 6000);
        }
</script>
<script src="js/script.js"></script>
</body>
</html>
<?php
include 'config.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: signin.php');
    exit;
}

// Fetch current admin data
$username = $_SESSION['username'];
$sql = "SELECT id, username, email FROM admin WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $admin = $result->fetch_assoc();
    $admin_id = $admin['id'];
    $_SESSION['email'] = $admin['email']; // Set initial email in session
} else {
    // Handle case where admin is not found
    header('Location: signin.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "UPDATE admin SET username = ?, email = ?, password = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $new_username, $email, $password, $admin_id);

    if ($stmt->execute()) {
        $_SESSION['username'] = $new_username;
        $_SESSION['email'] = $email;
        echo "<script>";
        echo "let successMsg = '<i class=\"bx bxs-check-circle\"></i> Successfully updated.';";
        echo "showToast(successMsg, 'success');";
        echo "</script>";
    } else {
        echo "<script>";
        echo "let errorMsg = '<i class=\"bx bxs-error-circle\"></i> Error in updating admin\'s profile.';";
        echo "showToast(errorMsg, 'error');";
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
    <link rel="stylesheet" href="css/admin_styles.css">
    <title>Internflo - Administrator</title>
    <link rel="icon" href="ucc-logo1.png">
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
            background: #FFFFFF;
            color: #046C34;
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
        .toast.error i {
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
        .toast.error::after {
            background: #FF0000;
        }
        button {
            background-color: #4CAF50;
    color: white;
    border: none;
    padding: 10px;
    border-radius: 3px;
    cursor: pointer;
        }
    </style> 
    <title>OnTheGo Admin</title>
</head>
<body>
<section id="sidebar">
    <div class="logo">
        <img src="ucc.png" class="logo-full" alt="UCC Full Logo">
        <img src="ucc-logo1.png" class="logo-icon" alt="UCC Icon">
    </div>
    <ul class="side-menu">
        <li><a href="admin_index.php" class="active"><i class='bx bxs-dashboard icon'></i>Dashboard</a></li>
        <li><a href="interns.php"><i class='bx bxs-graduation icon'></i>Interns</a></li>
        <li>
            <a href="#"><i class='bx bxs-analyse icon'></i>Affiliates<i class='bx bx-chevron-right icon-right'></i></a>
            <ul class="side-dropdown">
                <li><a href="partnership.php"><i class='bx bxs-briefcase icon'></i>Partnership</a></li>
                <li><a href="requests.php"><i class='bx bxs-envelope icon'></i>Requests</a></li>
            </ul>
        </li>
        <li>
            <a href="#"><i class='bx bxs-message-rounded-detail icon'></i>Feedbacks<i class='bx bx-chevron-right icon-right'></i></a>
            <ul class="side-dropdown">
                <li><a href="studentnumberfeedback.php"><i class='bx bxs-id-card icon'></i>Student Number</a></li>
                <li><a href="websitefeedback.php"><i class='bx bx-globe icon'></i>Website</a></li>
            </ul>
        </li>
        <li>
            <a href="#"><i class='bx bxs-analyse icon'></i>Accounts<i class='bx bx-chevron-right icon-right'></i></a>
            <ul class="side-dropdown">
                <li><a href="studentList.php"><i class='bx bxs-user-detail icon'></i>Students</a></li>
                <li><a href="companyList.php"><i class='bx bx-run icon'></i>Companies</a></li>
            </ul>
        </li>
        <li>
            <a href="#"><i class='bx bxs-user-account icon'></i>Adviser Account<i class='bx bx-chevron-right icon-right'></i></a>
            <ul class="side-dropdown">
                <li><a href="create_account.php"><i class='bx bxs-user-detail icon'></i>Create Advisor</a></li> 
                <li><a href="assignAdvisor.php"><i class='bx bxs-book-add icon'></i>Assign Adviser</a></li>
                <li><a href="advisorList.php"><i class='bx bxs-user-detail icon'></i>List of Adviser</a></li>  
            </ul>
        </li>
        <li>
            <a href="#"><i class='bx bxs-file-archive icon'></i>All Student Records<i class='bx bx-chevron-right icon-right'></i></a>
            <ul class="side-dropdown">
                <li><a href="student_clas.php"><i class='bx bxs-graduation icon'></i>CLAS</a></ Wli>
                <li><a href="student_cba.php"><i class='bx bxs-briefcase-alt-2 icon'></i>CBA</a></li>
                <li><a href="student_ce.php"><i class='bx bxs-building-house icon'></i>CE</a></li>
                <li><a href="student_crim.php"><i class='bx bxs-shield icon'></i>CCJE</a></li>  
            </ul>
        </li>
    </ul>
</section>

<section id="content">
    <nav>
        <i class='bx bx-menu toggle-sidebar'></i>
        <form action="#">
            <div class="form-group">
                <input type="text" placeholder="Search">
                <i class='bx bx-search icon'></i>
            </div>
        </form>
        <div class="profile">
            <img src="user.jpg" alt="">
            <ul class="profile-link">
                <p>Username: <span><?php echo htmlspecialchars($_SESSION['username']); ?></span></p>
                <li><a href="profile.php"><i class='bx bxs-user-circle icon'></i>Profile</a></li>
                <li><a href="logout.php"><i class='bx bxs-log-out-circle icon'></i>Logout</a></li>
            </ul>
        </div>
    </nav>

    <main>
        <h1 class="title">Profile</h1>
        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3>Internflo Admin Profile</h3>
                </div>
                <form method="post" action="profile.php">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($admin['username']); ?>" required>

                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>

                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>

                    <button type="submit">Update Profile</button>
                </form>
                <div id="toastBox"></div>
            </div>
        </div>
    </main>
</section>

<script>
function showToast(msg, type) {
    let toastBox = document.getElementById('toastBox');
    let toast = document.createElement('div');
    toast.classList.add('toast');
    toast.innerHTML = msg;
    
    if (type === 'error') {
        toast.classList.add('error');
    }

    toastBox.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 6000);
}
</script>
<script src="js/script.js"></script>
</body>
</html>
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
    <link rel="stylesheet" href="css/admin_styles.css">
    <title>Internflo - Administrator</title>
    <link rel="icon" href="ucc-logo1.png">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
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
                <li><a href="companyList.php"><i class='bx bx-run icon'></i> Companies</a></li>
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
                <li><a href="student_clas.php"><i class='bx bxs-graduation icon'></i> CLAS</a></li> 
                <li><a href="student_cba.php"><i class='bx bxs-briefcase-alt-2 icon'></i> CBA</a></li>
                <li><a href="student_ce.php"><i class='bx bxs-building-house icon'></i> CE</a></li>
                <li><a href="student_crim.php"><i class='bx bxs-shield icon'></i> CCJE</a></li>  
            </ul>

        </li>
    </ul>
</section>

<section id="content">
    <nav>
        <i class='bx bx-menu toggle-sidebar'></i>
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
        <h1 class="title">Dashboard</h1>
        <div class="info-data">
            <div class="card">
                <div class="head">
                    <div>
                        <?php 
                            $select_account = mysqli_query($conn, "SELECT * FROM `students`") or die('query failed');
                            $number_of_accounts = mysqli_num_rows($select_account);
                            $total_accounts = 200;
                            $percentage = ($number_of_accounts / $total_accounts) * 100;
                        ?>
                        <h2><?php echo $number_of_accounts; ?></h2>
                        <p>Students</p>
                    </div>
                    <i class='bx bxs-user icon'></i>
                </div>
                <span class="progress" data-value="<?php echo $percentage; ?>%"></span>
                <span class="label"><?php echo $percentage; ?>%</span>
            </div>
            <div class="card">
                <div class="head">
                    <div>
                        <?php 
                            $select_feedbacks = mysqli_query($conn, "SELECT * FROM `internshipad`") or die('query failed');
                            $number_of_feedbacks = mysqli_num_rows($select_feedbacks);
                            $total_feedbacks = 200;
                            $percentage = ($number_of_feedbacks / $total_feedbacks) * 100;
                        ?>
                        <h2><?php echo $number_of_feedbacks; ?></h2>
                        <p>Internship</p>
                    </div>
                    <i class='bx bxs-message-rounded-detail icon'></i>
                </div>
                <span class="progress" data-value="<?php echo $percentage; ?>%"></span>
                <span class="label"><?php echo $percentage; ?>%</span>
            </div>
            <div class="card">
                <div class="head">
                    <div>
                        <?php 
                            $sql_total_searches = "SELECT COUNT(*) AS total_searches FROM approvedrecruiters";
                            $result_total_searches = mysqli_query($conn, $sql_total_searches);
                            $row_total_searches = mysqli_fetch_assoc($result_total_searches);
                            $total_searches = $row_total_searches['total_searches'];
                        ?>
                        <h2><?php echo $total_searches; ?></h2>
                        <p>Company</p>
                    </div>
                    <i class='bx bxs-search-alt-2 icon'></i>
                </div>
                <?php 
                    $total_expected_searches = 500;
                    $percentage_searches = ($total_searches / $total_expected_searches) * 100;
                ?>
                <span class="progress" data-value="<?php echo $percentage_searches; ?>%"></span>
                <span class="label"><?php echo round($percentage_searches, 2); ?>%</span>
            </div>
            <div class="card">
                <div class="head">
                    <div>
                        <?php 
                            $select_itineraries = mysqli_query($conn, "SELECT * FROM `m_advisor_assignments`") or die('query failed');
                            $number_of_itineraries = mysqli_num_rows($select_itineraries);
                            $total_itineraries = 200;
                            $percentage = ($number_of_itineraries / $total_itineraries) * 100;
                        ?>
                        <h2><?php echo $number_of_itineraries; ?></h2>
                        <p>Advisors</p>
                    </div>
                    <i class='bx bx-run icon'></i>
                </div>
                <span class="progress" data-value="<?php echo $percentage; ?>%"></span>
                <span class="label"><?php echo $percentage; ?>%</span>
            </div>
        </div>
        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3>Internflo Report</h3>
                </div>
                <div class="chart">
                    <div id="chart"></div>
                </div>
            </div>
        </div>
    </main>
</section>

<script>
    const allProgress = document.querySelectorAll('main .card .progress');

    allProgress.forEach(item=> {
        item.style.setProperty('--value', item.dataset.value)
    })

    document.addEventListener("DOMContentLoaded", function() {
        fetch('chart_data.php')
            .then(response => response.json())
            .then(data => {
                console.log(data);

                var options = {
                    series: Object.entries(data).map(([key, value]) => ({ name: key, data: value })),
                    chart: {
                        height: 500,
                        type: 'area',
                        events: {
                            dataPointMouseEnter: function(event, chartContext, config) {
                                console.log(config.seriesName, config.dataPointIndex, config.w.globals.labels[config.dataPointIndex]);
                            }
                        }
                    },
                    stroke: {
                        curve: 'smooth'
                    },
                    xaxis: {
                        type: 'category',
                        categories: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
                    },
                    legend: {
                        position: 'top',
                        offsetY: 10
                    },
                    markers: {
                        size: 5
                    },
                    colors: ['#2487EC', '#42C7CA', '#F6C361', '#D74941', '#775DD0'],
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.7,
                            opacityTo: 0.9,
                            stops: [0, 90, 100]
                        }
                    },
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: {
                                height: 300
                            }
                        }
                    }]
                };

                var chart = new ApexCharts(document.querySelector("#chart"), options);
                chart.render();
            })
            .catch(error => {
                console.error('Error fetching data:', error);
            });
    });
</script>
<script src="js/script.js"></script>
</body>
</html>
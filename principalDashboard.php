<?php
session_start();
include 'dbConnect.php';

// Redirect to login page if not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: loginPrincipal.php");
    exit;
}

$PrincipalEmail = $_SESSION["username"];

// Initialize variable to hold principal name
$PrincipalName = "";

// Prepare a select statement to get the principal's name
$sql = "SELECT PrincipalName FROM Principal WHERE PrincipalEmail = ?";

if ($stmt = mysqli_prepare($dbCon, $sql)) {
    // Bind variables to the prepared statement as parameters
    mysqli_stmt_bind_param($stmt, "s", $PrincipalEmail);

    // Attempt to execute the prepared statement
    if (mysqli_stmt_execute($stmt)) {
        // Store result
        mysqli_stmt_store_result($stmt);

        // Check if the principal exists, if yes then retrieve data
        if (mysqli_stmt_num_rows($stmt) == 1) {
            // Bind result variables
            mysqli_stmt_bind_result($stmt, $retrieved_PrincipalName);

            // Fetch and assign principal name
            mysqli_stmt_fetch($stmt);
            $PrincipalName = $retrieved_PrincipalName; // Assign the retrieved name to $PrincipalName
        } else {
            echo "No principal found."; // Debug message
        }
    } else {
        echo "Oops! Something went wrong. Please try again later. " . mysqli_stmt_error($stmt); // Debug message
    }

    // Close statement
    mysqli_stmt_close($stmt);
} else {
    echo "Oops! Database connection error."; // Debug message
}

// Function to fetch total number of rows from a table
function getTotalRows($conn, $table) {
    $sql = "SELECT COUNT(*) AS total_rows FROM $table";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['total_rows'];
    } else {
        return 0;
    }
}

// Function to fetch total donations amount
function getTotalDonations($conn) {
    $sql = "SELECT SUM(DonationAmount) AS total_donations FROM DONATION";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['total_donations'];
    } else {
        return 0; // Return 0 if no donations found
    }
}

// Function to fetch monthly donation amounts
function getMonthlyDonations($conn) {
    $sql = "SELECT DATE_FORMAT(DonationDate, '%Y-%m') AS donation_month, SUM(DonationAmount) AS total_donations 
            FROM DONATION 
            GROUP BY donation_month 
            ORDER BY donation_month";
    $result = $conn->query($sql);
    
    $monthlyDonations = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $monthlyDonations[$row['donation_month']] = $row['total_donations'];
        }
    }
    return $monthlyDonations;
}

// Fetch data using $dbCon from dbConnect.php
$totalDonors = getTotalRows($dbCon, 'DONOR');
$totalClerks = getTotalRows($dbCon, 'CLERK');
$totalDonations = getTotalDonations($dbCon);
$monthlyDonations = getMonthlyDonations($dbCon);

// Fetch number of donors by allocation type
$sql = "SELECT a.AllocationType, COALESCE(COUNT(DISTINCT d.DonorID), 0) AS TotalDonors
        FROM ALLOCATION a
        LEFT JOIN DONATION d ON a.AllocationID = d.AllocationID
        GROUP BY a.AllocationType";
$result = $dbCon->query($sql);

$allocationTypes = [];
$totalDonorsArray = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $allocationTypes[] = $row['AllocationType'];
        $totalDonorsArray[] = $row['TotalDonors'];
    }
}

// Close connection
mysqli_close($dbCon);
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Principal Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Playfair+Display&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .navbar {
            background: linear-gradient(to right, #4CAF50, #8E44AD);
            color: white;
            padding: 10px 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar img {
            height: 60px;
            margin-right: 20px;
        }
        .navbar-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            margin-right: auto;
        }
        .navbar-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 10px 15px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
        }
        .navbar a:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        .dropdown {
            position: relative;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: white;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            z-index: 1;
            border-radius: 5px;
            overflow: hidden;
            top: 100%;
            left: 0;
            min-width: 160px;
        }
        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }
        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }
        .dropdown:hover .dropdown-content {
            display: block;
        }
        .container {
            padding: 40px;
            text-align: center;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
        }
        .welcome {
            font-size: 2rem;
            margin-bottom: 20px;
            background: linear-gradient(to right, #4CAF50, #8E44AD);
            color: white;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            width: 100%;
        }
        .info-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin: 20px;
            text-align: left;
            width: 300px; /* Fixed width for consistency */
        }
        .info-card h3 {
            margin-top: 0;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .info-card p {
            margin: 10px 0;
            font-size: 1.2rem;
        }
        .info-card a {
            color: #007bff;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .info-card a:hover {
            color: #0056b3;
        }
        .icon {
            font-size: 1.5rem;
        }
        .chart-container {
            width: 300px; /* Smaller width for the chart */
            margin: auto;
        }
        .line-chart-container {
            width: 600px; /* Larger width for the line chart */
            margin: auto;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <img src="images/logo.png" alt="Logo">
        <div class="navbar-title">MA'AHAD TAHFIZ WAL TARBIYAH DARUL IMAN</div>
        <div class="navbar-links">
            <a href="principalDashboard.php"><i class="fas fa-chart-line icon"></i> Dashboard</a>
            <a href="addClerk.php"><i class="fas fa-user-plus icon"></i> Add Clerk</a>
            <div class="dropdown">
                <a href="#"><i class="fas fa-file-alt icon"></i> View Report <i class="fas fa-caret-down"></i></a>
                <div class="dropdown-content">
                    <a href="allocationReport.php">Allocation Report</a>
                    <a href="donorReport.php">Donor Report</a>
                    <a href="summaryReport.php">Summary Report</a>
                </div>
            </div>
            <a href="loginPrincipal.php"><i class="fas fa-sign-out-alt icon"></i> Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="welcome">Welcome,  <?= htmlspecialchars($PrincipalName) ?>!</div>

        <div class="info-card">
            <h3><i class="fas fa-users icon"></i> Total Donors</h3>
            <p><?php echo $totalDonors; ?></p>
            <p><a href="viewDonor.php">See more</a></p>
        </div>

        <div class="info-card">
            <h3><i class="fas fa-user-tie icon"></i> Total Clerks</h3>
            <p><?php echo $totalClerks; ?></p>
            <p><a href="viewClerk.php">See more</a></p>
        </div>

        <div class="info-card">
            <h3><i class="fas fa-donate icon"></i> Total Donations (RM)</h3>
            <p><?php echo number_format($totalDonations, 2); ?></p>
        </div>

        <div class="info-card line-chart-container">
            <h3><i class="fas fa-chart-pie icon"></i> Total Donation for Each Allocation</h3>
            <canvas id="donationChart"></canvas>
        </div>

        <div class="info-card line-chart-container">
            <h3><i class="fas fa-chart-line icon"></i> Monthly Donations (RM)</h3>
            <canvas id="monthlyDonationsChart"></canvas>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var ctx = document.getElementById('donationChart').getContext('2d');
            var donationChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($allocationTypes); ?>,
                    datasets: [{
                        label: 'Total Donors by Allocation Type',
                        data: <?php echo json_encode($totalDonorsArray); ?>,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    plugins: {
                        datalabels: {
                            anchor: 'end',
                            align: 'top',
                            formatter: (value) => value,
                            font: {
                                weight: 'bold'
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });

            var lineCtx = document.getElementById('monthlyDonationsChart').getContext('2d');
            var monthlyDonationsChart = new Chart(lineCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_keys($monthlyDonations)); ?>,
                    datasets: [{
                        label: 'Monthly Donations',
                        data: <?php echo json_encode(array_values($monthlyDonations)); ?>,
                        backgroundColor: 'rgba(153, 102, 255, 0.2)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 1,
                        fill: true
                    }]
                },
                options: {
                    plugins: {
                        datalabels: {
                            anchor: 'end',
                            align: 'top',
                            formatter: (value) => value.toLocaleString('en-US', { style: 'currency', currency: 'RM' }),
                            font: {
                                weight: 'bold'
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        });
    </script>
</body>
</html>
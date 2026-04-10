<?php
session_start();
include 'dbConnect.php'; // Include the database connection

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: loginClerk.php");
    exit;
}

$ClerkEmail = $_SESSION["username"]; // Assuming clerk email is stored in session username

// Initialize variable to hold clerk name
$ClerkName = "";

// Prepare a select statement to get the clerk's name
$sql = "SELECT ClerkName FROM Clerk WHERE ClerkEmail = ?";

if ($stmt = mysqli_prepare($dbCon, $sql)) {
    // Bind variables to the prepared statement as parameters
    mysqli_stmt_bind_param($stmt, "s", $ClerkEmail);

    // Attempt to execute the prepared statement
    if (mysqli_stmt_execute($stmt)) {
        // Store result
        mysqli_stmt_store_result($stmt);

        // Check if the clerk exists, if yes then retrieve data
        if (mysqli_stmt_num_rows($stmt) == 1) {
            // Bind result variables
            mysqli_stmt_bind_result($stmt, $retrieved_ClerkName);

            // Fetch and assign clerk name
            if (mysqli_stmt_fetch($stmt)) {
                $ClerkName = $retrieved_ClerkName;
            }
        } else {
            echo "No clerk found."; // Debug message
        }
    } else {
        echo "Oops! Something went wrong. Please try again later. " . mysqli_stmt_error($stmt); // Debug message
    }

    // Close statement
    mysqli_stmt_close($stmt);
} else {
    echo "Oops! Database connection error."; // Debug message
}

// Fetch the number of donors
$sql = "SELECT COUNT(*) as numDonors FROM DONOR";
$result = $dbCon->query($sql);
$numDonors = $result->fetch_assoc()['numDonors'];

// Fetch the number of donations
$sql = "SELECT COUNT(*) as numDonations FROM DONATION WHERE ConfirmationReceipt != 'not uploaded'";
$result = $dbCon->query($sql);
$numDonations = $result->fetch_assoc()['numDonations'];

// Fetch the total amount of donations
$sqlTotalDonations = "SELECT CONCAT('RM', FORMAT(SUM(DonationAmount), 2)) as totalAmount 
                        FROM DONATION 
                        WHERE ConfirmationReceipt != 'not uploaded'";
$resultTotalDonations = $dbCon->query($sqlTotalDonations);
$totalDonations = $resultTotalDonations->fetch_assoc()['totalAmount'];

// Fetch the number of reports
$sql = "SELECT COUNT(*) as numReports FROM REPORT";
$result = $dbCon->query($sql);
$numReports = $result->fetch_assoc()['numReports'];

// Fetch donation data for the chart
$sql = "SELECT a.AllocationType, SUM(d.DonationAmount) as totalAmount
        FROM DONATION d
        JOIN ALLOCATION a ON d.AllocationID = a.AllocationID
        WHERE d.ConfirmationReceipt != 'not uploaded'
        GROUP BY a.AllocationType";
$result = $dbCon->query($sql);

$allocationTypes = [];
$totalAmounts = [];

while($row = $result->fetch_assoc()) {
    $allocationTypes[] = $row['AllocationType'];
    $totalAmounts[] = $row['totalAmount'];
}


// Fetch total donations for today
$sqlTodayTotal = "SELECT CONCAT('RM', FORMAT(SUM(DonationAmount), 2)) as totalTodayAmount
                  FROM DONATION
                  WHERE DATE(DonationDate) = CURDATE()";
$resultTodayTotal = $dbCon->query($sqlTodayTotal);
$totalTodayDonations = $resultTodayTotal->fetch_assoc()['totalTodayAmount'];


// Generate random colors for the chart
$colors = [];
for ($i = 0; $i < count($allocationTypes); $i++) {
    $colors[] = '#' . substr(str_shuffle('ABCDEF0123456789'), 0, 6);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clerk Dashboard</title>
    <link rel="stylesheet" href="css/CDashboard.css"> <!-- Link to your CSS file -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Font Awesome CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
</head>
<body class="animated fadeIn">

<!-- ======================================== navigation bar ============================================== -->   

<?php include 'CNavigation.php'; ?>

<!-- =========================================== content ================================================= -->   
    <div class="dashboard-content">
    <h2>Welcome, <?= htmlspecialchars($ClerkName) ?></h2>

        <div class="metrics-container">
            <div class="metric-box">
                <i class="fa fa-users fa-3x"></i>
                <h3>Number of Donors</h3>
                <p id="numDonors"><?php echo $numDonors; ?></p>
            </div>
            <div class="metric-box">
                <i class="fa fa-hand-holding-dollar fa-3x"></i>
                <h3>Number of Donations</h3>
                <p id="numDonations"><?php echo $numDonations; ?></p>
            </div>
            <div class="metric-box">
                <i class="fa fa-money-bill-1-wave fa-3x"></i>
                <h3>Total amount of donations</h3>
                <p id="totalDonations"><?php echo $totalDonations; ?></p>
            </div>
            <div class="metric-box">
                <i class="fa fa-calendar-day fa-3x"></i>
                <h3>Today's Donation</h3>
                <p id="TodayDonations"><?php echo $totalTodayDonations; ?></p>
            </div>
            <div class="metric-box">
                <i class="fa fa-file-alt fa-3x"></i>
                <h3>Number of Reports</h3>
                <p id="numReports"><?php echo $numReports; ?></p>
            </div>
        </div>

        <div class="chart-container">
            <canvas id="donationChart"></canvas>
        </div>
    </div>

    <script>
        // Chart.js code to create a sample chart
        const ctx = document.getElementById('donationChart').getContext('2d');
        const donationChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($allocationTypes); ?>,
                datasets: [{
                    label: 'Total Donation Amount',
                    data: <?php echo json_encode($totalAmounts); ?>,
                    backgroundColor: <?php echo json_encode($colors); ?>,
                    borderWidth: 1
                }]
            },
            options: {
                plugins: {
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        formatter: (value, context) => {
                            return 'RM ' + value;
                        },
                        font: {
                            size: 14,  // Adjust the font size
                            weight: 'bold'  // Adjust the font weight
                        },
                        color: '#696969'  // Adjust the font color to dark grey
                    },
                    legend: {
                        display: false  // Hide the legend
                    },
                    title: {
                        display: true,
                        text: 'Total Donation Amount for Each Allocation',
                        font: {
                            size: 18,  // Adjust the title font size
                            weight: 'bold'  // Adjust the title font weight
                        },
                        padding: {
                            top: 10,
                            bottom: 30
                        },
                        color: '#696969' // Set the title color to dark grey
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'RM ' + value; // Display RM before y-axis values
                            },
                            font: {
                                size: 14,  // Adjust the font size
                                weight: 'bold'  // Adjust the font weight
                            },
                            color: '#696969'  // Adjust the font color to dark grey
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    </script>

</body>
</html>

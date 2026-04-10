<?php
session_start();
include 'dbConnect.php'; // Include the database connection

// Redirect to login page if not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: loginPrincipal.php");
    exit;
}

// Get the report ID from the URL
$reportID = isset($_GET['reportID']) ? $_GET['reportID'] : null;

if (!$reportID) {
    echo "Invalid report ID.";
    exit;
}

// Fetch the report details
$sqlReport = "SELECT * FROM REPORT WHERE ReportID = ?";
$stmt = $dbCon->prepare($sqlReport);
$stmt->bind_param("s", $reportID);
$stmt->execute();
$resultReport = $stmt->get_result();
$reportDetails = $resultReport->fetch_assoc();

if (!$reportDetails) {
    echo "Report not found.";
    exit;
}

$startDate = $reportDetails['StartDate'];
$endDate = $reportDetails['EndDate'];
$reportType = $reportDetails['ReportType'];
$reportName = $reportDetails['ReportName'];

// Function to fetch data for Donor Report
function fetchDonorReport($dbCon, $startDate, $endDate) {
    $sql = "SELECT d.DonorName, d.DonorEmail, d.DonorPhone, COUNT(dn.DonationID) AS DonationCount, 
            CONCAT('RM ', COALESCE(SUM(dn.DonationAmount), 0)) AS TotalDonation, 
            COALESCE(GROUP_CONCAT(DISTINCT dn.DonationPurpose SEPARATOR ', '), 'None') AS DonationPurposes
        FROM DONOR d
        LEFT JOIN DONATION dn ON d.DonorID = dn.DonorID AND dn.ConfirmationReceipt != 'not uploaded'
        WHERE dn.DonationDate BETWEEN ? AND ?
        GROUP BY d.DonorID";
    $stmt = $dbCon->prepare($sql);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    return $stmt->get_result();
}


// Function to fetch data for Allocation Report
function fetchAllocationReport($dbCon, $startDate, $endDate) {
    $sql = "SELECT a.AllocationType, a.Description, COUNT(d.DonationID) AS DonationCount, CONCAT('RM ', COALESCE(SUM(d.DonationAmount), 0)) AS TotalDonation
            FROM ALLOCATION a
            LEFT JOIN DONATION d ON a.AllocationID = d.AllocationID
            WHERE d.DonationDate BETWEEN ? AND ?
            GROUP BY a.AllocationID";
    $stmt = $dbCon->prepare($sql);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    return $stmt->get_result();
}

function fetchSummaryReport($dbCon, $startDate, $endDate) {
    $sqlSummary = "SELECT 
                    COUNT(DonationID) AS TotalDonations,
                    CONCAT('RM ', COALESCE(SUM(DonationAmount), 0)) AS TotalDonationAmount
                   FROM DONATION
                   WHERE DonationDate BETWEEN ? AND ?
                     AND ConfirmationReceipt != 'not uploaded'";
    $stmtSummary = $dbCon->prepare($sqlSummary);
    $stmtSummary->bind_param("ss", $startDate, $endDate);
    $stmtSummary->execute();
    $resultSummary = $stmtSummary->get_result();
    $summary = $resultSummary->fetch_assoc();

    $sqlMethods = "SELECT 
                    DonationMethod,
                    COUNT(DonationID) AS MethodCount,
                    CONCAT('RM ', COALESCE(SUM(DonationAmount), 0)) AS MethodTotal
                   FROM DONATION
                   WHERE DonationDate BETWEEN ? AND ?
                     AND ConfirmationReceipt != 'not uploaded'
                   GROUP BY DonationMethod";
    $stmtMethods = $dbCon->prepare($sqlMethods);
    $stmtMethods->bind_param("ss", $startDate, $endDate);
    $stmtMethods->execute();
    $resultMethods = $stmtMethods->get_result();

    $sqlPurposes = "SELECT 
                    DonationPurpose,
                    COUNT(DonationID) AS PurposeCount,
                    CONCAT('RM ', COALESCE(SUM(DonationAmount), 0)) AS PurposeTotal
                   FROM DONATION
                   WHERE DonationDate BETWEEN ? AND ?
                     AND ConfirmationReceipt != 'not uploaded'
                     AND DonationPurpose != '0'
                   GROUP BY DonationPurpose";
    $stmtPurposes = $dbCon->prepare($sqlPurposes);
    $stmtPurposes->bind_param("ss", $startDate, $endDate);
    $stmtPurposes->execute();
    $resultPurposes = $stmtPurposes->get_result();

    return [$summary, $resultMethods, $resultPurposes];
}


// Fetch the report data based on the report type
if ($reportType == 'Donors') {
    $reportData = fetchDonorReport($dbCon, $startDate, $endDate);
} elseif ($reportType == 'Allocation') {
    $reportData = fetchAllocationReport($dbCon, $startDate, $endDate);
} elseif ($reportType == 'Summary') {
    list($summary, $resultMethods, $resultPurposes) = fetchSummaryReport($dbCon, $startDate, $endDate);
} else {
    echo "Invalid report type.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($reportName); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        padding: 20px;
    }
    .company-info {
        text-align: center;
        margin-bottom: 20px;
    }
    .company-info h2 {
        margin: 0;
        font-size: 1.5rem;
        color: #4CAF50;
    }
    .company-info p {
        margin: 5px 0;
        font-size: 1rem;
        color: #333;
    }
    .table-container {
        max-width: 900px;
        margin: 20px auto;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        padding: 20px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
        margin-bottom: 20px;
    }
    th, td {
        padding: 12px;
        border: 1px solid #ddd;
        text-align: left;
    }
    th {
        background-color: #4CAF50;
        color: black; /* Changed to black */
        font-weight: 500;
    }
    tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    .print-button {
        display: block;
        margin-left: auto;
        padding: 10px 20px;
        background-color: #4CAF50;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1rem;
        text-align: center;
        transition: background-color 0.3s ease;
        margin-bottom: 20px;
    }
    .print-button i {
        margin-right: 5px;
    }
    .print-button:hover {
        background-color: #45a049;
    }
    @media print {
        body {
            background-color: white;
            padding: 20px;
        }
        .navbar, .print-button, .footer {
            display: none;
        }
        .container {
            padding: 0;
        }
        .company-info, .table-container {
            margin: 0;
            padding: 0;
            width: 100%;
        }
        .company-info {
            margin-bottom: 20px;
        }
        .table-container {
            margin-top: 20px;
            border: 1px solid #000;
            border-radius: 0;
            box-shadow: none;
            padding: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }
        table th, table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
            font-size: 0.9rem;
        }
        th {
            background-color: #4CAF50 !important;
            color: black !important;
        }
        tr:nth-child(even) {
            background-color: #f0f0f0 !important;
        }
        tr:nth-child(odd) {
            background-color: #ffffff !important;
        }
    }
</style>
</head>
<body>
    <div class="navbar">
        <a href="#" class="back-link"><i class="fas fa-arrow-left"></i></a>
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
        <div class="print-button-container" style="text-align: right;">
            <button class="print-button" onclick="window.print()">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>
        
        <div class="company-info">
            <div class="logo">
                <img src="images/logo.png" alt="Company Logo">
            </div>
            <h2><?php echo htmlspecialchars($reportName); ?></h2>
            <p>KM 14, JALAN KELANTAN, KAMPUNG TOK JIRING 21060 KUALA NERUS TERENGGANU</p>
            <p>Email: mtt_trg@yahoo.com | Phone: 09-666 9550</p>
        </div>
        <div class="table-container">
            <?php if ($reportType == 'Summary'): ?>
                <h3>Summary</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Total Donations</th>
                            <th>Total Donation Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo $summary['TotalDonations']; ?></td>
                            <td><?php echo $summary['TotalDonationAmount']; ?></td>
                        </tr>
                    </tbody>
                </table>

                <h3>Donation By Methods</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Donation Method</th>
                            <th>Count</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $resultMethods->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['DonationMethod']; ?></td>
                                <td><?php echo $row['MethodCount']; ?></td>
                                <td><?php echo $row['MethodTotal']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <h3>Donation By Purposes</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Donation Purpose</th>
                            <th>Count</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $resultPurposes->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['DonationPurpose']; ?></td>
                                <td><?php echo $row['PurposeCount']; ?></td>
                                <td><?php echo $row['PurposeTotal']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <table>
                <thead>
                    <tr>
                        <?php if ($reportType == 'Donors'): ?>
                            <th>Donor Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Donation Count</th>
                            <th>Total Donation</th>
                            <th>Donation Purposes</th>
                        <?php elseif ($reportType == 'Allocation'): ?>
                            <th>Allocation Type</th>
                            <th>Description</th>
                            <th>Donation Count</th>
                            <th>Total Donation</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($reportType == 'Donors'): ?>
                        <?php while ($row = $reportData->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['DonorName']; ?></td>
                                <td><?php echo $row['DonorEmail']; ?></td>
                                <td><?php echo $row['DonorPhone']; ?></td>
                                <td><?php echo $row['DonationCount']; ?></td>
                                <td><?php echo $row['TotalDonation']; ?></td>
                                <td><?php echo $row['DonationPurposes']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php elseif ($reportType == 'Allocation'): ?>
                        <?php while ($row = $reportData->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['AllocationType']; ?></td>
                                <td><?php echo $row['Description']; ?></td>
                                <td><?php echo $row['DonationCount']; ?></td>
                                <td><?php echo $row['TotalDonation']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        document.querySelector('.back-link').addEventListener('click', function(event) {
            event.preventDefault(); // Prevent default action (e.g., following the href link)

            window.history.back(); // Go back to the previous page
        });
    </script>
</body>
</html>

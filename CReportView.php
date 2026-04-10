<?php
session_start();
include 'dbConnect.php'; // Include the database connection

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: loginClerk.php");
    exit;
}

// Get the report ID from the URL
$reportID = isset($_GET['ReportID']) ? $_GET['ReportID'] : null;

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
    $sql = "SELECT a.AllocationType, a.Description, COUNT(d.DonationID) AS DonationCount, 
            CONCAT('RM ', COALESCE(SUM(d.DonationAmount), 0)) AS TotalDonation
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
    <title>Report View</title>
    <link rel="stylesheet" href="css/CDonation.css"> <!-- Link to your CSS file -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Font Awesome CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- SweetAlert CDN -->
    <style>
        body {
            padding-left: 10%;
            padding-right: 10%;
        }

        .container {
            width: 80%;
            margin: auto;
            overflow: hidden;
            margin-top: 20px;
            padding: 20px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            animation: slideIn 1s forwards;
        }

        h1 {
            color: #333;
            text-align: center;
        }

        .btn-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .btn-container a {
            text-decoration: none;
            color: #fff;
            background-color: #007bff;
            padding: 10px 20px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-container a i {
            margin-right: 5px;
        }

        .company-info {
            text-align: center;
            margin-bottom: 20px;
        }

        .company-info img {
            height: 150px;
        }

        .company-info p {
            margin: 5px 0;
            font-size: 1rem;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #deebab;
            color: black;
        }

        /* Slide-in animation for metric boxes and chart */
        @keyframes slideIn {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Styles to hide elements in print view */
        @media print {
            body {
                padding-left: 0;
                padding-right: 0;
            }

            .container {
                width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }

            .btn-container, .nav-bar.nav-bar, .logo img, .nav-links, .nav-links a, .nav-links a:hover, .nav-links a i, .badge {
                display: none;
            }

            table, th, td {
                font-size: 10px;
            }

            table {
                page-break-inside: avoid;
            }

            /* Adjust th background color for print */
            th {
                background-color: #deebab !important;
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact; 
            }

            /* Adjust company info image size for print */
            .company-info img {
                height: 100px;
            }
        }
    </style>
</head>
<body class="animated fadeIn">

<!-- ======================================== navigation bar ============================================== -->   

<?php include 'CNavigation.php'; ?>

<!-- =========================================== content ================================================= -->    

<div class="container">
    <div class="btn-container">
        <a href="CReport.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
        <a href="#" class="btn-download"><i class="fas fa-print"></i> Print Report</a>
    </div>

    <div class="company-info">
        <img src="images/logo.png" alt="Company Logo">
        <p>KM 14, JALAN KELANTAN, KAMPUNG TOK JIRING 21060 KUALA NERUS TERENGGANU</p>
        <p>Email: mtt_trg@yahoo.com | Phone: 09-666 9550</p>
    </div>

    <h1><?php echo htmlspecialchars($reportName); ?></h1>
    <p>Report ID: <?php echo htmlspecialchars($reportID); ?></p>

<!-- =========================================== Donors Report ================================================= -->   

    <?php if ($reportType == 'Donors'): ?>
    <div class="report-list">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Donor Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Number of Donations</th>
                        <th>Total Donation Amount</th>
                        <th>Donation Purposes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $counter = 1;
                    while ($row = $reportData->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><?php echo htmlspecialchars($row['DonorName']); ?></td>
                            <td><?php echo htmlspecialchars($row['DonorEmail']); ?></td>
                            <td><?php echo htmlspecialchars($row['DonorPhone']); ?></td>
                            <td><?php echo htmlspecialchars($row['DonationCount']); ?></td>
                            <td><?php echo htmlspecialchars($row['TotalDonation']); ?></td>
                            <td><?php echo htmlspecialchars($row['DonationPurposes']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

<!-- =========================================== Allocation Report ================================================= -->   

    <?php elseif ($reportType == 'Allocation'): ?>
    <div class="report-list">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Allocation Type</th>
                        <th>Description</th>
                        <th>Number of Donations</th>
                        <th>Total Donation Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $counter = 1;
                    while ($row = $reportData->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><?php echo htmlspecialchars($row['AllocationType']); ?></td>
                            <td><?php echo htmlspecialchars($row['Description']); ?></td>
                            <td><?php echo htmlspecialchars($row['DonationCount']); ?></td>
                            <td><?php echo htmlspecialchars($row['TotalDonation']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

<!-- =========================================== Summary Report ================================================= -->   

    <?php elseif ($reportType == 'Summary'): ?>
    <div class="report-list">
        <h2>Summary</h2>
        <table>
            <tr>
                <th>Total Donations</th>
                <td><?php echo htmlspecialchars($summary['TotalDonations']); ?></td>
            </tr>
            <tr>
                <th>Total Donation Amount</th>
                <td><?php echo htmlspecialchars($summary['TotalDonationAmount']); ?></td>
            </tr>
        </table>

        <h2>Donations by Method</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Method</th>
                        <th>Number of Donations</th>
                        <th>Total Donation Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $counter = 1;
                    while ($rowMethod = $resultMethods->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><?php echo htmlspecialchars($rowMethod['DonationMethod']); ?></td>
                            <td><?php echo htmlspecialchars($rowMethod['MethodCount']); ?></td>
                            <td><?php echo htmlspecialchars($rowMethod['MethodTotal']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <h2>Donations by Purpose</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Purpose</th>
                        <th>Number of Donations</th>
                        <th>Total Donation Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $counter = 1;
                    while ($rowPurpose = $resultPurposes->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><?php echo htmlspecialchars($rowPurpose['DonationPurpose']); ?></td>
                            <td><?php echo htmlspecialchars($rowPurpose['PurposeCount']); ?></td>
                            <td><?php echo htmlspecialchars($rowPurpose['PurposeTotal']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    // Print report function
    document.querySelector('.btn-download').addEventListener('click', function () {
        window.print();
    });
</script>

</body>
</html>

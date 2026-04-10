<?php
session_start();
require_once "dbConnect.php";

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Get the donation ID from the URL
$donationID = $_GET['donation_id'];

// Retrieve donation and donor details
$sql = "SELECT 
            d.DonorID, d.DonorName, d.DonorEmail, d.DonorPhone, 
            dn.DonationID, dn.DonationAmount, dn.DonationDate, dn.DonationPurpose, dn.DonationSend, dn.DonationMethod, 
            dn.InvoiceID, dn.InvoiceDate, dn.InvoiceTime 
        FROM 
            DONOR d 
        JOIN 
            DONATION dn ON d.DonorID = dn.DonorID 
        WHERE 
            dn.DonationID = ?";
$stmt = mysqli_prepare($dbCon, $sql);
mysqli_stmt_bind_param($stmt, "s", $donationID);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Invalid Donation ID");
}

$donor = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            width: 80%;
            margin: auto;
            background: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }
        .header-section {
            text-align: center;
            border-bottom: 1px solid #ddd;
            padding-bottom: 20px;
        }
        .header-section img {
            max-width: 100px;
        }
        .header-section h2 {
            margin: 10px 0;
        }
        .invoice-header {
            text-align: center;
            margin: 20px 0;
        }
        .invoice-details, .donor-details, .donation-summary {
            margin: 20px 0;
        }
        .details-header {
            font-weight: bold;
        }
        .details-content {
            margin: 5px 0;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table th, .summary-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        .summary-table th {
            background-color: #f2f2f2;
        }
        .print-button {
            display: block;
            width: 100px;
            margin: 20px auto;
            padding: 10px;
            text-align: center;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .print-button:hover {
            background-color: #218838;
        }
        .dashboard-button {
            position: absolute;
            top: 20px;
            left: 20px;
            font-size: 40px;
            color: purple;
            text-decoration: none;
        }

        /* Print styles */
        @media print {
            .print-button, .dashboard-button {
                display: none;
            }
            .container {
                width: 100%;
                box-shadow: none;
                margin-top: 0;
            }
        }
    </style>
</head>
<body>
<a href="donorDashboard.php" class="dashboard-button"><i class="fa fa-angle-double-left"></i></a>
    <div class="container">
        <div class="header-section">
            <img src="images/logo.png" alt="Organization Logo">
            <h2>Maahad Tahfiz Wal Tarbiyyah (MTT) Darul Iman</h2>
        </div>
        <div class="invoice-header">
            <h1>Invoice</h1>
            <p>Invoice #: <?php echo htmlspecialchars($donor['InvoiceID']); ?></p>
            <p>Generated on: <?php echo htmlspecialchars($donor['InvoiceDate']); ?> at <?php echo htmlspecialchars($donor['InvoiceTime']); ?></p>
        </div>
        <div class="donor-details">
            <h3>Donor Information</h3>
            <p class="details-content">Donor ID: <?php echo htmlspecialchars($donor['DonorID']); ?></p>
            <p class="details-content">Name: <?php echo htmlspecialchars($donor['DonorName']); ?></p>
            <p class="details-content">Email: <?php echo htmlspecialchars($donor['DonorEmail']); ?></p>
            <p class="details-content">Phone No: <?php echo htmlspecialchars($donor['DonorPhone']); ?></p>
        </div>
        <div class="donation-summary">
            <h3>Donation Summary</h3>
            <table class="summary-table">
                <tr>
                    <th>Donation ID</th>
                    <th>Donation Amount (RM)</th>
                    <th>Donation Date</th>
                    <th>Donation Purpose</th>
                    <th>Donation Sent To</th>
                    <th>Donation Method</th>
                </tr>
                <tr>
                    <td><?php echo htmlspecialchars($donor['DonationID']); ?></td>
                    <td><?php echo htmlspecialchars(number_format($donor['DonationAmount'], 2)); ?></td>
                    <td><?php echo htmlspecialchars($donor['DonationDate']); ?></td>
                    <td><?php echo htmlspecialchars($donor['DonationPurpose']); ?></td>
                    <td><?php echo htmlspecialchars($donor['DonationSend']); ?></td>
                    <td><?php echo htmlspecialchars($donor['DonationMethod']); ?></td>
                </tr>
            </table>
        </div>
        <a href="javascript:window.print()" class="print-button">Print</a>
    </div>
</body>
</html>

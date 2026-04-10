<?php
session_start();
include 'dbConnect.php'; // Include the database connection

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: loginClerk.php");
    exit;
}

// Fetch the donation ID from the URL
if (isset($_GET['donationID'])) {
    $donationID = $_GET['donationID'];
} else {
    // If no donation ID is provided, redirect to the previous page or show an error
    header("location: CDonation.php"); // Change this to the appropriate page
    exit;
}

// Update donation status to 'Read'
$sql = "UPDATE DONATION SET ReadStatus = 'Read' WHERE DonationID = ?";
$stmt = $dbCon->prepare($sql);
$stmt->bind_param("s", $donationID);
$stmt->execute();

// Function to fetch donation details by ID
function fetchDonationDetails($dbCon, $donationID) {
    $sql = "SELECT d.*,donor.*,a.*
            FROM DONATION d
            JOIN DONOR donor ON d.DonorID = donor.DonorID
            JOIN ALLOCATION a ON d.AllocationID = a.AllocationID
            WHERE d.DonationID = ?";
    $stmt = $dbCon->prepare($sql);
    $stmt->bind_param("s", $donationID);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Fetch donation details
$donation = fetchDonationDetails($dbCon, $donationID);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation</title>
    <link rel="stylesheet" href="css/CDonation.css"> <!-- Link to your CSS file -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Font Awesome CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- SweetAlert CDN -->
    <style>
        .receipt {
            width: 95%;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 18px;
            align-items: center;
        }

        .receipt .section {
            margin-bottom: 15px;
        }

        .receipt .section label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
            color: #333;
        }

        .receipt .section .data {
            color: #555;
        }

        .receipt .line {
            border-top: 1px solid black;
            margin: 5px 0;
        }

        .receipt .flex-container {
            display: flex;
            justify-content: space-between;
        }

        .receipt .flex-item {
            width: 48%;
        }

        .receipt .full-payment {
            border-top: 1px solid black;
            border-bottom: 1px solid black;
            padding: 5px 0;
            margin: 5px 0;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }
    </style>
</head>
<body class="animated fadeIn">

<!-- ======================================== navigation bar ============================================== -->   

<?php include 'CNavigation.php'; ?>

<!-- =========================================== content ================================================= -->    

<div class="container">
    <h1>Donation</h1>
    <div class="receipt">
            <div class="flex-container">
                <div class="flex-item section">
                    <label>Donation ID:</label>
                    <div class="data"><?php echo $donation['DonationID']; ?></div>
                </div>
                <div class="flex-item section">
                    <label>Donation Date:</label>
                    <div class="data"><?php echo $donation['DonationDate']; ?></div>
                </div>
            </div>
            <div class="line"></div>
            <div class="flex-container ">
                <div class="flex-item section">
                    <label>Donor ID:</label>
                    <div class="data"><?php echo $donation['DonorID']; ?></div>
                </div>
                <div class="flex-item section">
                    <label>Invoice ID:</label>
                    <div class="data"><?php echo $donation['InvoiceID']; ?></div>
                </div>
            </div>
            <div class="flex-container ">
                <div class="flex-item section">
                    <label>Donor Name:</label>
                    <div class="data"><?php echo $donation['DonorName']; ?></div>
                </div>
                <div class="flex-item section">
                    <label>Invoice date:</label>
                    <div class="data"><?php echo $donation['InvoiceDate']; ?></div>
                </div>
            </div>
            <div class="flex-container ">
                <div class="flex-item section">
                    <label>Phone Number:</label>
                    <div class="data"><?php echo $donation['DonorPhone']; ?></div>
                </div>
                <div class="flex-item section">
                    <label>Invoice Time:</label>
                    <div class="data"><?php echo $donation['InvoiceTime']; ?></div>
                </div>
            </div>
            <div class="section">
                <label>Email:</label>
                <div class="data"><?php echo $donation['DonorEmail']; ?></div>
            </div>
            <div class="line"></div>
            <div class="flex-container">
                <div class="flex-item section">
                    <label>Sending Bank:</label>
                    <div class="data"><?php echo $donation['PaymentOption']; ?></div>
                </div>
                <div class="flex-item section">
                    <label>Recipient Bank</label>
                    <div class="data"><?php echo $donation['DonationSend']; ?></div>
                </div>
            </div>
            <div class="flex-container">
                <div class="flex-item section">
                    <label>Donation Purpose:</label>
                    <div class="data"><?php echo $donation['AllocationType'] . ' - ' . $donation['Description']; ?></div>
                </div>
            </div>
            <div class="section full-payment">
                <label>Donation Amount:</label>
                <div class="data">RM <?php echo $donation['DonationAmount']; ?></div>
            </div>
            <p><a href="CDonation.php" class="btn-primary">Back</a></p>
        </div>


</div>

</body>
</html>

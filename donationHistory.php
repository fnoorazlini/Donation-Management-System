<?php
session_start();
require_once "dbConnect.php";

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$donorEmail = $_SESSION["username"]; // Assuming donor email is stored in session username

// Retrieve donor ID based on the email
$sql = "SELECT DonorID, DonorName FROM DONOR WHERE DonorEmail =?";
$stmt = mysqli_prepare($dbCon, $sql);
mysqli_stmt_bind_param($stmt, "s", $donorEmail);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $donor = mysqli_fetch_assoc($result);
    $donorID = $donor['DonorID'];
    $donorName = $donor['DonorName'];

    // Fetch donations associated with the logged-in donor
    $sql = "SELECT d.DonationID, d.DonationAmount, d.DonationMethod, d.DonationSend, d.DonationDate,
                   CASE
                       WHEN d.ConfirmationReceipt IS NULL THEN 'Incomplete'
                       WHEN d.ConfirmationReceipt = 'not uploaded' THEN 'Incomplete'
                       ELSE 'Completed'
                   END AS StatusDonation
            FROM DONATION d
            WHERE d.DonorID =?";
    $stmt = mysqli_prepare($dbCon, $sql);
    mysqli_stmt_bind_param($stmt, "s", $donorID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    // Handle case where donor ID is not found (though it should ideally be found)
    $donorName = "Unknown Donor";
    $result = null; // Set result to null or handle as needed
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation List</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- SweetAlert CDN -->
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: url('images/b.jpg') no-repeat center center fixed;
            background-size: cover;
        }

        .container {
            width: 80%;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(to bottom right, #66cc66, #9933ff);
            padding: 10px 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        header img {
            height: 70px;
        }

        header nav {
            display: flex;
            gap: 15px;
        }

        header nav a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            transition: background-color 0.3s ease;
            font-size: 20px;
        }

        header nav a:hover {
            background-color: #4b0082;
        }

        .logout {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            font-size: 1.8em;
        }

        .logout i {
            margin-left: 5px;
        }

        .logout:hover {
            background-color: #4b0082;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        table th {
            background-color: #f2f2f2;
        }

        .status-complete {
            color: green;
            font-weight: bold;
        }

        .status-incomplete {
            color: red;
            font-weight: bold;
        }

        .invoice-icon {
            color: #4b0082;
            font-size: 1.5em;
            text-align: center;
        }
    </style>
</head>
<body>

<header>
    <img src="images/logo.png" alt="logo" />
    <nav>
        <a href="donorDashboard.php"><i class="fas fa-home"></i> Home</a>
        <a href="donation.php"><i class="fas fa-donate"></i> Donate</a>
        <a href="upload_receipt.php"><i class="fas fa-upload"></i> Upload Receipt</a>
        <a href="donationHistory.php"><i class="fas fa-list"></i> My Donations</a>
        <a href="accountSummary.php"><i class="fas fa-cog"></i> Account Settings</a>
    </nav>
    <a class="logout" href="logout.php"><i class="fas fa-sign-out-alt"></i></a>
</header>

<div class="container">
    <h1>History of Contributions by <?php echo htmlspecialchars($donorName); ?></h1>
    <table>
        <thead>
        <tr>
            <th>Donation ID</th>
            <th>Donation Amount</th>
            <th>Donation Method</th>
            <th>Recipient Bank</th>
            <th>Donation Date</th>
            <th>Status</th>
            <th>Invoice</th>
        </tr>
        </thead>
        <tbody>
        <?php
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $statusClass = strtolower($row['StatusDonation']) === 'completed' ? 'status-complete' : 'status-incomplete';
                $invoiceLink = strtolower($row['StatusDonation']) === 'completed' ? "invoice.php?donation_id=" . urlencode($row['DonationID']) : "#";
                $onclick = strtolower($row['StatusDonation']) === 'completed' ? "" : "onclick=\"showError()\"";
                echo "<tr>";
                echo "<td>". htmlspecialchars($row['DonationID']) ."</td>";
                echo "<td>RM ". htmlspecialchars(number_format($row['DonationAmount'], 2)) ."</td>";
                echo "<td>". htmlspecialchars($row['DonationMethod']) ."</td>";
                echo "<td>". htmlspecialchars($row['DonationSend']) ."</td>";
                echo "<td>". htmlspecialchars($row['DonationDate']) ."</td>";
                echo "<td class='$statusClass'>". htmlspecialchars($row['StatusDonation']) ."</td>";
                echo "<td class='invoice-icon'><a href='$invoiceLink' $onclick><i class='fas fa-file-invoice'></i></a></td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='7'>No donations found.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>

<script>
function showError() {
    Swal.fire({
        icon: 'error',
        title: 'Incomplete Donation',
        text: 'Please complete your donation by uploading your receipt and allocating your donation first.'
    });
}
</script>

</body>
</html>

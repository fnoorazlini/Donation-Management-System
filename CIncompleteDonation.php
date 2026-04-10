<?php
session_start();
include 'dbConnect.php'; // Include the database connection

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: loginClerk.php");
    exit;
}

// Function to fetch incomplete donations
function fetchIncompleteDonations($dbCon) {
    $sql = "SELECT d.DonationID, d.DonorID, donor.DonorName, d.AllocationID, d.DonationAmount, d.DonationMethod, d.DonationPurpose, d.DonationDate, d.ConfirmationReceipt
            FROM DONATION d
            JOIN DONOR donor ON d.DonorID = donor.DonorID
            WHERE d.ConfirmationReceipt = 'not uploaded'
              AND (d.AllocationID IS NULL OR d.AllocationID = 0)
            ORDER BY d.DonationID";
    $result = $dbCon->query($sql);
    return $result;
}

// Fetch incomplete donations
$donations = fetchIncompleteDonations($dbCon);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incomplete Donations</title>
    <link rel="stylesheet" href="css/CDonation.css"> <!-- Link to your CSS file -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Font Awesome CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- SweetAlert CDN -->
    <style>
        .back-btn {
    background-color: #4CAF50; /* Green background */
    color: white;              /* White text */
    padding: 10px 20px;       /* Padding for size */
    text-align: center;        /* Center text */
    text-decoration: none;      /* No underline */
    display: inline-block;      /* Inline-block for spacing */
    font-size: 16px;           /* Font size */
    border: none;              /* No border */
    border-radius: 5px;       /* Rounded corners */
    transition: background-color 0.3s; /* Transition effect */
}

.back-btn:hover {
    background-color: #45a049; /* Darker green on hover */
}

</style>
</head>
<body class="animated fadeIn">

<!-- ======================================== navigation bar ============================================== -->   

<?php include 'CNavigation.php'; ?>

<!-- =========================================== content ================================================= -->    

<div class="container">
<a href="CDonation.php" class="btn back-btn">Back</a>
    <h1>Pending Donations: Successful donations awaiting completion</h1>
    <table>
        <thead>
            <tr>
                <th>Donation ID</th>
                <th>Donor Name</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Date</th>
               
            </tr>
        </thead>
        <tbody>
            <?php while($row = $donations->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['DonationID']; ?></td>
                <td><?php echo $row['DonorName']; ?></td>
                <td><?php echo "RM ".$row['DonationAmount']; ?></td>
                <td><?php echo $row['DonationMethod']; ?></td>
                <td><?php echo $row['DonationDate']; ?></td>
                
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>

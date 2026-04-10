<?php
session_start();
include 'dbConnect.php'; // Include the database connection

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: loginClerk.php");
    exit;
}

// Function to fetch non-cash donations with pagination
function fetchNonCashDonations($dbCon, $start, $limit) {
    $sql = "SELECT d.DonationID, d.DonorID, donor.DonorName, d.AllocationID, allocation.AllocationType, d.DonationAmount, d.DonationMethod, d.DonationPurpose, d.DonationDate, d.ConfirmationReceipt, d.ReadStatus
            FROM DONATION d
            JOIN DONOR donor ON d.DonorID = donor.DonorID
            JOIN ALLOCATION allocation ON d.AllocationID = allocation.AllocationID
            WHERE d.DonationMethod != 'Cash'
            ORDER BY d.DonationDate DESC
            LIMIT ?, ?";
    $stmt = $dbCon->prepare($sql);
    $stmt->bind_param("ii", $start, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result;
}

// Get the total number of non-cash donations
$totalDonationsQuery = "SELECT COUNT(*) AS total FROM DONATION WHERE DonationMethod != 'Cash'";
$totalDonationsResult = $dbCon->query($totalDonationsQuery);
$totalDonations = $totalDonationsResult->fetch_assoc()['total'];

// Define pagination variables
$limit = 6; // Number of donations per page
$totalPages = ceil($totalDonations / $limit);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Fetch non-cash donations for the current page
$donations = fetchNonCashDonations($dbCon, $start, $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clerk Donations</title>
    <link rel="stylesheet" href="css/CDonation.css"> <!-- Link to your CSS file -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Font Awesome CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- SweetAlert CDN -->
    <style>
        /* Add styles for pagination controls */
        .pagination {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }
        .pagination a {
            color: black;
            padding: 8px 16px;
            text-decoration: none;
            border: 1px solid #ddd;
            margin: 0 4px;
        }
        .pagination a.active {
            background-color: #4CAF50;
            color: white;
            border: 1px solid #4CAF50;
        }
        .pagination a:hover:not(.active) {
            background-color: #ddd;
        }
        .incomplete-button {
            margin-bottom: 20px;
        }
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
    <a href="CIncompleteDonation.php" class="btn back-btn">Incomplete Donations</a>
    <h1>Donations</h1>

    <table>
        <thead>
            <tr>
                <th>Donation ID</th>
                <th>Donor Name</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Purpose</th>
                <th>Date</th>
                <th>Receipt</th>
                <th>Read/Unread</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $donations->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['DonationID']; ?></td>
                <td><?php echo $row['DonorName']; ?></td>
                <td><?php echo "RM ".$row['DonationAmount']; ?></td>
                <td><?php echo $row['DonationMethod']; ?></td>
                <td><?php echo $row['DonationPurpose']; ?></td>
                <td><?php echo $row['DonationDate']; ?></td>
                <td>
                    <?php if (!empty($row['ConfirmationReceipt'])): ?>
                        <a href="<?php echo $row['ConfirmationReceipt']; ?>" target="_blank" class="receipt-link">View Receipt</a>
                    <?php else: ?>
                        No Receipt
                    <?php endif; ?>
                </td>
                <td style="font-weight: bold; color: <?php echo ($row['ReadStatus'] === 'Read') ? 'green' : 'red'; ?>">
                    <?php echo ucfirst($row['ReadStatus']); ?>
                </td>
                <td class="actions">
                    <button onclick="viewDonation('<?php echo $row['DonationID']; ?>')" class="view-btn"><i class="fa fa-eye"></i> View</button>
                </td>

            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Pagination controls -->
<div class="pagination">
    <?php for($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?php echo $i; ?>" class="<?php if($page == $i) echo 'active'; ?>"><?php echo $i; ?></a>
    <?php endfor; ?>
</div>

<script>
    function viewDonation(donationID) {
        console.log("Clicked Donation ID: " + donationID);
        window.location.href = "CDonationView.php?donationID=" + donationID;
    }
</script>

</body>
</html>

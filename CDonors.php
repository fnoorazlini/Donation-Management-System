<?php
session_start();
include 'dbConnect.php'; // Include the database connection

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: loginClerk.php");
    exit;
}

// Function to fetch donations that are not made with cash
function fetchDonors($dbCon) {
    $sql = "SELECT *  FROM DONOR ";
    $result = $dbCon->query($sql);
    return $result;
}

// Fetch non-cash donations
$donors = fetchDonors($dbCon);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donors</title>
    <link rel="stylesheet" href="css/CDonation.css"> <!-- Link to your CSS file -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Font Awesome CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- SweetAlert CDN -->
</head>
<body class="animated fadeIn">

<!-- ======================================== navigation bar ============================================== -->   

<?php include 'CNavigation.php'; ?>

<!-- =========================================== content ================================================= -->    

<div class="container">
    <h1>Donors</h1>
    <table>
        <thead>
            <tr>
                <th>Donor ID</th>
                <th>Donor Name</th>
                <th>Email</th>
                <th>Phone Number</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $donors->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['DonorID']; ?></td>
                <td><?php echo $row['DonorName']; ?></td>
                <td><?php echo $row['DonorEmail']; ?></td>
                <td><?php echo $row['DonorPhone']; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>

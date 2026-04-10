<?php
session_start();
require_once "dbConnect.php";

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$donorEmail = $_SESSION["username"];
$donorID = null;

// Retrieve donor ID based on the email
$sql = "SELECT DonorID FROM DONOR WHERE DonorEmail = ?";
$stmt = mysqli_prepare($dbCon, $sql);
mysqli_stmt_bind_param($stmt, "s", $donorEmail);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$donor = mysqli_fetch_assoc($result);
$donorID = $donor['DonorID'];

$success = false;
$error = '';

// Fetch the donation ID from the URL
if (isset($_GET['donation_id'])) {
    $donationID = $_GET['donation_id'];
} else {
    $error = "No donation ID provided.";
}

// Function to fetch donation details by ID
function fetchDonationDetails($dbCon, $donationID) {
    $sql = "SELECT * FROM DONATION WHERE DonationID = ?";
    $stmt = mysqli_prepare($dbCon, $sql);
    mysqli_stmt_bind_param($stmt, "s", $donationID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fund = $_POST["fund"];
    date_default_timezone_set('Asia/Kuala_Lumpur');

    $invoiceDate = date("Y-m-d");
    $invoiceTime = date("H:i:s");
    
    // Generate InvoiceID
    $sql = "SELECT MAX(InvoiceID) as maxInvoiceID FROM DONATION";
    $result = mysqli_query($dbCon, $sql);
    $row = mysqli_fetch_assoc($result);
    $maxInvoiceID = $row['maxInvoiceID'];

    if ($maxInvoiceID) {
        $invoiceNum = (int)substr($maxInvoiceID, 3) + 1;
        $newInvoiceID = "INV" . str_pad($invoiceNum, 3, "0", STR_PAD_LEFT);
    } else {
        $newInvoiceID = "INV001";
    }

    // Retrieve AllocationID based on selected fund
    $sql = "SELECT AllocationID FROM ALLOCATION WHERE AllocationID = ?";
    $stmt = mysqli_prepare($dbCon, $sql);
    mysqli_stmt_bind_param($stmt, "s", $fund);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $allocation = mysqli_fetch_assoc($result);
    $allocationID = $allocation['AllocationID'];

    // Update the DONATION table with allocation details
    $sql = "UPDATE DONATION SET AllocationID = ?, DonationPurpose = (SELECT AllocationType FROM ALLOCATION WHERE AllocationID = ?), 
            InvoiceID = ?, InvoiceDate = ?, InvoiceTime = ? WHERE DonationID = ?";
    $stmt = mysqli_prepare($dbCon, $sql);
    mysqli_stmt_bind_param($stmt, "ssssss", $allocationID, $fund, $newInvoiceID, $invoiceDate, $invoiceTime, $donationID);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        $success = true;
    } else {
        $error = "Error updating donation details: " . mysqli_error($dbCon);
    }
}

// Fetch donation details
$donation = fetchDonationDetails($dbCon, $donationID);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation Process</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
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
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.9);
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

        .logout:hover {
            background-color: #4b0082;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .form-group input[type="text"],
        .form-group select {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .form-group button {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #6FC276;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .form-group button:hover {
            background-color: #56a982;
        }
        .image-gallery {
            display: flex;
            overflow-x: auto;
            padding: 10px 0;
            justify-content: center; /* Center the images */
            align-items: center; /* Align items vertically */
        }

        .image-item {
            text-align: center;
            margin: 0 10px; /* Spacing between images */
        }

        .image-item img {
            width: 200px; /* Adjust as needed */
            height: auto; /* Maintain aspect ratio */
            border-radius: 8px;
            cursor: pointer; /* Indicate that the images are clickable */
        }

        .image-item p {
            margin-top: 5px;
            font-size: 14px;
            color: #555;
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
    <h1>Allocate Your Donation</h1>
    <?php if ($donation): ?>
        <p>Donation ID: <?php echo $donation['DonationID']; ?> - RM <?php echo $donation['DonationAmount']; ?></p>
    <?php else: ?>
        <p><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?donation_id=" . $donationID; ?>" method="post">
        <div class="form-group">
            <label for="fund">Allocate Your Donation:</label>
            <select id="fund" name="fund" required>
                <option value="">Select Allocation</option>
                <?php
                // Fetch allocations from the ALLOCATION table
                $sql = "SELECT AllocationID, AllocationType, Description FROM ALLOCATION";
                $result = mysqli_query($dbCon, $sql);

                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<option value=\"" . $row['AllocationID'] . "\">" . $row['AllocationType'] . " - " . $row['Description'] . "</option>";
                    }
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <button type="submit">Allocate Donation</button>
        </div>
    </form>
</div>

<!-- Image Gallery Section -->
<div class="image-gallery">
    <div class="image-item">
        <img src="images/allocate1.png" alt="Description of image">
        <p>Providing Food Assistance</p>
    </div>
    <div class="image-item">
        <img src="images/allocate2.jpg" alt="Description of another image">
        <p>Facilities Upgrade</p>
    </div>
    <div class="image-item">
        <img src="images/allocate3.png" alt="Description of another image">
        <p>Supporting Educational Programs</p>
    </div>
    <div class="image-item">
        <img src="images/allocate4.jpg" alt="Description of another image">
        <p>Improving Healthcare Facilities</p>
    </div>
    <div class="image-item">
        <img src="images/allocate5.png" alt="Description of another image">
        <p>Enhancing Staff Welfare</p>
    </div>
</div>


<script>
document.addEventListener("DOMContentLoaded", function() {
    <?php if ($success): ?>
        Swal.fire({
            title: 'Success!',
            text: 'You are done with your donation, click OK to get your invoice.',
            icon: 'success',
            confirmButtonText: 'OK'
        }).then(function() {
            window.location.href = 'invoice.php?donation_id=<?php echo $donationID; ?>';
        });
    <?php elseif (isset($error) && $error): ?>
        Swal.fire({
            title: 'Error!',
            text: '<?php echo htmlspecialchars($error); ?>',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    <?php endif; ?>
});
</script>

</body>
</html>

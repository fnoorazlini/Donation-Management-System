<?php 
session_start();
require_once "dbConnect.php"; 

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$donorEmail = $_SESSION["username"]; // Assuming donor email is stored in session username
date_default_timezone_set('Asia/Kuala_Lumpur');

// Retrieve donor ID based on the email
$sql = "SELECT DonorID FROM DONOR WHERE DonorEmail = ?";
$stmt = mysqli_prepare($dbCon, $sql);
mysqli_stmt_bind_param($stmt, "s", $donorEmail);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$donor = mysqli_fetch_assoc($result);
$donorID = $donor['DonorID'];

$success = false; // Flag to determine if the operation was successful

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $donationID = $_POST["donation_id"];
    $fund = $_POST["fund"];
    $donationPurpose = $_POST["donation_purpose"]?? ''; // Set default value to empty string if not set

    // Generate InvoiceID
    $sql = "SELECT MAX(InvoiceID) as maxInvoiceID FROM DONATION";
    $result = mysqli_query($dbCon, $sql);
    $row = mysqli_fetch_assoc($result);
    $maxInvoiceID = $row['maxInvoiceID'];

    if ($maxInvoiceID) {
        $invoiceNum = (int)substr($maxInvoiceID, 3) + 1;
        $newInvoiceID = "INV". str_pad($invoiceNum, 3, "0", STR_PAD_LEFT);
    } else {
        $newInvoiceID = "INV001";
    }

    $invoiceDate = date("Y-m-d");
    $invoiceTime = date("H:i:s");

    // Check if a file was uploaded without errors
    if (isset($_FILES["receipt"]) && $_FILES["receipt"]["error"] == UPLOAD_ERR_OK) {
        $receipt = $_FILES["receipt"];

        // Directory where the receipt will be uploaded
        $targetDir = "uploads/";

        // Ensure the directory exists
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Target file path
        $targetFile = $targetDir. basename($receipt["name"]);

        // Check if file was uploaded without errors
        if (move_uploaded_file($receipt["tmp_name"], $targetFile)) {
            // Update the DONATION table
            $sql = "UPDATE DONATION SET ConfirmationReceipt =?, AllocationID =?, DonationPurpose = (SELECT AllocationType FROM ALLOCATION WHERE AllocationID =?), InvoiceID =?, InvoiceDate =?, InvoiceTime =? WHERE DonationID =?";
            $stmt = mysqli_prepare($dbCon, $sql);
            mysqli_stmt_bind_param($stmt, "sssssss", $targetFile, $fund, $fund, $newInvoiceID, $invoiceDate, $invoiceTime, $donationID);
            mysqli_stmt_execute($stmt);

            // Check if the update was successful
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $success = true;
            } else {
                $error = "Error updating donation details: ". mysqli_error($dbCon);
            }
        } else {
            $error = "Error: Unable to move the uploaded file.";
        }
    } else {
        $error = "Error: ". $_FILES["receipt"]["error"];
    }
}
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
        /* Global Styles */
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
            background-color: rgba(255, 255, 255, 0.7);
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
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-group input[type="file"] {
            margin-top: 10px;
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
        .payment-method {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 10px;
        }
        .payment-method label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        .payment-method label img {
            width: 50px;
            height: auto;
            margin-right: 10px;
            cursor: pointer;
        }
        .payment-method input[type="radio"] {
            display: none;
        }
        .payment-method input[type="radio"] + span {
            font-weight: bold;
        }
        .payment-method input[type="radio"]:checked + span {
            color: #6FC276;
        }
        .payment-details {
            margin-top: 10px;
            padding: 10px;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            border-radius: 4px;
            display: none;
        }
        #qr_code_img {
            width: 200px;
            height: auto;
            display: block;
            margin: 0 auto;
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
    <h1>Please Upload Your Receipt and Choose the Allocation</h1>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="donation_id">Select Donation:</label>
            <select name="donation_id" id="donation_id" required>
                <option value="" disabled selected>Select Donation ID</option>
                <?php
                if ($donorID) {
                    // Fetch donations made by the current donor that do not have a receipt
                    $sql = "SELECT DonationID, DonationAmount FROM DONATION WHERE DonorID = ? AND ConfirmationReceipt = 'not uploaded'";
                    $stmt = mysqli_prepare($dbCon, $sql);
                    mysqli_stmt_bind_param($stmt, "s", $donorID);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);

                    if ($result && mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<option value=\"" . $row['DonationID'] . "\">" . $row['DonationID'] . " - RM " . $row['DonationAmount'] . "</option>";
                        }
                    }
                    mysqli_stmt_close($stmt);
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="receipt">Upload Receipt:</label>
            <input type="file" name="receipt" id="receipt" accept="image/*,application/pdf" required>
        </div>
        <div class="form-group">
            <label for="fund">Fund Allocation:</label>
            <select name="fund" id="fund" required>
                <option value="" disabled selected>Select Fund</option>
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
            <button type="submit">Upload and Allocate</button>
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

<?php
mysqli_close($dbCon);
?>

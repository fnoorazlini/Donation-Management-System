<?php
// Include database connection
require_once 'dbConnect.php';

// Initialize session (if not already started)
session_start();

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Define variables and initialize with empty values
$donationAmount = $donationMethod = $donationPurpose = $donationSend = $paymentOption = "";
$confirmationReceipt = "not uploaded"; // Default value

$donorEmail = $_SESSION["username"]; // Assuming you have donor's email stored in session

// Retrieve DonorID based on donorEmail
$sql = "SELECT DonorID FROM DONOR WHERE DonorEmail = ?";
if ($stmt = mysqli_prepare($dbCon, $sql)) {
    mysqli_stmt_bind_param($stmt, "s", $donorEmail);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $donorID);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
} else {
    echo "Error retrieving DonorID: " . mysqli_error($dbCon);
    header("location: error.php");
    exit;
}

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST)) {
    // Validate donation amount
    if (isset($_POST["amount"]) && !empty(trim($_POST["amount"]))) {
        $donationAmount = trim($_POST["amount"]);
        if (!is_numeric($donationAmount) || $donationAmount <= 0) {
            echo "Error: Invalid donation amount.";
            header("location: error.php");
            exit;
        }
    } else {
        echo "Error: Donation amount is required.";
        header("location: error.php");
        exit;
    }

    // Validate donation send
    if (isset($_POST["donationSend"]) && !empty(trim($_POST["donationSend"]))) {
        $donationSend = trim($_POST["donationSend"]);
        if (!in_array($donationSend, array('bank_islam', 'bank_muamalat', 'jomPay'))) {
            echo "Error: Invalid donation send method.";
            header("location: error.php");
            exit;
        }
        // Set donation method based on donation send
        if ($donationSend === 'bank_islam') {
            $donationMethod = "qrCode";
        } elseif ($donationSend === 'bank_muamalat' || $donationSend === 'jomPay') {
            $donationMethod = "onlinebanking";
        }
    } else {
        echo "Error: Donation send method is required.";
        header("location: error.php");
        exit;
    }

    // Check payment option if not QR code
    if ($donationMethod !== 'qrCode') {
        if (isset($_POST["payment_option"]) && !empty(trim($_POST["payment_option"]))) {
            $paymentOption = trim($_POST["payment_option"]);
        } else {
            echo "Error: Payment option is required.";
            header("location: error.php");
            exit;
        }
    } else {
        $paymentOption = ""; // No payment option needed for QR code
    }

    // Set the donation date to the current date
    $donationDate = date("Y-m-d");

    // Insert donation record into the database
    $insertSQL = "INSERT INTO DONATION (DonationID, DonorID, ClerkID, AllocationID, DonationAmount, DonationMethod, DonationPurpose, DonationDate, PaymentOption, DonationSend, ConfirmationReceipt) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($dbCon, $insertSQL)) {
        // Bind variables to the prepared statement as parameters
        $donationID = "DN01"; // Default value for donation ID
        // Retrieve the highest DonationID from the DONATION table
        $result = mysqli_query($dbCon, "SELECT DonationID FROM DONATION ORDER BY DonationID DESC LIMIT 1");
        if ($row = mysqli_fetch_assoc($result)) {
            // Increment DonationID by 1
            $lastID = $row["DonationID"];
            $numericID = intval(substr($lastID, 2)) + 1; // Extract the numeric part and increment by 1
            $donationID = "DN". str_pad($numericID, 2, "0", STR_PAD_LEFT); // Format as DNXX
        }

        // Bind parameters
        mysqli_stmt_bind_param($stmt, "ssssssdssss", $donationID, $donorID, $clerkID, $allocationID, $donationAmount, $donationMethod, $donationPurpose, $donationDate, $paymentOption, $donationSend, $confirmationReceipt);

        // Execute the prepared statement
        if (mysqli_stmt_execute($stmt)) {
            // Redirect to upload_receipt.php after successful insertion
            header("location: upload_receipt.php");
            exit;
        } else {
            echo "Error: Could not execute query: ". mysqli_stmt_error($stmt);
            header("location: error.php");
            exit;
        }

        // Close statement
        mysqli_stmt_close($stmt);
    } else {
        echo "Error: Could not prepare query: ". mysqli_error($dbCon);
        header("location: error.php");
        exit;
    }

    // Close connection
    mysqli_close($dbCon);
}
?>
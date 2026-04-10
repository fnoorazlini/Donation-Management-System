<?php
session_start(); // Make sure to start the session
require_once 'dbConnect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $donorName = $_POST['donorName'];
    $donorEmail = $_POST['donorEmail'];
    $donorPhone = $_POST['donorPhone'];

    // Update the donor profile
    $query = "UPDATE DONOR SET DonorName = ?, DonorEmail = ?, DonorPhone = ? WHERE DonorEmail = ?";
    $stmt = $dbCon->prepare($query);
    $stmt->bind_param("ssss", $donorName, $donorEmail, $donorPhone, $_SESSION['username']); // Assuming 'username' holds the old email
    if ($stmt->execute()) {
        // Update session if email changed
        $_SESSION['username'] = $donorEmail;
        echo 'Profile updated successfully!';
    } else {
        echo 'Error updating profile!';
    }
    $stmt->close();
} else {
    echo 'Invalid request!';
}
?>

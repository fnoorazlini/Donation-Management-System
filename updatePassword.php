<?php
session_start(); // Make sure to start the session
require_once 'dbConnect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldPassword = $_POST['oldPassword'];
    $newPassword = $_POST['newPassword'];

    $query = "SELECT DonorPass FROM DONOR WHERE DonorEmail = ?";
    $stmt = $dbCon->prepare($query);
    $stmt->bind_param("s", $_SESSION['username']); // Use the correct session variable
    $stmt->execute();
    $stmt->bind_result($hashedPassword);
    $stmt->fetch();
    $stmt->close();

    if (password_verify($oldPassword, $hashedPassword)) {
        $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $query = "UPDATE DONOR SET DonorPass = ? WHERE DonorEmail = ?";
        $stmt = $dbCon->prepare($query);
        $stmt->bind_param("ss", $hashedNewPassword, $_SESSION['username']); // Use the correct session variable
        if ($stmt->execute()) {
            echo 'Password changed successfully!';
        } else {
            echo 'Error changing password!';
        }
        $stmt->close();
    } else {
        echo 'Invalid current password!';
    }
} else {
    echo 'Invalid request!';
}
?>

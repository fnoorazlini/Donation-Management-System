<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Font Awesome CDN -->
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        /* Add animations */
        body.animated {
            animation: fadeIn 1s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        /*=======================================navigation bar===============================================*/
        .nav-bar {
            background-color: #deebab;
            color: black;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
        }

        .logo img {
            height: 60px; /* Adjust the height as needed */
            padding:0;
        }

        .nav-links {
            display: flex;
            position: relative; /* Position relative for dropdown */
        }

        .nav-links a {
            color: black;
            text-decoration: none;
            padding: 10px 20px;
            display: flex;
            align-items: center; /* Center icon and text vertically */
        }

        .nav-links a:hover {
            background-color: rgba(255, 255, 255, 0.6);
        }

        .nav-links a i {
            margin-right: 10px; /* Space between icon and text */
        }

        .badge {
            background-color: red;
            color: white;
            border-radius: 50%;
            padding: 5px 10px;
            margin-left: 5px;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 250px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            right: 0; /* Align dropdown to the right */
        }

        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }
    </style>
</head>
<body>
    <?php

    // Fetch unread donations
    $sql = "SELECT d.DonationID AS NotifyID, donor.DonorName AS NotifyName, d.DonationAmount AS NotifyAmount
            FROM DONATION d
            JOIN DONOR donor ON d.DonorID = donor.DonorID
            WHERE d.ReadStatus = 'Unread' AND ConfirmationReceipt != 'not uploaded'";
    $result = $dbCon->query($sql);

    $unreadCount = $result->num_rows;
    $unreadDonations = [];

    if ($unreadCount > 0) {
        while($row = $result->fetch_assoc()) {
            $unreadDonations[] = $row;
        }
    }
    ?>
    <div class="nav-bar">
        <div class="logo">
            <img src="images/logo.png" alt="Logo">
        </div>
        <div class="nav-links">
            <a href="CDashboard.php"><i class="fa fa-home"></i> Dashboard</a>
            <a href="CInitiative.php"><i class="fa fa-lightbulb"></i> Initiative</a>
            <a href="CDonors.php"><i class="fa fa-user-friends"></i> Donors</a>
            <a href="CDonation.php"><i class="fa fa-donate"></i> Donations</a>
            <a href="CAllocation.php"><i class="fa fa-pie-chart"></i> Cash Allocation</a>
            <a href="CReport.php"><i class="fa fa-file-alt"></i> Reports</a>
            <div class="dropdown">
                <a href="#"><i class="fa fa-bell"></i> Notifications <span id="notification-count" class="badge"><?php echo $unreadCount; ?></span></a>
                <div class="dropdown-content">
                    <?php if ($unreadCount > 0): ?>
                        <?php foreach ($unreadDonations as $notif): ?>
                            <a href="CDonationView.php?donationID=<?php echo $notif['NotifyID']; ?>">
                                <?php echo $notif['NotifyName']; ?> donated RM <?php echo $notif['NotifyAmount']; ?></a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <a href="#">No new donations</a>
                    <?php endif; ?>
                </div>
            </div>
            <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Log Out</a>
        </div>
    </div>
</body>
</html>

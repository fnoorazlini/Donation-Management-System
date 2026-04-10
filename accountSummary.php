<?php
session_start();
require_once "dbConnect.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$donorEmail = $_SESSION["username"];

$query = "SELECT DonorID, DonorName, DonorEmail, DonorPhone FROM DONOR WHERE DonorEmail = ?";
$stmt = $dbCon->prepare($query);
$stmt->bind_param("s", $donorEmail);
$stmt->execute();
$result = $stmt->get_result();
$donor = $result->fetch_assoc();

if (!$donor) {
    echo "Error fetching donor data";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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

        .profile-section {
            margin: 20px 0;
            padding: 20px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .profile-card {
            display: flex;
            align-items: center;
            padding: 20px;
        }

        .profile-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 20px;
        }

        .profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-details {
            flex: 1;
        }

        .profile-details table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .profile-details table th,
        .profile-details table td {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        .profile-details table th {
            background-color: #588c7e;
            color: white;
        }

        .profile-details table td {
            background-color: #f9f9f9;
        }

        .update-btn,
        .change-password-btn {
            background-color: #588c7e;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .update-btn:hover,
        .change-password-btn:hover {
            background-color: #96ceb4;
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
        <a href="accountSettings.php"><i class="fas fa-cog"></i> Account Settings</a>
    </nav>
    <a class="logout" href="logout.php"><i class="fas fa-sign-out-alt"></i></a>
</header>

<div class="container">
    <div class="profile-section">
        <h2>Profile Information</h2>
        <div class="profile-card">
            <div class="profile-details">
                <table>
                    <tr>
                        <th>ID</th>
                        <td><?php echo htmlspecialchars($donor['DonorID']);?></td>
                    </tr>
                    <tr>
                        <th>Name</th>
                        <td><?php echo htmlspecialchars($donor['DonorName']);?></td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td><?php echo htmlspecialchars($donor['DonorEmail']);?></td>
                    </tr>
                    <tr>
                        <th>Phone</th>
                        <td><?php echo htmlspecialchars($donor['DonorPhone']);?></td>
                    </tr>
                </table>
            </div>
        </div>
        <button class="update-btn" onclick="showUpdateForm()">Update Profile</button>
        <button class="change-password-btn" onclick="showChangePasswordForm()">Change Password</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function showUpdateForm() {
    Swal.fire({
        title: 'Update Profile',
        html: `
            <form id="updateProfileForm" method="POST" action="updateProfile.php">
                <input type="text" id="donorName" name="donorName" class="swal2-input" placeholder="Name" value="<?php echo htmlspecialchars($donor['DonorName']); ?>">
                <input type="email" id="donorEmail" name="donorEmail" class="swal2-input" placeholder="Email" value="<?php echo htmlspecialchars($donor['DonorEmail']); ?>">
                <input type="text" id="donorPhone" name="donorPhone" class="swal2-input" placeholder="Phone" value="<?php echo htmlspecialchars($donor['DonorPhone']); ?>">
            </form>
        `,
        focusConfirm: false,
        preConfirm: () => {
            const formData = new FormData(document.getElementById('updateProfileForm'));
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'updateProfile.php', true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Profile successfully updated',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = 'accountSettings.php';
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Error updating profile',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            };
            xhr.send(formData);
        },
        showCancelButton: true,
        confirmButtonText: 'Update',
        cancelButtonText: 'Cancel'
    });
}

function showChangePasswordForm() {
    Swal.fire({
        title: 'Change Password',
        html: `
            <form id="changePasswordForm">
                <input type="password" id="oldPassword" name="oldPassword" class="swal2-input" placeholder="Current Password">
                <input type="password" id="newPassword" name="newPassword" class="swal2-input" placeholder="New Password">
                <input type="password" id="confirmNewPassword" name="confirmNewPassword" class="swal2-input" placeholder="Confirm New Password">
            </form>
        `,
        focusConfirm: false,
        preConfirm: () => {
            const oldPassword = document.getElementById('oldPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmNewPassword = document.getElementById('confirmNewPassword').value;

            if (newPassword.length < 6) {
                Swal.showValidationMessage('Password must be at least 6 characters!');
                return false;
            }

            if (newPassword !== confirmNewPassword) {
                Swal.showValidationMessage('Passwords do not match!');
                return false;
            }

            const formData = new FormData(document.getElementById('changePasswordForm'));
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'updatePassword.php', true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Password successfully changed',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.href = 'accountSummary.php';
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: response.message,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Error changing password',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            };
            xhr.send(formData);
        },
        showCancelButton: true,
        confirmButtonText: 'Change Password',
        cancelButtonText: 'Cancel'
    });
}
</script>
</body>
</html>
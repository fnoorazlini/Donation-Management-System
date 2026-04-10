<?php
require_once "dbConnect.php";

$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate password length
    if (strlen($new_password) < 6) {
        $errorMessage = "Password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $errorMessage = "Passwords do not match.";
    } else {
        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

        // Check if the email exists in any of the tables
        $sql = "SELECT DonorID AS UserID, DonorEmail AS Email, 'Donor' AS UserType FROM DONOR WHERE DonorEmail = ?
                UNION
                SELECT PrincipalID AS UserID, PrincipalEmail AS Email, 'Principal' AS UserType FROM PRINCIPAL WHERE PrincipalEmail = ?";

        if ($stmt = mysqli_prepare($dbCon, $sql)) {
            mysqli_stmt_bind_param($stmt, "ss", $email, $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) > 0) {
                mysqli_stmt_bind_result($stmt, $userID, $userEmail, $userType);
                mysqli_stmt_fetch($stmt);

                if ($userType == 'Donor') {
                    $updateSql = "UPDATE DONOR SET DonorPass = ? WHERE DonorEmail = ?";
                } elseif ($userType == 'Principal') {
                    $updateSql = "UPDATE PRINCIPAL SET PrincipalPass = ? WHERE PrincipalEmail = ?";
                }

                if ($updateStmt = mysqli_prepare($dbCon, $updateSql)) {
                    mysqli_stmt_bind_param($updateStmt, "ss", $hashedPassword, $userEmail);
                    mysqli_stmt_execute($updateStmt);

                    // Redirect to home.php after successfully resetting the password
                    header("Location: home.php");
                    exit();
                }
            } else {
                $errorMessage = "No account found with that email address.";
            }

            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            width: 30%;
            margin: auto;
            background: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-top: 100px;
            text-align: center;
        }
        .form-group {
            margin: 15px 0;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
        }
        .submit-btn {
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            cursor: pointer;
        }
        .submit-btn:hover {
            background-color: #218838;
        }
        .message {
            color: red;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Reset Password</h2>
        <form action="forgetPassword.php" method="post" onsubmit="return validatePasswords()">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="submit-btn">Reset Password</button>
        </form>
        <?php
        if (!empty($errorMessage)) {
            echo "<div class='message'>$errorMessage</div>";
        }
        ?>
    </div>

    <script>
        function validatePasswords() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (newPassword !== confirmPassword) {
                document.querySelector('.message').innerText = 'Passwords do not match.';
                return false;
            }

            return true;
        }
    </script>
</body>
</html>

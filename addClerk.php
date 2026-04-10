<?php
session_start();
include 'dbConnect.php';

// Redirect to login page if not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: loginPrincipal.php");
    exit;
}

$PrincipalID = $_SESSION["username"];

// Define variables and initialize with empty values
$ClerkID = $ClerkPass = $ClerkName = $ClerkEmail = $ClerkAddress = "";
$clerk_id_err = $clerk_pass_err = $clerk_name_err = $clerk_email_err = $clerk_address_err = "";

// Function to generate next Clerk ID
function generateNextClerkID($dbCon) {
    $sql = "SELECT MAX(CAST(SUBSTRING(ClerkID, 2) AS UNSIGNED)) AS max_id FROM CLERK";
    $result = mysqli_query($dbCon, $sql);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $max_id = (int) $row['max_id'];
        $newID = 'C' . str_pad($max_id + 1, 2, '0', STR_PAD_LEFT);
    } else {
        $newID = 'C01';
    }

    return $newID;
}

// Auto-generate Clerk ID
$ClerkID = generateNextClerkID($dbCon);

// Default password for all clerks
$ClerkPass = "staff";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate Clerk Name
    $input_clerk_name = isset($_POST["ClerkName"]) ? trim($_POST["ClerkName"]) : '';
    if (empty($input_clerk_name)) {
        $clerk_name_err = "Please enter Clerk Name.";
    } else {
        $ClerkName = $input_clerk_name;
    }

    // Validate Clerk Email
    $input_clerk_email = isset($_POST["ClerkEmail"]) ? trim($_POST["ClerkEmail"]) : '';
    if (empty($input_clerk_email)) {
        $clerk_email_err = "Please enter Clerk Email.";
    } elseif (!filter_var($input_clerk_email, FILTER_VALIDATE_EMAIL)) {
        $clerk_email_err = "Invalid email format.";
    } else {
        $ClerkEmail = $input_clerk_email;
    }

    // Validate Clerk Address
    $input_clerk_address = isset($_POST["ClerkAddress"]) ? trim($_POST["ClerkAddress"]) : '';
    if (empty($input_clerk_address)) {
        $clerk_address_err = "Please enter Clerk Address.";
    } else {
        $ClerkAddress = $input_clerk_address;
    }

    // Check input errors before inserting in database
    if (empty($clerk_name_err) && empty($clerk_email_err) && empty($clerk_address_err)) {
        // Prepare an insert statement
        $sql = "INSERT INTO CLERK (ClerkID, ClerkPass, ClerkName, ClerkEmail, ClerkAddress) VALUES (?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($dbCon, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "sssss", $param_clerk_id, $param_clerk_pass, $param_clerk_name, $param_clerk_email, $param_clerk_address);

            // Set parameters
            $param_clerk_id = $ClerkID;
            $param_clerk_pass = password_hash($ClerkPass, PASSWORD_DEFAULT); // Hashed password
            $param_clerk_name = $ClerkName;
            $param_clerk_email = $ClerkEmail;
            $param_clerk_address = $ClerkAddress;

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Success message handled by JavaScript
                $success_message = "Clerk added successfully.";
            } else {
                echo "Something went wrong. Please try again later.";
            }
        
            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    // Close connection
    mysqli_close($dbCon);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Clerk</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .navbar {
            background: linear-gradient(to right, #4CAF50, #8E44AD);
            color: white;
            padding: 10px 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar img {
            height: 60px;
        }
        .navbar-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            margin-right: auto;
        }
        .navbar-links {
            display: flex;
            gap: 30px;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 10px 15px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            display: flex; /* Ensure icons align properly */
            align-items: center; /* Center icon vertically */
        }
        .navbar a:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        .dropdown {
            position: relative;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: white;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            z-index: 1;
            border-radius: 5px;
            overflow: hidden;
            top: 100%;
            left: 0;
            min-width: 160px;
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
        .container {
            padding: 40px;
            text-align: center;
        }
        .welcome {
            font-size: 2rem;
            margin-bottom: 20px;
            background: linear-gradient(to right, #4CAF50, #8E44AD);
            color: white;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
        }
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .form-group label {
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            font-size: 1rem;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .form-group .help-block {
            color: red;
        }
        .form-group button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }
        .form-group button:hover {
            background-color: #45a049;
        }
        .cancel-link {
            margin-top: 10px;
            display: inline-block;
            color: #8E44AD;
            text-decoration: none;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <img src="images/logo.png" alt="Logo">
        <div class="navbar-title">MA'AHAD TAHFIZ WAL TARBIYAH DARUL IMAN</div>
        <div class="navbar-links">
            <a href="principalDashboard.php"><i class="fas fa-chart-line icon"></i> Dashboard</a>
            <a href="addClerk.php"><i class="fas fa-user-plus icon"></i> Add Clerk</a>
            <div class="dropdown">
                <a href="#"><i class="fas fa-file-alt icon"></i> View Report <i class="fas fa-caret-down"></i></a>
                <div class="dropdown-content">
                    <a href="allocationReport.php">Allocation Report</a>
                    <a href="donorReport.php">Donor Report</a>
                    <a href="summaryReport.php">Summary Report</a>
                </div>
            </div>
            <a href="loginPrincipal.php"><i class="fas fa-sign-out-alt icon"></i> Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="welcome">Add New Clerk</div>

        <div class="form-container">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group <?php echo (!empty($clerk_id_err)) ? 'has-error' : ''; ?>">
                    <label>Clerk ID</label>
                    <input type="text" name="ClerkID" class="form-control" value="<?php echo $ClerkID; ?>" readonly>
                    <span class="help-block"><?php echo $clerk_id_err; ?></span>
                </div>
                <div class="form-group <?php echo (!empty($clerk_pass_err)) ? 'has-error' : ''; ?>">
                    <label>Password</label>
                    <input type="password" name="ClerkPass" class="form-control" value="staff" readonly>
                    <span class="help-block"><?php echo $clerk_pass_err; ?></span>
                </div>
                <div class="form-group <?php echo (!empty($clerk_name_err)) ? 'has-error' : ''; ?>">
                    <label>Clerk Name</label>
                    <input type="text" name="ClerkName" class="form-control" value="<?php echo $ClerkName; ?>">
                    <span class="help-block"><?php echo $clerk_name_err; ?></span>
                </div>
                <div class="form-group <?php echo (!empty($clerk_email_err)) ? 'has-error' : ''; ?>">
                    <label>Clerk Email</label>
                    <input type="text" name="ClerkEmail" class="form-control" value="<?php echo $ClerkEmail; ?>">
                    <span class="help-block"><?php echo $clerk_email_err; ?></span>
                </div>
                <div class="form-group <?php echo (!empty($clerk_address_err)) ? 'has-error' : ''; ?>">
                    <label>Clerk Address</label>
                    <input type="text" name="ClerkAddress" class="form-control" value="<?php echo $ClerkAddress; ?>">
                    <span class="help-block"><?php echo $clerk_address_err; ?></span>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Submit</button>
                    <a href="principalDashboard.php" class="cancel-link">Cancel</a>
                </div>
            </form>
        </div>
    </div>

     <?php if (isset($success_message)): ?>
    <script>
        Swal.fire({
            title: 'Success!',
            text: '<?php echo $success_message; ?>',
            icon: 'success',
            confirmButtonText: 'OK'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location = 'principalDashboard.php';
            } else {
                window.location = 'principalDashboard.php';
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
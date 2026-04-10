<?php
session_start();
include 'dbConnect.php';

// Redirect to login page if not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: loginPrincipal.php");
    exit;
}

$PrincipalID = $_SESSION["username"];

// Process delete request
$deletionSuccess = false;
$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_id"])) {
    $delete_id = $_POST["delete_id"];

    // Prepare a delete statement
    $delete_sql = "DELETE FROM CLERK WHERE ClerkID = ?";

    if ($stmt = $dbCon->prepare($delete_sql)) {
        // Bind variables to the prepared statement as parameters
        $stmt->bind_param("s", $delete_id);

        // Attempt to execute the prepared statement
        try {
            if ($stmt->execute()) {
                $deletionSuccess = true;
            } else {
                $errorMessage = "Failed to delete the clerk. Please try again.";
            }
        } catch (mysqli_sql_exception $exception) {
            // Check for specific MySQL error code for foreign key constraint violation
            if ($exception->getCode() == 1451) {
                $errorMessage = "This clerk cannot be deleted due to existing references in other tables.";
            } else {
                $errorMessage = "An error occurred while deleting the clerk.";
            }
        }

        // Close statement
        $stmt->close();
    } else {
        $errorMessage = "An error occurred while preparing the delete statement.";
    }
}

// Query to retrieve all clerks
$select_sql = "SELECT ClerkID, ClerkName, ClerkEmail, ClerkAddress FROM CLERK";
$result = $dbCon->query($select_sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Clerks</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Custom Styles -->
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
            display: flex;
            align-items: center;
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
        .table-container {
            margin-top: 20px;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .btn-group .btn {
            margin-right: 5px;
        }
        .help-block {
            color: red;
        }
        .back-icon {
            margin: 20px 0;
            display: flex;
            align-items: center;
            font-size: 1.25rem;
            color: #4CAF50;
            text-decoration: none;
        }
        .back-icon i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <img src="images/logo.png" alt="Logo">
        <div class="navbar-title">MA'AHAD TAHFIZ WAL TARBIYAH DARUL IMAN</div>
        <div class="navbar-links">
            <a href="principalDashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="addClerk.php"><i class="fas fa-user-plus"></i> Add Clerk</a>
            <div class="dropdown">
                <a href="#"><i class="fas fa-file-alt"></i> View Report <i class="fas fa-caret-down"></i></a>
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
        <a href="principalDashboard.php" class="back-icon"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        <div class="welcome">View Clerks</div>

        <div class="table-container">
            <table class="table table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th scope="col">Clerk ID</th>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Address</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row["ClerkID"]) . "</td>";
                            echo "<td>" . htmlspecialchars($row["ClerkName"]) . "</td>";
                            echo "<td>" . htmlspecialchars($row["ClerkEmail"]) . "</td>";
                            echo "<td>" . htmlspecialchars($row["ClerkAddress"]) . "</td>";
                            echo "<td>";
                            echo "<div class='btn-group' role='group'>";
                            echo "<button type='button' class='btn btn-danger' onclick='confirmDelete(\"" . htmlspecialchars($row["ClerkID"]) . "\")'>Delete</button>";
                            echo "</div>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5'>No clerks found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <form id="deleteForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="display:none;">
        <input type="hidden" name="delete_id" id="delete_id">
    </form>

    <!-- Bootstrap and Font Awesome JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <!-- SweetAlert JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmDelete(clerkID) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete_id').value = clerkID;
                    document.getElementById('deleteForm').submit();
                }
            })
        }

        <?php if ($deletionSuccess): ?>
            Swal.fire({
                icon: 'success',
                title: 'Deleted!',
                text: 'The clerk has been deleted.',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                window.location.href = 'viewClerk.php';
            });
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '<?php echo $errorMessage; ?>',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
        <?php endif; ?>
    </script>
</body>
</html>

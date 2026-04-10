<?php
session_start();
include 'dbConnect.php';

// Redirect to login page if not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: loginPrincipal.php");
    exit;
}

$PrincipalID = $_SESSION["username"];

// Initialize variables for pagination
$records_per_page = 5;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start_from = ($page - 1) * $records_per_page;

// Initialize variable to store search parameter
$searchTerm = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and store form input
    $searchTerm = htmlspecialchars($_POST["search_term"]);

    // Construct the WHERE clause of SQL query based on provided filter
    $whereClause = "WHERE 1=1"; // Default condition

    if (!empty($searchTerm)) {
        // Adjust SQL query to search across multiple columns dynamically
        $whereClause .= " AND (";
        $whereClause .= " r.ReportID LIKE '%" . $searchTerm . "%'";
        $whereClause .= " OR r.ReportName LIKE '%" . $searchTerm . "%'";
        $whereClause .= " OR r.ReportDate LIKE '%" . $searchTerm . "%'";
        // Add more columns as needed

        $whereClause .= ")";
    }

    // Fetch summary report data from the database with filter applied
    $sql = "SELECT 
                r.ReportID,
                r.ReportName,
                r.ReportDate,
                c.ClerkName
            FROM report r
            LEFT JOIN clerk c ON r.ClerkID = c.ClerkID
            " . $whereClause . "
            AND r.ReportName LIKE '%summary%'
            ORDER BY r.ReportDate DESC
            LIMIT $start_from, $records_per_page";
    $result = $dbCon->query($sql);
} else {
    // Fetch all summary reports if no filters are applied initially
    $sql = "SELECT 
                r.ReportID,
                r.ReportName,
                r.ReportDate,
                c.ClerkName
            FROM report r
            LEFT JOIN clerk c ON r.ClerkID = c.ClerkID
            WHERE r.ReportName LIKE '%summary%'
            ORDER BY r.ReportDate DESC
            LIMIT $start_from, $records_per_page";
    $result = $dbCon->query($sql);
}

// Count total number of records
$total_records_sql = "SELECT COUNT(*) AS total FROM report r LEFT JOIN clerk c ON r.ClerkID = c.ClerkID WHERE r.ReportName LIKE '%summary%'";
if (!empty($searchTerm)) {
    $total_records_sql .= " AND (";
    $total_records_sql .= " r.ReportID LIKE '%" . $searchTerm . "%'";
    $total_records_sql .= " OR r.ReportName LIKE '%" . $searchTerm . "%'";
    $total_records_sql .= " OR r.ReportDate LIKE '%" . $searchTerm . "%'";
    // Add more columns as needed
    $total_records_sql .= ")";
}
$total_records_result = $dbCon->query($total_records_sql);
$total_records = $total_records_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Summary Report</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Keep the existing CSS from donorReport.php */
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
        .navbar-logo {
            display: flex;
            align-items: center;
        }
        .navbar-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            margin: 0;
            color: white;
            margin-left: 10px; /* Adjust margin as needed */
        }
        .navbar-links {
            display: flex;
            gap: 20px;
            align-items: center;
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
            padding: 20px;
        }
        .report-title {
            text-align: center;
            margin-bottom: 20px;
        }
        .report-title h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #4CAF50;
        }
        .table-container {
            max-width: 900px;
            margin: 20px auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
            font-weight: 500;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        form {
            margin-bottom: 20px;
            background-color: #f1f1f1;
            padding: 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
        }
        form label {
            margin-right: 10px;
            font-weight: 500;
        }
        form input[type="text"] {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-right: 10px;
            width: 300px; /* Adjust width as needed */
        }
        form button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        form button:hover {
            background-color: #45a049;
        }
        .pagination {
            margin-top: 20px;
            text-align: center;
        }
        .pagination a {
            color: #4CAF50;
            padding: 8px 16px;
            text-decoration: none;
            transition: background-color 0.3s;
            border: 1px solid #ddd;
            margin: 0 4px;
            border-radius: 5px;
        }
        .pagination a.active {
            background-color: #4CAF50;
            color: white;
            border: 1px solid #4CAF50;
        }
        .pagination a:hover:not(.active) {
            background-color: #ddd;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-logo">
            <img src="images/logo.png" alt="Logo">
            <div class="navbar-title">MA'AHAD TAHFIZ WAL TARBIYAH DARUL IMAN</div>
        </div>
        <div class="navbar-links">
            <a href="principalDashboard.php"><i class="fas fa-chart-line icon"></i> Dashboard</a>
            <a href="addClerk.php"><i class="fas fa-user-plus icon"></i> Add Clerk</a>
            <div class="dropdown">
                <a href="#"><i class="fas fa-file-alt icon"></i> Reports <i class="fa fa-caret-down"></i></a>
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
        <div class="report-title">
            <h2>Summary Report</h2>
        </div>

        <!-- Search Form -->
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <label for="search_term">Search:</label>
            <input type="text" id="search_term" name="search_term" value="<?php echo $searchTerm; ?>">
            <button type="submit">Search</button>
        </form>

        <!-- Table to display reports -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Report ID</th>
                        <th>Report Name</th>
                        <th>Date Generated</th>
                        <th>Generated By</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row['ReportID'] . "</td>";
                            echo "<td>" . $row['ReportName'] . "</td>";
                            echo "<td>" . $row['ReportDate'] . "</td>";
                            echo "<td>";
                            if (isset($row['ClerkName'])) {
                                echo $row['ClerkName'];
                            } else {
                                echo "N/A"; // Display N/A if ClerkName is not set
                            }
                            echo "</td>";
                            echo "<td><a href='viewReport.php?reportID=" . $row['ReportID'] . "'>View</a></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5'>No records found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Links -->
        <div class="pagination">
            <?php
            // Pagination links
            for ($i = 1; $i <= $total_pages; $i++) {
                echo "<a href='summaryReport.php?page=" . $i . "'";
                if ($i == $page) {
                    echo " class='active'";
                }
                echo ">" . $i . "</a>";
            }
            ?>
        </div>
    </div>
</body>
</html>

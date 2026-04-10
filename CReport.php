<?php
session_start();
include 'dbConnect.php';

// Redirect to login page if not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: loginClerk.php");
    exit;
}

// Check if all necessary parameters are present
if (isset($_GET['reportType'], $_GET['startDate'], $_GET['endDate'], $_GET['reportName'])) {
    $currentDate = date('Y-m-d'); // Format: YYYY-MM-DD
    $clerkID = $_SESSION['clerk_id']; // Retrieved from session or login

    // Retrieve form data from URL parameters
    $startDate = $_GET['startDate'];
    $endDate = $_GET['endDate'];
    $reportType = $_GET['reportType'];
    $reportName = $_GET['reportName'];

    // Generate Report ID (adjust your generation logic as needed)
    $query = "SELECT MAX(SUBSTRING(ReportID, 3)) AS max_id FROM REPORT";
    $result = mysqli_query($dbCon, $query);
    $row = mysqli_fetch_assoc($result);
    $max_id = $row['max_id'];
    $new_id = intval($max_id) + 1;
    $reportID = 'R' . sprintf('%03d', $new_id);

    // Insert report data into database
    $sql = "INSERT INTO REPORT (ReportID, ClerkID, PrincipalID, StartDate, EndDate, ReportType, ReportName, ReportDate) 
            VALUES (?, ?, 'P01', ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($dbCon, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssssss", $reportID, $clerkID, $startDate, $endDate, $reportType, $reportName, $currentDate);
        $execution = mysqli_stmt_execute($stmt);
        if ($execution) {
            // Redirect to report view page with report ID
            header("Location: CReportView.php?ReportID=$reportID");
            exit;
        } else {
            echo "Failed to execute database query.";
        }
    } else {
        echo "Prepare statement error: " . mysqli_error($dbCon);
    }
} 

// Function to fetch Donors report
function fetchDonors($dbCon) {
    $sql = "SELECT r.*, c.ClerkName 
            FROM REPORT r
            JOIN CLERK c ON r.ClerkID = c.ClerkID
            WHERE r.ReportType = 'Donors'
            ORDER BY r.ReportID DESC ";
    $result = $dbCon->query($sql);
    return $result;
}

// Function to fetch Allocation report
function fetchAllocation($dbCon) {
    $sql = "SELECT r.*, c.ClerkName 
            FROM REPORT r
            JOIN CLERK c ON r.ClerkID = c.ClerkID
            WHERE r.ReportType = 'Allocation'
            ORDER BY r.ReportID DESC ";
    $result = $dbCon->query($sql);
    return $result;
}

// Function to fetch Summary report
function fetchSummary($dbCon) {
    $sql = "SELECT r.*, c.ClerkName 
            FROM REPORT r
            JOIN CLERK c ON r.ClerkID = c.ClerkID
            WHERE r.ReportType = 'Summary'";
    $result = $dbCon->query($sql);
    return $result;
}

$donors = fetchDonors($dbCon);
$allocation = fetchAllocation($dbCon);
$summary = fetchSummary($dbCon);

?>

<!DOCTYPE html>
<html lang="en">
<head>  
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link rel="stylesheet" href="css/CReport.css"> <!-- Link to the new CSS file for report generation -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Font Awesome CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- SweetAlert CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- jQuery CDN -->
    <style>
        body {
            padding-left: 10%;
            padding-right: 10%;
        }

        .container {
            position: relative; /* Ensure container is relative for absolute positioning inside */
            width: 80%;
            margin: auto;
            overflow: hidden;
            margin-top: 20px;
            padding: 20px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            animation: slideIn 1s forwards;
        }

        .dashboard-content {
            padding: 20px;
        }

        .dashboard-content h2 {
            font-size: 2em;
            margin-bottom: 20px;
        }

        .metrics-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
            margin-top: 50px;
        }

        h1 {
            color: #333;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #deebab;
            color: black;
        }

        .metric-box {
            background-color: #f0f0f0;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            flex: 1;
            margin: 0 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            animation: slideIn 1s forwards;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            max-width: 200px;
        }

        .metric-box:hover {
            transform: scale(0.95);
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.4);
        }

        .metric-box i {
            color: #757b5f;
        }

        .report-list {
            display: none;
            margin-top: 20px;
        }

        .report-list h3{
            text-align: center;
        }

        .report-list.active {
            display: block;
        }

        tr[data-href] {
            cursor: pointer;
            transition: background-color 0.3s;
        }

        tr[data-href]:hover {
            background-color: #f5f5f5;
        }

        .search-container {
            display: flex;
            justify-content: space-between; /* Align contents to the ends */
            align-items: center;
            margin-bottom: 20px;
            text-align: center;
        }

        .search-wrapper {
            flex: 1; /* Take up remaining space */
            max-width: 60%; /* Adjust maximum width of search bar */
        }

        .search-container input[type=text] {
            width: 60%; /* Occupy full width of search wrapper */
            padding: 10px;
            margin-top: 8px;
            margin-bottom: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
            margin-left: 395px;
        }

        .button-container {
            margin-left: 10px; /* Adjust the space between search bar and button */
        }

        .generate-report-button {
            padding: 10px 20px;
            background-color: #007bff; /* Adjust background color as needed */
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .generate-report-button:hover {
            background-color: #0056b3; /* Darker shade for hover */
        }

        .generate-report-button i {
            margin-right: 5px; /* Adjust space between icon and text */
        }
        /* Slide-in animation for metric boxes and chart */
        @keyframes slideIn {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body class="animated fadeIn">

<!-- ======================================== navigation bar ============================================== -->   

<?php include 'CNavigation.php'; ?>

<!-- =========================================== content ================================================= -->    

<div class="container">
    <h1>Reports</h1>

    <div class="metrics-container">
        <div class="metric-box" id="allocation-reports">
            <i class="fas fa-file-alt fa-2x"></i>
            <h3>Allocation Reports</h3>
        </div>
        <div class="metric-box" id="donor-reports">
            <i class="fas fa-hand-holding-usd fa-2x"></i>
            <h3>Donor Reports</h3>
        </div>
        <div class="metric-box" id="summary-reports">
            <i class="fas fa-chart-line fa-2x"></i>
            <h3>Summary Reports</h3>
        </div>
    </div>

    <div class="search-container">
        <div class="search-wrapper">
            <input type="text" id="reportSearch" onkeyup="searchReports()" placeholder="Search for report names...">
        </div>
        <button class="generate-report-button" id="generateReportBtn">
            <i class="fas fa-file-alt"></i> Generate Report
        </button>
    </div>

<!-- =========================================== Allocation report ================================================= -->   

    <div class="report-list" id="allocation-report-list">
        <h3>Allocation Reports</h3>
        <table id="allocationTable" class="report-table">
            <thead>
                <tr>
                    <th>Report ID</th>
                    <th>Report Name</th>
                    <th>Date Generated</th>
                    <th>Generated By</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $allocation->fetch_assoc()): ?>
                <tr data-href="CReportView.php?ReportID=<?php echo $row['ReportID']; ?>" id="allocation_<?php echo $row['ReportID']; ?>">
                    <td><?php echo $row['ReportID']; ?></td>
                    <td><?php echo $row['ReportName']; ?></td>
                    <td><?php echo $row['ReportDate']; ?></td>
                    <td><?php echo $row['ClerkName']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

<!-- =========================================== Donors report ================================================= -->   

    <div class="report-list" id="donor-report-list">
        <h3>Donor Reports</h3>
        <table id="donorTable" class="report-table">
            <thead>
                <tr>
                    <th>Report ID</th>
                    <th>Report Name</th>
                    <th>Date Generated</th>
                    <th>Generated By</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $donors->fetch_assoc()): ?>
                <tr data-href="CReportView.php?ReportID=<?php echo $row['ReportID']; ?>" id="donor_<?php echo $row['ReportID']; ?>">
                    <td><?php echo $row['ReportID']; ?></td>
                    <td><?php echo $row['ReportName']; ?></td>
                    <td><?php echo $row['ReportDate']; ?></td>
                    <td><?php echo $row['ClerkName']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

<!-- =========================================== Summary report ================================================= -->   

    <div class="report-list" id="summary-report-list">
        <h3>Summary Reports</h3>
        <table id="summaryTable" class="report-table">
            <thead>
                <tr>
                    <th>Report ID</th>
                    <th>Report Name</th>
                    <th>Date Generated</th>
                    <th>Generated By</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $summary->fetch_assoc()): ?>
                <tr data-href="CReportView.php?ReportID=<?php echo $row['ReportID']; ?>" id="summary_<?php echo $row['ReportID']; ?>">
                    <td><?php echo $row['ReportID']; ?></td>
                    <td><?php echo $row['ReportName']; ?></td>
                    <td><?php echo $row['ReportDate']; ?></td>
                    <td><?php echo $row['ClerkName']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Toggle active class on report lists
    document.getElementById('allocation-reports').addEventListener('click', function() {
        toggleReportList('allocation-report-list');
    });

    document.getElementById('donor-reports').addEventListener('click', function() {
        toggleReportList('donor-report-list');
    });

    document.getElementById('summary-reports').addEventListener('click', function() {
        toggleReportList('summary-report-list');
    });

    function toggleReportList(listId) {
        document.querySelectorAll('.report-list').forEach(function(list) {
            if (list.id === listId) {
                list.classList.toggle('active');
            } else {
                list.classList.remove('active');
            }
        });
    }

    // Search function
    function searchReports() {
        var input, filter, tables, rows, cells, i, txtValue;
        input = document.getElementById("reportSearch");
        filter = input.value.toUpperCase();
        tables = document.getElementsByClassName("report-table");

        // Loop through all tables and hide those that don't match the search query
        for (var table of tables) {
            rows = table.getElementsByTagName("tr");
            for (i = 0; i < rows.length; i++) {
                cells = rows[i].getElementsByTagName("td");
                for (var cell of cells) {
                    if (cell) {
                        txtValue = cell.textContent || cell.innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            rows[i].style.display = "";
                            break;
                        } else {
                            rows[i].style.display = "none";
                        }
                    }
                }
            }
        }
    }

    // Redirect on row click
    document.querySelectorAll('tr[data-href]').forEach(function(row) {
        row.addEventListener('click', function() {
            window.location.href = row.dataset.href;
        });
    });

    // SweetAlert for generating report
    document.getElementById('generateReportBtn').addEventListener('click', function() {
    Swal.fire({
        title: 'Generate Report',
        html: `<div class="form-group">
                    <label for="report-type">Report Type</label>
                    <select id="report-type" name="report-type">
                        <option value="" disabled selected>Select an option</option>
                        <option value="Allocation">Allocation Report</option>
                        <option value="Donors">Donors Report</option>
                        <option value="Summary">Summary Report</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="report-schedule">Report Schedule</label>
                    <select id="report-schedule" name="report-schedule">
                        <option value="" disabled selected>Select an option</option>
                        <option value="Monthly">Monthly</option>
                        <option value="Annual">Annual</option>
                    </select>
                </div>
                <div id="monthlyOptions" style="display: none;" class="form-group">
                    <label for="month-year">Select Month</label>
                    <input type="month" id="month-year" name="month-year">
                </div>
                <div id="annualOptions" style="display: none;" class="form-group">
                    <label for="year">Select Year</label>
                    <input type="number" id="year" name="year" min="2000" max="2100" step="1" value="2024">
                </div>
                <div class="form-group">
                    <label for="generated-report-name">Report Title</label>
                    <input type="text" id="generated-report-name" name="generated-report-name" readonly>
                </div>`,
        showCloseButton: true,
        showCancelButton: true,
        focusConfirm: false,
        confirmButtonText: 'Generate',
        confirmButtonAriaLabel: 'Generate',
        preConfirm: () => {
            const reportType = document.getElementById('report-type').value;
            const reportSchedule = document.getElementById('report-schedule').value;
            const monthYear = document.getElementById('month-year').value;
            const year = document.getElementById('year').value;
            let startDate, endDate;

            if (!reportType) {
                Swal.showValidationMessage('Please select a report type');
                return false;
            }

            if (!reportSchedule) {
                Swal.showValidationMessage('Please select a report schedule');
                return false;
            }

            if (reportSchedule === 'Monthly') {
                if (!monthYear) {
                    Swal.showValidationMessage('Please select a month and year');
                    return false;
                }
                startDate = monthYear + '-01';
                const [selectedYear, selectedMonth] = monthYear.split('-');
                const daysInMonth = new Date(selectedYear, selectedMonth, 0).getDate();
                endDate = monthYear + '-' + daysInMonth;
            } else if (reportSchedule === 'Annual') {
                if (!year) {
                    Swal.showValidationMessage('Please select a year');
                    return false;
                }
                startDate = year + '-01-01';
                endDate = year + '-12-31';
            }

            const reportName = document.getElementById('generated-report-name').value;
            if (!reportName) {
                Swal.showValidationMessage('Report name could not be generated');
                return false;
            }

            Swal.fire({
                icon: 'info',
                title: 'Generating...',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                window.location.href = `CReport.php?reportType=${reportType}&startDate=${startDate}&endDate=${endDate}&reportName=${reportName}`;
            });
        }
    });

    // Show/hide monthly or annual options based on report schedule selection
    document.getElementById('report-schedule').addEventListener('change', function() {
        const selectedSchedule = this.value;
        if (selectedSchedule === 'Monthly') {
            document.getElementById('monthlyOptions').style.display = 'block';
            document.getElementById('annualOptions').style.display = 'none';
        } else if (selectedSchedule === 'Annual') {
            document.getElementById('monthlyOptions').style.display = 'none';
            document.getElementById('annualOptions').style.display = 'block';
        }
        generateReportName();
    });

    document.getElementById('report-type').addEventListener('change', generateReportName);
    document.getElementById('month-year').addEventListener('change', generateReportName);
    document.getElementById('year').addEventListener('change', generateReportName);

    function generateReportName() {
        const reportType = document.getElementById('report-type').value;
        const reportSchedule = document.getElementById('report-schedule').value;
        let reportName = '';

        if (reportSchedule === 'Monthly') {
            const monthYear = document.getElementById('month-year').value;
            if (monthYear) {
                const [year, month] = monthYear.split('-');
                const monthName = new Date(year, month - 1).toLocaleString('default', { month: 'long' });
                reportName = `Monthly ${reportType} Report ${monthName} ${year}`;
            }
        } else if (reportSchedule === 'Annual') {
            const year = document.getElementById('year').value;
            if (year) {
                reportName = `Annual ${reportType} Report ${year}`;
            }
        }

        document.getElementById('generated-report-name').value = reportName;
    }
});

</script>

</body>
</html>

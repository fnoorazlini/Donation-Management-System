<?php
session_start();
include 'dbConnect.php'; // Include the database connection

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: loginClerk.php");
    exit;
}

// Function to fetch cash donations
function fetchCashDonations($dbCon) {
    $sql = "SELECT d.DonationID, c.ClerkName, d.DonationAmount, d.DonationMethod, a.AllocationType, d.DonationPurpose, d.DonationDate
            FROM DONATION d
            JOIN CLERK c ON d.ClerkID = c.ClerkID
            JOIN ALLOCATION a ON d.AllocationID = a.AllocationID
            WHERE d.DonationMethod = 'Cash'";
    $result = mysqli_query($dbCon, $sql);
    return $result; 
}

// Function to fetch all allocation types
function fetchAllocations($dbCon) {
    $sql = "SELECT * FROM ALLOCATION";
    $result = mysqli_query($dbCon, $sql);
    return $result;
}

// Function to delete a donation
if (isset($_POST['deleteDonation'])) {
    $donationID = $_POST['donationID'];

    $sql = "DELETE FROM DONATION WHERE DonationID = ?";
    $stmt = mysqli_prepare($dbCon, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $donationID);
        $execution = mysqli_stmt_execute($stmt);
        if ($execution) {
            echo "Donation deleted successfully!<br>"; // Debugging Output
            exit; // Exit to prevent further execution
        } else {
            echo "Error executing deletion: " . mysqli_stmt_error($stmt) . "<br>"; // Debugging Output
        }
    } else {
        echo "Error preparing deletion statement: " . mysqli_error($dbCon) . "<br>"; // Debugging Output
    }
}


// Function to add a new donation
if (isset($_POST['addDonation'])) {
    $donationAmount = $_POST['donationAmount'];
    $allocationID = $_POST['allocationID'];
    $clerkID = $_SESSION['clerk_id']; // Retrieved from session or login
    $currentDate = date('Y-m-d'); // Format: YYYY-MM-DD

    // Retrieve the AllocationType using AllocationID
    $allocQuery = "SELECT AllocationType FROM ALLOCATION WHERE AllocationID = ?";
    $allocStmt = mysqli_prepare($dbCon, $allocQuery);
    mysqli_stmt_bind_param($allocStmt, "s", $allocationID);
    mysqli_stmt_execute($allocStmt);
    mysqli_stmt_bind_result($allocStmt, $allocationType);
    mysqli_stmt_fetch($allocStmt);
    mysqli_stmt_close($allocStmt);

    // Set purpose as AllocationType
    $purpose = $allocationType;

    // Generate Donation ID
    $query = "SELECT MAX(SUBSTRING(DonationID, 3)) AS max_id FROM DONATION";
    $result = mysqli_query($dbCon, $query);
    $row = mysqli_fetch_assoc($result);
    $max_id = $row['max_id'];
    $new_id = intval($max_id) + 1;
    $donationID = 'DN' . sprintf('%02d', $new_id);

    $sql = "INSERT INTO DONATION (DonationID, DonorID, ClerkID, AllocationID, DonationAmount, DonationMethod,
                                 DonationPurpose, DonationDate, PaymentOption, DonationSend, InvoiceID, InvoiceDate, InvoiceTime, ConfirmationReceipt,ReadStatus) 
            VALUES (?, NULL, ?, ?, ?, 'Cash', ?, ?, NULL, NULL, NULL, NULL, NULL, 'No receipt','Read')";
    $stmt = mysqli_prepare($dbCon, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssdss", $donationID, $clerkID, $allocationID, $donationAmount, $purpose, $currentDate);
        $execution = mysqli_stmt_execute($stmt);
        if ($execution) {
            header("Location: CAllocation.php");
        } 
    }  
} 

//Function to update donation
if (isset($_POST['updateDonation'])) {
    $donationID = $_POST['donationID'];
    $donationAmount = $_POST['donationAmount'];
    $allocationID = $_POST['allocationID'];

    $sql = "UPDATE DONATION 
            SET AllocationID = ?, DonationAmount = ? 
            WHERE DonationID = ?";
    $stmt = mysqli_prepare($dbCon, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sds", $allocationID, $donationAmount, $donationID,);
        $execution = mysqli_stmt_execute($stmt);
        if ($execution) {
            header("Location: CAllocation.php");
        } 
    }  
}

// Handle AJAX request to get current donation data
if (isset($_GET['donationID'])) {
    $donationID = $_GET['donationID'];

    // Fetch current donation data
    $sql = "SELECT DonationAmount, AllocationID FROM DONATION WHERE DonationID = ?";
    $stmt = mysqli_prepare($dbCon, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $donationID);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $donationAmount, $allocationID);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        // Return JSON response
        echo json_encode([
            'donationAmount' => $donationAmount,
            'allocationID' => $allocationID
        ]);
        exit;
    }
}



// Fetch cash donations and allocation types
$cashDonations = fetchCashDonations($dbCon);
$allocations = fetchAllocations($dbCon);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Allocate Donations</title>
    <link rel="stylesheet" href="css/CAllocation.css"> <!-- Link to your CSS file -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css"> <!-- SweetAlert2 CSS -->
</head>
<body class="animated fadeIn">

<!-- =================================================== navigation bar ======================================================== -->   

<?php include 'CNavigation.php'; ?>

<!-- ===================================================== content ============================================================= -->   

<div class="container">
    <h1>Allocate Cash Donations</h1>
    <button class="add-donation-btn" onclick="showAddDonationForm()"><i class="fa fa-dollar"></i>  Add Donation </button>
    <table>
        <thead>
            <tr>
                <th>Donation ID</th>
                <th>Clerk In Charge</th>
                <th>Amount</th>
                <th>Allocation</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = mysqli_fetch_assoc($cashDonations)): ?>
                <tr>
                    <td><?php echo $row['DonationID']; ?></td>
                    <td><?php echo $row['ClerkName']; ?></td>
                    <td><?php echo "RM ".$row['DonationAmount']; ?></td>
                    <td><?php echo $row['AllocationType']; ?></td>
                    <td><?php echo $row['DonationDate']; ?></td>
                    <td class="action-icons">
                        <i class="fas fa-edit update-icon" onclick="updateDonation('<?php echo $row['DonationID']; ?>')"></i>
                        <i class="fas fa-trash-alt delete-icon" onclick="deleteDonation('<?php echo $row['DonationID']; ?>')"></i>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

<!-- ===================================================== form ============================================================= -->   
    
    <div id="addDonationForm" style="display:none;">
        <form method="POST" action="CAllocation.php">
            
            <label for="donationAmount">Amount:</label>
            <input type="number" name="donationAmount" id="donationAmount" required><br>
            
            <label for="allocationID">Allocation:</label>
            <select name="allocationID" id="allocationID" required>
                <option value="">Select Allocation</option>
                <?php while($alloc = mysqli_fetch_assoc($allocations)): ?>
                <option value="<?php echo $alloc['AllocationID']; ?>"><?php echo $alloc['AllocationType']; ?></option>
                <?php endwhile; ?>
            </select><br>
            
            
        </form>
    </div>
</div>

<!-- =================================================== javascript ======================================================== -->  

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>

function updateDonation(donationID) {
    // Make AJAX call to fetch current data before showing SweetAlert
    $.ajax({
        url: 'CAllocation.php',
        method: 'GET',
        data: { donationID: donationID },
        dataType: 'json',
        success: function(response) {
            // Create a temporary container to hold the form HTML
            let formHtml = document.createElement('div');
            formHtml.innerHTML = document.getElementById('addDonationForm').innerHTML;

            // Fill the form with the fetched data
            formHtml.querySelector('input[name="donationAmount"]').value = response.donationAmount;
            formHtml.querySelector('select[name="allocationID"]').value = response.allocationID;

            // Show SweetAlert with pre-filled data
            Swal.fire({
                title: 'Update Donation',
                html: formHtml.innerHTML,
                showCancelButton: true,
                cancelButtonText: 'Cancel',
                confirmButtonText: 'Update Donation',
                preConfirm: () => {
                    // Grab the form data
                    let donationAmount = Swal.getPopup().querySelector('input[name="donationAmount"]').value;
                    let allocationID = Swal.getPopup().querySelector('select[name="allocationID"]').value;

                    if (!donationAmount || !allocationID) {
                        Swal.showValidationMessage('Please fill out all fields');
                        return false;
                    }

                    // Submit the form via AJAX
                    let formData = new FormData();
                    formData.append('donationAmount', donationAmount);
                    formData.append('allocationID', allocationID);
                    formData.append('donationID', donationID);
                    formData.append('updateDonation', true);

                    return fetch('CAllocation.php', {
                        method: 'POST',
                        body: formData
                    }).then(response => {
                        return response.text().then(text => {
                            console.log(text); // Debug the response from the server
                            if (response.ok) {
                                Swal.fire({
                                    title: 'Donation Updated!',
                                    icon: 'success',
                                    timer: 1500, // Auto close in 1.5 seconds
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire('Failed to update donation', '', 'error');
                            }
                        });
                    }).catch(error => {
                        Swal.fire('An error occurred', '', 'error');
                    });
                }
            });
        },
        error: function() {
            Swal.fire('Failed to fetch donation data', '', 'error');
        }
    });
}


    function deleteDonation(donationID) {
        Swal.fire({
            title: 'Delete Donation',
            text: 'Are you sure you want to delete this donation?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it'
        }).then((result) => {
            if (result.isConfirmed) {
                // Perform AJAX request to delete donation
                let formData = new FormData();
                formData.append('deleteDonation', true);
                formData.append('donationID', donationID);

                fetch('CAllocation.php', {
                    method: 'POST',
                    body: formData
                }).then(response => {
                    return response.text().then(text => {
                        console.log(text); // Add this line to debug the response from the server
                        if (response.ok) {
                            Swal.fire({
                                title: 'Deleted!',
                                icon: 'success',
                                timer: 1500, // Auto close in 1.5 seconds
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Failed to delete donation', '', 'error');
                        }
                    });
                }).catch(error => {
                    Swal.fire('An error occurred', '', 'error');
                });
            }
        });
    }

    function showAddDonationForm() {
        Swal.fire({
            title: 'Add New Donation',
            html: document.getElementById('addDonationForm').innerHTML,
            showCancelButton: true,
            cancelButtonText: 'Cancel',
            confirmButtonText: 'Add Donation',
            preConfirm: () => {
                // Grab the form data
                let donationAmount = Swal.getPopup().querySelector('input[name="donationAmount"]').value;
                let allocationID = Swal.getPopup().querySelector('select[name="allocationID"]').value;

                if (!donationAmount || !allocationID) {
                    Swal.showValidationMessage('Please fill out all fields');
                    return false;
                }

                // Submit the form via AJAX
                let formData = new FormData();
                formData.append('donationAmount', donationAmount);
                formData.append('allocationID', allocationID);
                formData.append('addDonation', true);

                return fetch('CAllocation.php', {
                    method: 'POST',
                    body: formData
                }).then(response => {
                    return response.text().then(text => {
                        if (response.ok) {
                            Swal.fire({
                                title: 'Donation Added!',
                                icon: 'success',
                                timer: 1500, // Auto close in 1.5 seconds
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Failed to add donation', '', 'error');
                        }
                    });
                }).catch(error => {
                    Swal.fire('An error occurred', '', 'error');
                });
            }

        });
    }

</script>
</body>
</html>
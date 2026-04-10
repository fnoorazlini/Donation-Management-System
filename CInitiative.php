<?php
session_start();
include 'dbConnect.php'; // Include the database connection

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: loginClerk.php");
    exit;
}

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['donationImages'])) {
        $donationImages = $_FILES['donationImages'];

        foreach ($donationImages['tmp_name'] as $key => $tmp_name) {
            if ($donationImages['error'][$key] !== UPLOAD_ERR_OK) {
                echo "Error uploading file: " . $donationImages['error'][$key] . "<br>";
                continue;
            }

            $target_dir = "uploads/";
            $target_file = $target_dir . basename($donationImages['name'][$key]);

            if (move_uploaded_file($tmp_name, $target_file)) {
                $id = generateInitiativeID($dbCon);
                $clerkID = $_SESSION['clerk_id'];
                $currentDate = date('Y-m-d');

                // Insert into database
                $sql = "INSERT INTO initiative (InitiativeID, ClerkID, InitiativeImage, InitiativeCategory, InitiativeDate) 
                        VALUES (?, ?, ?, 'donation', ?)";
                $stmt = $dbCon->prepare($sql);
                $stmt->bind_param("ssss", $id, $clerkID, $target_file, $currentDate);
                $stmt->execute();
            } else {
                echo "File upload failed: " . $target_file . "<br>";
            }
        }
    }

    if (isset($_FILES['eventsImages'])) {
        $eventsImages = $_FILES['eventsImages'];

        foreach ($eventsImages['tmp_name'] as $key => $tmp_name) {
            if ($eventsImages['error'][$key] !== UPLOAD_ERR_OK) {
                echo "Error uploading file: " . $eventsImages['error'][$key] . "<br>";
                continue;
            }

            $target_dir = "uploads/";
            $target_file = $target_dir . basename($eventsImages['name'][$key]);

            if (move_uploaded_file($tmp_name, $target_file)) {
                $id = generateInitiativeID($dbCon);
                $clerkID = $_SESSION['clerk_id'];
                $currentDate = date('Y-m-d');

                // Insert into database
                $sql = "INSERT INTO initiative (InitiativeID, ClerkID, InitiativeImage, InitiativeCategory, InitiativeDate) 
                        VALUES (?, ?, ?, 'events', ?)";
                $stmt = $dbCon->prepare($sql);
                $stmt->bind_param("ssss", $id, $clerkID, $target_file, $currentDate);
                $stmt->execute();
            } else {
                echo "File upload failed: " . $target_file . "<br>";
            }
        }
    }
}

    // Handle image deletion
    if (isset($_POST['deleteImageId']) && isset($_POST['deleteCategory'])) {
        $imageId = $_POST['deleteImageId'];
        $category = $_POST['deleteCategory'];

        // Delete image from database
        $sql = "DELETE FROM initiative WHERE InitiativeID = ?";
        $stmt = $dbCon->prepare($sql);
        $stmt->bind_param("s", $imageId);
        $stmt->execute();

        // Delete image file from uploads directory (if necessary)
        $sql_select = "SELECT InitiativeImage FROM initiative WHERE InitiativeID = ?";
        $stmt_select = $dbCon->prepare($sql_select);
        $stmt_select->bind_param("s", $imageId);
        $stmt_select->execute();
        $stmt_select->bind_result($imageFile);
        $stmt_select->fetch();
        unlink($imageFile);

        // Respond with success status
        http_response_code(200);
        exit;
    }


// Function to generate InitiativeID
function generateInitiativeID($dbCon) {
    // Find the highest existing ID in the initiative table
    $query = "SELECT MAX(InitiativeID) AS max_id FROM initiative WHERE InitiativeID LIKE 'I%'";
    $result = mysqli_query($dbCon, $query);
    $row = mysqli_fetch_assoc($result);
    
    $max_id = $row['max_id'];
    
    if ($max_id) {
        // Extract the numeric part of the ID and increment it
        $num = intval(substr($max_id, 1)) + 1; // Remove the 'I' prefix and increment
        $id = 'I' . $num; // Generate new ID
    } else {
        // If no IDs exist, start with 'I1'
        $id = 'I1';
    }

    return $id;
}

// Fetch images
function fetchImages($dbCon, $category) {
    $sql = "SELECT InitiativeID, InitiativeImage FROM initiative WHERE InitiativeCategory = ?";
    $stmt = $dbCon->prepare($sql);
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
    $images = [];
    while ($row = $result->fetch_assoc()) {
        $images[] = $row;
    }
    return $images;
}

$donationImages = fetchImages($dbCon, 'donation');
$eventsImages = fetchImages($dbCon, 'events');

// Check for category in GET request
if (isset($_GET['InitiativeCategory'])) {
    $category = $_GET['InitiativeCategory'];
    $images = fetchImages($dbCon, $category);
    echo json_encode($images);
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initiative</title>
    <link rel="stylesheet" href="css/CInitiative.css"> <!-- Link to your CSS file -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Font Awesome CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- SweetAlert CDN -->
</head>
<body class="animated fadeIn">

<!-- ======================================== navigation bar ============================================== -->   

<?php include 'CNavigation.php'; ?>

<!-- =========================================== content ================================================= -->    

<div class="container">
    <h1>Initiative</h1>
    
    <div class="section-container">
        <div class="image-section">
            <h2>Donation</h2>
            <form id="donationForm" enctype="multipart/form-data" method="POST">
                <div style="display: flex; align-items: center;"> <!-- Added a div wrapper for flexbox -->
                    <input type="file" id="donationInput" name="donationImages[]" multiple accept="image/*" style="flex: 1;"> <!-- Added inline style for flexbox -->
                    <button type="submit" class="upload-button">Upload</button>
                </div>
            </form>
            <div id="donationImages">
                <?php foreach ($donationImages as $image): ?>
                    <div class="image-item">
                        <img src="<?php echo $image['InitiativeImage']; ?>" alt="Donation Image">
                        <form class="delete-form" method="POST">
                            <input type="hidden" name="deleteImageId" value="<?php echo $image['InitiativeID']; ?>">
                            <input type="hidden" name="deleteCategory" value="donation">
                            <button class="delete-button" type="button" onclick="confirmDelete(this)">Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="image-section">
            <h2>Events/Improvements</h2>
            <form id="eventsForm" enctype="multipart/form-data" method="POST">
                <div style="display: flex; align-items: center;"> <!-- Added a div wrapper for flexbox -->
                    <input type="file" id="eventsInput" name="eventsImages[]" multiple accept="image/*" style="flex: 1;"> <!-- Added inline style for flexbox -->
                    <button type="submit" class="upload-button">Upload</button>
                </div>
            </form>
            <div id="eventsImages">
                <?php foreach ($eventsImages as $image): ?>
                    <div class="image-item">
                        <img src="<?php echo $image['InitiativeImage']; ?>" alt="Events/Improvements Image">
                        <form class="delete-form" method="POST">
                            <input type="hidden" name="deleteImageId" value="<?php echo $image['InitiativeID']; ?>">
                            <input type="hidden" name="deleteCategory" value="events">
                            <button class="delete-button" type="button" onclick="confirmDelete(this)">Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
   document.addEventListener('DOMContentLoaded', function() {
    // Handle form submission for image upload
    document.getElementById('donationForm').addEventListener('submit', validateAndUploadImages);
    document.getElementById('eventsForm').addEventListener('submit', validateAndUploadImages);
});

function validateAndUploadImages(event) {
    event.preventDefault();
    const form = event.target;
    const input = form.querySelector('input[type="file"]');
    const files = input.files;
    const category = form === document.getElementById('donationForm') ? 'donation' : 'events';

    if (files.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'No files selected',
            text: 'Please select at least one image to upload.'
        });
        return;
    }

    const validImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    for (let file of files) {
        if (!validImageTypes.includes(file.type)) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid file type',
                text: 'Please upload only images (JPEG, PNG, GIF, WEBP).'
            });
            return;
        }
    }

    const formData = new FormData(form);

    fetch('CInitiative.php', {
        method: 'POST',
        body: formData
    }).then(response => {
        if (response.ok) {
            Swal.fire({
                icon: 'success',
                title: 'Images uploaded successfully!',
                showConfirmButton: false,
                timer: 1500
            });

            fetchImages(category);
            form.reset();
        }
    });
}


function fetchImages(category) {
    fetch(`CInitiative.php?InitiativeCategory=${category}`)
        .then(response => response.json())
        .then(images => {
            const imagesContainer = document.getElementById(`${category}Images`);
            imagesContainer.innerHTML = '';

            images.forEach(image => {
                const imageItem = document.createElement('div');
                imageItem.classList.add('image-item');
                imageItem.innerHTML = `
                    <img src="${image.InitiativeImage}" alt="${category} Image">
                    <form class="delete-form" method="POST">
                        <input type="hidden" name="deleteImageId" value="${image.InitiativeID}">
                        <input type="hidden" name="deleteCategory" value="${category}">
                        <button class="delete-button" type="button" onclick="confirmDelete(this)">Delete</button>
                    </form>
                `;
                imagesContainer.appendChild(imageItem);
            });
        });
}

function confirmDelete(button) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'You won\'t be able to revert this!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = button.closest('form');
            const formData = new FormData(form);

            fetch('CInitiative.php', {
                method: 'POST',
                body: formData
            }).then(response => {
                if (response.ok) {
                    // Show success message with SweetAlert
                    Swal.fire({
                        icon: 'success',
                        title: 'Image deleted successfully!',
                        showConfirmButton: false,
                        timer: 1500
                    });

                    // Redirect to CInitiative.php after deletion
                    setTimeout(() => {
                        window.location.href = 'CInitiative.php';
                    }, 1500);
                } else {
                    // Show error message with SweetAlert
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed to delete image',
                        text: 'Please try again later.'
                    });
                }
            });
        }
    });
}
</script>

</body>
</html>

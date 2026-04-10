<?php
session_start();

// Include config file
require_once "dbConnect.php";

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$donorEmail = $_SESSION["username"]; // Assuming donor email is stored in session username

// Initialize variable to hold donor name and total donations
$donorName = "";
$totalDonations = 0;
$donorID = 0;

// Prepare a select statement to get the donor's name and ID
$sql = "SELECT DonorID, DonorName FROM donor WHERE DonorEmail = ?";

if ($stmt = mysqli_prepare($dbCon, $sql)) {
    mysqli_stmt_bind_param($stmt, "s", $donorEmail);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) == 1) {
            mysqli_stmt_bind_result($stmt, $retrieved_donorID, $retrieved_donorName);
            if (mysqli_stmt_fetch($stmt)) {
                $donorID = $retrieved_donorID;
                $donorName = $retrieved_donorName;
            }
        } else {
            echo "No donor found."; 
        }
    } else {
        echo "Oops! Something went wrong. Please try again later. " . mysqli_stmt_error($stmt); 
    }
    mysqli_stmt_close($stmt);
} else {
    echo "Oops! Database connection error."; 
}

// Prepare a select statement to count the total donations
$sql_total_donations = "SELECT COUNT(*) AS totalDonations FROM donation WHERE DonorID = ?";

if ($stmt_total = mysqli_prepare($dbCon, $sql_total_donations)) {
    mysqli_stmt_bind_param($stmt_total, "s", $donorID);
    if (mysqli_stmt_execute($stmt_total)) {
        mysqli_stmt_bind_result($stmt_total, $retrieved_totalDonations);
        if (mysqli_stmt_fetch($stmt_total)) {
            $totalDonations = $retrieved_totalDonations;
        }
    } else {
        echo "Oops! Something went wrong while fetching total donations. Please try again later. " . mysqli_stmt_error($stmt_total); 
    }
    mysqli_stmt_close($stmt_total);
}

function fetchImages($dbCon, $category) {
    $sql = "SELECT InitiativeImage FROM initiative WHERE InitiativeCategory = ?";
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

mysqli_close($dbCon);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Global Styles */
        body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background: url('images/b.jpg') no-repeat center center fixed; /* Specify the path to your image */
        background-size: cover; /* Ensure the background image covers the entire body */
    }

    .container {
    width: 80%;
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
    background-color: rgba(255, 255, 255, 0.7); /* Adjust the opacity (0.9 for 90% opaque) */
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(to bottom right, #66cc66, #9933ff); /* Green to purple gradient */
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
            background-color: #4b0082; /* Dark purple on hover */
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
            background-color: #4b0082; /* Dark purple on hover */
        }

        .hero {
            background: linear-gradient(to bottom right, #ccccff 0%, #ffffff 100%);
            background-size: cover;
            background-position: center;
            height: 100px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            text-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .hero h1 {
            font-size: 36px;
            font-weight: bold;
            margin: 0;
        }

        .main-content {
            text-align: center;
        }

        .statistics {
            background: linear-gradient(to bottom right, #ccccff 0%, #ffffff 100%);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .slideshow-container {
            position: relative;
            max-width: 100%;
            overflow: hidden;
            margin: 20px auto;
            border-radius: 8px;
        }

        .slide {
            display: none;
            width: 100%;
            text-align: center;
        }

        .slide img {
            max-width: 100%;
            height: auto;
            max-height: 400px;
            object-fit: cover;
            border-radius: 8px;
        }

        .slide.active {
            display: block;
            animation: fade 1s ease;
        }

        @keyframes fade {
            0% {
                opacity: 0;
            }
            100% {
                opacity: 1;
            }
        }

        .impact-slideshow {
            position: relative;
            max-width: 100%;
            overflow: hidden;
            margin: 20px auto;
            border-radius: 8px;
        }

        .impact-slide {
            display: none;
            width: 100%;
            text-align: center;
        }

        .impact-slide img {
            max-width: 100%;
            height: auto;
            max-height: 400px;
            object-fit: cover;
            border-radius: 8px;
        }

        .impact-slide.active {
            display: block;
            animation: fade 1s ease;
        }

        @keyframes fade {
            0% {
                opacity: 0;
            }
            100% {
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .container {
                width: 90%;
            }
        }

        .dashboard-grid {
            display: flex;
            gap: 20px;
        }

        .dashboard-grid .column {
            flex: 1;
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
<a href="accountSummary.php"><i class="fas fa-cog"></i> Account Settings</a>

</nav>
    <a class="logout" href="logout.php"><i class="fas fa-sign-out-alt"></i></a>
</header>

<div class="container">
    <div class="hero">
        <h1>Welcome, <?= htmlspecialchars($donorName) ?>!</h1>
    </div>

    <div class="statistics">
        <h2 style="text-align:center;">Your Charitable Contributions: <?= $totalDonations ?></h2>
    </div>

    <div class="dashboard-grid">
        <div class="column">
        <h2  style="text-align:center;">Join Our Active Donation Efforts</h2>
            <div class="slideshow-container">
                <?php foreach ($donationImages as $index => $image): ?>
                    <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>">
                        <img src="<?php echo $image['InitiativeImage']; ?>" alt="Donation Image">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="column">
        <h2  style="text-align:center;">Empowering Our Madrasah Through Your Generosity</h2>
            <div class="impact-slideshow">
                <?php foreach ($eventsImages as $index => $image): ?>
                    <div class="impact-slide <?php echo $index === 0 ? 'active' : ''; ?>">
                        <img src="<?php echo $image['InitiativeImage']; ?>" alt="Events/Improvements Image">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
    let slideIndex = 0;
    let impactSlideIndex = 0;

    function showSlides() {
        let slides = document.querySelectorAll('.slide');
        slides.forEach((slide) => slide.style.display = 'none');
        slideIndex++;
        if (slideIndex > slides.length) { slideIndex = 1; }
        slides[slideIndex - 1].style.display = 'block';
        setTimeout(showSlides, 4000);
    }

    function showImpactSlides() {
        let impactSlides = document.querySelectorAll('.impact-slide');
        impactSlides.forEach((slide) => slide.style.display = 'none');
        impactSlideIndex++;
        if (impactSlideIndex > impactSlides.length) { impactSlideIndex = 1; }
        impactSlides[impactSlideIndex - 1].style.display = 'block';
        setTimeout(showImpactSlides, 3000);
    }

    showSlides();
    showImpactSlides();
</script>

</body>
</html>
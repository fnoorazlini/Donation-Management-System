
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation Process</title>
     <!-- Include SweetAlert2 CSS -->
     <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Include FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Add your custom CSS here -->
    <style>
        /* Your existing styles */
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
            background-color: rgba(255, 255, 255, 0.7);
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

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select {
            width: 90%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .form-group input[type="file"] {
            margin-top: 10px;
        }

        .form-group button {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #6FC276;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .form-group button:hover {
            background-color: #56a982;
        }

        .payment-method {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 10px;
        }

        .payment-method label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .payment-method label img {
            width: 60px;
            height: auto;
            margin-right: 10px;
            cursor: pointer;
        }

        .payment-method input[type="radio"] {
            display: none;
        }

        .payment-method input[type="radio"] + span {
            font-weight: bold;
        }

        .payment-method input[type="radio"]:checked + span {
            color: #6FC276;
        }

        .payment-details {
            margin-top: 10px;
            padding: 10px;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            border-radius: 4px;
            display: none;
        }

        #qr_code_img {
            width: 200px;
            height: auto;
            display: block;
            margin: 0 auto;
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
    <h1>Please Complete All of the Donation Process</h1>

    <form id="donation_form" action="processDonation.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Donation can be sent through:</label>
            <div class="payment-method">
                <label>
                    <input type="radio" id="donationSend_bankislam" name="donationSend" value="bank_islam" required>
                    <img src="images/qr.JPG" alt="QR Code">
                    <span>QR Code</span>
                </label>
                <label>
                    <input type="radio" id="donationSend_bankmuamalat" name="donationSend" value="bank_muamalat" required>
                    <img src="images/bank.JPG" alt="Bank Muamalat">
                    <span>Bank Muamalat</span>
                </label>
                <label>
                    <input type="radio" id="donationSend_jomPay" name="donationSend" value="jomPay" required>
                    <img src="images/jompay.JPG" alt="JomPay">
                    <span>JomPay</span>
                </label>
            </div>
        </div>

        <div id="qr_code_details" class="payment-details" style="display: none;">
            <img id="qr_code_img" src="images/qr2.JPG" alt="QR Code Image">
        </div>

        <div id="bank_muamalat_details" class="payment-details" style="display: none;">
            <p>Bank Muamalat Account Number: <strong>13010002829710</strong></p>
        </div>

        <div id="jompay_details" class="payment-details" style="display: none;">
            <p>JomPay Number: <strong>999748</strong></p>
        </div>

        <div class="form-group">
            <label for="amount">Enter Donation Amount (RM):</label>
            <input type="number" id="amount" name="amount" min="1" step="any" required>
        </div>

        <div id="payment_option_group" class="form-group" style="display: none;">
            <label for="payment_option">Select Your Online Banking:</label>
            <select id="payment_option" name="payment_option">
                <option value="maybank2u">Maybank2u</option>
                <option value="cimbclicks">CIMB Clicks</option>
                <option value="rhbnow">RHB Now</option>
                <option value="bankislam">Bank Islam</option>
                <option value="hongleongconnect">Hong Leong Connect</option>
                <option value="ambank">AmBank</option>
                <option value="pbebank">Public Bank</option>
                <option value="bankrakyat">Bank Rakyat</option>
                <option value="affinonline">AffinOnline</option>
                <option value="bsn">BSN</option>
                <option value="muamalat">Bank Muamalat</option>
                <option value="uob">UOB</option>
            </select>
        </div>

        <div class="form-group">
            <button type="button" id="submit_button" onclick="handleSubmit()">Pay Now</button>
        </div>
    </form>
</div>

<!-- Include SweetAlert2 JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
    function displayPaymentDetails(paymentMethod) {
        document.querySelectorAll('.payment-details').forEach(function (element) {
            element.style.display = 'none';
        });

        var detailsElement = document.getElementById(paymentMethod + '_details');
        if (detailsElement) {
            detailsElement.style.display = 'block';
        }

        if (paymentMethod === 'qr_code') {
            document.getElementById('payment_option_group').style.display = 'none';
            document.getElementById('submit_button').innerText = 'Done';
            document.getElementById('donation_form').action = 'processDonation.php';
        } else {
            document.getElementById('payment_option_group').style.display = 'block';
            document.getElementById('submit_button').innerText = 'Pay Now';
            document.getElementById('donation_form').action = 'processDonation.php';
        }
    }

    document.getElementById('donationSend_bankislam').addEventListener('change', function() {
        displayPaymentDetails('qr_code');
    });

    document.getElementById('donationSend_bankmuamalat').addEventListener('change', function() {
        displayPaymentDetails('bank_muamalat');
    });

    document.getElementById('donationSend_jomPay').addEventListener('change', function() {
        displayPaymentDetails('jompay');
    });

    function handleSubmit() {
    const selectedDonationSend = document.querySelector('input[name="donationSend"]:checked');
    if (!selectedDonationSend) {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'Please select which bank you want to send your donation!'
        });
        return;
    }

    const donationSend = selectedDonationSend.value;
    const amount = document.getElementById('amount').value;

    if (isNaN(amount) || amount <= 0) {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'Invalid donation amount!'
        });
        return;
    }

    let message = '';
    if (donationSend === 'bank_islam') {
        message = 'Are you sure you want to proceed with this donation?';
    } else {
        message = 'Are you sure you want to proceed with this payment method?';
    }

    // Show SweetAlert confirmation dialog
    Swal.fire({
        title: "Donation Successful!",
        text: "Please upload the receipt and allocate your donation.",
        icon: "success",
        showCancelButton: true,
        confirmButtonColor: '#6FC276',
        cancelButtonColor: '#d33',
        confirmButtonText: 'OK!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Proceed with form submission
            document.getElementById('donation_form').submit();
        }
    });
}
</script>

</body>
</html>
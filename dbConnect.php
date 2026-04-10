<?php
/* Database credentials. Assuming you are running MySQL server with default setting (user 'root' with no password) */
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'DonationDB');


/* Attempt to connect to MySQL Database */
$dbCon = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($dbCon === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
?>
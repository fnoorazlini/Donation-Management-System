<?php
require_once("dbConnect.php");

$PrincipalID = $PrincipalName = $PrincipalPass = $confirm_password = "";
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate all fields
    if (empty(trim($_POST["name"])) || empty(trim($_POST["password"])) || empty(trim($_POST["confirm_password"]))) {
        $error_message = "Please fill out all the fields in the form.";
    } else {
        $PrincipalName = trim($_POST['name']);
        $PrincipalPass = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']);

        // Validate password length
        if (strlen($PrincipalPass) < 6) {
            $error_message = "Password must have at least 6 characters.";
        }

        // Validate confirm password
        if ($PrincipalPass !== $confirm_password) {
            $error_message = "Passwords do not match.";
        }
    }

    // If no errors, proceed with registration
    if (empty($error_message)) {
        // Generate PrincipalID
        $sql = "SELECT MAX(SUBSTRING(PrincipalID, 2)) AS max_id FROM PRINCIPAL";
        $result = mysqli_query($dbCon, $sql);

        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $max_id = (int) $row['max_id'];
            $newID = 'P' . str_pad($max_id + 1, 2, '0', STR_PAD_LEFT);
        } else {
            $newID = 'P01';
        }

        // Hash the password using password_hash()
        $hashed_password = password_hash($PrincipalPass, PASSWORD_DEFAULT);

        // Insert the new Principal into the database
        $sql = "INSERT INTO PRINCIPAL (PrincipalID, PrincipalName, PrincipalPass) VALUES (?,?,?)";
        $stmt = mysqli_prepare($dbCon, $sql);
        mysqli_stmt_bind_param($stmt, "sss", $newID, $PrincipalName, $hashed_password);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) > 0) {
            echo "<script>alert('Successfully registered. You can now login.'); window.location.href='loginPrincipal.php';</script>";
        } else {
            echo "<script>alert('Error: ". mysqli_error($dbCon). "');</script>";
        }
    } else {
        echo "<script>alert('$error_message');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Principal Register</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<style>
body {
  background-image: url('images/mjtj.jpg');
  background-size: cover;
  background-attachment: fixed;
  background-position: center;
  background-repeat: no-repeat;
  font-family: Arial, sans-serif;
  margin: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  height: 100vh;
}

.content {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex: 1;
  width: 100%;
}

.container {
  background-color: rgba(255, 255, 255, 0.6);
  width: 75%;
  padding: 40px;
  border-radius: 10px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  text-align: center;
}

.container img {
  max-width: 100%;
  height: auto;
  border-radius: 10px;
  margin-bottom: 20px;
}

h2 {
  margin-bottom: 20px;
  font-size: 28px;
}

p {
  font-size: 18px;
  line-height: 1.6;
  margin-bottom: 20px;
}

.input-container {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  margin-bottom: 15px;
}

.icon {
  padding: 10px;
  background-color: #4CAF50;
  color: white;
  min-width: 50px;
  text-align: center;
}

.input-field {
  width: calc(45% - 60px); /* Adjusted width for better alignment */
  padding: 10px;
  outline: none;
  border: 1px solid #ccc;
  border-radius: 5px;
}

.input-field:focus {
  border-color: dodgerblue;
}

.error {
  color: red;
  font-size: 14px;
  margin-top: 5px;
}

button {
  background-color: #4CAF50;
  color: white;
  padding: 12px 20px;
  margin-top: 10px;
  border: none;
  cursor: pointer;
  width: 40%;
  border-radius: 5px;
  font-size: 16px;
}

button:hover {
  background-color: #45a049;
}

button a {
  color: white;
  text-decoration: none;
  display: block;
}
.home-icon {
  position: absolute;
  top: 20px;
  left: 20px;
  font-size: 40px;
  color: black;
  text-decoration: none;
}
</style>
</head>
<body>
<a href="home.php" class="home-icon"><i class="fa fa-home"></i></a>
<div class="content">
  <div class="container">
    <img src="images/banner.png" alt="Header Image">
    <h2>Principal Register</h2>
    
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
      <div class="input-container">
        <i class="fa fa-user icon"></i>
        <input class="input-field" type="text" name="name" placeholder="Name" value="<?php echo htmlspecialchars($PrincipalName); ?>">
      </div>

      <div class="input-container">
        <i class="fa fa-key icon"></i>
        <input class="input-field" type="password" name="password" placeholder="Password">
      </div>
      
      <div class="input-container">
        <i class="fa fa-key icon"></i>
        <input class="input-field" type="password" name="confirm_password" placeholder="Confirm Password">
      </div>

      <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="login.php" style="color:#4CAF50; text-decoration:none;">Login</a></p>
  </div>
</div>

</body>
</html>

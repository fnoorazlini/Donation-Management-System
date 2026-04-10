<?php
session_start();
require_once("dbConnect.php");

$username = $password = "";
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    if (empty($username) || empty($password)) {
        $message = "All fields are required.";
    } else {
        // Define table and column names for clerks
        $table = 'CLERK';
        $id_column = 'ClerkEmail';
        $pass_column = 'ClerkPass';

        // Prepare SQL statement
        $sql = "SELECT ClerkID, $id_column, $pass_column FROM $table WHERE $id_column = ?";
        $stmt = mysqli_prepare($dbCon, $sql);

        // Check for errors
        if (!$stmt) {
            $message = "Error preparing SQL statement: " . mysqli_error($dbCon);
        } else {
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            // Check for errors
            if (!$result) {
                $message = "Error executing SQL statement: " . mysqli_error($dbCon);
            } else {
                if (mysqli_num_rows($result) > 0) {
                    $row = mysqli_fetch_assoc($result);
                    // Verify password using password_verify()
                    if (password_verify($password, $row[$pass_column])) {
                        $_SESSION['loggedin'] = true;
                        $_SESSION['username'] = $row[$id_column];
                        $_SESSION['clerk_id'] = $row['ClerkID']; // Store ClerkID in session
                        header("location: CDashboard.php");
                        exit();
                    } else {
                        $message = "Invalid username or password. Please try again.";
                    }
                } else {
                    $message = "Invalid username or password. Please try again.";
                }
            }
        }

        mysqli_stmt_close($stmt);
    }

    mysqli_close($dbCon);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Page</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>
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
  width: 80%;
  max-width: 1200px;
  padding: 40px;
  border-radius: 10px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  text-align: center;
  position: relative;
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
  width: calc(35% - 60px);
  padding: 10px;
  outline: none;
  border: 1px solid #ccc;
  border-radius: 5px;
}

.input-field:focus {
  border-color: dodgerblue;
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

.error {
  color: red;
  font-size: 16px;
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
window.onload = function() {
  Swal.fire({
    title: "Greetings!",
    text: "Hi Clerk 👋, let's get started with your tasks!",
    icon: "success",
    toast: true,
    timer: 2000,
    showConfirmButton: false
  });
};
</script>
</head>
<body>
<a href="home.php" class="home-icon"><i class="fa fa-home"></i></a>
<div class="content">
  <div class="container">
    <img src="images/banner.png" alt="Header Image">
    <h2>Clerk Login</h2>
    
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
      <div class="input-container">
        <i class="fa fa-user icon"></i>
        <input class="input-field" type="text" name="username" placeholder="Email" value="<?php echo htmlspecialchars($username); ?>">
      </div>
      
      <div class="input-container">
        <i class="fa fa-key icon"></i>
        <input class="input-field" type="password" name="password" placeholder="Password">
      </div>
      <button type="submit">Login</button>
      <span class="error"><?php echo $message; ?></span>
    </form>
  </div>
</div>

</body>
</html>

<?php
session_start();
require 'db.php';

$message = '';
if (isset($_SESSION['user_id'])) {
  echo "<script>window.top.location = '../dashboard.php';</script>";
  exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get POST request info
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $message = "<p style='color: red;'>Passwords do not match.</p>";
    } else {
        // Parameterized query to check if user already exists
        $sql = "SELECT * FROM users WHERE username=? AND email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        
        mysqli_stmt_bind_param($stmt, "ss", $username, $email);
    
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt) ;

        if ($result->num_rows > 0) {
            $message = "<p style='color: red;'>Username/Email are already taken. Please choose another one.</p>";
        } else {
            // If passowrds match and username/email not taken, create user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sss", $username, $email, $password);

            if (mysqli_stmt_execute($stmt)) {
              echo "<script>window.top.location = '../?auth=signin';</script>";
              exit();
            } else {
                $message = "<p style='color: red;'>An error occurred while registering. Please try again later.</p>";
            }
        }
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Sign Up</title>
  <link rel="stylesheet" href="../styles.css" />
</head>
<body>
  <div class="auth-container">
    <h2>Create Account</h2>
    <form class="auth-form" action="signup.php" method="POST">
      <input type="text" name="username" placeholder="Username" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" minLength="10" required>
      <input type="password" name="confirm_password" placeholder="Confirm Password" required>
      <input type="submit" class="submit-btn" value="Sign Up">
    </form>

    <?php
    if ($message != '') {
        echo $message;
    }
    ?>

    <p>
      By signing up, you agree to our 
      <a href="https://www.example.com/terms" target="_blank">Terms of Service</a> 
      and 
      <a href="https://www.example.com/privacy" target="_blank">Privacy Policy</a>.
    </p>

    <p>
      Already have an account? 
      <a href="#" onclick="parent.loadForm('backend/signin.php'); return false;">Sign In</a>
    </p>
  </div>
</body>
</html>
<?php
session_start();
require 'db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $sql = "SELECT * FROM users WHERE username='$username'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['logged_in'] = true;
            echo "<script>window.top.location = '../';</script>";
            exit();
        } else {
            $message = "<p style='color: red;'>Password is incorrect.</p>";
        }
    } else {
        $message = "<p style='color: red;'>User not found.</p>";
    }
}
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Sign In</title>
  <link rel="stylesheet" href="../styles.css" />
</head>
<body>
  <div class="auth-container">
    <h2>Sign In</h2>
    <form class="auth-form" action="signin.php" method="POST"> 
      <input type="text" name="username" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <input type="submit" class="submit-btn" value="Sign In">
    </form>
    <?php
    if ($message != '') {
        echo $message;
    }
    ?>
    <p>
      Don't have an account?
      <a href="#" onclick="parent.loadForm('backend/signup.php'); return false;">Sign Up</a>
    </p>
  </div>
</body>
</html>
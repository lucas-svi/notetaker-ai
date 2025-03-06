<?php
session_start();
require 'db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username=?";
    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "s", $username);

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt) ;

    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['logged_in'] = true;
            echo "<script>window.top.location = '../backend/dashboard.php';</script>";
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
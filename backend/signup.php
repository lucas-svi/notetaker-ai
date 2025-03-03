<?php
require 'db.php';

// Initialize message variable for errors or success
$message = '';

// Get fields and sanitize (only if the form was submitted)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);

    // Check if passwords match
    if ($password !== $confirm_password) {
        $message = "<p style='color: red;'>Passwords do not match.</p>";
    } else {
        // Check if the username already exists
        $query = "SELECT username, email FROM users WHERE username = '$username' OR email = '$email'";
        $result = $conn->query($query);

        if ($result->num_rows > 0) {
            // If the username is found, display a message
            $message = "<p style='color: red;'>Username/Email are already taken. Please choose another one.</p>";
        } else {
            // Otherwise, hash the password and add user data
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$hashed_password')";

            if (mysqli_query($conn, $sql)) {
                // Redirect to sign-in page on successful registration
                header("Location: ../signin.html");
                exit();
            } else {
                $message = "<p style='color: red;'>An error occurred while registering. Please try again later.</p>";
            }
        }
    }
}

// Close the database connection
mysqli_close($conn);
?>

<!-- Re-open sign-up page with error message -->

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
      <input type="password" name="password" placeholder="Password" required>
      <input type="password" name="confirm_password" placeholder="Confirm Password" required>
      <input type="submit" class="submit-btn" value="Sign Up">
    </form>
    <!-- Display any error messages -->
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
      <a href="#" onclick="parent.loadForm('signin.html'); return false;">Sign In</a>
    </p>
  </div>
</body>
</html>
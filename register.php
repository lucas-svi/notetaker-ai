<?php session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "app-db";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input_username = $_POST['username'];
    $input_password = $_POST['password'];
    $input_email = $_POST['email'];
    $confirm_password = $_POST['confirm_password'];

    // Check if the passwords match
    if ($input_password !== $confirm_password) {
        echo "<p>Passwords do not match.</p>";
        exit();
    }

    // Hash the password for storage
    $hashed_password = password_hash($input_password, PASSWORD_BCRYPT);

    // Check if the username already exists
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $input_username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<p>Username already exists. Please choose a different one.</p>";
    } else {
        // Insert the new user into the database
        $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $input_username, $input_email, $hashed_password);

        if ($stmt->execute()) {
            echo "<p>Registration successful! You can now <a href='login.html'>login</a>.</p>";
        } else {
            echo "<p>There was an error. Please try again later.</p>";
        }
    }

    $conn->close();
}
?>
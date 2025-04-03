<?php
require_once PROJECT_ROOT_PATH . "/Model/Database.php";
class UserModel extends Database
{
    public function getUsers($limit)
    {
        return $this->select("SELECT * FROM users ORDER BY username ASC LIMIT ?", ["i", $limit]);
    }

    public function createUser($username, $email, $password)
    {
        // Check to see if user already exists
        $check_sql = "SELECT * FROM users WHERE username=? OR email=?";
        $check_stmt = mysqli_prepare($this->connection, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "ss", $username, $email);
        mysqli_stmt_execute($check_stmt);
        $result = mysqli_stmt_get_result($check_stmt);

        if ($result->num_rows > 0) {
            throw new Exception("Username/Email are already taken. Please choose another one.");
        }

        // Otherwise, insert new user

        // Hash given password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($this->connection, $sql);
        mysqli_stmt_bind_param($stmt, "sss", $username, $email, $hashed_password);

        // Execute statement and check if it works
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("An error occured while registering. Please try again later.");
        }
        mysqli_stmt_close($stmt);
        return true;
    }
}
?>
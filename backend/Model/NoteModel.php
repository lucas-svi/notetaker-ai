<?php
require_once PROJECT_ROOT_PATH . "/Model/Database.php";
class UserModel extends Database
{
    public function getNotes($limit)
    {
        return $this->select("SELECT * FROM notes ORDER BY id ASC LIMIT ?", ["i", $limit]);
    }

    public function createNote($username, $note)
    {
        $sql = "INSERT INTO notes (username, note) VALUES (?, ?)";
        $stmt = mysqli_prepare($this->$conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $username, $note);
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . mysqli_error($this->connection));
        }
        mysqli_stmt_bind_param($stmt, "ss", $username, $note);

        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt); // Here we makes ure the statement is closed
            throw new Exception("An error occurred while creating the note. Please try again later.");
        }

        mysqli_stmt_close($stmt); //Close the statement after execution
        return true; // Indicate success
    }
    public function deleteNote($note_id)
    {
        // We makin sure $note_id is an integer
        $note_id = intval($note_id);
    
        // Prepthe DELETE query
        $delete_query = "DELETE FROM notes WHERE id=?";
        $stmt = mysqli_prepare($this->connection, $delete_query);
    
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . mysqli_error($this->connection));
        }
    
        // Bind the parameter
        mysqli_stmt_bind_param($stmt, "i", $note_id);
    
        // Execute the statement
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt); //Lets make sure statement is closed
            throw new Exception("An error occurred while deleting the note. Please try again later.");
        }
        mysqli_stmt_close($stmt);
    
        return true;
    }
}
?>
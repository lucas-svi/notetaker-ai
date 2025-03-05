<?php
session_start();
require 'db.php';
require 'authenticated.php';

$username = $_SESSION['username'];
$message = '';

// Delete note
if (isset($_GET['delete'])) {
  $note_id = intval($_GET['delete']);
  $delete_query = "DELETE FROM notes WHERE id='$note_id' AND username='$username'";
  if (mysqli_query($conn, $delete_query)) {
      header('Location: dashboard.php');
      exit();
  } else {
      echo "<p style='color: red;'>Error deleting note.</p>";
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note = mysqli_real_escape_string($conn, $_POST['note']);

    // Check if it's an update or create operation
    if (isset($_POST['note_id']) && !empty($_POST['note_id'])) {
        $note_id = intval($_POST['note_id']);
        $sql = "UPDATE notes SET note='$note' WHERE id='$note_id' AND username='$username'";
    } else {
        $sql = "INSERT INTO notes (username, note) VALUES ('$username', '$note')";
    }

    if (mysqli_query($conn, $sql)) {
        header('Location: dashboard.php');
        exit();
    } else {
        $message = "<p style='color: red;'>An error occurred. Please try again later.</p>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title><?php echo isset($_GET['edit']) ? 'Edit Note' : 'Create Note'; ?></title>
  <link rel="stylesheet" href="../styles.css" />
</head>
<body>
  <div class="auth-container">
    <h2><?php
        $note = '';
        if (isset($_GET['edit'])) {
            $note_id = intval($_GET['edit']);
            $result = mysqli_query($conn, "SELECT note FROM notes WHERE id='$note_id' AND username='$username'");
            if ($result && mysqli_num_rows($result) === 1) {
                $note_data = mysqli_fetch_assoc($result);
                $note = $note_data['note'];
            }
        }
?></h2>

    <form class="auth-form" action="note.php" method="POST">
    <textarea name="note" required rows="5" cols="100" placeholder="Write note here..."><?php echo htmlspecialchars($note); ?></textarea>


      <?php if (isset($_GET['edit'])): ?>
        <input type="hidden" name="note_id" value="<?php echo htmlspecialchars($note_id); ?>">
      <?php endif; ?>

    <input type="submit" class="submit-btn" value="<?php echo isset($_GET['edit']) ? 'Update' : 'Create'; ?> Note">
  </form>

  <?php if ($message): ?>
    <p><?php echo $message; ?></p>
  <?php endif; ?>
</body>
</html>

<?php mysqli_close($conn); ?>

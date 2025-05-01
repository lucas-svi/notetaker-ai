<?php
session_start(); // Start the session to manage user authentication
require 'db.php'; // Include database connection
require 'authenticated.php'; // Make sure user is authenticated

$username = $_SESSION['username'];
$message = '';


// AI
if (isset($_GET['ai'])) {
    $note_id = intval($_GET['ai']);
    $sql = "UPDATE notes SET note=? WHERE id = ? AND username = ?";
    $stmt = mysqli_prepare($conn, $sql);
    $note_text = "AI generated note";
    mysqli_stmt_bind_param($stmt, "sisi", $note_text, $note_id, $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header('Location: dashboard.php');
    exit();
}


// Delete note
if (isset($_GET['delete'])) {
  $note_id = intval($_GET['delete']);
  $delete_query = "DELETE FROM notes WHERE id=? AND username=?";
  $stmt = mysqli_prepare($conn, $delete_query);
  mysqli_stmt_bind_param($stmt, "is", $note_id, $username);
  if (mysqli_stmt_execute($stmt)) {
      header('Location: dashboard.php'); // Redirect to dashboard after deleting
      exit();
  } else {
      echo "<p style='color: red;'>Error deleting note.</p>";
  }
  mysqli_stmt_close($stmt);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $cat_id = isset($_POST['category_id']) && $_POST['category_id'] !== ''
  ? intval($_POST['category_id'])
  : null;
    $note = mysqli_real_escape_string($conn, $_POST['note']);

    if (isset($_POST['note_id']) && !empty($_POST['note_id'])) {
        $note_id = intval($_POST['note_id']);
        $sql = "UPDATE notes SET note=? WHERE id=? AND username=? AND category_id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sisi", $note, $note_id, $username, $cat_id);
    } else {
        $sql = "INSERT INTO notes (username, note, category_id) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssi", $username, $note, $cat_id);
    }

    if (mysqli_stmt_execute($stmt)) {
        header('Location: dashboard.php'); // Redirect to dashboard (after saving note)
        exit();
    } else {
        $message = "<p style='color: red;'>An error occurred. Please try again later.</p>";
    }
    mysqli_stmt_close($stmt);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title><?= isset($_GET['edit']) ? 'Edit Note' : 'Create Note'; ?></title>
    <link rel="stylesheet" href="../styles.css" />
</head>
<body>
<div class="auth-container">

<h2><?= isset($_GET['edit']) ? 'Edit Note' : 'Create Note'; ?></h2>

<form class="auth-form" action="note.php" method="POST">

    <!-- note body -->
    <textarea name="note" required rows="5" cols="100"
              placeholder="Write note here..."><?= htmlspecialchars($note_text); ?></textarea>

    <!-- category dropdown -->
    <select name="category_id" style="margin:15px 0;width:100%;padding:10px;">
        <option value="">— None —</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>"
                    <?= $cat['id'] == $currentCat ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <!-- hidden id if editing -->
    <?php if (isset($_GET['edit'])): ?>
        <input type="hidden" name="note_id" value="<?= htmlspecialchars($note_id); ?>">
    <?php endif; ?>

    <!-- submit -->
    <input type="submit" class="submit-btn"
           value="<?= isset($_GET['edit']) ? 'Update' : 'Create'; ?> Note">
</form>

<?= $message ?>

</div>
</body>
</html>
<?php mysqli_close($conn); ?>

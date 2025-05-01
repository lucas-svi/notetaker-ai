<?php
session_start();
require 'db.php';
require 'authenticated.php';

$username = $_SESSION['username'];
$message = '';
$currentCat = NULL;
$note_id = NULL;
$note_text = NULL;
if (isset($_GET['ai'])) {
    $note_id = intval($_GET['ai']);
    $sql = "UPDATE notes SET note=? WHERE id = ? AND username = ?";
    $stmt = mysqli_prepare($conn, $sql);
    $note_text = "AI generated note";
    mysqli_stmt_bind_param($stmt, "sis", $note_text, $note_id, $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header('Location: dashboard.php');
    exit();
}


if (isset($_GET['delete'])) {
  $note_id = intval($_GET['delete']);
  $delete_query = "DELETE FROM notes WHERE id=? AND username=?";
  $stmt = mysqli_prepare($conn, $delete_query);
  mysqli_stmt_bind_param($stmt, "is", $note_id, $username);
  if (mysqli_stmt_execute($stmt)) {
      header('Location: dashboard.php');
      exit();
  } else {
      echo "
<p style='color: red;'>Error deleting note.</p>";
  }
  mysqli_stmt_close($stmt);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $cat_id = isset($_POST['category_id']) && $_POST['category_id'] !== ''
  ? intval($_POST['category_id'])
  : null;
    $note = mysqli_real_escape_string($conn, $_POST['note']);

    if (isset($_POST['note_id']) && !empty($_POST['note_id'])) {

      $note_id = (int)$_POST['note_id'];
      $cat_id  = ($_POST['category_id'] !== '') ? (int)$_POST['category_id'] : null;
  
      $sql  = "UPDATE notes
               SET note = ?, category_id = ?
               WHERE id = ? AND username = ?";
  
      $stmt = $conn->prepare($sql);
      /*            s      i        i      s   */
      $stmt->bind_param("siis", $note, $cat_id, $note_id, $username);
  }else {
        $sql = "INSERT INTO notes (username, note, category_id) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssi", $username, $note, $cat_id);
    }

    if (mysqli_stmt_execute($stmt)) {
        header('Location: dashboard.php');
        exit();
    } else {
        $message = "
<p style='color: red;'>An error occurred. Please try again later.</p>";
    }
    mysqli_stmt_close($stmt);
}

if (isset($_GET['edit'])) {
  $note_id = intval($_GET['edit']);

  $fetch_sql  = "SELECT note, category_id
                 FROM notes
                 WHERE id=? AND username=?";
  $fetch_stmt = mysqli_prepare($conn, $fetch_sql);
  mysqli_stmt_bind_param($fetch_stmt, "is", $note_id, $username);
  mysqli_stmt_execute($fetch_stmt);
  mysqli_stmt_bind_result($fetch_stmt, $note_text, $currentCat);
  mysqli_stmt_fetch($fetch_stmt);
  mysqli_stmt_close($fetch_stmt);
}



$categories = [];
$cat_sql = "SELECT id, name FROM categories WHERE username=? OR username IS NULL";
$cat_stmt = mysqli_prepare($conn, $cat_sql);
mysqli_stmt_bind_param($cat_stmt, "s", $username);
mysqli_stmt_execute($cat_stmt);
$result = mysqli_stmt_get_result($cat_stmt);
$categories = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($cat_stmt);
?>




<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title> <?= isset($_GET['edit']) ? 'Edit Note' : 'Create Note'; ?> </title>
    <link rel="stylesheet" href="../styles.css" />
  </head>
  <body>
    <div class="auth-container">
      <h2> <?= isset($_GET['edit']) ? 'Edit Note' : 'Create Note'; ?> </h2>
      <form class="auth-form" action="note.php" method="POST">
      <textarea name="note" rows="5" cols="100" placeholder="Write note here..."><?=
    isset($note_text) ? htmlspecialchars($note_text) : '' ?></textarea>
        <select name="category_id" style="margin:15px 0;width:100%;padding:10px;">
          <option value="">— None —</option> <?php foreach ($categories as $cat): ?> <option value="
						<?= $cat['id'] ?>" <?= $cat['id'] == $currentCat ? 'selected' : '' ?>> <?= htmlspecialchars($cat['name']) ?> </option> <?php endforeach; ?>
        </select>
        <?php if (isset($_GET['edit'])): ?> <input type="hidden" name="note_id" value="
					<?= htmlspecialchars($note_id); ?>"> <?php endif; ?>
        <input type="submit" class="submit-btn" value="
						<?= isset($_GET['edit']) ? 'Update' : 'Create'; ?> Note">
      </form> <?= $message ?>
    </div>
  </body>
</html> <?php mysqli_close($conn); ?>
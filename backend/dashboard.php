<?php
session_start();
require 'authenticated.php';
require 'db.php';

$username = $_SESSION['username'];

$stmt_cats = $conn->prepare("
    SELECT id, name
    FROM categories
    WHERE username = ?
    ORDER BY name
");
$stmt_cats->bind_param("s", $username);
$stmt_cats->execute();
$cats_res   = $stmt_cats->get_result();
$categories = $cats_res->fetch_all(MYSQLI_ASSOC);

$catMap = [];
foreach ($categories as $c) { $catMap[$c['id']] = $c['name']; }

$selectedCatId   = isset($_GET['cat_id']) && ctype_digit($_GET['cat_id'])
                 ? (int)$_GET['cat_id']
                 : null;

$selectedCatName = $selectedCatId && isset($catMap[$selectedCatId])
                 ? $catMap[$selectedCatId]
                 : null;



if ($selectedCatId) {
    $stmt_user_notes = $conn->prepare("
        SELECT id, note, username, category_id
        FROM notes
        WHERE username = ?
          AND category_id = ?
    ");
    $stmt_user_notes->bind_param("si", $username, $selectedCatId);
} else {
    $stmt_user_notes = $conn->prepare("
        SELECT id, note, username, category_id
        FROM notes
        WHERE username = ?
    ");
    $stmt_user_notes->bind_param("s", $username);
}
$stmt_user_notes->execute();
$user_notes_result = $stmt_user_notes->get_result();

$stmt_other_notes = $conn->prepare("
    SELECT id, note, username, category_id
    FROM notes 
    WHERE username != ? 
");
$stmt_other_notes->bind_param("s", $username);
$stmt_other_notes->execute();
$other_notes_result = $stmt_other_notes->get_result();

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes Dashboard</title>
    <link rel="stylesheet" href="../styles.css" />
    <style>
      .dashboard-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 30px;
        display: flex;
        flex-direction: column;
        align-items: center;
      }

      .welcome-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 40px;
        border-bottom: 1px solid #eee;
        padding-bottom: 20px;
        width: 100%;
      }

      .note-form-container {
        background-color: #f9f9f9;
        padding: 30px;
        border-radius: 8px;
        margin-bottom: 40px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        text-align: center;
        max-width: 800px;
        width: 100%;
        box-sizing: border-box;
      }

      .note-form {
        width: 100%;
        max-width: 700px;
        margin: 0 auto;
      }

      .notes-section {
        background-color: white;
        border-radius: 8px;
        padding: 30px;
        margin-bottom: 40px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        width: 100%;
      }

      .section-header {
        border-bottom: 2px solid #5cb85c;
        padding-bottom: 15px;
        margin-bottom: 25px;
        color: #333;
      }

      .notes-list {
        list-style-type: none;
        padding: 0;
      }

      .note-item {
        background-color: #f9f9f9;
        border-radius: 6px;
        padding: 20px;
        margin-bottom: 20px;
        border-left: 4px solid #5cb85c;
      }

      .note-content {
        margin-bottom: 15px;
        line-height: 1.6;
        text-align: left;
      }

      .note-actions {
        text-align: right;
        padding-top: 10px;
        border-top: 1px solid #eee;
      }

      .action-link {
        display: inline-block;
        color: #5cb85c;
        text-decoration: none;
        margin-left: 15px;
        font-size: 14px;
      }

      .action-link:hover {
        text-decoration: underline;
      }

      .other-note-item {
        background-color: #f9f9f9;
        border-radius: 6px;
        padding: 20px;
        margin-bottom: 20px;
        border-left: 4px solid #66a3ff;
      }

      .note-author {
        font-weight: bold;
        color: #66a3ff;
        margin-bottom: 12px;
        text-align: left;
      }

      textarea {
        width: 100%;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        resize: vertical;
        font-family: inherit;
        font-size: 16px;
        box-sizing: border-box;
        display: block;
        margin: 0 auto;
      }

      button {
        background-color: #5cb85c;
        color: white;
        border: none;
        padding: 12px 50px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
        min-width: 200px;
      }

      button:hover {
        background-color: #4cae4c;
      }

      .logout-button {
        background-color: #f44336;
      }

      .logout-button:hover {
        background-color: #d32f2f;
      }

      .textbook-link {
        text-decoration: none;
      }

      .textbook {
        --hue: 140;
        --cover: hsl(var(--hue) 65% 45%);
        --cover-light: hsl(var(--hue) 65% 54%);
        --pages: #fff;
        width: 140px;
        height: 190px;
        position: relative;
        transform: rotateY(-14deg);
        transform-style: preserve-3d;
        transition: transform .25s;
        cursor: pointer;
      }

      .textbook:hover {
        transform: rotateY(-6deg) translateX(4px);
      }

      .textbook::before,
      .textbook::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border-radius: 4px;
        backface-visibility: hidden;
      }

      .textbook::before {
        background: linear-gradient(135deg, var(--cover) 0%, var(--cover-light) 100%);
        box-shadow: 0 2px 5px rgba(0, 0, 0, .25);
        transform: translateZ(12px);
      }

      .textbook::after {
        background:
          repeating-linear-gradient(90deg,
            var(--pages) 0 2px,
            #f2f2f2 2px 4px);
        transform: translateZ(0);
      }

      .textbook-title {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 12px;
        text-align: center;
        font-weight: 700;
        color: #fff;
        pointer-events: none;
        z-index: 1;
        transform: translateZ(14px);
      }
    </style>
  </head>
  <body>
    <div class="dashboard-container">
      <div class="welcome-header">
        <h1>Welcome, <?php echo htmlspecialchars($username); ?> </h1>
        <div style="display: flex; gap: 10px; align-items: center;">
          <form action="create_category.php" method="POST" style="display: flex; align-items: center; gap: 5px; background-color: #ffa500; border: none; border-radius: 4px; padding: 5px 10px;">
            <input type="text" name="category_name" placeholder="New category" style="border:none; border-radius:4px; width:120px;" required>
            <button type="submit" style="border-radius:4px; padding: 7px 15px; background:none; border:none; color: white; font-weight: bold; cursor: pointer;">+ Category</button>
          </form>
          <a href="index.php/user/leaderboard" style="text-decoration: none;">
            <button type="button" style="background-color:rgb(7, 82, 54); color: white; border: none; border-radius: 4px; padding: 11px 15px;">Leaderboard</button>
          </a>
          <a href="quiz_history_view.php" style="text-decoration: none;">
            <button type="button" style="background-color: #66a3ff; color: white; border: none; border-radius: 4px; padding: 11px 15px;">Quiz History</button>
          </a>
          <form action="logout.php" method="POST">
            <button type="submit" class="logout-button" style="background-color: #d9534f; color: white; border: none; border-radius: 4px; padding: 11px 15px;">Logout</button>
          </form>
        </div>
      </div>
      <div class="note-form-container">
        <h2 class="section-header">Create New Note</h2>
        <form action="note.php" method="POST" class="note-form">
          <textarea name="note" rows="4" placeholder="Write your note here..." required></textarea>
          <div style="margin-top: 20px; text-align: center;">
            <select name="category_id" style="margin-top:15px;width:100%;padding:10px;">
              <option value="">— None —</option> <?php foreach ($categories as $cat): ?> <option value="
											<?= $cat['id'] ?>"> <?= htmlspecialchars($cat['name']) ?> </option> <?php endforeach; ?>
            </select>
            <button type="submit">Create Note</button>
          </div>
        </form>
      </div> <?php if (count($categories)): ?> <div class="notes-section" style="margin-bottom:30px;">
        <h2 class="section-header">Your Textbooks</h2>
        <div style="display:flex;flex-wrap:wrap;gap:16px;">
          <a href="dashboard.php" class="textbook-link">
            <div class="textbook" style="--cover:#ffffff;--cover-light:#ffffff;">
              <span class="textbook-title" style="color:#333;">All Notes</span>
            </div>
          </a> <?php foreach ($categories as $cat): ?> <?php
        $hue = crc32($cat['id']) % 360
    ?> <a href="dashboard.php?cat_id=
									<?= $cat['id']; ?>" class="textbook-link">
            <div class="textbook" style="--hue:
										<?= $hue; ?>;">
              <span class="textbook-title"> <?= htmlspecialchars($cat['name']) ?> </span>
            </div>
          </a> <?php endforeach; ?>
        </div>
      </div> <?php endif; ?> <div class="notes-section">
        <h2 class="section-header"> <?= $selectedCatName ? "Notes in " . htmlspecialchars($selectedCatName)
                         : "Your Notes"; ?> </h2> <?php if ($user_notes_result->num_rows > 0): ?> <ul class="notes-list"> <?php while($row = $user_notes_result->fetch_assoc()): ?> <li class="note-item">
            <div class="note-content"> <?php if ($row['category_id']): 
                                $hash = crc32($row['category_id']);
                                $hue  = $hash % 360;
                                $bg   = "hsl($hue, 70%, 85%)";
                                $fg   = "hsl($hue, 70%, 25%)";
                                ?> <span style="background:
											<?= $bg; ?>;color:
											<?= $fg; ?>;padding:2px 8px;
                 border-radius:12px;font-size:12px;margin-right:6px;"> <?= htmlspecialchars($catMap[$row['category_id']] ?? '?') ?> </span> <?php endif; ?> <?php echo nl2br(htmlspecialchars($row['note'])); ?> </div>
            <div class="note-actions">
              <a href="index.php/ai/reformat?note_id=
											<?= $row['id'] ?>" class="action-link">AI </a>
              <a href="quiz_view.php?note_id=
											<?php echo $row['id']; ?>" class="action-link">Quiz </a>
              <a href="note.php?edit=
											<?php echo $row['id']; ?>" class="action-link">Edit </a>
              <a href="note.php?delete=
											<?php echo $row['id']; ?>" class="action-link">Delete </a>
            </div>
          </li> <?php endwhile; ?> </ul> <?php else: ?> <p>You haven't created any notes yet.</p> <?php endif; ?>
      </div>
      <div class="notes-section">
        <h2 class="section-header">Community Notes</h2> <?php if ($other_notes_result->num_rows > 0): ?> <ul class="notes-list"> <?php while($row = $other_notes_result->fetch_assoc()): ?> <li class="other-note-item">
            <div class="note-author"> <?php echo htmlspecialchars($row['username']); ?> </div>
            <div class="note-content"> <?php echo nl2br(htmlspecialchars($row['note'])); ?> </div>
          </li> <?php endwhile; ?> <?php else: ?> <p>No community notes found.</p> <?php endif; ?>
      </div>
    </div>
  </body>
</html> <?php
$stmt_user_notes->close();
$stmt_other_notes->close();
$conn->close();
?>
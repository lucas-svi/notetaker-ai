<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Leaderboard</title>
    <style>
      table { border-collapse: collapse; width: 50%; margin: 2em auto; }
      th, td { border: 1px solid #ccc; padding: 0.5em; text-align: center; }
      th { background: #f4f4f4; }
      .btn { display: inline-block; margin: 1em; padding: 0.5em 1em; background: #007BFF; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
  <h1 style="text-align:center;">👑 Leaderboard 👑</h1>

  <table>
    <thead>
      <tr>
        <th>Rank</th>
        <th>Username</th>
        <th>Points</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($leaderboard as $i => $user): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= htmlspecialchars($user['username']) ?></td>
          <td><?= (int)$user['quiz_points'] ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div style="text-align:center;">
    <a href="/notetaker-ai/backend/dashboard.php" class="btn">← Back to Dashboard</a>
  </div>
</body>
</html>

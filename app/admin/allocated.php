<?php
session_start();
if (empty($_SESSION['admin_logged_in'])) { header("Location: ../Index.php"); exit(); }
require_once '../Dbconnect.php';

$row = null; $searched = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searched    = true;
    $subjectName = trim($_POST['subject'] ?? '');
    $sessionId   = (int)($_POST['session_id'] ?? 0);

    // JOIN across normalized tables — DB Technique: relational query
    $stmt = $conn->prepare(
        "SELECT t.name AS teacher, s.name AS subject, s.course_code, ss.session_year
         FROM teacher_assignments ta
         JOIN teachers t ON t.id = ta.teacher_id
         JOIN subjects  s ON s.id = ta.subject_id
         JOIN sessions  ss ON ss.id = ta.session_id
         WHERE LOWER(s.name) = LOWER(?) AND ta.session_id = ?"
    );
    $stmt->bind_param("si", $subjectName, $sessionId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) $row = $result->fetch_assoc();
    $stmt->close();
}

$sessions = getSessions($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Allocation Status — Admin</title>
  <link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="bg-pattern"></div>
<nav>
  <div class="nav-brand"><div class="logo-mark">BZ</div><span class="nav-title">Admin — Allocation Status</span></div>
  <ul>
    <li><a href="Admin.php">Dashboard</a></li>
    <li><a href="assign.php">Assign</a></li>
    <li><a href="upteacher.php">Update Teacher</a></li>
    <li><a href="update.php">Records</a></li>
    <li><button class="btn-logout" onclick="window.location.href='../Index.php'">Log Out</button></li>
  </ul>
</nav>
<div class="page-wrapper">
  <div class="page-hero">
    <h1>Subject <span>Allocation Status</span></h1>
    <p>Look up which teacher is assigned to a given subject and session.</p>
  </div>

  <div style="max-width:560px;margin-bottom:32px;">
    <div class="card">
      <form method="POST">
        <div class="form-group">
          <label>Subject / Course Name</label>
          <input type="text" name="subject" required placeholder="e.g. DBMS">
        </div>
        <div class="form-group">
          <label>Session</label>
          <select name="session_id" required>
            <option value="">Select session</option>
            <?php foreach ($sessions as $id => $year): ?>
              <option value="<?= $id ?>"><?= htmlspecialchars($year) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
      </form>
    </div>
  </div>

  <?php if ($searched): ?>
    <?php if ($row): ?>
    <div class="card">
      <h2 style="font-family:'Playfair Display',serif;font-size:1.2rem;margin-bottom:20px;">Allocation Record</h2>
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Teacher</th><th>Subject</th><th>Course Code</th><th>Session</th></tr></thead>
          <tbody>
            <tr>
              <td><?= htmlspecialchars($row['teacher']) ?></td>
              <td><?= htmlspecialchars($row['subject']) ?></td>
              <td><?= htmlspecialchars($row['course_code']) ?></td>
              <td><?= htmlspecialchars($row['session_year']) ?></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <?php else: ?>
    <div class="alert alert-error">No allocation record found for the specified subject and session.</div>
    <?php endif; ?>
  <?php endif; ?>
</div>
<footer>Department of Computer Engineering — BZU Multan &copy; <?= date('Y') ?></footer>
</body>
</html>

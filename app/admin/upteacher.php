<?php
session_start();
if (empty($_SESSION['admin_logged_in'])) { header("Location: ../Index.php"); exit(); }
require_once '../Dbconnect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['edit_tname']    = trim($_POST['tname']    ?? '');
    $_SESSION['edit_tsession'] = (int)($_POST['session_id'] ?? 0);
    $_SESSION['edit_tchange']  = $_POST['change'] ?? '';
    header('Location: updated_teacher.php'); exit();
}

$sessions = getSessions($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Update Teacher — Admin</title>
  <link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="bg-pattern"></div>
<nav>
  <div class="nav-brand"><div class="logo-mark">BZ</div><span class="nav-title">Admin — Update Teacher</span></div>
  <ul>
    <li><a href="Admin.php">Dashboard</a></li>
    <li><a href="assign.php">Assign</a></li>
    <li><a href="allocated.php">Allocation</a></li>
    <li><a href="update.php">Records</a></li>
    <li><button class="btn-logout" onclick="window.location.href='../Index.php'">Log Out</button></li>
  </ul>
</nav>
<div class="page-wrapper">
  <div class="page-hero">
    <h1>Update <span>Teacher Record</span></h1>
    <p>Search for a teacher and select what you want to modify.</p>
  </div>
  <div style="max-width:540px;">
    <div class="card">
      <form method="POST">
        <div class="form-group">
          <label>Teacher Name</label>
          <input type="text" name="tname" required placeholder="Full teacher name">
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
        <div class="form-group">
          <label>What to Update</label>
          <div style="display:flex;flex-direction:column;gap:12px;margin-top:4px;">
            <label style="display:flex;align-items:center;gap:10px;font-size:0.9rem;color:var(--white);text-transform:none;letter-spacing:0;font-weight:400;cursor:pointer;">
              <input type="radio" name="change" value="subject" style="width:auto;accent-color:var(--gold);" required> Change Course Name
            </label>
            <label style="display:flex;align-items:center;gap:10px;font-size:0.9rem;color:var(--white);text-transform:none;letter-spacing:0;font-weight:400;cursor:pointer;">
              <input type="radio" name="change" value="coursecode" style="width:auto;accent-color:var(--gold);"> Change Course Code
            </label>
          </div>
        </div>
        <div style="display:flex;gap:12px;margin-top:8px;">
          <button type="submit" class="btn btn-primary">Search Teacher</button>
          <a href="Admin.php" class="btn btn-ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
<footer>Department of Computer Engineering — BZU Multan &copy; <?= date('Y') ?></footer>
</body>
</html>

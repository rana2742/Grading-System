<?php
session_start();
if (empty($_SESSION['admin_logged_in'])) { header("Location: ../Index.php"); exit(); }
if (empty($_SESSION['edit_mark_id']))    { header("Location: update.php");   exit(); }
require_once '../Dbconnect.php';

$mark_id    = (int)$_SESSION['edit_mark_id'];
$name       = $_SESSION['edit_name']    ?? '';
$roll_no    = $_SESSION['edit_roll_no'] ?? '';
$subject    = $_SESSION['edit_subject'] ?? '';
$session_yr = $_SESSION['edit_session'] ?? '';

$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mid   = (int)$_POST['mid_marks'];
    $ses   = (int)$_POST['sessional'];
    $fin   = (int)$_POST['final_marks'];

    // DB Technique: UPDATE on the single marks table by PK.
    // The TRIGGER recalculates total_marks automatically.
    $stmt = $conn->prepare(
        "UPDATE marks SET mid_marks = ?, sessional = ?, final_marks = ?
         WHERE id = ?"
    );
    $stmt->bind_param("iiii", $mid, $ses, $fin, $mark_id);
    if ($stmt->execute()) {
        $success = "Record updated successfully!";
    } else {
        $error = "Update failed: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch current marks to pre-fill form
$stmt = $conn->prepare("SELECT mid_marks, sessional, final_marks, total_marks FROM marks WHERE id = ?");
$stmt->bind_param("i", $mark_id);
$stmt->execute();
$current = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Update Marks — Admin</title>
  <link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="bg-pattern"></div>
<nav>
  <div class="nav-brand"><div class="logo-mark">BZ</div><span class="nav-title">Admin — Update Marks</span></div>
  <ul>
    <li><a href="Admin.php">Dashboard</a></li>
    <li><a href="update.php">Student Records</a></li>
    <li><button class="btn-logout" onclick="window.location.href='../Index.php'">Log Out</button></li>
  </ul>
</nav>
<div class="page-wrapper">
  <div class="page-hero">
    <h1>Update <span>Student Marks</span></h1>
    <p>Editing record for <strong style="color:var(--gold)"><?= htmlspecialchars($name) ?></strong> — Roll No. <?= htmlspecialchars($roll_no) ?> · <?= htmlspecialchars($subject) ?> · Session <?= htmlspecialchars($session_yr) ?></p>
  </div>

  <div style="max-width:520px;">
    <?php if ($success): ?><div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card">
      <div style="background:rgba(201,168,76,0.07);border:1px solid var(--border);border-radius:10px;padding:16px 20px;margin-bottom:24px;">
        <p style="font-size:0.82rem;color:var(--text-muted);margin-bottom:4px;">Editing marks for:</p>
        <p style="font-weight:600;font-size:0.95rem;"><?= htmlspecialchars($name) ?> &nbsp;·&nbsp; Roll #<?= htmlspecialchars($roll_no) ?></p>
        <p style="font-size:0.82rem;color:var(--text-muted);margin-top:2px;"><?= htmlspecialchars($subject) ?> &nbsp;|&nbsp; Session <?= htmlspecialchars($session_yr) ?></p>
      </div>

      <form method="POST">
        <div class="form-group">
          <label>Mid Term Marks <span style="color:var(--text-muted);font-weight:400;">(max 30)</span></label>
          <input type="number" name="mid_marks" required min="0" max="30" value="<?= $current['mid_marks'] ?? '' ?>">
        </div>
        <div class="form-group">
          <label>Sessional Marks <span style="color:var(--text-muted);font-weight:400;">(max 20)</span></label>
          <input type="number" name="sessional" required min="0" max="20" value="<?= $current['sessional'] ?? '' ?>">
        </div>
        <div class="form-group">
          <label>Final Exam Marks <span style="color:var(--text-muted);font-weight:400;">(max 50)</span></label>
          <input type="number" name="final_marks" required min="0" max="50" value="<?= $current['final_marks'] ?? '' ?>">
        </div>
        <div class="form-group">
          <label>Current Total (auto-recalculated by DB trigger)</label>
          <div style="font-family:'Playfair Display',serif;font-size:1.6rem;color:var(--gold);font-weight:700;"><?= $current['total_marks'] ?? '—' ?> / 100</div>
        </div>
        <div style="display:flex;gap:12px;">
          <button type="submit" class="btn btn-primary">Update Record</button>
          <a href="update.php" class="btn btn-ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
<footer>Department of Computer Engineering — BZU Multan &copy; <?= date('Y') ?></footer>
</body>
</html>

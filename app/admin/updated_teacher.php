<?php
session_start();
if (empty($_SESSION['admin_logged_in'])) { header("Location: ../Index.php"); exit(); }
require_once '../Dbconnect.php';

$tname     = $_SESSION['edit_tname']    ?? '';
$sessionId = (int)($_SESSION['edit_tsession'] ?? 0);
$change    = $_SESSION['edit_tchange']  ?? '';

$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    // DB Technique: UPDATE with JOIN-equivalent subquery on normalized tables
    if ($change === 'subject') {
        $val = trim($_POST['subject'] ?? '');
        // Update subject name in subjects table via subquery through assignment
        $stmt = $conn->prepare(
            "UPDATE subjects s
             JOIN teacher_assignments ta ON ta.subject_id = s.id
             JOIN teachers t             ON t.id = ta.teacher_id
             SET s.name = ?
             WHERE t.name = ? AND ta.session_id = ?"
        );
        $stmt->bind_param("ssi", $val, $tname, $sessionId);
    } elseif ($change === 'coursecode') {
        $val = strtoupper(trim($_POST['coursecode'] ?? ''));
        $stmt = $conn->prepare(
            "UPDATE subjects s
             JOIN teacher_assignments ta ON ta.subject_id = s.id
             JOIN teachers t             ON t.id = ta.teacher_id
             SET s.course_code = ?
             WHERE t.name = ? AND ta.session_id = ?"
        );
        $stmt->bind_param("ssi", $val, $tname, $sessionId);
    }

    if (isset($stmt)) {
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $success = "Teacher record updated successfully!";
        } elseif ($stmt->affected_rows === 0) {
            $error = "No matching teacher/session found to update.";
        } else {
            $error = "Update failed: " . $stmt->error;
        }
        $stmt->close();
    }
}

$labels = ['subject' => 'Course Name', 'coursecode' => 'Course Code'];
$label  = $labels[$change] ?? 'Field';

// Load session year for display
$sessions = getSessions($conn);
$sessionYear = $sessions[$sessionId] ?? "ID $sessionId";
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
    <li><a href="upteacher.php">Back</a></li>
    <li><button class="btn-logout" onclick="window.location.href='../Index.php'">Log Out</button></li>
  </ul>
</nav>
<div class="page-wrapper">
  <div class="page-hero">
    <h1>Update <span><?= htmlspecialchars($label) ?></span></h1>
    <p>Editing <strong style="color:var(--gold)"><?= htmlspecialchars($tname) ?></strong> — Session <?= htmlspecialchars($sessionYear) ?></p>
  </div>
  <div style="max-width:480px;">
    <?php if ($success): ?><div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <div class="card">
      <div style="background:rgba(201,168,76,0.07);border:1px solid var(--border);border-radius:10px;padding:16px 20px;margin-bottom:24px;">
        <p style="font-size:0.82rem;color:var(--text-muted);">Updating <?= htmlspecialchars($label) ?> for:</p>
        <p style="font-weight:600;"><?= htmlspecialchars($tname) ?> &nbsp;|&nbsp; Session <?= htmlspecialchars($sessionYear) ?></p>
      </div>
      <form method="POST">
        <?php if ($change === 'subject'): ?>
          <div class="form-group"><label>New Course Name</label><input type="text" name="subject" required placeholder="Enter new course name"></div>
        <?php elseif ($change === 'coursecode'): ?>
          <div class="form-group"><label>New Course Code</label><input type="text" name="coursecode" required placeholder="e.g. CPE-401"></div>
        <?php endif; ?>
        <div style="display:flex;gap:12px;">
          <button type="submit" name="submit" class="btn btn-primary">Save Changes</button>
          <a href="upteacher.php" class="btn btn-ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
<footer>Department of Computer Engineering — BZU Multan &copy; <?= date('Y') ?></footer>
</body>
</html>

<?php
session_start();
if (empty($_SESSION['teacher_logged_in'])) {
    header("Location: ../Index.php"); exit();
}
require_once '../Dbconnect.php';

$subject_id = (int)$_SESSION['subject_id'];
$session_id = (int)$_SESSION['session_id'];
$success = ''; $error = '';

// ================================================================
//  ADD / UPDATE MARKS
//  DB Technique: INSERT ... ON DUPLICATE KEY UPDATE avoids duplicate
//  rows for the same student+subject (enforced by UNIQUE KEY).
//  total_marks is handled by the DB TRIGGER — no PHP calculation.
// ================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roll_no     = trim($_POST['roll_no']     ?? '');
    $name        = trim($_POST['name']        ?? '');
    $mid_marks   = (int)($_POST['mid_marks']   ?? 0);
    $sessional   = (int)($_POST['sessional']   ?? 0);
    $final_marks = (int)($_POST['final_marks'] ?? 0);

    if ($mid_marks > 30 || $sessional > 20 || $final_marks > 50) {
        $error = "Marks out of range — Mid ≤30, Sessional ≤20, Final ≤50.";
    } else {
        // 1. Upsert student (get or create)
        $stmt = $conn->prepare(
            "INSERT INTO students (roll_no, name, session_id)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE name = VALUES(name), id = LAST_INSERT_ID(id)"
        );
        $stmt->bind_param("ssi", $roll_no, $name, $session_id);
        $stmt->execute();
        $student_id = $conn->insert_id;
        $stmt->close();

        // 2. Upsert marks — trigger computes total_marks automatically
        $stmt = $conn->prepare(
            "INSERT INTO marks (student_id, subject_id, mid_marks, sessional, final_marks)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               mid_marks    = VALUES(mid_marks),
               sessional    = VALUES(sessional),
               final_marks  = VALUES(final_marks)"
        );
        $stmt->bind_param("iiiii", $student_id, $subject_id, $mid_marks, $sessional, $final_marks);
        if ($stmt->execute()) {
            $success = "Marks for " . htmlspecialchars($name) . " saved successfully!";
        } else {
            $error = "DB Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// ================================================================
//  LOAD all marks for this subject + session (for the table view)
//  JOIN across 3 tables — demonstrates proper relational query
// ================================================================
$records = [];
$stmt = $conn->prepare(
    "SELECT st.roll_no, st.name, m.mid_marks, m.sessional, m.final_marks, m.total_marks, m.updated_at
     FROM marks m
     JOIN students st ON st.id = m.student_id
     WHERE m.subject_id = ? AND st.session_id = ?
     ORDER BY st.roll_no"
);
$stmt->bind_param("ii", $subject_id, $session_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $records[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Teacher Dashboard — BZU Grading</title>
  <link rel="stylesheet" href="teacher.css">
</head>
<body>
<div class="bg-pattern"></div>
<nav>
  <div class="nav-brand">
    <div class="logo-mark">BZ</div>
    <span class="nav-title">Teacher Dashboard</span>
  </div>
  <div class="nav-actions">
    <a href="../Index.php" class="btn-logout">Log Out</a>
  </div>
</nav>

<div class="welcome-bar">
  Signed in as <strong><?= htmlspecialchars($_SESSION['tname']) ?></strong>
  &nbsp;·&nbsp; Subject: <strong><?= htmlspecialchars($_SESSION['subject']) ?></strong>
  &nbsp;·&nbsp; Course Code: <strong><?= htmlspecialchars($_SESSION['coursecode']) ?></strong>
  &nbsp;·&nbsp; Session: <strong><?= htmlspecialchars($_SESSION['semester']) ?></strong>
</div>

<div class="page-wrapper">
  <div class="page-hero">
    <h1>Add <span>Student Marks</span></h1>
    <p>Enter marks for your assigned subject. Mid ≤30 · Sessional ≤20 · Final ≤50. Total is auto-calculated.</p>
  </div>

  <div class="two-col">
    <!-- ADD MARKS FORM -->
    <div class="card">
      <h2>Enter Marks</h2>
      <?php if ($success): ?><div class="alert alert-success">✓ <?= $success ?></div><?php endif; ?>
      <?php if ($error):   ?><div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>
      <form method="POST" onsubmit="return validateMarks(event)">
        <div class="form-group">
          <label>Roll Number</label>
          <input type="text" name="roll_no" required placeholder="e.g. 2023-CE-01">
        </div>
        <div class="form-group">
          <label>Student Name</label>
          <input type="text" name="name" required placeholder="Full student name">
        </div>
        <div class="form-group">
          <label>Mid Term Marks <span style="color:var(--text-muted);font-weight:400;">(max 30)</span></label>
          <input type="number" name="mid_marks" id="mid_marks" required placeholder="0–30" min="0" max="30">
        </div>
        <div class="form-group">
          <label>Sessional Marks <span style="color:var(--text-muted);font-weight:400;">(max 20)</span></label>
          <input type="number" name="sessional" id="sessional" required placeholder="0–20" min="0" max="20">
        </div>
        <div class="form-group">
          <label>Final Exam Marks <span style="color:var(--text-muted);font-weight:400;">(max 50)</span></label>
          <input type="number" name="final_marks" id="final_marks" required placeholder="0–50" min="0" max="50">
        </div>
        <div class="form-group">
          <label>Calculated Total</label>
          <div style="font-family:'Playfair Display',serif;font-size:1.8rem;color:var(--gold);font-weight:700;" id="total_preview">—</div>
          <div style="font-size:0.78rem;color:var(--text-muted);margin-top:4px;">Computed automatically by the database trigger</div>
        </div>
        <button type="submit" class="btn btn-primary">Save Marks</button>
      </form>
    </div>

    <!-- RECORDS TABLE -->
    <div class="card">
      <h2>Current Records (<?= count($records) ?> students)</h2>
      <?php if (empty($records)): ?>
        <p style="color:var(--text-muted);font-size:0.9rem;">No marks entered yet for this subject and session.</p>
      <?php else: ?>
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>Roll No</th>
              <th>Name</th>
              <th>Mid</th>
              <th>Ses</th>
              <th>Final</th>
              <th>Total</th>
              <th>Grade</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($records as $r):
              $total = (int)$r['total_marks'];
              $grade = $total >= 90 ? 'A+' : ($total >= 80 ? 'A' : ($total >= 70 ? 'B+' : ($total >= 60 ? 'B' : ($total >= 50 ? 'C' : 'F'))));
              $gc    = $total >= 70 ? '#2ecc71' : ($total >= 50 ? '#f39c12' : '#e74c3c');
            ?>
            <tr>
              <td><?= htmlspecialchars($r['roll_no']) ?></td>
              <td><?= htmlspecialchars($r['name']) ?></td>
              <td><?= $r['mid_marks'] ?></td>
              <td><?= $r['sessional'] ?></td>
              <td><?= $r['final_marks'] ?></td>
              <td><strong><?= $total ?></strong></td>
              <td><span style="color:<?= $gc ?>;font-weight:700;"><?= $grade ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<footer>Department of Computer Engineering — BZU Multan &copy; <?= date('Y') ?></footer>

<script>
function validateMarks(e) {
  const mid = parseInt(document.getElementById('mid_marks').value) || 0;
  const ses = parseInt(document.getElementById('sessional').value) || 0;
  const fin = parseInt(document.getElementById('final_marks').value) || 0;
  if (mid > 30 || ses > 20 || fin > 50) {
    alert('Marks out of range!');
    e.preventDefault();
    return false;
  }
  return true;
}

// Live total preview
['mid_marks','sessional','final_marks'].forEach(id => {
  document.getElementById(id).addEventListener('input', () => {
    const m = parseInt(document.getElementById('mid_marks').value) || 0;
    const s = parseInt(document.getElementById('sessional').value) || 0;
    const f = parseInt(document.getElementById('final_marks').value) || 0;
    document.getElementById('total_preview').textContent = m + s + f;
  });
});
</script>
</body>
</html>

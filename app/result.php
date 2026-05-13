<?php
session_start();
require_once 'Dbconnect.php';

$subject    = $_SESSION['subject']  ?? '';
$semester   = $_SESSION['semester'] ?? '';
$studentName = $_SESSION['student'] ?? '';
$roll_no    = $_SESSION['roll_no']  ?? '';

$row = null; $error = null;

if (!$subject || !$semester || !$studentName || !$roll_no) {
    $error = "Missing search parameters. Please go back and fill in all fields.";
} else {
    // DB Technique: single JOIN query across 4 normalized tables
    // instead of dynamically choosing from 4 session tables
    $stmt = $conn->prepare(
        "SELECT st.name, st.roll_no,
                m.mid_marks, m.sessional, m.final_marks, m.total_marks,
                s.name AS subject_name, s.course_code,
                ss.session_year
         FROM students st
         JOIN marks m     ON m.student_id  = st.id
         JOIN subjects s  ON s.id          = m.subject_id
         JOIN sessions ss ON ss.id         = st.session_id
         WHERE LOWER(st.name)   = LOWER(?)
           AND LOWER(st.roll_no) = LOWER(?)
           AND LOWER(s.name)    = LOWER(?)
           AND ss.session_year   = ?
         LIMIT 1"
    );
    $stmt->bind_param("ssss", $studentName, $roll_no, $subject, $semester);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
    } else {
        $error = "No matching record found. Please verify your name, roll number, subject and session.";
    }
    $stmt->close();
}
mysqli_close($conn);

function getGrade(int $total): array {
    if ($total >= 90) return ['A+', '#2ecc71'];
    if ($total >= 80) return ['A',  '#27ae60'];
    if ($total >= 70) return ['B+', '#f39c12'];
    if ($total >= 60) return ['B',  '#e67e22'];
    if ($total >= 50) return ['C',  '#e74c3c'];
    return ['F', '#c0392b'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Result — BZU Grading</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { display: flex; flex-direction: column; min-height: 100vh; }
    .result-page { flex: 1; display: flex; align-items: center; justify-content: center; padding: 60px 24px; }
    .result-container { width: 100%; max-width: 580px; }
    .result-header { text-align: center; margin-bottom: 28px; }
    .result-header h1 { font-family: 'Playfair Display', serif; font-size: 1.7rem; font-weight: 700; margin-bottom: 6px; }
    .result-header p { color: var(--text-muted); font-size: 0.88rem; }
    .result-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 20px; overflow: hidden; backdrop-filter: blur(20px); }
    .result-top { background: linear-gradient(135deg, var(--navy-light), var(--navy-mid)); padding: 28px 32px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
    .student-info h2 { font-family: 'Playfair Display', serif; font-size: 1.3rem; font-weight: 600; margin-bottom: 4px; }
    .student-info p { color: var(--text-muted); font-size: 0.85rem; }
    .grade-badge { width: 64px; height: 64px; border-radius: 16px; display: flex; flex-direction: column; align-items: center; justify-content: center; font-family: 'Playfair Display', serif; font-size: 1.6rem; font-weight: 700; border: 2px solid; flex-shrink: 0; }
    .grade-badge .grade-label { font-size: 0.6rem; font-family: 'DM Sans', sans-serif; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; margin-top: 2px; }
    .marks-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1px; background: var(--border); }
    .mark-cell { background: var(--card-bg); padding: 22px 28px; }
    .mark-cell .cell-label { font-size: 0.73rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 8px; }
    .mark-cell .cell-value { font-family: 'Playfair Display', serif; font-size: 1.6rem; font-weight: 700; color: var(--white); }
    .mark-cell.total-cell { grid-column: 1 / -1; background: rgba(201,168,76,0.06); }
    .mark-cell.total-cell .cell-value { color: var(--gold); font-size: 2rem; }
    .progress-bar { height: 4px; border-radius: 2px; background: rgba(255,255,255,0.08); margin-top: 8px; overflow: hidden; }
    .progress-fill { height: 100%; border-radius: 2px; background: linear-gradient(90deg, var(--gold), var(--gold-light)); }
    .result-footer { padding: 24px 32px; display: flex; gap: 12px; }
    .btn-sm { padding: 10px 22px; font-size: 0.85rem; }
    .error-card { background: var(--card-bg); border: 1px solid rgba(231,76,60,0.3); border-radius: 20px; padding: 40px; text-align: center; backdrop-filter: blur(20px); }
    .error-icon { font-size: 2.5rem; margin-bottom: 16px; }
    .error-card h2 { font-family: 'Playfair Display', serif; font-size: 1.3rem; margin-bottom: 10px; }
    .error-card p { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 24px; }
    @media (max-width: 480px) { .result-top { flex-direction: column; gap: 16px; align-items: flex-start; } .marks-grid { grid-template-columns: 1fr; } .mark-cell.total-cell { grid-column: auto; } .result-footer { flex-direction: column; } }
  </style>
</head>
<body>
<div class="bg-pattern"></div>
<header class="site-header">
  <div class="logo-mark">BZ</div>
  <div class="header-info">
    <span class="header-title">BZU Multan</span>
    <span class="header-subtitle">Result</span>
  </div>
  <nav>
    <a href="checkresult.php">← Search Again</a>
    <a href="Index.php">Home</a>
  </nav>
</header>

<div class="result-page">
  <div class="result-container">
    <div class="result-header">
      <h1>Academic Result</h1>
      <p>Department of Computer Engineering — BZU Multan</p>
    </div>

    <?php if ($row):
      $total = (int)$row['total_marks'];
      [$grade, $gradeColor] = getGrade($total);
    ?>
    <div class="result-card">
      <div class="result-top">
        <div class="student-info">
          <h2><?= htmlspecialchars($row['name']) ?></h2>
          <p>Roll No: <?= htmlspecialchars($row['roll_no']) ?> &nbsp;|&nbsp; <?= htmlspecialchars($row['subject_name']) ?> (<?= htmlspecialchars($row['course_code']) ?>)</p>
          <p style="margin-top:4px;font-size:0.8rem;color:var(--text-muted);">Session <?= htmlspecialchars($row['session_year']) ?></p>
        </div>
        <div class="grade-badge" style="color:<?= $gradeColor ?>;border-color:<?= $gradeColor ?>20;background:<?= $gradeColor ?>15;">
          <?= $grade ?>
          <span class="grade-label">Grade</span>
        </div>
      </div>

      <div class="marks-grid">
        <div class="mark-cell">
          <div class="cell-label">Mid Term Marks</div>
          <div class="cell-value"><?= $row['mid_marks'] ?></div>
          <div class="progress-bar"><div class="progress-fill" style="width:<?= min(100, $row['mid_marks'] * 3.33) ?>%"></div></div>
        </div>
        <div class="mark-cell">
          <div class="cell-label">Sessional Marks</div>
          <div class="cell-value"><?= $row['sessional'] ?></div>
          <div class="progress-bar"><div class="progress-fill" style="width:<?= min(100, $row['sessional'] * 5) ?>%"></div></div>
        </div>
        <div class="mark-cell">
          <div class="cell-label">Final Term Marks</div>
          <div class="cell-value"><?= $row['final_marks'] ?></div>
          <div class="progress-bar"><div class="progress-fill" style="width:<?= min(100, $row['final_marks'] * 2) ?>%"></div></div>
        </div>
        <div class="mark-cell total-cell">
          <div class="cell-label">Total Marks (out of 100)</div>
          <div class="cell-value"><?= $total ?></div>
          <div class="progress-bar"><div class="progress-fill" style="width:<?= $total ?>%"></div></div>
        </div>
      </div>

      <div class="result-footer">
        <a href="checkresult.php" class="btn btn-primary btn-sm">Search Another</a>
        <a href="Index.php" class="btn btn-ghost btn-sm">Go Home</a>
      </div>
    </div>

    <?php else: ?>
    <div class="error-card">
      <div class="error-icon">⚠️</div>
      <h2>Record Not Found</h2>
      <p><?= htmlspecialchars($error ?? 'The information you entered does not match our records.') ?></p>
      <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
        <a href="checkresult.php" class="btn btn-primary btn-sm">Try Again</a>
        <a href="Index.php" class="btn btn-ghost btn-sm">Home</a>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<footer class="site-footer">Department of Computer Engineering — BZU Multan &copy; <?= date('Y') ?></footer>
</body>
</html>

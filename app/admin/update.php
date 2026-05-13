<?php
session_start();
if (empty($_SESSION['admin_logged_in'])) { header("Location: ../Index.php"); exit(); }
require_once '../Dbconnect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roll_no    = trim($_POST['roll_no']    ?? '');
    $subject    = trim($_POST['subject']    ?? '');
    $session_id = (int)($_POST['session_id'] ?? 0);

    // Look up student + marks via JOIN — DB Technique: relational query
    $stmt = $conn->prepare(
        "SELECT st.id AS student_id, st.name, st.roll_no,
                m.id AS mark_id, m.mid_marks, m.sessional, m.final_marks, m.total_marks,
                s.id AS subject_id, s.name AS subject_name, ss.session_year
         FROM students st
         JOIN marks m     ON m.student_id = st.id
         JOIN subjects s  ON s.id = m.subject_id
         JOIN sessions ss ON ss.id = st.session_id
         WHERE st.roll_no = ? AND LOWER(s.name) = LOWER(?) AND st.session_id = ?
         LIMIT 1"
    );
    $stmt->bind_param("ssi", $roll_no, $subject, $session_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $rec = $result->fetch_assoc();
        // Store needed info in session to pass to update page
        $_SESSION['edit_mark_id']    = $rec['mark_id'];
        $_SESSION['edit_student_id'] = $rec['student_id'];
        $_SESSION['edit_name']       = $rec['name'];
        $_SESSION['edit_roll_no']    = $rec['roll_no'];
        $_SESSION['edit_subject']    = $rec['subject_name'];
        $_SESSION['edit_session']    = $rec['session_year'];
        $stmt->close();
        header("Location: updated.php"); exit();
    } else {
        $error = "No matching record found. Verify roll number, subject and session.";
        $stmt->close();
    }
}

$sessions = getSessions($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Update Student Record — Admin</title>
  <link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="bg-pattern"></div>
<nav>
  <div class="nav-brand"><div class="logo-mark">BZ</div><span class="nav-title">Admin — Student Records</span></div>
  <ul>
    <li><a href="Admin.php">Dashboard</a></li>
    <li><a href="assign.php">Assign</a></li>
    <li><a href="allocated.php">Allocation</a></li>
    <li><a href="upteacher.php">Teacher</a></li>
    <li><button class="btn-logout" onclick="window.location.href='../Index.php'">Log Out</button></li>
  </ul>
</nav>
<div class="page-wrapper">
  <div class="page-hero">
    <h1>Update <span>Student Record</span></h1>
    <p>Search by roll number, subject, and session to find and edit marks.</p>
  </div>
  <div style="max-width:540px;">
    <?php if ($error): ?><div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <div class="card">
      <form method="POST">
        <div class="form-group">
          <label>Roll Number</label>
          <input type="text" name="roll_no" required placeholder="e.g. 2023-CE-01">
        </div>
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
        <div style="display:flex;gap:12px;">
          <button type="submit" class="btn btn-primary">Search Record</button>
          <a href="Admin.php" class="btn btn-ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
<footer>Department of Computer Engineering — BZU Multan &copy; <?= date('Y') ?></footer>
</body>
</html>

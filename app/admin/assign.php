<?php
session_start();
if (empty($_SESSION['admin_logged_in'])) { header("Location: ../Index.php"); exit(); }
require_once '../Dbconnect.php';

$success = ''; $error = '';

// ================================================================
//  ASSIGN SUBJECT TO TEACHER
//  DB Technique: INSERT IGNORE prevents duplicate assignments
//  (UNIQUE KEY on teacher_id, subject_id, session_id).
//  We upsert teacher and subject records first, then link them.
// ================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacherName = trim($_POST['teacher_name'] ?? '');
    $teacherEmail = trim($_POST['teacher_email'] ?? '');
    $subjectName  = trim($_POST['subject_name']  ?? '');
    $courseCode   = strtoupper(trim($_POST['course_code'] ?? ''));
    $sessionId    = (int)($_POST['session_id'] ?? 0);

    if (!$teacherName || !$teacherEmail || !$subjectName || !$courseCode || !$sessionId) {
        $error = "All fields are required.";
    } else {
        // 1. Upsert teacher
        $stmt = $conn->prepare(
            "INSERT INTO teachers (name, email) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE name = VALUES(name), id = LAST_INSERT_ID(id)"
        );
        $stmt->bind_param("ss", $teacherName, $teacherEmail);
        $stmt->execute();
        $teacher_id = $conn->insert_id;
        $stmt->close();

        // 2. Upsert subject
        $stmt = $conn->prepare(
            "INSERT INTO subjects (name, course_code) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE name = VALUES(name), id = LAST_INSERT_ID(id)"
        );
        $stmt->bind_param("ss", $subjectName, $courseCode);
        $stmt->execute();
        $subject_id = $conn->insert_id;
        $stmt->close();

        // 3. Create assignment (ignore if duplicate)
        $stmt = $conn->prepare(
            "INSERT IGNORE INTO teacher_assignments (teacher_id, subject_id, session_id)
             VALUES (?, ?, ?)"
        );
        $stmt->bind_param("iii", $teacher_id, $subject_id, $sessionId);
        if ($stmt->execute()) {
            $success = "Subject '$subjectName' assigned to '$teacherName' successfully!";
        } else {
            $error = "DB Error: " . $stmt->error;
        }
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
  <title>Assign Subjects — Admin</title>
  <link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="bg-pattern"></div>
<nav>
  <div class="nav-brand"><div class="logo-mark">BZ</div><span class="nav-title">Admin — Assign Subjects</span></div>
  <ul>
    <li><a href="Admin.php">Dashboard</a></li>
    <li><a href="upteacher.php">Update Teacher</a></li>
    <li><a href="allocated.php">Allocation</a></li>
    <li><a href="update.php">Records</a></li>
    <li><button class="btn-logout" onclick="window.location.href='../Index.php'">Log Out</button></li>
  </ul>
</nav>

<div class="page-wrapper">
  <div class="page-hero">
    <h1>Assign <span>Subjects</span></h1>
    <p>Link a teacher to a subject and session. New teachers and subjects are created automatically.</p>
  </div>

  <div style="max-width:620px;">
    <?php if ($success): ?><div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card">
      <form method="POST">
        <div class="form-group">
          <label>Teacher Name</label>
          <input type="text" name="teacher_name" required placeholder="Full name e.g. Dr.Shahid">
        </div>
        <div class="form-group">
          <label>Teacher Email</label>
          <input type="email" name="teacher_email" required placeholder="teacher@bzu.edu.pk">
        </div>
        <div class="form-group">
          <label>Subject / Course Name</label>
          <input type="text" name="subject_name" required placeholder="e.g. Data Structures">
        </div>
        <div class="form-group">
          <label>Course Code</label>
          <input type="text" name="course_code" required placeholder="e.g. CPE-301">
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
        <div style="display:flex;gap:12px;margin-top:8px;">
          <button type="submit" class="btn btn-primary">Assign Subject</button>
          <a href="Admin.php" class="btn btn-ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Show current assignments -->
  <div class="card" style="margin-top:32px;">
    <h2 style="font-family:'Playfair Display',serif;font-size:1.2rem;margin-bottom:20px;">Current Assignments</h2>
    <?php
    $allAssign = $conn->query(
        "SELECT t.name AS teacher, s.name AS subject, s.course_code, ss.session_year
         FROM teacher_assignments ta
         JOIN teachers t ON t.id = ta.teacher_id
         JOIN subjects  s ON s.id = ta.subject_id
         JOIN sessions  ss ON ss.id = ta.session_id
         ORDER BY ss.session_year, t.name"
    );
    if ($allAssign->num_rows === 0):
    ?>
      <p style="color:var(--text-muted);">No assignments yet.</p>
    <?php else: ?>
    <div class="table-wrapper">
      <table class="data-table">
        <thead><tr><th>Teacher</th><th>Subject</th><th>Course Code</th><th>Session</th></tr></thead>
        <tbody>
          <?php while ($a = $allAssign->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($a['teacher']) ?></td>
            <td><?= htmlspecialchars($a['subject']) ?></td>
            <td><?= htmlspecialchars($a['course_code']) ?></td>
            <td><?= htmlspecialchars($a['session_year']) ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<footer>Department of Computer Engineering — BZU Multan &copy; <?= date('Y') ?></footer>
</body>
</html>

<?php
session_start();
if (empty($_SESSION['admin_logged_in'])) {
    header("Location: ../Index.php"); exit();
}
require_once '../Dbconnect.php';

// Live stats using COUNT aggregation — DB technique: aggregate queries
$stats = [];
$res = $conn->query("SELECT COUNT(*) AS cnt FROM teacher_assignments"); $stats['assignments'] = $res->fetch_assoc()['cnt'];
$res = $conn->query("SELECT COUNT(*) AS cnt FROM teachers");            $stats['teachers']    = $res->fetch_assoc()['cnt'];
$res = $conn->query("SELECT COUNT(*) AS cnt FROM students");            $stats['students']    = $res->fetch_assoc()['cnt'];
$res = $conn->query("SELECT COUNT(*) AS cnt FROM sessions");            $stats['sessions']    = $res->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard — BZU Grading</title>
  <link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="bg-pattern"></div>

<nav>
  <div class="nav-brand">
    <div class="logo-mark">BZ</div>
    <span class="nav-title">Admin Dashboard</span>
  </div>
  <ul>
    <li><a href="assign.php">Assign Subjects</a></li>
    <li><a href="upteacher.php">Update Teacher</a></li>
    <li><a href="allocated.php">Allocation Status</a></li>
    <li><a href="update.php">Student Records</a></li>
    <li><button class="btn-logout" onclick="window.location.href='../Index.php'">Log Out</button></li>
  </ul>
</nav>

<div class="page-wrapper">
  <div class="page-hero">
    <h1>Welcome, <span><?= htmlspecialchars($_SESSION['admin_name']) ?></span></h1>
    <p>Manage teacher assignments, student records, and subject allocation from a central dashboard.</p>
  </div>

  <div class="stats-row">
    <div class="stat-card"><div class="icon">🎓</div><div class="value"><?= $stats['sessions'] ?></div><div class="label">Active Sessions</div></div>
    <div class="stat-card"><div class="icon">📚</div><div class="value"><?= $stats['assignments'] ?></div><div class="label">Assigned Subjects</div></div>
    <div class="stat-card"><div class="icon">👩‍🏫</div><div class="value"><?= $stats['teachers'] ?></div><div class="label">Teachers</div></div>
    <div class="stat-card"><div class="icon">📋</div><div class="value"><?= $stats['students'] ?></div><div class="label">Student Records</div></div>
  </div>

  <div class="feature-grid">
    <a href="assign.php" class="feature-card">
      <div class="fc-icon">📌</div>
      <h3>Assign Subjects</h3>
      <p>Assign courses and subjects to teachers with session-specific credentials.</p>
      <div class="fc-arrow">Go to Assign Subjects →</div>
    </a>
    <a href="upteacher.php" class="feature-card">
      <div class="fc-icon">✏️</div>
      <h3>Update Teacher Record</h3>
      <p>Search and modify existing teacher assignments and course information.</p>
      <div class="fc-arrow">Update Teacher →</div>
    </a>
    <a href="allocated.php" class="feature-card">
      <div class="fc-icon">📊</div>
      <h3>Allocation Status</h3>
      <p>Review current subject allocations across sessions and departments.</p>
      <div class="fc-arrow">View Allocation →</div>
    </a>
    <a href="update.php" class="feature-card">
      <div class="fc-icon">🗂️</div>
      <h3>Update Student Record</h3>
      <p>Search, edit and manage student grade records and academic data.</p>
      <div class="fc-arrow">Manage Records →</div>
    </a>
  </div>

  <div class="card">
    <h2 style="font-family:'Playfair Display',serif;font-size:1.3rem;margin-bottom:12px;">DB Design Improvements</h2>
    <p style="color:var(--text-muted);font-size:0.9rem;line-height:1.7;margin-bottom:16px;">
      This system uses a fully normalized (3NF) database. The old design had 4 separate session tables
      (session21–24) which was redundant and unscalable. Now a single <code>marks</code> table with a
      <code>session_id</code> foreign key handles all sessions with proper indexing and referential integrity.
    </p>
    <p style="color:var(--text-muted);font-size:0.9rem;line-height:1.7;">
      Passwords are hashed (SHA-256). Total marks are auto-computed by a database TRIGGER — not PHP —
      ensuring data consistency even if records are modified directly in SQL. All queries use prepared
      statements to prevent SQL injection.
    </p>
  </div>
</div>

<footer>Department of Computer Engineering — Bahauddin Zakariya University Multan &copy; <?= date('Y') ?></footer>
</body>
</html>

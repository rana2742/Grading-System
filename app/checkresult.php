<?php
session_start();
require_once 'Dbconnect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['student'] = trim($_POST['studentn'] ?? '');
    $_SESSION['semester'] = trim($_POST['semester'] ?? '');
    $_SESSION['roll_no']  = trim($_POST['roll_no']  ?? '');
    $_SESSION['subject']  = trim($_POST['subject']  ?? '');
    header('Location: result.php'); exit();
}

$sessions = getSessions($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Check Result — BZU Grading</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { display: flex; flex-direction: column; min-height: 100vh; }
    .result-hero { flex: 1; display: flex; align-items: center; justify-content: center; padding: 60px 24px; }
    .result-container { width: 100%; max-width: 520px; }
    .result-header { text-align: center; margin-bottom: 36px; }
    .result-header .icon { width: 68px; height: 68px; border-radius: 18px; background: linear-gradient(135deg, var(--gold-dim), rgba(201,168,76,0.3)); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 20px; }
    .result-header h1 { font-family: 'Playfair Display', serif; font-size: 1.8rem; font-weight: 700; margin-bottom: 8px; }
    .result-header p { color: var(--text-muted); font-size: 0.9rem; }
    .result-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 20px; padding: 36px; backdrop-filter: blur(20px); }
    .btn-full { width: 100%; }
    .back-link { margin-top: 20px; text-align: center; }
    .back-link a { font-size: 0.85rem; color: var(--text-muted); text-decoration: none; transition: color 0.2s; }
    .back-link a:hover { color: var(--gold); }
  </style>
</head>
<body>
<div class="bg-pattern"></div>
<header class="site-header">
  <div class="logo-mark">BZ</div>
  <div class="header-info">
    <span class="header-title">BZU Multan</span>
    <span class="header-subtitle">Result Inquiry</span>
  </div>
  <nav><a href="Index.php">← Back to Login</a></nav>
</header>

<div class="result-hero">
  <div class="result-container">
    <div class="result-header">
      <div class="icon">📋</div>
      <h1>Check Your Result</h1>
      <p>Enter your details below to retrieve your academic performance record.</p>
    </div>
    <div class="result-card">
      <form action="checkresult.php" method="POST">
        <div class="form-group">
          <label>Student Name</label>
          <input type="text" name="studentn" required placeholder="Enter your full name">
        </div>
        <div class="form-group">
          <label>Session</label>
          <select name="semester" required>
            <option value="">Select your session</option>
            <?php foreach ($sessions as $id => $year): ?>
              <option value="<?= htmlspecialchars($year) ?>"><?= htmlspecialchars($year) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Roll Number</label>
          <input type="text" name="roll_no" required placeholder="e.g. 2023-CE-01">
        </div>
        <div class="form-group">
          <label>Subject Name</label>
          <input type="text" name="subject" required placeholder="e.g. DBMS">
        </div>
        <button type="submit" class="btn btn-primary btn-full">Search Result</button>
      </form>
    </div>
    <div class="back-link"><a href="Index.php">← Return to portal login</a></div>
  </div>
</div>

<footer class="site-footer">Department of Computer Engineering — BZU Multan &copy; <?= date('Y') ?></footer>
</body>
</html>

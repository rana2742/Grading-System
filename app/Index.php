<?php
session_start();
require_once 'Dbconnect.php';

$error_admin   = '';
$error_teacher = '';

// ================================================================
//  ADMIN LOGIN
// ================================================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login_type']) && $_POST['login_type'] === 'admin') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $adminId  = trim($_POST['id']       ?? '');

    $hashed = hashPassword($password);

    $stmt = $conn->prepare(
        "SELECT id FROM admins WHERE name = ? AND email = ? AND password = ? AND id = ?"
    );
    $stmt->bind_param("sssi", $name, $email, $hashed, $adminId);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_name']      = htmlspecialchars($name);
        $stmt->close();
        header("Location: admin/Admin.php");
        exit();
    } else {
        $error_admin = "Invalid credentials. Please try again.";
        $stmt->close();
    }
}

// ================================================================
//  TEACHER LOGIN
//  Looks up teacher_assignments JOIN teachers JOIN subjects JOIN sessions
// ================================================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login_type']) && $_POST['login_type'] === 'teacher') {
    $tname      = trim($_POST['tname']      ?? '');
    $subject    = trim($_POST['subject']    ?? '');
    $coursecode = trim($_POST['coursecode'] ?? '');
    $session    = trim($_POST['semester']   ?? '');

    $stmt = $conn->prepare(
        "SELECT ta.id, ta.teacher_id, ta.subject_id, ta.session_id,
                t.name AS teacher_name,
                s.name AS subject_name, s.course_code,
                ss.session_year
         FROM teacher_assignments ta
         JOIN teachers t  ON t.id  = ta.teacher_id
         JOIN subjects  s  ON s.id  = ta.subject_id
         JOIN sessions  ss ON ss.id = ta.session_id
         WHERE t.name = ? AND s.name = ? AND s.course_code = ? AND ss.session_year = ?"
    );
    $stmt->bind_param("ssss", $tname, $subject, $coursecode, $session);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['teacher_logged_in'] = true;
        $_SESSION['teacher_id']        = $row['teacher_id'];
        $_SESSION['tname']             = $row['teacher_name'];
        $_SESSION['subject']           = $row['subject_name'];
        $_SESSION['subject_id']        = $row['subject_id'];
        $_SESSION['coursecode']        = $row['course_code'];
        $_SESSION['semester']          = $row['session_year'];
        $_SESSION['session_id']        = $row['session_id'];
        $stmt->close();
        header("Location: teacher/teacher.php");
        exit();
    } else {
        $error_teacher = "Invalid credentials. Please check your name, subject, course code and session.";
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BZU Grading System — Login</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { display: flex; flex-direction: column; min-height: 100vh; overflow-x: hidden; }
    .hero { position: relative; min-height: calc(100vh - 72px); display: flex; align-items: center; overflow: hidden; }
    .hero-bg { position: absolute; inset: 0; z-index: 0; }
    .slideshow-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0; transition: opacity 1.2s ease; }
    .slideshow-img.active { opacity: 1; }
    .hero-overlay { position: absolute; inset: 0; background: linear-gradient(105deg, rgba(10,22,40,0.97) 0%, rgba(10,22,40,0.85) 42%, rgba(10,22,40,0.65) 100%); }
    .hero-content { position: relative; z-index: 2; width: 100%; max-width: 1160px; margin: 0 auto; padding: 60px 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 32px; align-items: start; }
    .hero-intro { padding-right: 20px; padding-top: 20px; }
    .hero-badge { display: inline-flex; align-items: center; gap: 8px; background: var(--gold-dim); border: 1px solid var(--border); color: var(--gold); font-size: 0.75rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; padding: 6px 14px; border-radius: 100px; margin-bottom: 28px; }
    .hero-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: var(--gold); }
    .hero-heading { font-family: 'Playfair Display', serif; font-size: clamp(2rem, 4vw, 3.2rem); font-weight: 700; line-height: 1.2; margin-bottom: 16px; color: var(--white); }
    .hero-heading span { color: var(--gold); }
    .hero-desc { color: var(--text-muted); font-size: 1rem; max-width: 400px; margin-bottom: 32px; }
    .hero-stat { display: flex; align-items: center; gap: 12px; margin-top: 48px; }
    .stat-block { text-align: center; padding: 16px 20px; background: var(--glass); border: 1px solid var(--border); border-radius: 12px; }
    .stat-block .num { font-family: 'Playfair Display', serif; font-size: 1.6rem; font-weight: 700; color: var(--gold); }
    .stat-block .lbl { font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em; margin-top: 2px; }
    .login-panel { background: rgba(17,34,64,0.92); border: 1px solid var(--border); border-radius: 20px; backdrop-filter: blur(24px); overflow: hidden; }
    .tab-bar { display: flex; border-bottom: 1px solid var(--border); }
    .tab-btn { flex: 1; padding: 18px 24px; background: transparent; border: none; cursor: pointer; font-family: 'DM Sans', sans-serif; font-size: 0.9rem; font-weight: 600; color: var(--text-muted); transition: all 0.25s; position: relative; }
    .tab-btn.active { color: var(--gold); background: var(--gold-dim); }
    .tab-btn.active::after { content: ''; position: absolute; bottom: -1px; left: 0; right: 0; height: 2px; background: var(--gold); }
    .tab-panel { display: none; padding: 32px; }
    .tab-panel.active { display: block; }
    .panel-heading { font-family: 'Playfair Display', serif; font-size: 1.4rem; font-weight: 600; margin-bottom: 6px; }
    .panel-sub { color: var(--text-muted); font-size: 0.85rem; margin-bottom: 28px; }
    .result-cta { margin-top: 24px; padding: 16px 20px; background: rgba(201,168,76,0.06); border: 1px solid var(--border); border-radius: 12px; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .result-cta p { font-size: 0.85rem; color: var(--text-muted); }
    .result-cta a { white-space: nowrap; font-size: 0.85rem; font-weight: 600; color: var(--gold); text-decoration: none; transition: color 0.2s; }
    .result-cta a:hover { color: var(--gold-light); }
    @media (max-width: 900px) { .hero-content { grid-template-columns: 1fr; } .hero-intro { padding-right: 0; } }
    @media (max-width: 480px) { .tab-panel { padding: 20px; } .hero-stat { display: none; } }
  </style>
</head>
<body>
<div class="bg-pattern"></div>

<header class="site-header">
  <div class="logo-mark">BZ</div>
  <div class="header-info">
    <span class="header-title">BZU Multan</span>
    <span class="header-subtitle">Online Grading System</span>
  </div>
  <nav>
    <a href="checkresult.php">Check Result</a>
  </nav>
</header>

<section class="hero">
  <div class="hero-bg">
    <img class="slideshow-img active" src="pics/book-6957870_1920.jpg" alt="">
    <img class="slideshow-img" src="pics/man-2562325_1920.jpg" alt="">
    <img class="slideshow-img" src="pics/university-105709_1920.jpg" alt="">
    <img class="slideshow-img" src="pics/Pexels.jpg" alt="">
    <div class="hero-overlay"></div>
  </div>

  <div class="hero-content">
    <div class="hero-intro">
      <div class="hero-badge">Department Portal</div>
      <h1 class="hero-heading">Academic<br><span>Grading</span><br>Management</h1>
      <p class="hero-desc">Streamlined grade management for Bahauddin Zakariya University's Department of Computer Engineering.</p>
      <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <a href="checkresult.php" class="btn btn-ghost" style="font-size:0.85rem;padding:10px 22px;">↗ Check Your Result</a>
      </div>
      <div class="hero-stat">
        <div class="stat-block"><div class="num">4</div><div class="lbl">Sessions</div></div>
        <div class="stat-block"><div class="num">3</div><div class="lbl">Portals</div></div>
        <div class="stat-block"><div class="num">100%</div><div class="lbl">Secure</div></div>
      </div>
    </div>

    <div class="login-panel">
      <div class="tab-bar">
        <button class="tab-btn active" onclick="switchTab('teacher', this)">Teacher Login</button>
        <button class="tab-btn" onclick="switchTab('admin', this)">Admin Login</button>
      </div>

      <!-- TEACHER FORM -->
      <div class="tab-panel active" id="tab-teacher">
        <h2 class="panel-heading">Teacher Portal</h2>
        <p class="panel-sub">Enter your credentials to access the grading dashboard.</p>
        <?php if ($error_teacher): ?>
          <div class="alert alert-error"><?= htmlspecialchars($error_teacher) ?></div>
        <?php endif; ?>
        <form method="POST" action="Index.php">
          <input type="hidden" name="login_type" value="teacher">
          <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="tname" required placeholder="e.g. Dr.Shahid">
          </div>
          <div class="form-group">
            <label>Subject</label>
            <input type="text" name="subject" required placeholder="e.g. DBMS">
          </div>
          <div class="form-group">
            <label>Course Code</label>
            <input type="text" name="coursecode" required placeholder="e.g. CPE-101">
          </div>
          <div class="form-group">
            <label>Session</label>
            <select name="semester" required>
              <option value="">Select session</option>
              <?php
              $sessions = getSessions($conn);
              foreach ($sessions as $sid => $sy): ?>
                <option value="<?= htmlspecialchars($sy) ?>"><?= htmlspecialchars($sy) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">Sign In to Portal</button>
        </form>
        <div class="result-cta">
          <p>Looking for student results?</p>
          <a href="checkresult.php">Check Result →</a>
        </div>
      </div>

      <!-- ADMIN FORM -->
      <div class="tab-panel" id="tab-admin">
        <h2 class="panel-heading">Admin Portal</h2>
        <p class="panel-sub">Administrator access for managing records and assignments.</p>
        <?php if ($error_admin): ?>
          <div class="alert alert-error"><?= htmlspecialchars($error_admin) ?></div>
        <?php endif; ?>
        <form method="POST" action="Index.php">
          <input type="hidden" name="login_type" value="admin">
          <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="name" required placeholder="Admin name">
          </div>
          <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" required placeholder="admin@bzu.edu.pk">
          </div>
          <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required placeholder="••••••••">
          </div>
          <div class="form-group">
            <label>Admin ID</label>
            <input type="number" name="id" required placeholder="Enter your admin ID">
          </div>
          <button type="submit" class="btn btn-primary">Sign In to Admin</button>
        </form>
      </div>
    </div>
  </div>
</section>

<footer class="site-footer">
  Department of Computer Engineering — Bahauddin Zakariya University Multan &copy; <?= date('Y') ?>
</footer>

<script>
function switchTab(name, btn) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('tab-' + name).classList.add('active');
}
let idx = 0;
const imgs = document.querySelectorAll('.slideshow-img');
setInterval(() => {
  imgs[idx].classList.remove('active');
  idx = (idx + 1) % imgs.length;
  imgs[idx].classList.add('active');
}, 4000);
</script>
</body>
</html>

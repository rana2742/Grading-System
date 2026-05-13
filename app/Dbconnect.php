<?php
// ============================================================
//  Dbconnect.php — Central DB connection using MySQLi
//  Uses prepared statements throughout; connection shared via
//  require_once so only one connection per request.
// ============================================================

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "myproject";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// ============================================================
//  Helper: hash password consistently (SHA2-256, same as SQL)
// ============================================================
function hashPassword(string $plain): string {
    return hash('sha256', $plain);
}

// ============================================================
//  Helper: get all sessions as assoc [id => session_year]
// ============================================================
function getSessions(mysqli $conn): array {
    $rows = [];
    $res  = $conn->query("SELECT id, session_year FROM sessions ORDER BY session_year");
    while ($r = $res->fetch_assoc()) {
        $rows[$r['id']] = $r['session_year'];
    }
    return $rows;
}

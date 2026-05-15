<?php
// Database connection (mysqli). Beginner-friendly, single shared $conn.
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'dcas_db';

$conn = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die('<div style="font-family:Arial;padding:24px;background:#fee;color:#900">'
      .'<h3>Database connection failed</h3>'
      .'<p>'.htmlspecialchars($conn->connect_error).'</p>'
      .'<p>1) Make sure MySQL is running.<br>2) Import <code>database.sql</code>.<br>'
      .'3) Check credentials in <code>includes/db.php</code>.</p></div>');
}
$conn->set_charset('utf8mb4');

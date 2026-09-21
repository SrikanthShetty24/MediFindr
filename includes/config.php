<?php
// ─── MediFindr Database Configuration ────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Change to your MySQL username
define('DB_PASS', '');           // Change to your MySQL password
define('DB_NAME', 'medifindr');
define('DB_PORT', 3306);

define('BASE_URL', 'http://localhost/medifindr'); // Change to your domain
define('SITE_NAME', 'MediFindr');

// ─── Database Connection (MySQLi) ─────────────────────────────────────────────
function getDB() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        if ($conn->connect_error) {
            die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

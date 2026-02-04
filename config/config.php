<?php
// Start session for Electron + browser
session_start();

// ===== App settings =====
define('BASE_URL', 'https://ics-dev.io');

// Replace with values from Hostinger → Databases → MySQL
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456_sample_php_pdo');
define('DB_USER', 'u123456_dbuser');
define('DB_PASS', 'STRONG_PASSWORD');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit(json_encode([
        "status" => "error",
        "message" => "Database connection failed"
    ]));
}

<?php
// Central DB configuration + PDO connection.
// Change BASE_URL if you serve the app from a subfolder.
define('BASE_URL', '/crossrent');           // e.g. '' if at web root
define('DB_HOST', 'localhost');
define('DB_NAME', 'crossrent');
define('DB_USER', 'root');
define('DB_PASS', '');                      // XAMPP default = empty
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('LOG_FILE',   __DIR__ . '/../logs/error.log');

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,   // real prepared statements
        ]);
    } catch (PDOException $e) {
        @file_put_contents(LOG_FILE, '[' . date('c') . '] DB FAIL: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
        http_response_code(500);
        die('Service temporarily unavailable. Please try again later.');
    }
    return $pdo;
}
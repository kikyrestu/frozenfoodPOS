<?php
declare(strict_types = 1)
;

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'fun_frozen_food');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $opts = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
        }
        catch (PDOException $e) {
            http_response_code(500);
            // Check if it's an API call
            if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                header('Content-Type: application/json');
                die(json_encode(['success' => false, 'message' => 'Koneksi database gagal.']));
            }
            die('<div style="font-family:sans-serif;padding:40px;background:#1a1a1a;color:#e74c3c;text-align:center">
                <h2>&#9888; Koneksi Database Gagal</h2>
                <p style="color:#888">Pastikan MySQL berjalan dan konfigurasi di <code>config/database.php</code> sudah benar.</p>
            </div>');
        }
    }
    return $pdo;
}
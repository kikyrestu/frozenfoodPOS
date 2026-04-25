<?php
declare(strict_types=1);
// ============================================================
//  APPLICATION CONFIGURATION
// ============================================================

// Error reporting — log errors, jangan tampilkan di production
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', dirname(__DIR__) . '/logs/php-errors.log');

if (!defined('BASE_URL')) {
    $root    = str_replace('\\', '/', dirname(__DIR__));
    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
    $base    = str_replace($docRoot, '', $root);
    define('BASE_URL', rtrim($base, '/'));
}

define('ROOT_PATH',   dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads/');
define('UPLOAD_URL',  BASE_URL . '/uploads/');

define('APP_NAME',    'Fun Frozen Food POS');
define('APP_VERSION', '1.0.0');

define('MAX_UPLOAD_SIZE',     2 * 1024 * 1024);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_IMAGE_EXTS',  ['jpg', 'jpeg', 'png', 'webp']);

if (!defined('CURRENCY_SYMBOL')) {
    define('CURRENCY_SYMBOL', 'Rp');
}

if (session_status() === PHP_SESSION_NONE) {
    session_name('FFF_POS');
    session_start();
}

require_once __DIR__ . '/database.php';

// ============================================================
//  HELPER FUNCTIONS
// ============================================================

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

function verifyCsrf(): bool {
    $token = $_POST['csrf_token'] ?? '';
    return $token !== '' && hash_equals(csrfToken(), $token);
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function apiRequireLogin(): void {
    if (!isLoggedIn()) {
        jsonResponse(['success' => false, 'message' => 'Sesi habis, silakan login ulang.'], 401);
    }
}

function apiRequireAdmin(): void {
    apiRequireLogin();
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        jsonResponse(['success' => false, 'message' => 'Akses ditolak.'], 403);
    }
}

function requireAdmin(): void {
    requireLogin();
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        header('Location: ' . BASE_URL . '/pos.php');
        exit;
    }
}

// Cek apakah user adalah admin
function isAdmin(): bool {
    return ($_SESSION['user_role'] ?? '') === 'admin';
}

function getSetting(string $key, bool $clearCache = false): string {
    static $cache = [];
    if ($clearCache) { $cache = []; return ''; }
    if (array_key_exists($key, $cache)) return $cache[$key];
    $db   = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
    $stmt->execute([$key]);
    $row  = $stmt->fetch();
    return $cache[$key] = ($row ? (string)$row['setting_value'] : '');
}

function clearSettingsCache(): void {
    getSetting('', true);
}


function rateLimit(string $key, int $maxAttempts = 10, int $windowSeconds = 60): bool {
    $now = time();

    // Use IP-scoped file buckets to make limits harder to bypass than session-only counters.
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $bucketKey = hash('sha256', $key . '|' . $ip);
    $dir = ROOT_PATH . '/logs/ratelimit';

    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        // Fallback when file storage is not writable.
        $sessionKey = 'rl_' . $key;
        if (!isset($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = ['count' => 0, 'reset' => $now + $windowSeconds];
        }
        if ($now > $_SESSION[$sessionKey]['reset']) {
            $_SESSION[$sessionKey] = ['count' => 0, 'reset' => $now + $windowSeconds];
        }
        $_SESSION[$sessionKey]['count']++;
        return $_SESSION[$sessionKey]['count'] <= $maxAttempts;
    }

    $file = $dir . '/' . $bucketKey . '.json';
    $bucket = ['count' => 0, 'reset' => $now + $windowSeconds];

    if (is_file($file)) {
        $raw = @file_get_contents($file);
        $decoded = $raw ? json_decode($raw, true) : null;
        if (is_array($decoded) && isset($decoded['count'], $decoded['reset'])) {
            $bucket = [
                'count' => (int)$decoded['count'],
                'reset' => (int)$decoded['reset'],
            ];
        }
    }

    if ($now > $bucket['reset']) {
        $bucket = ['count' => 0, 'reset' => $now + $windowSeconds];
    }

    $bucket['count']++;
    @file_put_contents($file, json_encode($bucket), LOCK_EX);

    return $bucket['count'] <= $maxAttempts;
}

function formatRupiah(float $amount): string {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function generateInvoiceNo(): string {
    $db     = getDB();
    $prefix = 'INV' . date('Ymd');
    $stmt   = $db->prepare("SELECT invoice_no FROM transactions WHERE invoice_no LIKE ? ORDER BY invoice_no DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $last = $stmt->fetchColumn();
    $seq  = $last ? ((int)substr($last, -4) + 1) : 1;
    return $prefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
}

function sanitize(string $input): string {
    return strip_tags(trim($input));
}

/**
 * Securely validate and upload an image file.
 * Returns ['success' => true, 'filename' => '...'] or ['success' => false, 'message' => '...']
 */
function secureImageUpload(array $file, string $destDir, string $prefix = 'img'): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload gagal (error code: ' . $file['error'] . ')'];
    }

    // 1. Check file size
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'message' => 'Ukuran file maksimal 2MB!'];
    }

    // 2. Validate extension (whitelist)
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_IMAGE_EXTS, true)) {
        return ['success' => false, 'message' => 'Ekstensi file tidak diizinkan! Hanya: ' . implode(', ', ALLOWED_IMAGE_EXTS)];
    }

    // 3. Validate MIME type from file content
    $fType = mime_content_type($file['tmp_name']);
    if (!in_array($fType, ALLOWED_IMAGE_TYPES, true)) {
        return ['success' => false, 'message' => 'Tipe file tidak valid! Hanya gambar JPG, PNG, atau WebP.'];
    }

    // 4. Verify actual image via getimagesize (magic bytes check)
    $imgInfo = @getimagesize($file['tmp_name']);
    if ($imgInfo === false) {
        return ['success' => false, 'message' => 'File bukan gambar yang valid!'];
    }

    // 5. Cross-check: detected image type must match allowed MIME types
    $detectedMime = image_type_to_mime_type($imgInfo[2]);
    if (!in_array($detectedMime, ALLOWED_IMAGE_TYPES, true)) {
        return ['success' => false, 'message' => 'Konten file tidak sesuai dengan tipe gambar yang diizinkan!'];
    }

    // 6. Re-process image through GD to strip any embedded malicious code
    $srcImage = null;
    switch ($imgInfo[2]) {
        case IMAGETYPE_JPEG:
            $srcImage = @imagecreatefromjpeg($file['tmp_name']);
            $ext = 'jpg';
            break;
        case IMAGETYPE_PNG:
            $srcImage = @imagecreatefrompng($file['tmp_name']);
            $ext = 'png';
            break;
        case IMAGETYPE_WEBP:
            $srcImage = @imagecreatefromwebp($file['tmp_name']);
            $ext = 'webp';
            break;
    }

    if (!$srcImage) {
        return ['success' => false, 'message' => 'Gagal memproses gambar!'];
    }

    // 7. Generate safe filename (no user input in filename)
    $newName = $prefix . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;

    // 8. Ensure destination directory exists
    $fullDir = rtrim(UPLOAD_PATH, '/') . '/' . trim($destDir, '/') . '/';
    if (!is_dir($fullDir)) {
        mkdir($fullDir, 0775, true);
    }

    $destPath = $fullDir . $newName;

    // 9. Save re-processed (clean) image
    $saved = false;
    switch ($imgInfo[2]) {
        case IMAGETYPE_JPEG:
            $saved = imagejpeg($srcImage, $destPath, 90);
            break;
        case IMAGETYPE_PNG:
            imagesavealpha($srcImage, true);
            $saved = imagepng($srcImage, $destPath, 8);
            break;
        case IMAGETYPE_WEBP:
            $saved = imagewebp($srcImage, $destPath, 85);
            break;
    }
    imagedestroy($srcImage);

    if (!$saved) {
        return ['success' => false, 'message' => 'Gagal menyimpan gambar!'];
    }

    // 10. Remove any execute permission
    chmod($destPath, 0644);

    return ['success' => true, 'filename' => $newName];
}

function redirect(string $url): void {
    header('Location: ' . BASE_URL . $url);
    exit;
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function getLogoUrl(): string {
    $logo = getSetting('store_logo');
    if ($logo && file_exists(UPLOAD_PATH . 'logo/' . $logo)) {
        return UPLOAD_URL . 'logo/' . $logo;
    }
    return BASE_URL . '/assets/images/logo.png';
}

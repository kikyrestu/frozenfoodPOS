<?php
declare(strict_types=1);

require_once '../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

if (!rateLimit('upload_proof', 10, 60)) {
    jsonResponse(['success' => false, 'message' => 'Terlalu banyak upload, coba lagi nanti.'], 429);
}

if (!isset($_FILES['proof']) || $_FILES['proof']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['success' => false, 'message' => 'File tidak ditemukan atau gagal diupload']);
}

$result = secureImageUpload($_FILES['proof'], 'proofs', 'bukti');

if (!$result['success']) {
    jsonResponse(['success' => false, 'message' => $result['message']]);
}

$proofUrl = UPLOAD_URL . 'proofs/' . $result['filename'];

jsonResponse(['success' => true, 'url' => $proofUrl, 'filename' => $result['filename']]);

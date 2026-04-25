<?php
declare(strict_types=1);

require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || ($_SESSION['user_role'] ?? '') !== 'admin') {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    jsonResponse(['success' => false, 'message' => 'ID tidak valid']);
}

$db = getDB();

$stmt = $db->prepare("SELECT * FROM online_orders WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    jsonResponse(['success' => false, 'message' => 'Pesanan tidak ditemukan']);
}

$items = $db->prepare("SELECT * FROM online_order_items WHERE order_id = ?");
$items->execute([$id]);
$order['items'] = $items->fetchAll();

jsonResponse(['success' => true, 'order' => $order]);

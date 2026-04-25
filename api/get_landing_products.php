<?php
declare(strict_types=1);

require_once '../config/config.php';

$db = getDB();

$stmt = $db->query("SELECT p.id, p.name, p.price, p.stock, p.unit, p.image, p.description,
                            c.name as category_name
                     FROM products p
                     LEFT JOIN categories c ON p.category_id = c.id
                     WHERE p.status = 1 AND p.stock > 0
                     ORDER BY p.name ASC");
$products = $stmt->fetchAll();

foreach ($products as &$p) {
    $p['id']    = (int)$p['id'];
    $p['price'] = (float)$p['price'];
    $p['stock'] = (int)$p['stock'];
    $p['image_url'] = $p['image'] ? (UPLOAD_URL . 'products/' . $p['image']) : '';
}
unset($p);

// Payment settings
$qrisImage = getSetting('qris_image');
$payment = [
    'qris_image'          => $qrisImage ? (UPLOAD_URL . 'logo/' . $qrisImage) : '',
    'bank_name'           => getSetting('bank_name'),
    'bank_account_number' => getSetting('bank_account_number'),
    'bank_account_holder' => getSetting('bank_account_holder'),
    'store_phone'         => getSetting('store_phone'),
];

jsonResponse(['success' => true, 'data' => $products, 'payment' => $payment]);

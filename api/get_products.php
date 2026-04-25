<?php
declare(strict_types=1);

require_once '../config/config.php';
apiRequireLogin();

$db = getDB();
$params = [];
$where = ["p.status = 1"];

if (!empty($_GET['category_id']) && is_numeric($_GET['category_id'])) {
    $where[] = "p.category_id = ?";
    $params[] = (int)$_GET['category_id'];
}

if (!empty($_GET['search'])) {
    $where[] = "p.name LIKE ?";
    $params[] = '%' . trim($_GET['search']) . '%';
}

$sql = "SELECT p.id, p.name, p.price, p.stock, p.unit, p.image, p.low_stock_alert,
               c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY p.name ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Cast numeric fields to proper types
foreach ($products as &$p) {
    $p['id']              = (int)$p['id'];
    $p['price']           = (float)$p['price'];
    $p['stock']           = (int)$p['stock'];
    $p['low_stock_alert'] = (int)$p['low_stock_alert'];
}
unset($p);

jsonResponse(['success' => true, 'data' => $products, 'tax_percent' => (float)(getSetting('tax_percent') ?: 0)]);

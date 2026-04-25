<?php
declare(strict_types=1);

require_once '../config/config.php';
apiRequireLogin();

$db = getDB();
$limit = 20;

// Kasir only sees their own transactions; admin sees all
$where = '1=1';
$params = [];
if (!isAdmin()) {
    $where = 't.cashier_id = ?';
    $params[] = $_SESSION['user_id'];
}

$stmt = $db->prepare("SELECT t.id, t.invoice_no, t.customer_name, t.total, t.payment_method, t.status, t.created_at, u.full_name as cashier_name
    FROM transactions t LEFT JOIN users u ON t.cashier_id = u.id
    WHERE {$where}
    ORDER BY t.created_at DESC LIMIT {$limit}");
$stmt->execute($params);
$transactions = $stmt->fetchAll();

foreach ($transactions as &$t) {
    $t['created_at_formatted'] = $t['created_at'] ? date('d/m/Y H:i', strtotime($t['created_at'])) : '-';
    $t['total'] = (float)$t['total'];
}
unset($t);

jsonResponse(['success' => true, 'data' => $transactions]);

<?php
declare(strict_types=1);

require_once '../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

if (!rateLimit('submit_order', 5, 60)) {
    jsonResponse(['success' => false, 'message' => 'Terlalu banyak percobaan, coba lagi nanti.'], 429);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    jsonResponse(['success' => false, 'message' => 'Invalid request body']);
}

$name    = trim($input['customer_name'] ?? '');
$phone   = trim($input['customer_phone'] ?? '');
$address = trim($input['customer_address'] ?? '');
$method  = in_array($input['payment_method'] ?? '', ['qris', 'bank', 'cod'], true) ? $input['payment_method'] : 'qris';
$proof   = trim($input['proof_image'] ?? '');
$items   = $input['items'] ?? [];

if ($name === '' || $phone === '' || $address === '') {
    jsonResponse(['success' => false, 'message' => 'Nama, telepon, dan alamat wajib diisi']);
}
if (empty($items) || !is_array($items)) {
    jsonResponse(['success' => false, 'message' => 'Keranjang kosong']);
}

$db = getDB();

// Normalize items to prevent duplicate product rows in the same order payload.
$requestedItems = [];
foreach ($items as $item) {
    $pid = (int)($item['id'] ?? 0);
    $qty = (int)($item['qty'] ?? 0);
    if ($pid <= 0 || $qty <= 0) {
        continue;
    }
    if (!isset($requestedItems[$pid])) {
        $requestedItems[$pid] = 0;
    }
    $requestedItems[$pid] += $qty;
}

if (empty($requestedItems)) {
    jsonResponse(['success' => false, 'message' => 'Tidak ada produk valid di keranjang']);
}

try {
    $db->beginTransaction();

    $total = 0.0;
    $validItems = [];
    $productStmt = $db->prepare("SELECT id, name, price, stock FROM products WHERE id = ? AND status = 1 LIMIT 1 FOR UPDATE");

    foreach ($requestedItems as $pid => $qty) {
        $productStmt->execute([$pid]);
        $product = $productStmt->fetch();

        if (!$product) {
            $db->rollBack();
            jsonResponse(['success' => false, 'message' => 'Produk tidak ditemukan atau nonaktif']);
        }

        if ((int)$product['stock'] < $qty) {
            $db->rollBack();
            jsonResponse(['success' => false, 'message' => 'Stok ' . $product['name'] . ' tidak mencukupi (sisa: ' . $product['stock'] . ')']);
        }

        $sub = (float)$product['price'] * $qty;
        $total += $sub;
        $validItems[] = [
            'product_id'   => (int)$product['id'],
            'product_name' => $product['name'],
            'price'        => (float)$product['price'],
            'qty'          => $qty,
            'subtotal'     => $sub,
        ];
    }

    // Generate order number inside transaction to reduce duplicate race risk.
    $prefix = 'ORD' . date('Ymd');
    $stmt = $db->prepare("SELECT order_no FROM online_orders WHERE order_no LIKE ? ORDER BY order_no DESC LIMIT 1 FOR UPDATE");
    $stmt->execute([$prefix . '%']);
    $last = $stmt->fetchColumn();
    $seq  = $last ? ((int)substr((string)$last, -4) + 1) : 1;
    $orderNo = $prefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);

    $stmt = $db->prepare("INSERT INTO online_orders (order_no, customer_name, customer_phone, customer_address, payment_method, proof_image, subtotal, total, status)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$orderNo, $name, $phone, $address, $method, $proof, $total, $total]);
    $orderId = (int)$db->lastInsertId();

    $stmtItem = $db->prepare("INSERT INTO online_order_items (order_id, product_id, product_name, price, qty, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($validItems as $vi) {
        $stmtItem->execute([$orderId, $vi['product_id'], $vi['product_name'], $vi['price'], $vi['qty'], $vi['subtotal']]);
    }

    $db->commit();

    jsonResponse([
        'success'  => true,
        'order_no' => $orderNo,
        'total'    => $total,
        'message'  => 'Pesanan berhasil dikirim! Nomor order: ' . $orderNo,
    ]);
} catch (\Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse(['success' => false, 'message' => 'Gagal menyimpan pesanan, coba lagi.']);
}

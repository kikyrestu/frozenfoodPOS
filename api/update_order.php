<?php
declare(strict_types=1);

require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || ($_SESSION['user_role'] ?? '') !== 'admin') {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    jsonResponse(['success' => false, 'message' => 'Invalid request body']);
}

$orderId = (int)($input['id'] ?? 0);
$action  = $input['action'] ?? '';
$note    = trim($input['admin_note'] ?? '');

if ($orderId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Order ID tidak valid']);
}

$allowed = ['approve', 'reject', 'complete'];
if (!in_array($action, $allowed, true)) {
    jsonResponse(['success' => false, 'message' => 'Aksi tidak valid']);
}

$db = getDB();

$stmt = $db->prepare("SELECT * FROM online_orders WHERE id = ? LIMIT 1");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    jsonResponse(['success' => false, 'message' => 'Pesanan tidak ditemukan']);
}

$statusMap = [
    'approve'  => 'approved',
    'reject'   => 'rejected',
    'complete' => 'completed',
];

$newStatus = $statusMap[$action];

// Validate transitions
if ($action === 'approve' && $order['status'] !== 'pending') {
    jsonResponse(['success' => false, 'message' => 'Hanya pesanan pending yang bisa di-approve']);
}
if ($action === 'reject' && !in_array($order['status'], ['pending', 'approved'], true)) {
    jsonResponse(['success' => false, 'message' => 'Pesanan ini tidak bisa ditolak']);
}
if ($action === 'complete' && $order['status'] !== 'approved') {
    jsonResponse(['success' => false, 'message' => 'Hanya pesanan approved yang bisa diselesaikan']);
}

try {
    $db->beginTransaction();

    $stmt = $db->prepare("UPDATE online_orders SET status = ?, admin_note = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$newStatus, $note, $orderId]);

    // Deduct stock when approved
    if ($action === 'approve') {
        $items = $db->prepare("SELECT product_id, product_name, qty FROM online_order_items WHERE order_id = ?");
        $items->execute([$orderId]);
        $productStockStmt = $db->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");
        $upd = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
        $shStmt = $db->prepare("INSERT INTO stock_history (product_id, type, qty, stock_before, stock_after, note, reference, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($items->fetchAll() as $item) {
            if ($item['product_id']) {
                $productStockStmt->execute([$item['product_id']]);
                $curStock = $productStockStmt->fetchColumn();
                if ($curStock === false) {
                    $db->rollBack();
                    jsonResponse(['success' => false, 'message' => 'Produk tidak ditemukan saat proses approve.']);
                }
                $curStock = (int)$curStock;
                $upd->execute([$item['qty'], $item['product_id'], $item['qty']]);
                if ($upd->rowCount() === 0) {
                    $db->rollBack();
                    jsonResponse(['success' => false, 'message' => 'Stok ' . $item['product_name'] . ' tidak mencukupi, approve gagal.']);
                }
                $shStmt->execute([$item['product_id'], 'out', $item['qty'], $curStock, $curStock - $item['qty'], 'Online Order #' . $order['order_no'], $order['order_no'], $_SESSION['user_id'] ?? null]);
            }
        }
    }

    // Restore stock when rejecting an approved order
    if ($action === 'reject' && $order['status'] === 'approved') {
        $items = $db->prepare("SELECT product_id, qty FROM online_order_items WHERE order_id = ?");
        $items->execute([$orderId]);
        $productStockStmt = $db->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");
        $restockStmt = $db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
        $shStmt = $db->prepare("INSERT INTO stock_history (product_id, type, qty, stock_before, stock_after, note, reference, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($items->fetchAll() as $item) {
            if ($item['product_id']) {
                $productStockStmt->execute([$item['product_id']]);
                $curStock = $productStockStmt->fetchColumn();
                if ($curStock === false) {
                    continue;
                }
                $curStock = (int)$curStock;
                $restockStmt->execute([$item['qty'], $item['product_id']]);
                $shStmt->execute([$item['product_id'], 'in', $item['qty'], $curStock, $curStock + $item['qty'], 'Reject Order #' . $order['order_no'], $order['order_no'], $_SESSION['user_id'] ?? null]);
            }
        }
    }

    // Record into transactions table when completed so it shows in reports/dashboard
    if ($action === 'complete') {
        $invoiceNo = generateInvoiceNo();
        $payMethod = 'transfer'; // online payments mapped to 'transfer' in transactions enum

        $taxPercent = (float)(getSetting('tax_percent') ?: 0);
        $subtotal   = (float)$order['subtotal'];
        $taxAmount  = $taxPercent > 0 ? round($subtotal * $taxPercent / 100, 2) : 0;
        $total      = (float)$order['total'];

        $trxStmt = $db->prepare("INSERT INTO transactions (invoice_no, customer_name, payment_method, subtotal, discount, tax_amount, tax_percent, total, paid_amount, change_amount, note, cashier_id, status, created_at)
                                  VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, 0, ?, ?, 'completed', NOW())");
        $trxStmt->execute([
            $invoiceNo,
            $order['customer_name'],
            $payMethod,
            $subtotal,
            $taxAmount,
            $taxPercent,
            $total,
            $total,
            'Online Order #' . $order['order_no'],
            $_SESSION['user_id'] ?? null,
        ]);
        $trxId = (int)$db->lastInsertId();

        $orderItems = $db->prepare("SELECT product_id, product_name, price, qty, subtotal FROM online_order_items WHERE order_id = ?");
        $orderItems->execute([$orderId]);
        $tiStmt = $db->prepare("INSERT INTO transaction_items (transaction_id, product_id, product_name, price, qty, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($orderItems->fetchAll() as $oi) {
            $tiStmt->execute([$trxId, $oi['product_id'], $oi['product_name'], $oi['price'], $oi['qty'], $oi['subtotal']]);
        }
    }

    $db->commit();

    $labels = ['approved' => 'disetujui', 'rejected' => 'ditolak', 'completed' => 'diselesaikan'];
    jsonResponse(['success' => true, 'message' => 'Pesanan berhasil ' . $labels[$newStatus]]);
} catch (\Exception $e) {
    $db->rollBack();
    jsonResponse(['success' => false, 'message' => 'Gagal memperbarui pesanan']);
}

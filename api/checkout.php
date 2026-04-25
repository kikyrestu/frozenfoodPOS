<?php
declare(strict_types=1);

require_once '../config/config.php';
apiRequireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    jsonResponse(['success' => false, 'message' => 'Data tidak valid']);
}

$items          = $input['items'] ?? [];
$customerName   = sanitize($input['customer_name'] ?? '');
$paymentMethod  = in_array($input['payment_method'] ?? '', ['tunai','transfer']) ? $input['payment_method'] : 'tunai';
$paidAmount     = (float)($input['paid_amount'] ?? 0);

if (empty($items)) {
    jsonResponse(['success' => false, 'message' => 'Keranjang kosong']);
}

$db = getDB();

// Pre-validate item structure only (no stock check yet — done inside txn)
$requestedItems = [];
foreach ($items as $item) {
    $productId = (int)($item['product_id'] ?? 0);
    $qty = (int)($item['qty'] ?? 0);

    if ($productId <= 0 || $qty <= 0) {
        jsonResponse(['success' => false, 'message' => 'Data produk tidak valid']);
    }
    $requestedItems[] = ['product_id' => $productId, 'qty' => $qty];
}

// Save transaction
try {
    $db->beginTransaction();

    // Validate stock & calculate totals INSIDE transaction with row locks
    $subtotal = 0;
    $validatedItems = [];

    foreach ($requestedItems as $item) {
        $stmt = $db->prepare("SELECT id, name, price, stock FROM products WHERE id = ? AND status = 1 FOR UPDATE");
        $stmt->execute([$item['product_id']]);
        $product = $stmt->fetch();

        if (!$product) {
            $db->rollBack();
            jsonResponse(['success' => false, 'message' => "Produk ID {$item['product_id']} tidak ditemukan"]);
        }
        if ($product['stock'] < $item['qty']) {
            $db->rollBack();
            jsonResponse(['success' => false, 'message' => "Stok {$product['name']} tidak mencukupi (Tersedia: {$product['stock']})"]);
        }

        $itemSubtotal = $product['price'] * $item['qty'];
        $subtotal += $itemSubtotal;

        $validatedItems[] = [
            'product_id'   => $item['product_id'],
            'product_name' => $product['name'],
            'price'        => $product['price'],
            'qty'          => $item['qty'],
            'subtotal'     => $itemSubtotal,
            'stock_before' => $product['stock'],
            'stock_after'  => $product['stock'] - $item['qty']
        ];
    }

    // Calculate tax
    $taxPercent = (float)(getSetting('tax_percent') ?: 0);
    $taxAmount  = $taxPercent > 0 ? round($subtotal * $taxPercent / 100, 2) : 0;
    $total      = $subtotal + $taxAmount;

    $changeAmount = $paymentMethod === 'tunai' ? max(0, $paidAmount - $total) : 0;

    if ($paymentMethod === 'tunai' && $paidAmount < $total) {
        $db->rollBack();
        jsonResponse(['success' => false, 'message' => 'Uang bayar kurang']);
    }

    $invoiceNo = generateInvoiceNo();

    $stmt = $db->prepare("INSERT INTO transactions 
        (invoice_no, customer_name, payment_method, subtotal, discount, tax_amount, tax_percent, total, paid_amount, change_amount, cashier_id, status)
        VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, 'completed')");
    $stmt->execute([
        $invoiceNo, $customerName, $paymentMethod,
        $subtotal, $taxAmount, $taxPercent, $total, $paidAmount, $changeAmount,
        $_SESSION['user_id']
    ]);
    $transactionId = $db->lastInsertId();

    foreach ($validatedItems as $item) {
        // Insert item
        $stmt = $db->prepare("INSERT INTO transaction_items 
            (transaction_id, product_id, product_name, price, qty, subtotal)
            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $transactionId, $item['product_id'], $item['product_name'],
            $item['price'], $item['qty'], $item['subtotal']
        ]);

        // Update stock
        $stmt = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt->execute([$item['qty'], $item['product_id']]);

        // Stock history
        $stmt = $db->prepare("INSERT INTO stock_history 
            (product_id, type, qty, stock_before, stock_after, note, reference, user_id)
            VALUES (?, 'out', ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $item['product_id'], $item['qty'],
            $item['stock_before'], $item['stock_after'],
            'Penjualan', $invoiceNo, $_SESSION['user_id']
        ]);
    }

    $db->commit();

    jsonResponse([
        'success'        => true,
        'message'        => 'Transaksi berhasil',
        'transaction_id' => $transactionId,
        'invoice_no'     => $invoiceNo
    ]);

} catch (Exception $e) {
    $db->rollBack();
    error_log('Checkout error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Gagal menyimpan transaksi. Silakan coba lagi.']);
}

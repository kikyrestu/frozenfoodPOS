<?php
declare(strict_types=1);

require_once '../config/config.php';
apiRequireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) jsonResponse(['success' => false, 'message' => 'ID tidak valid']);

$db = getDB();

$stmt = $db->prepare("SELECT t.*, u.full_name as cashier_name FROM transactions t
                      LEFT JOIN users u ON t.cashier_id = u.id
                      WHERE t.id = ?");
$stmt->execute([$id]);
$trx = $stmt->fetch();

if (!$trx) jsonResponse(['success' => false, 'message' => 'Transaksi tidak ditemukan']);

// Items
$stmt = $db->prepare("SELECT * FROM transaction_items WHERE transaction_id = ?");
$stmt->execute([$id]);
$trx['items'] = $stmt->fetchAll();

// Store settings
$trx['store_name']        = getSetting('store_name');
$trx['store_address']     = getSetting('store_address');
$trx['store_phone']       = getSetting('store_phone');
$trx['store_logo']        = getSetting('store_logo');
$trx['receipt_footer']    = getSetting('receipt_footer');
$trx['receipt_paper_size']= getSetting('receipt_paper_size') ?: '80';
$trx['tax_amount']        = (float)($trx['tax_amount'] ?? 0);
$trx['tax_percent']       = (float)($trx['tax_percent'] ?? 0);
$trx['created_at']        = $trx['created_at'] ? date('d/m/Y H:i:s', strtotime($trx['created_at'])) : '-';

jsonResponse(['success' => true, 'transaction' => $trx]);

<?php
declare(strict_types=1);

require_once '../config/config.php';
apiRequireLogin();

$db = getDB();
$stmt = $db->query("SELECT c.id, c.name, COUNT(p.id) as product_count
                    FROM categories c
                    LEFT JOIN products p ON p.category_id = c.id AND p.status = 1
                    GROUP BY c.id, c.name
                    ORDER BY c.name ASC");
$categories = $stmt->fetchAll();

jsonResponse(['success' => true, 'data' => $categories]);

<?php
/* Add a shop item to the current user's cart (or increment its quantity).
   Same security order as every other state-changing endpoint in api/:
   POST-only  ->  CSRF verified BEFORE the write branch  ->  auth  ->  write. */
require_once dirname(__DIR__) . '/config/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'POST only']);
    exit;
}

csrf_verify_json();

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Login required', 'login' => true]);
    exit;
}

$uid    = (int)current_user()['id'];
$itemId = (int)post('item_id');
$qty    = (int)post('qty');
if ($qty < 1)  { $qty = 1; }
if ($qty > 99) { $qty = 99; }          // clamp: never trust a client quantity

if ($itemId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid item']);
    exit;
}

$pdo = db();

$chk = $pdo->prepare('SELECT id, name FROM shop_items WHERE id = ?');
$chk->execute([$itemId]);
$item = $chk->fetch();
if (!$item) {
    echo json_encode(['ok' => false, 'error' => 'Product not found']);
    exit;
}

/* UNIQUE(user_id,item_id) turns "add again" into an increment rather than a
   duplicate row, atomically — no read-modify-write race. */
$ins = $pdo->prepare(
    'INSERT INTO shop_cart (user_id, item_id, quantity) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE quantity = LEAST(99, quantity + VALUES(quantity))'
);
$ins->execute([$uid, $itemId, $qty]);

$st = $pdo->prepare('SELECT COALESCE(SUM(quantity),0) FROM shop_cart WHERE user_id = ?');
$st->execute([$uid]);
$count = (int)$st->fetchColumn();

echo json_encode(['ok' => true, 'count' => $count, 'name' => $item['name']]);

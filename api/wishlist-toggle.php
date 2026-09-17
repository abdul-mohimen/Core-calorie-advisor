<?php
/* Toggle a shop item in the current user's wishlist.
   Security order matches every other state-changing endpoint in api/:
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

if ($itemId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid item']);
    exit;
}

$pdo = db();

// Item must exist — never let a client write an arbitrary id.
$chk = $pdo->prepare('SELECT id FROM shop_items WHERE id = ?');
$chk->execute([$itemId]);
if (!$chk->fetch()) {
    echo json_encode(['ok' => false, 'error' => 'Product not found']);
    exit;
}

// Toggle. The DELETE reports whether a row existed, so we never need a
// SELECT-then-INSERT (which would race on a double-click).
$del = $pdo->prepare('DELETE FROM shop_wishlist WHERE user_id = ? AND item_id = ?');
$del->execute([$uid, $itemId]);

if ($del->rowCount() > 0) {
    $saved = false;
} else {
    // UNIQUE(user_id,item_id) makes this safe under concurrent requests.
    $ins = $pdo->prepare('INSERT IGNORE INTO shop_wishlist (user_id, item_id) VALUES (?, ?)');
    $ins->execute([$uid, $itemId]);
    $saved = true;
}

$count = (int)$pdo->query('SELECT COUNT(*) FROM shop_wishlist WHERE user_id = ' . $uid)->fetchColumn();

echo json_encode(['ok' => true, 'saved' => $saved, 'count' => $count]);

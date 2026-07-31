<?php
/* ============ Notification bell — list / mark-read (AJAX, JSON) ============ */
require_once dirname(__DIR__) . '/config/config.php';
header('Content-Type: application/json');

if (!is_logged_in()) { http_response_code(401); echo json_encode(['ok' => false, 'error' => 'Login required']); exit; }
$uid = (int)$_SESSION['user']['id'];

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

if ($action === 'read') {
    $token = $_POST['csrf'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) { http_response_code(419); echo json_encode(['ok' => false, 'error' => 'CSRF invalid']); exit; }
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $st = db()->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
        $st->execute([$id, $uid]);
    } else {
        $st = db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
        $st->execute([$uid]);
    }
    echo json_encode(['ok' => true, 'unread' => unread_count($uid)]);
    exit;
}

/* default: list recent + unread count */
$st = db()->prepare('SELECT id, type, title, body, link, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 12');
$st->execute([$uid]);
$items = [];
foreach ($st->fetchAll() as $n) {
    $d = max(1, time() - strtotime($n['created_at']));
    $when = $d < 60 ? 'just now' : ($d < 3600 ? floor($d / 60) . 'm' : ($d < 86400 ? floor($d / 3600) . 'h' : floor($d / 86400) . 'd'));
    $items[] = [
        'id'    => (int)$n['id'],
        'type'  => $n['type'],
        'title' => $n['title'],
        'body'  => $n['body'],
        'link'  => $n['link'],
        'read'  => (int)$n['is_read'],
        'when'  => $when,
    ];
}
echo json_encode(['ok' => true, 'unread' => unread_count($uid), 'items' => $items]);

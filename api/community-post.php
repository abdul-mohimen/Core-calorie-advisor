<?php
/* ============ Community — create a post (AJAX, JSON) ============ */
require_once dirname(__DIR__) . '/config/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok' => false, 'error' => 'POST only']); exit; }
$token = $_POST['csrf'] ?? '';
if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
    http_response_code(419); echo json_encode(['ok' => false, 'error' => 'CSRF invalid']); exit;
}
if (!is_logged_in()) { http_response_code(401); echo json_encode(['ok' => false, 'error' => 'Login required']); exit; }

$body = trim((string)($_POST['body'] ?? ''));
if ($body === '') { echo json_encode(['ok' => false, 'error' => 'Post cannot be empty']); exit; }
if (mb_strlen($body) > 500) $body = mb_substr($body, 0, 500);

$ins = db()->prepare('INSERT INTO community_posts (user_id, body) VALUES (?, ?)');
$ins->execute([$_SESSION['user']['id'], $body]);

$u = $_SESSION['user'];
echo json_encode(['ok' => true, 'post' => [
    'id'      => (int)db()->lastInsertId(),
    'name'    => $u['name'],
    'initial' => strtoupper(mb_substr($u['name'], 0, 1)),
    'role'    => $u['role'],
    'body'    => $body,
    'likes'   => 0,
    'when'    => 'just now',
]]);

<?php
/* ============ Feedback submission (public, AJAX, JSON) ============ */
require_once dirname(__DIR__) . '/config/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok' => false, 'error' => 'POST only']); exit; }
$token = $_POST['csrf'] ?? '';
if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) { http_response_code(419); echo json_encode(['ok' => false, 'error' => 'CSRF invalid']); exit; }

$u       = current_user();
$name    = trim((string)($_POST['name'] ?? ($u['name'] ?? '')));
$email   = filter_var(trim((string)($_POST['email'] ?? ($u['email'] ?? ''))), FILTER_VALIDATE_EMAIL) ?: null;
$message = trim((string)($_POST['message'] ?? ''));

if ($message === '') { echo json_encode(['ok' => false, 'error' => 'Please write your feedback']); exit; }
$message = mb_substr($message, 0, 1000);

$ins = db()->prepare('INSERT INTO feedback (user_id, name, email, message) VALUES (?,?,?,?)');
$ins->execute([$u['id'] ?? null, mb_substr($name, 0, 100) ?: null, $email, $message]);

echo json_encode(['ok' => true]);

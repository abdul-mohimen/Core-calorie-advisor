<?php
/* ============ AI Food Scanner result -> "Add to Daily Log" (AJAX, JSON response) ============ */
require_once dirname(__DIR__) . '/config/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok' => false, 'error' => 'POST only']); exit; }

$token = $_POST['csrf'] ?? '';
if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
    http_response_code(419); echo json_encode(['ok' => false, 'error' => 'CSRF invalid']); exit;
}
if (!is_logged_in()) { http_response_code(401); echo json_encode(['ok' => false, 'error' => 'Login required']); exit; }
if (($_SESSION['user']['role'] ?? '') !== 'member') { http_response_code(403); echo json_encode(['ok' => false, 'error' => 'Member Portal only']); exit; }

$name    = trim((string)($_POST['name'] ?? ''));
$kcal    = max(0, min(5000, (int)($_POST['kcal'] ?? 0)));
$protein = max(0, min(500, (float)($_POST['protein'] ?? 0)));
$carbs   = max(0, min(500, (float)($_POST['carbs'] ?? 0)));
$fats    = max(0, min(500, (float)($_POST['fats'] ?? 0)));

if ($name === '') { echo json_encode(['ok' => false, 'error' => 'Food name required']); exit; }

$ins = db()->prepare('INSERT INTO food_logs (user_id, food_name, kcal, protein, carbs, fats) VALUES (?,?,?,?,?,?)');
$ins->execute([$_SESSION['user']['id'], $name, $kcal, $protein, $carbs, $fats]);

echo json_encode(['ok' => true, 'log_id' => (int)db()->lastInsertId()]);

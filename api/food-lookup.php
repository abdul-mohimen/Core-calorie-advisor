<?php
/* Manual nutrition lookup. This is intentionally a database reference match, not a
   fabricated visual AI result. Users can review the returned serving before logging it. */
require_once dirname(__DIR__) . '/config/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok' => false, 'error' => 'POST only']); exit; }
if (!is_logged_in() || !is_pro() || (current_user()['role'] ?? '') !== 'member') { http_response_code(403); echo json_encode(['ok' => false, 'error' => 'CCA Pro member access is required.']); exit; }
$token = $_POST['csrf'] ?? '';
if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) { http_response_code(419); echo json_encode(['ok' => false, 'error' => 'Your session token is invalid. Refresh and try again.']); exit; }

$query = mb_substr(trim((string)($_POST['query'] ?? '')), 0, 120);
if ($query === '') { echo json_encode(['ok' => false, 'error' => 'Describe a meal to continue.']); exit; }

$terms = preg_split('/\s+/', mb_strtolower($query), -1, PREG_SPLIT_NO_EMPTY) ?: [];
$terms = array_values(array_filter($terms, fn(string $term): bool => mb_strlen($term) >= 3));
$food = null;
foreach ($terms as $term) {
    $st = db()->prepare('SELECT * FROM foods WHERE LOWER(name) LIKE ? ORDER BY name LIMIT 1');
    $st->execute(['%' . $term . '%']);
    $food = $st->fetch();
    if ($food) break;
}
if (!$food) {
    echo json_encode(['ok' => false, 'error' => 'No reference food matched. Upload a clear meal photo for AI analysis or try a simpler food name.']);
    exit;
}

echo json_encode(['ok' => true, 'demo' => true, 'result' => [
    'name' => $food['name'], 'serving' => $food['serving'], 'kcal' => (int)$food['kcal'],
    'protein' => (float)$food['protein'], 'carbs' => (float)$food['carbs'], 'fats' => (float)$food['fats'],
    'verdict' => 'Reference nutrition for ' . $food['serving'],
]]);

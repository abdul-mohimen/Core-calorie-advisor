<?php
/* ============ Player workout complete -> log in DB (AJAX, JSON response) ============ */
require_once dirname(__DIR__) . '/config/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok' => false, 'error' => 'POST only']); exit; }

/* ---- CSRF (AJAX se aata hai) ---- */
$token = $_POST['csrf'] ?? '';
if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
    http_response_code(419); echo json_encode(['ok' => false, 'error' => 'CSRF invalid']); exit;
}
if (!is_logged_in()) { http_response_code(401); echo json_encode(['ok' => false, 'error' => 'Login required']); exit; }

$workoutId = (int)($_POST['workout_id'] ?? 0);
$kcal      = max(0, min(5000, (int)($_POST['kcal'] ?? 0)));
$duration  = max(0, min(36000, (int)($_POST['duration'] ?? 0)));

$st = db()->prepare('SELECT id FROM workouts WHERE id = ?');
$st->execute([$workoutId]);
if (!$st->fetch()) { echo json_encode(['ok' => false, 'error' => 'Workout not found']); exit; }

$ins = db()->prepare('INSERT INTO workout_logs (user_id, workout_id, kcal_burned, duration_sec) VALUES (?,?,?,?)');
$ins->execute([$_SESSION['user']['id'], $workoutId, $kcal, $duration]);

echo json_encode(['ok' => true, 'kcal' => $kcal, 'log_id' => (int)db()->lastInsertId()]);

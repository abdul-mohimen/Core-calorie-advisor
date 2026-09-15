<?php
/* Admin-only: grant/revoke a user's subscription plan (free/pro/elite).
   Used by the Core Calorie Advisor Control Room's user table. */
require_once dirname(__DIR__) . '/config/config.php';

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok' => false, 'error' => 'POST only']); exit; }
if (!is_logged_in() || (current_user()['role'] ?? '') !== 'admin') { http_response_code(403); echo json_encode(['ok' => false, 'error' => 'Admin only']); exit; }
csrf_verify();

$uid  = (int)post('user_id');
$plan = post('plan');
if ($uid <= 0 || !in_array($plan, ['free', 'pro', 'elite'], true)) {
    http_response_code(422); echo json_encode(['ok' => false, 'error' => 'Invalid user or plan']); exit;
}

$st = db()->prepare('SELECT id, name, plan FROM users WHERE id = ?');
$st->execute([$uid]);
$target = $st->fetch();
if (!$target) { http_response_code(404); echo json_encode(['ok' => false, 'error' => 'User not found']); exit; }

db()->prepare('UPDATE users SET plan = ? WHERE id = ?')->execute([$plan, $uid]);
/* keep the admin's own session fresh if they changed their own plan */
if ((int)current_user()['id'] === $uid) $_SESSION['user']['plan'] = $plan;

if (in_array($plan, ['pro', 'elite'], true)) {
    notify($uid, '💎 ' . strtoupper($plan) . ' access granted', 'Core Calorie Advisor admin ne aap ko ' . ucfirst($plan) . ' plan ka full access de diya hai — tamam PRO features ab unlocked hain.', 'success', 'pages/workouts.php');
} else {
    notify($uid, 'Plan changed to FREE', 'Aap ka subscription plan admin ne FREE par set kar diya hai.', 'info', 'pages/pricing.php');
}

echo json_encode(['ok' => true, 'name' => $target['name'], 'plan' => $plan]);

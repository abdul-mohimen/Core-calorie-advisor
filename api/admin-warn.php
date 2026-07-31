<?php
/* ============ Admin issues a Warning -> notifies target Doctor/Trainer ============ */
require_once dirname(__DIR__) . '/config/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok' => false, 'error' => 'POST only']); exit; }
csrf_verify_json();
if (!is_logged_in() || ($_SESSION['user']['role'] ?? '') !== 'admin') { http_response_code(403); echo json_encode(['ok' => false, 'error' => 'Admin only']); exit; }

$targetId = (int)($_POST['target_user_id'] ?? 0);
$severity = in_array($_POST['severity'] ?? '', ['notice','warning','severe'], true) ? $_POST['severity'] : 'warning';
$reason   = trim((string)($_POST['reason'] ?? ''));
$reportId = (int)($_POST['report_id'] ?? 0) ?: null;

if ($reason === '') { echo json_encode(['ok' => false, 'error' => 'Reason is required']); exit; }
$reason = mb_substr($reason, 0, 500);

/* validate target is a warnable role (doctor/trainer) */
$st = db()->prepare("SELECT id, name, role FROM users WHERE id = ? AND role IN ('doctor','trainer')");
$st->execute([$targetId]);
$target = $st->fetch();
if (!$target) { echo json_encode(['ok' => false, 'error' => 'Target must be a Doctor or Trainer']); exit; }

/* record the warning */
$ins = db()->prepare('INSERT INTO warnings (admin_id, target_user_id, severity, reason, report_id) VALUES (?,?,?,?,?)');
$ins->execute([$_SESSION['user']['id'], $targetId, $severity, $reason, $reportId]);

/* trigger a notification in that user's portal (bell) */
$label = ['notice' => '📢 Notice', 'warning' => '⚠ Official Warning', 'severe' => '🛑 Severe Warning'][$severity];
notify($targetId, $label . ' from Admin', $reason, 'warning', BASE_URL . '/portals/' . $target['role'] . '.php');

/* if tied to a report, mark it actioned */
if ($reportId) {
    $up = db()->prepare("UPDATE issue_reports SET status = 'actioned' WHERE id = ?");
    $up->execute([$reportId]);
}

echo json_encode(['ok' => true, 'target' => $target['name'], 'severity' => $severity]);

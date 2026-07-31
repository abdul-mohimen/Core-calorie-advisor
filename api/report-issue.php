<?php
/* ============ Contact / Report Issue -> Admin (AJAX, JSON) ============ */
require_once dirname(__DIR__) . '/config/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok' => false, 'error' => 'POST only']); exit; }
$token = $_POST['csrf'] ?? '';
if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) { http_response_code(419); echo json_encode(['ok' => false, 'error' => 'CSRF invalid']); exit; }
if (!is_logged_in()) { http_response_code(401); echo json_encode(['ok' => false, 'error' => 'Login required']); exit; }

$targetType = in_array($_POST['target_type'] ?? '', ['doctor','trainer','hospital','general'], true) ? $_POST['target_type'] : 'general';
$targetId   = (int)($_POST['target_id'] ?? 0) ?: null;
$subject    = trim((string)($_POST['subject'] ?? ''));
$message    = trim((string)($_POST['message'] ?? ''));

if ($subject === '' || $message === '') { echo json_encode(['ok' => false, 'error' => 'Subject and details are required']); exit; }
$subject = mb_substr($subject, 0, 150);
$message = mb_substr($message, 0, 1000);

$ins = db()->prepare('INSERT INTO issue_reports (reporter_id, target_type, target_id, subject, message) VALUES (?,?,?,?,?)');
$ins->execute([$_SESSION['user']['id'], $targetType, $targetId, $subject, $message]);
$reportId = (int)db()->lastInsertId();

/* notify all admins so it shows on their bell */
$reporter = $_SESSION['user']['name'];
foreach (db()->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_COLUMN) as $adminId) {
    notify((int)$adminId, '🚩 New issue reported', $reporter . ' reported: ' . $subject, 'system', BASE_URL . '/portals/admin.php#issues');
}

echo json_encode(['ok' => true, 'id' => $reportId]);

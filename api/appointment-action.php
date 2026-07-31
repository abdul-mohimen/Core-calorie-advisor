<?php
/* ============ Trainer/Doctor accepts or rejects an appointment ============ */
require_once dirname(__DIR__) . '/config/config.php';
csrf_verify();
require_role('trainer', 'doctor', 'admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');

$id     = (int)post('id', '0');
$action = post('action');
$role   = $_SESSION['user']['role'];
$uid    = $_SESSION['user']['id'];

if (!in_array($action, ['accept', 'reject'], true)) { flash('err', 'Ghalat action.'); redirect('portals/' . $role . '.php'); }

/* ---- Ownership check: sirf apni appointment change kar sakte ho (admin sab) ---- */
$st = db()->prepare('SELECT trainer_id FROM appointments WHERE id = ?');
$st->execute([$id]); $row = $st->fetch();
if (!$row)                                        { flash('err', 'Appointment nahi mili.'); redirect('portals/' . $role . '.php'); }
if ($role !== 'admin' && (int)$row['trainer_id'] !== $uid) { flash('err', 'Ye appointment aap ki nahi hai.'); redirect('portals/' . $role . '.php'); }

$status = $action === 'accept' ? 'accepted' : 'rejected';
db()->prepare('UPDATE appointments SET status = ? WHERE id = ?')->execute([$status, $id]);

flash('ok', $action === 'accept' ? '✔ Appointment accept ho gayi!' : '✕ Appointment reject kar di gayi.');
redirect('portals/' . $role . '.php');

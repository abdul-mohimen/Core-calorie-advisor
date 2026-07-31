<?php
/* ============ Book an appointment (member/patient -> trainer/doctor) ============ */
require_once dirname(__DIR__) . '/config/config.php';
csrf_verify();
require_role('member', 'patient', 'admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('pages/trainers.php');

$trainerId = (int)post('trainer_id', '0');
$goal      = post('goal');
$date      = post('appt_date');

/* ---- Validate: trainer must exist and be trainer/doctor ---- */
$st = db()->prepare("SELECT id FROM users WHERE id = ? AND role IN ('trainer','doctor')");
$st->execute([$trainerId]);
if (!$st->fetch())            { flash('err', 'Ye trainer/doctor mojood nahi.'); redirect('pages/trainers.php'); }
if ($goal === '' || !$date)   { flash('err', 'Goal aur date dono lazmi hain.'); redirect('pages/trainers.php'); }

$ts = strtotime($date);
if (!$ts)                     { flash('err', 'Date format ghalat hai.'); redirect('pages/trainers.php'); }

$ins = db()->prepare("INSERT INTO appointments (member_id, trainer_id, goal, appt_date, status) VALUES (?,?,?,?, 'pending')");
$ins->execute([$_SESSION['user']['id'], $trainerId, mb_substr($goal, 0, 150), date('Y-m-d H:i:s', $ts)]);

flash('ok', '✔ Appointment request bhej di gayi! Trainer accept karega to portal me dikhega.');
redirect('portals/' . $_SESSION['user']['role'] . '.php');

<?php
/* Development-only confirmation for appointment checkout.  The fee and
   provider always come from the appointment row, never from the browser. */
require_once dirname(__DIR__) . '/config/config.php';
require_role('member', 'patient');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('POST only'); }
csrf_verify();
if (!sandbox_checkout_enabled()) { http_response_code(403); exit('Local sandbox disabled'); }

$appointmentId = (int)post('appointment_id');
$uid = (int)current_user()['id'];
$returnPath = current_user()['role'] === 'patient' ? 'patient/appointments.php' : 'member/appointments.php';
if ($appointmentId <= 0) { flash('err', 'Invalid appointment.'); redirect($returnPath); }

$pdo = db();
try {
    $pdo->beginTransaction();
    $st = $pdo->prepare('SELECT id, member_id, trainer_id, fee, status FROM appointments WHERE id = ? FOR UPDATE');
    $st->execute([$appointmentId]);
    $appointment = $st->fetch();
    if (!$appointment || (int)$appointment['member_id'] !== $uid || $appointment['status'] !== 'pending') {
        throw new RuntimeException('Appointment is unavailable.');
    }

    $fee = (float)$appointment['fee'];
    if ($fee <= 0) throw new RuntimeException('Invalid appointment fee.');
    $providerId = (int)$appointment['trainer_id'];
    $split = calculate_commission($fee, $providerId);
    $reference = 'sandbox-appointment-' . $appointmentId;

    $payment = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, reference_type, reference_id, description, status, stripe_session_id)
        VALUES (?, 'payment', ?, 'appointment', ?, ?, 'completed', ?)");
    $payment->execute([$uid, $fee, $appointmentId, "Local demo payment for appointment #$appointmentId", $reference]);
    $transactionId = (int)$pdo->lastInsertId();

    $pdo->prepare("INSERT INTO transactions (user_id, type, amount, reference_type, reference_id, description, status, stripe_session_id)
        VALUES (?, 'commission', ?, 'appointment', ?, ?, 'completed', ?)")
        ->execute([1, $split['commission'], $appointmentId, "Local demo commission for appointment #$appointmentId", $reference . '-commission']);
    $pdo->prepare('INSERT INTO wallets (user_id, balance) VALUES (?, ?) ON DUPLICATE KEY UPDATE balance = balance + VALUES(balance)')
        ->execute([$providerId, $split['payout']]);
    $pdo->prepare("UPDATE appointments SET status = 'accepted', transaction_id = ? WHERE id = ?")
        ->execute([$transactionId, $appointmentId]);
    $pdo->commit();

    notify($providerId, 'New demo appointment', "Appointment #$appointmentId has been confirmed in local demo mode.", 'system', $returnPath);
    flash('ok', 'Local demo appointment confirmed.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    @file_put_contents(dirname(__DIR__) . '/logs/appointment-error.log', date('c') . ' sandbox appointment ' . $e->getMessage() . PHP_EOL, FILE_APPEND | LOCK_EX);
    flash('err', $e instanceof RuntimeException ? $e->getMessage() : 'Appointment confirmation failed. Please try again.');
}
redirect($returnPath);

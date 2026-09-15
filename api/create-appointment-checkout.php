<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('member', 'patient');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('POST required', 405);
}

csrf_verify_json();

$providerId = (int)post('provider_id');
$apptDate   = trim(post('appt_date'));
$goal       = trim(post('goal'));
$uid        = (int)$_SESSION['user']['id'];

if ($providerId <= 0 || $apptDate === '' || mb_strlen($goal) < 5 || mb_strlen($goal) > 150) {
    json_error('Missing required booking details (provider, date, goal).');
}

try {
    $when = new DateTimeImmutable($apptDate);
    $now = new DateTimeImmutable('now');
    if ($when <= $now || $when > $now->modify('+1 year')) throw new RuntimeException('invalid date');
    $appointmentAt = $when->format('Y-m-d H:i:s');
} catch (Throwable $e) {
    json_error('Choose a future appointment date within the next year.');
}

// Fetch provider details to determine fee
$st = db()->prepare("SELECT tp.*, u.name, u.role FROM trainer_profiles tp JOIN users u ON u.id = tp.user_id WHERE u.id = ? AND u.role IN ('trainer', 'doctor')");
$st->execute([$providerId]);
$provider = $st->fetch();

if (!$provider) {
    json_error('Provider not found.');
}

$fee = $provider['role'] === 'doctor' ? (float)($provider['consultation_fee'] ?? 100.00) : (float)($provider['hourly_rate'] ?? 50.00);

// Create pending appointment
db()->beginTransaction();
try {
    $dupe = db()->prepare("SELECT id FROM appointments WHERE member_id = ? AND trainer_id = ? AND appt_date = ? AND status IN ('pending', 'accepted') FOR UPDATE");
    $dupe->execute([$uid, $providerId, $appointmentAt]);
    if ($dupe->fetch()) {
        db()->rollBack();
        json_error('This appointment time is already booked. Choose another slot.', 409);
    }
    $ins = db()->prepare("INSERT INTO appointments (member_id, trainer_id, appt_date, goal, status, fee, type) VALUES (?, ?, ?, ?, 'pending', ?, ?)");
    $ins->execute([$uid, $providerId, $appointmentAt, $goal, $fee, $provider['role'] === 'doctor' ? 'consultation' : 'training']);
    $apptId = db()->lastInsertId();

    db()->commit();

    // If in test mode / sandbox checkout:
    json_response([
        'success' => true,
        'appointment_id' => $apptId,
        'fee' => $fee,
        'provider_name' => $provider['name'],
        'checkout_url' => url("pages/appointment-checkout.php?id=$apptId")
    ]);
} catch (Throwable $e) {
    if (db()->inTransaction()) db()->rollBack();
    @file_put_contents(dirname(__DIR__) . '/logs/appointment-error.log', date('c') . ' create appointment ' . $e->getMessage() . PHP_EOL, FILE_APPEND | LOCK_EX);
    json_error('Appointment could not be created. Please try again.', 500);
}

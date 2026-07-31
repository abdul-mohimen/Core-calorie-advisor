<?php
require_once dirname(__DIR__) . '/config/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('POST required', 405);
}

csrf_verify_json();

$providerId = (int)post('provider_id');
$apptDate   = trim(post('appt_date'));
$goal       = trim(post('goal'));
$uid        = (int)$_SESSION['user']['id'];

if ($providerId <= 0 || !$apptDate || !$goal) {
    json_error('Missing required booking details (provider, date, goal).');
}

// Fetch provider details to determine fee
$st = db()->prepare("SELECT tp.*, u.name, u.role FROM trainer_profiles tp JOIN users u ON u.id = tp.user_id WHERE u.id = ?");
$st->execute([$providerId]);
$provider = $st->fetch();

if (!$provider) {
    json_error('Provider not found.');
}

$fee = $provider['role'] === 'doctor' ? (float)($provider['consultation_fee'] ?? 100.00) : (float)($provider['hourly_rate'] ?? 50.00);

// Create pending appointment
db()->beginTransaction();
try {
    $ins = db()->prepare("INSERT INTO appointments (member_id, trainer_id, appt_date, goal, status, fee, type) VALUES (?, ?, ?, ?, 'pending', ?, ?)");
    $ins->execute([$uid, $providerId, $apptDate, $goal, $fee, $provider['role'] === 'doctor' ? 'consultation' : 'training']);
    $apptId = db()->lastInsertId();

    db()->commit();

    // If in test mode / sandbox checkout:
    json_response([
        'success' => true,
        'appointment_id' => $apptId,
        'fee' => $fee,
        'provider_name' => $provider['name'],
        'checkout_url' => url("pages/checkout.php?type=appointment&appt_id=$apptId&fee=$fee")
    ]);
} catch (Throwable $e) {
    if (db()->inTransaction()) db()->rollBack();
    json_error('Failed to create appointment: ' . $e->getMessage());
}

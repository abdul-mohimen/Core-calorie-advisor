<?php
/* Creates a Stripe Checkout session for an appointment after loading every
   sensitive value from our database.  Browser-supplied prices are ignored. */
require_once dirname(__DIR__) . '/config/config.php';
require_role('member', 'patient');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('POST only'); }
csrf_verify();

$appointmentId = (int)post('appointment_id');
$uid = (int)current_user()['id'];
$returnPath = current_user()['role'] === 'patient' ? 'patient/appointments.php' : 'member/appointments.php';
if ($appointmentId <= 0) { flash('err', 'Invalid appointment.'); redirect($returnPath); }

$st = db()->prepare("SELECT a.id, a.member_id, a.trainer_id, a.fee, a.status, u.name AS provider_name
    FROM appointments a JOIN users u ON u.id = a.trainer_id WHERE a.id = ? AND a.member_id = ?");
$st->execute([$appointmentId, $uid]);
$appointment = $st->fetch();
if (!$appointment || $appointment['status'] !== 'pending' || (float)$appointment['fee'] <= 0) {
    flash('err', 'This appointment is not available for payment.');
    redirect($returnPath);
}

$secret = env('STRIPE_SECRET_KEY');
if ($secret === '' || !function_exists('curl_init')) {
    flash('err', 'Secure appointment billing is not configured.');
    redirect('pages/appointment-checkout.php?id=' . $appointmentId);
}

$amountCents = (int)round((float)$appointment['fee'] * 100);
$params = [
    'mode' => 'payment',
    'line_items[0][price_data][currency]' => 'usd',
    'line_items[0][price_data][product_data][name]' => 'Appointment with ' . $appointment['provider_name'],
    'line_items[0][price_data][unit_amount]' => (string)$amountCents,
    'line_items[0][quantity]' => '1',
    'customer_email' => current_user()['email'],
    'client_reference_id' => (string)$uid,
    'metadata[type]' => 'appointment',
    'metadata[user_id]' => (string)$uid,
    'metadata[appointment_id]' => (string)$appointmentId,
    'metadata[provider_id]' => (string)$appointment['trainer_id'],
    'success_url' => url('pages/billing-success.php?session_id={CHECKOUT_SESSION_ID}'),
    'cancel_url' => url('pages/appointment-checkout.php?id=' . $appointmentId),
];
$ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($params),
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $secret, 'Content-Type: application/x-www-form-urlencoded'],
]);
$raw = curl_exec($ch); $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE); curl_close($ch);
$response = json_decode((string)$raw, true);
if ($status < 200 || $status >= 300 || empty($response['url'])) {
    @file_put_contents(dirname(__DIR__) . '/logs/billing-error.log', date('c') . ' appointment checkout ' . $status . PHP_EOL, FILE_APPEND | LOCK_EX);
    flash('err', 'We could not start secure payment. Please try again.');
    redirect('pages/appointment-checkout.php?id=' . $appointmentId);
}
header('Location: ' . $response['url'], true, 303);
exit;

<?php
/* Creates a hosted Stripe Checkout session. Plan access remains unchanged here; the
   signed webhook is the sole authority that can activate a subscription. */
require_once dirname(__DIR__) . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('POST only'); }
csrf_verify();
require_login();

$plan = post('plan');
if (!in_array($plan, ['pro', 'elite'], true)) { flash('err', 'Invalid subscription plan.'); redirect('pages/pricing.php'); }
$priceId = env($plan === 'pro' ? 'STRIPE_PRICE_PRO_MONTHLY' : 'STRIPE_PRICE_ELITE_MONTHLY');
$secret = env('STRIPE_SECRET_KEY');
if ($priceId === '' || $secret === '' || !function_exists('curl_init')) { flash('err', 'Billing is not configured yet.'); redirect('pages/checkout.php?plan=' . $plan); }

$uid = (int)current_user()['id'];
$params = [
    'mode' => 'subscription',
    'line_items[0][price]' => $priceId,
    'line_items[0][quantity]' => '1',
    'customer_email' => current_user()['email'],
    'client_reference_id' => (string)$uid,
    'metadata[user_id]' => (string)$uid,
    'metadata[plan]' => $plan,
    'success_url' => url('pages/billing-success.php?session_id={CHECKOUT_SESSION_ID}'),
    'cancel_url' => url('pages/checkout.php?plan=' . $plan),
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
    @file_put_contents(dirname(__DIR__) . '/logs/billing-error.log', date('c') . ' checkout ' . $status . PHP_EOL, FILE_APPEND | LOCK_EX);
    flash('err', 'We could not start secure checkout. Please try again.'); redirect('pages/checkout.php?plan=' . $plan);
}
try {
    db()->prepare("INSERT INTO subscriptions (user_id, plan, provider, status) VALUES (?,?,'stripe','pending')")->execute([$uid, $plan]);
} catch (Throwable $e) { /* upgrade may not be installed yet; Stripe flow remains safe */ }
header('Location: ' . $response['url'], true, 303);
exit;

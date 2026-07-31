<?php
/* Sandbox (demo) checkout — ONLY available while Stripe is not configured.
   The moment real Stripe keys exist in .env this endpoint refuses, so it can
   never be used to bypass real billing. No card data ever reaches the server:
   the form's card fields have no name attributes and are validated client-side
   purely for the demo experience. */
require_once dirname(__DIR__) . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('POST only'); }
csrf_verify();
require_login();

$plan = post('plan');
if (!in_array($plan, ['pro', 'elite'], true)) { flash('err', 'Invalid subscription plan.'); redirect('pages/pricing.php'); }

$priceKey = $plan === 'pro' ? 'STRIPE_PRICE_PRO_MONTHLY' : 'STRIPE_PRICE_ELITE_MONTHLY';
if (env('STRIPE_SECRET_KEY') !== '' && env($priceKey) !== '') {
    // Real billing is live — sandbox path is permanently closed.
    http_response_code(410);
    flash('err', 'Sandbox checkout is disabled — please use secure payment.');
    redirect('pages/checkout.php?plan=' . rawurlencode($plan));
}

$uid = (int)current_user()['id'];
try {
    $pdo = db();
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE subscriptions SET status='canceled' WHERE user_id = ? AND status IN ('pending','active')")->execute([$uid]);
    $pdo->prepare("INSERT INTO subscriptions (user_id, plan, provider, status, current_period_end)
                   VALUES (?,?,'sandbox','active', DATE_ADD(NOW(), INTERVAL 1 MONTH))")->execute([$uid, $plan]);
    $pdo->prepare('UPDATE users SET plan = ? WHERE id = ?')->execute([$plan, $uid]);
    $pdo->commit();
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    @file_put_contents(dirname(__DIR__) . '/logs/billing-error.log', date('c') . ' sandbox ' . $e->getMessage() . PHP_EOL, FILE_APPEND | LOCK_EX);
    flash('err', 'Checkout mukammal nahi ho saka — dobara koshish karein.');
    redirect('pages/checkout.php?plan=' . rawurlencode($plan));
}

$_SESSION['user']['plan'] = $plan;
$method = post('payment_method', 'Gemini Pay');
notify($uid, '🎉 ' . strtoupper($plan) . ' plan activated', 'Aap ka CCA ' . ucfirst($plan) . ' subscription active ho gaya hai (sandbox). Tamam PRO features ab unlocked hain.', 'success', 'pages/workouts.php');
redirect('pages/billing-success.php?mode=sandbox&method=' . urlencode($method));

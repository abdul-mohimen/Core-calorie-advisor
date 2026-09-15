<?php
/* Stripe webhook: verify signature before trusting ANY payment event. */
require_once dirname(__DIR__) . '/config/config.php';

$secret = env('STRIPE_WEBHOOK_SECRET');
$payload = (string)file_get_contents('php://input');
$signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

/* ---- Signature verification is MANDATORY. No environment bypasses ----
   Removed 2026-07-30: this block used to accept an unsigned payload whenever
   STRIPE_WEBHOOK_SECRET was empty AND APP_ENV=development. Both were true in .env,
   so anyone could POST a fake checkout.session.completed and grant themselves a paid
   plan. An endpoint that upgrades subscriptions must never trust unsigned input,
   whatever APP_ENV says. For local testing use api/sandbox-checkout.php, or set a
   STRIPE_WEBHOOK_SECRET and sign the payload the same way Stripe does. */
if ($secret === '' || $signature === '') { http_response_code(400); exit('Missing webhook signature'); }

$timestamp = null; $v1 = null;
foreach (explode(',', $signature) as $part) {
    [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
    if ($key === 't') $timestamp = $value;
    if ($key === 'v1') $v1 = $value;
}
if (!$timestamp || !$v1 || abs(time() - (int)$timestamp) > 300) { http_response_code(400); exit('Invalid signature timestamp'); }
$expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
if (!hash_equals($expected, $v1)) { http_response_code(400); exit('Invalid signature'); }
$event = json_decode($payload, true);

if (!is_array($event)) { http_response_code(400); exit('Invalid payload'); }

$type = $event['type'] ?? '';

if ($type === 'checkout.session.completed') {
    $session = $event['data']['object'] ?? [];
    $sessionId = (string)($session['id'] ?? '');
    if ($sessionId === '') { http_response_code(400); exit('Missing checkout session'); }
    /* Stripe can retry a delivered event.  A completed session must mutate our
       ledger once only; the database unique key is the race-safe backstop. */
    $seen = db()->prepare('SELECT id FROM transactions WHERE stripe_session_id = ? LIMIT 1');
    $seen->execute([$sessionId]);
    if ($seen->fetch()) { http_response_code(200); echo 'ok'; exit; }
    $metadata = $session['metadata'] ?? [];
    $uid = (int)($metadata['user_id'] ?? $session['client_reference_id'] ?? 0);
    $type = $metadata['type'] ?? 'subscription';

    if ($type === 'appointment') {
        $apptId = (int)($metadata['appointment_id'] ?? 0);

        if ($apptId > 0 && $uid > 0) {
            db()->beginTransaction();
            try {
                $appt = db()->prepare('SELECT member_id, trainer_id, fee, status, transaction_id FROM appointments WHERE id = ? FOR UPDATE');
                $appt->execute([$apptId]);
                $appointment = $appt->fetch();
                if (!$appointment || (int)$appointment['member_id'] !== $uid || $appointment['status'] !== 'pending' || (float)$appointment['fee'] <= 0) {
                    db()->rollBack();
                    http_response_code(200); echo 'ignored'; exit;
                }
                $providerId = (int)$appointment['trainer_id'];
                $fee = (float)$appointment['fee'];

                // Calculate commission split
                $split = calculate_commission($fee, $providerId);

                // Record payment transaction
                $st = db()->prepare('INSERT INTO transactions (user_id, type, amount, reference_type, reference_id, description, status, stripe_session_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $st->execute([$uid, 'payment', $fee, 'appointment', $apptId, "Appointment #$apptId payment", 'completed', $sessionId]);
                $txnId = db()->lastInsertId();

                // Record commission for platform
                $st->execute([1, 'commission', $split['commission'], 'appointment', $apptId, "Platform commission ({$split['rate']}%) for Appointment #$apptId", 'completed', $sessionId . ':commission']);

                // Credit provider wallet
                db()->prepare('INSERT INTO wallets (user_id, balance) VALUES (?, ?) ON DUPLICATE KEY UPDATE balance = balance + VALUES(balance)')
                    ->execute([$providerId, $split['payout']]);

                // Update appointment status and transaction link
                db()->prepare("UPDATE appointments SET status = 'accepted', fee = ?, transaction_id = ? WHERE id = ?")
                    ->execute([$fee, $txnId, $apptId]);

                notify($providerId, 'New Paid Appointment', "Appointment #$apptId has been paid ($" . number_format($split['payout'], 2) . " credited to your wallet).", 'system', BASE_URL . '/trainer/dashboard.php');
                notify($uid, 'Appointment Confirmed', "Payment of $" . number_format($fee, 2) . " confirmed for Appointment #$apptId.", 'system', BASE_URL . '/member/appointments.php');

                db()->commit();
            } catch (Throwable $e) {
                if (db()->inTransaction()) db()->rollBack();
                throw $e;
            }
        }
    } else {
        // Subscription handling
        $plan = $metadata['plan'] ?? '';
        $subscriptionId = (string)($session['subscription'] ?? '');
        $customerId = (string)($session['customer'] ?? '');

        if ($uid > 0 && in_array($plan, ['pro', 'elite'], true)) {
            $user = db()->prepare('SELECT id FROM users WHERE id = ?'); $user->execute([$uid]);
            if ($user->fetch()) {
                db()->beginTransaction();
                try {
                    db()->prepare('UPDATE users SET plan = ? WHERE id = ?')->execute([$plan, $uid]);
                    db()->prepare("INSERT INTO subscriptions (user_id, plan, provider, provider_customer_id, provider_subscription_id, status) VALUES (?,?,'stripe',?,?,'active') ON DUPLICATE KEY UPDATE plan=VALUES(plan), provider_customer_id=VALUES(provider_customer_id), status='active'")
                        ->execute([$uid, $plan, $customerId, $subscriptionId]);

                    // Log transaction
                    $amount = $plan === 'elite' ? 29.99 : 9.99;
                    db()->prepare("INSERT INTO transactions (user_id, type, amount, description, status, stripe_session_id) VALUES (?, 'payment', ?, ?, 'completed', ?)")
                        ->execute([$uid, $amount, ucfirst($plan) . " Subscription", $sessionId]);

                    notify($uid, 'Subscription Active', strtoupper($plan) . ' subscription is now active.', 'system', BASE_URL . '/member/billing.php');
                    db()->commit();
                } catch (Throwable $e) {
                    if (db()->inTransaction()) db()->rollBack();
                    throw $e;
                }
            }
        }
    }
}

http_response_code(200);
echo 'ok';

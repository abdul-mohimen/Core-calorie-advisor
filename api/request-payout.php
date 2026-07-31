<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('trainer', 'doctor', 'admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('POST required', 405);
}

csrf_verify_json();

$uid    = (int)$_SESSION['user']['id'];
$amount = (float)post('amount');
$wallet = get_wallet_balance($uid);

if ($amount <= 0) {
    json_error('Invalid payout amount.');
}

if ($amount > $wallet) {
    json_error("Insufficient wallet balance. Available: $" . number_format($wallet, 2));
}

db()->beginTransaction();
try {
    // Deduct from wallet balance immediately
    db()->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ? AND balance >= ?")
        ->execute([$amount, $uid, $amount]);

    // Create payout request
    db()->prepare("INSERT INTO payout_requests (user_id, amount, status) VALUES (?, ?, 'pending')")
        ->execute([$uid, $amount]);
    $requestId = db()->lastInsertId();

    // Log transaction
    db()->prepare("INSERT INTO transactions (user_id, type, amount, reference_type, reference_id, description, status) VALUES (?, 'payout', ?, 'payout_request', ?, 'Payout request submitted', 'pending')")
        ->execute([$uid, $amount, $requestId]);

    notify_admins('Payout Requested', $_SESSION['user']['name'] . " requested a payout of $" . number_format($amount, 2), BASE_URL . '/admin/monetization-stripe.php');

    db()->commit();

    json_response(['success' => true, 'message' => 'Payout request submitted successfully. Admin review pending.']);
} catch (Throwable $e) {
    if (db()->inTransaction()) db()->rollBack();
    json_error('Failed to submit payout request: ' . $e->getMessage());
}

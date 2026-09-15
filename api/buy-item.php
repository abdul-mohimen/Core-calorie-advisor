<?php
require_once dirname(__DIR__) . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('POST only');
}

csrf_verify();
require_login();

$itemId = (int)post('item_id');
$name = post('name');
$address = post('address');
$paymentMethod = post('payment_method', 'local-demo');

if ($itemId <= 0 || mb_strlen($name) < 3 || mb_strlen($name) > 120 || mb_strlen($address) < 10 || mb_strlen($address) > 300) {
    flash('err', 'Inputs incomplete or incorrect. Address detailed hona chahiye.');
    redirect('pages/shop.php');
}

if (!in_array($paymentMethod, ['local-demo', 'cash_on_delivery'], true)) {
    flash('err', 'Invalid payment method.');
    redirect('pages/shop.php');
}
if ($paymentMethod === 'local-demo' && !sandbox_checkout_enabled()) {
    http_response_code(403);
    flash('err', 'Local demo checkout is disabled. Configure a live payment provider.');
    redirect('pages/shop.php');
}

// Fetch item details
$st = db()->prepare("SELECT * FROM shop_items WHERE id = ?");
$st->execute([$itemId]);
$item = $st->fetch();

if (!$item) {
    flash('err', 'Product nahi mila.');
    redirect('pages/shop.php');
}

$uid = (int)current_user()['id'];
$totalPrice = (float)$item['price'];
$orderStatus = $paymentMethod === 'cash_on_delivery' ? 'pending' : 'completed';

try {
    $pdo = db();
    $pdo->beginTransaction();
    
    // The shipping address is validated above (min 10 chars) but used to be
    // dropped on the floor here — the customer typed it, the order stored it
    // nowhere, and pages/receipt.php then rendered a PHP warning trying to
    // print $order['address']. It is now persisted with the order.
    $ins = $pdo->prepare("INSERT INTO shop_orders (user_id, item_id, quantity, total_price, payment_method, status, address)
                          VALUES (?, ?, 1, ?, ?, ?, ?)");
    $ins->execute([$uid, $itemId, $totalPrice, $paymentMethod, $orderStatus, $address]);
    $orderId = (int)$pdo->lastInsertId();
    
    $pdo->commit();
    
    /* Cross-portal notification.
       The buyer gets their confirmation, and every admin is told a sale
       happened — previously only the buyer was notified, so nobody running the
       platform learned about an order from inside the app. Each side gets a
       link into ITS OWN portal, so the notification is actionable for that
       role rather than a dead end. */
    notify(
        $uid,
        '📦 Order Placed Successfully',
        'Aap ka order "' . $item['name'] . '" secure processing me chala gaya hai via ' . $paymentMethod . '.',
        'info',
        'pages/receipt.php?order_id=' . $orderId
    );

    $buyerName = current_user()['name'] ?? 'A member';
    foreach ($pdo->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_COLUMN) as $adminId) {
        if ((int)$adminId === $uid) { continue; }   // don't notify an admin about their own purchase twice
        notify(
            (int)$adminId,
            '🛒 New shop order #' . $orderId,
            $buyerName . ' ne "' . $item['name'] . '" khareeda — $' . number_format((float)$totalPrice, 2)
                . ' via ' . $paymentMethod . '.',
            'system',
            'pages/receipt.php?order_id=' . $orderId
        );
    }
    
    flash('ok', '🎉 Order successfully confirmed via ' . $paymentMethod . '!');
    redirect('pages/receipt.php?order_id=' . $orderId);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    @file_put_contents(dirname(__DIR__) . '/logs/shop-error.log', date('c') . ' shop ' . $e->getMessage() . PHP_EOL, FILE_APPEND | LOCK_EX);
    flash('err', 'Order save nahi ho saka. Dobara koshish karein.');
    redirect('pages/checkout-shop.php?id=' . $itemId);
}

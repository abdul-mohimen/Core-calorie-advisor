<?php
require_once dirname(__DIR__) . '/config/config.php';
require_login();

$uid = (int)current_user()['id'];

/* All mutations are POST + CSRF. Quantity is clamped server-side; the client
   value is never trusted, matching api/cart-add.php. */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = post('action');
    $itemId = (int)post('item_id');

    if ($action === 'remove' && $itemId > 0) {
        db()->prepare('DELETE FROM shop_cart WHERE user_id = ? AND item_id = ?')->execute([$uid, $itemId]);
        flash('ok', 'Cart se hata diya.');
    } elseif ($action === 'qty' && $itemId > 0) {
        $qty = (int)post('qty');
        if ($qty < 1)  { $qty = 1; }
        if ($qty > 99) { $qty = 99; }
        db()->prepare('UPDATE shop_cart SET quantity = ? WHERE user_id = ? AND item_id = ?')->execute([$qty, $uid, $itemId]);
        flash('ok', 'Quantity update ho gayi.');
    } elseif ($action === 'clear') {
        db()->prepare('DELETE FROM shop_cart WHERE user_id = ?')->execute([$uid]);
        flash('ok', 'Cart khali kar diya.');
    }
    redirect('pages/cart.php');
}

$st = db()->prepare(
    'SELECT c.quantity, i.*
       FROM shop_cart c
       JOIN shop_items i ON i.id = c.item_id
      WHERE c.user_id = ?
      ORDER BY c.created_at DESC'
);
$st->execute([$uid]);
$rows = $st->fetchAll();

$subtotal = 0.0;
foreach ($rows as $r) { $subtotal += (float)$r['price'] * (int)$r['quantity']; }
$totalQty = array_sum(array_map(static fn($r) => (int)$r['quantity'], $rows));

$pageTitle = 'Cart';
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="wrap" style="padding-top:2.5rem; padding-bottom:4rem">

  <div class="sec-head" style="margin-bottom:1.5rem">
    <div>
      <span class="eyebrow">Your basket</span>
      <h1 style="font-family:var(--font-disp); text-transform:uppercase; letter-spacing:.04em; color:var(--text-1)">Cart</h1>
      <p style="color:var(--text-2); margin-top:.35rem">
        <?= (int)$totalQty ?> item<?= $totalQty === 1 ? '' : 's' ?> in your cart.
      </p>
    </div>
  </div>

  <?php if (!$rows): ?>
    <div class="cca-card" style="text-align:center; padding:64px 24px">
      <div class="cca-card__icon" style="margin:0 auto 16px">🛒</div>
      <h3 class="cca-card__title" style="justify-content:center">Cart khali hai</h3>
      <p class="cca-card__subtitle" style="margin-bottom:20px">Shop se koi product "Add to Cart" karein.</p>
      <a class="wk-btn wk-btn-open" style="max-width:220px; margin:0 auto" href="<?= url('pages/shop.php') ?>">Browse Shop</a>
    </div>
  <?php else: ?>
    <div style="display:grid; grid-template-columns:minmax(0,1fr); gap:20px" class="cca-cart-grid">
      <div style="display:flex; flex-direction:column; gap:14px">
        <?php foreach ($rows as $r): $line = (float)$r['price'] * (int)$r['quantity']; ?>
          <div class="cca-card" style="flex-direction:row; align-items:center; gap:16px; padding:14px">
            <img src="<?= e($r['image']) ?>" alt="<?= e($r['name']) ?>" loading="lazy"
                 style="width:88px; height:88px; object-fit:cover; border-radius:12px; flex:none; background:var(--bg3)">
            <div style="flex:1; min-width:0">
              <h3 class="cca-card__title" style="margin-bottom:4px"><?= e($r['name']) ?></h3>
              <span class="cca-card__label"><?= e($r['category']) ?> · $<?= number_format((float)$r['price'], 2) ?> each</span>
              <div style="display:flex; align-items:center; gap:8px; margin-top:10px; flex-wrap:wrap">
                <form method="post" style="display:flex; align-items:center; gap:6px">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="qty">
                  <input type="hidden" name="item_id" value="<?= (int)$r['id'] ?>">
                  <label class="cca-card__label" for="q<?= (int)$r['id'] ?>">Qty</label>
                  <input id="q<?= (int)$r['id'] ?>" name="qty" type="number" min="1" max="99"
                         value="<?= (int)$r['quantity'] ?>"
                         style="width:70px; padding:6px 8px; border-radius:8px; border:1px solid var(--border); background:var(--bg2); color:var(--text-1)">
                  <button type="submit" class="wk-btn cca-btn-soft" style="width:auto; padding:7px 12px">Update</button>
                </form>
                <form method="post">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="remove">
                  <input type="hidden" name="item_id" value="<?= (int)$r['id'] ?>">
                  <button type="submit" class="wk-btn cca-btn-soft" style="width:auto; padding:7px 12px">Remove</button>
                </form>
              </div>
            </div>
            <div style="text-align:right; flex:none">
              <span class="cca-card__label">Line total</span>
              <div class="cca-card__value" style="font-size:22px">$<?= number_format($line, 2) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="cca-card" style="align-self:start">
        <h3 class="cca-card__title">Order Summary</h3>
        <div class="cca-card__meta" style="justify-content:space-between">
          <span class="cca-card__label">Items</span><span><?= (int)$totalQty ?></span>
        </div>
        <div class="cca-card__meta" style="justify-content:space-between">
          <span class="cca-card__label">Shipping</span><span>Free</span>
        </div>
        <div class="cca-card__footer" style="justify-content:space-between">
          <span class="cca-card__label">Subtotal</span>
          <span class="cca-card__value" style="font-size:24px">$<?= number_format($subtotal, 2) ?></span>
        </div>
        <p class="cca-card__subtitle" style="font-size:12px">
          Checkout ek waqt me ek product process karta hai, is liye har item alag se confirm hoga.
        </p>
        <div style="display:flex; flex-direction:column; gap:8px">
          <a class="wk-btn wk-btn-open" href="<?= url('pages/checkout-shop.php?id=' . (int)$rows[0]['id']) ?>">
            Checkout “<?= e($rows[0]['name']) ?>”
          </a>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="clear">
            <button type="submit" class="wk-btn cca-btn-soft" style="width:100%">Clear Cart</button>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>

</div>

<style>
  @media (min-width: 900px) {
    .cca-cart-grid { grid-template-columns: minmax(0,1.6fr) minmax(300px,.9fr) !important; }
  }
</style>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

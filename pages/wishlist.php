<?php
require_once dirname(__DIR__) . '/config/config.php';
require_login();

$uid = (int)current_user()['id'];

/* Remove is a POST so it is CSRF-protected — a GET link would let any page
   on the internet empty a signed-in user's wishlist with an <img> tag. */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $itemId = (int)post('item_id');
    if ($itemId > 0) {
        db()->prepare('DELETE FROM shop_wishlist WHERE user_id = ? AND item_id = ?')->execute([$uid, $itemId]);
        flash('ok', 'Wishlist se hata diya.');
    }
    redirect('pages/wishlist.php');
}

$st = db()->prepare(
    'SELECT i.*, w.created_at AS saved_at
       FROM shop_wishlist w
       JOIN shop_items i ON i.id = w.item_id
      WHERE w.user_id = ?
      ORDER BY w.created_at DESC'
);
$st->execute([$uid]);
$items = $st->fetchAll();

$pageTitle = 'Wishlist';
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="wrap" style="padding-top:2.5rem; padding-bottom:4rem">

  <div class="sec-head" style="margin-bottom:1.5rem">
    <div>
      <span class="eyebrow">Saved for later</span>
      <h1 style="font-family:var(--font-disp); text-transform:uppercase; letter-spacing:.04em; color:var(--text-1)">Wishlist</h1>
      <p style="color:var(--text-2); margin-top:.35rem">
        <?= count($items) ?> item<?= count($items) === 1 ? '' : 's' ?> saved.
      </p>
    </div>
  </div>

  <?php if (!$items): ?>
    <div class="cca-card" style="text-align:center; padding:64px 24px">
      <div class="cca-card__icon" style="margin:0 auto 16px">♡</div>
      <h3 class="cca-card__title" style="justify-content:center">Wishlist khali hai</h3>
      <p class="cca-card__subtitle" style="margin-bottom:20px">
        Shop me kisi product par dil ka nishan dabayein — wo yahan mehfooz ho jayega.
      </p>
      <a class="wk-btn wk-btn-open" style="max-width:220px; margin:0 auto" href="<?= url('pages/shop.php') ?>">Browse Shop</a>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
      <?php foreach ($items as $p): ?>
        <div class="cca-card">
          <div class="cca-card__media">
            <img src="<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
            <span class="cca-card__badge cca-card__badge--left"><?= e($p['category']) ?></span>
          </div>
          <div class="flex-1 flex flex-col justify-between">
            <div>
              <h3 class="cca-card__title"><?= e($p['name']) ?></h3>
              <p class="cca-card__subtitle" style="display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden">
                <?= e($p['description']) ?>
              </p>
            </div>
            <div class="cca-card__footer" style="display:block">
              <div class="cca-card__meta" style="justify-content:space-between; margin-bottom:10px">
                <span class="cca-card__label">Price</span>
                <span class="cca-card__value" style="font-size:20px">$<?= number_format((float)$p['price'], 2) ?></span>
              </div>
              <div class="cca-card__actions">
                <form method="post" style="flex:1; display:flex">
                  <?= csrf_field() ?>
                  <input type="hidden" name="item_id" value="<?= (int)$p['id'] ?>">
                  <button type="submit" class="wk-btn cca-btn-soft" style="width:100%">Remove</button>
                </form>
                <a class="wk-btn wk-btn-open" href="<?= url('pages/checkout-shop.php?id=' . (int)$p['id']) ?>">Buy Now</a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

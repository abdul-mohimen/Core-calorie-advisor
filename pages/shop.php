<?php
require_once dirname(__DIR__) . '/config/config.php';

// Dynamically create tables and seed if not present
try {
    $db = db();
    $db->exec("CREATE TABLE IF NOT EXISTS shop_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        image VARCHAR(300),
        category VARCHAR(50)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    $db->exec("CREATE TABLE IF NOT EXISTS shop_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        item_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        total_price DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50) NOT NULL DEFAULT 'Gemini Pay',
        status ENUM('pending','completed','canceled') NOT NULL DEFAULT 'completed',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (item_id) REFERENCES shop_items(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Shipping address column — api/buy-item.php validates an address but had
    // no column to store it in, so pages/receipt.php warned on every invoice.
    $hasAddress = $db->query("SHOW COLUMNS FROM shop_orders LIKE 'address'")->fetch();
    if (!$hasAddress) {
        $db->exec("ALTER TABLE shop_orders ADD COLUMN address VARCHAR(300) NULL AFTER payment_method");
    }

    // Wishlist: UNIQUE(user_id,item_id) makes the toggle idempotent, so a
    // double-click can never create two rows.
    $db->exec("CREATE TABLE IF NOT EXISTS shop_wishlist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        item_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_wish (user_id, item_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (item_id) REFERENCES shop_items(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Cart: same UNIQUE key so adding an item already in the cart increments
    // its quantity instead of inserting a duplicate row.
    $db->exec("CREATE TABLE IF NOT EXISTS shop_cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        item_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_cart (user_id, item_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (item_id) REFERENCES shop_items(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Seed data if empty
    $count = (int)$db->query("SELECT COUNT(*) FROM shop_items")->fetchColumn();
    if ($count === 0) {
        $items = [
            [
                'CCA Adjustable Dumbbells (Pair)', 
                'Premium 24kg adjustable dumbbell set. Fast select dials allow weights adjustments from 2kg to 24kg instantly.', 
                149.99, 
                'https://images.unsplash.com/photo-1638536532686-d610adfc8e5c?w=500&q=60&auto=format&fit=crop', 
                'Equipment'
            ],
            [
                'Pro Resistance Band Set', 
                'Heavy-duty latex bands with anti-snap technology. Includes 5 colored bands, handles, ankle straps and a carry bag.', 
                24.99, 
                'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?w=500&q=60&auto=format&fit=crop', 
                'Equipment'
            ],
            [
                'CCA Whey Protein Pure-Iso', 
                'Premium grass-fed whey isolate. 25g protein per scoop, zero sugar, chocolate fudge flavor for clean muscle recovery.', 
                59.99, 
                'https://images.unsplash.com/photo-1593095948071-474c5cc2989d?w=500&q=60&auto=format&fit=crop', 
                'Supplements'
            ],
            [
                'Premium Non-Slip Yoga Mat', 
                'Eco-friendly high-density TPE mat. 6mm thick cushioning with alignment lines for optimal yoga and core workouts.', 
                34.99, 
                'https://images.unsplash.com/photo-1601925260368-ae2f83cf8b7f?w=500&q=60&auto=format&fit=crop', 
                'Equipment'
            ],
            [
                'Core Calorie Advisor Steel Shaker Bottle', 
                'Double-wall vacuum insulated stainless steel shaker. Keeps shakes ice cold for 24 hours. Leak-proof leak guard lid.', 
                19.99, 
                'https://images.unsplash.com/photo-1553530666-ba11a7da3888?w=500&q=60&auto=format&fit=crop', 
                'Accessories'
            ],
            [
                'Cast Iron Kettlebell 16kg', 
                'Solid cast-iron kettlebell with powder coat finish. Wide textured handle for ultimate grip and conditioning loops.', 
                49.99, 
                'https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?w=500&q=60&auto=format&fit=crop', 
                'Equipment'
            ],
            [
                'CCA Athletic Compression Shirt', 
                'Ultra-breathable dry-fit material with ergonomic flat seams. Drives heat away and maintains muscle warmth.', 
                29.99, 
                'https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?w=500&q=60&auto=format&fit=crop', 
                'Apparel'
            ],
            [
                'CCA Gym Training Gloves', 
                'Breathable mesh backing with padded leather palms and integrated wrist wrap support. Prevents calluses.', 
                15.99, 
                'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?w=500&q=60&auto=format&fit=crop', 
                'Accessories'
            ]
        ];

        $ins = $db->prepare("INSERT INTO shop_items (name, description, price, image, category) VALUES (?,?,?,?,?)");
        foreach ($items as $it) {
            $ins->execute($it);
        }
    }
} catch (Throwable $dbErr) {
    // Fail silently or log
}

// Fetch categories
$categories = ['All', 'Equipment', 'Supplements', 'Apparel', 'Accessories'];
$selectedCat = get('category', 'All');
$q = get('q');

// Build query
$params = [];
$sql = "SELECT * FROM shop_items WHERE 1=1";
if ($selectedCat !== 'All') {
    $sql .= " AND category = ?";
    $params[] = $selectedCat;
}
if ($q !== '') {
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
$sql .= " ORDER BY id";

$st = db()->prepare($sql);
$st->execute($params);
$products = $st->fetchAll();

/* Which of these products the viewer has already wish-listed, so the heart
   renders in its saved state on first paint rather than flickering after JS. */
$wishIds = [];
if (is_logged_in()) {
    $w = db()->prepare('SELECT item_id FROM shop_wishlist WHERE user_id = ?');
    $w->execute([(int)current_user()['id']]);
    $wishIds = array_map('intval', $w->fetchAll(PDO::FETCH_COLUMN));
}

$pageTitle = 'CCA Shop';
include dirname(__DIR__) . '/includes/header.php';
?>

<!-- Hero Section with Canvas Target -->
<div class="relative h-[32vh] min-h-[260px] w-full bg-[url('https://images.unsplash.com/photo-1517838277536-f5f99be501cd?w=1600&q=80&auto=format&fit=crop')] bg-cover bg-center bg-no-repeat flex items-center tf-hero-anim">
  <div class="absolute inset-0 bg-black/80 z-0"></div>
  <div class="relative z-10 max-w-7xl mx-auto px-6 w-full text-center">
    <span class="text-[#FFB800] font-bold tracking-[0.25em] uppercase text-xs md:text-sm block">Gear Up</span>
    <h1 class="text-white font-black tracking-wider uppercase mt-2 text-3xl md:text-5xl">CCA Shop</h1>
    <p class="text-gray-300 max-w-2xl mx-auto mt-3 leading-relaxed text-xs md:text-sm">Premium gym equipment, clothing, and recovery accessories built to elevate your athletic performance.</p>
  </div>
</div>

<div class="wrap text-gray-900 dark:text-white" style="padding-top: 3rem; padding-bottom: 5rem;">
  
  <!-- Search and Filter Bar -->
  <div class="flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4 mb-8">
    
    <!-- Category Pills -->
    <div class="flex gap-2 flex-wrap">
      <?php foreach ($categories as $cat): ?>
        <a class="px-4 py-2 rounded-xl text-xs font-extrabold uppercase tracking-wider border border-gray-200 dark:border-white/10 text-gray-600 dark:text-gray-400 bg-white dark:bg-[#121212] transition-all duration-200 hover:text-brand-accent hover:border-brand-accent <?= $selectedCat === $cat ? 'bg-gradient-to-r from-[#FF6B1A] to-[#FF3D00] text-white border-transparent' : '' ?>" 
           href="<?= url('pages/shop.php?category=' . urlencode($cat) . ($q ? '&q=' . urlencode($q) : '')) ?>">
          <?= e($cat) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Search Input -->
    <form method="get" class="w-full max-w-sm">
      <input type="hidden" name="category" value="<?= e($selectedCat) ?>">
      <div class="flex items-center bg-white dark:bg-[#121212] border border-gray-200 dark:border-white/10 rounded-xl px-3 py-2 shadow-sm focus-within:border-[#FF6B1A]">
        <svg class="text-gray-400 mr-2" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="7"/><path d="M20 20 L16.5 16.5"/></svg>
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search products..." class="bg-transparent border-0 text-sm focus:outline-none focus:ring-0 text-gray-900 dark:text-white w-full">
      </div>
    </form>
  </div>

  <!-- Products Grid -->
  <?php if (!$products): ?>
    <div class="cca-card" style="text-align:center; padding:64px 24px">
      <span class="text-4xl block mb-3">🔍</span>
      <h3 class="text-lg font-bold">No Products Found</h3>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Tamam filters check karein ya naya search word try karein.</p>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
      <?php foreach ($products as $p): ?>
        <!-- Phase 10: joins the shared card system (.cca-card + .cca-card__media)
             instead of hand-rolling a panel out of Tailwind utilities, so shop
             products match workout and category cards in both themes. -->
        <div class="cca-card group">

          <!-- Image Container -->
          <div class="cca-card__media">
            <img src="<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
            <span class="cca-card__badge cca-card__badge--left">
              <?= e($p['category']) ?>
            </span>
            <button type="button" class="cca-card__wish js-wish"
                    data-id="<?= (int)$p['id'] ?>"
                    aria-pressed="<?= in_array((int)$p['id'], $wishIds, true) ? 'true' : 'false' ?>"
                    aria-label="<?= in_array((int)$p['id'], $wishIds, true) ? 'Remove from wishlist' : 'Save to wishlist' ?>"
                    title="Wishlist">
              <svg viewBox="0 0 24 24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1L12 21.2l7.7-7.7 1.1-1a5.5 5.5 0 0 0 0-7.9z"/></svg>
            </button>
          </div>

          <!-- Body -->
          <div class="flex-1 flex flex-col justify-between">
            <div>
              <h3 class="text-base font-extrabold text-gray-900 dark:text-white mb-2 leading-tight"><?= e($p['name']) ?></h3>
              <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed mb-4 line-clamp-3"><?= e($p['description']) ?></p>
            </div>
            
            <div class="cca-card__footer" style="display:block">
              <div class="cca-card__meta" style="justify-content:space-between; margin-bottom:10px">
                <span class="cca-card__label">Price</span>
                <span class="cca-card__value" style="font-size:20px">$<?= number_format($p['price'], 2) ?></span>
              </div>

              <!-- Add to Cart is the secondary (outlined) action so Buy Now
                   remains the single filled CTA on the card. -->
              <div class="cca-card__actions">
                <button type="button" class="wk-btn cca-btn-soft js-cart" data-id="<?= (int)$p['id'] ?>">
                  Add to Cart
                </button>
                <a href="<?= url('pages/checkout-shop.php?id=' . $p['id']) ?>" class="wk-btn wk-btn-open">
                  Buy Now
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<script>
/* Shop card actions — wishlist toggle + add to cart.
   Anonymous visitors are sent to login rather than silently no-op'ing: the
   shop page itself is public, so the buttons render for logged-out users too. */
(function () {
  const LOGIN = <?= json_encode(url('auth/login.php')) ?>;
  const API   = <?= json_encode(url('api/')) ?>;

  async function call(endpoint, body, btn) {
    btn.disabled = true;
    try {
      const res = await fetch(API + endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(Object.assign({ csrf: window.TF.csrf }, body))
      });
      const data = await res.json();
      if (res.status === 401 || data.login) { window.location.href = LOGIN; return null; }
      if (!data.ok) { alert(data.error || 'Kuch ghalat ho gaya.'); return null; }
      return data;
    } catch (e) {
      alert('Network error — dobara koshish karein.');
      return null;
    } finally {
      btn.disabled = false;
    }
  }

  document.querySelectorAll('.js-wish').forEach(btn => {
    btn.addEventListener('click', async () => {
      const data = await call('wishlist-toggle.php', { item_id: btn.dataset.id }, btn);
      if (!data) return;
      btn.setAttribute('aria-pressed', data.saved ? 'true' : 'false');
      btn.setAttribute('aria-label', data.saved ? 'Remove from wishlist' : 'Save to wishlist');
    });
  });

  document.querySelectorAll('.js-cart').forEach(btn => {
    const label = btn.textContent.trim();
    btn.addEventListener('click', async () => {
      const data = await call('cart-add.php', { item_id: btn.dataset.id, qty: 1 }, btn);
      if (!data) return;
      btn.textContent = '✓ Added';
      setTimeout(() => { btn.textContent = label; }, 1600);
    });
  });
})();
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

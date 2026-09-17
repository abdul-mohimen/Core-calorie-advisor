<?php
require_once dirname(__DIR__) . '/config/config.php';
require_login();

$orderId = (int)get('order_id');
$uid = (int)current_user()['id'];

$st = db()->prepare("SELECT o.*, i.name AS item_name, i.description AS item_desc, i.image AS item_image, u.name AS user_name, u.email AS user_email 
                     FROM shop_orders o 
                     JOIN shop_items i ON i.id = o.item_id 
                     JOIN users u ON u.id = o.user_id 
                     WHERE o.id = ? AND (o.user_id = ? OR ? IN (SELECT id FROM users WHERE role='admin'))");
$st->execute([$orderId, $uid, $uid]);
$order = $st->fetch();

if (!$order) {
    flash('err', 'Invoice not found.');
    redirect('pages/shop.php');
}

$pageTitle = 'Order Invoice #' . $order['id'];
include dirname(__DIR__) . '/includes/header.php';
?>

<style>
.invoice-wrap { max-width: 800px; margin: 40px auto 90px; padding: 0 24px; }
.invoice-card {
  /* Phase 10: stays a bespoke class because of its @media print variant
     below, which .cca-card has no business owning. Its VISUAL design is
     aligned to the shared card system so it does not look like a different
     product: same surface token, border, radius and elevation.
     --surface (not --bg3) also fixes the light-theme case where invoice
     text sat on a grey panel. */
  background: var(--surface); border: 1px solid var(--border);
  border-radius: 16px; box-shadow: var(--shadow-1);
  overflow: hidden; padding: 32px; position: relative;
}
.invoice-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px dashed var(--line); padding-bottom: 30px; margin-bottom: 30px; flex-wrap: wrap; gap: 20px; }
.invoice-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; color: var(--text-1); font-family: var(--disp); font-size: 20px; }
.invoice-logo svg { width: 44px; height: 44px; }
.invoice-meta { text-align: right; }
.invoice-meta h2 { font-family: var(--tech); font-size: 22px; text-transform: uppercase; color: var(--molten); margin: 0 0 5px; }
.invoice-meta p { color: var(--muted); font-size: 13px; margin: 0; }
.invoice-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 35px; }
.invoice-block h3 { font-family: var(--tech); text-transform: uppercase; font-size: 13px; letter-spacing: 1px; color: var(--muted); border-bottom: 1px solid var(--line); padding-bottom: 6px; margin-bottom: 12px; }
.invoice-block p { color: var(--text-1); font-size: 14.5px; line-height: 1.5; margin: 0 0 4px; }
.invoice-block small { color: var(--muted); display: block; font-size: 12.5px; }
.invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 35px; }
.invoice-table th { font-family: var(--tech); text-transform: uppercase; font-size: 12px; letter-spacing: 1.2px; text-align: left; color: var(--muted); padding: 12px 10px; border-bottom: 1.5px solid var(--line2); }
.invoice-table td { padding: 16px 10px; border-bottom: 1px solid var(--line); font-size: 14.5px; color: var(--text-1); }
.invoice-table td.num { font-family: 'JetBrains Mono', monospace; text-align: right; }
.invoice-totals { display: flex; flex-direction: column; align-items: flex-end; margin-bottom: 40px; border-top: 1.5px double var(--line2); padding-top: 20px; }
.invoice-total-row { display: flex; justify-content: space-between; width: 280px; font-size: 14px; color: var(--muted); padding: 5px 0; }
.invoice-total-row.grand { font-size: 18px; font-weight: 800; color: var(--text-1); border-top: 1px solid var(--line); margin-top: 8px; padding-top: 12px; }
.invoice-total-row.grand span { color: var(--molten); }
.invoice-footer { text-align: center; border-top: 1px solid var(--line); padding-top: 30px; margin-top: 10px; }
.invoice-footer p { font-size: 13.5px; color: var(--muted); margin: 0 0 15px; }
.invoice-badge { display: inline-flex; align-items: center; gap: 6px; background: rgba(46, 204, 113, 0.12); border: 1px solid rgba(46, 204, 113, 0.35); color: var(--success-text); font: 700 11px var(--tech); letter-spacing: 1.2px; text-transform: uppercase; border-radius: 999px; padding: 5px 14px; margin-top: 10px; }
.barcode-svg { max-width: 260px; margin: 0 auto; opacity: 0.65; transition: opacity 0.2s; }
.barcode-svg:hover { opacity: 0.85; }
.invoice-actions { display: flex; justify-content: center; gap: 15px; margin-top: 30px; }
@media print {
  body { background: #fff !important; color: #000 !important; }
  header.nav, aside, footer, .invoice-actions, #tf-chat { display: none !important; }
  .invoice-wrap { max-width: 100%; margin: 0; padding: 0; }
  .invoice-card { background: #fff !important; border: 0 !important; box-shadow: none !important; padding: 0 !important; color: #000 !important; }
  .invoice-card h1, .invoice-card h2, .invoice-card h3, .invoice-block p, .invoice-table td, .invoice-total-row.grand, .invoice-logo { color: #000 !important; }
  .invoice-table th { border-bottom: 2px solid #000 !important; color: #000 !important; }
  .invoice-table td { border-bottom: 1px solid #ddd !important; }
  .invoice-total-row.grand { border-top: 2px solid #000 !important; }
  .invoice-badge { border: 1px solid #2ECC71 !important; color: #2ECC71 !important; print-color-adjust: exact; }
}
</style>

<div class="invoice-wrap">
  <article class="invoice-card">
    
    <!-- Invoice Header -->
    <div class="invoice-header">
      <a class="invoice-logo" href="<?= url('index.php') ?>">
        <?= $BRAND_SVG ?? '' ?>
        <b>CORE<em>CALORIE</em></b>
      </a>
      <div class="invoice-meta">
        <h2>INVOICE</h2>
        <p>Invoice ID: <b>#TF-SHOP-<?= str_pad((string)$order['id'], 6, '0', STR_PAD_LEFT) ?></b></p>
        <p>Date: <?= date('F j, Y', strtotime($order['created_at'])) ?></p>
        <div class="invoice-badge">✓ Paid Completed</div>
      </div>
    </div>

    <!-- Client / Seller Grid -->
    <div class="invoice-grid">
      <div class="invoice-block">
        <h3>Billed To</h3>
        <p><strong><?= e($order['user_name']) ?></strong></p>
        <p>Email: <?= e($order['user_email']) ?></p>
        <p>Shipping: <?= e($order['address'] ?? '—') ?></p>
      </div>
      <div class="invoice-block">
        <h3>Payment Details</h3>
        <p>Method: <b><?= e($order['payment_method']) ?></b></p>
        <p>Transaction ID: <span style="font-family: monospace; font-size: 13px;"><?= hash('md5', (string)$order['id']) ?></span></p>
        <p>Status: <b style="color:var(--success-text);">Success</b></p>
      </div>
    </div>

    <!-- Items Table -->
    <table class="invoice-table">
      <thead>
        <tr>
          <th>Item Description</th>
          <th style="text-align: center; width: 80px;">Qty</th>
          <th style="text-align: right; width: 140px;">Unit Price</th>
          <th style="text-align: right; width: 140px;">Total Price</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>
            <strong><?= e($order['item_name']) ?></strong>
            <small style="color: var(--muted); font-size: 12px; margin-top: 4px;"><?= e($order['item_desc']) ?></small>
          </td>
          <td style="text-align: center; font-family: monospace;"><?= (int)$order['quantity'] ?></td>
          <td class="num">$<?= number_format($order['total_price'] / $order['quantity'], 2) ?></td>
          <td class="num">$<?= number_format($order['total_price'], 2) ?></td>
        </tr>
      </tbody>
    </table>

    <!-- Totals -->
    <div class="invoice-totals">
      <div class="invoice-total-row">
        <span>Subtotal</span>
        <strong style="font-family: monospace;">$<?= number_format($order['total_price'], 2) ?></strong>
      </div>
      <div class="invoice-total-row">
        <span>Sales Tax (0%)</span>
        <strong style="font-family: monospace;">$0.00</strong>
      </div>
      <div class="invoice-total-row">
        <span>Shipping &amp; Handling</span>
        <strong style="font-family: monospace;">$0.00 (Free)</strong>
      </div>
      <div class="invoice-total-row grand">
        <span>Total Paid</span>
        <strong style="font-family: monospace;"><span>$<?= number_format($order['total_price'], 2) ?></span></strong>
      </div>
    </div>

    <!-- Barcode / QR Section -->
    <div class="invoice-footer">
      <p>Please present this slip at the time of delivery or keep it for your training records.</p>
      
      <!-- Realistic looking SVG barcode -->
      <div class="barcode-svg" aria-hidden="true">
        <svg viewBox="0 0 200 45" width="100%" height="auto">
          <rect width="200" height="45" fill="none"/>
          <g fill="var(--muted)">
            <rect x="10" y="5" width="2" height="30"/>
            <rect x="14" y="5" width="1" height="30"/>
            <rect x="17" y="5" width="3" height="30"/>
            <rect x="22" y="5" width="1" height="30"/>
            <rect x="25" y="5" width="2" height="30"/>
            <rect x="30" y="5" width="4" height="30"/>
            <rect x="36" y="5" width="1" height="30"/>
            <rect x="39" y="5" width="2" height="30"/>
            <rect x="43" y="5" width="3" height="30"/>
            <rect x="48" y="5" width="1" height="30"/>
            <rect x="52" y="5" width="2" height="30"/>
            <rect x="56" y="5" width="4" height="30"/>
            <rect x="62" y="5" width="1" height="30"/>
            <rect x="65" y="5" width="3" height="30"/>
            <rect x="70" y="5" width="1" height="30"/>
            <rect x="73" y="5" width="2" height="30"/>
            <rect x="78" y="5" width="4" height="30"/>
            <rect x="84" y="5" width="1" height="30"/>
            <rect x="87" y="5" width="2" height="30"/>
            <rect x="91" y="5" width="3" height="30"/>
            <rect x="96" y="5" width="1" height="30"/>
            <rect x="100" y="5" width="2" height="30"/>
            <rect x="104" y="5" width="4" height="30"/>
            <rect x="110" y="5" width="1" height="30"/>
            <rect x="113" y="5" width="3" height="30"/>
            <rect x="118" y="5" width="1" height="30"/>
            <rect x="121" y="5" width="2" height="30"/>
            <rect x="126" y="5" width="4" height="30"/>
            <rect x="132" y="5" width="1" height="30"/>
            <rect x="135" y="5" width="2" height="30"/>
            <rect x="139" y="5" width="3" height="30"/>
            <rect x="144" y="5" width="1" height="30"/>
            <rect x="148" y="5" width="2" height="30"/>
            <rect x="152" y="5" width="4" height="30"/>
            <rect x="158" y="5" width="1" height="30"/>
            <rect x="161" y="5" width="3" height="30"/>
            <rect x="166" y="5" width="1" height="30"/>
            <rect x="169" y="5" width="2" height="30"/>
            <rect x="174" y="5" width="4" height="30"/>
            <rect x="180" y="5" width="2" height="30"/>
            <rect x="184" y="5" width="1" height="30"/>
            <rect x="187" y="5" width="3" height="30"/>
            
            <text x="100" y="42" font-size="6" text-anchor="middle" font-family="monospace" letter-spacing="1">
              TF-<?= hash('crc32b', (string)$order['id']) ?>-<?= $order['id'] ?>
            </text>
          </g>
        </svg>
      </div>

      <p style="font-size: 11px; color: var(--muted); margin-top: 15px;">Core Calorie Advisor Secure Ledger Integration. Built for legendary warriors.</p>
    </div>

  </article>

  <!-- Print Actions -->
  <div class="invoice-actions">
    <button onclick="window.print()" class="btn btn-fire font-bold uppercase tracking-wider" style="cursor:pointer">
      🖨 Print Invoice
    </button>
    <a href="<?= url('pages/shop.php') ?>" class="btn btn-ghost font-bold uppercase tracking-wider">
      🛍 Keep Shopping
    </a>
  </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

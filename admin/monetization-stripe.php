<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('admin');

/* Handle settings POST.
   NOTE: csrf_verify*() returns void, so it must NEVER be used as a condition —
   `POST && csrf_verify_json()` evaluates to false always and silently disabled
   this entire handler. Use csrf_verify() (plain-text 419) because this is an
   HTML form POST that redirects, not a JSON endpoint. */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    foreach (['commission_rate','top_rated_commission_rate','top_rated_threshold'] as $k) {
        if (isset($_POST[$k])) set_setting($k, $_POST[$k]);
    }
    /* Page-local key on purpose: $_SESSION['flash'] belongs to flash_render()
       (functions.php:136) which iterates it as a list of [type, msg] pairs.
       Assigning a plain string there makes header.php:207 foreach over a string. */
    $_SESSION['mon_flash'] = 'Settings saved!';
    header('Location: ' . url('admin/monetization-stripe.php'));
    exit;
}

$txns = []; try { $txns = db()->query('SELECT t.*, u.name FROM transactions t JOIN users u ON u.id = t.user_id ORDER BY t.created_at DESC LIMIT 50')->fetchAll(); } catch (Throwable $e) {}
$payouts = []; try { $payouts = db()->query("SELECT pr.*, u.name FROM payout_requests pr JOIN users u ON u.id = pr.user_id ORDER BY pr.created_at DESC LIMIT 20")->fetchAll(); } catch (Throwable $e) {}

$portal = 'admin'; $pageTitle = 'Monetization';
include dirname(__DIR__) . '/includes/header.php';
?>
<section class="cca-hero cca-hero--gradient"><div class="cca-hero__overlay"></div><div class="cca-hero__content"><div class="cca-hero__text">
  <div class="cca-hero__breadcrumb"><a href="<?= url('admin/dashboard.php') ?>">Dashboard</a> <span class="sep">›</span> Monetization</div>
  <h1 class="cca-hero__title">Monetization & <span class="grad">Stripe</span></h1>
  <p class="cca-hero__subtitle">Configure commission rates, manage payouts, and review webhook transaction logs.</p>
</div></div></section>
<?= portal_nav('admin') ?>
<div class="cca-page-container">
  <?php if (isset($_SESSION['mon_flash'])): ?><div class="cca-alert cca-alert-success" style="margin-bottom:16px"><?= e($_SESSION['mon_flash']) ?></div><?php unset($_SESSION['mon_flash']); endif; ?>

  <div class="cca-grid-2" style="margin-bottom:24px">
    <div class="cca-card">
      <h3 class="cca-h3">⚙️ Commission Settings</h3>
      <form method="post" style="margin-top:12px">
        <?= csrf_field() ?>
        <div style="margin-bottom:12px">
          <label style="font-size:12px; color:var(--text-3)">Default Commission Rate (%)</label>
          <input type="number" name="commission_rate" value="<?= e(get_setting('commission_rate','20')) ?>" min="0" max="100" step="1" style="width:100%; padding:10px 14px; border-radius:10px; border:1px solid var(--border); background:var(--surface); color:var(--text-1); margin-top:6px">
        </div>
        <div style="margin-bottom:12px">
          <label style="font-size:12px; color:var(--text-3)">Top-Rated Commission Rate (%)</label>
          <input type="number" name="top_rated_commission_rate" value="<?= e(get_setting('top_rated_commission_rate','10')) ?>" min="0" max="100" step="1" style="width:100%; padding:10px 14px; border-radius:10px; border:1px solid var(--border); background:var(--surface); color:var(--text-1); margin-top:6px">
        </div>
        <div style="margin-bottom:16px">
          <label style="font-size:12px; color:var(--text-3)">Top-Rated Threshold (★)</label>
          <input type="number" name="top_rated_threshold" value="<?= e(get_setting('top_rated_threshold','4.5')) ?>" min="1" max="5" step="0.1" style="width:100%; padding:10px 14px; border-radius:10px; border:1px solid var(--border); background:var(--surface); color:var(--text-1); margin-top:6px">
        </div>
        <button class="cca-btn cca-btn-primary" style="width:100%; justify-content:center" type="submit">💾 Save Settings</button>
      </form>
    </div>

    <div class="cca-card">
      <h3 class="cca-h3">💸 Payout Requests</h3>
      <div class="cca-table-wrap" style="margin-top:12px"><table class="cca-table">
        <tr><th>User</th><th>Amount</th><th>Status</th><th>Date</th></tr>
        <?php if (!$payouts): ?><tr><td colspan="4" style="color:var(--text-3)">No payouts requested.</td></tr><?php endif; ?>
        <?php foreach ($payouts as $p): ?>
        <tr><td><?= e($p['name']) ?></td><td><b>$<?= number_format((float)$p['amount'], 2) ?></b></td>
          <td><span class="cca-badge <?= $p['status'] === 'paid' ? 'cca-badge-success' : ($p['status'] === 'rejected' ? 'cca-badge-danger' : 'cca-badge-warning') ?>"><?= ucfirst($p['status']) ?></span></td>
          <td style="font-size:12px"><?= date('M d', strtotime($p['created_at'])) ?></td></tr>
        <?php endforeach; ?>
      </table></div>
    </div>
  </div>

  <div class="cca-card">
    <h3 class="cca-h3">📄 Transaction Log</h3>
    <div class="cca-table-wrap"><table class="cca-table">
      <tr><th>Date</th><th>User</th><th>Type</th><th>Amount</th><th>Description</th><th>Status</th></tr>
      <?php if (!$txns): ?><tr><td colspan="6" style="color:var(--text-3)">No transactions.</td></tr><?php endif; ?>
      <?php foreach ($txns as $t): ?>
      <tr><td style="font-size:12px"><?= date('M d, H:i', strtotime($t['created_at'])) ?></td><td><?= e($t['name']) ?></td>
        <td><span class="cca-badge"><?= ucfirst($t['type']) ?></span></td>
        <td style="font-weight:700">$<?= number_format((float)$t['amount'], 2) ?></td>
        <td style="color:var(--text-3)"><?= e($t['description'] ?? '—') ?></td>
        <td><span class="cca-badge <?= $t['status'] === 'completed' ? 'cca-badge-success' : 'cca-badge-warning' ?>"><?= ucfirst($t['status']) ?></span></td></tr>
      <?php endforeach; ?>
    </table></div>
  </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

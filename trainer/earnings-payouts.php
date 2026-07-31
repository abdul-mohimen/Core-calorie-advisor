<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('trainer', 'admin');
$uid = $_SESSION['user']['id'];
$wallet = get_wallet_balance($uid);
$txns = []; try { $st = db()->prepare('SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 20'); $st->execute([$uid]); $txns = $st->fetchAll(); } catch (Throwable $e) {}
$payouts = []; try { $st = db()->prepare('SELECT * FROM payout_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 10'); $st->execute([$uid]); $payouts = $st->fetchAll(); } catch (Throwable $e) {}
$commRate = calculate_commission(100, $uid);

$portal = 'trainer'; $pageTitle = 'Earnings & Payouts';
include dirname(__DIR__) . '/includes/header.php';
?>
<section class="cca-hero cca-hero--gradient"><div class="cca-hero__overlay"></div><div class="cca-hero__content"><div class="cca-hero__text">
  <div class="cca-hero__breadcrumb"><a href="<?= url('trainer/dashboard.php') ?>">Dashboard</a> <span class="sep">›</span> Earnings</div>
  <h1 class="cca-hero__title">Earnings & <span class="grad">Payouts</span></h1>
  <p class="cca-hero__subtitle">Track your revenue, commission breakdown, and request payouts.</p>
</div>
<div class="cca-hero__glass">
  <div class="cca-hero__glass-title">Financials</div>
  <div class="cca-hero__kpi-row"><span class="cca-hero__kpi-icon c1">💰</span><div><div class="cca-hero__kpi-val">$<?= number_format($wallet, 2) ?></div><div class="cca-hero__kpi-lbl">Available Balance</div></div></div>
  <div class="cca-hero__kpi-row"><span class="cca-hero__kpi-icon c2">📊</span><div><div class="cca-hero__kpi-val"><?= $commRate['rate'] ?>%</div><div class="cca-hero__kpi-lbl">Platform Commission</div></div></div>
  <div class="cca-hero__kpi-row"><span class="cca-hero__kpi-icon c3">💸</span><div><div class="cca-hero__kpi-val"><?= count($txns) ?></div><div class="cca-hero__kpi-lbl">Total Transactions</div></div></div>
</div></div></section>
<?= portal_nav('trainer') ?>
<div class="cca-page-container">
  <div class="cca-grid-2" style="margin-bottom:24px">
    <div class="cca-card" style="text-align:center; padding:32px">
      <div style="font-family:var(--font-disp); font-size:42px; color:var(--success-text)">$<?= number_format($wallet, 2) ?></div>
      <div style="font-size:14px; color:var(--text-3); margin-top:8px">Available for Payout</div>
      <button class="cca-btn cca-btn-primary" style="margin-top:20px">💸 Request Payout</button>
    </div>
    <div class="cca-card">
      <h3 class="cca-h3">📊 Commission Breakdown</h3>
      <p style="font-size:13px; color:var(--text-3); margin:8px 0 16px">Example: For a $100 client payment:</p>
      <div class="cca-table-wrap"><table class="cca-table">
        <tr><td>Client Pays</td><td><b>$100.00</b></td></tr>
        <tr><td>Platform Fee (<?= $commRate['rate'] ?>%)</td><td style="color:var(--danger-text)">-$<?= number_format($commRate['commission'], 2) ?></td></tr>
        <tr><td><b>Your Earnings</b></td><td style="color:var(--success-text)"><b>$<?= number_format($commRate['payout'], 2) ?></b></td></tr>
      </table></div>
    </div>
  </div>
  <div class="cca-card">
    <h3 class="cca-h3">📄 Transaction History</h3>
    <div class="cca-table-wrap"><table class="cca-table">
      <tr><th>Date</th><th>Type</th><th>Description</th><th>Amount</th><th>Status</th></tr>
      <?php if (!$txns): ?><tr><td colspan="5" style="color:var(--text-3)">No transactions yet.</td></tr><?php endif; ?>
      <?php foreach ($txns as $t): ?>
      <tr><td style="font-size:13px"><?= date('M d', strtotime($t['created_at'])) ?></td>
        <td><span class="cca-badge"><?= ucfirst($t['type']) ?></span></td>
        <td><?= e($t['description'] ?? '—') ?></td>
        <td style="font-weight:700; color:<?= $t['type'] === 'payout' ? 'var(--danger)' : 'var(--success)' ?>">$<?= number_format((float)$t['amount'], 2) ?></td>
        <td><span class="cca-badge <?= $t['status'] === 'completed' ? 'cca-badge-success' : 'cca-badge-warning' ?>"><?= ucfirst($t['status']) ?></span></td></tr>
      <?php endforeach; ?>
    </table></div>
  </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

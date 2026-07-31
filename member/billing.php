<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('member', 'admin');
$uid = $_SESSION['user']['id'];

$plan = $_SESSION['user']['plan'] ?? 'free';
$isPro = is_pro();

/* ---- Subscription info ---- */
$sub = null;
try {
    $st = db()->prepare('SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
    $st->execute([$uid]); $sub = $st->fetch();
} catch (Throwable $e) {}

/* ---- Transaction history ---- */
$txns = [];
try {
    $st = db()->prepare('SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 20');
    $st->execute([$uid]); $txns = $st->fetchAll();
} catch (Throwable $e) {}

$portal    = 'member';
$pageTitle = 'Billing & Subscription';
include dirname(__DIR__) . '/includes/header.php';

$navLinks = [
    ['Dashboard',        url('member/dashboard.php'),        nav_icon('dashboard')],
    ['Workouts',         url('member/workouts.php'),          nav_icon('dumbbell')],
    ['Diet Planner',     url('member/diet-planner.php'),      nav_icon('diet')],
    ['Trainers & Docs',  url('member/trainers-doctors.php'),  nav_icon('users')],
    ['Appointments',     url('member/appointments.php'),      nav_icon('calendar')],
    ['Billing',          url('member/billing.php'),           nav_icon('billing')],
];
?>

<section class="cca-hero cca-hero--gradient">
  <div class="cca-hero__overlay"></div>
  <div class="cca-hero__content">
    <div class="cca-hero__text">
      <div class="cca-hero__breadcrumb"><a href="<?= url('member/dashboard.php') ?>">Dashboard</a> <span class="sep">›</span> Billing</div>
      <h1 class="cca-hero__title">Billing & <span class="grad">Subscription</span></h1>
      <p class="cca-hero__subtitle">Manage your plan, view invoices, and upgrade to unlock premium features.</p>
    </div>
    <div class="cca-hero__glass">
      <div class="cca-hero__glass-title">Current Plan</div>
      <div style="text-align:center; padding:16px 0">
        <div style="font-family:var(--font-disp); font-size:32px; color:<?= $isPro ? 'var(--success)' : 'var(--text-2)' ?>; text-transform:uppercase"><?= e(strtoupper($plan)) ?></div>
        <div style="font-size:13px; color:var(--text-3); margin-top:4px"><?= $isPro ? 'All features unlocked' : 'Upgrade to unlock Pro features' ?></div>
        <?php if (!$isPro): ?>
          <a class="cca-btn cca-btn-primary" style="margin-top:16px" href="<?= url('pages/pricing.php') ?>">💎 Upgrade to Pro</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?= portal_nav('member', $navLinks) ?>

<div class="cca-page-container">
  <!-- ═══ SUBSCRIPTION DETAILS ═══ -->
  <div class="cca-grid-2" style="margin-bottom:24px">
    <div class="cca-card">
      <h3 class="cca-h3">📋 Subscription Details</h3>
      <div class="cca-table-wrap" style="margin-top:12px"><table class="cca-table">
        <tr><td style="color:var(--text-3)">Plan</td><td><b><?= e(strtoupper($plan)) ?></b></td></tr>
        <tr><td style="color:var(--text-3)">Status</td><td><span class="cca-badge <?= $isPro ? 'cca-badge-success' : 'cca-badge-warning' ?>"><?= $isPro ? 'Active' : 'Free Tier' ?></span></td></tr>
        <?php if ($sub): ?>
        <tr><td style="color:var(--text-3)">Provider</td><td><?= e(ucfirst($sub['provider'])) ?></td></tr>
        <tr><td style="color:var(--text-3)">Renews</td><td><?= $sub['current_period_end'] ? date('M d, Y', strtotime($sub['current_period_end'])) : '—' ?></td></tr>
        <?php endif; ?>
      </table></div>
    </div>

    <div class="cca-card">
      <h3 class="cca-h3">💳 Payment Methods</h3>
      <div style="display:flex; align-items:center; gap:12px; margin-top:16px; padding:16px; border-radius:12px; background:var(--surface); border:1px solid var(--border)">
        <!-- The Visa gradient below is intentionally a literal: it reproduces the
             card issuer's own brand colours, which must not shift with our theme. -->
        <div style="width:48px; height:32px; border-radius:6px; background:linear-gradient(135deg, #1A1F71, #2E77BD); display:flex; align-items:center; justify-content:center; color:var(--on-media); font-size:12px; font-weight:700">VISA</div>
        <div><div style="font-size:14px">•••• •••• •••• 4242</div><div style="font-size:12px; color:var(--text-3)">Expires 12/28</div></div>
      </div>
      <p style="font-size:12px; color:var(--text-3); margin-top:12px">Managed via Stripe. Update payment methods in your Stripe customer portal.</p>
    </div>
  </div>

  <!-- ═══ TRANSACTION HISTORY ═══ -->
  <div class="cca-card">
    <h3 class="cca-h3">📄 Transaction History</h3>
    <div class="cca-table-wrap"><table class="cca-table">
      <tr><th>Date</th><th>Type</th><th>Description</th><th>Amount</th><th>Status</th></tr>
      <?php if (!$txns): ?><tr><td colspan="5" style="color:var(--text-3)">No transactions yet.</td></tr><?php endif; ?>
      <?php foreach ($txns as $t): ?>
      <tr>
        <td style="font-size:13px"><?= date('M d, Y', strtotime($t['created_at'])) ?></td>
        <td><span class="cca-badge" style="font-size:11px"><?= ucfirst($t['type']) ?></span></td>
        <td><?= e($t['description'] ?? '—') ?></td>
        <td style="font-weight:700; color:<?= $t['type'] === 'refund' ? 'var(--danger)' : 'var(--success)' ?>">$<?= number_format((float)$t['amount'], 2) ?></td>
        <td><span class="cca-badge <?= $t['status'] === 'completed' ? 'cca-badge-success' : ($t['status'] === 'failed' ? 'cca-badge-danger' : 'cca-badge-warning') ?>"><?= ucfirst($t['status']) ?></span></td>
      </tr>
      <?php endforeach; ?>
    </table></div>
  </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

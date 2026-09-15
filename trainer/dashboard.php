<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('trainer', 'admin');
$uid = $_SESSION['user']['id'];

$pend = db()->prepare("SELECT a.*, u.name member_name FROM appointments a JOIN users u ON u.id = a.member_id WHERE a.trainer_id = ? AND a.status = 'pending' ORDER BY a.appt_date");
$pend->execute([$uid]); $pending = $pend->fetchAll();
$acc = db()->prepare("SELECT COUNT(*) c FROM appointments WHERE trainer_id = ? AND status = 'accepted'");
$acc->execute([$uid]); $accepted = (int)$acc->fetch()['c'];
$cli = db()->prepare('SELECT COUNT(DISTINCT member_id) c FROM appointments WHERE trainer_id = ?');
$cli->execute([$uid]); $clients = (int)$cli->fetch()['c'];
$rev = db()->prepare('SELECT AVG(rating) r, COUNT(*) c FROM reviews WHERE trainer_id = ?');
$rev->execute([$uid]); $rv = $rev->fetch();
$wallet = get_wallet_balance($uid);

$portal = 'trainer'; $pageTitle = 'Trainer Dashboard';
include dirname(__DIR__) . '/includes/header.php';
?>
<section class="cca-hero cca-hero--video cca-hero--particles">
  <video class="cca-hero__video" autoplay muted loop playsinline preload="metadata" poster="<?= url('assets/images/hero-poster.jpg') ?>">
    <source src="<?= url('assets/videos/hero-loop.mp4') ?>" type="video/mp4" media="(min-width: 768px)">
    <source src="<?= url('assets/videos/hero-loop-mobile.mp4') ?>" type="video/mp4" media="(max-width: 767px)">
  </video>
  <canvas id="heroParticles"></canvas>
  <div class="cca-hero__overlay"></div>
  <div class="cca-hero__content">
    <div class="cca-hero__text">
      <div class="cca-hero__breadcrumb"><a href="<?= url('index.php') ?>">Home</a> <span class="sep">›</span> Trainer Portal</div>
      <div class="cca-hero__badge cca-hero__badge--live"><span class="dot"></span> Active Coach</div>
      <h1 class="cca-hero__title">Coach <span class="grad">Dashboard</span> 💪</h1>
      <p class="cca-hero__subtitle">Review incoming bookings, manage client routines, track revenue, and grow your reputation.</p>
    </div>
    <div class="cca-hero__glass">
      <div class="cca-hero__glass-title">Quick Stats</div>
      <div class="cca-hero__kpi-row"><span class="cca-hero__kpi-icon c1">👥</span><div><div class="cca-hero__kpi-val"><?= $clients ?></div><div class="cca-hero__kpi-lbl">Active Clients</div></div></div>
      <div class="cca-hero__kpi-row"><span class="cca-hero__kpi-icon c2">📥</span><div><div class="cca-hero__kpi-val"><?= count($pending) ?></div><div class="cca-hero__kpi-lbl">Pending Requests</div></div></div>
      <div class="cca-hero__kpi-row"><span class="cca-hero__kpi-icon c4">⭐</span><div><div class="cca-hero__kpi-val"><?= $rv['r'] ? number_format((float)$rv['r'], 1) : '—' ?></div><div class="cca-hero__kpi-lbl">Rating (<?= (int)$rv['c'] ?> reviews)</div></div></div>
      <div class="cca-hero__kpi-row"><span class="cca-hero__kpi-icon c3">💰</span><div><div class="cca-hero__kpi-val">$<?= number_format($wallet, 2) ?></div><div class="cca-hero__kpi-lbl">Wallet Balance</div></div></div>
    </div>
  </div>
</section>
<?= portal_nav('trainer') ?>
<?= portal_dashboard_hub('trainer') ?>
<div class="cca-page-container">
  <div class="cca-grid-4">
    <div class="cca-metric"><span class="cca-metric-icon c1">👥</span><div><div class="cca-metric-val"><?= $clients ?></div><div class="cca-metric-lbl">My Clients</div></div></div>
    <div class="cca-metric"><span class="cca-metric-icon c2">📥</span><div><div class="cca-metric-val"><?= count($pending) ?></div><div class="cca-metric-lbl">Pending</div></div></div>
    <div class="cca-metric"><span class="cca-metric-icon c3">✅</span><div><div class="cca-metric-val"><?= $accepted ?></div><div class="cca-metric-lbl">Accepted</div></div></div>
    <div class="cca-metric"><span class="cca-metric-icon c4">★</span><div><div class="cca-metric-val"><?= $rv['r'] ? round((float)$rv['r'], 1) : '—' ?></div><div class="cca-metric-lbl">Rating</div></div></div>
  </div>
  <h3 class="cca-h2" style="margin-top:20px; margin-bottom:14px">📥 Appointment Requests</h3>
  <div class="cca-card">
    <div class="cca-table-wrap"><table class="cca-table"><tr><th>Member</th><th>Goal</th><th>Date</th><th>Action</th></tr>
      <?php if (!$pending): ?><tr><td colspan="4" style="color:var(--text-3)">All requests handled ✔</td></tr><?php endif; ?>
      <?php foreach ($pending as $a): ?>
      <tr>
        <td><?= e($a['member_name']) ?></td><td><?= e($a['goal']) ?></td>
        <td><?= date('d M, g A', strtotime($a['appt_date'])) ?></td>
        <td>
          <form method="post" action="<?= url('api/appointment-action.php') ?>" style="display:inline"><?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="action" value="accept">
            <button class="cca-btn cca-btn-primary" type="submit" style="padding:4px 12px; font-size:12px; height:auto; min-height:auto;">✔ Accept</button></form>
          <form method="post" action="<?= url('api/appointment-action.php') ?>" style="display:inline"><?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="action" value="reject">
            <button class="cca-btn cca-btn-ghost" type="submit" style="padding:4px 12px; font-size:12px; height:auto; min-height:auto;">✕ Reject</button></form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table></div>
  </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

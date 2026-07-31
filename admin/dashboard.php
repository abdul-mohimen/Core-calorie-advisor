<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('admin');
$uid = $_SESSION['user']['id'];

$uct = db()->query('SELECT COUNT(*) c FROM users')->fetch(); $totalUsers = (int)$uct['c'];
$mct = db()->query("SELECT COUNT(*) c FROM users WHERE role='member'")->fetch(); $totalMembers = (int)$mct['c'];
$tct = db()->query("SELECT COUNT(*) c FROM users WHERE role='trainer'")->fetch(); $totalTrainers = (int)$tct['c'];
$dct = db()->query("SELECT COUNT(*) c FROM users WHERE role='doctor'")->fetch(); $totalDoctors = (int)$dct['c'];
$act = db()->query('SELECT COUNT(*) c FROM appointments')->fetch(); $totalAppts = (int)$act['c'];
$rev = 0; try { $rev = (float)db()->query("SELECT COALESCE(SUM(amount),0) s FROM transactions WHERE type='commission' AND status='completed'")->fetch()['s']; } catch (Throwable $e) {}
$pendPay = 0; try { $pendPay = (int)db()->query("SELECT COUNT(*) c FROM payout_requests WHERE status='pending'")->fetch()['c']; } catch (Throwable $e) {}
$pendRev = 0; try { $pendRev = (int)db()->query("SELECT COUNT(*) c FROM reviews WHERE status='pending'")->fetch()['c']; } catch (Throwable $e) {}

$portal = 'admin'; $pageTitle = 'Admin Dashboard';
include dirname(__DIR__) . '/includes/header.php';
$navLinks = [['Dashboard',url('admin/dashboard.php'),nav_icon('dashboard')],['Users',url('admin/user-management.php'),nav_icon('users')],['Monetization',url('admin/monetization-stripe.php'),nav_icon('money')],['Appointments',url('admin/appointments-master.php'),nav_icon('calendar')],['Exercises',url('admin/exercise-library-admin.php'),nav_icon('library')],['Reviews',url('admin/reviews-moderation.php'),nav_icon('moderate')]];
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
      <div class="cca-hero__breadcrumb"><a href="<?= url('index.php') ?>">Home</a> <span class="sep">›</span> Admin Control Panel</div>
      <div class="cca-hero__badge cca-hero__badge--admin"><span class="dot"></span> System Admin</div>
      <h1 class="cca-hero__title">Platform <span class="grad">Command Center</span> ⚡</h1>
      <p class="cca-hero__subtitle">Monitor gross revenue, manage users, configure monetization, and moderate content across the entire platform.</p>
    </div>
    <div class="cca-hero__glass">
      <div class="cca-hero__glass-title">Platform Health</div>
      <div class="cca-hero__kpi-row"><span class="cca-hero__kpi-icon c1">👥</span><div><div class="cca-hero__kpi-val"><?= $totalUsers ?></div><div class="cca-hero__kpi-lbl">Total Users</div></div></div>
      <div class="cca-hero__kpi-row"><span class="cca-hero__kpi-icon c4">💰</span><div><div class="cca-hero__kpi-val">$<?= number_format($rev, 2) ?></div><div class="cca-hero__kpi-lbl">Commission Revenue</div></div></div>
      <div class="cca-hero__kpi-row"><span class="cca-hero__kpi-icon c2">📅</span><div><div class="cca-hero__kpi-val"><?= $totalAppts ?></div><div class="cca-hero__kpi-lbl">Total Appointments</div></div></div>
      <div class="cca-hero__kpi-row"><span class="cca-hero__kpi-icon c3">⏳</span><div><div class="cca-hero__kpi-val"><?= $pendPay ?></div><div class="cca-hero__kpi-lbl">Pending Payouts</div></div></div>
    </div>
  </div>
</section>
<?= portal_nav('admin', $navLinks) ?>
<div class="cca-page-container">
  <div class="cca-grid-4">
    <div class="cca-metric"><span class="cca-metric-icon c1">👥</span><div><div class="cca-metric-val"><?= $totalMembers ?></div><div class="cca-metric-lbl">Members</div></div></div>
    <div class="cca-metric"><span class="cca-metric-icon c2">🏋️</span><div><div class="cca-metric-val"><?= $totalTrainers ?></div><div class="cca-metric-lbl">Trainers</div></div></div>
    <div class="cca-metric"><span class="cca-metric-icon c3">🩺</span><div><div class="cca-metric-val"><?= $totalDoctors ?></div><div class="cca-metric-lbl">Doctors</div></div></div>
    <div class="cca-metric"><span class="cca-metric-icon c4">⭐</span><div><div class="cca-metric-val"><?= $pendRev ?></div><div class="cca-metric-lbl">Reviews to Moderate</div></div></div>
  </div>

  <div class="cca-grid-2" style="margin-top:24px">
    <div class="cca-card"><h3>⚡ Quick Actions</h3>
      <div style="display:flex; flex-direction:column; gap:10px; margin-top:12px">
        <a class="cca-btn cca-btn-primary" href="<?= url('admin/user-management.php') ?>" style="justify-content:center">👥 Manage Users</a>
        <a class="cca-btn cca-btn-secondary" href="<?= url('admin/monetization-stripe.php') ?>" style="justify-content:center">💰 Monetization Settings</a>
        <a class="cca-btn cca-btn-ghost" href="<?= url('admin/reviews-moderation.php') ?>" style="justify-content:center">⭐ Moderate Reviews (<?= $pendRev ?>)</a>
        <a class="cca-btn cca-btn-ghost" href="<?= url('admin/exercise-library-admin.php') ?>" style="justify-content:center">📚 Exercise Library</a>
      </div>
    </div>
    <div class="cca-card"><h3>📊 Revenue Breakdown</h3>
      <div class="cca-table-wrap" style="margin-top:12px"><table class="cca-table">
        <tr><td>Commission Revenue</td><td style="color:var(--success-text)"><b>$<?= number_format($rev, 2) ?></b></td></tr>
        <tr><td>Pending Payouts</td><td><span class="cca-badge cca-badge-warning"><?= $pendPay ?></span></td></tr>
        <tr><td>Commission Rate</td><td><b><?= get_setting('commission_rate', '20') ?>%</b></td></tr>
        <tr><td>Top-rated Rate</td><td><b><?= get_setting('top_rated_commission_rate', '10') ?>%</b> (≥<?= get_setting('top_rated_threshold', '4.5') ?>★)</td></tr>
      </table></div>
    </div>
  </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

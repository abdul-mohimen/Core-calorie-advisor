<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('member', 'admin');
$uid = $_SESSION['user']['id'];

$appts = db()->prepare('SELECT a.*, u.name provider_name, u.role provider_role FROM appointments a JOIN users u ON u.id = a.trainer_id WHERE a.member_id = ? ORDER BY a.appt_date DESC');
$appts->execute([$uid]); $appointments = $appts->fetchAll();

$upcoming = array_filter($appointments, fn($a) => strtotime($a['appt_date']) >= time() && $a['status'] !== 'rejected');
$past = array_filter($appointments, fn($a) => strtotime($a['appt_date']) < time() || $a['status'] === 'rejected');

$portal    = 'member';
$pageTitle = 'My Appointments';
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
      <div class="cca-hero__breadcrumb"><a href="<?= url('member/dashboard.php') ?>">Dashboard</a> <span class="sep">›</span> Appointments</div>
      <h1 class="cca-hero__title">My <span class="grad">Appointments</span></h1>
      <p class="cca-hero__subtitle">View upcoming sessions, track booking status, and manage your calendar.</p>
      <div class="cca-hero__actions">
        <a class="cca-btn cca-btn-primary" href="<?= url('member/trainers-doctors.php') ?>">📅 Book New Session</a>
      </div>
    </div>
    <div class="cca-hero__glass">
      <div class="cca-hero__glass-title">Quick Stats</div>
      <div class="cca-hero__kpi-row"><span class="cca-hero__kpi-icon c1">📅</span><div><div class="cca-hero__kpi-val"><?= count($upcoming) ?></div><div class="cca-hero__kpi-lbl">Upcoming</div></div></div>
      <div class="cca-hero__kpi-row"><span class="cca-hero__kpi-icon c2">✅</span><div><div class="cca-hero__kpi-val"><?= count(array_filter($appointments, fn($a) => $a['status'] === 'accepted')) ?></div><div class="cca-hero__kpi-lbl">Confirmed</div></div></div>
      <div class="cca-hero__kpi-row"><span class="cca-hero__kpi-icon c3">📊</span><div><div class="cca-hero__kpi-val"><?= count($appointments) ?></div><div class="cca-hero__kpi-lbl">Total Booked</div></div></div>
    </div>
  </div>
</section>

<?= portal_nav('member', $navLinks) ?>

<div class="cca-page-container">
  <?php if ($upcoming): ?>
  <h3 class="cca-h2" style="margin-bottom:16px">📅 Upcoming Sessions</h3>
  <div class="cca-card" style="margin-bottom:24px">
    <div class="cca-table-wrap"><table class="cca-table">
      <tr><th>Provider</th><th>Type</th><th>Goal</th><th>Date</th><th>Status</th></tr>
      <?php foreach ($upcoming as $a): ?>
      <tr>
        <td><b><?= e($a['provider_name']) ?></b></td>
        <td><span class="cca-badge" style="background:color-mix(in srgb, var(--info) 10%, transparent); color:var(--info-text)"><?= e(ucfirst($a['provider_role'])) ?></span></td>
        <td><?= e($a['goal']) ?></td>
        <td><?= date('M d, Y · g:i A', strtotime($a['appt_date'])) ?></td>
        <td><span class="cca-badge <?= $a['status'] === 'accepted' ? 'cca-badge-success' : 'cca-badge-warning' ?>"><?= ucfirst($a['status']) ?></span></td>
      </tr>
      <?php endforeach; ?>
    </table></div>
  </div>
  <?php endif; ?>

  <h3 class="cca-h2" style="margin-bottom:16px">📋 All Appointments</h3>
  <div class="cca-card">
    <div class="cca-table-wrap"><table class="cca-table">
      <tr><th>Provider</th><th>Goal</th><th>Date</th><th>Status</th></tr>
      <?php if (!$appointments): ?><tr><td colspan="4" style="color:var(--text-3)">No appointments booked yet. Browse trainers and doctors to get started.</td></tr><?php endif; ?>
      <?php foreach ($appointments as $a): ?>
      <tr>
        <td><?= e($a['provider_name']) ?></td>
        <td><?= e($a['goal']) ?></td>
        <td><?= date('M d, g:i A', strtotime($a['appt_date'])) ?></td>
        <td><span class="cca-badge <?= $a['status'] === 'accepted' ? 'cca-badge-success' : ($a['status'] === 'rejected' ? 'cca-badge-danger' : 'cca-badge-warning') ?>"><?= ucfirst($a['status']) ?></span></td>
      </tr>
      <?php endforeach; ?>
    </table></div>
  </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

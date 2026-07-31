<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('doctor', 'admin');
$uid = $_SESSION['user']['id'];

$patients = db()->query("SELECT id, name, disease FROM users WHERE role = 'patient' ORDER BY name")->fetchAll();
$consults = db()->prepare("SELECT a.*, u.name patient_name FROM appointments a JOIN users u ON u.id = a.member_id WHERE a.trainer_id = ? AND a.status = 'pending' ORDER BY a.appt_date");
$consults->execute([$uid]); $pending = $consults->fetchAll();
$allAppts = db()->prepare("SELECT COUNT(*) c FROM appointments WHERE trainer_id = ?"); $allAppts->execute([$uid]);
$totalConsults = (int)$allAppts->fetch()['c'];
$wallet = get_wallet_balance($uid);

$portal = 'doctor'; $pageTitle = 'Doctor Dashboard';
include dirname(__DIR__) . '/includes/header.php';
$navLinks = [['Dashboard',url('doctor/dashboard.php'),nav_icon('dashboard')],['Patient Queue',url('doctor/patient-queue.php'),nav_icon('queue')],['Consultations',url('doctor/consultations.php'),nav_icon('stethoscope')],['Financials',url('doctor/financials.php'),nav_icon('money')],['Ratings',url('doctor/ratings.php'),nav_icon('star')]];
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
      <div class="cca-hero__breadcrumb"><a href="<?= url('index.php') ?>">Home</a> <span class="sep">›</span> Doctor Portal</div>
      <div class="cca-hero__badge cca-hero__badge--medical"><span class="dot"></span> Clinical Command</div>
      <h1 class="cca-hero__title">Medical <span class="grad">Dashboard</span> 🩺</h1>
      <p class="cca-hero__subtitle">Review patient consult requests, manage prescriptions, and track clinical earnings.</p>
    </div>
    <div class="cca-hero__glass">
      <div class="cca-hero__glass-title">Today's Overview</div>
      <div class="cca-hero__kpi-row"><span class="cca-hero__kpi-icon c1">👥</span><div><div class="cca-hero__kpi-val"><?= count($patients) ?></div><div class="cca-hero__kpi-lbl">Total Patients</div></div></div>
      <div class="cca-hero__kpi-row"><span class="cca-hero__kpi-icon c2">📥</span><div><div class="cca-hero__kpi-val"><?= count($pending) ?></div><div class="cca-hero__kpi-lbl">Pending Consults</div></div></div>
      <div class="cca-hero__kpi-row"><span class="cca-hero__kpi-icon c3">📊</span><div><div class="cca-hero__kpi-val"><?= $totalConsults ?></div><div class="cca-hero__kpi-lbl">Total Consultations</div></div></div>
      <div class="cca-hero__kpi-row"><span class="cca-hero__kpi-icon c4">💰</span><div><div class="cca-hero__kpi-val">$<?= number_format($wallet, 2) ?></div><div class="cca-hero__kpi-lbl">Wallet Balance</div></div></div>
    </div>
  </div>
</section>
<?= portal_nav('doctor', $navLinks) ?>
<div class="cca-page-container">
  <div class="cca-grid-4">
    <div class="cca-metric"><span class="cca-metric-icon c1">👥</span><div><div class="cca-metric-val"><?= count($patients) ?></div><div class="cca-metric-lbl">Patients</div></div></div>
    <div class="cca-metric"><span class="cca-metric-icon c2">📥</span><div><div class="cca-metric-val"><?= count($pending) ?></div><div class="cca-metric-lbl">Pending</div></div></div>
    <div class="cca-metric"><span class="cca-metric-icon c3">📊</span><div><div class="cca-metric-val"><?= $totalConsults ?></div><div class="cca-metric-lbl">Total Consults</div></div></div>
    <div class="cca-metric"><span class="cca-metric-icon c4">💰</span><div><div class="cca-metric-val">$<?= number_format($wallet, 2) ?></div><div class="cca-metric-lbl">Earnings</div></div></div>
  </div>

  <?php if ($pending): ?>
  <h3 class="cca-h2" style="margin-top:20px; margin-bottom:14px">📥 Consult Requests</h3>
  <div class="cca-card">
    <div class="cca-table-wrap"><table class="cca-table"><tr><th>Patient</th><th>Reason</th><th>Date</th><th>Action</th></tr>
      <?php foreach ($pending as $a): ?>
      <tr><td><?= e($a['patient_name']) ?></td><td><?= e($a['goal']) ?></td><td><?= date('d M, g A', strtotime($a['appt_date'])) ?></td>
        <td>
          <form method="post" action="<?= url('api/appointment-action.php') ?>" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="action" value="accept"><button class="cca-btn cca-btn-primary" style="padding:4px 12px; font-size:12px; height:auto; min-height:auto;">✔ Accept</button></form>
          <form method="post" action="<?= url('api/appointment-action.php') ?>" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="action" value="reject"><button class="cca-btn cca-btn-ghost" style="padding:4px 12px; font-size:12px; height:auto; min-height:auto;">✕ Reject</button></form>
        </td></tr>
      <?php endforeach; ?>
    </table></div>
  </div>
  <?php endif; ?>

  <div class="cca-grid-2" style="margin-top:24px">
    <div class="cca-card"><h3>👥 My Patients</h3>
      <div class="cca-table-wrap"><table class="cca-table"><tr><th>Patient</th><th>Condition</th></tr>
        <?php foreach ($patients as $p): ?>
        <tr><td><?= e($p['name']) ?></td><td><span class="cca-badge cca-badge-warning"><?= e($p['disease'] ?? '—') ?></span></td></tr>
        <?php endforeach; ?>
      </table></div></div>
    <div class="cca-card"><h3>💊 Quick Actions</h3>
      <div style="display:flex; flex-direction:column; gap:10px; margin-top:12px">
        <a class="cca-btn cca-btn-primary" href="<?= url('doctor/patient-queue.php') ?>" style="justify-content:center">📋 Patient Queue</a>
        <a class="cca-btn cca-btn-secondary" href="<?= url('doctor/consultations.php') ?>" style="justify-content:center">📊 Consultation History</a>
        <a class="cca-btn cca-btn-ghost" href="<?= url('doctor/financials.php') ?>" style="justify-content:center">💰 View Earnings</a>
      </div>
    </div>
  </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

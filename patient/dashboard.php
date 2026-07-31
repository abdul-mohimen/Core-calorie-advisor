<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('patient', 'admin');
$uid = $_SESSION['user']['id'];

$me = db()->prepare('SELECT disease FROM users WHERE id = ?');
$me->execute([$uid]); $disease = $me->fetch()['disease'] ?? null;

/* Vitals */
$vitals = [];
try {
    $vst = db()->prepare('SELECT * FROM vitals_logs WHERE user_id = ? ORDER BY logged_at DESC LIMIT 10');
    $vst->execute([$uid]); $vitals = $vst->fetchAll();
} catch (Throwable $e) {}
$latestVitals = $vitals[0] ?? null;

/* Prescriptions */
$prescriptions = [];
try {
    $pst = db()->prepare('SELECT p.*, u.name doctor_name FROM prescriptions p JOIN users u ON u.id = p.doctor_id WHERE p.patient_id = ? ORDER BY p.created_at DESC');
    $pst->execute([$uid]); $prescriptions = $pst->fetchAll();
} catch (Throwable $e) {}

/* Appointments */
$appts = db()->prepare('SELECT a.*, u.name doctor_name FROM appointments a JOIN users u ON u.id = a.trainer_id WHERE a.member_id = ? ORDER BY a.appt_date DESC LIMIT 5');
$appts->execute([$uid]); $appointments = $appts->fetchAll();

/* Safe plans */
$safe = [];
if ($disease) {
    $st = db()->prepare('SELECT w.id, w.name, w.tag, w.image, d.precautions, u.name doctor FROM disease_plans dp JOIN diseases d ON d.id = dp.disease_id JOIN workouts w ON w.id = dp.workout_id LEFT JOIN users u ON u.id = dp.approved_by WHERE d.name = ?');
    $st->execute([$disease]); $safe = $st->fetchAll();
}

/* Reminders */
$rem = db()->prepare('SELECT * FROM reminders WHERE user_id = ? ORDER BY id');
$rem->execute([$uid]); $reminders = $rem->fetchAll();

$portal    = 'patient';
$pageTitle = 'Patient Dashboard';
include dirname(__DIR__) . '/includes/header.php';

$navLinks = [
    ['Dashboard',      url('patient/dashboard.php'),      nav_icon('dashboard')],
    ['Doctors',        url('patient/doctors.php'),         nav_icon('stethoscope')],
    ['Prescriptions',  url('patient/prescriptions.php'),   nav_icon('prescription')],
    ['Vitals Log',     url('patient/vitals-log.php'),      nav_icon('chart')],
    ['Appointments',   url('patient/appointments.php'),    nav_icon('calendar')],
];
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
      <div class="cca-hero__breadcrumb"><a href="<?= url('index.php') ?>">Home</a> <span class="sep">›</span> Patient Portal</div>
      <div class="cca-hero__badge cca-hero__badge--medical"><span class="dot"></span> Medical Dashboard</div>
      <h1 class="cca-hero__title">Safe <span class="grad">Training Zone</span> ❤️</h1>
      <p class="cca-hero__subtitle">Your personalized medical fitness hub. Track vitals, manage prescriptions, and consult with certified doctors.</p>
      <div class="cca-hero__actions">
        <a class="cca-btn cca-btn-primary" href="<?= url('patient/doctors.php') ?>" style="background:var(--info)">🩺 Book Doctor Consult</a>
        <a class="cca-btn cca-btn-ghost" href="<?= url('patient/vitals-log.php') ?>">📊 Log Vitals</a>
      </div>
    </div>
    <div class="cca-hero__glass">
      <div class="cca-hero__glass-title">Health Vitals Summary</div>
      <div class="cca-hero__kpi-row"><span class="cca-hero__kpi-icon c1">❤️</span><div><div class="cca-hero__kpi-val"><?= $latestVitals ? $latestVitals['heart_rate'] . ' bpm' : '—' ?></div><div class="cca-hero__kpi-lbl">Heart Rate</div></div></div>
      <div class="cca-hero__kpi-row"><span class="cca-hero__kpi-icon c3">🩸</span><div><div class="cca-hero__kpi-val"><?= $latestVitals ? $latestVitals['blood_sugar'] . ' mg/dL' : '—' ?></div><div class="cca-hero__kpi-lbl">Blood Sugar</div></div></div>
      <div class="cca-hero__kpi-row"><span class="cca-hero__kpi-icon c2">⚖️</span><div><div class="cca-hero__kpi-val"><?= $latestVitals ? $latestVitals['weight_kg'] . ' kg' : '—' ?></div><div class="cca-hero__kpi-lbl">Weight</div></div></div>
      <div class="cca-hero__kpi-row"><span class="cca-hero__kpi-icon c4">📈</span><div><div class="cca-hero__kpi-val"><?= $latestVitals ? number_format((float)$latestVitals['bmi'], 1) : '—' ?></div><div class="cca-hero__kpi-lbl">BMI</div></div></div>
    </div>
  </div>
</section>

<?= portal_nav('patient', $navLinks) ?>

<div class="cca-page-container">
  <div class="cca-grid-4">
    <div class="cca-metric"><span class="cca-metric-icon c1">❤️</span><div><div class="cca-metric-val" style="font-size:18px"><?= e($disease ?? 'None') ?></div><div class="cca-metric-lbl">My Condition</div></div></div>
    <div class="cca-metric"><span class="cca-metric-icon c2">✔</span><div><div class="cca-metric-val"><?= count($safe) ?></div><div class="cca-metric-lbl">Safe Plans</div></div></div>
    <div class="cca-metric"><span class="cca-metric-icon c3">💊</span><div><div class="cca-metric-val"><?= count($prescriptions) ?></div><div class="cca-metric-lbl">Prescriptions</div></div></div>
    <div class="cca-metric"><span class="cca-metric-icon c4">🩺</span><div><div class="cca-metric-val"><?= count($appointments) ?></div><div class="cca-metric-lbl">Consults</div></div></div>
  </div>

  <?php if ($safe): ?>
  <h3 class="cca-h2" style="margin-bottom:14px">✅ Doctor-Approved Safe Workouts</h3>
  <div class="cca-grid-3" style="margin-bottom:30px">
    <?php foreach ($safe as $s): ?>
    <div class="cca-card" style="padding:0; overflow:hidden; position:relative">
      <span class="cca-badge cca-badge-success" style="position:absolute; top:10px; right:10px; z-index:10">SAFE</span>
      <div style="width:100%; height:160px"><img src="<?= e($s['image']) ?>" alt="<?= e($s['name']) ?>" loading="lazy" style="width:100%; height:100%; object-fit:cover"></div>
      <div style="padding:16px">
        <h3 style="font-size:16px; margin-bottom:4px"><?= e($s['name']) ?></h3>
        <p style="font-size:13px; color:var(--text-2)"><?= e($s['tag']) ?></p>
        <p style="color:var(--danger-text); font-size:13px; margin-top:10px">⚠️ <?= e($s['precautions']) ?></p>
        <p style="color:var(--text-3); font-size:12px; margin-top:6px">Approved by: <?= e($s['doctor'] ?? 'Pending') ?></p>
        <a class="cca-btn cca-btn-primary" style="margin-top:14px; width:100%; justify-content:center" href="<?= url('pages/player.php?id=' . $s['id']) ?>">▶ Start Safely</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="cca-grid-2">
    <div class="cca-card"><h3>⏰ Today's Reminders</h3>
      <div class="cca-table-wrap"><table class="cca-table"><tr><th>Task</th><th>Time</th><th>Status</th></tr>
        <?php if (!$reminders): ?><tr><td colspan="3" style="color:var(--text-3)">No reminders set.</td></tr><?php endif; ?>
        <?php foreach ($reminders as $r): ?>
        <tr><td><?= e($r['title']) ?></td><td><?= e($r['remind_time']) ?></td>
          <td><span class="cca-badge <?= $r['done'] ? 'cca-badge-success' : 'cca-badge-warning' ?>"><?= $r['done'] ? 'Done' : 'Pending' ?></span></td></tr>
        <?php endforeach; ?>
      </table></div></div>
    <div class="cca-card"><h3>🩺 Recent Consultations</h3>
      <div class="cca-table-wrap"><table class="cca-table"><tr><th>Doctor</th><th>Reason</th><th>Status</th></tr>
        <?php if (!$appointments): ?><tr><td colspan="3" style="color:var(--text-3)">No consultations yet.</td></tr><?php endif; ?>
        <?php foreach ($appointments as $a): ?>
        <tr><td><?= e($a['doctor_name']) ?></td><td><?= e($a['goal']) ?></td>
          <td><span class="cca-badge <?= $a['status'] === 'accepted' ? 'cca-badge-success' : ($a['status'] === 'rejected' ? 'cca-badge-danger' : 'cca-badge-warning') ?>"><?= ucfirst($a['status']) ?></span></td></tr>
        <?php endforeach; ?>
      </table></div></div>
  </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

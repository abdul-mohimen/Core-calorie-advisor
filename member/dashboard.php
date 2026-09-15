<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('member', 'admin');
$uid = $_SESSION['user']['id'];

/* ---- Stats ---- */
$stats = db()->prepare('SELECT COUNT(*) done, COALESCE(SUM(kcal_burned),0) kcal, COALESCE(SUM(duration_sec),0) secs FROM workout_logs WHERE user_id = ?');
$stats->execute([$uid]); $s = $stats->fetch();
$week = db()->prepare('SELECT COUNT(*) c FROM workout_logs WHERE user_id = ? AND completed_at > NOW() - INTERVAL 7 DAY');
$week->execute([$uid]); $wk = $week->fetch();
$appts = db()->prepare('SELECT a.*, u.name trainer_name FROM appointments a JOIN users u ON u.id = a.trainer_id WHERE a.member_id = ? ORDER BY a.appt_date DESC LIMIT 5');
$appts->execute([$uid]); $appointments = $appts->fetchAll();
$logs = db()->prepare('SELECT wl.*, w.name AS workout_name FROM workout_logs wl JOIN workouts w ON w.id = wl.workout_id WHERE wl.user_id = ? ORDER BY wl.completed_at DESC LIMIT 6');
$logs->execute([$uid]); $recent = $logs->fetchAll();

/* last-7-days daily kcal for chart */
$cst = db()->prepare("SELECT DATE(completed_at) d, COALESCE(SUM(kcal_burned),0) k FROM workout_logs WHERE user_id=? AND completed_at > NOW() - INTERVAL 7 DAY GROUP BY DATE(completed_at)");
$cst->execute([$uid]);
$byDay = [];
foreach ($cst as $r) { $byDay[$r['d']] = (int)$r['k']; }
$chartRows = [];
for ($i = 6; $i >= 0; $i--) { $day = date('Y-m-d', strtotime("-$i day")); $chartRows[] = ['lbl' => date('D', strtotime($day)), 'k' => $byDay[$day] ?? 0]; }
$maxChart = 1; foreach ($chartRows as $cr) { $maxChart = max($maxChart, $cr['k']); }

$trainers = db()->query('SELECT tp.*, u.name FROM trainer_profiles tp JOIN users u ON u.id = tp.user_id ORDER BY tp.rating DESC LIMIT 4')->fetchAll();

/* Derived metrics */
$totalKcal = (int)$s['kcal']; $totalDone = (int)$s['done']; $weekDone = (int)$wk['c'];
$activeMin = (int)round(((int)$s['secs']) / 60);
$weekGoal  = 5; $weekPct = $weekGoal ? min($weekDone / $weekGoal, 1) : 0;
$ringCirc  = 2 * M_PI * 52; $ringOffset = $ringCirc * (1 - $weekPct);
$avgKcal   = $totalDone ? round($totalKcal / $totalDone) : 0;

/* Streak calculation */
$streakDays = 0;
$streakSt = db()->prepare("SELECT DISTINCT DATE(completed_at) d FROM workout_logs WHERE user_id = ? ORDER BY d DESC LIMIT 30");
$streakSt->execute([$uid]);
$streakDates = $streakSt->fetchAll(PDO::FETCH_COLUMN);
$checkDate = date('Y-m-d');
foreach ($streakDates as $d) {
    if ($d === $checkDate) { $streakDays++; $checkDate = date('Y-m-d', strtotime("$checkDate -1 day")); }
    else break;
}

$portal    = 'member';
$pageTitle = 'Member Dashboard';
include dirname(__DIR__) . '/includes/header.php';

/* Portal sub-navigation */
?>
<svg width="0" height="0" style="position:absolute"><defs>
  <linearGradient id="heroRingGrad" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#FF3D00"/><stop offset="1" stop-color="#FFC02E"/></linearGradient>
</defs></svg>

<!-- ═══ CINEMATIC HERO ═══ -->
<section class="cca-hero cca-hero--video cca-hero--particles">
  <video class="cca-hero__video" autoplay muted loop playsinline preload="metadata" poster="<?= url('assets/images/hero-poster.jpg') ?>">
    <source src="<?= url('assets/videos/hero-loop.mp4') ?>" type="video/mp4" media="(min-width: 768px)">
    <source src="<?= url('assets/videos/hero-loop-mobile.mp4') ?>" type="video/mp4" media="(max-width: 767px)">
  </video>
  <canvas id="heroParticles"></canvas>
  <div class="cca-hero__overlay"></div>
  <div class="cca-hero__content">
    <div class="cca-hero__text">
      <div class="cca-hero__breadcrumb">
        <a href="<?= url('index.php') ?>">Home</a> <span class="sep">›</span> Member Portal
      </div>
      <div class="cca-hero__badge cca-hero__badge--live"><span class="dot"></span> Active Member</div>
      <h1 class="cca-hero__title">Welcome back, <span class="grad"><?= e(explode(' ', $_SESSION['user']['name'])[0]) ?></span> 👋</h1>
      <p class="cca-hero__subtitle">Your personal fitness command center. Track workouts, manage nutrition, and connect with elite trainers and doctors.</p>
      <div class="cca-hero__actions">
        <a class="cca-btn cca-btn-primary" href="<?= url('pages/workouts.php') ?>">🔥 Start Workout</a>
        <a class="cca-btn cca-btn-secondary" href="<?= url('pages/scanner-food.php') ?>">📷 Food Scan</a>
        <a class="cca-btn cca-btn-ghost" href="<?= url('pages/scanner-body.php') ?>">🏋️ Body Scan</a>
      </div>
    </div>
    <div class="cca-hero__glass">
      <div class="cca-hero__glass-title">Weekly Progress</div>
      <div class="cca-hero__ring">
        <svg viewBox="0 0 120 120">
          <circle class="cca-hero__ring-track" cx="60" cy="60" r="52"/>
          <circle class="cca-hero__ring-fill" cx="60" cy="60" r="52"
            stroke-dasharray="<?= round($ringCirc, 1) ?>" stroke-dashoffset="<?= round($ringOffset, 1) ?>"/>
        </svg>
        <div class="cca-hero__ring-center">
          <span class="cca-hero__ring-val"><?= $weekDone ?>/<?= $weekGoal ?></span>
          <span class="cca-hero__ring-lbl">This Week</span>
        </div>
      </div>
      <div class="cca-hero__kpi-row">
        <span class="cca-hero__kpi-icon c1">🔥</span>
        <div><div class="cca-hero__kpi-val"><span data-count-to="<?= $totalKcal ?>"><?= number_format($totalKcal) ?></span></div><div class="cca-hero__kpi-lbl">Total Calories Burned</div></div>
      </div>
      <div class="cca-hero__kpi-row">
        <span class="cca-hero__kpi-icon c2">🏆</span>
        <div><div class="cca-hero__kpi-val"><span data-count-to="<?= $streakDays ?>"><?= $streakDays ?></span> Day<?= $streakDays !== 1 ? 's' : '' ?></div><div class="cca-hero__kpi-lbl">Active Streak</div></div>
      </div>
      <div class="cca-hero__kpi-row">
        <span class="cca-hero__kpi-icon c3">⚡</span>
        <div><div class="cca-hero__kpi-val"><span data-count-to="<?= $avgKcal ?>"><?= $avgKcal ?></span> kcal</div><div class="cca-hero__kpi-lbl">Avg per Workout</div></div>
      </div>
    </div>
  </div>
</section>

<?= portal_nav('member') ?>
<?= portal_dashboard_hub('member') ?>

<div class="cca-page-container">
  <!-- ═══ STAT CARDS ═══ -->
  <div class="cca-grid-4">
    <div class="cca-metric"><span class="cca-metric-icon c1">🔥</span><div><div class="cca-metric-val"><span data-count-to="<?= $totalKcal ?>"><?= number_format($totalKcal) ?></span></div><div class="cca-metric-lbl">Calories Burned</div></div></div>
    <div class="cca-metric"><span class="cca-metric-icon c2">🏋️</span><div><div class="cca-metric-val"><span data-count-to="<?= $totalDone ?>"><?= $totalDone ?></span></div><div class="cca-metric-lbl">Workouts Done</div></div></div>
    <div class="cca-metric"><span class="cca-metric-icon c3">⏱</span><div><div class="cca-metric-val"><span data-count-to="<?= $activeMin ?>"><?= $activeMin ?></span> min</div><div class="cca-metric-lbl">Active Time</div></div></div>
    <div class="cca-metric"><span class="cca-metric-icon c4">🔥</span><div><div class="cca-metric-val"><span data-count-to="<?= $streakDays ?>"><?= $streakDays ?></span></div><div class="cca-metric-lbl">Day Streak</div></div></div>
  </div>

  <!-- ═══ ACTIVITY CHART + RECENT WORKOUTS ═══ -->
  <div class="cca-grid-2">
    <div class="cca-card">
      <h3 class="cca-h3">📊 7-Day Activity</h3>
      <div style="display:flex; align-items:flex-end; gap:8px; height:140px; padding-top:16px">
        <?php foreach ($chartRows as $cr): ?>
          <?php $pct = $maxChart > 0 ? ($cr['k'] / $maxChart * 100) : 0; ?>
          <div style="flex:1; text-align:center">
            <div style="background: linear-gradient(to top, var(--primary-hot), var(--gold)); border-radius:6px 6px 0 0; height:<?= max($pct, 4) ?>%; min-height:4px; transition:height 0.6s ease; opacity:<?= $cr['k'] > 0 ? 1 : 0.2 ?>"></div>
            <div style="font-size:10px; color:var(--text-3); margin-top:6px; font-family:var(--font-tech)"><?= $cr['lbl'] ?></div>
            <div style="font-size:11px; color:var(--text-2); font-weight:600"><?= $cr['k'] ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="cca-card">
      <h3 class="cca-h3">🕐 Recent Workouts</h3>
      <div class="cca-table-wrap"><table class="cca-table">
        <tr><th>Workout</th><th>Kcal</th><th>When</th></tr>
        <?php if (!$recent): ?><tr><td colspan="3" style="color:var(--text-3)">No workouts yet — start your first session!</td></tr><?php endif; ?>
        <?php foreach ($recent as $r): ?>
        <tr>
          <td><?= e($r['workout_name']) ?></td>
          <td><span class="cca-badge cca-badge-warning"><?= (int)$r['kcal_burned'] ?> kcal</span></td>
          <td style="color:var(--text-3); font-size:13px"><?= date('M d, g:i A', strtotime($r['completed_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </table></div>
    </div>
  </div>

  <!-- ═══ UPCOMING APPOINTMENTS ═══ -->
  <div class="cca-card" style="margin-top:24px">
    <h3 class="cca-h3">📅 Upcoming Appointments</h3>
    <div class="cca-table-wrap"><table class="cca-table">
      <tr><th>Trainer / Doctor</th><th>Goal</th><th>Date</th><th>Status</th></tr>
      <?php if (!$appointments): ?><tr><td colspan="4" style="color:var(--text-3)">No appointments booked yet.</td></tr><?php endif; ?>
      <?php foreach ($appointments as $a): ?>
      <tr>
        <td><?= e($a['trainer_name']) ?></td>
        <td><?= e($a['goal']) ?></td>
        <td><?= e(date('M d, g:i A', strtotime($a['appt_date']))) ?></td>
        <td><span class="cca-badge <?= $a['status'] === 'accepted' ? 'cca-badge-success' : ($a['status'] === 'rejected' ? 'cca-badge-danger' : 'cca-badge-warning') ?>"><?= e(ucfirst($a['status'])) ?></span></td>
      </tr>
      <?php endforeach; ?>
    </table></div>
  </div>

  <!-- ═══ TOP TRAINERS ═══ -->
  <?php if ($trainers): ?>
  <h3 class="cca-h2" style="margin-top:32px; margin-bottom:16px">⭐ Top Rated Trainers</h3>
  <div class="cca-grid-4">
    <?php foreach ($trainers as $t): ?>
    <div class="cca-card" style="text-align:center; padding:24px 16px">
      <img src="<?= e($t['photo']) ?>" alt="<?= e($t['name']) ?>" loading="lazy" style="width:72px; height:72px; border-radius:50%; object-fit:cover; margin:0 auto 12px; border:2px solid var(--primary); box-shadow:0 0 16px color-mix(in srgb, var(--primary) 20%, transparent)">
      <h4 style="font-size:15px; margin-bottom:4px"><?= e($t['name']) ?></h4>
      <p style="font-size:12px; color:var(--text-3); margin-bottom:8px"><?= e($t['specialty']) ?></p>
      <span class="cca-badge cca-badge-warning">⭐ <?= number_format((float)$t['rating'], 1) ?></span>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

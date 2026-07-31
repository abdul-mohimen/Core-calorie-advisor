<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('member', 'admin');
$uid = $_SESSION['user']['id'];

/* ---- Fetch food database ---- */
$foods = db()->query('SELECT * FROM foods ORDER BY name')->fetchAll();

/* ---- Today's food log ---- */
$todayLog = db()->prepare("SELECT * FROM food_logs WHERE user_id = ? AND DATE(logged_at) = CURDATE() ORDER BY logged_at DESC");
$todayLog->execute([$uid]); $todayFoods = $todayLog->fetchAll();

$todayKcal = 0; $todayProtein = 0; $todayCarbs = 0; $todayFats = 0;
foreach ($todayFoods as $f) {
    $todayKcal += (int)$f['kcal'];
    $todayProtein += (float)$f['protein'];
    $todayCarbs += (float)$f['carbs'];
    $todayFats += (float)$f['fats'];
}

$dailyGoal = 2200; // default daily calorie target
$remaining = max(0, $dailyGoal - $todayKcal);

$portal    = 'member';
$pageTitle = 'Diet Planner';
include dirname(__DIR__) . '/includes/header.php';

?>

<section class="cca-hero cca-hero--gradient">
  <div class="cca-hero__overlay"></div>
  <div class="cca-hero__content">
    <div class="cca-hero__text">
      <div class="cca-hero__breadcrumb">
        <a href="<?= url('member/dashboard.php') ?>">Dashboard</a> <span class="sep">›</span> Diet Planner
      </div>
      <h1 class="cca-hero__title"><span class="grad">Diet</span> Planner</h1>
      <p class="cca-hero__subtitle">Track your daily intake, generate meal plans, and export grocery lists. AI-powered nutrition at your fingertips.</p>
    </div>
    <div class="cca-hero__glass">
      <div class="cca-hero__glass-title">Today's Intake</div>
      <div class="cca-hero__kpi-row"><span class="cca-hero__kpi-icon c1">🔥</span><div><div class="cca-hero__kpi-val"><?= number_format($todayKcal) ?></div><div class="cca-hero__kpi-lbl"><?= $remaining ?> kcal remaining</div></div></div>
      <div class="cca-hero__kpi-row"><span class="cca-hero__kpi-icon c2">💪</span><div><div class="cca-hero__kpi-val"><?= number_format($todayProtein, 1) ?>g</div><div class="cca-hero__kpi-lbl">Protein</div></div></div>
      <div class="cca-hero__kpi-row"><span class="cca-hero__kpi-icon c3">🍞</span><div><div class="cca-hero__kpi-val"><?= number_format($todayCarbs, 1) ?>g</div><div class="cca-hero__kpi-lbl">Carbs</div></div></div>
      <div class="cca-hero__kpi-row"><span class="cca-hero__kpi-icon c4">🥑</span><div><div class="cca-hero__kpi-val"><?= number_format($todayFats, 1) ?>g</div><div class="cca-hero__kpi-lbl">Fats</div></div></div>
    </div>
  </div>
</section>

<?= portal_nav('member') ?>

<div class="cca-page-container">
  <!-- ═══ MACRO SLIDERS ═══ -->
  <div class="cca-card" style="margin-bottom:24px">
    <h3 class="cca-h3">🎯 Daily Calorie Goal</h3>
    <div style="display:flex; gap:24px; align-items:center; margin-top:16px; flex-wrap:wrap">
      <div style="flex:1; min-width:200px">
        <label style="font-size:12px; color:var(--text-3); text-transform:uppercase; letter-spacing:1px">Target Calories</label>
        <input type="range" min="1200" max="4000" step="50" value="<?= $dailyGoal ?>" id="calGoal" style="width:100%; margin-top:8px; accent-color:var(--primary)">
        <div style="display:flex; justify-content:space-between; font-size:11px; color:var(--text-3)"><span>1200</span><span id="calGoalVal"><?= $dailyGoal ?> kcal</span><span>4000</span></div>
      </div>
      <div style="text-align:center">
        <div style="font-family:var(--font-disp); font-size:36px; color:var(--primary-text)"><?= number_format($todayKcal) ?></div>
        <div style="font-size:12px; color:var(--text-3)">consumed today</div>
      </div>
    </div>
  </div>

  <!-- ═══ FOOD DATABASE BROWSER ═══ -->
  <h3 class="cca-h2" style="margin-bottom:16px">🥗 Food Database</h3>
  <div class="cca-grid-4">
    <?php foreach ($foods as $f): ?>
    <div class="cca-card" style="padding:0; overflow:hidden">
      <div style="height:120px; overflow:hidden"><img src="<?= e($f['image']) ?>" alt="<?= e($f['name']) ?>" loading="lazy" style="width:100%; height:100%; object-fit:cover"></div>
      <div style="padding:14px">
        <h4 style="font-size:14px; margin-bottom:4px"><?= e($f['name']) ?></h4>
        <div style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:10px">
          <span class="cca-badge cca-badge-warning" style="font-size:10px"><?= $f['kcal'] ?> kcal</span>
          <span class="cca-badge" style="font-size:10px; background:color-mix(in srgb, var(--success) 10%, transparent); color:var(--success-text)">P: <?= $f['protein'] ?>g</span>
        </div>
        <p style="font-size:11px; color:var(--text-3)"><?= e($f['serving']) ?></p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ═══ TODAY'S LOG ═══ -->
  <div class="cca-card" style="margin-top:24px">
    <h3 class="cca-h3">📋 Today's Food Log</h3>
    <div class="cca-table-wrap"><table class="cca-table">
      <tr><th>Food</th><th>Calories</th><th>Protein</th><th>Carbs</th><th>Fats</th><th>Time</th></tr>
      <?php if (!$todayFoods): ?><tr><td colspan="6" style="color:var(--text-3)">No food logged today. Scan or add food to start tracking.</td></tr><?php endif; ?>
      <?php foreach ($todayFoods as $f): ?>
      <tr>
        <td><?= e($f['food_name']) ?></td>
        <td><b><?= $f['kcal'] ?></b></td>
        <td><?= $f['protein'] ?>g</td>
        <td><?= $f['carbs'] ?>g</td>
        <td><?= $f['fats'] ?>g</td>
        <td style="color:var(--text-3); font-size:12px"><?= date('g:i A', strtotime($f['logged_at'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </table></div>
  </div>
</div>

<script>
document.getElementById('calGoal')?.addEventListener('input', e => {
  document.getElementById('calGoalVal').textContent = e.target.value + ' kcal';
});
</script>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('trainer', 'admin');
$uid = $_SESSION['user']['id'];
$exercises = db()->query('SELECT * FROM exercises ORDER BY name')->fetchAll();
$workouts = db()->query('SELECT id, name FROM workouts ORDER BY name')->fetchAll();

$portal = 'trainer'; $pageTitle = 'Routine Creator';
include dirname(__DIR__) . '/includes/header.php';
$navLinks = [['Dashboard',url('trainer/dashboard.php'),nav_icon('dashboard')],['Client Roster',url('trainer/client-roster.php'),nav_icon('users')],['Routine Creator',url('trainer/routine-creator.php'),nav_icon('dumbbell')],['Earnings',url('trainer/earnings-payouts.php'),nav_icon('money')],['Reviews',url('trainer/reviews-ratings.php'),nav_icon('star')]];
?>
<section class="cca-hero cca-hero--gradient"><div class="cca-hero__overlay"></div><div class="cca-hero__content"><div class="cca-hero__text">
  <div class="cca-hero__breadcrumb"><a href="<?= url('trainer/dashboard.php') ?>">Dashboard</a> <span class="sep">›</span> Routine Creator</div>
  <div class="cca-hero__badge cca-hero__badge--pro"><span class="dot"></span> 3D Builder</div>
  <h1 class="cca-hero__title">Routine <span class="grad">Creator</span></h1>
  <p class="cca-hero__subtitle">Build custom workout routines for your clients using the exercise library. Drag and drop to reorder.</p>
</div></div></section>
<?= portal_nav('trainer', $navLinks) ?>
<div class="cca-page-container">
  <div class="cca-grid-2" style="grid-template-columns: 1fr 360px; align-items:start; gap:24px">
    <!-- Exercise Library -->
    <div>
      <div class="cca-card" style="margin-bottom:16px">
        <h3 class="cca-h3">📚 Exercise Library</h3>
        <p style="font-size:13px; color:var(--text-3); margin:8px 0 16px">Click exercises to add them to your routine builder.</p>
        <div class="cca-grid-3">
          <?php foreach ($exercises as $ex): ?>
          <div class="cca-card exercise-item" style="cursor:pointer; padding:14px; text-align:center; transition:all 0.2s" data-name="<?= e($ex['name']) ?>" data-secs="<?= $ex['seconds'] ?>" data-kcal="<?= $ex['kcal'] ?>" data-anim="<?= e($ex['anim_mode']) ?>" data-muscle="<?= e($ex['target_muscle'] ?? 'full-body') ?>">
            <div style="font-size:15px; margin-bottom:4px"><?= e($ex['name']) ?></div>
            <div style="font-size:11px; color:var(--text-3)"><?= $ex['seconds'] ?>s · <?= $ex['kcal'] ?> kcal</div>
            <span class="cca-badge" style="font-size:10px; margin-top:6px"><?= e($ex['target_muscle'] ?? $ex['anim_mode']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Routine Builder (sidebar) -->
    <div class="cca-card" style="position:sticky; top:90px">
      <h3 class="cca-h3">🔨 Routine Builder</h3>
      <input type="text" id="routineName" placeholder="Routine name..." style="width:100%; padding:10px 14px; border-radius:10px; border:1px solid var(--border); background:var(--surface); color:var(--text-1); margin:12px 0; font-size:14px">
      <div id="routineList" style="min-height:120px; border:1px dashed var(--border); border-radius:10px; padding:12px; margin-bottom:12px">
        <p style="font-size:13px; color:var(--text-3); text-align:center">Click exercises to add them here</p>
      </div>
      <div style="display:flex; justify-content:space-between; font-size:13px; color:var(--text-2); margin-bottom:12px">
        <span>Exercises: <b id="exCount">0</b></span>
        <span>Total kcal: <b id="exKcal">0</b></span>
      </div>
      <button class="cca-btn cca-btn-primary" style="width:100%; justify-content:center" id="saveRoutine">💾 Save Routine</button>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const list = document.getElementById('routineList');
  const countEl = document.getElementById('exCount');
  const kcalEl = document.getElementById('exKcal');
  let items = [];

  document.querySelectorAll('.exercise-item').forEach(el => {
    el.addEventListener('click', () => {
      const item = { name: el.dataset.name, secs: +el.dataset.secs, kcal: +el.dataset.kcal, anim: el.dataset.anim };
      items.push(item);
      renderList();
    });
  });

  function renderList() {
    if (!items.length) { list.innerHTML = '<p style="font-size:13px; color:var(--text-3); text-align:center">Click exercises to add them here</p>'; countEl.textContent = '0'; kcalEl.textContent = '0'; return; }
    list.innerHTML = items.map((it, i) => `<div style="display:flex; justify-content:space-between; align-items:center; padding:8px 10px; background:var(--surface); border-radius:8px; margin-bottom:6px; font-size:13px">
      <span>${i + 1}. ${it.name} <span style="color:var(--text-3)">(${it.secs}s)</span></span>
      <button onclick="removeItem(${i})" style="background:none; border:none; color:var(--danger-text); cursor:pointer; font-size:16px">×</button>
    </div>`).join('');
    countEl.textContent = items.length;
    kcalEl.textContent = items.reduce((s, it) => s + it.kcal, 0);
  }

  window.removeItem = (i) => { items.splice(i, 1); renderList(); };

  document.getElementById('saveRoutine')?.addEventListener('click', () => {
    const name = document.getElementById('routineName')?.value?.trim();
    if (!name) return alert('Enter a routine name');
    if (!items.length) return alert('Add exercises first');
    alert('Routine "' + name + '" saved with ' + items.length + ' exercises!');
  });
});
</script>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('member', 'admin');
$uid = $_SESSION['user']['id'];

/* ---- Fetch workouts with muscle filter ---- */
$muscleFilter = get('muscle', '');
$sql = 'SELECT w.*, GROUP_CONCAT(DISTINCT e.target_muscle) AS muscles
        FROM workouts w LEFT JOIN exercises e ON e.workout_id = w.id';
$params = [];
if ($muscleFilter) {
    $sql .= ' WHERE e.target_muscle = ?';
    $params[] = $muscleFilter;
}
$sql .= ' GROUP BY w.id ORDER BY w.name';
$st = db()->prepare($sql);
$st->execute($params);
$workouts = $st->fetchAll();

/* User plan check */
$isPro = is_pro();

$portal    = 'member';
$pageTitle = '3D Workout Studio';
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

<section class="cca-hero cca-hero--particles">
  <canvas id="heroParticles"></canvas>
  <div class="cca-hero__overlay"></div>
  <div class="cca-hero__content">
    <div class="cca-hero__text">
      <div class="cca-hero__breadcrumb">
        <a href="<?= url('member/dashboard.php') ?>">Dashboard</a> <span class="sep">›</span> Workouts
      </div>
      <div class="cca-hero__badge cca-hero__badge--pro"><span class="dot"></span> 3D Studio</div>
      <h1 class="cca-hero__title">3D <span class="grad">Workout Studio</span></h1>
      <p class="cca-hero__subtitle">Interactive 3D exercise player with real-time animation controls. Select a muscle group to filter workouts instantly.</p>
    </div>
    <div class="cca-hero__glass" id="hero3d" style="min-height:280px; display:flex; align-items:center; justify-content:center">
      <span class="h3d-tag" style="position:absolute; top:12px; left:16px; font-size:11px; opacity:0.7">● Live 3D Coach</span>
    </div>
  </div>
</section>

<?= portal_nav('member', $navLinks) ?>

<div class="cca-page-container">
  <div class="cca-grid-2" style="grid-template-columns: 260px 1fr; align-items:start; gap:24px">
    <!-- Muscle Map Sidebar -->
    <div class="cca-card" style="position:sticky; top:90px">
      <h3 class="cca-h3" style="margin-bottom:16px">🎯 Target Muscle</h3>
      <div id="muscleMap"></div>
    </div>

    <!-- Workout Grid -->
    <div>
      <div class="cca-grid-3" id="workoutGrid">
        <?php foreach ($workouts as $w): ?>
        <div class="cca-card" style="padding:0; overflow:hidden; position:relative" data-muscles="<?= e($w['muscles'] ?? '') ?>">
          <?php if (!$w['is_free'] && !$isPro): ?>
            <span class="cca-badge cca-badge-warning" style="position:absolute; top:10px; right:10px; z-index:10">PRO</span>
          <?php endif; ?>
          <div style="width:100%; height:160px; overflow:hidden">
            <img src="<?= e($w['image']) ?>" alt="<?= e($w['name']) ?>" loading="lazy" style="width:100%; height:100%; object-fit:cover; transition:transform 0.4s ease">
          </div>
          <div style="padding:16px">
            <h4 style="font-size:15px; margin-bottom:4px"><?= e($w['name']) ?></h4>
            <p style="font-size:12px; color:var(--text-3); margin-bottom:4px"><?= e($w['tag']) ?></p>
            <p style="font-size:13px; color:var(--text-2); margin-bottom:14px; line-height:1.5"><?= e(substr($w['description'], 0, 90)) ?>…</p>
            <?php if ($w['is_free'] || $isPro): ?>
              <a class="cca-btn cca-btn-primary" style="width:100%; justify-content:center" href="<?= url('pages/player.php?id=' . $w['id']) ?>">▶ Start Workout</a>
            <?php else: ?>
              <a class="cca-btn cca-btn-secondary" style="width:100%; justify-content:center" href="<?= url('pages/pricing.php') ?>">🔒 Unlock Pro</a>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php if (!$workouts): ?>
        <div class="cca-empty-state">No workouts found for this muscle group.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="<?= asset('js/cca-voice.js') ?>"></script>
<script src="<?= asset('js/cca-coach.js') ?>"></script>
<script src="<?= asset('js/cca-wardrobe.js') ?>"></script>
<script src="<?= asset('js/titan3d.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  /* Init 3D Coach in hero */
  if (typeof initThree === 'function') initThree('hero3d');

  /* Init Muscle Map */
  if (typeof CCAMuscleMap !== 'undefined') {
    CCAMuscleMap.init('#muscleMap', {
      onSelect: (muscle) => {
        const cards = document.querySelectorAll('#workoutGrid > .cca-card');
        cards.forEach(card => {
          const muscles = (card.dataset.muscles || '').split(',');
          if (!muscle || muscles.includes(muscle) || muscles.includes('full-body')) {
            card.style.display = '';
          } else {
            card.style.display = 'none';
          }
        });
        /* Trigger 3D highlight */
        if (typeof highlightMuscle === 'function') highlightMuscle(muscle);
      }
    });
  }
});
</script>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

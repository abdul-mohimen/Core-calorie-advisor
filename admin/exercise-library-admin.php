<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('admin');
$exercises = db()->query('SELECT e.*, w.name AS workout_name FROM exercises e LEFT JOIN workouts w ON w.id = e.workout_id ORDER BY e.name')->fetchAll();

$portal = 'admin'; $pageTitle = 'Exercise Library';
include dirname(__DIR__) . '/includes/header.php';
?>
<section class="cca-hero cca-hero--gradient"><div class="cca-hero__overlay"></div><div class="cca-hero__content"><div class="cca-hero__text">
  <div class="cca-hero__breadcrumb"><a href="<?= url('admin/dashboard.php') ?>">Dashboard</a> <span class="sep">›</span> Exercise Library</div>
  <h1 class="cca-hero__title">Exercise <span class="grad">Library</span></h1>
  <p class="cca-hero__subtitle">Manage 3D animation mappings, upload .glb models, configure target muscle metadata.</p>
</div></div></section>
<?= portal_nav('admin') ?>
<div class="cca-page-container"><div class="cca-card">
  <h3 class="cca-h3">📚 All Exercises (<?= count($exercises) ?>)</h3>
  <div class="cca-table-wrap"><table class="cca-table">
    <tr><th>ID</th><th>Exercise</th><th>Workout</th><th>Anim Mode</th><th>Target Muscle</th><th>Duration</th><th>Kcal</th></tr>
    <?php foreach ($exercises as $ex): ?>
    <tr><td><?= $ex['id'] ?></td><td><b><?= e($ex['name']) ?></b></td><td style="color:var(--text-3)"><?= e($ex['workout_name'] ?? '—') ?></td>
      <td><span class="cca-badge" style="font-size:11px"><?= e($ex['anim_mode'] ?? '—') ?></span></td>
      <td><span class="cca-badge cca-badge-warning" style="font-size:11px"><?= e($ex['target_muscle'] ?? '—') ?></span></td>
      <td><?= $ex['seconds'] ?>s</td><td><?= $ex['kcal'] ?></td></tr>
    <?php endforeach; ?>
  </table></div>
</div></div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

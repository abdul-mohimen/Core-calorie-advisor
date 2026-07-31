<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('patient', 'admin');
$uid = $_SESSION['user']['id'];

$doctors = db()->query("SELECT tp.*, u.id AS user_id, u.name, u.role FROM trainer_profiles tp JOIN users u ON u.id = tp.user_id WHERE u.role = 'doctor' ORDER BY tp.rating DESC")->fetchAll();

$portal = 'patient'; $pageTitle = 'Find Doctors';
include dirname(__DIR__) . '/includes/header.php';
?>
<section class="cca-hero cca-hero--gradient"><div class="cca-hero__overlay"></div><div class="cca-hero__content"><div class="cca-hero__text">
  <div class="cca-hero__breadcrumb"><a href="<?= url('patient/dashboard.php') ?>">Dashboard</a> <span class="sep">›</span> Doctors</div>
  <h1 class="cca-hero__title">Find <span class="grad">Doctors</span></h1>
  <p class="cca-hero__subtitle">Browse certified Dietitians, Endocrinologists, Physiotherapists, and Sports Medicine specialists. Filter by rating and fee.</p>
</div></div></section>
<?= portal_nav('patient') ?>
<div class="cca-page-container">
  <div class="cca-grid-3">
    <?php foreach ($doctors as $d): $rate = $d['consultation_fee'] ?? 100; ?>
    <div class="cca-card" style="padding:0; overflow:hidden">
      <div style="display:flex; align-items:center; gap:16px; padding:20px">
        <img src="<?= e($d['photo']) ?>" alt="<?= e($d['name']) ?>" loading="lazy" style="width:72px; height:72px; border-radius:50%; object-fit:cover; border:2px solid var(--info)">
        <div>
          <h4 style="font-size:16px"><?= e($d['name']) ?></h4>
          <p style="font-size:13px; color:var(--text-3)"><?= e($d['specialty']) ?></p>
          <span style="color:var(--warning-text); font-size:14px"><?= str_repeat('★', (int)round((float)$d['rating'])) ?></span>
          <span style="font-size:12px; color:var(--text-3)"><?= number_format((float)$d['rating'], 1) ?></span>
        </div>
      </div>
      <div style="padding:0 20px 20px; display:flex; gap:8px">
        <span class="cca-badge" style="background:color-mix(in srgb, var(--info) 10%, transparent); color:var(--info-text); padding:6px 12px">$<?= number_format((float)$rate, 0) ?>/consult</span>
        <a class="cca-btn cca-btn-primary" style="flex:1; justify-content:center; background:var(--info)" href="<?= url('pages/trainers.php') ?>">📅 Book Consult</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if (!$doctors): ?><div class="cca-empty-state">No doctors registered yet.</div><?php endif; ?>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

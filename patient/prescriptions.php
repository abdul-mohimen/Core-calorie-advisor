<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('patient', 'admin');
$uid = $_SESSION['user']['id'];

$prescriptions = [];
try {
    $pst = db()->prepare('SELECT p.*, u.name doctor_name FROM prescriptions p JOIN users u ON u.id = p.doctor_id WHERE p.patient_id = ? ORDER BY p.created_at DESC');
    $pst->execute([$uid]); $prescriptions = $pst->fetchAll();
} catch (Throwable $e) {}

$portal = 'patient'; $pageTitle = 'Prescriptions';
include dirname(__DIR__) . '/includes/header.php';
?>
<section class="cca-hero cca-hero--gradient"><div class="cca-hero__overlay"></div><div class="cca-hero__content"><div class="cca-hero__text">
  <div class="cca-hero__breadcrumb"><a href="<?= url('patient/dashboard.php') ?>">Dashboard</a> <span class="sep">›</span> Prescriptions</div>
  <h1 class="cca-hero__title">E-<span class="grad">Prescriptions</span></h1>
  <p class="cca-hero__subtitle">Your digital prescription vault. View active medications, dosage instructions, and doctor notes.</p>
</div></div></section>
<?= portal_nav('patient') ?>
<div class="cca-page-container">
  <?php if ($prescriptions): ?>
  <div class="cca-grid-2">
    <?php foreach ($prescriptions as $rx): ?>
    <div class="cca-card">
      <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:12px">
        <div>
          <h3 style="font-size:17px; margin-bottom:4px">💊 <?= e($rx['medication']) ?></h3>
          <p style="font-size:13px; color:var(--text-3)">Prescribed by <?= e($rx['doctor_name']) ?></p>
        </div>
        <span class="cca-badge <?= $rx['is_active'] ? 'cca-badge-success' : 'cca-badge-warning' ?>"><?= $rx['is_active'] ? 'Active' : 'Completed' ?></span>
      </div>
      <div style="background:var(--surface); border-radius:10px; padding:14px; border:1px solid var(--border); margin-bottom:10px">
        <div style="font-size:13px; color:var(--text-2)"><b>Dosage:</b> <?= e($rx['dosage'] ?? '—') ?></div>
        <?php if ($rx['notes']): ?><div style="font-size:13px; color:var(--text-3); margin-top:6px"><b>Notes:</b> <?= e($rx['notes']) ?></div><?php endif; ?>
      </div>
      <div style="font-size:12px; color:var(--text-3)">📅 <?= date('M d, Y', strtotime($rx['created_at'])) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
    <div class="cca-empty-state">No prescriptions on file. They'll appear here after a doctor consultation.</div>
  <?php endif; ?>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

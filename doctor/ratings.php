<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('doctor', 'admin');
$uid = $_SESSION['user']['id'];
$rev = db()->prepare('SELECT r.*, u.name reviewer FROM reviews r JOIN users u ON u.id = r.user_id WHERE r.trainer_id = ? ORDER BY r.created_at DESC');
$rev->execute([$uid]); $reviews = $rev->fetchAll();
$avg = 0; $total = count($reviews);
foreach ($reviews as $r) $avg += $r['rating'];
$avg = $total ? round($avg / $total, 1) : 0;

$portal = 'doctor'; $pageTitle = 'Ratings';
include dirname(__DIR__) . '/includes/header.php';
$navLinks = [['Dashboard',url('doctor/dashboard.php'),nav_icon('dashboard')],['Patient Queue',url('doctor/patient-queue.php'),nav_icon('queue')],['Consultations',url('doctor/consultations.php'),nav_icon('stethoscope')],['Financials',url('doctor/financials.php'),nav_icon('money')],['Ratings',url('doctor/ratings.php'),nav_icon('star')]];
?>
<section class="cca-hero cca-hero--gradient"><div class="cca-hero__overlay"></div><div class="cca-hero__content"><div class="cca-hero__text">
  <div class="cca-hero__breadcrumb"><a href="<?= url('doctor/dashboard.php') ?>">Dashboard</a> <span class="sep">›</span> Ratings</div>
  <h1 class="cca-hero__title">Clinical <span class="grad">Ratings</span></h1>
  <p class="cca-hero__subtitle">Patient reviews and your clinical reputation score. Verified doctor badge status.</p>
</div>
<div class="cca-hero__glass">
  <div class="cca-hero__glass-title">Clinical Score</div>
  <div style="text-align:center; padding:16px 0">
    <div style="font-family:var(--font-disp); font-size:48px; color:var(--accent-violet-text)"><?= $avg ?></div>
    <div style="color:var(--warning-text); font-size:20px"><?= str_repeat('★', (int)round($avg)) ?><?= str_repeat('☆', 5-(int)round($avg)) ?></div>
    <div style="font-size:13px; color:var(--text-3)"><?= $total ?> reviews</div>
    <?php if ($avg >= 4.5): ?><span class="cca-badge cca-badge-success" style="margin-top:10px">✓ Verified Doctor</span><?php endif; ?>
  </div>
</div></div></section>
<?= portal_nav('doctor', $navLinks) ?>
<div class="cca-page-container"><div class="cca-card">
  <h3 class="cca-h3">💬 Patient Reviews</h3>
  <?php if (!$reviews): ?><div class="cca-empty-state">No reviews yet.</div><?php endif; ?>
  <?php foreach ($reviews as $r): ?>
  <div style="padding:16px; border-bottom:1px solid var(--border)">
    <div style="display:flex; justify-content:space-between; margin-bottom:6px"><b><?= e($r['reviewer']) ?></b><span style="color:var(--warning-text)"><?= str_repeat('★', $r['rating']) ?></span></div>
    <p style="font-size:13px; color:var(--text-2)"><?= e($r['comment'] ?? '') ?></p>
    <span style="font-size:11px; color:var(--text-3)"><?= date('M d, Y', strtotime($r['created_at'])) ?></span>
  </div>
  <?php endforeach; ?>
</div></div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

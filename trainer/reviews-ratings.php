<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('trainer', 'admin');
$uid = $_SESSION['user']['id'];
$reviews = db()->prepare('SELECT r.*, u.name reviewer_name FROM reviews r JOIN users u ON u.id = r.user_id WHERE r.trainer_id = ? ORDER BY r.created_at DESC');
$reviews->execute([$uid]); $allReviews = $reviews->fetchAll();
$avg = 0; $total = count($allReviews); $stars = [5=>0,4=>0,3=>0,2=>0,1=>0];
foreach ($allReviews as $r) { $avg += $r['rating']; $stars[$r['rating']] = ($stars[$r['rating']] ?? 0) + 1; }
$avg = $total ? round($avg / $total, 1) : 0;

$portal = 'trainer'; $pageTitle = 'Reviews & Ratings';
include dirname(__DIR__) . '/includes/header.php';
?>
<section class="cca-hero cca-hero--gradient"><div class="cca-hero__overlay"></div><div class="cca-hero__content"><div class="cca-hero__text">
  <div class="cca-hero__breadcrumb"><a href="<?= url('trainer/dashboard.php') ?>">Dashboard</a> <span class="sep">›</span> Reviews</div>
  <h1 class="cca-hero__title">Reviews & <span class="grad">Ratings</span></h1>
  <p class="cca-hero__subtitle">Your reputation score and member feedback. Higher ratings mean higher visibility and lower platform fees.</p>
</div>
<div class="cca-hero__glass">
  <div class="cca-hero__glass-title">Rating Score</div>
  <div style="text-align:center; padding:16px 0">
    <div style="font-family:var(--font-disp); font-size:48px; color:var(--warning-text)"><?= $avg ?></div>
    <div style="color:var(--warning-text); font-size:20px; margin:4px 0"><?= str_repeat('★', (int)round($avg)) ?><?= str_repeat('☆', 5 - (int)round($avg)) ?></div>
    <div style="font-size:13px; color:var(--text-3)"><?= $total ?> total reviews</div>
  </div>
</div></div></section>
<?= portal_nav('trainer') ?>
<div class="cca-page-container">
  <!-- Star breakdown -->
  <div class="cca-card" style="margin-bottom:24px">
    <h3 class="cca-h3">📊 Rating Breakdown</h3>
    <div style="margin-top:16px">
      <?php foreach ([5,4,3,2,1] as $s): $pct = $total ? round(($stars[$s] / $total) * 100) : 0; ?>
      <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px">
        <span style="width:20px; text-align:right; font-size:13px; color:var(--text-2)"><?= $s ?>★</span>
        <div style="flex:1; height:8px; background:var(--surface-elevated); border-radius:4px; overflow:hidden">
          <div style="width:<?= $pct ?>%; height:100%; background:linear-gradient(90deg, var(--primary), var(--gold)); border-radius:4px; transition:width 0.6s"></div>
        </div>
        <span style="width:30px; font-size:12px; color:var(--text-3)"><?= $stars[$s] ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Reviews list -->
  <div class="cca-card">
    <h3 class="cca-h3">💬 Member Feedback</h3>
    <?php if (!$allReviews): ?><div class="cca-empty-state">No reviews yet.</div><?php endif; ?>
    <?php foreach ($allReviews as $r): ?>
    <div style="padding:16px; border-bottom:1px solid var(--border)">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px">
        <b style="font-size:14px"><?= e($r['reviewer_name']) ?></b>
        <span style="color:var(--warning-text)"><?= str_repeat('★', $r['rating']) ?></span>
      </div>
      <p style="font-size:13px; color:var(--text-2); line-height:1.5"><?= e($r['comment'] ?? '') ?></p>
      <span style="font-size:11px; color:var(--text-3)"><?= date('M d, Y', strtotime($r['created_at'])) ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('member', 'admin');
$uid = $_SESSION['user']['id'];

/* ---- Fetch all trainers + doctors (with rating-driven sorting) ---- */
$providers = db()->query("SELECT tp.*, u.id AS user_id, u.name, u.role
    FROM trainer_profiles tp JOIN users u ON u.id = tp.user_id
    WHERE u.role IN ('trainer','doctor')
    ORDER BY tp.rating DESC, u.name")->fetchAll();

/* ---- Reviews count per provider ---- */
$reviewCounts = [];
try {
    $rc = db()->query('SELECT trainer_id, COUNT(*) c, AVG(rating) avg_r FROM reviews GROUP BY trainer_id');
    foreach ($rc as $r) $reviewCounts[(int)$r['trainer_id']] = $r;
} catch (Throwable $e) {}

$portal    = 'member';
$pageTitle = 'Trainers & Doctors';
include dirname(__DIR__) . '/includes/header.php';

?>

<section class="cca-hero cca-hero--particles">
  <canvas id="heroParticles"></canvas>
  <div class="cca-hero__overlay"></div>
  <div class="cca-hero__content">
    <div class="cca-hero__text">
      <div class="cca-hero__breadcrumb"><a href="<?= url('member/dashboard.php') ?>">Dashboard</a> <span class="sep">›</span> Marketplace</div>
      <h1 class="cca-hero__title">Find Your <span class="grad">Expert</span></h1>
      <p class="cca-hero__subtitle">Browse, rate, and book elite Trainers or certified Doctors. Top-rated experts are boosted to the top.</p>
    </div>
  </div>
</section>

<?= portal_nav('member') ?>

<div class="cca-page-container">
  <!-- ═══ FILTER BAR ═══ -->
  <div class="cca-card" style="margin-bottom:24px; display:flex; gap:12px; flex-wrap:wrap; align-items:center">
    <button class="cca-btn cca-btn-primary filter-btn active" data-filter="all">All</button>
    <button class="cca-btn cca-btn-ghost filter-btn" data-filter="trainer">🏋️ Trainers</button>
    <button class="cca-btn cca-btn-ghost filter-btn" data-filter="doctor">🩺 Doctors</button>
    <div style="flex:1"></div>
    <input type="text" id="providerSearch" placeholder="Search by name or specialty..." style="padding:10px 16px; border-radius:10px; border:1px solid var(--border); background:var(--surface); color:var(--text-1); font-size:14px; width:240px">
  </div>

  <!-- ═══ PROVIDER GRID ═══ -->
  <div class="cca-grid-3" id="providerGrid">
    <?php foreach ($providers as $p):
      $revData = $reviewCounts[(int)$p['user_id']] ?? null;
      $revCount = $revData ? (int)$revData['c'] : 0;
      $stars = (float)$p['rating'];
      $isTopRated = $stars >= 4.5;
      $rate = $p['role'] === 'doctor' ? ($p['consultation_fee'] ?? 100) : ($p['hourly_rate'] ?? 50);
    ?>
    <div class="cca-card provider-card" data-role="<?= e($p['role']) ?>" data-name="<?= e(strtolower($p['name'])) ?>" data-specialty="<?= e(strtolower($p['specialty'])) ?>" style="padding:0; overflow:hidden; position:relative">
      <?php if ($isTopRated): ?>
        <span class="cca-badge cca-badge-success" style="position:absolute; top:10px; right:10px; z-index:10">⭐ Top Rated</span>
      <?php endif; ?>
      <div style="display:flex; align-items:center; gap:16px; padding:20px">
        <img src="<?= e($p['photo']) ?>" alt="<?= e($p['name']) ?>" loading="lazy" style="width:72px; height:72px; border-radius:50%; object-fit:cover; border:2px solid <?= $p['role'] === 'doctor' ? 'var(--info)' : 'var(--success)' ?>; box-shadow:0 0 16px <?= $p['role'] === 'doctor' ? 'color-mix(in srgb, var(--info) 20%, transparent)' : 'color-mix(in srgb, var(--success) 20%, transparent)' ?>">
        <div>
          <h4 style="font-size:16px; margin-bottom:2px"><?= e($p['name']) ?></h4>
          <p style="font-size:13px; color:var(--text-3)"><?= e($p['specialty']) ?></p>
          <div style="display:flex; gap:8px; margin-top:6px; align-items:center">
            <span style="color:var(--warning-text); font-size:14px"><?= str_repeat('★', (int)round($stars)) ?><?= str_repeat('☆', 5 - (int)round($stars)) ?></span>
            <span style="font-size:12px; color:var(--text-3)">(<?= $revCount ?> reviews)</span>
          </div>
        </div>
      </div>
      <div style="padding:0 20px 20px; display:flex; gap:8px">
        <span class="cca-badge" style="background:color-mix(in srgb, var(--primary) 10%, transparent); color:var(--primary-text); font-size:13px; padding:6px 12px">$<?= number_format((float)$rate, 0) ?>/<?= $p['role'] === 'doctor' ? 'consult' : 'hr' ?></span>
        <a class="cca-btn cca-btn-primary" style="flex:1; justify-content:center; font-size:13px" href="<?= url('pages/trainers.php') ?>">📅 Book Now</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const cards = document.querySelectorAll('.provider-card');
  const filterBtns = document.querySelectorAll('.filter-btn');
  const searchInput = document.getElementById('providerSearch');

  function filterCards() {
    const activeFilter = document.querySelector('.filter-btn.active')?.dataset.filter || 'all';
    const searchTerm = (searchInput?.value || '').toLowerCase();
    cards.forEach(card => {
      const role = card.dataset.role;
      const name = card.dataset.name;
      const spec = card.dataset.specialty;
      const matchRole = activeFilter === 'all' || role === activeFilter;
      const matchSearch = !searchTerm || name.includes(searchTerm) || spec.includes(searchTerm);
      card.style.display = matchRole && matchSearch ? '' : 'none';
    });
  }

  filterBtns.forEach(btn => btn.addEventListener('click', () => {
    filterBtns.forEach(b => b.classList.remove('active', 'cca-btn-primary'));
    filterBtns.forEach(b => b.classList.add('cca-btn-ghost'));
    btn.classList.add('active', 'cca-btn-primary');
    btn.classList.remove('cca-btn-ghost');
    filterCards();
  }));

  searchInput?.addEventListener('input', filterCards);
});
</script>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

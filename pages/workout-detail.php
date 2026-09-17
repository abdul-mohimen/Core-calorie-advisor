<?php
require_once dirname(__DIR__) . '/config/config.php';
$id = (int)get('id', '0');
$st = db()->prepare('SELECT * FROM workouts WHERE id = ?');
$st->execute([$id]); $w = $st->fetch();
if (!$w) { flash('err', 'Workout not found.'); redirect('pages/workouts.php'); }

/* ---- PRO gating: paid workout => login + pro/elite plan lazmi ---- */
$isPro = (bool)($w['is_pro'] ?? !($w['is_free'] ?? 1));
if ($isPro) {
    if (!is_logged_in()) { flash('warn', '🔒 This is a PRO workout — please log in first!'); redirect('auth/login.php?next=' . urlencode('pages/workout-detail.php?id=' . $id)); }
    if (!is_pro())       { flash('warn', '🔒 This is a PRO workout — active subscription required!'); redirect('pages/pricing.php'); }
}
$ex = db()->prepare('SELECT * FROM exercises WHERE workout_id = ? ORDER BY sort_order');
$ex->execute([$id]); $exercises = $ex->fetchAll();

/* ---- Dynamic duration & calorie calculation ---- */
$totalSeconds = 0; $totalKcal = 0;
foreach ($exercises as $e) {
    $totalSeconds += (int)$e['seconds'];
    $totalKcal += (int)$e['kcal'];
}
$totalMinutes = round($totalSeconds / 60, 1);

$wTitle = $w['name'] ?? $w['title'] ?? 'Workout';
$wDesc  = $w['description'] ?? 'Push your physical boundaries with this high-intensity training regimen.';
$wTag   = $w['tag'] ?? 'WORKOUT PLAN';
$wImg   = $w['image'] ?? 'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?w=1600&q=75&auto=format&fit=crop';

$pageTitle = $wTitle . ' — Detail';
include dirname(__DIR__) . '/includes/header.php';
?>
<!-- Google Fonts link removed in Phase 11: Teko was its only family, and Teko
     is now unified onto var(--font-disp) which the global header already loads. -->
<style>
  .page-hero::before, .page-hero::after { display: none !important; }
  /* ── Detail Hero ── */
  .wd-hero { position: relative; width: 100%; min-height: 420px; display: flex; align-items: flex-end; overflow: hidden; margin-bottom: 3rem; }
  .wd-hero-bg { position: absolute; inset: 0; background-size: cover; background-position: center; z-index: 0; transition: transform 1s; }
  .wd-hero:hover .wd-hero-bg { transform: scale(1.04); }
  .wd-hero-overlay { position: absolute; inset: 0; z-index: 1; background: linear-gradient(to top, var(--stage-bg) 0%, rgba(10,10,10,.7) 50%, rgba(10,10,10,.35) 100%); }
  .wd-hero-inner { position: relative; z-index: 2; max-width: 1200px; margin: 0 auto; padding: 2.5rem 1.5rem; width: 100%; display: flex; align-items: flex-end; gap: 2.5rem; flex-wrap: wrap; }
  /* Floating image card */
  .wd-img-card {
    width: 220px; height: 160px; border-radius: 1rem; overflow: hidden; flex-shrink: 0;
    border: 2px solid rgba(245,158,11,.2); box-shadow: 0 12px 40px rgba(0,0,0,.6), 0 0 30px rgba(245,158,11,.08);
  }
  .wd-img-card img { width: 100%; height: 100%; object-fit: cover; }
  @media (max-width: 640px) { .wd-img-card { width: 100%; height: 180px; } }
  .wd-hero-text { flex: 1; min-width: 280px; }
  /* Back link */
  .wd-back {
    display: inline-flex; align-items: center; gap: .5rem; padding: .35rem 1rem; border-radius: 2rem;
    font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .12em;
    background: rgba(245,158,11,.08); color: var(--warning-text); border: 1px solid rgba(245,158,11,.2);
    text-decoration: none; margin-bottom: 1rem; transition: all .25s;
  }
  .wd-back:hover { background: rgba(245,158,11,.16); }
  /* Tag */
  /* Tag */
  .wd-tag { font-size: .7rem; font-weight: 800; letter-spacing: .15em; color: var(--primary-text); text-transform: uppercase; margin-bottom: .35rem; display: block; }
  /* Title */
  .wd-title { font-size: 3rem; font-weight: 900; text-transform: uppercase; letter-spacing: .06em; color: var(--on-media); font-family: var(--font-disp); line-height: 1; margin: 0 0 .75rem; }
  @media (min-width: 768px) { .wd-title { font-size: 4.5rem; } }
  /* Desc */
  .wd-desc { color: rgba(255,255,255,.6); font-size: .9rem; line-height: 1.6; max-width: 600px; margin: 0 0 1.25rem; }
  /* Stat badges */
  .wd-badges { display: flex; flex-wrap: wrap; gap: .6rem; align-items: center; margin-bottom: 1.5rem; }
  .wd-badge {
    display: inline-flex; align-items: center; gap: .4rem; padding: .45rem .9rem; border-radius: .7rem;
    font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em;
    backdrop-filter: blur(8px);
  }
  .wd-badge-time { background: rgba(255,107,26,.1); color: var(--primary-text); border: 1px solid rgba(255,107,26,.2); }
  .wd-badge-count { background: rgba(255,255,255,.06); color: var(--on-media); border: 1px solid rgba(255,255,255,.08); }
  .wd-badge-kcal { background: rgba(239,68,68,.1); color: var(--danger-text); border: 1px solid rgba(239,68,68,.2); }
  .wd-badge-diff { background: rgba(168,85,247,.1); color: var(--accent-violet-text); border: 1px solid rgba(168,85,247,.2); }
  /* CTA */
  .wd-cta {
    display: inline-flex; align-items: center; gap: .5rem; padding: .9rem 2.5rem;
    border-radius: .85rem; font-size: .85rem; font-weight: 900; text-transform: uppercase;
    letter-spacing: .1em; text-decoration: none; transition: all .25s;
    background: linear-gradient(135deg, var(--primary), var(--primary-hot)); color: var(--on-media);
    box-shadow: 0 8px 24px rgba(255,107,26,.2); font-family: var(--font-disp);
  }
  .wd-cta:hover { background: linear-gradient(135deg, var(--primary), var(--primary)); transform: translateY(-2px); box-shadow: 0 12px 32px rgba(255,107,26,.3); }
  .wd-cta:active { transform: scale(.98); }
  .wd-pro-tag {
    display: inline-flex; align-items: center; padding: .4rem .9rem; border-radius: .5rem;
    font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .1em;
    background: rgba(255,107,26,.1); color: var(--primary-text); border: 1px solid rgba(255,107,26,.2);
  }
  /* ── Exercise breakdown cards ── */
  .ex-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.25rem; margin-bottom: 3rem; }
  .ex-card {
    background: var(--card); border: 1px solid var(--border); border-radius: .85rem;
    padding: 1.25rem; position: relative; overflow: hidden; transition: all .25s;
  }
  .ex-card:hover { border-color: rgba(255,107,26,.3); }
  .ex-card-corner { position: absolute; top: 0; right: 0; width: 5rem; height: 5rem; background: rgba(255,107,26,.04); border-bottom-left-radius: 100%; z-index: 0; transition: background .25s; }
  .ex-card:hover .ex-card-corner { background: rgba(255,107,26,.08); }
  .ex-num { font-size: .68rem; font-weight: 800; letter-spacing: .15em; color: var(--primary-text); text-transform: uppercase; margin-bottom: .5rem; position: relative; z-index: 1; }
  .ex-name { font-size: 1.15rem; font-weight: 800; color: var(--text); text-transform: uppercase; letter-spacing: .05em; font-family: var(--font-disp); margin: 0 0 .75rem; position: relative; z-index: 1; }
  .ex-meta { display: flex; justify-content: space-between; align-items: center; font-size: .8rem; color: var(--muted); position: relative; z-index: 1; }
  .ex-meta b { color: var(--text); font-family: 'JetBrains Mono', monospace; }
</style>

<!-- HERO SECTION -->
<div class="wd-hero">
  <div class="wd-hero-bg" style="background-image: url('<?= e($wImg) ?>')"></div>
  <div class="wd-hero-overlay"></div>
  <div class="wd-hero-inner">
    <!-- Floating image card -->
    <div class="wd-img-card">
      <img src="<?= e($wImg) ?>" alt="<?= e($wTitle) ?>">
    </div>

    <!-- Text content -->
    <div class="wd-hero-text">
      <a class="wd-back" href="<?= url('pages/workouts.php') ?>">← Back to Workouts</a>
      <span class="wd-tag"><?= e($wTag) ?></span>
      <h1 class="wd-title"><?= e($wTitle) ?></h1>
      <p class="wd-desc"><?= e($wDesc) ?></p>

      <!-- Stat badges -->
      <div class="wd-badges">
        <span class="wd-badge wd-badge-time">⏱ 10 Minutes Total Duration</span>
        <span class="wd-badge wd-badge-count">💪 <?= count($exercises) ?> Exercises</span>
        <span class="wd-badge wd-badge-kcal">🔥 <?= $totalKcal ?> Kcal Estimated Burn</span>
        <?php
          $avgDuration = count($exercises) > 0 ? round($totalSeconds / count($exercises)) : 0;
          $difficulty = count($exercises) >= 8 ? 'Advanced' : (count($exercises) >= 5 ? 'Intermediate' : 'Beginner');
        ?>
        <span class="wd-badge wd-badge-diff">📊 <?= $difficulty ?></span>
      </div>

      <!-- CTA -->
      <div style="display:flex; flex-wrap:wrap; gap:1rem; align-items:center;">
        <a class="wd-cta" href="<?= url('pages/player.php?id=' . $w['id']) ?>">▶ Begin Forging</a>
        <?php if ($isPro): ?>
          <span class="wd-pro-tag">🔒 PRO PLAN REQUIRED</span>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- EXERCISE BREAKDOWN -->
<section class="wrap" style="padding-top:0;">
  <div class="sec-head" style="margin-bottom:1.5rem;">
    <div>
      <span class="eyebrow" style="color:var(--primary-text);">SESSION BREAKDOWN</span>
      <h2 style="font-family: var(--font-disp); letter-spacing:.06em; text-transform:uppercase;">Exercises Included</h2>
    </div>
  </div>
  
  <div class="ex-grid">
    <?php foreach ($exercises as $i => $e): ?>
    <div class="ex-card">
      <div class="ex-card-corner"></div>
      <span class="ex-num">Exercise <?= $i + 1 ?></span>
      <h3 class="ex-name"><?= e($e['name']) ?></h3>
      <div class="ex-meta">
        <span>⏱ Duration: <b><?= (int)$e['seconds'] ?>s</b></span>
        <span>🔥 Energy: <b><?= (int)$e['kcal'] ?> kcal</b></span>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Full-width Start Button at the bottom of the overview -->
  <div style="margin-top: 3rem; margin-bottom: 3rem; text-align: center; width: 100%;">
    <a class="wd-cta" href="<?= url('pages/player.php?id=' . $w['id']) ?>" style="display: block; width: 100%; box-sizing: border-box; text-align: center; font-size: 1.5rem; padding: 1.25rem; border-radius: 1rem; font-family: var(--font-disp); letter-spacing: 0.15em; text-transform: uppercase;">
      ▶ Start Workout
    </a>
  </div>
</section>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

<?php
require_once dirname(__DIR__) . '/config/config.php';

$categories = [
    'strength' => 'Strength',
    'hiit-cardio' => 'HIIT & Cardio',
    'yoga-stretching' => 'Yoga & Stretching',
    'warmup-recovery' => 'Warmup & Recovery',
    'specialized' => 'Specialized / Myofascial Release'
];

/* ---- Category-specific hero metadata ---- */
$categoryHeroes = [
    'strength' => [
        'title' => 'Strength',
        'desc'  => 'Forge unbreakable power. Master compound lifts, progressive overload, and raw force — sculpt every muscle fibre to its absolute peak.',
        'img'   => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=1600&q=80&auto=format&fit=crop',
        'accent' => 'var(--warning)'
    ],
    'hiit-cardio' => [
        'title' => 'HIIT & Cardio',
        'desc'  => 'Ignite your metabolism. Explosive intervals, relentless pace, and zero excuses — push your heart rate through the roof and torch every calorie.',
        'img'   => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=1600&q=80&auto=format&fit=crop',
        'accent' => 'var(--danger)'
    ],
    'yoga-stretching' => [
        'title' => 'Yoga & Stretching',
        'desc'  => 'Find your center. Flow through guided poses, unlock deep flexibility, and cultivate the mindful balance every athlete needs.',
        'img'   => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=1600&q=80&auto=format&fit=crop',
        'accent' => 'var(--success)'
    ],
    'warmup-recovery' => [
        'title' => 'Warmup & Recovery',
        'desc'  => 'Train smarter, recover faster. Prime your joints before battle and cool down with precision — the foundation every session needs.',
        'img'   => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=1600&q=80&auto=format&fit=crop',
        'accent' => 'var(--primary)'
    ],
    'specialized' => [
        'title' => 'Specialized',
        'desc'  => 'Unlock targeted therapy. Foam rolling, myofascial release, and rehabilitation-focused routines engineered for precision recovery.',
        'img'   => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=1600&q=80&auto=format&fit=crop',
        'accent' => 'var(--accent-violet)'
    ],
];

$selectedCat = get('category');

if ($selectedCat !== '') {
    $dbCategories = [$selectedCat];
    if ($selectedCat === 'strength') {
        $dbCategories = ['strength', 'weights', 'legs', 'abs'];
    } elseif ($selectedCat === 'hiit-cardio') {
        $dbCategories = ['hiit-cardio', 'hiit', 'cardio'];
    } elseif ($selectedCat === 'yoga-stretching') {
        $dbCategories = ['yoga-stretching', 'yoga'];
    } elseif ($selectedCat === 'warmup-recovery') {
        $dbCategories = ['warmup-recovery', 'recovery', 'medical'];
    } elseif ($selectedCat === 'specialized') {
        $dbCategories = ['specialized', 'specialized-myofascial'];
    }

    $placeholders = implode(',', array_fill(0, count($dbCategories), '?'));
    $st = db()->prepare("SELECT * FROM workouts WHERE category IN ($placeholders) ORDER BY id");
    $st->execute($dbCategories);
    $workouts = $st->fetchAll();
} else {
    $workouts = db()->query('SELECT * FROM workouts ORDER BY id')->fetchAll();
}

/* Dynamic page title */
$heroData = ($selectedCat !== '' && isset($categoryHeroes[$selectedCat]))
    ? $categoryHeroes[$selectedCat]
    : ['title' => 'All Programs', 'desc' => 'Select a dynamic program below. Our photorealistic 3D coach will guide you through every rep with perfect sync and voice cues.', 'img' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=800&q=80&auto=format&fit=crop', 'accent' => 'var(--primary)'];

$pageTitle = $heroData['title'] . ' — Workouts';
include dirname(__DIR__) . '/includes/header.php';

function renderWorkoutCard($w) {
    $stats = db()->prepare('SELECT SUM(seconds) s, SUM(kcal) k FROM exercises WHERE workout_id = ?');
    $stats->execute([$w['id']]); $s = $stats->fetch();
    $isPro = (int)($w['is_free'] ?? 1) === 0;
    $wTitle = $w['title'] ?? $w['name'] ?? 'Workout Program';
    $wDesc = $w['description'] ?? $w['tag'] ?? 'No description available';
    $url = url('pages/workout-detail.php?id=' . $w['id']);
    ?>
    <div class="wk-card">
      <div class="wk-thumb">
        <?php if ($isPro): ?>
          <span class="wk-badge wk-badge-pro">🔒 PRO</span>
        <?php else: ?>
          <span class="wk-badge wk-badge-free">FREE</span>
        <?php endif; ?>
        <img src="<?= e(!empty($w['image']) ? $w['image'] : 'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?w=500&q=60&auto=format&fit=crop') ?>" alt="<?= e($wTitle) ?>" loading="lazy">
      </div>
      <div class="wk-body">
        <div>
          <h3 class="wk-title"><?= e($wTitle) ?></h3>
          <p class="wk-desc"><?= e($wDesc) ?></p>
        </div>
        <div>
          <div class="wk-stats">
            <span>⏱ <b><?= round(($s['s'] ?? 0) / 60, 1) ?> min</b></span>
            <span>🔥 <b><?= (int)($s['k'] ?? 0) ?> kcal</b></span>
          </div>
          <a class="wk-btn <?= $isPro ? 'wk-btn-pro' : 'wk-btn-open' ?>" href="<?= $url ?>">
            <?= $isPro ? '🔒 Unlock' : '▶ Open' ?>
          </a>
        </div>
      </div>
    </div>
    <?php
}

function renderHeroWorkoutCard($w) {
    $stats = db()->prepare('SELECT SUM(seconds) s, SUM(kcal) k FROM exercises WHERE workout_id = ?');
    $stats->execute([$w['id']]); $s = $stats->fetch();
    $isPro = (int)($w['is_free'] ?? 1) === 0;
    $wTitle = $w['title'] ?? $w['name'] ?? 'Workout Program';
    $wDesc = $w['description'] ?? 'No description available';
    $url = url('pages/workout-detail.php?id=' . $w['id']);
    ?>
    <div class="hero-wk-card">
      <div class="hero-wk-img-wrap">
        <img src="<?= e(!empty($w['image']) ? $w['image'] : 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=800&q=60&auto=format&fit=crop') ?>" alt="<?= e($wTitle) ?>">
        <div class="hero-wk-overlay"></div>
      </div>
      <div class="hero-wk-info">
        <div>
          <span class="hero-wk-kicker">🔥 Featured Workout</span>
          <h2 class="hero-wk-title"><?= e($wTitle) ?></h2>
          <p class="hero-wk-desc"><?= e($wDesc) ?></p>
        </div>
        <div class="hero-wk-footer">
          <div class="hero-wk-stats">
            <span>⏱ <b><?= round(($s['s'] ?? 0) / 60, 1) ?> mins</b></span>
            <span>🔥 <b><?= (int)($s['k'] ?? 0) ?> kcal</b></span>
            <span>💪 <b>Full Body</b></span>
          </div>
          <a class="hero-wk-btn" href="<?= $url ?>">▶ Start Training</a>
        </div>
      </div>
    </div>
    <?php
}
?>
<style>
  .page-hero::before, .page-hero::after { display: none !important; }
  /* ── Workout sub-cards ── */
  .wk-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1.5rem; }
  @media (min-width: 768px) { .wk-grid { grid-template-columns: repeat(3, 1fr); } }
  @media (min-width: 1100px) { .wk-grid { grid-template-columns: repeat(4, 1fr); } }
  /* .wk-card* and .hero-wk-* anatomy REMOVED in Phase 10 — now owned by
     assets/css/cca-cards.css so every card in the app shares one anatomy.
     .wk-grid, .wk-hero* and .cat-pill* stay here: they are page layout. */
  /* Hero section */
  .wk-hero { position: relative; min-height: 300px; display: flex; align-items: center; overflow: hidden; }
  .wk-hero-bg { position: absolute; inset: 0; background-size: cover; background-position: center; z-index: 0; }
  .wk-hero-overlay { position: absolute; inset: 0; z-index: 1; background: linear-gradient(to right, rgba(10,10,10,.92) 0%, rgba(10,10,10,.65) 60%, rgba(10,10,10,.35) 100%); }
  .wk-hero-content { position: relative; z-index: 2; max-width: 1200px; margin: 0 auto; padding: 3rem 1.5rem; width: 100%; }
  /* Category filter pills */
  .cat-pills { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 1.75rem; }
  .cat-pill {
    padding: .45rem .9rem; border-radius: .65rem; font-size: .7rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: .05em; text-decoration: none;
    border: 1px solid var(--border); color: var(--muted); background: var(--card);
    transition: all .25s;
  }
  .cat-pill:hover { color: var(--warning-text); border-color: rgba(245,158,11,.3); background: rgba(245,158,11,.06); }
  .cat-pill.active { background: linear-gradient(135deg, var(--warning), var(--warning-text)); color: var(--on-accent); border-color: var(--warning); }

  /* ── Featured Wide Hero Card ── */
</style>

<!-- Dynamic Hero Section -->
<div class="wk-hero tf-hero-anim">
  <div class="wk-hero-bg" style="background-image: url('<?= e($heroData['img']) ?>')"></div>
  <div class="wk-hero-overlay"></div>
  <div class="wk-hero-content">
    <span class="eyebrow" style="color: <?= $heroData['accent'] ?>">Premium Workout Engine</span>
    <!-- --on-media / --on-media-dim are intentionally theme-invariant: this copy
         sits on a dark photo behind .wk-hero-overlay in BOTH themes, so it must
         stay light. Using --text-1 here would make it near-black on the photo. -->
    <h1 style="color:var(--on-media); font-weight:900; letter-spacing:.06em; text-transform:uppercase; margin:.5rem 0; font-size:2.5rem; line-height:1.1;"><?= e($heroData['title']) ?></h1>
    <p style="color:var(--on-media-dim); max-width:600px; margin:.75rem 0 0; font-size:.9rem; line-height:1.6;"><?= e($heroData['desc']) ?></p>
  </div>
</div>

<!-- Content Area -->
<div class="wrap" style="padding-top:2rem; padding-bottom:3rem;">
  
  <!-- Category Filter Pills -->
  <div class="cat-pills">
    <a class="cat-pill <?= $selectedCat === '' ? 'active' : '' ?>" href="<?= url('pages/workouts.php') ?>">All</a>
    <?php foreach ($categories as $slug => $label): ?>
      <a class="cat-pill <?= $selectedCat === $slug ? 'active' : '' ?>" href="<?= url('pages/workouts.php?category=' . urlencode($slug)) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>

  <?php if ($selectedCat === 'strength'): ?>
    <!-- STRENGTH CATEGORY NESTED LAYOUT -->
    <!-- 1. Hero Card (Full Body) -->
    <?php
      $heroSt = db()->prepare("SELECT * FROM workouts WHERE slug = 'full-body' LIMIT 1");
      $heroSt->execute();
      $heroWk = $heroSt->fetch();
      if ($heroWk) {
          renderHeroWorkoutCard($heroWk);
      }
    ?>
    
    <!-- 2. Abs & Core Section -->
    <div class="sec-head" style="margin-top: 2rem; margin-bottom: 1.25rem;">
      <h2 style="font-family: var(--font-disp); text-transform:uppercase; letter-spacing:.05em; font-size:1.9rem; border-bottom: 1px solid var(--border); padding-bottom:0.5rem; color:var(--text-1);">Abs & Core</h2>
    </div>
    <div class="wk-grid" style="margin-bottom: 3rem;">
      <?php
        $sec1St = db()->prepare("SELECT * FROM workouts WHERE category = 'strength' AND subcategory = 'abs-core' AND slug != 'full-body' ORDER BY id");
        $sec1St->execute();
        $sec1Wks = $sec1St->fetchAll();
        foreach ($sec1Wks as $w) {
            renderWorkoutCard($w);
        }
      ?>
    </div>

    <!-- 3. Lower Body Section -->
    <div class="sec-head" style="margin-top: 2rem; margin-bottom: 1.25rem;">
      <h2 style="font-family: var(--font-disp); text-transform:uppercase; letter-spacing:.05em; font-size:1.9rem; border-bottom: 1px solid var(--border); padding-bottom:0.5rem; color:var(--text-1);">Lower Body</h2>
    </div>
    <div class="wk-grid" style="margin-bottom: 3rem;">
      <?php
        $sec2St = db()->prepare("SELECT * FROM workouts WHERE category = 'strength' AND subcategory = 'lower-body' AND slug != 'full-body' ORDER BY id");
        $sec2St->execute();
        $sec2Wks = $sec2St->fetchAll();
        foreach ($sec2Wks as $w) {
            renderWorkoutCard($w);
        }
      ?>
    </div>

    <!-- 4. Upper Body Section -->
    <div class="sec-head" style="margin-top: 2rem; margin-bottom: 1.25rem;">
      <h2 style="font-family: var(--font-disp); text-transform:uppercase; letter-spacing:.05em; font-size:1.9rem; border-bottom: 1px solid var(--border); padding-bottom:0.5rem; color:var(--text-1);">Upper Body</h2>
    </div>
    <div class="wk-grid" style="margin-bottom: 3rem;">
      <?php
        $sec3St = db()->prepare("SELECT * FROM workouts WHERE category = 'strength' AND subcategory = 'upper-body' AND slug != 'full-body' ORDER BY id");
        $sec3St->execute();
        $sec3Wks = $sec3St->fetchAll();
        foreach ($sec3Wks as $w) {
            renderWorkoutCard($w);
        }
      ?>
    </div>

  <?php elseif ($selectedCat === 'hiit-cardio'): ?>
    <!-- HIIT & CARDIO NESTED LAYOUT -->
    <div class="wk-grid" style="margin-bottom: 3rem;">
      <?php
        // 1. Fetch core routines in sequence by querying the slugs in order
        $slugs = ['hiit', 'light-cardio', 'tabata', 'cardio-strength-intervals'];
        $placeholders = implode(',', array_fill(0, count($slugs), '?'));
        $hiitSt = db()->prepare("SELECT * FROM workouts WHERE slug IN ($placeholders)");
        $hiitSt->execute($slugs);
        $hiitWks = $hiitSt->fetchAll();
        
        $orderedWks = [];
        foreach ($slugs as $slug) {
            foreach ($hiitWks as $w) {
                if ($w['slug'] === $slug) {
                    $orderedWks[] = $w;
                    break;
                }
            }
        }
        
        foreach ($orderedWks as $w) {
            renderWorkoutCard($w);
        }
      ?>
    </div>

    <!-- 2. SPECIAL Section -->
    <div class="sec-head" style="margin-top: 2rem; margin-bottom: 1.25rem;">
      <h2 style="font-family: var(--font-disp); text-transform:uppercase; letter-spacing:.05em; font-size:1.9rem; border-bottom: 1px solid var(--border); padding-bottom:0.5rem; color:var(--text-1);">Special</h2>
    </div>
    <div class="wk-grid">
      <?php
        $specSlugs = ['plyometrics', 'joint-friendly'];
        $specPl = implode(',', array_fill(0, count($specSlugs), '?'));
        $specSt = db()->prepare("SELECT * FROM workouts WHERE slug IN ($specPl)");
        $specSt->execute($specSlugs);
        $specWks = $specSt->fetchAll();
        
        $orderedSpec = [];
        foreach ($specSlugs as $slug) {
            foreach ($specWks as $w) {
                if ($w['slug'] === $slug) {
                    $orderedSpec[] = $w;
                    break;
                }
            }
        }
        
        foreach ($orderedSpec as $w) {
            renderWorkoutCard($w);
        }
      ?>
    </div>

  <?php elseif ($selectedCat === 'yoga-stretching'): ?>
    <!-- YOGA & STRETCHING NESTED LAYOUT (Precise Sequence) -->
    <div class="wk-grid">
      <?php
        $yogaSlugs = ['full-body-flexibility', 'for-runners', 'healthy-back', 'morning-yoga', 'yoga-for-sleep', 'more-yoga'];
        $yogaPl = implode(',', array_fill(0, count($yogaSlugs), '?'));
        $yogaSt = db()->prepare("SELECT * FROM workouts WHERE slug IN ($yogaPl)");
        $yogaSt->execute($yogaSlugs);
        $yogaWks = $yogaSt->fetchAll();
        
        $orderedYoga = [];
        foreach ($yogaSlugs as $slug) {
            foreach ($yogaWks as $w) {
                if ($w['slug'] === $slug) {
                    $orderedYoga[] = $w;
                    break;
                }
            }
        }
        
        foreach ($orderedYoga as $w) {
            renderWorkoutCard($w);
        }
      ?>
    </div>

  <?php elseif ($selectedCat === 'warmup-recovery'): ?>
    <!-- WARMUP & RECOVERY NESTED LAYOUT (Precise Sequence) -->
    <div class="wk-grid">
      <?php
        $warmSlugs = ['warm-up', 'cool-down', 'full-body-rolling', 'back-rolling', 'legs-rolling', 'neck-release'];
        $warmPl = implode(',', array_fill(0, count($warmSlugs), '?'));
        $warmSt = db()->prepare("SELECT * FROM workouts WHERE slug IN ($warmPl)");
        $warmSt->execute($warmSlugs);
        $warmWks = $warmSt->fetchAll();
        
        $orderedWarm = [];
        foreach ($warmSlugs as $slug) {
            foreach ($warmWks as $w) {
                if ($w['slug'] === $slug) {
                    $orderedWarm[] = $w;
                    break;
                }
            }
        }
        
        foreach ($orderedWarm as $w) {
            renderWorkoutCard($w);
        }
      ?>
    </div>

  <?php else: ?>
    <!-- Other Categories (Specialized, or All) -->
    <div class="wk-grid">
      <?php foreach ($workouts as $w): ?>
        <?php renderWorkoutCard($w); ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

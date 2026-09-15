<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Home';
$trainers = db()->query('SELECT tp.*, u.name FROM trainer_profiles tp JOIN users u ON u.id = tp.user_id ORDER BY tp.rating DESC LIMIT 4')->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<style>
/* ── Hero Cinematic Video Integration ── */
.hero-bg {
  overflow: hidden; /* Ensure video doesn't bleed */
}
.hero-bg::after {
  display: none !important; /* Hide global flat overlay in favor of our cinematic gradient */
}
.hero-3d {
  background: rgba(15, 15, 20, 0.85) !important;
  backdrop-filter: blur(16px) !important;
  -webkit-backdrop-filter: blur(16px) !important;
  border: 1px solid rgba(255, 140, 0, 0.25) !important;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6), 0 0 25px rgba(255, 120, 0, 0.12) !important;
  border-radius: 20px !important;
}
.hero-video {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  z-index: 0;
}
.hero-video-overlay {
  position: absolute;
  inset: 0;
  z-index: 1; /* Above video, below text */
  /* Dark gradient left-to-right + bottom fade, matching brand ember tint */
  background: 
    linear-gradient(to right, rgba(10,10,13,0.85) 0%, rgba(10,10,13,0.4) 40%, rgba(255,107,26,0.05) 100%),
    linear-gradient(to top, rgba(10,10,13,0.8) 0%, transparent 15%);
  pointer-events: none;
}
</style>

<div class="wrap">
  <div class="hero tf-hero-anim">
    <div class="hero-bg" style="background-image:url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=1600&q=75&auto=format&fit=crop')">
      <!-- HERO CINEMATIC VIDEO -->
      <video class="hero-video" id="heroVideo" autoplay muted loop playsinline preload="metadata" poster="<?= url('assets/images/hero-poster.jpg') ?>">
        <source src="<?= url('assets/videos/hero-loop.mp4') ?>" type="video/mp4" media="(min-width: 768px)">
        <source src="<?= url('assets/videos/hero-loop-mobile.mp4') ?>" type="video/mp4" media="(max-width: 767px)">
      </video>
      <div class="hero-video-overlay"></div>
    </div>
    <div>
      <span class="eyebrow">Precision nutrition meets 3D coaching.</span>
      <h1>Master Your <span class="grad-text">Calories.</span></h1>
      <p>Experience the world's most advanced 3D workout engine paired with intelligent calorie tracking. Train with a photorealistic coach, scan your meals, and build legendary strength today.</p>
      <div class="hero-cta">
        <a class="btn btn-fire" href="<?= url('pages/workouts.php') ?>" style="display:inline-flex;align-items:center;gap:8px"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2s4.5 3.6 4.5 8A4.5 4.5 0 0 1 12 14.5 4.5 4.5 0 0 1 7.5 10c0-1 .4-1.8.4-1.8S6 10 6 13a6 6 0 0 0 12 0c0-5.2-6-11-6-11z"/></svg> Start Training</a>
        <a class="btn btn-ghost" href="<?= url('pages/pricing.php') ?>" style="display:inline-flex;align-items:center;gap:8px"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h12l4 6-10 12L2 9z"/><path d="M2 9h20M12 3 8 9l4 12 4-12-4-6z"/></svg> Go Pro</a>
      </div>
      <div class="hero-stats">
        <div class="hstat"><b>120<i>+</i></b><span>3D Workouts</span></div>
        <div class="hstat"><b>50<i>K</i></b><span>Members</span></div>
        <div class="hstat"><b>85<i>+</i></b><span>Trainers &amp; Doctors</span></div>
        <div class="hstat"><b>4.9<i>★</i></b><span>Rating</span></div>
      </div>
    </div>
    <div class="hero-3d" id="hero3d">
      <span class="h3d-tag">● Live 3D Coach</span>
      <div class="h3d-modes">
        <button data-mode="idle" class="on">Warm-Up</button>
        <button data-mode="jumpingjack">Jumping Jack</button>
        <button data-mode="squat">Squat</button>
        <button data-mode="pushup">Push-Up</button>
        <button data-mode="curl">Dumbbell Curl</button>
        <button data-mode="yoga">Yoga</button>
      </div>
    </div>
  </div>
</div>

<script>
/* ── Hero Video Performance & A11y ── */
document.addEventListener('DOMContentLoaded', () => {
  const scanVideo = document.getElementById('scanVideo');

  // 1. Respect prefers-reduced-motion (applies to hero + scanner banner videos)
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReducedMotion) {
    if (scanVideo) { scanVideo.pause(); scanVideo.style.display = 'none'; }
  }

  const video = document.getElementById('heroVideo');
  if (!video) return;

  if (prefersReducedMotion) {
    video.pause();
    video.style.display = 'none'; // Fallback to poster/CSS background
    return;
  }

  // 2. Lazy/deferred: play only when in view
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        video.play().catch(e => console.log('Autoplay prevented by browser:', e));
      } else {
        video.pause(); // Pause when off-screen to save battery/CPU
      }
    });
  }, { threshold: 0.1 });
  
  observer.observe(document.querySelector('.hero'));

  // 3. Same treatment for the AI Body Scanner banner video
  const scanBanner = document.querySelector('.scan-banner');
  if (scanVideo && scanBanner) {
    const scanObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          scanVideo.play().catch(e => console.log('Autoplay prevented by browser:', e));
        } else {
          scanVideo.pause();
        }
      });
    }, { threshold: 0.1 });
    scanObserver.observe(scanBanner);
  }
});
</script>

<section class="wrap">
  <div class="sec-head rv"><div><span class="eyebrow">Why Core Calorie Advisor</span><h2 class="text-gray-900 dark:text-white">Built Different</h2></div></div>
  <div class="grid g4">
    <div class="rv flex flex-row h-48 bg-white dark:bg-[#121212] rounded-2xl overflow-hidden border border-gray-200 dark:border-white/10 hover:border-brand-accent transition-all">
      <img src="https://images.unsplash.com/photo-1541534741688-6078c6bfb5c5?w=600&q=60&auto=format&fit=crop" alt="3D workouts" class="w-2/5 h-full object-cover" loading="lazy">
      <div class="w-3/5 p-5 flex flex-col justify-center gap-1">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white leading-tight">3D Animated Workouts</h3>
        <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed line-clamp-3">Living 3D trainer har exercise correct form, timing aur rest ke saath karta hai — bas follow karo.</p>
      </div>
    </div>
    <div class="rv flex flex-row h-48 bg-white dark:bg-[#121212] rounded-2xl overflow-hidden border border-gray-200 dark:border-white/10 hover:border-brand-accent transition-all">
      <img src="https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=600&q=60&auto=format&fit=crop" alt="AI scanner" class="w-2/5 h-full object-cover" loading="lazy">
      <div class="w-3/5 p-5 flex flex-col justify-center gap-1">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white leading-tight">AI Body Scanner</h3>
        <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed line-clamp-3">Google-Lens style scan batata hai gain karna hai ya cut — phir trainer aur workout suggest karta hai.</p>
      </div>
    </div>
    <div class="rv flex flex-row h-48 bg-white dark:bg-[#121212] rounded-2xl overflow-hidden border border-gray-200 dark:border-white/10 hover:border-brand-accent transition-all">
      <img src="https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&q=60&auto=format&fit=crop" alt="Nutrition" class="w-2/5 h-full object-cover" loading="lazy">
      <div class="w-3/5 p-5 flex flex-col justify-center gap-1">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white leading-tight">Food Scanner &amp; Nutrition</h3>
        <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed line-clamp-3">Kisi bhi meal ka scan — instant calories, protein, carbs aur fats rich food database se.</p>
      </div>
    </div>
    <div class="rv flex flex-row h-48 bg-white dark:bg-[#121212] rounded-2xl overflow-hidden border border-gray-200 dark:border-white/10 hover:border-brand-accent transition-all">
      <img src="https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=600&q=60&auto=format&fit=crop" alt="Doctors" class="w-2/5 h-full object-cover" loading="lazy">
      <div class="w-3/5 p-5 flex flex-col justify-center gap-1">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white leading-tight">Disease-Safe Plans</h3>
        <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed line-clamp-3">Heart, diabetes, BP ya knee pain? Doctors special safe workout plans approve karte hain.</p>
      </div>
    </div>
  </div>
</section>

<?php
/* ---- Category definitions with premium metadata ---- */
$catDefs = [
  'strength' => [
    'icon' => '💪', 'title' => 'Strength',
    'desc' => 'Build raw power with compound lifts and progressive overload.',
    'img'  => 'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?w=800&q=80&auto=format&fit=crop',
    'dbCats' => ['strength','weights','legs','abs']
  ],
  'hiit-cardio' => [
    'icon' => '⚡', 'title' => 'HIIT & Cardio',
    'desc' => 'Torch calories and boost your endurance with high-intensity intervals.',
    'img'  => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=800&q=80&auto=format&fit=crop',
    'dbCats' => ['hiit-cardio','hiit','cardio']
  ],
  'yoga-stretching' => [
    'icon' => '🧘', 'title' => 'Yoga & Stretching',
    'desc' => 'Improve flexibility, balance, and mindfulness through guided flows.',
    'img'  => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=800&q=80&auto=format&fit=crop',
    'dbCats' => ['yoga-stretching','yoga']
  ],
  'warmup-recovery' => [
    'icon' => '🩹', 'title' => 'Warmup & Recovery',
    'desc' => 'Prime your body before training and speed up recovery after.',
    'img'  => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=800&q=80&auto=format&fit=crop',
    'dbCats' => ['warmup-recovery','recovery','medical']
  ],
  'specialized' => [
    'icon' => '🧬', 'title' => 'Specialized / Myofascial Release',
    'desc' => 'Targeted therapy, foam rolling, and myofascial release techniques.',
    'img'  => 'https://images.unsplash.com/photo-1600880292203-757bb62b4baf?w=800&q=80&auto=format&fit=crop',
    'dbCats' => ['specialized','specialized-myofascial']
  ],
];

/* Count workouts per category for the badges, and check if PRO is available */
$catCounts = [];
$catHasPro = [];
foreach ($catDefs as $slug => $def) {
  $ph = implode(',', array_fill(0, count($def['dbCats']), '?'));
  $cst = db()->prepare("SELECT COUNT(*) c FROM workouts WHERE category IN ($ph)");
  $cst->execute($def['dbCats']);
  $catCounts[$slug] = (int)$cst->fetchColumn();

  $pst = db()->prepare("SELECT COUNT(*) c FROM workouts WHERE category IN ($ph) AND is_free = 0");
  $pst->execute($def['dbCats']);
  $catHasPro[$slug] = (int)$pst->fetchColumn() > 0;
}
?>

<style>
/* ── Premium Category Cards (Medium-length Layout) ── */
.cat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
@media (min-width: 900px) { .cat-grid { grid-template-columns: repeat(3, 1fr); } }
@media (min-width: 1200px) { .cat-grid { grid-template-columns: repeat(5, 1fr); } }

/* ── .cat-card* anatomy intentionally REMOVED in Phase 10 ──
   These rules defined a competing card design (absolute-positioned photo with
   text sitting directly on it). They are now owned by assets/css/cca-cards.css,
   which gives every card in the app one anatomy: media -> icon -> title ->
   meta -> footer, on a theme-driven surface. Only the .cat-grid layout above
   remains page-specific. */
</style>

<section class="wrap">
  <div class="sec-head rv">
    <div>
      <span class="eyebrow">Programs</span>
      <h2>Choose Your Path</h2>
    </div>
    <a class="btn btn-ghost btn-sm" href="<?= url('pages/workouts.php') ?>">Explore All Programs →</a>
  </div>

  <div class="cat-grid">
    <?php foreach ($catDefs as $slug => $cat): ?>
    <a class="cat-card rv" href="<?= url('pages/workouts.php?category=' . urlencode($slug)) ?>">
      <div class="cat-card-bg" style="background-image:url('<?= $cat['img'] ?>')"></div>
      <div class="cat-card-overlay"></div>
      
      <!-- Top Corner Tag -->
      <?php if ($catHasPro[$slug]): ?>
        <span class="cat-badge-top cat-badge-pro">🔒 PRO</span>
      <?php else: ?>
        <span class="cat-badge-top cat-badge-free">FREE</span>
      <?php endif; ?>

      <div class="cat-card-body">
        <span class="cat-card-icon"><?= $cat['icon'] ?></span>
        <h3 class="cat-card-title"><?= e($cat['title']) ?></h3>
        <p class="cat-card-desc"><?= e($cat['desc']) ?></p>
        <div class="cat-card-meta">
          <span class="cat-card-count">🔥 <?= $catCounts[$slug] ?> Program<?= $catCounts[$slug] !== 1 ? 's' : '' ?></span>
          <span class="cat-card-cta">Explore →</span>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- 5 Portals Gateway Showcase -->
<style>
/* ── Portal Gateway Cards ── */
.portal-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 18px; }
@media (min-width: 1100px) { .portal-grid { grid-template-columns: repeat(5, 1fr); } }

/* ── .portal-* anatomy intentionally REMOVED in Phase 10 ──
   .portal-card previously hardcoded a dark panel (rgba(15,18,25,.55)) that
   stayed dark in light theme, and .portal-badge coloured its label with the
   raw accent (2.43:1 in light theme). Both now live in
   assets/css/cca-cards.css, which uses --portal-accent for the hairline/icon
   and --portal-accent-text for the label. Only .portal-grid stays here. */
</style>
<section class="wrap">
  <div class="sec-head rv">
    <div>
      <span class="eyebrow">Integrated Architecture</span>
      <h2 class="text-gray-900 dark:text-white">5 Specialized Portals</h2>
    </div>
    <a class="btn btn-ghost btn-sm" href="<?= url('auth/login.php') ?>">Access Portal Login →</a>
  </div>

  <div class="portal-grid">
    <!-- Member Portal (PRIMARY) -->
    <div class="portal-card featured rv" style="--portal-accent: var(--primary); --portal-accent-text: var(--primary-text)">
      <figure class="portal-visual">
        <img src="https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&amp;fit=crop&amp;w=760&amp;q=84" alt="Athlete training in a gym" loading="lazy">
        <figcaption>Personal performance</figcaption>
      </figure>
      <div>
        <h3 class="portal-title">Member Portal</h3>
        <p class="portal-desc">3D workouts, calorie tracking, AI body & food scanners, nutrition log, and trainer appointments.</p>
        <div class="portal-features">
          <span class="portal-feat">3D Workouts</span>
          <span class="portal-feat">AI Scanner</span>
          <span class="portal-feat">Nutrition</span>
          <span class="portal-feat">Shop</span>
        </div>
      </div>
      <div class="portal-footer">
        <span class="portal-badge">Member Access</span>
        <a class="btn btn-fire btn-sm" href="<?= url('auth/login.php') ?>" style="padding:5px 12px;font-size:11px">Enter Portal</a>
      </div>
    </div>

    <!-- Patient Portal (PRIMARY) -->
    <div class="portal-card featured rv" style="--portal-accent: var(--success); --portal-accent-text: var(--success-text)">
      <figure class="portal-visual">
        <img src="https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&amp;fit=crop&amp;w=760&amp;q=84" alt="Doctor reviewing a patient's care plan" loading="lazy">
        <figcaption>Guided health</figcaption>
      </figure>
      <div>
        <h3 class="portal-title">Patient Portal</h3>
        <p class="portal-desc">Doctor-approved disease-safe workout plans, health reminders, and medical consultations.</p>
        <div class="portal-features">
          <span class="portal-feat">Safe Plans</span>
          <span class="portal-feat">Reminders</span>
          <span class="portal-feat">Consults</span>
        </div>
      </div>
      <div class="portal-footer">
        <span class="portal-badge">Patient Access</span>
        <a class="btn btn-ghost btn-sm" href="<?= url('auth/login.php') ?>" style="padding:5px 12px;font-size:11px">Enter Portal</a>
      </div>
    </div>

    <!-- Trainer Portal (SECONDARY) -->
    <div class="portal-card rv" style="--portal-accent: var(--info); --portal-accent-text: var(--info-text)">
      <figure class="portal-visual">
        <img src="https://images.unsplash.com/photo-1538805060514-97d9cc17730c?auto=format&amp;fit=crop&amp;w=760&amp;q=84" alt="Trainer coaching an athlete" loading="lazy">
        <figcaption>Coach command</figcaption>
      </figure>
      <div>
        <h3 class="portal-title">Trainer Portal</h3>
        <p class="portal-desc">Manage client bookings, review performance, and assign custom training plans.</p>
        <div class="portal-features">
          <span class="portal-feat">Bookings</span>
          <span class="portal-feat">Clients</span>
          <span class="portal-feat">Plans</span>
        </div>
      </div>
      <div class="portal-footer">
        <span class="portal-badge">Trainer Access</span>
        <a class="btn btn-ghost btn-sm" href="<?= url('auth/login.php') ?>" style="padding:5px 12px;font-size:11px">Enter Portal</a>
      </div>
    </div>

    <!-- Doctor Portal (SECONDARY) -->
    <div class="portal-card rv" style="--portal-accent: var(--accent-cyan); --portal-accent-text: var(--accent-cyan-text)">
      <figure class="portal-visual">
        <img src="https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&amp;fit=crop&amp;w=760&amp;q=84" alt="Doctor working in a modern clinic" loading="lazy">
        <figcaption>Clinical oversight</figcaption>
      </figure>
      <div>
        <h3 class="portal-title">Doctor Portal</h3>
        <p class="portal-desc">Authorize safe workout plans, review patient health data, and manage medical consultations.</p>
        <div class="portal-features">
          <span class="portal-feat">Approvals</span>
          <span class="portal-feat">Patients</span>
          <span class="portal-feat">Safety</span>
        </div>
      </div>
      <div class="portal-footer">
        <span class="portal-badge">Doctor Access</span>
        <a class="btn btn-ghost btn-sm" href="<?= url('auth/login.php') ?>" style="padding:5px 12px;font-size:11px">Enter Portal</a>
      </div>
    </div>

    <!-- Admin Portal (RESTRICTED) -->
    <div class="portal-card restricted rv" style="--portal-accent: var(--gold); --portal-accent-text: var(--gold-text)">
      <figure class="portal-visual">
        <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&amp;fit=crop&amp;w=760&amp;q=84" alt="Analytics dashboard on a computer screen" loading="lazy">
        <figcaption>Platform control</figcaption>
      </figure>
      <div>
        <h3 class="portal-title">Admin Portal</h3>
        <p class="portal-desc">Full system control — user management, subscriptions, issue reports, and warnings.</p>
        <div class="portal-features">
          <span class="portal-feat">Users</span>
          <span class="portal-feat">Revenue</span>
          <span class="portal-feat">Reports</span>
          <span class="portal-feat">Control</span>
        </div>
      </div>
      <div class="portal-footer">
        <span class="portal-badge">Admin Control</span>
        <a class="btn btn-fire btn-sm" href="<?= url('auth/login.php') ?>" style="padding:5px 12px;font-size:11px">Enter Portal</a>
      </div>
    </div>
  </div>
</section>

<section class="wrap">
  <div class="scan-banner rv">
    <div class="sb-txt">
      <span class="lock-tag">🔒 Pro Feature</span>
      <h2 class="text-gray-900 dark:text-white">AI Body Scanner — <span class="grad-text">Like Google Lens</span> For Your Body</h2>
      <p>Camera apni taraf point karo. AI physique analyze kar ke batata hai gain ya cut, body fat estimate karta hai, aur perfect trainer + workout plan recommend karta hai.</p>
      <div class="hero-cta">
        <?php if ($u && $u['role'] === 'member'): ?>
          <a class="btn btn-fire" href="<?= url('pages/scanner-body.php') ?>">Open Scanner 🔬</a>
          <?php if (!is_pro()): ?><a class="btn btn-ghost" href="<?= url('pages/pricing.php') ?>">Unlock With Pro 💎</a><?php endif; ?>
        <?php else: ?>
          <a class="btn btn-fire" href="<?= url('pages/pricing.php') ?>">Unlock With Pro 💎</a>
          <a class="btn btn-ghost" href="<?= url('auth/register.php') ?>">Join As Member</a>
        <?php endif; ?>
      </div>
    </div>
    <div class="scan-visual">
      <video class="scan-video" id="scanVideo" autoplay muted loop playsinline preload="metadata">
        <source src="<?= url('assets/videos/Athletic_body_training_in_gym_202607221638.mp4') ?>" type="video/mp4">
      </video>
      <div class="scan-corners"><i></i><i></i><i></i><i></i></div><div class="scan-line"></div>
    </div>
  </div>
</section>

<section class="wrap">
  <div class="sec-head rv"><div><span class="eyebrow">Squad</span><h2 class="text-gray-900 dark:text-white">Elite Trainers &amp; Doctors</h2></div><a class="btn btn-ghost btn-sm" href="<?= url('pages/trainers.php') ?>">Meet All →</a></div>
  <div class="grid g4">
    <?php foreach ($trainers as $t): ?>
    <div class="rv bg-white dark:bg-[#121212] rounded-2xl overflow-hidden border border-gray-200 dark:border-white/10 flex flex-col hover:border-brand-accent transition-all">
      <img src="<?= e($t['photo']) ?>" alt="<?= e($t['name']) ?>" class="w-full h-56 object-cover object-top" loading="lazy">
      <div class="p-5 flex flex-col gap-2 flex-1 justify-between">
        <div>
          <h3 class="text-base font-bold text-gray-900 dark:text-white leading-tight"><?= e($t['name']) ?></h3>
          <p class="text-xs text-[#38BDF8] font-semibold uppercase tracking-wider"><?= e($t['specialty']) ?></p>
          <div class="text-xs text-amber-400 font-semibold mt-1">★ ★ ★ ★ ★ <?= e((string)$t['rating']) ?></div>
        </div>
        <div class="border-t border-gray-200 dark:border-white/5 pt-3 mt-2 flex items-center justify-between">
          <a class="btn btn-ghost btn-sm w-full text-center" href="<?= url('pages/trainers.php') ?>">Book</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="wrap">
  <div class="sec-head rv"><div><span class="eyebrow">Know Your Numbers</span><h2 class="text-gray-900 dark:text-white">Quick BMI Check</h2></div><a class="btn btn-ghost btn-sm" href="<?= url('pages/calculators.php') ?>">All Calculators →</a></div>
  <div class="rv grid grid-cols-1 md:grid-cols-2 bg-white dark:bg-[#121212] rounded-2xl overflow-hidden border border-gray-200 dark:border-white/10">
    <div class="p-8 flex flex-col justify-center">
      <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">BMI Calculator</h3>
      <div class="grid grid-cols-2 gap-4">
        <div class="field"><label class="text-gray-600 dark:text-gray-400">Height (cm)</label><input type="number" id="qH" class="w-full p-2 bg-gray-100 dark:bg-[#0A0A0A] border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white rounded-lg outline-none focus:ring-2 focus:ring-brand-accent" value="175"></div>
        <div class="field"><label class="text-gray-600 dark:text-gray-400">Weight (kg)</label><input type="number" id="qW" class="w-full p-2 bg-gray-100 dark:bg-[#0A0A0A] border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white rounded-lg outline-none focus:ring-2 focus:ring-brand-accent" value="72"></div>
      </div>
      <button class="btn btn-fire w-full mt-4" onclick="quickBMI()">Calculate ⚡</button>
      <div class="calc-out mt-4 p-4 bg-gray-100 dark:bg-[#0A0A0A] border border-gray-200 dark:border-white/10 rounded-xl" id="qOut" style="display:none"><b id="qBMI" class="text-gray-900 dark:text-white text-xl">0</b><span id="qCat" class="text-gray-600 dark:text-gray-400 ml-2">—</span></div>
    </div>
    <div class="h-full min-h-[300px]">
      <img src="https://images.unsplash.com/photo-1434682881908-b43d0467b798?w=800&q=60&auto=format&fit=crop" class="w-full h-full object-cover">
    </div>
  </div>
</section>

<?php
$ccaVoiceJs = asset('js/cca-voice.js');
$ccaCoachJs = asset('js/cca-coach.js');
$ccaWardrobeJs = asset('js/cca-wardrobe.js');
$titanJs = asset('js/titan3d.js');
$titanRigJs = asset('js/titan-rig.js');
$extraScripts = <<<HTML
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/DRACOLoader.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>
<script src="{$ccaVoiceJs}"></script>
<script src="{$ccaCoachJs}"></script>
<script src="{$ccaWardrobeJs}"></script>
<script src="{$titanRigJs}"></script>
<script src="{$titanJs}"></script>
<script>
/* Start the 3D trainer AFTER the page has finished loading.
   Running it inline meant the 6.5 MB GLB fetch+parse raced the Tailwind CDN,
   Google Fonts, the hero video and the particle canvas — on a cold cache it
   lost that race and the loader's timeout dropped the homepage to the blocky
   procedural stand-in. Deferring costs nothing visually (the panel already
   shows its own loading state) and lets the model win. */
(function () {
  var go = function () { if (typeof initThree === 'function') initThree('hero3d'); };
  if (document.readyState === 'complete') { go(); }
  else { window.addEventListener('load', go, { once: true }); }
})();
</script>
HTML;
include __DIR__ . '/includes/footer.php';

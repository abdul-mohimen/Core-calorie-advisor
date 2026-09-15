<?php
/* ============ CORE CALORIE ADVISOR — Global Header (navbar + sidebar) ============
   Har page pehle config require kare, phir: $pageTitle='...'; include header.php
   Auth pages (login/register) set $authMinimal = true; for a clean, link-less bar. */
if (!defined('BASE_URL')) { die('Config load nahi hui — page ke top par config/config.php require karo.'); }
/* PHASE I: makes portal_hero() available to every portal page without each one
   requiring it. Defines a function only — emits nothing. */
require_once __DIR__ . '/portal-hero.php';
$u          = current_user();
$role       = $u['role'] ?? null;
$authMinimal = $authMinimal ?? false;
/* ---- Portal scope: a page may set $portal='admin|member|doctor|trainer|patient'
   before including this header to activate its per-portal accent + component CSS. */
$portal = $portal ?? null;
if ($portal !== null && !in_array($portal, ['admin', 'member', 'doctor', 'trainer', 'patient'], true)) $portal = null;

/* ---- Current page (for active-link highlight) ---- */
$curFile = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: 'index.php');
if ($curFile === '' || !str_contains($curFile, '.php')) $curFile = 'index.php';

/* Hero imagery is page-aware but remains presentation-only. Dashboard heroes
   keep their looping video; other portal pages receive a relevant still image
   through --portal-hero-image (consumed by hero-system.css). */
$portalHeroImages = [
    'workouts.php'               => 'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?auto=format&fit=crop&w=1800&q=82',
    'diet-planner.php'           => 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?auto=format&fit=crop&w=1800&q=82',
    'trainers-doctors.php'       => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=1800&q=82',
    'appointments.php'           => 'https://images.unsplash.com/photo-1505751172876-fa1923c5c528?auto=format&fit=crop&w=1800&q=82',
    'billing.php'                => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&w=1800&q=82',
    'client-roster.php'          => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?auto=format&fit=crop&w=1800&q=82',
    'routine-creator.php'        => 'https://images.unsplash.com/photo-1517963879433-6ad2b056d712?auto=format&fit=crop&w=1800&q=82',
    'earnings-payouts.php'       => 'https://images.unsplash.com/photo-1551836022-d5d88e9218df?auto=format&fit=crop&w=1800&q=82',
    'reviews-ratings.php'        => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?auto=format&fit=crop&w=1800&q=82',
    'vitals-log.php'             => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=1800&q=82',
    'prescriptions.php'          => 'https://images.unsplash.com/photo-1471864190281-a93a3070b6de?auto=format&fit=crop&w=1800&q=82',
    'doctors.php'                => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=1800&q=82',
    'patient-queue.php'          => 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&w=1800&q=82',
    'consultations.php'          => 'https://images.unsplash.com/photo-1666214280557-f1b5022eb634?auto=format&fit=crop&w=1800&q=82',
    'financials.php'             => 'https://images.unsplash.com/photo-1554224154-26032ffc0d07?auto=format&fit=crop&w=1800&q=82',
    'user-management.php'        => 'https://images.unsplash.com/photo-1521737711867-e3b97375f902?auto=format&fit=crop&w=1800&q=82',
    'reviews-moderation.php'     => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=1800&q=82',
    'monetization-stripe.php'    => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=1800&q=82',
    'exercise-library-admin.php' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=1800&q=82',
    'appointments-master.php'    => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1800&q=82',
    'ratings.php'                => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=1800&q=82',
];
$portalHeroImage = $portalHeroImages[$curFile] ?? 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=1800&q=82';

/* ============================================================
   NAVIGATION — navbar ($navMenu) and sidebar ($sideMenu) are
   kept DISJOINT (no link appears in both). Primary links live in
   the navbar; tools/secondary/account live in the sidebar.
   ============================================================ */
if (!$u) {
    $navMenu  = [
        ['Home', url('index.php'), 'home'],
        ['Workouts', url('pages/workouts.php'), 'workout'],
        ['Nutrition', url('pages/nutrition.php'), 'nutrition'],
        ['Shop', url('pages/shop.php'), 'shop'],
        ['Trainers', url('pages/trainers.php'), 'trainer'],
        ['Pricing', url('pages/pricing.php'), 'pricing'],
    ];
    $sideMenu = [
        ['Calculators', url('pages/calculators.php'), 'calculator'],
        ['FAQ', url('pages/faq.php'), 'faq'],
        ['Endorsements', url('pages/endorsements.php'), 'star'],
        ['Feedback', url('pages/feedback.php'), 'feedback'],
        ['Privacy Policy', url('pages/privacy-policy.php'), 'privacy'],
        ['Terms', url('pages/terms-and-conditions.php'), 'terms'],
    ];
} else {
    $dash   = ['Dashboard', url("portals/$role.php"), 'dashboard'];
    $comm   = ['Community', url('pages/community.php'), 'community'];
    $report = ['Report Issue', url('pages/report-issue.php'), 'report'];
    $faq    = ['FAQ', url('pages/faq.php'), 'faq'];
    $shop   = ['Shop', url('pages/shop.php'), 'shop'];
    /* Every signed-in role gets these three in its sidebar. Notifications had
       no entry point from any portal at all — the page existed but was only
       reachable from the bell dropdown. Wishlist and Cart are new. */
    $notif  = ['Notifications', url('pages/notifications.php'), 'notification'];
    $wish   = ['Wishlist', url('pages/wishlist.php'), 'wishlist'];
    $cart   = ['Cart', url('pages/cart.php'), 'cart'];
    [$navMenu, $sideMenu] = match ($role) {
        'member' => [
            [$dash, ['Workouts', url('pages/workouts.php'), 'workout'], ['Nutrition', url('pages/nutrition.php'), 'nutrition'], $shop, $comm],
            [$notif, $cart, $wish, ['Trainers', url('pages/trainers.php'), 'trainer'], ['Calculators', url('pages/calculators.php'), 'calculator'], ['Pricing', url('pages/pricing.php'), 'pricing'], $faq, $report],
        ],
        'trainer' => [
            [$dash, ['Directory', url('pages/trainers.php'), 'directory'], $shop, $comm],
            [$notif, $cart, $wish, ['Calculators', url('pages/calculators.php'), 'calculator'], $faq, $report],
        ],
        'doctor' => [
            [$dash, ['Directory', url('pages/trainers.php'), 'directory'], $shop, $comm],
            [$notif, $cart, $wish, ['Calculators', url('pages/calculators.php'), 'calculator'], $faq, $report],
        ],
        'patient' => [
            [$dash, ['Safe Workouts', url('pages/workouts.php'), 'safe'], $shop, $comm],
            [$notif, $cart, $wish, ['Consult Doctor', url('pages/trainers.php'), 'doctor'], ['Calculators', url('pages/calculators.php'), 'calculator'], $faq, $report],
        ],
        'admin' => [
            [$dash, $comm, ['Pricing', url('pages/pricing.php'), 'pricing'], $shop],
            [$notif, $cart, $wish, ['Endorsements', url('pages/endorsements.php'), 'star'], ['Calculators', url('pages/calculators.php'), 'calculator'], $faq],
        ],
        default => [[$dash], []],
    };
}
$platformMenu = $navMenu;
/* The portal section strip used to sit below every hero. It now owns the
   logged-in desktop navbar; platform-discovery links are surfaced as cards on
   each dashboard instead of competing with portal work. */
if ($u && in_array($role, ['member', 'trainer', 'doctor', 'patient', 'admin'], true)) {
    $navMenu = portal_links($role);
}
$unread = $u ? unread_count((int)$u['id']) : 0;

/* ---- Search index (reflects only what this user can reach) ----
   ⚠️ CRITICAL: this include runs INSIDE every page's variable scope. Loop vars here
   MUST NOT reuse page variable names. The old code looped `as $w` / `as $t` / `as $f`
   / `catch $ex`, which OVERWROTE the calling page's $w (current workout!) with the
   alphabetically-last workout row ("Yoga & Stretch") — so workout-detail.php rendered
   the wrong title for EVERY workout and its Start-Session link sent players into the
   Yoga session regardless of what they picked. Prefixed names + unset() prevent it. */
$searchIndex = [];
foreach (array_merge($navMenu, $sideMenu, $platformMenu) as [$hdrNavTitle, $hdrNavLink]) $searchIndex[] = ['t' => $hdrNavTitle, 'k' => 'Page', 'u' => $hdrNavLink, 'img' => null];
if ($u && $role === 'member') {
    $searchIndex[] = ['t' => 'AI Body Scanner', 'k' => 'Pro Tool', 'u' => url('pages/scanner-body.php'), 'img' => null];
    $searchIndex[] = ['t' => 'AI Food Scanner', 'k' => 'Pro Tool', 'u' => url('pages/scanner-food.php'), 'img' => null];
}
try {
    foreach (db()->query('SELECT id,name,image FROM workouts ORDER BY name') as $hdrWorkoutRow)
        $searchIndex[] = ['t' => $hdrWorkoutRow['name'], 'k' => 'Workout', 'u' => url('pages/workout-detail.php?id=' . $hdrWorkoutRow['id']), 'img' => $hdrWorkoutRow['image']];
    foreach (db()->query('SELECT u.name, tp.photo FROM trainer_profiles tp JOIN users u ON u.id=tp.user_id') as $hdrTrainerRow)
        $searchIndex[] = ['t' => $hdrTrainerRow['name'], 'k' => 'Trainer', 'u' => url('pages/trainers.php'), 'img' => $hdrTrainerRow['photo']];
    foreach (db()->query('SELECT name,image FROM foods ORDER BY name') as $hdrFoodRow)
        $searchIndex[] = ['t' => $hdrFoodRow['name'], 'k' => 'Food', 'u' => url('pages/nutrition.php?q=' . urlencode($hdrFoodRow['name'])), 'img' => $hdrFoodRow['image']];
} catch (Throwable $hdrSearchErr) { /* DB na ho to bhi site chale */ }
unset($hdrNavTitle, $hdrNavLink, $hdrWorkoutRow, $hdrTrainerRow, $hdrFoodRow, $hdrSearchErr);

/* ---- Brand mark: ONE source of truth ----
   Phase 15: this used to be a hand-typed inline SVG that duplicated (and drifted
   from) the approved files in assets/brand/. The mark is now READ from
   assets/brand/logo-mark.svg, so there is exactly one definition site in the
   whole codebase — change that file and every header, footer, receipt, invoice
   and auth page follows.
   Read once per request and cached in a static. */
$BRAND_SVG = (static function (): string {
    static $svg = null;
    if ($svg !== null) return $svg;
    $f = __DIR__ . '/../assets/brand/logo-mark.svg';
    $raw = is_file($f) ? (string)file_get_contents($f) : '';
    if ($raw === '') return $svg = '';
    // size it for the navbar and drop the XML/title chrome the inline copy does not need
    $raw = preg_replace('/\s(width|height)="[^"]*"/', '', $raw, 2);
    $raw = str_replace('<svg ', '<svg width="34" height="34" style="width:34px;height:34px;flex-shrink:0" ', $raw);
    return $svg = $raw;
})();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>
  (function() {
    const theme = localStorage.getItem('tf-theme') || 'dark';
    document.documentElement.dataset.theme = theme;
    if (theme === 'dark') {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
  })();
</script>
<title><?= e($pageTitle ?? APP_NAME) ?> — Core Calorie Advisor</title>
<!-- Favicon points at the same file the navbar mark is read from, so the tab
     icon can never drift from the brand again (it used to be a separate
     hand-typed data-URI copy). -->
<link rel="icon" type="image/svg+xml" href="<?= asset('brand/favicon.svg') ?>">
<link rel="manifest" href="<?= url('manifest.webmanifest') ?>">
<meta name="theme-color" content="#FF6B1A">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="CCA">
<link rel="apple-touch-icon" href="<?= asset('brand/logo-mark.svg') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Russo+One&family=Rajdhani:wght@500;600;700&family=Manrope:wght@400;500;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<link rel="stylesheet" href="<?= asset('css/layout.css') ?>"><!-- app shell: single source of truth -->
<link rel="stylesheet" href="<?= asset('css/hero-system.css') ?>"><!-- cinematic hero components -->
<?php if ($portal && is_file(__DIR__ . "/../assets/css/portals/$portal.css")): ?>
<link rel="stylesheet" href="<?= asset("css/portals/$portal.css") ?>"><!-- portal-only components -->
<?php endif; ?>
<!-- Phase 9: maps Tailwind's fixed palette onto the --token system so CDN-styled
     pages follow the theme. Wins by specificity (html-prefixed), not load order. -->
<link rel="stylesheet" href="<?= asset('css/cca-tw-bridge.css') ?>">
<!-- Phase 10: the one card anatomy. Loaded last so it governs every card,
     including the legacy .cat-card / .portal-card / .wk-card definitions. -->
<link rel="stylesheet" href="<?= asset('css/cca-cards.css') ?>">
<!-- Final visual layer: keeps navigation, cards and typography consistently
     sized across legacy pages and the newer portal components. -->
<link rel="stylesheet" href="<?= asset('css/premium-ui.css') ?>">
<script>
window.TF = {
  baseUrl: <?= json_encode(BASE_URL) ?>,
  csrf: <?= json_encode(csrf_token()) ?>,
  loggedIn: <?= is_logged_in() ? 'true' : 'false' ?>,
  role: <?= json_encode($role) ?>,
  name: <?= json_encode($u['name'] ?? null) ?>,
  unread: <?= (int)$unread ?>,
  portal: <?= json_encode($portal) ?>
};
window.TF_INDEX = <?= json_encode($searchIndex, JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    darkMode: 'class',
    corePlugins: { preflight: false },
    theme: {
      extend: {
        colors: {
          'brand': '#FF6B1A',
          'brand-accent': '#38BDF8',
          'brand-gold': '#F59E0B',
          neonCyan: '#FF6B1A',
          neonRed: '#FF3300',
          darkBg: '#0A0A0A',
          darkCard: '#111827',
          darkGlass: 'rgba(255,107,26,0.06)',
          goldAccent: '#FF6B1A'
        },
        fontFamily: {
          inter: ['Inter', 'sans-serif'],
          mono: ['JetBrains Mono', 'monospace']
        },
        keyframes: {
          scan: {
            '0%, 100%': { top: '0%' },
            '50%': { top: '100%' }
          }
        },
        animation: {
          scan: 'scan 2s ease-in-out infinite'
        }
      }
    }
  };
</script>
</head>
<body class="bg-gray-50 text-gray-900 dark:bg-[#0A0A0A] dark:text-white transition-colors duration-200">
<?php
if (empty($_SESSION['cca_splash_shown'])):
    $_SESSION['cca_splash_shown'] = 1;
    include __DIR__ . '/intro.php';
endif;
?>
<div id="app"<?= $authMinimal ? ' class="auth-mode"' : '' ?><?= $portal ? ' data-portal="' . e($portal) . '" style="--portal-hero-image:url(\'' . e($portalHeroImage) . '\')"' : '' ?> data-page="<?= e($curFile) ?>">

<?php if ($authMinimal): ?>
<header class="nav nav-min">
  <a class="brand" href="<?= url('index.php') ?>">
    <?= $BRAND_SVG ?>
    <span class="brand-copy"><span class="brand-name">Core <em>Calorie</em></span><span class="brand-tagline">Performance intelligence</span></span>
  </a>
</header>
<main class="auth-main">
<?= flash_render() ?>
<?php return; endif; ?>

<header class="nav">
  <button class="burger" id="burger" aria-label="Toggle Navigation Menu"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
  <a class="brand" href="<?= $u ? url("portals/$role.php") : url('index.php') ?>">
    <?= $BRAND_SVG ?>
    <span class="brand-copy"><span class="brand-name">Core <em>Calorie</em></span><span class="brand-tagline">Performance intelligence</span></span>
  </a>
  <?php if (!$u): ?>
    <nav class="nav-links" aria-label="Public navigation">
      <?php foreach ($navMenu as [$label, $href]): ?>
        <a href="<?= $href ?>"<?= str_ends_with($href, $curFile) ? ' class="active"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
  <?php endif; ?>
  <div class="nav-spacer"></div>
  <?php if ($u): ?>
    <nav class="nav-links nav-links--portal" aria-label="Portal navigation">
      <?php foreach ($navMenu as [$label, $href]): ?>
        <a href="<?= $href ?>"<?= str_ends_with($href, $curFile) ? ' class="active"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
  <?php else: ?>
    <div class="searchbox nav-public-search" role="search">
      <span class="sicon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="11" cy="11" r="7"/><path d="M20 20 L16.5 16.5"/></svg></span>
      <input id="searchIn" type="search" placeholder="Search workouts, nutrition, trainers &amp; more" autocomplete="off" aria-label="Search Core Calorie Advisor">
      <div class="search-drop" id="searchDrop"><div id="searchResults"></div></div>
    </div>
  <?php endif; ?>
  <!-- Global Theme Toggle -->
  <button id="themeToggle" class="ml-4 p-2 text-gray-400 hover:text-brand-accent transition-colors flex items-center justify-center cursor-pointer" title="Toggle Theme" aria-label="Toggle Theme">
    <svg class="sun-icon hidden dark:block" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <circle cx="12" cy="12" r="5"></circle>
      <line x1="12" y1="1" x2="12" y2="3"></line>
      <line x1="12" y1="21" x2="12" y2="23"></line>
      <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
      <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
      <line x1="1" y1="12" x2="3" y2="12"></line>
      <line x1="21" y1="12" x2="23" y2="12"></line>
      <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
      <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
    </svg>
    <svg class="moon-icon block dark:hidden" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
    </svg>
  </button>
  <?php if ($u): ?>
    <div class="notif-wrap">
      <button class="icon-btn notif-btn" id="notifBtn" title="Notifications" aria-label="Notifications">
        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
        <span class="notif-badge" id="notifBadge"<?= $unread ? '' : ' style="display:none"' ?>><?= $unread > 9 ? '9+' : (int)$unread ?></span>
      </button>
      <div class="notif-drop" id="notifDrop">
        <div class="notif-head"><b>Notifications</b><button class="notif-clear" id="notifClear">Mark all read</button></div>
        <div class="notif-list" id="notifList"><div class="notif-empty">Loading…</div></div>
        <a class="notif-all" href="<?= url('pages/notifications.php') ?>" style="display:block;text-align:center;padding:11px;border-top:1px solid var(--line);font:700 12px var(--tech);letter-spacing:1px;text-transform:uppercase;color:var(--molten);text-decoration:none">View all notifications →</a>
      </div>
    </div>
    <!-- Account menu: the avatar initial was a bare link to the portal, so there
         was no way to sign out from the navbar. It now opens a dropdown. -->
    <div class="acct-wrap">
      <button class="nav-avatar" id="acctBtn" type="button"
              aria-haspopup="menu" aria-expanded="false"
              title="<?= e($u['name']) ?>"><?= e(strtoupper(substr($u['name'], 0, 1))) ?></button>
      <div class="acct-drop" id="acctDrop" role="menu">
        <div class="acct-head">
          <div class="acct-avatar"><?= e(strtoupper(substr($u['name'], 0, 1))) ?></div>
          <div class="acct-id">
            <b><?= e($u['name']) ?></b>
            <span class="acct-mail"><?= e($u['email']) ?></span>
            <span class="acct-role"><?= e(ucfirst($role)) ?> · <?= e(strtoupper($u['plan'])) ?></span>
          </div>
        </div>
        <a class="acct-link" role="menuitem" href="<?= url("portals/$role.php") ?>"><?= icon_img('dashboard') ?> My Portal</a>
        <a class="acct-link" role="menuitem" href="<?= url('pages/profile.php') ?>"><?= icon_img('profile') ?> Profile &amp; Settings</a>
        <a class="acct-link" role="menuitem" href="<?= url('pages/notifications.php') ?>"><?= icon_img('notification') ?> Notifications</a>
        <a class="acct-link acct-link--out" role="menuitem" href="<?= url('auth/logout.php') ?>"><?= icon_img('logout') ?> Logout</a>
      </div>
    </div>
  <?php else: ?>
    <a class="btn btn-fire btn-sm" href="<?= url('auth/login.php') ?>">Login</a>
  <?php endif; ?>
</header>

<div id="overlay"></div>
<aside id="sidebar">
  <div class="sb-head">
    <div class="avatar"><?= $u ? e(strtoupper(substr($u['name'],0,1))) : 'T' ?></div>
    <div><b><?= $u ? e($u['name']) : 'Guest Warrior' ?></b>
      <small><?= $u ? e(ucfirst($role)) . ' · ' . e(strtoupper($u['plan'])) . ' plan' : 'Login to unlock your portal' ?></small></div>
  </div>

  <!-- Primary links: shown in the navbar on desktop, surfaced here only on mobile (no desktop redundancy) -->
  <div class="sb-primary">
    <div class="sb-sec">Menu</div>
    <?php foreach ($navMenu as [$label, $href, $ic]): ?>
      <a class="sb-link<?= str_ends_with($href, $curFile) ? ' active' : '' ?>" href="<?= $href ?>"><?= icon_img($ic) ?> <?= e($label) ?></a>
    <?php endforeach; ?>
  </div>

  <?php if ($u && $role === 'member'): ?>
    <div class="sb-sec">AI Tools · Pro</div>
    <a class="sb-link" href="<?= url('pages/scanner-body.php') ?>"><?= icon_img('scanner') ?> AI Body Scanner <span class="pro"><?= is_pro() ? 'OPEN' : 'PRO' ?></span></a>
    <a class="sb-link" href="<?= url('pages/scanner-food.php') ?>"><?= icon_img('food') ?> AI Food Scanner <span class="pro"><?= is_pro() ? 'OPEN' : 'PRO' ?></span></a>
  <?php endif; ?>

  <?php if ($sideMenu): ?>
    <div class="sb-sec"><?= $u ? 'Quick Access' : 'More' ?></div>
    <?php foreach ($sideMenu as [$label, $href, $ic]): ?>
      <a class="sb-link<?= str_ends_with($href, $curFile) ? ' active' : '' ?>" href="<?= $href ?>"><?= icon_img($ic) ?> <?= e($label) ?></a>
    <?php endforeach; ?>
  <?php endif; ?>

  <div class="sb-sec">Account</div>
  <?php if ($u): ?>
    <a class="sb-link<?= str_ends_with(url('pages/profile.php'), $curFile) ? ' active' : '' ?>" href="<?= url('pages/profile.php') ?>"><?= icon_img('profile') ?> My Profile &amp; Settings</a>
    <a class="sb-link<?= str_ends_with(url('pages/contact.php'), $curFile) ? ' active' : '' ?>" href="<?= url('pages/contact.php') ?>"><?= icon_img('support') ?> Contact Support</a>
    <!-- Identity card, then Logout LAST — name, then email, then the way out. -->
    <div class="sb-acct">
      <div class="sb-acct-avatar"><?= e(strtoupper(substr($u['name'], 0, 1))) ?></div>
      <div class="sb-acct-id">
        <b><?= e($u['name']) ?></b>
        <span><?= e($u['email']) ?></span>
        <span class="sb-acct-role"><?= e(ucfirst($role)) ?> · <?= e(strtoupper($u['plan'])) ?> plan</span>
      </div>
    </div>
    <a class="sb-link sb-link--out" href="<?= url('auth/logout.php') ?>"><?= icon_img('logout') ?> Logout</a>
  <?php else: ?>
    <a class="sb-link<?= str_ends_with(url('pages/contact.php'), $curFile) ? ' active' : '' ?>" href="<?= url('pages/contact.php') ?>"><?= icon_img('support') ?> Contact Support</a>
    <div class="sb-acct sb-acct--guest">
      <div class="sb-acct-avatar">G</div>
      <div class="sb-acct-id">
        <b>Guest</b>
        <span>Not signed in</span>
      </div>
    </div>
    <a class="sb-link sb-link--in" href="<?= url('auth/login.php') ?>"><?= icon_img('login') ?> Login / Register</a>
  <?php endif; ?>
</aside>

<main>
<?= flash_render() ?>
<?php if ($u && $portal): ?>
  <div class="searchbox portal-hero-search" role="search">
    <span class="sicon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="11" cy="11" r="7"/><path d="M20 20 L16.5 16.5"/></svg></span>
    <input id="searchIn" type="search" placeholder="Search workouts, experts, nutrition &amp; more..." autocomplete="off" aria-label="Search Core Calorie Advisor">
    <div class="search-drop" id="searchDrop"><div id="searchResults"></div></div>
  </div>
<?php endif; ?>

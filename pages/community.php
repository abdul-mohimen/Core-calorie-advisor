<?php
require_once dirname(__DIR__) . '/config/config.php';
/* ---- Community is a members-area feature: any logged-in role ---- */
if (!is_logged_in()) { flash('warn', '🔒 Community sirf logged-in warriors ke liye hai — pehle login karo!'); redirect('auth/login.php?next=' . urlencode('pages/community.php')); }

$me = current_user();

/* time-ago helper */
function ago(string $ts): string {
    $d = max(1, time() - strtotime($ts));
    if ($d < 60) return 'just now';
    if ($d < 3600) return floor($d / 60) . 'm ago';
    if ($d < 86400) return floor($d / 3600) . 'h ago';
    if ($d < 604800) return floor($d / 86400) . 'd ago';
    return date('d M', strtotime($ts));
}

$posts = db()->query('SELECT cp.*, u.name, u.role FROM community_posts cp JOIN users u ON u.id = cp.user_id ORDER BY cp.created_at DESC LIMIT 40')->fetchAll();

/* leaderboard — top warriors by total calories burned */
$leaders = db()->query("SELECT u.name, COALESCE(SUM(wl.kcal_burned),0) kcal
                        FROM users u LEFT JOIN workout_logs wl ON wl.user_id = u.id
                        WHERE u.role IN ('member','patient')
                        GROUP BY u.id ORDER BY kcal DESC LIMIT 6")->fetchAll();

$activeCount = (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
$postCount   = (int)db()->query('SELECT COUNT(*) FROM community_posts')->fetchColumn();

$pageTitle = 'Community';
include dirname(__DIR__) . '/includes/header.php';
?>
<!-- Cinematic Hero Section -->
<div class="relative w-full h-[35vh] min-h-[300px] bg-[url('https://images.unsplash.com/photo-1517838277536-f5f99be501cd?w=1600&q=80&auto=format&fit=crop')] bg-cover bg-center border-b border-gray-200 dark:border-white/10 flex items-center justify-center text-center">
  <div class="absolute inset-0 bg-black/80"></div>
  <div class="relative z-10 px-4">
    <span class="text-xs font-bold text-[#F59E0B] tracking-widest uppercase block mb-2">Core Calorie Advisor · Members Community</span>
    <h1 class="text-white text-3xl md:text-5xl font-black tracking-wider uppercase font-['Russo_One',sans-serif]">Warriors' Circle</h1>
    <p class="mt-2 text-xs md:text-sm text-gray-300 max-w-xl mx-auto">Share wins, drop tips, and rise up the leaderboard. Every logged-in CCA trains together here.</p>
  </div>
</div>

<section class="wrap -mt-16 relative z-10 pb-12">

  <div class="comm-grid">
    <!-- FEED -->
    <div class="comm-main">
      <div class="comm-compose">
        <div class="cc-top">
          <div class="comm-ava"><?= e(strtoupper(substr($me['name'], 0, 1))) ?></div>
          <div><b style="font-family:var(--tech);letter-spacing:.4px"><?= e($me['name']) ?></b>
            <div style="color:var(--faint);font-size:12.5px"><?= e(ucfirst($me['role'])) ?> · share with the circle</div></div>
        </div>
        <form id="commForm">
          <?= csrf_field() ?>
          <textarea id="commBody" name="body" maxlength="500" placeholder="What did you forge today, <?= e(explode(' ', $me['name'])[0]) ?>? 🔥"></textarea>
          <div class="cc-actions">
            <span class="cc-hint"><span id="commCount">0</span>/500</span>
            <button class="btn btn-fire btn-sm" type="submit">Post to Community →</button>
          </div>
        </form>
      </div>

      <div id="commFeed">
        <?php foreach ($posts as $p): ?>
        <article class="comm-post" data-id="<?= (int)$p['id'] ?>">
          <div class="cp-head">
            <div class="comm-ava sm"><?= e(strtoupper(substr($p['name'], 0, 1))) ?></div>
            <div class="cp-meta">
              <b><?= e($p['name']) ?> <span class="role-chip"><?= e($p['role']) ?></span></b>
              <small><?= e(ago($p['created_at'])) ?></small>
            </div>
          </div>
          <p><?= nl2br(e($p['body'])) ?></p>
          <div class="cp-foot">
            <span class="cp-like">🔥 <b><?= (int)$p['likes'] ?></b></span>
            <span>💬 Reply</span>
            <span>↗ Share</span>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- SIDE -->
    <aside class="comm-side">
      <div class="comm-box">
        <h4><?= icon_img('community', 24) ?> Circle Stats</h4>
        <div class="lead-row"><span class="lead-name">Active Warriors</span><span class="lead-val"><?= number_format($activeCount) ?></span></div>
        <div class="lead-row"><span class="lead-name">Community Posts</span><span class="lead-val"><?= number_format($postCount) ?></span></div>
      </div>

      <div class="comm-box">
        <h4>🏆 Top Warriors</h4>
        <?php foreach ($leaders as $i => $l): ?>
        <div class="lead-row">
          <span class="lead-rank"><?= $i + 1 ?></span>
          <span class="lead-name"><?= e($l['name']) ?></span>
          <span class="lead-val"><?= number_format((int)$l['kcal']) ?> kcal</span>
        </div>
        <?php endforeach; ?>
        <?php if (!$leaders): ?><p style="color:var(--muted);font-size:14px">No workouts logged yet — be the first!</p><?php endif; ?>
      </div>

      <div class="comm-box">
        <h4>🔥 Active Challenges</h4>
        <a class="comm-tag">#30DayShred</a><a class="comm-tag">#5AMClub</a>
        <a class="comm-tag">#100Pushups</a><a class="comm-tag">#MealPrepSunday</a>
        <a class="comm-tag">#PBWeek</a>
      </div>
    </aside>
  </div>
</section>
<?php
$extraScripts = '<script src="' . asset('js/community.js') . '"></script>';
include dirname(__DIR__) . '/includes/footer.php';
?>

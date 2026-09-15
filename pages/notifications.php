<?php
require_once dirname(__DIR__) . '/config/config.php';
require_login();
$uid = (int)current_user()['id'];

/* Mark-all-read via POST (CSRF-protected), then show the fresh state. */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0')->execute([$uid]);
    flash('ok', 'Sab notifications parh li gayeen.');
    redirect('pages/notifications.php');
}

$filter = get('filter') === 'unread' ? 'unread' : 'all';
$sql = 'SELECT id, type, title, body, link, is_read, created_at FROM notifications WHERE user_id = ?'
     . ($filter === 'unread' ? ' AND is_read = 0' : '')
     . ' ORDER BY created_at DESC LIMIT 100';
$st = db()->prepare($sql);
$st->execute([$uid]);
$notes = $st->fetchAll();
$unread = unread_count($uid);

function notif_icon(string $type): string {
    return match ($type) {
        'success' => '✅',
        'warning' => '⚠️',
        'danger'  => '🚨',
        default   => '🔔',
    };
}
function notif_when(string $ts): string {
    $d = max(1, time() - strtotime($ts));
    if ($d < 60) return 'just now';
    if ($d < 3600) return floor($d / 60) . ' min ago';
    if ($d < 86400) return floor($d / 3600) . ' hours ago';
    if ($d < 604800) return floor($d / 86400) . ' days ago';
    return date('d M Y', strtotime($ts));
}

$pageTitle = 'Notifications';
include dirname(__DIR__) . '/includes/header.php';
?>
<style>
.notif-page { max-width: 980px; margin: 0 auto; padding: 46px 20px 90px; }
.notif-page-head { position:relative; display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap; overflow:hidden; padding:26px 28px; margin-bottom:24px; border:1px solid color-mix(in srgb,var(--primary) 19%,var(--line)); border-radius:20px; background:linear-gradient(120deg,color-mix(in srgb,var(--primary) 12%,var(--surface)),var(--surface) 64%); }
.notif-page-head::after { content:""; position:absolute; right:-45px; top:-72px; width:180px; height:180px; border:1px solid color-mix(in srgb,var(--gold) 42%,transparent); border-radius:50%; box-shadow:0 0 0 25px color-mix(in srgb,var(--primary) 6%,transparent); pointer-events:none; }.notif-page-head > *{position:relative;z-index:1}
.notif-page-head h1 { font:400 clamp(28px,4vw,38px)/1 var(--disp); letter-spacing:-.02em; margin:4px 0 0; }.notif-tabs { display: flex; gap: 8px; padding:4px; border:1px solid var(--line); border-radius:999px; background:color-mix(in srgb,var(--surface) 82%,var(--bg3)); }.notif-tab { padding:9px 17px; border-radius:999px; border:1px solid transparent; background:transparent; color:var(--muted); font:700 12px var(--tech); letter-spacing:.1em; text-transform:uppercase; text-decoration:none; transition:all .2s; }.notif-tab.on,.notif-tab:hover { border-color:color-mix(in srgb,var(--primary) 45%,transparent); color:var(--text); background:color-mix(in srgb,var(--primary) 14%,transparent); }
.notif-row { position:relative; display:flex; gap:16px; align-items:flex-start; padding:19px 20px; border:1px solid var(--line); border-radius:16px; background:linear-gradient(135deg,color-mix(in srgb,var(--surface) 96%,var(--primary) 4%),var(--surface)); margin-bottom:12px; text-decoration:none; color:var(--text); transition:transform .22s cubic-bezier(.22,.8,.3,1),border-color .22s,box-shadow .22s; animation:notifIn .45s cubic-bezier(.22,.8,.3,1) both; }.notif-row:hover { transform:translateY(-3px); border-color:color-mix(in srgb,var(--primary) 45%,var(--border)); box-shadow:var(--shadow-2); }.notif-row.unread { border-left:4px solid var(--molten); background:linear-gradient(90deg,color-mix(in srgb,var(--primary) 11%,var(--surface)),var(--surface) 48%); }.notif-row.unread::after{content:"New";position:absolute;right:17px;top:17px;color:var(--primary-text);font:800 9px var(--tech);letter-spacing:.13em;text-transform:uppercase}.notif-ic { width:42px; height:42px; flex:0 0 42px; display:grid; place-items:center; font-size:20px; line-height:1; border:1px solid color-mix(in srgb,var(--primary) 21%,var(--border)); border-radius:13px; background:color-mix(in srgb,var(--primary) 10%,transparent); }.notif-main { min-width:0; padding-right:42px; }.notif-main b { display:block; font:700 17px/1.25 var(--tech); margin-bottom:5px; letter-spacing:.01em; }.notif-main p { margin:0 0 8px; color:var(--text-2); font-size:14px; line-height:1.55; }.notif-main small { color:var(--text-3); font:700 11px var(--tech); letter-spacing:.08em; text-transform:uppercase; }.notif-none { text-align:center; padding:72px 20px; color:var(--muted); border:1px dashed var(--line); border-radius:18px; background:var(--surface); }
@keyframes notifIn { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
<?php for ($i = 1; $i <= 12; $i++): ?>
.notif-row:nth-child(<?= $i ?>) { animation-delay: <?= $i * 45 ?>ms; }
<?php endfor; ?>
@media(max-width:600px){.notif-page{padding:28px 14px 62px}.notif-page-head{padding:22px 19px;border-radius:17px}.notif-page-head>div:last-child{width:100%;justify-content:space-between}.notif-row{padding:16px}.notif-main b{font-size:16px}.notif-main{padding-right:30px}.notif-row.unread::after{right:14px;top:14px}}
</style>

<section class="notif-page">
  <div class="notif-page-head">
    <div>
      <span class="eyebrow">Inbox</span>
      <h1>Notifications <?php if ($unread): ?><span style="font-size:15px;color:var(--molten);vertical-align:middle">· <?= (int)$unread ?> unread</span><?php endif; ?></h1>
    </div>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
      <div class="notif-tabs">
        <a class="notif-tab <?= $filter === 'all' ? 'on' : '' ?>" href="<?= url('pages/notifications.php') ?>">All</a>
        <a class="notif-tab <?= $filter === 'unread' ? 'on' : '' ?>" href="<?= url('pages/notifications.php?filter=unread') ?>">Unread</a>
      </div>
      <?php if ($unread): ?>
      <form method="post" style="margin:0">
        <?= csrf_field() ?>
        <button class="btn btn-ghost btn-sm" type="submit">Mark all read ✓</button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!$notes): ?>
    <div class="notif-none">
      <div style="font-size:42px;margin-bottom:10px">🔔</div>
      <b>Sab clear hai!</b>
      <p style="margin:6px 0 0"><?= $filter === 'unread' ? 'Koi unread notification nahi.' : 'Abhi tak koi notification nahi aayi.' ?></p>
    </div>
  <?php else: ?>
    <?php foreach ($notes as $n):
      $inner = '<span class="notif-ic">' . notif_icon($n['type']) . '</span>'
             . '<span class="notif-main"><b>' . e($n['title']) . '</b>'
             . ($n['body'] ? '<p>' . e($n['body']) . '</p>' : '')
             . '<small>' . e(notif_when($n['created_at'])) . '</small></span>';
      $cls = 'notif-row' . ($n['is_read'] ? '' : ' unread');
      if ($n['link']): ?>
        <a class="<?= $cls ?>" href="<?= e(url($n['link'])) ?>"><?= $inner ?></a>
      <?php else: ?>
        <div class="<?= $cls ?>"><?= $inner ?></div>
      <?php endif; ?>
    <?php endforeach; ?>
  <?php endif; ?>
</section>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

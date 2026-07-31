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
.notif-page { max-width: 780px; margin: 0 auto; padding: 46px 20px 90px; }
.notif-page-head { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-bottom: 22px; }
.notif-page-head h1 { font-size: clamp(26px, 4vw, 36px); margin: 0; }
.notif-tabs { display: flex; gap: 8px; }
.notif-tab { padding: 8px 18px; border-radius: 999px; border: 1px solid var(--line); background: var(--glass); color: var(--muted); font: 700 12px var(--tech); letter-spacing: 1.2px; text-transform: uppercase; text-decoration: none; transition: all .2s; }
.notif-tab.on, .notif-tab:hover { border-color: var(--molten); color: var(--molten); background: rgba(255,107,26,.08); }
.notif-row { display: flex; gap: 14px; align-items: flex-start; padding: 17px 18px; border: 1px solid var(--line); border-radius: 15px; background: var(--surface); margin-bottom: 10px; text-decoration: none; color: var(--text); transition: transform .22s cubic-bezier(.22,.8,.3,1), border-color .22s, box-shadow .22s; animation: notifIn .45s cubic-bezier(.22,.8,.3,1) both; }
.notif-row:hover { transform: translateY(-2px); border-color: rgba(255,107,26,.4); box-shadow: 0 10px 26px -14px rgba(0,0,0,.4); }
.notif-row.unread { border-left: 3px solid var(--molten); background: linear-gradient(90deg, rgba(255,107,26,.06), var(--surface) 40%); }
.notif-ic { font-size: 22px; line-height: 1; margin-top: 2px; }
.notif-main b { display: block; font-size: 14.5px; margin-bottom: 3px; }
.notif-main p { margin: 0 0 6px; color: var(--muted); font-size: 13.5px; line-height: 1.55; }
.notif-main small { color: var(--faint); font-size: 11.5px; font-family: var(--tech); letter-spacing: .8px; text-transform: uppercase; }
.notif-none { text-align: center; padding: 60px 20px; color: var(--muted); border: 1px dashed var(--line); border-radius: 16px; }
@keyframes notifIn { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
<?php for ($i = 1; $i <= 12; $i++): ?>
.notif-row:nth-child(<?= $i ?>) { animation-delay: <?= $i * 45 ?>ms; }
<?php endfor; ?>
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

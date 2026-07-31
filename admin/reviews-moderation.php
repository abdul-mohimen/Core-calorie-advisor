<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('admin');

/* Handle approve/reject POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify_json()) {
    $rid = (int)($_POST['review_id'] ?? 0);
    $act = $_POST['mod_action'] ?? '';
    if ($rid && in_array($act, ['approved','rejected'])) {
        try { db()->prepare('UPDATE reviews SET status = ? WHERE id = ?')->execute([$act, $rid]); } catch (Throwable $e) {}
    }
    header('Location: ' . url('admin/reviews-moderation.php'));
    exit;
}

$reviews = []; try { $reviews = db()->query("SELECT r.*, u.name reviewer, t.name reviewed FROM reviews r JOIN users u ON u.id = r.user_id JOIN users t ON t.id = r.trainer_id ORDER BY FIELD(r.status,'pending','approved','rejected'), r.created_at DESC")->fetchAll(); } catch (Throwable $e) {}

$portal = 'admin'; $pageTitle = 'Review Moderation';
include dirname(__DIR__) . '/includes/header.php';
$navLinks = [['Dashboard',url('admin/dashboard.php'),nav_icon('dashboard')],['Users',url('admin/user-management.php'),nav_icon('users')],['Monetization',url('admin/monetization-stripe.php'),nav_icon('money')],['Appointments',url('admin/appointments-master.php'),nav_icon('calendar')],['Exercises',url('admin/exercise-library-admin.php'),nav_icon('library')],['Reviews',url('admin/reviews-moderation.php'),nav_icon('moderate')]];
?>
<section class="cca-hero cca-hero--gradient"><div class="cca-hero__overlay"></div><div class="cca-hero__content"><div class="cca-hero__text">
  <div class="cca-hero__breadcrumb"><a href="<?= url('admin/dashboard.php') ?>">Dashboard</a> <span class="sep">›</span> Reviews</div>
  <h1 class="cca-hero__title">Review <span class="grad">Moderation</span></h1>
  <p class="cca-hero__subtitle">Approve or reject member/patient reviews for Trainers and Doctors.</p>
</div></div></section>
<?= portal_nav('admin', $navLinks) ?>
<div class="cca-page-container"><div class="cca-card">
  <h3 class="cca-h3">⭐ All Reviews (<?= count($reviews) ?>)</h3>
  <div class="cca-table-wrap"><table class="cca-table">
    <tr><th>Reviewer</th><th>For</th><th>Rating</th><th>Comment</th><th>Status</th><th>Action</th></tr>
    <?php foreach ($reviews as $r): ?>
    <tr><td><?= e($r['reviewer']) ?></td><td><?= e($r['reviewed']) ?></td>
      <td style="color:var(--warning-text)"><?= str_repeat('★', $r['rating']) ?></td>
      <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap"><?= e($r['comment'] ?? '—') ?></td>
      <td><span class="cca-badge <?= match($r['status'] ?? 'approved'){'approved'=>'cca-badge-success','rejected'=>'cca-badge-danger',default=>'cca-badge-warning'} ?>"><?= ucfirst($r['status'] ?? 'approved') ?></span></td>
      <td>
        <?php if (($r['status'] ?? 'approved') === 'pending'): ?>
        <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="review_id" value="<?= $r['id'] ?>"><input type="hidden" name="mod_action" value="approved"><button class="cca-btn cca-btn-primary" style="padding:4px 10px; font-size:11px; height:auto; min-height:auto">✔</button></form>
        <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="review_id" value="<?= $r['id'] ?>"><input type="hidden" name="mod_action" value="rejected"><button class="cca-btn cca-btn-ghost" style="padding:4px 10px; font-size:11px; height:auto; min-height:auto">✕</button></form>
        <?php else: ?>—<?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table></div>
</div></div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

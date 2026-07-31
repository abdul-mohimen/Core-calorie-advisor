<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('admin');
$appts = db()->query('SELECT a.*, m.name member_name, t.name trainer_name FROM appointments a JOIN users m ON m.id = a.member_id JOIN users t ON t.id = a.trainer_id ORDER BY a.appt_date DESC')->fetchAll();

$portal = 'admin'; $pageTitle = 'Appointments Master';
include dirname(__DIR__) . '/includes/header.php';
$navLinks = [['Dashboard',url('admin/dashboard.php'),nav_icon('dashboard')],['Users',url('admin/user-management.php'),nav_icon('users')],['Monetization',url('admin/monetization-stripe.php'),nav_icon('money')],['Appointments',url('admin/appointments-master.php'),nav_icon('calendar')],['Exercises',url('admin/exercise-library-admin.php'),nav_icon('library')],['Reviews',url('admin/reviews-moderation.php'),nav_icon('moderate')]];
?>
<section class="cca-hero cca-hero--gradient"><div class="cca-hero__overlay"></div><div class="cca-hero__content"><div class="cca-hero__text">
  <div class="cca-hero__breadcrumb"><a href="<?= url('admin/dashboard.php') ?>">Dashboard</a> <span class="sep">›</span> Appointments</div>
  <h1 class="cca-hero__title">Master <span class="grad">Schedule</span></h1>
  <p class="cca-hero__subtitle">Platform-wide appointment overview. Manage disputes, process refunds, and resolve scheduling conflicts.</p>
</div></div></section>
<?= portal_nav('admin', $navLinks) ?>
<div class="cca-page-container"><div class="cca-card">
  <h3 class="cca-h3">📅 All Appointments (<?= count($appts) ?>)</h3>
  <div class="cca-table-wrap"><table class="cca-table">
    <tr><th>ID</th><th>Member</th><th>Provider</th><th>Goal</th><th>Date</th><th>Status</th></tr>
    <?php foreach ($appts as $a): ?>
    <tr><td><?= $a['id'] ?></td><td><?= e($a['member_name']) ?></td><td><?= e($a['trainer_name']) ?></td>
      <td><?= e($a['goal']) ?></td><td><?= date('M d · g:i A', strtotime($a['appt_date'])) ?></td>
      <td><span class="cca-badge <?= $a['status'] === 'accepted' ? 'cca-badge-success' : ($a['status'] === 'rejected' ? 'cca-badge-danger' : 'cca-badge-warning') ?>"><?= ucfirst($a['status']) ?></span></td></tr>
    <?php endforeach; ?>
  </table></div>
</div></div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

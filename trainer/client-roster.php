<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('trainer', 'admin');
$uid = $_SESSION['user']['id'];
$clients = db()->prepare('SELECT DISTINCT u.id, u.name, u.email, u.plan, a.goal, a.status FROM appointments a JOIN users u ON u.id = a.member_id WHERE a.trainer_id = ? ORDER BY u.name');
$clients->execute([$uid]); $roster = $clients->fetchAll();

$portal = 'trainer'; $pageTitle = 'Client Roster';
include dirname(__DIR__) . '/includes/header.php';
$navLinks = [['Dashboard',url('trainer/dashboard.php'),nav_icon('dashboard')],['Client Roster',url('trainer/client-roster.php'),nav_icon('users')],['Routine Creator',url('trainer/routine-creator.php'),nav_icon('dumbbell')],['Earnings',url('trainer/earnings-payouts.php'),nav_icon('money')],['Reviews',url('trainer/reviews-ratings.php'),nav_icon('star')]];
?>
<section class="cca-hero cca-hero--gradient"><div class="cca-hero__overlay"></div><div class="cca-hero__content"><div class="cca-hero__text">
  <div class="cca-hero__breadcrumb"><a href="<?= url('trainer/dashboard.php') ?>">Dashboard</a> <span class="sep">›</span> Clients</div>
  <h1 class="cca-hero__title">Client <span class="grad">Roster</span></h1>
  <p class="cca-hero__subtitle">Manage your assigned members and customize their workout and diet plans.</p>
</div></div></section>
<?= portal_nav('trainer', $navLinks) ?>
<div class="cca-page-container">
  <div class="cca-card">
    <h3 class="cca-h3">👥 My Clients</h3>
    <div class="cca-table-wrap"><table class="cca-table">
      <tr><th>Name</th><th>Email</th><th>Plan</th><th>Goal</th><th>Status</th></tr>
      <?php if (!$roster): ?><tr><td colspan="5" style="color:var(--text-3)">No clients assigned yet.</td></tr><?php endif; ?>
      <?php foreach ($roster as $c): ?>
      <tr><td><b><?= e($c['name']) ?></b></td><td style="color:var(--text-3)"><?= e($c['email']) ?></td>
        <td><span class="cca-badge"><?= strtoupper($c['plan']) ?></span></td><td><?= e($c['goal']) ?></td>
        <td><span class="cca-badge <?= $c['status'] === 'accepted' ? 'cca-badge-success' : 'cca-badge-warning' ?>"><?= ucfirst($c['status']) ?></span></td></tr>
      <?php endforeach; ?>
    </table></div>
  </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

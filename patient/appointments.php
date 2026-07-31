<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('patient', 'admin');
$uid = $_SESSION['user']['id'];
$appts = db()->prepare('SELECT a.*, u.name doctor_name FROM appointments a JOIN users u ON u.id = a.trainer_id WHERE a.member_id = ? ORDER BY a.appt_date DESC');
$appts->execute([$uid]); $appointments = $appts->fetchAll();

$portal = 'patient'; $pageTitle = 'Appointments';
include dirname(__DIR__) . '/includes/header.php';
?>
<section class="cca-hero cca-hero--gradient"><div class="cca-hero__overlay"></div><div class="cca-hero__content"><div class="cca-hero__text">
  <div class="cca-hero__breadcrumb"><a href="<?= url('patient/dashboard.php') ?>">Dashboard</a> <span class="sep">›</span> Appointments</div>
  <h1 class="cca-hero__title">My <span class="grad">Appointments</span></h1>
  <p class="cca-hero__subtitle">Clinical appointment booking and consultation history.</p>
  <div class="cca-hero__actions"><a class="cca-btn cca-btn-primary" style="background:var(--info)" href="<?= url('patient/doctors.php') ?>">🩺 Book New Consult</a></div>
</div></div></section>
<?= portal_nav('patient') ?>
<div class="cca-page-container">
  <div class="cca-card">
    <h3 class="cca-h3">📅 All Appointments</h3>
    <div class="cca-table-wrap"><table class="cca-table">
      <tr><th>Doctor</th><th>Reason</th><th>Date</th><th>Status</th></tr>
      <?php if (!$appointments): ?><tr><td colspan="4" style="color:var(--text-3)">No appointments. Book a consultation to get started.</td></tr><?php endif; ?>
      <?php foreach ($appointments as $a): ?>
      <tr><td><?= e($a['doctor_name']) ?></td><td><?= e($a['goal']) ?></td><td><?= date('M d, Y · g:i A', strtotime($a['appt_date'])) ?></td>
        <td><span class="cca-badge <?= $a['status'] === 'accepted' ? 'cca-badge-success' : ($a['status'] === 'rejected' ? 'cca-badge-danger' : 'cca-badge-warning') ?>"><?= ucfirst($a['status']) ?></span></td></tr>
      <?php endforeach; ?>
    </table></div>
  </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

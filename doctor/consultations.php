<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('doctor', 'admin');
$uid = $_SESSION['user']['id'];
$appts = db()->prepare("SELECT a.*, u.name patient_name FROM appointments a JOIN users u ON u.id = a.member_id WHERE a.trainer_id = ? ORDER BY a.appt_date DESC");
$appts->execute([$uid]); $consults = $appts->fetchAll();

$portal = 'doctor'; $pageTitle = 'Consultations';
include dirname(__DIR__) . '/includes/header.php';
?>
<section class="cca-hero cca-hero--gradient"><div class="cca-hero__overlay"></div><div class="cca-hero__content"><div class="cca-hero__text">
  <div class="cca-hero__breadcrumb"><a href="<?= url('doctor/dashboard.php') ?>">Dashboard</a> <span class="sep">›</span> Consultations</div>
  <h1 class="cca-hero__title">Consultation <span class="grad">History</span></h1>
  <p class="cca-hero__subtitle">Complete log of all patient consultations, diagnosis records, and referral history.</p>
</div></div></section>
<?= portal_nav('doctor') ?>
<div class="cca-page-container"><div class="cca-card">
  <h3 class="cca-h3">📊 All Consultations</h3>
  <div class="cca-table-wrap"><table class="cca-table">
    <tr><th>Patient</th><th>Reason</th><th>Date</th><th>Status</th></tr>
    <?php if (!$consults): ?><tr><td colspan="4" style="color:var(--text-3)">No consultations recorded.</td></tr><?php endif; ?>
    <?php foreach ($consults as $a): ?>
    <tr><td><?= e($a['patient_name']) ?></td><td><?= e($a['goal']) ?></td><td><?= date('M d, Y · g:i A', strtotime($a['appt_date'])) ?></td>
      <td><span class="cca-badge <?= $a['status'] === 'accepted' ? 'cca-badge-success' : ($a['status'] === 'rejected' ? 'cca-badge-danger' : 'cca-badge-warning') ?>"><?= ucfirst($a['status']) ?></span></td></tr>
    <?php endforeach; ?>
  </table></div>
</div></div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

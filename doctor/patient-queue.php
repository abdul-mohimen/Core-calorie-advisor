<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('doctor', 'admin');
$uid = $_SESSION['user']['id'];
$queue = db()->prepare("SELECT a.*, u.name patient_name, u.disease FROM appointments a JOIN users u ON u.id = a.member_id WHERE a.trainer_id = ? ORDER BY FIELD(a.status,'pending','accepted','rejected'), a.appt_date");
$queue->execute([$uid]); $patients = $queue->fetchAll();

$portal = 'doctor'; $pageTitle = 'Patient Queue';
include dirname(__DIR__) . '/includes/header.php';
?>
<section class="cca-hero cca-hero--gradient"><div class="cca-hero__overlay"></div><div class="cca-hero__content"><div class="cca-hero__text">
  <div class="cca-hero__breadcrumb"><a href="<?= url('doctor/dashboard.php') ?>">Dashboard</a> <span class="sep">›</span> Patient Queue</div>
  <h1 class="cca-hero__title">Patient <span class="grad">Queue</span></h1>
  <p class="cca-hero__subtitle">Live waiting room with clinical notes. Accept, reject, or manage patient intake requests.</p>
</div></div></section>
<?= portal_nav('doctor') ?>
<div class="cca-page-container">
  <div class="cca-card">
    <h3 class="cca-h3">📋 Consultation Queue</h3>
    <div class="cca-table-wrap"><table class="cca-table">
      <tr><th>Patient</th><th>Condition</th><th>Reason</th><th>Date</th><th>Status</th><th>Action</th></tr>
      <?php if (!$patients): ?><tr><td colspan="6" style="color:var(--text-3)">No patients in queue.</td></tr><?php endif; ?>
      <?php foreach ($patients as $a): ?>
      <tr>
        <td><b><?= e($a['patient_name']) ?></b></td>
        <td><span class="cca-badge cca-badge-warning"><?= e($a['disease'] ?? '—') ?></span></td>
        <td><?= e($a['goal']) ?></td>
        <td><?= date('M d, g:i A', strtotime($a['appt_date'])) ?></td>
        <td><span class="cca-badge <?= $a['status'] === 'accepted' ? 'cca-badge-success' : ($a['status'] === 'rejected' ? 'cca-badge-danger' : 'cca-badge-warning') ?>"><?= ucfirst($a['status']) ?></span></td>
        <td><?php if ($a['status'] === 'pending'): ?>
          <form method="post" action="<?= url('api/appointment-action.php') ?>" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="action" value="accept"><button class="cca-btn cca-btn-primary" style="padding:4px 10px; font-size:11px; height:auto; min-height:auto">✔</button></form>
          <form method="post" action="<?= url('api/appointment-action.php') ?>" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="action" value="reject"><button class="cca-btn cca-btn-ghost" style="padding:4px 10px; font-size:11px; height:auto; min-height:auto">✕</button></form>
        <?php else: ?>—<?php endif; ?></td>
      </tr>
      <?php endforeach; ?>
    </table></div>
  </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

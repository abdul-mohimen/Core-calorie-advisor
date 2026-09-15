<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('member', 'patient');

$appointmentId = (int)get('id');
$uid = (int)current_user()['id'];
$appointmentListPath = current_user()['role'] === 'patient' ? 'patient/appointments.php' : 'member/appointments.php';
$st = db()->prepare("SELECT a.id, a.goal, a.appt_date, a.fee, a.type, a.status, u.name AS provider_name, u.role AS provider_role
    FROM appointments a JOIN users u ON u.id = a.trainer_id
    WHERE a.id = ? AND a.member_id = ?");
$st->execute([$appointmentId, $uid]);
$appointment = $st->fetch();
if (!$appointment || $appointment['status'] !== 'pending') {
    flash('warn', 'This appointment is not available for payment.');
    redirect($appointmentListPath);
}

$stripeReady = env('STRIPE_SECRET_KEY') !== '';
$sandboxEnabled = sandbox_checkout_enabled();
$pageTitle = 'Appointment Checkout';
include dirname(__DIR__) . '/includes/header.php';
?>
<style>
.checkout-shell{max-width:1060px;margin:0 auto;padding:54px 24px 90px}.checkout-grid{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(300px,.95fr);gap:24px;align-items:start}.checkout-card{padding:30px}.checkout-step{font:700 11px var(--font-tech);letter-spacing:.12em;text-transform:uppercase;color:var(--primary-text)}.checkout-card h1{margin:10px 0;font-size:clamp(26px,3.6vw,38px)}.checkout-card h2{margin:10px 0 18px}.checkout-card p{color:var(--text-2);line-height:1.65}.checkout-list{margin:22px 0}.checkout-row{display:flex;justify-content:space-between;gap:18px;padding:13px 0;border-bottom:1px solid var(--border);color:var(--text-2)}.checkout-row strong{color:var(--text-1);text-align:right}.checkout-total{margin-top:12px;border-top:1px solid var(--border);font-size:18px}.checkout-summary{position:sticky;top:104px}.pay-btn{width:100%;justify-content:center}.sandbox-pill{display:inline-flex;margin:18px 0 0;padding:5px 10px;border-radius:999px;background:color-mix(in srgb,var(--warning) 15%,transparent);border:1px solid color-mix(in srgb,var(--warning) 30%,transparent);font:700 11px var(--font-tech);letter-spacing:.08em;text-transform:uppercase;color:var(--warning-text)}.checkout-lock{font-size:12px;color:var(--text-3)}@media(max-width:820px){.checkout-grid{grid-template-columns:1fr}.checkout-summary{position:static;order:-1}.checkout-shell{padding:32px 16px 64px}}
</style>
<section class="checkout-shell">
  <div class="checkout-grid">
    <article class="checkout-card">
      <span class="checkout-step">Secure appointment checkout</span>
      <h1>Confirm your session</h1>
      <p><?= e($appointment['type'] === 'consultation' ? 'Medical consultation' : 'Training session') ?> with <b><?= e($appointment['provider_name']) ?></b></p>
      <div class="checkout-list">
        <div class="checkout-row"><span>Date</span><strong><?= e(date('D, d M Y · g:i A', strtotime($appointment['appt_date']))) ?></strong></div>
        <div class="checkout-row"><span>Goal</span><strong><?= e($appointment['goal']) ?></strong></div>
      </div>
      <?php if ($stripeReady): ?>
        <form method="post" action="<?= url('api/create-appointment-payment.php') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="appointment_id" value="<?= (int)$appointment['id'] ?>">
          <button class="btn btn-fire pay-btn" type="submit">Continue to secure payment</button>
        </form>
        <p class="checkout-lock">Payment confirmation comes only from Stripe's signed webhook.</p>
      <?php elseif ($sandboxEnabled): ?>
        <span class="sandbox-pill">Local demo checkout</span>
        <p>This development-only flow simulates a confirmed appointment. No payment is processed.</p>
        <form method="post" action="<?= url('api/sandbox-appointment-checkout.php') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="appointment_id" value="<?= (int)$appointment['id'] ?>">
          <button class="btn btn-fire pay-btn" type="submit">Confirm local demo appointment</button>
        </form>
      <?php else: ?>
        <p>Secure appointment billing is not configured yet. Please contact support.</p>
      <?php endif; ?>
    </article>
    <aside class="checkout-card checkout-summary">
      <span class="checkout-step">Order summary</span>
      <h2><?= e($appointment['provider_name']) ?></h2>
      <div class="checkout-row checkout-total"><strong>Total today</strong><strong>$<?= number_format((float)$appointment['fee'], 2) ?></strong></div>
      <a href="<?= url($appointmentListPath) ?>" class="cca-card-link">Back to appointments</a>
    </aside>
  </div>
</section>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

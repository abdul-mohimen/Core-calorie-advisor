<?php
require_once dirname(__DIR__) . '/config/config.php';
require_role('admin');
/* `status` was in this SELECT but the users table has never had such a column —
   in any migration. The `status` ENUM belongs to `reviews`. The query threw
   "Unknown column 'status' in 'field list'" and the whole Admin → Users page
   died with an uncaught PDOException.
   The row rendering below already defaults to 'active' via `$u['status'] ??`,
   so dropping it from the SELECT restores the page with no display change.
   NOTE: the Status badge is therefore cosmetic until a real account-status
   feature (suspend/ban) exists — flagged rather than invented here. */
$users = db()->query('SELECT id, name, email, role, plan, created_at FROM users ORDER BY id DESC')->fetchAll();

$portal = 'admin'; $pageTitle = 'User Management';
include dirname(__DIR__) . '/includes/header.php';
?>
<section class="cca-hero cca-hero--gradient"><div class="cca-hero__overlay"></div><div class="cca-hero__content"><div class="cca-hero__text">
  <div class="cca-hero__breadcrumb"><a href="<?= url('admin/dashboard.php') ?>">Dashboard</a> <span class="sep">›</span> Users</div>
  <h1 class="cca-hero__title">User <span class="grad">Management</span></h1>
  <p class="cca-hero__subtitle">Switch roles, suspend accounts, and manage privileges across the platform.</p>
</div></div></section>
<?= portal_nav('admin') ?>
<div class="cca-page-container">
  <div class="cca-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px">
      <h3 class="cca-h3">👥 All Users (<?= count($users) ?>)</h3>
      <input type="text" id="userSearch" placeholder="Search users..." style="padding:8px 14px; border-radius:10px; border:1px solid var(--border); background:var(--surface); color:var(--text-1); font-size:13px; width:200px">
    </div>
    <div class="cca-table-wrap"><table class="cca-table" id="usersTable">
      <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Plan</th><th>Status</th><th>Joined</th></tr>
      <?php foreach ($users as $u): ?>
      <tr>
        <td><?= $u['id'] ?></td>
        <td><b><?= e($u['name']) ?></b></td>
        <td style="color:var(--text-3)"><?= e($u['email']) ?></td>
        <td><span class="cca-badge" style="font-size:11px; background:<?= match($u['role']){ 'admin'=>'color-mix(in srgb, var(--warning) 15%, transparent)','doctor'=>'color-mix(in srgb, var(--accent-violet) 15%, transparent)','trainer'=>'color-mix(in srgb, var(--success) 15%, transparent)','patient'=>'color-mix(in srgb, var(--info) 15%, transparent)', default=>'color-mix(in srgb, var(--primary) 15%, transparent)' } ?>; color:<?= match($u['role']){ 'admin'=>'var(--warning)','doctor'=>'var(--accent-violet)','trainer'=>'var(--success)','patient'=>'var(--info)', default=>'var(--primary)' } ?>"><?= ucfirst($u['role']) ?></span></td>
        <td><?= strtoupper($u['plan']) ?></td>
        <td><span class="cca-badge <?= ($u['status'] ?? 'active') === 'active' ? 'cca-badge-success' : 'cca-badge-danger' ?>"><?= ucfirst($u['status'] ?? 'active') ?></span></td>
        <td style="font-size:12px; color:var(--text-3)"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </table></div>
  </div>
</div>
<script>
document.getElementById('userSearch')?.addEventListener('input', e => {
  const q = e.target.value.toLowerCase();
  document.querySelectorAll('#usersTable tr:not(:first-child)').forEach(r => { r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none'; });
});
</script>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

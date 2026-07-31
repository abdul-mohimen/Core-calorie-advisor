<?php
require_once dirname(__DIR__) . '/config/config.php';
require_login();

$u = current_user();
$userId = (int)$u['id'];
$error = '';
$success = '';

// Fetch fresh details from DB
$st = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$st->execute([$userId]);
$user = $st->fetch();

if (!$user) {
    redirect('auth/logout.php');
}

// Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = post('action');

    if ($action === 'update_profile') {
        $newName = trim(post('name'));
        $newDisease = post('disease');
        
        if (mb_strlen($newName) < 3) {
            $error = 'Full name must be at least 3 characters.';
        } else {
            $st = db()->prepare('UPDATE users SET name = ?, disease = ? WHERE id = ?');
            $st->execute([$newName, $newDisease ?: null, $userId]);
            
            $_SESSION['user']['name'] = $newName;
            $user['name'] = $newName;
            $user['disease'] = $newDisease ?: null;
            
            $success = 'Profile updated successfully!';
        }
    } elseif ($action === 'change_password') {
        $currentPass = post('current_password');
        $newPass     = post('new_password');
        $confirmPass = post('confirm_password');

        if (!password_verify($currentPass, $user['password_hash'])) {
            $error = 'Current password is incorrect.';
        } elseif (!password_is_strong($newPass)) {
            $error = 'New password must be 12+ characters with uppercase, lowercase and a number.';
        } elseif (!hash_equals($newPass, $confirmPass)) {
            $error = 'New passwords do not match.';
        } else {
            $newHash = password_hash($newPass, PASSWORD_DEFAULT);
            $st = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $st->execute([$newHash, $userId]);
            
            $success = 'Password changed successfully!';
        }
    }
}

// Fetch stats for activity summary
$stW = db()->prepare('SELECT COUNT(*) as count FROM workout_logs WHERE user_id = ?');
$stW->execute([$userId]);
$workoutCount = (int)($stW->fetch()['count'] ?? 0);

$stF = db()->prepare('SELECT COUNT(*) as count FROM food_logs WHERE user_id = ?');
$stF->execute([$userId]);
$foodCount = (int)($stF->fetch()['count'] ?? 0);

$pageTitle = 'My Profile & Account Settings';
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="wrap" style="padding-top:40px;padding-bottom:60px">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:15px;margin-bottom:30px">
        <div>
            <span class="eyebrow">ACCOUNT MANAGEMENT</span>
            <h1 style="font-size:32px;margin-top:6px">User Profile &amp; Settings</h1>
        </div>
        <div>
            <a href="<?= url('portals/' . e($user['role']) . '.php') ?>" class="btn btn-ghost">← Back to <?= ucfirst(e($user['role'])) ?> Portal</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="flash flash-err" style="margin-bottom:20px"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="flash flash-ok" style="margin-bottom:20px"><?= e($success) ?></div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(300px, 1fr));gap:24px">
        <!-- Overview Card -->
        <div class="card" style="display:flex;flex-direction:column;gap:16px">
            <div style="display:flex;align-items:center;gap:16px">
                <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg, var(--fire), var(--purple));display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:bold;color:var(--on-media);box-shadow:0 4px 15px rgba(255,87,34,0.4)">
                    <?= strtoupper(substr(e($user['name']), 0, 1)) ?>
                </div>
                <div>
                    <h3 style="font-size:20px;margin-bottom:4px"><?= e($user['name']) ?></h3>
                    <p style="color:var(--muted);font-size:13px"><?= e($user['email']) ?></p>
                </div>
            </div>

            <hr style="border:0;border-top:1px solid var(--line);margin:8px 0">

            <div style="display:flex;flex-direction:column;gap:10px">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="color:var(--muted);font-size:13px">Portal Role:</span>
                    <span class="pill" style="text-transform:uppercase;font-size:11px"><?= e($user['role']) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="color:var(--muted);font-size:13px">Subscription Plan:</span>
                    <span class="pill <?= in_array($user['plan'], ['pro','elite'], true) ? 'pill-pro' : '' ?>" style="text-transform:uppercase;font-size:11px"><?= e($user['plan']) ?></span>
                </div>
                <?php if (!empty($user['disease'])): ?>
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="color:var(--muted);font-size:13px">Medical Condition:</span>
                    <span style="color:var(--fire);font-size:13px;font-weight:600"><?= e($user['disease']) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <hr style="border:0;border-top:1px solid var(--line);margin:8px 0">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;text-align:center">
                <div style="background:var(--card-bg);padding:12px;border-radius:8px;border:1px solid var(--line)">
                    <div style="font-size:22px;font-weight:bold;color:var(--fire)"><?= $workoutCount ?></div>
                    <div style="font-size:12px;color:var(--muted)">Workouts Completed</div>
                </div>
                <div style="background:var(--card-bg);padding:12px;border-radius:8px;border:1px solid var(--line)">
                    <div style="font-size:22px;font-weight:bold;color:var(--success-text)"><?= $foodCount ?></div>
                    <div style="font-size:12px;color:var(--muted)">Foods Logged</div>
                </div>
            </div>
        </div>

        <!-- Edit Profile Card -->
        <div class="card">
            <h3 style="font-size:18px;margin-bottom:16px;display:flex;align-items:center;gap:8px">✏️ Edit Personal Details</h3>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_profile">
                
                <div class="field" style="margin-bottom:14px">
                    <label>Full Name</label>
                    <input type="text" name="name" required value="<?= e($user['name']) ?>" placeholder="Your Name">
                </div>
                
                <div class="field" style="margin-bottom:14px">
                    <label>Email Address</label>
                    <input type="email" value="<?= e($user['email']) ?>" disabled style="opacity:0.6;cursor:not-allowed">
                    <small style="font-size:11px;color:var(--muted)">Email address cannot be changed directly.</small>
                </div>

                <div class="field" style="margin-bottom:20px">
                    <label>Medical Condition / Health Filter</label>
                    <select name="disease">
                        <option value="">None (Standard Workouts)</option>
                        <option value="Heart Disease" <?= $user['disease'] === 'Heart Disease' ? 'selected' : '' ?>>Heart Disease</option>
                        <option value="Diabetes Type 2" <?= $user['disease'] === 'Diabetes Type 2' ? 'selected' : '' ?>>Diabetes Type 2</option>
                        <option value="High Blood Pressure" <?= $user['disease'] === 'High Blood Pressure' ? 'selected' : '' ?>>High Blood Pressure</option>
                        <option value="Knee Pain" <?= $user['disease'] === 'Knee Pain' ? 'selected' : '' ?>>Knee Pain</option>
                    </select>
                </div>

                <button class="btn btn-fire" type="submit" style="width:100%;justify-content:center">Save Profile Changes</button>
            </form>
        </div>

        <!-- Security & Password Card -->
        <div class="card">
            <h3 style="font-size:18px;margin-bottom:16px;display:flex;align-items:center;gap:8px">🔒 Change Password</h3>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="change_password">

                <div class="field" style="margin-bottom:14px">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required placeholder="Enter current password">
                </div>

                <div class="field" style="margin-bottom:14px">
                    <label>New Password</label>
                    <input type="password" name="new_password" required minlength="12" placeholder="12+ chars, mixed case + numbers">
                </div>

                <div class="field" style="margin-bottom:20px">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" required minlength="12" placeholder="Re-type new password">
                </div>

                <button class="btn btn-ghost" type="submit" style="width:100%;justify-content:center;border-color:var(--line)">Update Password</button>
            </form>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

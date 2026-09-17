<?php
require_once dirname(__DIR__) . '/config/config.php';
csrf_verify();
$token = get('token');
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('mode') === 'request') {
    $email = filter_var(post('email'), FILTER_VALIDATE_EMAIL);
    if ($email) {
        $st = db()->prepare('SELECT id FROM users WHERE email = ?');
        $st->execute([$email]);
        if ($st->fetch()) {
            $rawToken = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $rawToken);
            db()->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$email]);
            db()->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?,?, NOW() + INTERVAL 1 HOUR)')->execute([$email, $tokenHash]);
            $resetUrl = url('auth/forgot-password.php?token=' . $rawToken);
            $headers = 'From: ' . env('MAIL_FROM', 'no-reply@localhost') . "\r\nContent-Type: text/plain; charset=UTF-8";
            @mail($email, APP_NAME . ' password reset', "Use this one-time link within 60 minutes to reset your password:\n\n" . $resetUrl, $headers);
            /* Local XAMPP normally has no mail transport; never show the secret URL in UI. */
            if (APP_ENV === 'development') {
                @file_put_contents(dirname(__DIR__) . '/logs/password-reset.log', date('c') . ' ' . $email . ' ' . $resetUrl . PHP_EOL, FILE_APPEND | LOCK_EX);
            }
        }
        $msg = 'If this email address is registered, a password reset link has been dispatched.';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('mode') === 'reset') {
    $t = post('token'); $pass = post('password'); $passConfirm = post('password_confirm');
    $st = db()->prepare('SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1');
    $st->execute([hash('sha256', $t)]); $row = $st->fetch();
    if (!$row)                  { flash('err', 'The password reset link has expired or is invalid — please request a new link.'); redirect('auth/forgot-password.php'); }
    elseif (!password_is_strong($pass))  { flash('err', 'Use 12+ characters with uppercase, lowercase and a number.'); redirect('auth/forgot-password.php?token=' . urlencode($t)); }
    elseif (!hash_equals($pass, $passConfirm)) { flash('err', 'Password confirmation does not match.'); redirect('auth/forgot-password.php?token=' . urlencode($t)); }
    else {
        db()->prepare('UPDATE users SET password_hash = ? WHERE email = ?')->execute([password_hash($pass, PASSWORD_DEFAULT), $row['email']]);
        db()->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$row['email']]);
        flash('ok', '✔ Password successfully updated — please log in with your new password!');
        redirect('auth/login.php');
    }
}
$pageTitle = 'Forgot Password';
$authMinimal = true;
include dirname(__DIR__) . '/includes/header.php';
?>
<div class="auth-wrap">
  <div class="auth-card">
    <div style="text-align:center"><span class="eyebrow" style="justify-content:center">Recover Access</span>
      <h2 style="font-size:26px;margin-top:12px"><?= $token ? 'New Password' : 'Forgot Password' ?></h2></div>
    <?php if ($msg): ?><div class="flash flash-ok" style="margin:16px 0"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($token): ?>
      <form method="post" style="margin-top:18px">
        <?= csrf_field() ?>
        <input type="hidden" name="mode" value="reset"><input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="field"><label>New Password</label><input type="password" name="password" autocomplete="new-password" minlength="12" required placeholder="12+ chars, upper/lowercase + number"></div>
        <div class="field"><label>Confirm New Password</label><input type="password" name="password_confirm" autocomplete="new-password" minlength="12" required placeholder="Repeat your password"></div>
        <button class="btn btn-fire" style="width:100%;justify-content:center" type="submit">🔑 Save New Password</button>
      </form>
    <?php else: ?>
      <form method="post" style="margin-top:18px">
        <?= csrf_field() ?>
        <input type="hidden" name="mode" value="request">
        <div class="field"><label>Your Email</label><input type="email" name="email" required placeholder="you@example.com"></div>
        <button class="btn btn-fire" style="width:100%;justify-content:center" type="submit">📧 Send Reset Link</button>
      </form>
    <?php endif; ?>
    <div class="auth-foot" style="justify-content:center;margin-top:16px"><a href="<?= url('auth/login.php') ?>">← Back to Login</a></div>
  </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

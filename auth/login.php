<?php
require_once dirname(__DIR__) . '/config/config.php';
if (is_logged_in()) redirect('portals/' . $_SESSION['user']['role'] . '.php');
csrf_verify();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(post('email'), FILTER_VALIDATE_EMAIL);
    $pass  = post('password');
    if (!$email || $pass === '') {
        $error = 'Please provide both email and password.';
    } elseif (login_is_throttled($email)) {
        $error = 'Too many attempts. Please wait 15 minutes before trying again.';
    } else {
        $st = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $st->execute([$email]);
        $user = $st->fetch();
        if (!$user) {
            /* Equalize timing: verify against a dummy hash so "account exists" can't
               be inferred from how fast the error comes back. */
            password_verify($pass, '$2y$10$T85dg3WIbbalE7PL6wHHteaeciwqq98hD7WVPwY/iuIuoyvqmPSGW');
        }
        if ($user && password_verify($pass, $user['password_hash'])) {
            session_regenerate_id(true);
            rotate_csrf_token();
            $_SESSION['user'] = ['id' => (int)$user['id'], 'name' => $user['name'], 'email' => $user['email'],
                                 'role' => $user['role'], 'plan' => $user['plan']];
            flash('ok', '⚡ Welcome back, ' . $user['name'] . '!');
            record_login_attempt($email, true);
            $next = safe_next_path(get('next'));
            if ($next !== '') redirect($next);
            redirect('portals/' . $user['role'] . '.php');
        }
        record_login_attempt((string)$email, false);
        $error = 'Invalid email or password.';
    }
}
$pageTitle = 'Login';
$authMinimal = true;
include dirname(__DIR__) . '/includes/header.php';
?>
<div class="auth-split">
  <aside class="auth-hero" style="background-image:url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=1400&q=75&auto=format&fit=crop')">
    <div class="auth-hero-inner">
      <a class="auth-hero-brand" href="<?= url('index.php') ?>"><?= $BRAND_SVG ?? '' ?> <span>CORE<em>CALORIE</em></span></a>
      <h2>Welcome back to <span>Core Calorie Advisor</span></h2>
      <p>Your 3D coach, AI scanners and elite trainers are waiting. Log in and keep training.</p>
      <ul class="auth-hero-points">
        <li>120+ 3D animated workouts</li>
        <li>AI body &amp; food scanning</li>
        <li>Certified trainers &amp; doctors</li>
      </ul>
    </div>
  </aside>
  <div class="auth-form-side">
    <div class="auth-card">
      <div style="text-align:center"><span class="eyebrow" style="justify-content:center">Secure Authentication</span>
      <h2 style="font-size:26px;margin-top:12px">Access the <span class="grad-text">CCA Network</span></h2></div>
    <div class="auth-tabs">
      <button class="on" type="button">Login</button>
      <button type="button" onclick="location.href='<?= url('auth/register.php?next=' . urlencode(get('next'))) ?>'">Register</button>
    </div>
    <div class="social-row">
      <a class="social-btn" href="<?= url('auth/oauth-google.php') ?>"><svg width="17" height="17" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l3.66-2.84z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/></svg> Google</a>
      <a class="social-btn" href="<?= url('auth/oauth-facebook.php') ?>"><svg width="17" height="17" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.09 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.7 4.53-4.7 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.96.93-1.96 1.89v2.26h3.33l-.53 3.49h-2.8V24C19.61 23.09 24 18.1 24 12.07z"/></svg> Facebook</a>
      <a class="social-btn" href="<?= url('auth/oauth-instagram.php') ?>"><svg width="17" height="17" viewBox="0 0 24 24"><defs><radialGradient id="ig" cx=".3" cy="1"><stop offset="0" stop-color="#FFDD55"/><stop offset=".5" stop-color="#FF543E"/><stop offset="1" stop-color="#C837AB"/></radialGradient></defs><rect width="24" height="24" rx="6" fill="url(#ig)"/><circle cx="12" cy="12" r="4.5" fill="none" stroke="#fff" stroke-width="1.8"/><circle cx="17.5" cy="6.5" r="1.3" fill="#fff"/></svg> Insta</a>
    </div>
    <div class="or-line">OR WITH EMAIL</div>
    <?php if ($error): ?><div class="flash flash-err" style="margin:0 0 14px"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <div class="field"><label>Email</label><input type="email" name="email" placeholder="member@corecalorieadvisor.com" required value="<?= e(post('email')) ?>"></div>
      <div class="field"><label>Password</label><input type="password" name="password" autocomplete="current-password" placeholder="Your password" required></div>
      <button class="btn btn-fire" style="width:100%;justify-content:center" type="submit">⚡ Enter Your Portal</button>
    </form>
    <div class="auth-foot">
      <a href="<?= url('auth/forgot-password.php') ?>">Forgot Password?</a>
      <span>New warrior? <a href="<?= url('auth/register.php') ?>">Register</a></span>
    </div>
    <p style="color:var(--faint);font-size:12px;margin-top:18px;text-align:center">Demo accounts (password sab ka <b>cca123</b>):<br>
      admin@ · member@ · patient@ · trainer@ · doctor@ corecalorieadvisor.com</p>
    </div>
  </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

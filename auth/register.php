<?php
require_once dirname(__DIR__) . '/config/config.php';
if (is_logged_in()) redirect('portals/' . $_SESSION['user']['role'] . '.php');
csrf_verify();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* Anti-bot honeypot protection */
    if (post('website_hp') !== '') {
        sleep(2);
        exit;
    }
    $name  = trim(post('name'));
    $email = filter_var(post('email'), FILTER_VALIDATE_EMAIL);
    $pass  = post('password');
    $passConfirm = post('password_confirm');
    $role  = post('role', 'member');
    $disease = post('disease');
    $termsAgree = post('terms_agree');

    /* Sensitive trainer / doctor portals require an admin-verified account. */
    if (!in_array($role, ['member', 'patient'], true)) $role = 'member';
    if (mb_strlen($name) < 3)      $error = 'Full name must be at least 3 characters.';
    elseif (!$email)               $error = 'Please enter a valid email address.';
    elseif (!password_is_strong($pass)) $error = 'Use 12+ characters with uppercase, lowercase and a number.';
    elseif (!hash_equals($pass, $passConfirm)) $error = 'Password confirmation does not match.';
    elseif (!$termsAgree)          $error = 'Please agree to the Terms & Privacy Policy to create your account.';
    elseif (registration_is_throttled()) $error = 'Too many new accounts from this network. Please try again later.';
    else {
        $st = db()->prepare('SELECT id FROM users WHERE email = ?');
        $st->execute([$email]);
        if ($st->fetch()) {
            $error = 'This email is already registered. Please log in.';
        } else {
            $st = db()->prepare('INSERT INTO users (name, email, password_hash, role, disease) VALUES (?,?,?,?,?)');
            $st->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role, $role === 'patient' ? ($disease ?: null) : null]);
            $id = (int)db()->lastInsertId();
            record_registration();
            $_SESSION['user'] = ['id' => $id, 'name' => $name, 'email' => $email, 'role' => $role, 'plan' => 'free'];
            session_regenerate_id(true);
            rotate_csrf_token();
            flash('ok', '🔥 Account created! Welcome to Core Calorie Advisor, ' . $name . '!');
            $next = safe_next_path(get('next'));
            if ($next !== '') redirect($next);
            redirect('portals/' . $role . '.php');
        }
    }
}
$pageTitle = 'Register';
$authMinimal = true;
include dirname(__DIR__) . '/includes/header.php';
?>
<div class="auth-split">
  <aside class="auth-hero" style="background-image:url('https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=1400&q=75&auto=format&fit=crop')">
    <div class="auth-hero-inner">
      <a class="auth-hero-brand" href="<?= url('index.php') ?>"><?= $BRAND_SVG ?? '' ?> <span>CORE<em>CALORIE</em></span></a>
      <h2>Start your <span>CCA</span> journey</h2>
      <p>Join thousands of warriors training smarter with 3D coaching, AI scans and disease-safe plans.</p>
      <ul class="auth-hero-points">
        <li>Free to start — upgrade anytime</li>
        <li>Personalised 3D workouts</li>
        <li>Doctor-approved safe training</li>
      </ul>
    </div>
  </aside>
  <div class="auth-form-side">
    <div class="auth-card">
      <div style="text-align:center"><span class="eyebrow" style="justify-content:center">Start Your Journey</span>
      <h2 style="font-size:26px;margin-top:12px">Start Your <span class="grad-text">CCA Journey</span></h2></div>
    <div class="auth-tabs">
      <button type="button" onclick="location.href='<?= url('auth/login.php?next=' . urlencode(get('next'))) ?>'">Login</button>
      <button class="on" type="button">Register</button>
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
      <!-- Invisible honeypot field to block automated spambots -->
      <div style="display:none !important;visibility:hidden !important;opacity:0 !important;position:absolute !important;left:-9999px !important;">
        <label for="website_hp">Leave this blank</label>
        <input type="text" name="website_hp" id="website_hp" tabindex="-1" autocomplete="off">
      </div>
      <div class="field"><label>Register As</label>
        <select name="role" id="roleSel" onchange="document.getElementById('disField').style.display=this.value==='patient'?'block':'none'">
          <option value="member">Member</option><option value="patient">Patient</option>
        </select></div>
      <div class="field" id="disField" style="display:none"><label>Disease / Condition</label>
        <select name="disease"><option>Heart Disease</option><option>Diabetes Type 2</option><option>High Blood Pressure</option><option>Knee Pain</option></select></div>
      <div class="field"><label>Full Name</label><input type="text" name="name" placeholder="Mohimen Khan" required value="<?= e(post('name')) ?>"></div>
      <div class="field"><label>Email</label><input type="email" name="email" placeholder="you@example.com" required value="<?= e(post('email')) ?>"></div>
      <div class="field"><label>Password</label>
        <div style="position:relative">
          <input type="password" name="password" id="regPass" autocomplete="new-password" minlength="12" placeholder="12+ chars, upper/lowercase + number" required style="padding-right:46px">
          <button type="button" id="regPassEye" onclick="tfTogglePass()" aria-label="Show password"
                  style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:0;cursor:pointer;font-size:16px;opacity:.65">👁</button>
        </div>
        <div id="capsWarn" style="display:none;font-size:11px;color:var(--primary-text);margin-top:4px">⚠️ Caps Lock is ON</div>
        <div id="pwMeter" style="display:flex;gap:5px;margin-top:8px" aria-hidden="true">
          <span style="flex:1;height:4px;border-radius:2px;background:var(--line);transition:background .25s"></span>
          <span style="flex:1;height:4px;border-radius:2px;background:var(--line);transition:background .25s"></span>
          <span style="flex:1;height:4px;border-radius:2px;background:var(--line);transition:background .25s"></span>
          <span style="flex:1;height:4px;border-radius:2px;background:var(--line);transition:background .25s"></span>
        </div>
        <div id="pwHint" style="font-size:11.5px;color:var(--muted);margin-top:5px"></div>
      </div>
      <div class="field"><label>Confirm Password</label><input type="password" name="password_confirm" id="regPass2" autocomplete="new-password" minlength="12" placeholder="Repeat your password" required>
        <div id="pwMatch" style="font-size:11.5px;margin-top:5px"></div>
      </div>
      <div style="margin:16px 0;font-size:13px;color:var(--muted);display:flex;align-items:flex-start;gap:8px">
        <input type="checkbox" name="terms_agree" id="termsAgree" required style="margin-top:3px;cursor:pointer">
        <label for="termsAgree" style="cursor:pointer;line-height:1.4">I agree to the <a href="<?= url('pages/terms-and-conditions.php') ?>" target="_blank" style="color:var(--fire);text-decoration:underline">Terms of Service</a> &amp; <a href="<?= url('pages/privacy-policy.php') ?>" target="_blank" style="color:var(--fire);text-decoration:underline">Privacy Policy</a>.</label>
      </div>
      <script>
      function tfTogglePass() {
        const i = document.getElementById('regPass'), b = document.getElementById('regPassEye');
        i.type = i.type === 'password' ? 'text' : 'password';
        b.textContent = i.type === 'password' ? '👁' : '🙈';
      }
      (function () {
        const pass = document.getElementById('regPass'), pass2 = document.getElementById('regPass2');
        const capsWarn = document.getElementById('capsWarn');
        const bars = document.querySelectorAll('#pwMeter span'), hint = document.getElementById('pwHint'), match = document.getElementById('pwMatch');
        const colors = ['#e5484d', '#ff9f1a', '#ffd23e', '#30c85e'];
        const labels = ['Weak — length >= 12 chars required', 'Fair — add uppercase & lowercase', 'Good — add numbers & special symbols', 'Strong password ✔'];
        
        pass.addEventListener('keyup', function(e) {
          if (e.getModifierState && e.getModifierState('CapsLock')) {
            capsWarn.style.display = 'block';
          } else {
            capsWarn.style.display = 'none';
          }
        });
        
        function score(v) {
          let s = 0;
          if (v.length >= 12) s++;
          if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++;
          if (/\d/.test(v)) s++;
          if (v.length >= 16 || /[^A-Za-z0-9]/.test(v)) s++;
          return v ? Math.max(1, s) : 0;
        }
        function paint() {
          const s = score(pass.value);
          bars.forEach((b, i) => b.style.background = i < s ? colors[s - 1] : 'var(--line)');
          hint.textContent = pass.value ? labels[s - 1] : '';
          hint.style.color = pass.value ? colors[s - 1] : 'var(--muted)';
          if (pass2.value) {
            const ok = pass.value === pass2.value;
            match.textContent = ok ? 'Passwords match ✔' : 'Passwords do not match';
            match.style.color = ok ? '#30c85e' : '#e5484d';
          } else match.textContent = '';
        }
        pass.addEventListener('input', paint);
        pass2.addEventListener('input', paint);
      })();
      </script>
      <p style="color:var(--muted);font-size:12px;line-height:1.55;margin-top:-4px">Trainer and doctor access is verified by the Core Calorie Advisor admin team.</p>
      <button class="btn btn-fire" style="width:100%;justify-content:center" type="submit">🔥 Create Account</button>
    </form>
    <div class="auth-foot"><span></span><span>Already a CCA? <a href="<?= url('auth/login.php') ?>">Login</a></span></div>
    </div>
  </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

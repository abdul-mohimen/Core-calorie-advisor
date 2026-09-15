<?php
/* ============ CORE CALORIE ADVISOR — Helper Functions ============ */

/* ---- XSS-safe output ---- */
function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* ---- CSRF Protection ---- */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}
function csrf_verify(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $t = $_POST['csrf'] ?? '';
        if (!$t || !hash_equals($_SESSION['csrf'] ?? '', $t)) {
            http_response_code(419);
            die('CSRF token invalid — form dobara submit karo.');
        }
    }
}
function csrf_verify_json(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $t = $_POST['csrf'] ?? '';
        if (!$t || !hash_equals($_SESSION['csrf'] ?? '', $t)) {
            http_response_code(419);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'CSRF invalid']);
            exit;
        }
    }
}

/* ---- Authentication safety helpers ---- */
function rotate_csrf_token(): void { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }

function client_ip(): string {
    /* Do not trust X-Forwarded-For unless a reverse proxy is explicitly configured. */
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
}

function login_attempt_key(string $email): string {
    return hash('sha256', strtolower(trim($email)) . '|' . client_ip());
}

function login_is_throttled(string $email): bool {
    try {
        $st = db()->prepare('SELECT COUNT(*) FROM auth_attempts WHERE attempt_key = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)');
        $st->execute([login_attempt_key($email)]);
        return (int)$st->fetchColumn() >= 5;
    } catch (Throwable $e) {
        /* The application remains usable before the one-time upgrade SQL is applied. */
        return false;
    }
}

function record_login_attempt(string $email, bool $success): void {
    try {
        $key = login_attempt_key($email);
        if ($success) {
            db()->prepare('DELETE FROM auth_attempts WHERE attempt_key = ?')->execute([$key]);
            return;
        }
        db()->prepare('INSERT INTO auth_attempts (attempt_key, attempted_at) VALUES (?, NOW())')->execute([$key]);
        db()->prepare('DELETE FROM auth_attempts WHERE attempted_at < (NOW() - INTERVAL 24 HOUR)')->execute();
    } catch (Throwable $e) { /* migration not applied yet */ }
}

/* Registration spam guard: max 5 new accounts per IP per hour. */
function registration_is_throttled(): bool {
    try {
        $key = hash('sha256', 'register|' . client_ip());
        $st = db()->prepare('SELECT COUNT(*) FROM auth_attempts WHERE attempt_key = ? AND attempted_at > (NOW() - INTERVAL 1 HOUR)');
        $st->execute([$key]);
        return (int)$st->fetchColumn() >= 5;
    } catch (Throwable $e) { return false; }
}
function record_registration(): void {
    try {
        db()->prepare('INSERT INTO auth_attempts (attempt_key, attempted_at) VALUES (?, NOW())')
            ->execute([hash('sha256', 'register|' . client_ip())]);
    } catch (Throwable $e) { /* migration not applied yet */ }
}

function password_is_strong(string $password): bool {
    return strlen($password) >= 12
        && preg_match('/[a-z]/', $password)
        && preg_match('/[A-Z]/', $password)
        && preg_match('/\d/', $password);
}

/** A safe return URL must stay on this app and must never include a host. */
function safe_next_path(string $candidate, string $fallback = ''): string {
    $candidate = trim($candidate);
    if ($candidate === '' || str_contains($candidate, "\0") || str_starts_with($candidate, '//') || str_contains($candidate, '\\')) return $fallback;
    $parts = parse_url($candidate);
    if ($parts === false || isset($parts['scheme']) || isset($parts['host']) || !isset($parts['path'])) return $fallback;
    $path = $parts['path'];
    $basePath = rtrim((string)parse_url(BASE_URL, PHP_URL_PATH), '/');
    if ($basePath !== '' && ($path === $basePath || str_starts_with($path, $basePath . '/'))) {
        $path = substr($path, strlen($basePath)) ?: '/';
    }
    if (str_starts_with($path, '/')) $path = ltrim($path, '/');
    if ($path === '' || str_contains($path, '..')) return $fallback;
    return $path . (isset($parts['query']) ? '?' . $parts['query'] : '');
}

/* ---- Current user / auth ---- */
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function is_logged_in(): bool { return isset($_SESSION['user']); }
function user_plan(): string { return $_SESSION['user']['plan'] ?? 'free'; }
function is_pro(): bool { return in_array(user_plan(), ['pro', 'elite'], true); }

/* Local simulated billing must be deliberately enabled; it is never a
   production fallback when a payment provider is unavailable. */
function sandbox_checkout_enabled(): bool {
    return APP_ENV === 'development'
        && in_array(strtolower(env('ALLOW_SANDBOX_CHECKOUT')), ['1', 'true', 'yes'], true);
}

function require_login(): void {
    if (!is_logged_in()) {
        flash('warn', 'Pehle login karo!');
        redirect('auth/login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
    }
}
function require_role(string ...$roles): void {
    require_login();
    if (!in_array($_SESSION['user']['role'], $roles, true)) {
        flash('warn', 'Is portal ka access aap ke role ke liye nahi hai.');
        redirect('index.php');
    }
}

/* ---- Redirect + flash messages ---- */
function redirect(string $path): never {
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}
function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function flash_render(): string {
    if (empty($_SESSION['flash'])) return '';
    $out = '';
    foreach ($_SESSION['flash'] as [$t, $m]) {
        $out .= '<div class="flash flash-' . e($t) . '">' . e($m) . '</div>';
    }
    unset($_SESSION['flash']);
    return $out;
}

/* ---- Input sanitize ---- */
function post(string $key, string $default = ''): string { return trim((string)($_POST[$key] ?? $default)); }
function get(string $key, string $default = ''): string { return trim((string)($_GET[$key] ?? $default)); }

/* JSON endpoints share one predictable response contract.  Several booking,
   payout and review handlers use these helpers, so defining them centrally
   prevents a runtime fatal before an API response can be sent. */
function json_response(array $payload, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}
function json_error(string $message, int $status = 422): never {
    json_response(['success' => false, 'error' => $message], $status);
}

/* ---- Asset / url helpers ---- */
function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }
function asset(string $path): string {
    $rel  = 'assets/' . ltrim($path, '/');
    $full = dirname(__DIR__) . '/' . $rel;
    // cache-bust on every file change so browsers never serve stale CSS/JS
    $v = is_file($full) ? filemtime($full) : date('Ymd');
    return BASE_URL . '/' . $rel . '?v=' . $v;
}

/* ---- Notifications ---- */
function notify(int $userId, string $title, string $body = '', string $type = 'info', string $link = ''): void {
    try {
        $st = db()->prepare('INSERT INTO notifications (user_id, type, title, body, link) VALUES (?,?,?,?,?)');
        $st->execute([$userId, $type, $title, $body ?: null, $link ?: null]);
    } catch (Throwable $e) { /* notifications table missing? fail silently */ }
}
function unread_count(int $userId): int {
    try {
        $st = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
        $st->execute([$userId]);
        return (int)$st->fetchColumn();
    } catch (Throwable $e) { return 0; }
}

/* ---- Premium image-thumbnail icons (self-contained data-URI, replaces emoji) ---- */
function icon_glyph(string $n): string {
    return match ($n) {
        'home'       => '<path d="M32 14 50 30V50H38V38H26V50H14V30Z" fill="#fff"/>',
        'workout'    => '<g fill="#fff"><rect x="12" y="27" width="6" height="10" rx="2"/><rect x="46" y="27" width="6" height="10" rx="2"/><rect x="20" y="30" width="24" height="4" rx="2"/><rect x="21" y="23" width="5" height="18" rx="2"/><rect x="38" y="23" width="5" height="18" rx="2"/></g>',
        'nutrition'  => '<path d="M32 23c-4-5-12-3-12 7 0 10 8 16 12 16s12-6 12-16c0-10-8-12-12-7Z" fill="#fff"/><path d="M32 23c1-4 4-7 8-7" stroke="#fff" stroke-width="3" fill="none" stroke-linecap="round"/>',
        'trainer'    => '<circle cx="32" cy="25" r="8" fill="#fff"/><path d="M18 48c0-10 8-14 14-14s14 4 14 14Z" fill="#fff"/>',
        'pricing'    => '<path d="M32 14 46 26 32 50 18 26Z" fill="#fff"/>',
        'dashboard'  => '<g fill="#fff"><rect x="16" y="16" width="14" height="14" rx="3"/><rect x="34" y="16" width="14" height="14" rx="3"/><rect x="16" y="34" width="14" height="14" rx="3"/><rect x="34" y="34" width="14" height="14" rx="3"/></g>',
        'community'  => '<g fill="#fff"><circle cx="24" cy="26" r="6"/><circle cx="40" cy="26" r="6"/><path d="M13 47c0-8 6-11 11-11s11 3 11 11Z"/><path d="M29 47c0-8 6-11 11-11s11 3 11 11Z"/></g>',
        'scanner'    => '<g fill="none" stroke="#fff" stroke-width="3.2" stroke-linecap="round"><path d="M18 25v-7h7"/><path d="M46 25v-7h-7"/><path d="M18 39v7h7"/><path d="M46 39v7h-7"/></g><rect x="24" y="29" width="16" height="6" rx="3" fill="#fff"/>',
        'food'       => '<g fill="none" stroke="#fff" stroke-width="3.2" stroke-linecap="round"><path d="M24 15v18M20 15v10M28 15v10M24 33v16"/><path d="M41 15c-4 4-4 13 0 15v19"/></g>',
        'calculator' => '<rect x="18" y="13" width="28" height="38" rx="5" fill="#fff"/><rect x="22" y="17" width="20" height="8" rx="2" fill="#FF6B1A"/><g fill="#FF6B1A"><circle cx="26" cy="33" r="2.4"/><circle cx="32" cy="33" r="2.4"/><circle cx="38" cy="33" r="2.4"/><circle cx="26" cy="41" r="2.4"/><circle cx="32" cy="41" r="2.4"/><circle cx="38" cy="41" r="2.4"/></g>',
        'directory'  => '<g fill="#fff"><circle cx="20" cy="22" r="3.2"/><rect x="28" y="20" width="20" height="4" rx="2"/><circle cx="20" cy="32" r="3.2"/><rect x="28" y="30" width="20" height="4" rx="2"/><circle cx="20" cy="42" r="3.2"/><rect x="28" y="40" width="20" height="4" rx="2"/></g>',
        'doctor'     => '<path d="M28 15h8v10h10v8H36v10h-8V33H18v-8h10Z" fill="#fff"/>',
        'logout'     => '<g fill="none" stroke="#fff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M28 18H18v28h10"/><path d="M37 24 45 32 37 40"/><path d="M45 32H27"/></g>',
        'login'      => '<g fill="none" stroke="#fff" stroke-width="3.2" stroke-linecap="round"><circle cx="26" cy="28" r="7"/><path d="M31 33 45 47M41 43l4-4M37 39l4-4"/></g>',
        'privacy'    => '<path d="M32 14 46 20v12c0 10-6 16-14 18-8-2-14-8-14-18V20Z" fill="#fff"/>',
        'terms'      => '<path d="M22 14h16l8 8v28H22Z" fill="#fff"/><g stroke="#FF6B1A" stroke-width="2.6" stroke-linecap="round"><path d="M27 30h14M27 36h14M27 42h9"/></g>',
        'safe'       => '<path d="M32 46C20 38 16 30 16 24c0-5 4-8 8-8s7 3 8 6c1-3 4-6 8-6s8 3 8 8c0 6-4 14-16 22Z" fill="#fff"/>',
        'faq'        => '<circle cx="32" cy="32" r="18" fill="none" stroke="#fff" stroke-width="3.4"/><path d="M27 27c0-3 2.5-5 5-5s5 2 5 4.6c0 3-4 3.4-5 6" fill="none" stroke="#fff" stroke-width="3.4" stroke-linecap="round"/><circle cx="32" cy="41" r="2.2" fill="#fff"/>',
        'star'       => '<path d="M32 15 37 27 50 28 40 37 43 50 32 43 21 50 24 37 14 28 27 27Z" fill="#fff"/>',
        'feedback'   => '<path d="M16 20h32a3 3 0 0 1 3 3v18a3 3 0 0 1-3 3H28l-9 7v-7h-3a3 3 0 0 1-3-3V23a3 3 0 0 1 3-3Z" fill="#fff"/><g fill="#FF6B1A"><circle cx="26" cy="32" r="2.4"/><circle cx="34" cy="32" r="2.4"/><circle cx="42" cy="32" r="2.4"/></g>',
        'report'     => '<path d="M32 14 52 48H12Z" fill="#fff"/><rect x="30" y="28" width="4" height="11" rx="2" fill="#FF6B1A"/><circle cx="32" cy="43" r="2.4" fill="#FF6B1A"/>',
        'shop'       => '<g fill="none" stroke="#fff" stroke-width="3.6" stroke-linecap="round"><rect x="18" y="22" width="28" height="26" rx="4"/><path d="M25 22v-6a7 7 0 0 1 14 0v6"/></g>',
        default      => '<circle cx="32" cy="32" r="12" fill="#fff"/>',
    };
}
function icon_img(string $name, int $size = 34): string {
    $imgUrl = match ($name) {
        'home'       => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=80&h=80&fit=crop&auto=format&q=80',
        'workout'    => 'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?w=80&h=80&fit=crop&auto=format&q=80',
        'nutrition'  => 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=80&h=80&fit=crop&auto=format&q=80',
        'shop'       => 'https://images.unsplash.com/photo-1553530666-ba11a7da3888?w=80&h=80&fit=crop&auto=format&q=80',
        'trainer'    => 'https://images.unsplash.com/photo-1567013127542-490d757e51fc?w=80&h=80&fit=crop&auto=format&q=80',
        'pricing'    => 'https://images.unsplash.com/photo-1541534741688-6078c6bfb5c5?w=80&h=80&fit=crop&auto=format&q=80',
        'calculator' => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=80&h=80&fit=crop&auto=format&q=80',
        'faq'        => 'https://images.unsplash.com/photo-1507398941214-572c25f4b1dc?w=80&h=80&fit=crop&auto=format&q=80',
        'star'       => 'https://images.unsplash.com/photo-1579202673506-ca3ce28943ef?w=80&h=80&fit=crop&auto=format&q=80',
        'feedback'   => 'https://images.unsplash.com/photo-1557200134-90327ee9fafa?w=80&h=80&fit=crop&auto=format&q=80',
        'privacy'    => 'https://images.unsplash.com/photo-1563986768609-322da13575f3?w=80&h=80&fit=crop&auto=format&q=80',
        'terms'      => 'https://images.unsplash.com/photo-1450133064473-71024230f91b?w=80&h=80&fit=crop&auto=format&q=80',
        'scanner'    => 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=80&h=80&fit=crop&auto=format&q=80',
        'food'       => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=80&h=80&fit=crop&auto=format&q=80',
        'logout'     => 'https://images.unsplash.com/photo-1509822929063-6b6cfc9b42f2?w=80&h=80&fit=crop&auto=format&q=80',
        'login'      => 'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=80&h=80&fit=crop&auto=format&q=80',
        default      => 'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?w=80&h=80&fit=crop&auto=format&q=60',
    };
    return '<img class="ti tf-icon-img" width="' . $size . '" height="' . $size . '" src="' . $imgUrl . '" alt="" style="border-radius:50%;object-fit:cover;border:1.5px solid var(--molten);box-shadow:0 0 10px color-mix(in srgb, var(--primary) 30%, transparent);margin-right:8px;vertical-align:middle;display:inline-block;">';
}

/* ---- Upload validation (MIME check) ---- */
function validate_image_upload(array $file): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > 5 * 1024 * 1024) return null; // 5MB max
    if (!is_uploaded_file($file['tmp_name'] ?? '')) return null;
    $mime = mime_content_type($file['tmp_name']);
    $ok = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    return $ok[$mime] ?? null;
}

/* ---- Platform Settings (key-value store) ---- */
function get_setting(string $key, string $default = ''): string {
    try {
        $st = db()->prepare('SELECT setting_value FROM platform_settings WHERE setting_key = ?');
        $st->execute([$key]);
        $v = $st->fetchColumn();
        return $v !== false ? $v : $default;
    } catch (Throwable $e) { return $default; }
}

function set_setting(string $key, string $value): void {
    try {
        db()->prepare('INSERT INTO platform_settings (setting_key, setting_value) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)')
            ->execute([$key, $value]);
    } catch (Throwable $e) { /* table may not exist yet */ }
}

/* ---- Wallet Helpers ---- */
function get_wallet_balance(int $userId): float {
    try {
        $st = db()->prepare('SELECT balance FROM wallets WHERE user_id = ?');
        $st->execute([$userId]);
        $b = $st->fetchColumn();
        return $b !== false ? (float)$b : 0.00;
    } catch (Throwable $e) { return 0.00; }
}

function credit_wallet(int $userId, float $amount, string $description = ''): void {
    try {
        db()->prepare('INSERT INTO wallets (user_id, balance) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE balance = balance + VALUES(balance)')
            ->execute([$userId, $amount]);
        db()->prepare('INSERT INTO transactions (user_id, type, amount, description, status) VALUES (?, ?, ?, ?, ?)')
            ->execute([$userId, 'payment', $amount, $description, 'completed']);
    } catch (Throwable $e) { /* silent */ }
}

/* ---- Commission Calculator ---- */
function calculate_commission(float $amount, int $providerId = 0): array {
    $rate = (float)get_setting('commission_rate', '20');
    if ($providerId > 0) {
        try {
            $st = db()->prepare('SELECT rating FROM trainer_profiles WHERE user_id = ?');
            $st->execute([$providerId]);
            $rating = (float)$st->fetchColumn();
            $threshold = (float)get_setting('top_rated_threshold', '4.5');
            if ($rating >= $threshold) {
                $rate = (float)get_setting('top_rated_commission_rate', '10');
            }
        } catch (Throwable $e) { /* use default rate */ }
    }
    $commission = round($amount * ($rate / 100), 2);
    $payout = round($amount - $commission, 2);
    return ['commission' => $commission, 'payout' => $payout, 'rate' => $rate];
}

/* ---- Portal Sub-page Navigation ----

   PHASE I: portal_links() is the canonical link set per portal.

   Measured before writing this: all 27 portal_nav() call sites were already in
   sync — every portal resolved to exactly ONE distinct link set. So this is not
   repairing drift, it is removing the duplication that makes drift inevitable:
   adding a portal page currently means editing 5-6 files and silently getting
   away with editing only some.

   Every entry below points at a file that EXISTS on disk. Entries the master
   prompt asks for but which have no page yet — member "Progress", doctor
   "Prescriptions", admin "Wardrobe Admin" (Phase F) and "Platform Settings"
   (Phase L) — are deliberately absent: a nav link to a 404 is a placeholder,
   which rule 0.1.6 forbids. Add them here when the page lands, not before. */
function portal_links(string $portal): array {
    $sets = [
        'member' => [
            ['Dashboard',       url('member/dashboard.php'),        nav_icon('dashboard')],
            ['Workouts',        url('member/workouts.php'),         nav_icon('dumbbell')],
            ['Diet Planner',    url('member/diet-planner.php'),     nav_icon('diet')],
            ['Trainers & Docs', url('member/trainers-doctors.php'), nav_icon('users')],
            ['Appointments',    url('member/appointments.php'),     nav_icon('calendar')],
            ['Billing',         url('member/billing.php'),          nav_icon('billing')],
            /* pages/trainer-studio.php exists and is member-facing, but was
               reachable only via the "Change" link inside the player. */
            ['Trainer Studio',  url('pages/trainer-studio.php'),    nav_icon('star')],
        ],
        'trainer' => [
            ['Dashboard',       url('trainer/dashboard.php'),        nav_icon('dashboard')],
            ['Client Roster',   url('trainer/client-roster.php'),    nav_icon('users')],
            ['Routine Creator', url('trainer/routine-creator.php'),  nav_icon('dumbbell')],
            ['Earnings',        url('trainer/earnings-payouts.php'), nav_icon('money')],
            ['Reviews',         url('trainer/reviews-ratings.php'),  nav_icon('star')],
        ],
        'doctor' => [
            ['Dashboard',     url('doctor/dashboard.php'),     nav_icon('dashboard')],
            ['Patient Queue', url('doctor/patient-queue.php'), nav_icon('queue')],
            ['Consultations', url('doctor/consultations.php'), nav_icon('stethoscope')],
            ['Financials',    url('doctor/financials.php'),    nav_icon('money')],
            ['Ratings',       url('doctor/ratings.php'),       nav_icon('star')],
        ],
        'patient' => [
            ['Dashboard',     url('patient/dashboard.php'),     nav_icon('dashboard')],
            ['Doctors',       url('patient/doctors.php'),       nav_icon('stethoscope')],
            ['Prescriptions', url('patient/prescriptions.php'), nav_icon('prescription')],
            ['Vitals Log',    url('patient/vitals-log.php'),    nav_icon('heart')],
            ['Appointments',  url('patient/appointments.php'),  nav_icon('calendar')],
        ],
        'admin' => [
            ['Dashboard',    url('admin/dashboard.php'),              nav_icon('dashboard')],
            ['Users',        url('admin/user-management.php'),        nav_icon('users')],
            ['Monetization', url('admin/monetization-stripe.php'),    nav_icon('money')],
            ['Appointments', url('admin/appointments-master.php'),    nav_icon('calendar')],
            ['Exercises',    url('admin/exercise-library-admin.php'), nav_icon('library')],
            ['Reviews',      url('admin/reviews-moderation.php'),     nav_icon('moderate')],
        ],
    ];
    return $sets[$portal] ?? [];
}

/* $links stays optional-overridable so a page with a genuine one-off need is not
   forced to fight the helper; omit it and you get the canonical set. */
function portal_nav(string $portal, ?array $links = null): string {
    $links = $links ?? portal_links($portal);
    $curFile = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: 'dashboard.php');

    /* Badge: unread notifications, shown on the portal's Dashboard entry.
       unread_count() is already the single source for this (functions.php:167). */
    $badge = 0;
    if (is_logged_in()) $badge = unread_count((int)($_SESSION['user']['id'] ?? 0));

    $html = '<nav class="cca-portal-nav" role="navigation" aria-label="'
          . e(ucfirst($portal)) . ' section">';
    foreach ($links as [$label, $href, $icon]) {
        $file   = basename(parse_url($href, PHP_URL_PATH) ?: '');
        $active = ($file === $curFile);
        $html  .= '<a href="' . e($href) . '" class="' . ($active ? 'active' : '') . '"'
                . ($active ? ' aria-current="page"' : '') . '>'
                . ($icon ? '<span style="display:inline-flex">' . $icon . '</span>' : '')
                . e($label);
        if ($badge > 0 && $label === 'Dashboard') {
            $html .= '<span class="cca-portal-nav__badge" aria-label="'
                   . (int)$badge . ' unread notifications">' . ($badge > 99 ? '99+' : (int)$badge) . '</span>';
        }
        $html .= '</a>';
    }
    $html .= '</nav>';
    return $html;
}

/* Platform discovery was moved out of the navbar so portal work stays focused.
   Dashboards expose these same destinations as clear, task-oriented cards. */
function portal_dashboard_hub(string $portal): string {
    $sets = [
        'member' => [
            ['Workout Library', 'Train with guided sessions', 'pages/workouts.php', 'dumbbell'],
            ['Nutrition Lab', 'Plan meals and log fuel', 'pages/nutrition.php', 'diet'],
            ['Expert Directory', 'Book a trainer or doctor', 'pages/trainers.php', 'users'],
            ['CCA Shop', 'Browse performance equipment', 'pages/shop.php', 'billing'],
        ],
        'patient' => [
            ['Safe Workouts', 'Explore approved movement plans', 'pages/workouts.php', 'heart'],
            ['Doctor Directory', 'Find the right clinical expert', 'pages/trainers.php', 'stethoscope'],
            ['Nutrition Lab', 'Build sustainable daily habits', 'pages/nutrition.php', 'diet'],
            ['CCA Shop', 'Browse recovery essentials', 'pages/shop.php', 'billing'],
        ],
        'trainer' => [
            ['Expert Directory', 'Keep your public profile visible', 'pages/trainers.php', 'users'],
            ['Community', 'Share expertise with members', 'pages/community.php', 'users'],
            ['CCA Shop', 'Recommend useful equipment', 'pages/shop.php', 'billing'],
            ['Calculators', 'Plan targets with confidence', 'pages/calculators.php', 'chart'],
        ],
        'doctor' => [
            ['Expert Directory', 'Manage your public presence', 'pages/trainers.php', 'stethoscope'],
            ['Community', 'Publish practical health guidance', 'pages/community.php', 'users'],
            ['CCA Shop', 'Review recovery equipment', 'pages/shop.php', 'billing'],
            ['Calculators', 'Check member targets quickly', 'pages/calculators.php', 'chart'],
        ],
        'admin' => [
            ['Community', 'Monitor the member conversation', 'pages/community.php', 'users'],
            ['Pricing', 'Review member plans and offers', 'pages/pricing.php', 'billing'],
            ['CCA Shop', 'Review storefront presentation', 'pages/shop.php', 'billing'],
            ['Calculators', 'Open the platform tools', 'pages/calculators.php', 'chart'],
        ],
    ];
    $items = $sets[$portal] ?? [];
    if (!$items) return '';
    $visuals = [
        'dumbbell'    => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=520&q=82',
        'diet'        => 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?auto=format&fit=crop&w=520&q=82',
        'users'       => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=520&q=82',
        'billing'     => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?auto=format&fit=crop&w=520&q=82',
        'heart'       => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=520&q=82',
        'stethoscope' => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=520&q=82',
        'chart'       => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=520&q=82',
    ];
    $html = '<section class="portal-dashboard-hub" aria-label="Explore Core Calorie Advisor">'
          . '<div class="portal-dashboard-hub__head"><div><span>Next move</span><h2>Keep your momentum moving</h2></div></div>'
          . '<div class="portal-dashboard-hub__grid">';
    foreach ($items as [$title, $desc, $href, $icon]) {
        $visual = $visuals[$icon] ?? $visuals['users'];
        $html .= '<a class="portal-dashboard-hub__card" href="' . e(url($href)) . '">'
              . '<span class="portal-dashboard-hub__visual" style="--hub-image:url(\'' . e($visual) . '\')" aria-hidden="true"></span>'
              . '<span><b>' . e($title) . '</b><small>' . e($desc) . '</small></span>'
              . '<i aria-hidden="true">→</i></a>';
    }
    return $html . '</div></section>';
}

/* ---- SVG Icon Library (compact icons for portal sub-navigation) ---- */
function nav_icon(string $name): string {
    $s = 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"';
    return match ($name) {
        'dashboard'    => "<svg viewBox='0 0 24 24' $s><rect x='3' y='3' width='7' height='7' rx='1'/><rect x='14' y='3' width='7' height='7' rx='1'/><rect x='3' y='14' width='7' height='7' rx='1'/><rect x='14' y='14' width='7' height='7' rx='1'/></svg>",
        'dumbbell'     => "<svg viewBox='0 0 24 24' $s><path d='M4 9v6M7 7.5v9M17 7.5v9M20 9v6M7 12h10'/></svg>",
        'diet'         => "<svg viewBox='0 0 24 24' $s><path d='M12 8c-2-2.5-6-1.6-6 2 0 4 3 8 6 8s6-4 6-8c0-3.6-4-4.5-6-2z'/><path d='M12 8V5c0-1.2 1-2.4 2.4-2.4'/></svg>",
        'users'        => "<svg viewBox='0 0 24 24' $s><circle cx='9' cy='7' r='4'/><path d='M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2'/><circle cx='18' cy='8' r='3'/><path d='M21 21v-2a3 3 0 0 0-2-2.8'/></svg>",
        'calendar'     => "<svg viewBox='0 0 24 24' $s><rect x='3' y='4' width='18' height='18' rx='2'/><path d='M16 2v4M8 2v4M3 10h18'/></svg>",
        'billing'      => "<svg viewBox='0 0 24 24' $s><rect x='2' y='5' width='20' height='14' rx='2'/><path d='M2 10h20'/></svg>",
        'heart'        => "<svg viewBox='0 0 24 24' $s><path d='M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8z'/></svg>",
        'prescription' => "<svg viewBox='0 0 24 24' $s><path d='M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2'/><rect x='8' y='2' width='8' height='4' rx='1'/><path d='M9 14l2 2 4-4'/></svg>",
        'chart'        => "<svg viewBox='0 0 24 24' $s><path d='M3 3v18h18'/><path d='M7 16l4-8 4 4 5-9'/></svg>",
        'money'        => "<svg viewBox='0 0 24 24' $s><path d='M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6'/></svg>",
        'star'         => "<svg viewBox='0 0 24 24' $s><path d='M12 2l3 6.2 6.8 1-5 4.8 1.2 6.8L12 17.5 5.8 20.8 7 14 2 9.2l6.8-1z'/></svg>",
        'shield'       => "<svg viewBox='0 0 24 24' $s><path d='M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z'/></svg>",
        'settings'     => "<svg viewBox='0 0 24 24' $s><circle cx='12' cy='12' r='3'/><path d='M12 22c4.97 0 9-2 9-5.5V5l-9-3-9 3v11.5c0 3.5 4.03 5.5 9 5.5z'/></svg>",
        'queue'        => "<svg viewBox='0 0 24 24' $s><path d='M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2'/><circle cx='9' cy='7' r='4'/><path d='M23 21v-2a4 4 0 0 0-3-3.87'/><path d='M16 3.13a4 4 0 0 1 0 7.75'/></svg>",
        'stethoscope'  => "<svg viewBox='0 0 24 24' $s><path d='M4.8 2.6A2 2 0 0 0 3 5v3a6 6 0 0 0 12 0V5a2 2 0 0 0-1.8-2.4'/><path d='M8 15a6 6 0 0 0 12 0v-3'/><circle cx='20' cy='10' r='2'/></svg>",
        'library'      => "<svg viewBox='0 0 24 24' $s><path d='M4 19.5A2.5 2.5 0 0 1 6.5 17H20'/><path d='M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z'/></svg>",
        'moderate'     => "<svg viewBox='0 0 24 24' $s><circle cx='12' cy='12' r='10'/><path d='M8 12l2 2 4-4'/></svg>",
        default        => "<svg viewBox='0 0 24 24' $s><circle cx='12' cy='12' r='10'/></svg>",
    };
}

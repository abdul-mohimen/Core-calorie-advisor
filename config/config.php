<?php
/* ============ CORE CALORIE ADVISOR — Core Config ============ */
declare(strict_types=1);

/* Keep local XAMPP usable while making production cookies HTTPS-only.
   Computed outside the session block because the header pass below also needs it. */
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? '') === '443');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $isHttps,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

/* ---- Always serve fresh HTML so CSS/JS edits appear instantly (no stale browser cache).
   Static assets are still cache-busted per-file via asset()'s ?v=filemtime. ---- */
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    /* Baseline security headers on every response. Camera stays self-allowed
       because the AI body/food scanners use getUserMedia on this origin. */
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(self), microphone=(), geolocation=()');

    /* ---- Content-Security-Policy ----
       The allow-list is exactly the third parties this app already loads:
         cdnjs / jsdelivr  → three.js r128 + GLTFLoader/DRACOLoader/OrbitControls, ApexCharts
         cdn.tailwindcss.com → the Tailwind runtime (it compiles at runtime, hence 'unsafe-eval')
         fonts.googleapis / fonts.gstatic → Russo One, Rajdhani, Manrope, Inter, JetBrains Mono
         images.unsplash.com → shop + landing photography
         api.anthropic.com → api/scan.php vision calls (server-side, kept for XHR parity)
       'unsafe-inline' is required because inline <script>/style= are used throughout the
       portals; removing it is a separate refactor, not a config change.
       object-src/base-uri/form-action are the parts that actually stop injection pivots. */
    header("Content-Security-Policy: "
        . "default-src 'self'; "
        . "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://cdn.tailwindcss.com https://www.gstatic.com; "
        . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; "
        . "font-src 'self' data: https://fonts.gstatic.com; "
        . "img-src 'self' data: blob: https:; "
        . "media-src 'self' blob:; "
        . "connect-src 'self' blob: data: https://api.anthropic.com https://www.gstatic.com; "
        . "worker-src 'self' blob:; "
        . "object-src 'none'; "
        . "base-uri 'self'; "
        . "form-action 'self'; "
        . "frame-ancestors 'self'");

    /* HSTS only once actually on HTTPS — sending it over plain http:// is ignored by
       browsers and would lock out local XAMPP if the host were ever cached. */
    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

/* ---- Load .env ---- */
$envFile = dirname(__DIR__) . '/.env';
if (is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
        $v = trim($v, " \t\"'");
        if ($k !== '' && getenv($k) === false) { putenv("$k=$v"); $_ENV[$k] = $v; }
    }
}
function env(string $key, string $default = ''): string {
    $v = getenv($key);
    return $v === false ? $default : $v;
}

define('APP_NAME', env('APP_NAME', 'Core Calorie Advisor'));

/* Dynamic BASE_URL auto-detection so it works on any XAMPP folder name (e.g. Core calorie advisor or core_calorie_advisor) */
$envAppUrl = env('APP_URL', '');
if (!empty($_SERVER['HTTP_HOST']) && PHP_SAPI !== 'cli') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $appPath = preg_replace('#/(portals|auth|pages|api|includes|config|member|trainer|doctor|patient|admin)$#i', '', $scriptDir);
    // RFC-3986 encode path segments so folder spaces like 'Core calorie advisor' don't break browser fetch() for GLB/media
    $encodedPath = implode('/', array_map('rawurlencode', explode('/', $appPath)));
    $baseUrl = $scheme . '://' . $host . rtrim($encodedPath, '/');
} else {
    $baseUrl = $envAppUrl !== '' ? str_replace(' ', '%20', $envAppUrl) : 'http://localhost/Core%20calorie%20advisor';
}

define('BASE_URL', rtrim($baseUrl, '/'));
define('APP_ENV',  env('APP_ENV', 'production'));

if (APP_ENV === 'development') { error_reporting(E_ALL); ini_set('display_errors', '1'); }
else { error_reporting(0); ini_set('display_errors', '0'); }

require_once __DIR__ . '/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

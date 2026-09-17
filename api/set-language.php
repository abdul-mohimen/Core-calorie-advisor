<?php
/* ============ CORE CALORIE ADVISOR — Language Switcher API ============ */
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/i18n.php';

$langs = get_supported_languages();
$code  = strtolower(trim((string)($_REQUEST['lang'] ?? 'en')));

if (!isset($langs[$code])) {
    $code = 'en';
}

$_SESSION['cca_lang'] = $code;
@setcookie('cca_lang', $code, [
    'expires'  => time() + 31536000,
    'path'     => '/',
    'samesite' => 'Lax',
    'httponly' => false,
]);

// If user is logged in, optionally update their profile preference if column exists
if (is_logged_in()) {
    try {
        db()->prepare('UPDATE users SET preferred_lang = ? WHERE id = ?')->execute([$code, $_SESSION['user']['id']]);
    } catch (Throwable $ignore) {}
}

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'ok'   => true,
        'lang' => $code,
        'info' => $langs[$code],
        'dir'  => $langs[$code]['dir'],
    ]);
    exit;
}

$ref = $_SERVER['HTTP_REFERER'] ?? '';
if ($ref !== '' && str_contains($ref, BASE_URL)) {
    // strip existing ?lang= from referer to prevent URL pollution
    $cleanRef = preg_replace('/([?&])lang=[^&]+(&|$)/', '$1', $ref);
    $cleanRef = rtrim($cleanRef, '?&');
    header('Location: ' . $cleanRef);
    exit;
}

header('Location: ' . BASE_URL . '/index.php');
exit;

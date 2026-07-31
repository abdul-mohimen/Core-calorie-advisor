<?php
require_once dirname(__DIR__) . '/config/config.php';
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'] ?? '/', $params['domain'] ?? '', (bool)($params['secure'] ?? false), (bool)($params['httponly'] ?? true));
}
session_destroy();
session_start();
flash('ok', 'Logout ho gaya — phir milen ge, CCA! 👋');
redirect('index.php');

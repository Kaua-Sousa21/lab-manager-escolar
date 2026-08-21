<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_name('labmanager_school_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/functions.php';

if (isset($_SESSION['user_id'])) {
    $now = time();
    if (isset($_SESSION['last_activity']) && $now - (int) $_SESSION['last_activity'] > SESSION_IDLE_TIMEOUT) {
        session_unset();
        session_destroy();
        session_start();
        flash('warning', 'Sua sessão expirou por inatividade. Entre novamente.');
    } else {
        $_SESSION['last_activity'] = $now;
        if (!isset($_SESSION['last_regeneration']) || $now - (int) $_SESSION['last_regeneration'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = $now;
        }
    }
}

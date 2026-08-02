<?php
/* ---------------------------------------------------------------------
   /admin — the door.

   One password, hashed, stored in a file ABOVE the web root so no URL
   can reach it even if PHP stops executing. Sessions are cookie-only,
   every write carries a CSRF token, and failed logins are rate-limited
   per IP so the password cannot be ground down.
   --------------------------------------------------------------------- */

declare(strict_types=1);

require_once __DIR__ . '/../lib/config.php';

const SESSION_NAME     = 'reels_admin';
const SESSION_IDLE     = 7200;   // sign out after 2h of no activity
const LOGIN_MAX_TRIES  = 6;      // per window, per IP
const LOGIN_WINDOW     = 900;    // 15 minutes

/**
 * Read the private config. Returns null when the site has not been set
 * up yet, which is what sends the admin to setup.php.
 *
 * @return array{password_hash:string}|null
 */
function admin_config(): ?array
{
    if (!is_file(PRIVATE_CONFIG)) {
        return null;
    }
    $config = require PRIVATE_CONFIG;
    if (!is_array($config) || empty($config['password_hash'])) {
        return null;
    }
    return $config;
}

/** Start the admin session with hardened cookie flags. */
function admin_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE . '/admin',
        'httponly' => true,
        'secure'   => $https,
        'samesite' => 'Strict',
    ]);
    session_start();
}

/** True when the current session is a signed-in, non-idle admin. */
function admin_logged_in(): bool
{
    admin_session_start();
    if (empty($_SESSION['admin'])) {
        return false;
    }
    if (time() - (int) ($_SESSION['seen'] ?? 0) > SESSION_IDLE) {
        admin_logout();
        return false;
    }
    $_SESSION['seen'] = time();
    return true;
}

/** Send anyone who is not signed in back to the login screen. */
function admin_require_login(): void
{
    if (!admin_logged_in()) {
        header('Location: ' . BASE . '/admin/');
        exit;
    }
}

function admin_login(): void
{
    admin_session_start();
    session_regenerate_id(true);      // no session fixation
    $_SESSION['admin'] = true;
    $_SESSION['seen']  = time();
    $_SESSION['csrf']  = bin2hex(random_bytes(32));
}

function admin_logout(): void
{
    admin_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'httponly' => true,
            'secure'   => $p['secure'],
            'samesite' => 'Strict',
        ]);
    }
    session_destroy();
}

/* ------------------------------------------------------------------ */
/* CSRF                                                                */
/* ------------------------------------------------------------------ */

function csrf_token(): string
{
    admin_session_start();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Abort the request unless the POST carries this session's token. */
function csrf_check(): void
{
    admin_session_start();
    $sent = (string) ($_POST['csrf'] ?? '');
    if ($sent === '' || empty($_SESSION['csrf'])
        || !hash_equals((string) $_SESSION['csrf'], $sent)) {
        http_response_code(400);
        exit('Bad request.');
    }
}

/* ------------------------------------------------------------------ */
/* Login rate limit                                                    */
/* ------------------------------------------------------------------ */

function throttle_file(): string
{
    return sys_get_temp_dir() . '/reels-admin-throttle.json';
}

/** Failed attempts from this IP inside the current window. */
function login_attempts(): int
{
    $data = @json_decode((string) @file_get_contents(throttle_file()), true);
    if (!is_array($data)) {
        return 0;
    }
    $entry = $data[login_ip()] ?? null;
    if (!is_array($entry) || (int) ($entry['at'] ?? 0) < time() - LOGIN_WINDOW) {
        return 0;
    }
    return (int) ($entry['n'] ?? 0);
}

function login_record_failure(): void
{
    $data = @json_decode((string) @file_get_contents(throttle_file()), true);
    if (!is_array($data)) {
        $data = [];
    }
    // Drop stale entries so the file cannot grow without bound.
    foreach ($data as $ip => $entry) {
        if ((int) ($entry['at'] ?? 0) < time() - LOGIN_WINDOW) {
            unset($data[$ip]);
        }
    }
    $ip = login_ip();
    $n  = (int) ($data[$ip]['n'] ?? 0);
    $data[$ip] = ['n' => $n + 1, 'at' => time()];
    @file_put_contents(throttle_file(), json_encode($data), LOCK_EX);
}

function login_clear_failures(): void
{
    $data = @json_decode((string) @file_get_contents(throttle_file()), true);
    if (is_array($data)) {
        unset($data[login_ip()]);
        @file_put_contents(throttle_file(), json_encode($data), LOCK_EX);
    }
}

/** Remote address only — proxy headers are attacker-controlled. */
function login_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

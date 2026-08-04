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
const SESSION_ROTATE   = 900;    // refresh the session id every 15 minutes

/** Security headers also apply on hosts/local previews that ignore .htaccess. */
function admin_security_headers(): void
{
    if (headers_sent()) {
        return;
    }
    header("Content-Security-Policy: default-src 'none'; script-src 'self'; script-src-attr 'none'; "
         . "style-src 'self'; style-src-attr 'none'; img-src 'self' data:; media-src 'self'; "
         . "connect-src 'self'; object-src 'none'; frame-src 'none'; frame-ancestors 'none'; "
         . "base-uri 'none'; form-action 'self'; require-trusted-types-for 'script'");
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');
    header('Cache-Control: no-store, max-age=0');
    header('Pragma: no-cache');
    header('X-Robots-Tag: noindex, nofollow');
}

admin_security_headers();

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

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_trans_sid', '0');
    session_cache_limiter('nocache');
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
    $agent = hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    if (!hash_equals((string) ($_SESSION['agent'] ?? ''), $agent)) {
        admin_logout();
        return false;
    }
    if (time() - (int) ($_SESSION['rotated'] ?? 0) > SESSION_ROTATE) {
        session_regenerate_id(true);
        $_SESSION['rotated'] = time();
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
    $_SESSION['rotated'] = time();
    $_SESSION['agent'] = hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
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
    return sys_get_temp_dir() . '/reels-admin-throttle-'
        . substr(hash('sha256', __DIR__), 0, 16) . '.json';
}

/** Failed attempts from this IP inside the current window. */
function login_attempts(): int
{
    $handle = @fopen(throttle_file(), 'c+');
    if ($handle === false) {
        return LOGIN_MAX_TRIES;
    }
    flock($handle, LOCK_SH);
    $data = json_decode((string) stream_get_contents($handle), true);
    flock($handle, LOCK_UN);
    fclose($handle);
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
    $handle = @fopen(throttle_file(), 'c+');
    if ($handle === false) {
        return;
    }
    flock($handle, LOCK_EX);
    rewind($handle);
    $data = json_decode((string) stream_get_contents($handle), true);
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
    rewind($handle);
    ftruncate($handle, 0);
    fwrite($handle, (string) json_encode($data));
    fflush($handle);
    @chmod(throttle_file(), 0600);
    flock($handle, LOCK_UN);
    fclose($handle);
}

function login_clear_failures(): void
{
    $handle = @fopen(throttle_file(), 'c+');
    if ($handle === false) {
        return;
    }
    flock($handle, LOCK_EX);
    rewind($handle);
    $data = json_decode((string) stream_get_contents($handle), true);
    if (!is_array($data)) {
        $data = [];
    }
    unset($data[login_ip()]);
    rewind($handle);
    ftruncate($handle, 0);
    fwrite($handle, (string) json_encode($data));
    fflush($handle);
    @chmod(throttle_file(), 0600);
    flock($handle, LOCK_UN);
    fclose($handle);
}

/** Remote address only — proxy headers are attacker-controlled. */
function login_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

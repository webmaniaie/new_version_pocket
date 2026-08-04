<?php
/* ---------------------------------------------------------------------
   /admin/setup.php — set the password, once.

   This page stops working the moment a password exists, so it cannot be
   used to take the blog over later. Before that it is guarded by the
   install token in admin/.install-token: a dotfile, which the site's
   .htaccess refuses to serve, so it is readable in File Manager and
   nowhere else.
   --------------------------------------------------------------------- */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

/* Already set up: this page is finished forever. */
if (admin_config() !== null) {
    http_response_code(403);
    exit('Already set up. Sign in at /admin/.');
}

const TOKEN_FILE = __DIR__ . '/.install-token';

$error   = '';
$written = false;
$manual  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $token    = trim((string) ($_POST['token'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirm  = (string) ($_POST['confirm'] ?? '');
    $expected = is_file(TOKEN_FILE) ? trim((string) file_get_contents(TOKEN_FILE)) : '';

    if (login_attempts() >= LOGIN_MAX_TRIES) {
        $error = 'Too many attempts. Try again in 15 minutes.';
    } elseif ($expected === '') {
        $error = 'admin/.install-token is missing. Re-upload it, then reload this page.';
    } elseif (!hash_equals($expected, $token)) {
        login_record_failure();
        $error = 'That install token does not match.';
        sleep(1);
    } elseif (strlen($password) < 15) {
        $error = 'Use at least 15 characters.';
    } elseif ($password !== $confirm) {
        $error = 'The two passwords do not match.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $php  = "<?php\n"
              . "/* reels agency blog — private. Kept outside public_html on purpose. */\n"
              . "return ['password_hash' => " . var_export($hash, true) . "];\n";

        if (@file_put_contents(PRIVATE_CONFIG, $php, LOCK_EX) !== false) {
            @chmod(PRIVATE_CONFIG, 0600);
            @unlink(TOKEN_FILE);   // the token has done its job
            login_clear_failures();
            $written = true;
        } else {
            // Some hosts do not let PHP write above the web root. Fall
            // back to showing the file so it can be created by hand.
            $manual = $php;
            $error  = 'Could not write the file automatically — create it by hand, below.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="Content-Security-Policy" content="default-src 'none'; script-src 'none'; script-src-attr 'none'; style-src 'self'; style-src-attr 'none'; img-src 'self'; base-uri 'none'; form-action 'self'; object-src 'none'; frame-src 'none'" />
    <meta name="robots" content="noindex, nofollow" />
    <title>Set up the blog admin</title>
    <link rel="stylesheet" href="<?= BASE ?>/admin/admin.css?v=<?= ASSET_V ?>" />
  </head>
  <body>
    <main class="shell shell--narrow">
      <h1 class="brand">reels<span>&copy;</span></h1>
      <p class="sub">One-time setup</p>

<?php if ($written): ?>
      <p class="flash flash--good">Password set. This page will not work again.</p>
      <p class="hint">Saved to <code><?= e(PRIVATE_CONFIG) ?></code>, outside <code>public_html</code>.</p>
      <p><a class="btn btn--primary" href="<?= BASE ?>/admin/">Sign in</a></p>
<?php else: ?>
<?php if ($error !== ''): ?>
      <p class="flash flash--bad"><?= e($error) ?></p>
<?php endif; ?>
<?php if ($manual !== ''): ?>
      <p class="hint">In hPanel File Manager, go one level above <code>public_html</code>, create
        <code>reels-blog-config.php</code>, and paste exactly this:</p>
      <pre class="manual"><?= e($manual) ?></pre>
<?php else: ?>
      <p class="hint">Open <code>admin/.install-token</code> in hPanel File Manager and paste
        what is inside it here. Then pick the password you will use to publish.</p>
      <form method="post" action="<?= BASE ?>/admin/setup.php">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
        <label for="token">Install token</label>
        <input id="token" name="token" type="text" required autocomplete="off" autofocus />

        <label for="password">New password</label>
        <input id="password" name="password" type="password" required autocomplete="new-password" minlength="15" />

        <label for="confirm">Again</label>
        <input id="confirm" name="confirm" type="password" required autocomplete="new-password" minlength="15" />

        <button type="submit">Set password</button>
      </form>
      <p class="hint">At least 15 characters. Use a password manager — there is no reset link,
        and recovering means deleting <code>reels-blog-config.php</code> and starting this page again.</p>
<?php endif; ?>
<?php endif; ?>
    </main>
  </body>
</html>

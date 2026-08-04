<?php
/* ---------------------------------------------------------------------
   /admin — write a post, press publish.

   Everything this page does is one of four things: sign in, list posts,
   save a post, delete a post. A post is still just a markdown file in
   /posts, so anything done here can be undone with a file manager.
   --------------------------------------------------------------------- */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../lib/blog.php';

/* No password set up yet — send them through first-run setup. */
if (admin_config() === null) {
    header('Location: ' . BASE . '/admin/setup.php');
    exit;
}

$action = (string) ($_GET['action'] ?? $_POST['action'] ?? '');
$notice = '';
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 30 * 1024 * 1024) {
    http_response_code(413);
    exit('Upload too large.');
}

/* ------------------------------------------------------------------ */
/* Sign in / out                                                       */
/* ------------------------------------------------------------------ */

if ($action === 'logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    admin_logout();
    header('Location: ' . BASE . '/admin/');
    exit;
}

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (login_attempts() >= LOGIN_MAX_TRIES) {
        $error = 'Too many attempts. Try again in 15 minutes.';
    } elseif (password_verify((string) ($_POST['password'] ?? ''), admin_config()['password_hash'])) {
        login_clear_failures();
        admin_login();
        header('Location: ' . BASE . '/admin/');
        exit;
    } else {
        login_record_failure();
        $error = 'Wrong password.';
    }
}

if (!admin_logged_in()) {
    render_login($error);
    exit;
}

/* ------------------------------------------------------------------ */
/* Writes                                                              */
/* ------------------------------------------------------------------ */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save') {
    csrf_check();
    try {
        $slug = save_post($_POST, $_FILES);
        // Redirect after the write so a refresh cannot save twice.
        header('Location: ' . BASE . '/admin/?action=edit&slug=' . urlencode($slug) . '&saved=1');
        exit;
    } catch (RuntimeException $ex) {
        $error = $ex->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    csrf_check();
    $post = find_post((string) ($_POST['slug'] ?? ''), true);
    if ($post !== null && is_file($post['file'])) {
        unlink($post['file']);
        $notice = 'Deleted “' . $post['title'] . '”.';
    } else {
        $error = 'That post no longer exists.';
    }
    $action = '';
}

if (isset($_GET['saved'])) {
    $post   = find_post((string) ($_GET['slug'] ?? ''), true);
    $notice = $post !== null && $post['draft']
        ? 'Saved as a draft — it shows on the blog as “Dropping soon”.'
        : 'Published. It is live on the blog now.';
}

/* ------------------------------------------------------------------ */
/* Screens                                                             */
/* ------------------------------------------------------------------ */

if ($action === 'new' || $action === 'edit') {
    $post = $action === 'edit'
        ? find_post((string) ($_GET['slug'] ?? ''), true)
        : null;
    if ($action === 'edit' && $post === null) {
        $error  = 'That post no longer exists.';
        render_list($notice, $error);
        exit;
    }
    render_editor($post, $notice, $error);
    exit;
}

render_list($notice, $error);

/* ================================================================== */
/* Saving                                                              */
/* ================================================================== */

/**
 * Write the submitted form to a markdown file.
 *
 * @throws RuntimeException on anything the author needs to fix
 * @return string the slug the post now lives at
 */
function save_post(array $form, array $files): string
{
    $title = form_text($form, 'title', 120, 'Headline');
    if ($title === '') {
        throw new RuntimeException('A post needs a title.');
    }

    $slug = slugify((string) ($form['slug'] ?? '')) ?: slugify($title);
    if ($slug === '') {
        throw new RuntimeException('That title does not make a usable web address — add a slug.');
    }

    $date = trim((string) ($form['date'] ?? ''));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $date = date('Y-m-d');
    }

    if (!is_dir(POSTS_DIR) || !is_writable(POSTS_DIR)) {
        throw new RuntimeException('The posts folder is not writable. Set it to 755 in File Manager.');
    }

    $target = post_filename($date, $slug);
    $wasAt  = trim((string) ($form['original_file'] ?? ''));

    // Editing an existing post: make sure we are not silently landing on
    // top of a different one after a title or date change.
    if ($wasAt !== '') {
        $wasAt = POSTS_DIR . '/' . basename($wasAt);
    }
    if (is_file($target) && $target !== $wasAt) {
        throw new RuntimeException('A post already uses that address on that date. Change the slug or the date.');
    }

    $cover = form_text($form, 'cover', 500, 'Cover path');
    if (!empty($files['cover_file']['name'])) {
        $cover = store_upload($files['cover_file']);
    }

    $topicsRaw = form_text($form, 'topics', 500, 'Topics');
    $topics = array_values(array_filter(array_map(
        'trim',
        explode(',', $topicsRaw)
    ), 'strlen'));
    if (count($topics) > 12) {
        throw new RuntimeException('Use no more than 12 topics.');
    }
    foreach ($topics as $topic) {
        if (text_length($topic) > 40) {
            throw new RuntimeException('Keep each topic under 40 characters.');
        }
    }

    $frontmatter = build_frontmatter([
        'title'   => $title,
        'serif'   => form_text($form, 'serif', 80, 'Serif tail'),
        'tag'     => form_text($form, 'tag', 30, 'Category') ?: 'Notes',
        'read'    => form_text($form, 'read', 12, 'Read time'),
        'topics'  => $topics,
        'excerpt' => form_text($form, 'excerpt', 320, 'Summary'),
        'author'  => form_text($form, 'author', 80, 'Author') ?: SITE_AUTHOR,
        'cover'   => $cover,
        'accent'  => trim((string) ($form['accent'] ?? '')),
        'slug'    => $slug,
        'date'    => $date,
        'draft'   => (($form['draft'] ?? '') === 'on'),
    ]);

    $body = form_text($form, 'body', 120000, 'Post body', false);
    $body = str_replace(["\r\n", "\r"], "\n", $body);
    if (file_put_contents($target, $frontmatter . trim($body) . "\n", LOCK_EX) === false) {
        throw new RuntimeException('Could not write the post file.');
    }

    // A rename leaves the old file behind; remove it once the new one is
    // safely on disk, never before.
    if ($wasAt !== '' && $wasAt !== $target && is_file($wasAt)) {
        unlink($wasAt);
    }
    return $slug;
}

/** Browser maxlength attributes are convenience only; enforce them here too. */
function form_text(
    array $form,
    string $key,
    int $max,
    string $label,
    bool $trim = true
): string {
    $value = (string) ($form[$key] ?? '');
    if (text_length($value) > $max) {
        throw new RuntimeException($label . ' is too long.');
    }
    return $trim ? trim($value) : $value;
}

function text_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

/**
 * Accept an image or short video into /assets/posts.
 *
 * Type is decided by sniffing the file, not by trusting its name, and
 * the stored name is rebuilt from scratch so nothing from the upload
 * reaches the filesystem verbatim.
 *
 * @throws RuntimeException
 * @return string web path of the stored file
 */
function store_upload(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException($file['error'] === UPLOAD_ERR_INI_SIZE
            ? 'That file is larger than the server allows.'
            : 'The upload did not complete.');
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('The upload did not complete.');
    }
    if ($file['size'] > 24 * 1024 * 1024) {
        throw new RuntimeException('Keep uploads under 24 MB.');
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        'video/mp4'  => 'mp4',
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = (string) $finfo->file($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Use a JPG, PNG, WEBP, GIF or MP4.');
    }
    // For images, confirm it really decodes as one.
    if (str_starts_with($mime, 'image/')) {
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            throw new RuntimeException('That image file looks damaged.');
        }
        $pixels = (int) $imageInfo[0] * (int) $imageInfo[1];
        if ($pixels <= 0 || $pixels > 40000000) {
            throw new RuntimeException('Keep images under 40 megapixels.');
        }
    }

    if (!is_dir(UPLOAD_DIR) && !@mkdir(UPLOAD_DIR, 0755, true)) {
        throw new RuntimeException('Could not create /assets/posts.');
    }
    if (!is_writable(UPLOAD_DIR)) {
        throw new RuntimeException('The /assets/posts folder is not writable. Set it to 755.');
    }

    $base = slugify(pathinfo((string) $file['name'], PATHINFO_FILENAME)) ?: 'image';
    $name = $base . '-' . bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $name)) {
        throw new RuntimeException('Could not save the upload.');
    }
    @chmod(UPLOAD_DIR . '/' . $name, 0644);
    return UPLOAD_URL . '/' . $name;
}

/* ================================================================== */
/* Views                                                               */
/* ================================================================== */

function admin_head(string $title): void
{
    $csp = "default-src 'none'; script-src 'self'; script-src-attr 'none'; style-src 'self'; "
         . "style-src-attr 'none'; img-src 'self' data:; media-src 'self'; connect-src 'self'; "
         . "object-src 'none'; frame-src 'none'; base-uri 'none'; form-action 'self'; "
         . "require-trusted-types-for 'script'";
    ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="Content-Security-Policy" content="<?= e($csp) ?>" />
    <meta name="robots" content="noindex, nofollow" />
    <title><?= e($title) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= BASE ?>/favicon.svg?v=<?= ASSET_V ?>" />
    <link rel="stylesheet" href="<?= BASE ?>/admin/admin.css?v=<?= ASSET_V ?>" />
  </head>
  <body>
<?php
}

function admin_foot(bool $withScript = false): void
{
    if ($withScript) {
        echo '    <script src="' . BASE . '/admin/admin.js?v=' . ASSET_V . '"></script>' . "\n";
    }
    echo "  </body>\n</html>\n";
}

function render_login(string $error): void
{
    admin_head('Sign in — ' . SITE_NAME);
    ?>
    <main class="shell shell--narrow">
      <h1 class="brand">reels<span>©</span></h1>
      <p class="sub">Blog admin</p>
<?php if ($error !== ''): ?>
      <p class="flash flash--bad"><?= e($error) ?></p>
<?php endif; ?>
      <form method="post" action="<?= BASE ?>/admin/">
        <input type="hidden" name="action" value="login" />
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required autofocus />
        <button type="submit">Sign in</button>
      </form>
    </main>
<?php
    admin_foot();
}

function render_list(string $notice, string $error): void
{
    $posts = all_posts(true);
    admin_head('Posts — ' . SITE_NAME);
    ?>
    <header class="bar">
      <h1 class="brand">reels<span>©</span></h1>
      <div class="bar-actions">
        <a class="btn btn--primary" href="<?= BASE ?>/admin/?action=new">New post</a>
        <a class="btn" href="<?= BASE ?>/learn" target="_blank" rel="noopener">View blog</a>
        <form method="post" action="<?= BASE ?>/admin/">
          <input type="hidden" name="action" value="logout" />
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
          <button class="btn btn--quiet" type="submit">Sign out</button>
        </form>
      </div>
    </header>
    <main class="shell">
<?php if ($notice !== ''): ?>
      <p class="flash flash--good"><?= e($notice) ?></p>
<?php endif; ?>
<?php if ($error !== ''): ?>
      <p class="flash flash--bad"><?= e($error) ?></p>
<?php endif; ?>
<?php if ($posts === []): ?>
      <p class="empty">No posts yet. <a href="<?= BASE ?>/admin/?action=new">Write the first one</a>.</p>
<?php else: ?>
      <ul class="posts">
<?php foreach ($posts as $post): ?>
        <li class="post">
          <div class="post-main">
            <a class="post-title" href="<?= BASE ?>/admin/?action=edit&amp;slug=<?= e(urlencode($post['slug'])) ?>"><?= e($post['title']) ?></a>
            <p class="post-meta">
              <span class="pill <?= $post['draft'] ? 'pill--draft' : 'pill--live' ?>"><?= $post['draft'] ? 'Draft' : 'Published' ?></span>
              <span><?= e($post['date']) ?></span>
              <span><?= e($post['tag']) ?></span>
<?php if ($post['read'] !== ''): ?>
              <span><?= e($post['read']) ?></span>
<?php endif; ?>
              <span class="path">/learn/<?= e($post['slug']) ?></span>
            </p>
          </div>
          <div class="post-actions">
            <a class="btn btn--small" href="<?= $post['draft']
                ? BASE . '/admin/preview.php?slug=' . e(urlencode($post['slug']))
                : e(post_url($post)) ?>" target="_blank" rel="noopener"><?= $post['draft'] ? 'Preview' : 'View' ?></a>
            <a class="btn btn--small" href="<?= BASE ?>/admin/?action=edit&amp;slug=<?= e(urlencode($post['slug'])) ?>">Edit</a>
            <form method="post" action="<?= BASE ?>/admin/" data-confirm="Delete “<?= e($post['title']) ?>” for good?">
              <input type="hidden" name="action" value="delete" />
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
              <input type="hidden" name="slug" value="<?= e($post['slug']) ?>" />
              <button class="btn btn--small btn--danger" type="submit">Delete</button>
            </form>
          </div>
        </li>
<?php endforeach; ?>
      </ul>
<?php endif; ?>
    </main>
<?php
    admin_foot(true);
}

function render_editor(?array $post, string $notice, string $error): void
{
    $isNew = $post === null;

    // A save that failed validation re-renders this form. Repopulate from
    // what was typed, so nothing the author wrote is thrown away.
    $posted = $_SERVER['REQUEST_METHOD'] === 'POST';
    $f = static function (string $key, $fallback = '') use ($posted) {
        if ($posted && isset($_POST[$key])) {
            return (string) $_POST[$key];
        }
        return (string) $fallback;
    };
    // Checkboxes are absent when unticked, so they cannot use $f.
    $isDraft = $posted
        ? isset($_POST['draft'])
        : ($isNew ? true : $post['draft']);

    admin_head(($isNew ? 'New post' : 'Edit post') . ' — ' . SITE_NAME);
    ?>
    <header class="bar">
      <h1 class="brand"><a href="<?= BASE ?>/admin/">reels<span>©</span></a></h1>
      <div class="bar-actions">
<?php if (!$isNew): ?>
        <a class="btn" href="<?= $post['draft']
            ? BASE . '/admin/preview.php?slug=' . e(urlencode($post['slug']))
            : e(post_url($post)) ?>" target="_blank" rel="noopener"><?= $post['draft'] ? 'Preview' : 'View' ?></a>
<?php endif; ?>
        <a class="btn" href="<?= BASE ?>/admin/">All posts</a>
        <form method="post" action="<?= BASE ?>/admin/">
          <input type="hidden" name="action" value="logout" />
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
          <button class="btn btn--quiet" type="submit">Sign out</button>
        </form>
      </div>
    </header>
    <main class="shell">
<?php if ($notice !== ''): ?>
      <p class="flash flash--good"><?= e($notice) ?></p>
<?php endif; ?>
<?php if ($error !== ''): ?>
      <p class="flash flash--bad"><?= e($error) ?></p>
<?php endif; ?>

      <form class="editor" method="post" action="<?= BASE ?>/admin/" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save" />
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
        <input type="hidden" name="original_file" value="<?= e($isNew ? '' : basename($post['file'])) ?>" />

        <div class="field">
          <label for="title">Headline</label>
          <input id="title" name="title" type="text" required maxlength="120"
                 value="<?= e($f('title', $isNew ? '' : $post['title'])) ?>"
                 data-slug-source />
          <p class="hint">The full headline, as it should read in Google.</p>
        </div>

        <div class="row">
          <div class="field">
            <label for="serif">Serif tail</label>
            <input id="serif" name="serif" type="text" maxlength="80"
                   value="<?= e($f('serif', $isNew ? '' : $post['serif'])) ?>" />
            <p class="hint">The end of the headline, set in italic serif — e.g. <code>two seconds.</code></p>
          </div>
          <div class="field">
            <label for="slug">Web address</label>
            <div class="prefixed"><span>/learn/</span>
              <input id="slug" name="slug" type="text" maxlength="80"
                     value="<?= e($f('slug', $isNew ? '' : $post['slug'])) ?>"
                     data-slug-target />
            </div>
            <p class="hint">Fills itself in from the headline. Don't change it after publishing.</p>
          </div>
        </div>

        <div class="row row--three">
          <div class="field">
            <label for="tag">Category</label>
            <input id="tag" name="tag" type="text" maxlength="30"
                   value="<?= e($f('tag', $isNew ? '' : $post['tag'])) ?>" placeholder="Hooks" />
          </div>
          <div class="field">
            <label for="read">Read time</label>
            <input id="read" name="read" type="text" maxlength="12"
                   value="<?= e($f('read', $isNew ? '' : $post['read'])) ?>" placeholder="4 min" />
          </div>
          <div class="field">
            <label for="date">Date</label>
            <input id="date" name="date" type="date"
                   value="<?= e($f('date', $isNew ? date('Y-m-d') : $post['date'])) ?>" />
          </div>
        </div>

        <div class="field">
          <label for="topics">Topics</label>
          <input id="topics" name="topics" type="text"
                 value="<?= e($f('topics', $isNew ? '' : implode(', ', array_map('strval', $post['topics'])))) ?>"
                 placeholder="Hooks, Retention, Scripting" />
          <p class="hint">Comma separated. These are the small chips under the headline.</p>
        </div>

        <div class="field">
          <label for="excerpt">Summary</label>
          <textarea id="excerpt" name="excerpt" rows="3" maxlength="320"><?= e($f('excerpt', $isNew ? '' : $post['excerpt'])) ?></textarea>
          <p class="hint">Shown on the blog index and used as the Google description. One or two sentences.</p>
        </div>

        <div class="field">
          <label for="author">Author</label>
          <input id="author" name="author" type="text" maxlength="80"
                 value="<?= e($f('author', $isNew ? SITE_AUTHOR : $post['author'])) ?>" />
        </div>

        <div class="row">
          <div class="field">
            <label for="cover_file">Cover image</label>
            <input id="cover_file" name="cover_file" type="file" accept="image/*,video/mp4" />
            <input type="hidden" name="cover" value="<?= e($f('cover', $isNew ? '' : $post['cover'])) ?>" />
<?php if (!$isNew && $post['cover'] !== ''): ?>
            <p class="hint">Currently: <code><?= e($post['cover']) ?></code></p>
<?php endif; ?>
          </div>
          <div class="field">
            <label for="accent">Card colour</label>
            <select id="accent" name="accent">
<?php foreach (['' => 'Automatic', 'plain' => 'White', 'pink' => 'Pink', 'navy' => 'Navy'] as $k => $label): ?>
              <option value="<?= e($k) ?>"<?= $f('accent', $isNew ? '' : $post['accent']) === $k ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="field">
          <label for="body">The post</label>
          <textarea id="body" name="body" rows="26" spellcheck="true"><?= e($f('body', $isNew ? '' : $post['source'])) ?></textarea>
        </div>

        <details class="cheats">
          <summary>How to format the post</summary>
          <table>
            <tr><td><code>## [The problem] Nobody chose your reel</code></td><td>New section. The part in brackets is the small pink label above the heading — leave it out if you don't want one.</td></tr>
            <tr><td><code>*two words*</code></td><td>Italic serif, the way headings on the site are styled.</td></tr>
            <tr><td><code>1. Name — hint</code></td><td>Numbered rows with a note on the right. Use a real dash with spaces around it.</td></tr>
            <tr><td><code>1. Just text</code></td><td>Numbered cards on a coloured band.</td></tr>
            <tr><td><code>- point</code></td><td>Bullet list.</td></tr>
            <tr><td><code>&gt; a line worth pulling out</code></td><td>Large italic pull quote.</td></tr>
            <tr><td><code>![what it shows](assets/posts/x.jpg)</code></td><td>An image. An <code>.mp4</code> becomes a looping video.</td></tr>
            <tr><td><code>![...](x.mp4 "portrait")</code></td><td>A 9:16 clip beside the text, two columns.</td></tr>
            <tr><td><code>| Metric | Result |</code></td><td>Start a Markdown table. Put <code>| --- | --- |</code> on the next line, then add data rows.</td></tr>
            <tr><td><code>bar: Opening A | 72</code></td><td>Add a labelled percentage bar. Put several together to make a comparison chart.</td></tr>
            <tr><td><code>## Heading {pink}</code></td><td>Force a section's colour: <code>white</code>, <code>pink</code> or <code>navy</code>.</td></tr>
          </table>
        </details>

        <div class="save-row">
          <label class="check">
            <input type="checkbox" name="draft"<?= $isDraft ? ' checked' : '' ?> />
            <span>Keep as a draft (shows as “Dropping soon”, not readable)</span>
          </label>
          <button class="btn btn--primary" type="submit">Save</button>
        </div>
      </form>
    </main>
<?php
    admin_foot(true);
}

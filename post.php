<?php
/* ---------------------------------------------------------------------
   /learn/<slug> — one post.

   Same shape as the hand-built learn-hooks.html: a case-hero, then the
   banded sections the markdown renderer produces, then a way onward.
   --------------------------------------------------------------------- */

declare(strict_types=1);

require_once __DIR__ . '/lib/blog.php';
require_once __DIR__ . '/lib/layout.php';

$slug = (string) ($_GET['slug'] ?? '');

/* Drafts are invisible here. To look at one before publishing, the admin
   includes this file from /admin/preview.php, which has already checked
   the session — so a public request never touches session state at all. */
$preview = defined('POST_PREVIEW') && POST_PREVIEW;

$post = find_post($slug, $preview);

/* ---- not found -------------------------------------------------- */
if ($post === null) {
    http_response_code(404);
    render_head([
        'title'       => 'Post not found | ' . SITE_NAME,
        'description' => 'That post is not here.',
        'body_class'  => 'learn-post-page',
    ], BASE . '/learn');
    ?>
    <main class="site">
      <section class="case-hero">
        <p class="reels-label reveal">404</p>
        <h1 class="reels-display fx-hero-display">
          <span>Not</span>
          <span data-fx-serif>here.</span>
        </h1>
      </section>
      <section class="reels-section reels-section--white">
        <p class="reels-body reveal">That post has moved or never existed.</p>
        <div class="reels-mt">
          <a class="reels-button reels-button--outline" href="<?= BASE ?>/learn">&#8592;&#65038; All posts</a>
        </div>
      </section>
    </main>
<?php
    render_footer();
    exit;
}

/* ---- the next post to read -------------------------------------- */
$published = array_values(array_filter(all_posts(), static fn(array $p) => !$p['teaser']));
$next = null;
foreach ($published as $i => $p) {
    if ($p['slug'] === $post['slug']) {
        $next = $published[$i + 1] ?? ($published[0]['slug'] === $post['slug'] ? null : $published[0]);
        break;
    }
}

$metaBits = array_filter(['Learn', $post['tag'], $post['read'], pretty_date($post['date'])], 'strlen');

render_head([
    'title'       => $post['title'] . ' | ' . SITE_NAME,
    'description' => $post['excerpt'] !== '' ? $post['excerpt'] : $post['title'],
    'body_class'  => 'learn-post-page',
    'canonical'   => site_url(post_url($post)),
    'type'        => 'article',
    'published'   => $post['date'],
    'image'       => $post['cover'] !== ''
        ? site_url(safe_url($post['cover']))
        : site_url(BASE . '/assets/logo-nav.svg'),
], BASE . '/learn');
?>

    <main class="site">
      <section class="case-hero">
        <p class="reels-label reveal"><?= e(implode(' · ', $metaBits)) ?></p>
        <h1 class="reels-display fx-hero-display">
          <span><?= e($post['lead']) ?></span>
<?php if ($post['serif'] !== '' && $post['lead'] !== $post['title']): ?>
          <span data-fx-serif><?= e($post['serif']) ?></span>
<?php endif; ?>
        </h1>
<?php if ($post['topics'] !== []): ?>
        <div class="case-hero-meta">
          <?php foreach ($post['topics'] as $t): ?><span><?= e((string) $t) ?></span><?php endforeach; ?>
        </div>
<?php endif; ?>
      </section>

<?php if ($post['cover'] !== ''): ?>
      <section class="reels-section reels-section--white reels-section--tight-top">
<?= render_figure($post['title'], $post['cover'], '') ?>
      </section>
<?php endif; ?>

<?= $post['html'] ?>

      <section class="reels-section reels-section--white reels-section--tight-top">
        <div class="post-end">
          <a class="reels-button reels-button--outline" href="<?= BASE ?>/learn">&#8592;&#65038; All posts</a>
<?php if ($next !== null): ?>
          <a class="reels-button" href="<?= e(post_url($next)) ?>">Next: <?= e($next['title']) ?> &#8599;&#65038;</a>
<?php endif; ?>
        </div>
      </section>
    </main>

<?php render_footer(); ?>

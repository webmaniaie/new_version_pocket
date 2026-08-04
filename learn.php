<?php
/* ---------------------------------------------------------------------
   /learn — the blog index.

   Builds the .learn-slide feed from whatever is in /posts. Numbering and
   card colours are derived, so adding a post never means editing markup.
   --------------------------------------------------------------------- */

declare(strict_types=1);

require_once __DIR__ . '/lib/blog.php';
require_once __DIR__ . '/lib/layout.php';

$posts = all_posts(true);   // drafts included: they render as "Dropping soon"

render_head([
    'title'       => 'Learn: Hooks, Formats & Retention | ' . SITE_NAME,
    'description' => 'Free short-form video strategy from the people making it: how to write '
                   . 'hooks, build repeatable formats, film with one phone and read retention '
                   . 'over likes.',
    'body_class'  => 'learn-page',
    'canonical'   => site_url(BASE . '/learn'),
], BASE . '/learn');
?>

    <main class="site">
      <section class="section reels-hero reels-hero--slim">
        <div class="reels-hero-content">
          <p class="reels-label reveal">Learn</p>
          <h1 class="reels-display reels-display--inline fx-hero-display">
            <span>Our</span>
            <span data-fx-serif>blog.</span>
          </h1>
          <p class="reels-hero-note reveal">Learn how to plan, shoot and improve your own Reels with our educational hub.</p>
        </div>
      </section>

<?php if ($posts === []): ?>
      <section class="reels-section reels-section--white reels-center">
        <p class="reels-body reveal">First post is on its way.</p>
      </section>
<?php else: ?>
      <div class="learn-feed">
<?php foreach ($posts as $i => $post):
        $accent = post_accent($post, $i);
        $reelCls = 'learn-reel'
                 . ($accent === 'plain' ? '' : ' learn-reel--' . $accent)
                 . ($post['cover'] !== '' ? ' learn-reel--media' : '')
                 . ' reveal';
        $top = '/ ' . sprintf('%02d', $i + 1) . ' &middot; ' . e($post['tag']);
        $url = post_url($post);
        $cover = safe_url($post['cover']);
        $coverIsVideo = $cover !== '' && preg_match('/\.(mp4|m4v|webm)$/i', $cover);
?>
        <article class="learn-slide">
<?php if ($post['teaser']): ?>
          <div class="<?= $reelCls ?>">
<?php if ($cover !== ''): ?>
            <div class="learn-reel-media" aria-hidden="true">
<?php if ($coverIsVideo): ?>
              <video src="<?= e($cover) ?>" autoplay muted loop playsinline preload="metadata"></video>
<?php else: ?>
              <img src="<?= e($cover) ?>" alt="" loading="lazy" decoding="async" />
<?php endif; ?>
            </div>
<?php endif; ?>
            <div class="learn-reel-top">
              <span><?= $top ?></span>
              <span><?= e($post['read']) ?></span>
            </div>
            <div class="learn-reel-rail" aria-hidden="true">
              <i class="learn-action-like"></i>
              <i class="learn-action-comment"></i>
              <i class="learn-action-share"></i>
            </div>
            <div class="learn-reel-foot"><span>Dropping soon</span><span aria-hidden="true">&middot;&middot;&middot;</span></div>
          </div>
<?php else: ?>
          <a class="<?= $reelCls ?>" href="<?= e($url) ?>" data-cursor="Read">
<?php if ($cover !== ''): ?>
            <div class="learn-reel-media" aria-hidden="true">
<?php if ($coverIsVideo): ?>
              <video src="<?= e($cover) ?>" autoplay muted loop playsinline preload="metadata"></video>
<?php else: ?>
              <img src="<?= e($cover) ?>" alt="" loading="lazy" decoding="async" />
<?php endif; ?>
            </div>
<?php endif; ?>
            <div class="learn-reel-top">
              <span><?= $top ?></span>
              <span><?= e($post['read']) ?></span>
            </div>
            <div class="learn-reel-rail" aria-hidden="true">
              <i class="learn-action-like"></i>
              <i class="learn-action-comment"></i>
              <i class="learn-action-share"></i>
            </div>
            <div class="learn-reel-foot"><span>Read it</span><span aria-hidden="true">&#8599;&#65038;</span></div>
          </a>
<?php endif; ?>
          <div class="learn-slide-copy reveal">
<?php if ($post['topics'] !== []): ?>
            <div class="learn-slide-meta"><?php foreach ($post['topics'] as $t): ?><span><?= e((string) $t) ?></span><?php endforeach; ?></div>
<?php endif; ?>
            <h2><?= title_with_em($post) ?></h2>
<?php if ($post['excerpt'] !== ''): ?>
            <p><?= e($post['excerpt']) ?></p>
<?php endif; ?>
<?php if ($post['teaser']): ?>
            <p class="reels-mt-sm"><span class="reels-soon">Dropping soon</span></p>
<?php else: ?>
            <p class="learn-post-cta"><a class="reels-button" href="<?= e($url) ?>">Read the post &#8599;&#65038;</a></p>
<?php endif; ?>
          </div>
        </article>
<?php endforeach; ?>
      </div>
<?php endif; ?>

      <section class="reels-section reels-section--pink reels-center">
        <p class="reels-note reveal">want this done for you instead?</p>
        <a class="reels-button reveal" href="<?= BASE ?>/product">See the product &#8599;&#65038;</a>
      </section>
    </main>

<?php render_footer(); ?>

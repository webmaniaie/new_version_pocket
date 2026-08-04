<?php
/* ---------------------------------------------------------------------
   reels agency — shared page chrome.

   Lifted verbatim from the hand-built static pages so a PHP-rendered
   page is byte-for-byte the same shell: same CSP, same nav, same mobile
   menu, same footer, same contact modal, same script order (script.js
   first, then GSAP/three from CDN with their SRI hashes, then fx.js and
   reels.js).
   --------------------------------------------------------------------- */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/** The exact CSP the static pages carry in their meta tag. */
const PAGE_CSP = "default-src 'self'; script-src 'self' https://cdnjs.cloudflare.com; "
    . "script-src-attr 'none'; style-src 'self' https://fonts.googleapis.com; "
    . "style-src-attr 'none'; font-src https://fonts.gstatic.com; img-src 'self'; "
    . "media-src 'self'; connect-src 'self'; worker-src 'none'; child-src 'none'; "
    . "object-src 'none'; frame-src 'none'; base-uri 'none'; form-action 'none'; "
    . "upgrade-insecure-requests; require-trusted-types-for 'script'";

/** The site's primary nav, in order. */
function nav_items(): array
{
    return [
        BASE . '/'          => 'Home',
        BASE . '/work'      => 'Work',
        BASE . '/product'   => 'Products',
        BASE . '/learn'     => 'Learn',
        BASE . '/team'      => 'Team',
        BASE . '/contacts'  => 'Contacts',
    ];
}

/**
 * Open the document: <head> + nav + mobile menu, up to <main>.
 *
 * @param array{title:string, description:string, body_class:string,
 *              canonical?:string, image?:string, type?:string,
 *              published?:string, author?:string, theme_color?:string} $meta
 */
function render_head(array $meta, string $activeUrl): void
{
    if (!headers_sent()) {
        header('Content-Security-Policy: ' . PAGE_CSP . "; frame-ancestors 'none'");
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cross-Origin-Resource-Policy: same-origin');
        header('Origin-Agent-Cluster: ?1');
        header('X-Permitted-Cross-Domain-Policies: none');
    }
    $v         = ASSET_V;
    $title     = $meta['title'];
    $desc      = $meta['description'];
    $canonical = $meta['canonical'] ?? site_url($_SERVER['REQUEST_URI'] ?? '/');
    $image     = $meta['image'] ?? site_url(BASE . '/assets/logo-nav.svg');
    $type      = $meta['type'] ?? 'website';
    $theme     = $meta['theme_color'] ?? '#ff7aac';
    ?>
<!doctype html>
<html lang="<?= SITE_LOCALE ?>">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="Content-Security-Policy" content="<?= e(PAGE_CSP) ?>" />
    <meta name="referrer" content="strict-origin-when-cross-origin" />
    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($desc) ?>" />
<?php if (!empty($meta['author'])): ?>
    <meta name="author" content="<?= e($meta['author']) ?>" />
<?php endif; ?>
    <meta name="theme-color" content="<?= e($theme) ?>" />
    <link rel="canonical" href="<?= e($canonical) ?>" />
    <meta property="og:type" content="<?= e($type) ?>" />
    <meta property="og:site_name" content="<?= e(SITE_NAME) ?>" />
    <meta property="og:locale" content="<?= str_replace('-', '_', SITE_LOCALE) ?>" />
    <meta property="og:url" content="<?= e($canonical) ?>" />
    <meta property="og:title" content="<?= e($title) ?>" />
    <meta property="og:description" content="<?= e($desc) ?>" />
    <meta property="og:image" content="<?= e($image) ?>" />
<?php if (!empty($meta['published'])): ?>
    <meta property="article:published_time" content="<?= e($meta['published']) ?>" />
<?php endif; ?>
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="<?= e($title) ?>" />
    <meta name="twitter:description" content="<?= e($desc) ?>" />
    <link rel="alternate" type="application/rss+xml" title="<?= e(SITE_NAME) ?> — Learn" href="<?= BASE ?>/rss.xml" />
    <link rel="icon" type="image/svg+xml" href="<?= BASE ?>/favicon.svg?v=<?= $v ?>" />
    <link rel="apple-touch-icon" href="<?= BASE ?>/apple-touch-icon.png?v=<?= $v ?>" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Montserrat:ital,wght@0,500;0,600;0,700;0,800;1,500;1,600;1,700;1,800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="<?= BASE ?>/styles.css?v=<?= $v ?>" />
    <link rel="stylesheet" href="<?= BASE ?>/fx.css?v=<?= $v ?>" />
    <link rel="stylesheet" href="<?= BASE ?>/reels.css?v=<?= $v ?>" />
  </head>
  <body class="<?= e($meta['body_class']) ?>">
    <header class="site-nav">
      <a class="logo" href="<?= BASE ?>/">
        <img src="<?= BASE ?>/assets/logo-nav.svg" alt="<?= e(SITE_NAME) ?>" width="661" height="200" />
      </a>
      <nav class="nav-links">
<?php foreach (nav_items() as $url => $label): ?>
        <a<?= $url === $activeUrl ? ' class="active"' : '' ?> href="<?= e($url) ?>"><?= e($label) ?></a>
<?php endforeach; ?>
      </nav>
      <button class="menu-toggle" type="button" aria-label="Open menu">
        <span></span>
        <span></span>
        <span></span>
      </button>
    </header>

    <div class="mobile-menu" aria-hidden="true">
      <div class="mobile-menu-overlay"></div>
      <div class="mobile-menu-panel">
        <div class="mobile-menu-header">
          <a class="logo" href="<?= BASE ?>/">
            <img src="<?= BASE ?>/assets/logo-nav.svg" alt="<?= e(SITE_NAME) ?>" width="661" height="200" />
          </a>
          <button class="menu-close" type="button" aria-label="Close menu">
            <span></span>
            <span></span>
          </button>
        </div>
        <nav class="mobile-menu-links">
<?php foreach (nav_items() as $url => $label): ?>
          <a<?= $url === $activeUrl ? ' class="active"' : '' ?> href="<?= e($url) ?>"><?= e($label) ?></a>
<?php endforeach; ?>
          <a href="#" data-contact>Ask question</a>
        </nav>
      </div>
    </div>
<?php
}

/** Close the document: footer + contact modal + scripts. */
function render_footer(bool $withEffects = true): void
{
    $v = ASSET_V;
    ?>
    <footer class="reels-footer">
      <div class="reels-footer-cta">
        <p class="reels-label">Got a brand to put in the feed?</p>
        <h2 class="reels-footer-title">Go viral <em>next.</em></h2>
        <div class="reels-footer-contacts">
          <a href="mailto:<?= e(SITE_EMAIL) ?>"><?= e(SITE_EMAIL) ?></a>
          <a href="tel:<?= e(SITE_TEL) ?>"><?= e(SITE_TEL_DISPLAY) ?></a>
        </div>
        <div class="reels-footer-socials" aria-label="Social media">
          <a href="#" aria-label="reels agency on Instagram">Instagram <span aria-hidden="true">&#8599;&#65038;</span></a>
          <a href="#" aria-label="reels agency on LinkedIn">LinkedIn <span aria-hidden="true">&#8599;&#65038;</span></a>
        </div>
      </div>
      <div class="reels-footer-grid">
        <nav aria-label="Footer">
<?php foreach (nav_items() as $url => $label): ?>
          <a href="<?= e($url) ?>"><?= e($label) ?></a>
<?php endforeach; ?>
        </nav>
        <span class="reels-footer-note">Ireland</span>
        <span class="reels-footer-note">&copy; <span data-year><?= date('Y') ?></span> <?= e(SITE_NAME) ?></span>
        <button class="reels-to-top" type="button" aria-label="Back to top">&#8593;&#65038;</button>
      </div>
      <div class="reels-footer-mark" aria-hidden="true">reels&copy;</div>
    </footer>

    <div class="contact-modal" aria-hidden="true">
      <div class="contact-modal-overlay"></div>
      <div class="contact-modal-card" role="dialog" aria-modal="true" aria-label="Contact">
        <button class="contact-modal-close" type="button" aria-label="Close">&times;</button>
        <h3>Ask any <em>question</em></h3>
        <div class="contact-modal-links">
          <a href="mailto:<?= e(SITE_EMAIL) ?>"><?= e(SITE_EMAIL) ?></a>
          <a href="tel:<?= e(SITE_TEL) ?>"><?= e(SITE_TEL_DISPLAY) ?></a>
          <a class="contact-wa" href="https://wa.me/<?= e(ltrim(SITE_TEL, '+')) ?>" target="_blank" rel="noopener noreferrer" aria-label="Message us on WhatsApp"><svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 004.79 1.22h.01c5.46 0 9.9-4.45 9.9-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0012.04 2zm5.79 14.16c-.24.68-1.42 1.31-1.96 1.35-.5.04-.5.4-3.16-.66-2.66-1.05-4.3-3.79-4.43-3.97-.13-.18-1.05-1.4-1.05-2.67 0-1.27.66-1.9.9-2.16.24-.26.52-.32.7-.32l.5.01c.16 0 .38-.06.59.45.24.58.81 2 .88 2.15.07.15.12.32.02.51-.1.19-.15.31-.29.48-.15.17-.31.38-.44.51-.15.15-.3.31-.13.6.17.29.76 1.25 1.63 2.02 1.12.99 2.06 1.3 2.35 1.45.29.15.46.12.63-.07.17-.19.73-.85.93-1.14.19-.29.39-.24.65-.15.26.1 1.67.79 1.96.93.29.15.48.22.55.34.07.12.07.69-.17 1.36z"/></svg><span>WhatsApp us</span></a>
        </div>
        <p class="contact-modal-note">We usually reply within a day.</p>
      </div>
    </div>

    <script src="<?= BASE ?>/script.js?v=<?= $v ?>"></script>
<?php if ($withEffects): ?>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" integrity="sha384-g4NTh/Iv5PPU4xPyhEWqPcwtNXOvdaDI8LLnyYfyNZOjKJeYQyjzQ9X5275eBjpt" crossorigin="anonymous"></script>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js" integrity="sha384-Z3REaz79l2IaAZqJsSABtTbhjgOUYyV3p90XNnAPCSHg3EMTz1fouunq9WZRtj3d" crossorigin="anonymous"></script>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js" integrity="sha384-CI3ELBVUz9XQO+97x6nwMDPosPR5XvsxW2ua7N1Xeygeh1IxtgqtCkGfQY9WWdHu" crossorigin="anonymous"></script>
    <script defer src="<?= BASE ?>/fx.js?v=<?= $v ?>"></script>
<?php endif; ?>
    <script defer src="<?= BASE ?>/reels.js?v=<?= $v ?>"></script>
  </body>
</html>
<?php
}

<?php
/* ---------------------------------------------------------------------
   reels agency — blog configuration.

   Everything the blog needs to know about the site lives here. The one
   thing that does NOT live here is the admin password: that is kept in
   a file above the web root so it is never servable, even if PHP breaks.
   --------------------------------------------------------------------- */

declare(strict_types=1);

/* The blog needs PHP 8 and the fileinfo extension (Hostinger has both by
   default). Say so plainly rather than dying with a parse error if the
   host is ever set to an old version in hPanel. */
if (PHP_VERSION_ID < 80000) {
    http_response_code(500);
    exit('This site needs PHP 8.0 or newer. Set it in hPanel → Advanced → PHP Configuration.');
}
if (!class_exists('finfo')) {
    http_response_code(500);
    exit('This site needs the PHP "fileinfo" extension. Enable it in hPanel → Advanced → PHP Configuration.');
}

/* Cache-bust for css/js. Bump this whenever styles.css / fx.css /
   reels.css / script.js change — same number the static pages use. */
const ASSET_V = '66';

/* Site-wide facts, reused in the nav, footer, contact modal and feeds. */
const SITE_NAME  = 'reels agency';
const SITE_EMAIL = 'platon.tsuz@gmail.com';
const SITE_TEL   = '+353892277248';
const SITE_TEL_DISPLAY = '+353 89 2277 248';
const SITE_LOCALE = 'en-IE';

/* Root-relative base. '' means the site is served from the domain root,
   which is how Hostinger serves public_html. Set to '/subfolder' only if
   the site ever moves into one. */
const BASE = '';

/* Where posts and their images live, on disk. */
define('POSTS_DIR',  dirname(__DIR__) . '/posts');
define('UPLOAD_DIR', dirname(__DIR__) . '/assets/posts');
const UPLOAD_URL = BASE . '/assets/posts';

/* The private config (admin password hash) sits one level ABOVE the web
   root, so no URL can ever reach it. On Hostinger that is the folder
   containing public_html. */
define('PRIVATE_CONFIG', dirname(dirname(__DIR__)) . '/reels-blog-config.php');

/* Absolute site URL, used for canonical links, OG tags and the RSS feed.
   Derived from the request so it is correct on the live domain, on a
   staging subdomain and on the local php -S server alike. */
function site_url(string $path = ''): string
{
    $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . BASE . $path;
}

/* Short-hand for escaping into HTML. Used everywhere; never echo raw. */
function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

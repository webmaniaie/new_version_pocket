<?php
/* Served at /robots.txt — the sitemap line fills in the live domain by
   itself, so it is right on the real host without being edited. */

declare(strict_types=1);

require_once __DIR__ . '/lib/config.php';

header('Content-Type: text/plain; charset=utf-8');
?>
User-agent: *
Allow: /
Disallow: /admin/

Sitemap: <?= site_url(BASE . '/sitemap.xml') . "\n" ?>

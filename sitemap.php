<?php
/* Served at /sitemap.xml — static pages plus every published post. */

declare(strict_types=1);

require_once __DIR__ . '/lib/blog.php';

header('Content-Type: application/xml; charset=utf-8');

/** path => change frequency weight */
$pages = [
    '/'                     => '1.0',
    '/work'                 => '0.9',
    '/product'              => '0.9',
    '/learn'                => '0.8',
    '/team'                 => '0.6',
    '/contacts'             => '0.6',
    '/case-sweet-baking'    => '0.7',
    '/case-ukrainian-brand' => '0.7',
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($pages as $path => $priority) {
    echo "  <url>\n"
       . '    <loc>' . e(site_url(BASE . $path)) . "</loc>\n"
       . '    <priority>' . $priority . "</priority>\n"
       . "  </url>\n";
}

foreach (all_posts() as $post) {
    echo "  <url>\n"
       . '    <loc>' . e(site_url(post_url($post))) . "</loc>\n"
       . '    <lastmod>' . e($post['date']) . "</lastmod>\n"
       . "    <priority>0.7</priority>\n"
       . "  </url>\n";
}

echo '</urlset>' . "\n";

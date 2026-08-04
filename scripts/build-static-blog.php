<?php
/* Build the PHP/Markdown blog as plain HTML for GitHub Pages. */

declare(strict_types=1);

$root = dirname(__DIR__);
$output = $argv[1] ?? ($root . '/dist');

if (!is_dir($output)) {
    fwrite(STDERR, "Static blog output directory does not exist.\n");
    exit(1);
}

/** Render one of the site's PHP routes without starting a web server. */
function capture_route(string $file, array $query, string $uri): string
{
    $_GET = $query;
    $_SERVER['HTTP_HOST'] = 'reelsagency.ie';
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['REQUEST_URI'] = $uri;

    ob_start();
    include $file;
    return (string) ob_get_clean();
}

/** Convert root-relative Hostinger routes to files GitHub Pages can serve. */
function static_paths(string $html): string
{
    $html = preg_replace_callback(
        '/(href|src)="\/learn\/([a-z0-9][a-z0-9-]*)"/',
        static fn(array $m): string => $m[1] . '="learn-' . $m[2] . '.html"',
        $html
    ) ?? $html;

    $routes = [
        'href="/"' => 'href="index.html"',
        'href="/work"' => 'href="work.html"',
        'href="/product"' => 'href="product.html"',
        'href="/learn"' => 'href="learn.html"',
        'href="/team"' => 'href="team.html"',
        'href="/contacts"' => 'href="contacts.html"',
        'href="/rss.xml"' => 'href="rss.xml"',
        'href="/favicon.svg' => 'href="favicon.svg',
        'href="/apple-touch-icon.png' => 'href="apple-touch-icon.png',
        'href="/styles.css' => 'href="styles.css',
        'href="/fx.css' => 'href="fx.css',
        'href="/reels.css' => 'href="reels.css',
        'src="/script.js' => 'src="script.js',
        'src="/fx.js' => 'src="fx.js',
        'src="/reels.js' => 'src="reels.js',
        'src="/assets/' => 'src="assets/',
    ];

    return str_replace(array_keys($routes), array_values($routes), $html);
}

$learn = static_paths(capture_route($root . '/learn.php', [], '/learn'));
file_put_contents($output . '/learn.html', $learn, LOCK_EX);

$published = all_posts();
foreach ($published as $post) {
    $slug = (string) $post['slug'];
    $html = static_paths(capture_route(
        $root . '/post.php',
        ['slug' => $slug],
        '/learn/' . $slug
    ));
    file_put_contents($output . '/learn-' . $slug . '.html', $html, LOCK_EX);
}

fwrite(STDOUT, 'Static blog ready: ' . count($published) . " published posts.\n");

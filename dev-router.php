<?php
/* ---------------------------------------------------------------------
   Local preview only — NOT used on Hostinger.

   `php -S` ignores .htaccess, so this router reproduces the rewrites the
   live server does: /learn, /learn/<slug>, the feeds, and the extension
   stripping. Run it with:

       php -S 127.0.0.1:8788 dev-router.php

   On Hostinger, .htaccess does all of this and this file is ignored.
   --------------------------------------------------------------------- */

declare(strict_types=1);

$path = parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$path = rtrim($path, '/');
$root = __DIR__;

/* Blocked exactly as .htaccess blocks them. */
if (preg_match('#^/(posts|lib|_archive)(/|$)#', $path)
    || preg_match('#(^|/)\.(?!well-known)#', $path)) {
    http_response_code(403);
    exit('Forbidden');
}

/* Old blog URLs. */
if ($path === '/learn.html') {
    header('Location: /learn', true, 301);
    exit;
}
if ($path === '/learn-hooks.html') {
    header('Location: /learn/hooks', true, 301);
    exit;
}

/* Blog routes. */
if ($path === '' || $path === '/index.html') {
    require $root . '/index.html';
    return true;
}
if ($path === '/learn') {
    require $root . '/learn.php';
    return true;
}
if (preg_match('#^/learn/([a-z0-9][a-z0-9-]*)$#', $path, $m)) {
    $_GET['slug'] = $m[1];
    require $root . '/post.php';
    return true;
}
if ($path === '/sitemap.xml') {
    require $root . '/sitemap.php';
    return true;
}
if ($path === '/rss.xml') {
    require $root . '/rss.php';
    return true;
}
if ($path === '/robots.txt') {
    require $root . '/robots.php';
    return true;
}

/* Real files win. */
$file = realpath($root . $path);
if ($file !== false && is_file($file) && str_starts_with($file, $root)) {
    return false;   // let the built-in server serve it
}

/* Extensionless URLs: try .html, then .php */
foreach (['.html', '.php'] as $ext) {
    $candidate = realpath($root . $path . $ext);
    if ($candidate !== false && is_file($candidate) && str_starts_with($candidate, $root)) {
        require $candidate;
        return true;
    }
}

/* Directory index (e.g. /admin/) */
$index = realpath($root . $path . '/index.php');
if ($index !== false && is_file($index)) {
    require $index;
    return true;
}

http_response_code(404);
echo 'Not found';
return true;

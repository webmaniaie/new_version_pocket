<?php
/* Served at /rss.xml — the Learn feed, newest first. */

declare(strict_types=1);

require_once __DIR__ . '/lib/blog.php';

header('Content-Type: application/rss+xml; charset=utf-8');

$posts = all_posts();
$built = $posts !== [] ? strtotime($posts[0]['date']) : time();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title><?= e(SITE_NAME) ?> — Learn</title>
    <link><?= e(site_url(BASE . '/learn')) ?></link>
    <description>Short-form video strategy from the people making it.</description>
    <language><?= e(strtolower(SITE_LOCALE)) ?></language>
    <lastBuildDate><?= date(DATE_RSS, $built ?: time()) ?></lastBuildDate>
    <atom:link href="<?= e(site_url(BASE . '/rss.xml')) ?>" rel="self" type="application/rss+xml" />
<?php foreach ($posts as $post): $ts = strtotime($post['date']); ?>
    <item>
      <title><?= e($post['title']) ?></title>
      <link><?= e(site_url(post_url($post))) ?></link>
      <guid isPermaLink="true"><?= e(site_url(post_url($post))) ?></guid>
      <pubDate><?= date(DATE_RSS, $ts ?: time()) ?></pubDate>
      <description><?= e($post['excerpt']) ?></description>
<?php foreach ($post['topics'] as $t): ?>
      <category><?= e((string) $t) ?></category>
<?php endforeach; ?>
    </item>
<?php endforeach; ?>
  </channel>
</rss>

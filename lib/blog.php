<?php
/* ---------------------------------------------------------------------
   reels agency — the post store.

   A post is one markdown file in /posts named `YYYY-MM-DD-slug.md`, with
   a frontmatter block on top. There is no database and no build step:
   the folder IS the blog. Drop a file in, it is published; take it out,
   it is gone.
   --------------------------------------------------------------------- */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/markdown.php';

/* Card looks, rotated across the feed so consecutive posts differ. */
const POST_ACCENTS = ['plain', 'pink', 'navy'];

/* ------------------------------------------------------------------ */
/* Frontmatter                                                         */
/* ------------------------------------------------------------------ */

/**
 * Parse the `---` fenced header at the top of a post file.
 *
 * Deliberately a small YAML subset — `key: value`, `key: [a, b]`, and
 * `key:` followed by `- item` lines. Anything fancier is a typo waiting
 * to break a live page, and the admin form only ever writes this much.
 *
 * @return array{0: array<string,mixed>, 1: string} [fields, body]
 */
function parse_frontmatter(string $raw): array
{
    $raw = str_replace(["\r\n", "\r"], "\n", $raw);
    if (strncmp($raw, "---\n", 4) !== 0) {
        return [[], $raw];
    }
    $end = strpos($raw, "\n---", 3);
    if ($end === false) {
        return [[], $raw];
    }
    $head = substr($raw, 4, $end - 3);
    $body = ltrim(substr($raw, $end + 4), "\n");

    $fields  = [];
    $listKey = null;
    foreach (explode("\n", $head) as $line) {
        if (trim($line) === '') {
            continue;
        }
        // Continuation of a `key:` / `- item` block list.
        if ($listKey !== null && preg_match('/^\s*-\s*(.*)$/', $line, $m)) {
            $fields[$listKey][] = frontmatter_scalar($m[1]);
            continue;
        }
        if (!preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\s*:\s*(.*)$/', $line, $m)) {
            continue;
        }
        [, $key, $value] = $m;
        $key   = strtolower($key);
        $value = trim($value);

        if ($value === '') {                     // block list follows
            $fields[$key] = [];
            $listKey = $key;
            continue;
        }
        $listKey = null;
        if (preg_match('/^\[(.*)\]$/', $value, $m2)) {   // inline list
            $items = array_filter(array_map('trim', explode(',', $m2[1])), 'strlen');
            $fields[$key] = array_values(array_map('frontmatter_scalar', $items));
            continue;
        }
        $fields[$key] = frontmatter_scalar($value);
    }
    return [$fields, $body];
}

/** Unquote a scalar and coerce the booleans we care about. */
function frontmatter_scalar(string $v)
{
    $v = trim($v);
    if (strlen($v) >= 2
        && (($v[0] === '"' && substr($v, -1) === '"')
         || ($v[0] === "'" && substr($v, -1) === "'"))) {
        $v = substr($v, 1, -1);
    }
    $low = strtolower($v);
    if ($low === 'true'  || $low === 'yes') return true;
    if ($low === 'false' || $low === 'no')  return false;
    return $v;
}

/** Serialise fields back to frontmatter — used by the admin publisher. */
function build_frontmatter(array $fields): string
{
    $out = "---\n";
    foreach ($fields as $key => $value) {
        if ($value === null || $value === '' || $value === []) {
            continue;
        }
        if (is_array($value)) {
            $out .= $key . ': [' . implode(', ', array_map(
                static fn($v) => frontmatter_quote((string) $v),
                $value
            )) . "]\n";
            continue;
        }
        if (is_bool($value)) {
            $out .= $key . ': ' . ($value ? 'true' : 'false') . "\n";
            continue;
        }
        $out .= $key . ': ' . frontmatter_quote((string) $value) . "\n";
    }
    return $out . "---\n\n";
}

/** Quote only when a bare value would be ambiguous or would break parsing. */
function frontmatter_quote(string $v): string
{
    $v = str_replace(["\n", "\r"], ' ', trim($v));
    if ($v !== '' && !preg_match('/^[\[\]"\']|[,\]]|^(true|false|yes|no)$/i', $v)) {
        return $v;
    }
    // Only a *matching* outer pair is stripped on read, so wrap in
    // whichever quote the text itself does not use.
    $q = str_contains($v, '"') ? "'" : '"';
    return $q . str_replace($q, $q === '"' ? "'" : '"', $v) . $q;
}

/* ------------------------------------------------------------------ */
/* Slugs                                                               */
/* ------------------------------------------------------------------ */

/**
 * Reduce arbitrary text to a safe URL slug.
 *
 * This is also the only guard between a slug from the query string and
 * the filesystem, so it is strict on purpose: lowercase a-z, 0-9 and
 * hyphens, nothing else. No dots, no slashes, so `..` can never survive.
 */
function slugify(string $text): string
{
    $text = strtolower(trim($text));
    if (function_exists('iconv')) {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        if ($ascii !== false) {
            $text = $ascii;
        }
    }
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim(substr($text, 0, 80), '-');
}

/* ------------------------------------------------------------------ */
/* Loading                                                             */
/* ------------------------------------------------------------------ */

/**
 * Read one post file into an array. Returns null if the file is missing.
 * `$withBody` renders the markdown; the feed skips it to stay cheap.
 */
function load_post(string $file, bool $withBody = false): ?array
{
    if (!is_file($file)) {
        return null;
    }
    $raw = file_get_contents($file);
    if ($raw === false) {
        return null;
    }
    [$fm, $body] = parse_frontmatter($raw);

    $name = basename($file, '.md');
    // `2026-08-01-virality` → date 2026-08-01, slug `virality`
    if (preg_match('/^(\d{4}-\d{2}-\d{2})-(.+)$/', $name, $m)) {
        $fileDate = $m[1];
        $fileSlug = $m[2];
    } else {
        $fileDate = null;
        $fileSlug = $name;
    }

    $title = (string) ($fm['title'] ?? 'Untitled');
    $serif = trim((string) ($fm['serif'] ?? ''));

    $post = [
        'file'    => $file,
        'slug'    => slugify((string) ($fm['slug'] ?? $fileSlug)),
        'title'   => $title,
        'serif'   => $serif,
        'lead'    => hero_lead($title, $serif),
        'tag'     => (string) ($fm['tag'] ?? 'Notes'),
        'read'    => (string) ($fm['read'] ?? ''),
        'topics'  => array_values((array) ($fm['topics'] ?? [])),
        'excerpt' => (string) ($fm['excerpt'] ?? ''),
        'cover'   => trim((string) ($fm['cover'] ?? '')),
        'accent'  => (string) ($fm['accent'] ?? ''),
        'date'    => (string) ($fm['date'] ?? $fileDate ?? date('Y-m-d', (int) filemtime($file))),
        'draft'   => (bool)   ($fm['draft'] ?? false),
        'source'  => $body,
        'html'    => $withBody ? render_markdown($body) : '',
    ];
    // A draft with no writing in it is a planned post: the feed shows it
    // as "Dropping soon" rather than pretending it is readable.
    $post['teaser'] = $post['draft'];
    return $post;
}

/**
 * Every post, newest first. Drafts are included only for the admin.
 *
 * @return array<int, array<string,mixed>>
 */
function all_posts(bool $includeDrafts = false): array
{
    $files = glob(POSTS_DIR . '/*.md') ?: [];
    $posts = [];
    foreach ($files as $file) {
        $post = load_post($file);
        if ($post === null || $post['slug'] === '') {
            continue;
        }
        if ($post['draft'] && !$includeDrafts) {
            continue;
        }
        $posts[] = $post;
    }
    // Newest first; drafts sink below published posts so the feed reads
    // as "here is what exists, here is what is coming".
    usort($posts, static function (array $a, array $b): int {
        if ($a['draft'] !== $b['draft']) {
            return $a['draft'] ? 1 : -1;
        }
        return strcmp($b['date'], $a['date']);
    });
    return $posts;
}

/** One published post by slug, body rendered. Null when not found. */
function find_post(string $slug, bool $includeDrafts = false): ?array
{
    $slug = slugify($slug);
    if ($slug === '') {
        return null;
    }
    foreach (glob(POSTS_DIR . '/*.md') ?: [] as $file) {
        $post = load_post($file, true);
        if ($post !== null && $post['slug'] === $slug) {
            if ($post['draft'] && !$includeDrafts) {
                return null;
            }
            return $post;
        }
    }
    return null;
}

/** Public URL of a post. */
function post_url(array $post): string
{
    return BASE . '/learn/' . $post['slug'];
}

/** The filename a post should live under, given its date and slug. */
function post_filename(string $date, string $slug): string
{
    $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : date('Y-m-d');
    return POSTS_DIR . '/' . $date . '-' . slugify($slug) . '.md';
}

/* ------------------------------------------------------------------ */
/* Presentation helpers                                                */
/* ------------------------------------------------------------------ */

/**
 * The hero splits the headline in two: upright lead, serif tail.
 * If the serif tail is literally the end of the title we take it off, so
 * "The first two seconds" + "two seconds." renders as "The first" /
 * "two seconds." rather than repeating the words.
 */
function hero_lead(string $title, string $serif): string
{
    $serifPlain = rtrim(trim($serif), '.');
    if ($serifPlain === '') {
        return $title;
    }
    $len = strlen($serifPlain);
    if ($len > 0 && strcasecmp(substr($title, -$len), $serifPlain) === 0) {
        $lead = rtrim(substr($title, 0, -$len));
        if ($lead !== '') {
            return $lead;
        }
    }
    return $title;
}

/** Card/heading title with the serif tail wrapped in <em>. Escaped. */
function title_with_em(array $post): string
{
    if ($post['serif'] === '') {
        return e($post['title']);
    }
    $lead = $post['lead'];
    if ($lead === $post['title']) {          // tail was not a suffix
        return e($post['title']);
    }
    return e($lead) . ' <em>' . e(rtrim($post['serif'], '.')) . '</em>';
}

/** Accent for card N, honouring an explicit `accent:` when given. */
function post_accent(array $post, int $index): string
{
    $accent = strtolower(trim($post['accent']));
    if (in_array($accent, POST_ACCENTS, true)) {
        return $accent;
    }
    return POST_ACCENTS[$index % count(POST_ACCENTS)];
}

/** Human date for the post header, e.g. "1 August 2026". */
function pretty_date(string $ymd): string
{
    $ts = strtotime($ymd);
    return $ts ? date('j F Y', $ts) : $ymd;
}

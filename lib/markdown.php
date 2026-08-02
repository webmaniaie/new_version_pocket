<?php
/* ---------------------------------------------------------------------
   reels agency — markdown to page.

   Not a general markdown library. It renders the small dialect the blog
   actually uses, straight into this site's own class vocabulary, so a
   written post comes out looking like a designed one: banded sections,
   reveal-on-scroll paragraphs, numbered rows, stat cards.

   The dialect:

     ## [The problem] Nobody *chose* your reel   → new section, eyebrow
                                                   label + heading
     ## Heading {pink}                           → force the band colour
                                                   (white | pink | navy)
     ---                                         → new section, no heading
     ### Sub-heading                             → sub-heading
     Plain paragraph text                        → body copy
     1. Name — hint                              → numbered row with a hint
     1. Just text                                → numbered stat card
     - bullet                                    → bullet list
     > quoted line                               → pull quote
     ![alt](assets/posts/x.jpg)                  → figure (mp4 → video)

   Everything is escaped before any tag is inserted, so a post can never
   inject markup — which also keeps it inside the site's strict CSP.
   --------------------------------------------------------------------- */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/** Render a whole post body. */
function render_markdown(string $source): string
{
    $source = str_replace(["\r\n", "\r"], "\n", trim($source));
    if ($source === '') {
        return '';
    }

    $out       = '';
    $bandTurn  = 0;   // alternates pink → navy for auto-banded sections
    foreach (split_sections($source) as $section) {
        $blocks = parse_blocks($section['body']);

        // A section that is nothing but a numbered list is a feature band:
        // give it colour, the way the hand-built posts do.
        $band = $section['band'];
        if ($band === '') {
            $onlyList = $blocks !== [] && count(array_filter(
                $blocks,
                static fn(array $b) => $b['type'] !== 'ordered'
            )) === 0;
            if ($onlyList) {
                $band = ['pink', 'navy'][$bandTurn++ % 2];
            } else {
                $band = 'white';
            }
        }

        $head = '';
        if ($section['label'] !== '') {
            $head .= '        <p class="reels-label reveal">'
                  . inline($section['label']) . "</p>\n";
        }
        if ($section['heading'] !== '') {
            $head .= '        <h2 class="reels-heading reveal">'
                  . inline($section['heading']) . "</h2>\n";
        }

        // A portrait clip beside copy is this site's signature two-column
        // block, so pull it out of the flow and set the section as a grid.
        $portrait = null;
        foreach ($blocks as $i => $b) {
            if ($b['type'] === 'figure' && stripos($b['lines'][2], 'portrait') !== false) {
                $portrait = $b;
                unset($blocks[$i]);
                break;
            }
        }
        $blocks = array_values($blocks);

        $out .= '      <section class="reels-section reels-section--' . $band . '">' . "\n";
        if ($portrait !== null && ($head !== '' || $blocks !== [])) {
            $out .= '        <div class="reels-grid-2">' . "\n          <div>\n"
                 .  $head . render_blocks($blocks)
                 .  "          </div>\n"
                 .  render_figure($portrait['lines'][0], $portrait['lines'][1], $portrait['lines'][2], true)
                 .  "        </div>\n";
        } else {
            $out .= $head . render_blocks($blocks);
            if ($portrait !== null) {
                $out .= render_figure($portrait['lines'][0], $portrait['lines'][1], $portrait['lines'][2]);
            }
        }
        $out .= "      </section>\n";
    }
    return $out;
}

/* ------------------------------------------------------------------ */
/* Sections                                                            */
/* ------------------------------------------------------------------ */

/**
 * Cut the body into sections at `## ` headings and `---` rules.
 *
 * @return array<int, array{label:string, heading:string, band:string, body:string}>
 */
function split_sections(string $source): array
{
    $sections = [];
    $current  = ['label' => '', 'heading' => '', 'band' => '', 'body' => ''];
    $started  = false;
    $inFence  = false;

    foreach (explode("\n", $source) as $line) {
        if (preg_match('/^\s*```/', $line)) {
            $inFence = !$inFence;
        }
        if (!$inFence && preg_match('/^##\s+(.*\S)\s*$/', $line, $m)) {
            if ($started || trim($current['body']) !== '') {
                $sections[] = $current;
            }
            $current = parse_heading($m[1]);
            $started = true;
            continue;
        }
        if (!$inFence && preg_match('/^-{3,}\s*$/', $line)) {
            if ($started || trim($current['body']) !== '') {
                $sections[] = $current;
            }
            $current = ['label' => '', 'heading' => '', 'band' => '', 'body' => ''];
            $started = true;
            continue;
        }
        $current['body'] .= $line . "\n";
    }
    if ($started || trim($current['body']) !== '') {
        $sections[] = $current;
    }

    // Drop sections that ended up completely empty (e.g. a trailing rule).
    return array_values(array_filter($sections, static fn(array $s) =>
        $s['heading'] !== '' || $s['label'] !== '' || trim($s['body']) !== ''
    ));
}

/** Pull `[eyebrow]`, `{band}` and the heading text out of a `## ` line. */
function parse_heading(string $text): array
{
    $band = '';
    if (preg_match('/\s*\{(white|pink|navy)\}\s*$/i', $text, $m)) {
        $band = strtolower($m[1]);
        $text = trim(substr($text, 0, -strlen($m[0])));
    }
    $label = '';
    if (preg_match('/^\[([^\]]*)\]\s*(.*)$/', $text, $m)) {
        $label = trim($m[1]);
        $text  = trim($m[2]);
    }
    return ['label' => $label, 'heading' => $text, 'band' => $band, 'body' => ''];
}

/* ------------------------------------------------------------------ */
/* Blocks                                                              */
/* ------------------------------------------------------------------ */

/**
 * Group the lines of a section into typed blocks.
 *
 * @return array<int, array{type:string, lines:array<int,string>}>
 */
function parse_blocks(string $body): array
{
    $blocks  = [];
    $current = null;

    $flush = static function () use (&$blocks, &$current): void {
        if ($current !== null && $current['lines'] !== []) {
            $blocks[] = $current;
        }
        $current = null;
    };

    foreach (explode("\n", $body) as $line) {
        $trim = trim($line);

        if ($trim === '') {
            $flush();
            continue;
        }
        if (preg_match('/^!\[([^\]]*)\]\(([^)\s]+)(?:\s+"([^"]*)")?\)$/', $trim, $m)) {
            $flush();
            $blocks[] = ['type' => 'figure', 'lines' => [$m[1], $m[2], $m[3] ?? '']];
            continue;
        }
        if (preg_match('/^###\s+(.*\S)\s*$/', $trim, $m)) {
            $flush();
            $blocks[] = ['type' => 'subheading', 'lines' => [$m[1]]];
            continue;
        }
        if (preg_match('/^\d+[.)]\s+(.*)$/', $trim, $m)) {
            if ($current === null || $current['type'] !== 'ordered') {
                $flush();
                $current = ['type' => 'ordered', 'lines' => []];
            }
            $current['lines'][] = $m[1];
            continue;
        }
        if (preg_match('/^[-*+]\s+(.*)$/', $trim, $m)) {
            if ($current === null || $current['type'] !== 'bullet') {
                $flush();
                $current = ['type' => 'bullet', 'lines' => []];
            }
            $current['lines'][] = $m[1];
            continue;
        }
        if (preg_match('/^>\s?(.*)$/', $trim, $m)) {
            if ($current === null || $current['type'] !== 'quote') {
                $flush();
                $current = ['type' => 'quote', 'lines' => []];
            }
            $current['lines'][] = $m[1];
            continue;
        }
        if ($current === null || $current['type'] !== 'para') {
            $flush();
            $current = ['type' => 'para', 'lines' => []];
        }
        $current['lines'][] = $trim;
    }
    $flush();
    return $blocks;
}

/** Turn typed blocks into the site's markup. */
function render_blocks(array $blocks): string
{
    $out   = '';
    $first = true;

    foreach ($blocks as $block) {
        // The first paragraph clears the heading; the rest sit tighter.
        $gap   = $first ? 'reels-mt' : 'reels-mt-sm';
        $first = false;

        switch ($block['type']) {
            case 'para':
                $out .= '        <p class="reels-body ' . $gap . ' reveal">'
                     . inline(implode(' ', $block['lines'])) . "</p>\n";
                break;

            case 'subheading':
                $out .= '        <h3 class="reels-subheading reels-mt reveal">'
                     . inline($block['lines'][0]) . "</h3>\n";
                break;

            case 'quote':
                $out .= '        <blockquote class="reels-quote reels-mt reveal">'
                     . inline(implode(' ', $block['lines'])) . "</blockquote>\n";
                break;

            case 'bullet':
                $out .= '        <ul class="reels-list reels-mt-sm reveal">' . "\n";
                foreach ($block['lines'] as $item) {
                    $out .= '          <li>' . inline($item) . "</li>\n";
                }
                $out .= "        </ul>\n";
                break;

            case 'ordered':
                $out .= render_ordered($block['lines']);
                break;

            case 'figure':
                $out .= render_figure($block['lines'][0], $block['lines'][1], $block['lines'][2]);
                break;
        }
    }
    return $out;
}

/**
 * A numbered list renders two ways. Items shaped `Name — hint` become the
 * indexed rows used elsewhere on the site; plain items become stat cards.
 * The dash decides, so one list is never half of each.
 */
function render_ordered(array $items): string
{
    $split = [];
    $rows  = true;
    foreach ($items as $item) {
        $parts = preg_split('/\s+(?:—|--|–)\s+/u', $item, 2);
        if (count($parts) === 2) {
            $split[] = [$parts[0], $parts[1]];
        } else {
            $split[] = [$item, ''];
            $rows = false;
        }
    }

    if ($rows) {
        $out = '        <div class="reels-services-index reels-mt">' . "\n";
        foreach ($split as $i => [$name, $hint]) {
            $out .= '          <div class="reels-service-row reveal">' . "\n"
                 .  '            <span class="num">' . sprintf('%02d', $i + 1) . "</span>\n"
                 .  '            <span class="name">' . inline($name) . "</span>\n"
                 .  '            <span class="hint">' . inline($hint) . "</span>\n"
                 .  '            <span class="arr">&#8599;</span>' . "\n"
                 .  "          </div>\n";
        }
        return $out . "        </div>\n";
    }

    $cls = 'reels-stats' . (count($split) === 3 ? ' reels-stats--three' : '');
    $out = '        <div class="' . $cls . ' reels-mt">' . "\n";
    foreach ($split as $i => [$text]) {
        $out .= '          <div class="reels-stat reveal">' . "\n"
             .  '            <strong>' . sprintf('%02d', $i + 1) . "</strong>\n"
             .  '            <span>' . inline($text) . "</span>\n"
             .  "          </div>\n";
    }
    return $out . "        </div>\n";
}

/**
 * An image, or a video when the file is an mp4. `"portrait"` goes 9:16.
 * `$flush` drops the top margin — used when the figure is a grid column
 * and should line up with the copy beside it, not sit below it.
 */
function render_figure(string $alt, string $src, string $hint, bool $flush = false): string
{
    $url = safe_url($src);
    if ($url === '') {
        return '';
    }
    $portrait = stripos($hint, 'portrait') !== false;
    $wrap     = ($portrait ? 'reel-portrait' : 'reels-figure') . ($flush ? '' : ' reels-mt');

    if (preg_match('/\.(mp4|m4v|webm)$/i', $url)) {
        $media = '<video autoplay muted loop playsinline preload="metadata" src="'
               . e($url) . '"></video>';
    } else {
        $media = '<img src="' . e($url) . '" alt="' . e($alt)
               . '" loading="lazy" decoding="async" />';
    }
    $out = '        <div class="' . $wrap . ' reveal">' . $media;
    if (!$portrait && $alt !== '' && stripos($hint, 'caption') !== false) {
        $out .= '<p class="reels-figure-note">' . inline($alt) . '</p>';
    }
    return $out . "</div>\n";
}

/* ------------------------------------------------------------------ */
/* Inline                                                              */
/* ------------------------------------------------------------------ */

/**
 * Escape first, then add inline markup. Because the text is already
 * escaped, a post can contain `<script>` and it stays visible text.
 */
function inline(string $text): string
{
    $html = e(trim($text));

    // `code`
    $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html) ?? $html;
    // **bold**
    $html = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $html) ?? $html;
    // *italic* — the serif accent this site uses in headings
    $html = preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/', '<em>$1</em>', $html) ?? $html;

    // [text](url)
    $html = preg_replace_callback(
        '/\[([^\]]+)\]\(([^)\s]+)\)/',
        static function (array $m): string {
            $url = safe_url(html_entity_decode($m[2], ENT_QUOTES, 'UTF-8'));
            if ($url === '') {
                return $m[1];
            }
            $ext = preg_match('#^https?://#i', $url)
                 ? ' target="_blank" rel="noopener noreferrer"' : '';
            return '<a href="' . e($url) . '"' . $ext . '>' . $m[1] . '</a>';
        },
        $html
    ) ?? $html;

    // " -- " reads as an em dash in prose
    return str_replace(' -- ', ' &mdash; ', $html);
}

/**
 * Allow only URLs that cannot execute: site-relative paths, http(s),
 * mailto and tel. Anything else (javascript:, data:) returns empty and
 * the link degrades to plain text.
 */
function safe_url(string $url): string
{
    $url = trim($url);
    if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url)) {
        return '';
    }
    if (preg_match('#^(https?://|mailto:|tel:)#i', $url)) {
        return $url;
    }
    if (str_starts_with($url, '//')) {          // protocol-relative
        return '';
    }
    if (str_contains($url, ':')) {              // any other scheme
        return '';
    }
    if (str_contains($url, '..')) {
        return '';
    }
    return str_starts_with($url, '/') ? $url : BASE . '/' . ltrim($url, '/');
}

<?php
/**
 * Experience records: every project or role on the Resume page is its own
 * row, managed under /admin/experiences.php. Up to EXPERIENCE_MAX_HIGHLIGHTS
 * of them are highlighted on the Resume page; the rest unfold under
 * "Show all projects".
 *
 * The Resume page text holds a placeholder where the list goes:
 *   <p data-experiences="career-highlights">…</p>
 * live_experiences() swaps it for the rendered list on every request.
 *
 * Work (Nov 2026): the same records feed the Work page. A record ticked
 * "Show on Work" gets a card on the Work page and its own case page at
 * /work/<slug>, with a longer case text (work_body) and a short summary.
 *   <p data-experiences="work">…</p>    the Work cards
 *   <p data-experiences="other">…</p>   every project that is not on Work
 */

const EXPERIENCE_MAX_HIGHLIGHTS = 5;

/** Create the table on first use, so no manual SQL step is needed. */
function ensure_experiences_table(): void
{
    static $done = false;
    if ($done) return;
    db()->exec(
        'CREATE TABLE IF NOT EXISTS experiences (
           id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
           period       VARCHAR(120) NOT NULL DEFAULT \'\',
           title        VARCHAR(200) NOT NULL,
           subtitle     VARCHAR(255) NOT NULL DEFAULT \'\',
           description  TEXT         NOT NULL,
           sort_order   INT          NOT NULL DEFAULT 0,
           is_highlight TINYINT(1)   NOT NULL DEFAULT 0,
           created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
           updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
           PRIMARY KEY (id),
           KEY idx_experiences_order (sort_order, id)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    // Columns for the Work page, added to existing tables too (staging gets
    // live's table structure every night, so this must keep working).
    $have = array_column(q('SHOW COLUMNS FROM experiences'), 'Field');
    $add = [
        'employer'     => "ADD COLUMN employer VARCHAR(120) NOT NULL DEFAULT '' AFTER period",
        'slug'         => 'ADD COLUMN slug VARCHAR(160) NULL AFTER subtitle, ADD UNIQUE KEY uq_experiences_slug (slug)',
        'on_work'      => 'ADD COLUMN on_work TINYINT(1) NOT NULL DEFAULT 0 AFTER is_highlight',
        'work_summary' => 'ADD COLUMN work_summary TEXT NULL AFTER description',
        'work_body'    => 'ADD COLUMN work_body MEDIUMTEXT NULL AFTER work_summary',
    ];
    foreach ($add as $col => $sql) {
        if (!in_array($col, $have, true)) db()->exec('ALTER TABLE experiences ' . $sql);
    }
    $done = true;
}

/* ---------------------------------------------------------------------
 *  Work page
 * ------------------------------------------------------------------- */

/** URL of a case page. */
function experience_url(array $e): string
{
    return '/work/' . rawurlencode((string) $e['slug']);
}

/** A unique slug from the project name, e.g. "hertek-safety". */
function experience_unique_slug(string $title, int $exceptId = 0): string
{
    $base = str_replace('_', '-', slugify($title));
    $slug = $base;
    for ($i = 2; q1('SELECT id FROM experiences WHERE slug = ? AND id <> ?', [$slug, $exceptId]); $i++) {
        $slug = $base . '-' . $i;
    }
    return $slug;
}

/** Records shown on the Work page, in display order. Fills in missing slugs. */
function experiences_on_work(): array
{
    ensure_experiences_table();
    $rows = q('SELECT * FROM experiences WHERE on_work = 1 ORDER BY sort_order, id');
    foreach ($rows as &$e) {
        if (($e['slug'] ?? '') === '') {
            $e['slug'] = experience_unique_slug($e['title'], (int) $e['id']);
            exec_sql('UPDATE experiences SET slug = ? WHERE id = ?', [$e['slug'], $e['id']]);
        }
    }
    return $rows;
}

/** One case by its slug, only when it is on Work. */
function experience_by_slug(string $slug): ?array
{
    ensure_experiences_table();
    return q1('SELECT * FROM experiences WHERE slug = ? AND on_work = 1', [$slug]);
}

/** Short text for a card or a case intro: the summary, else the description. */
function experience_summary(array $e): string
{
    $s = trim((string) ($e['work_summary'] ?? ''));
    return $s !== '' ? $s : trim((string) $e['description']);
}

/**
 * The Work cards, in the same shape and title colours as the Thoughts
 * cards, so both pages read as one site.
 */
function render_work(array $items): string
{
    if (!$items) return '';
    $html = "<ol class=\"article-listing work-listing\">\n";
    foreach ($items as $e) {
        $html .= '  <li class="listing-result">'
               . '<a href="' . esc(experience_url($e)) . '" class="link-figure">'
               . '<figure class="article-figure" style="background-image:' . esc(title_gradient($e['title'])) . '">'
               . '<span class="article-date">' . esc($e['subtitle']) . '</span>'
               . '<strong class="article-title">' . esc($e['title']) . '</strong>'
               . "</figure></a></li>\n";
    }
    return $html . "</ol>\n";
}

/** All experiences in display order. */
function experiences_all(): array
{
    ensure_experiences_table();
    return q('SELECT * FROM experiences ORDER BY sort_order, id');
}

/** How many are highlighted right now, optionally ignoring one record. */
function experiences_highlight_count(int $exceptId = 0): int
{
    ensure_experiences_table();
    $row = q1('SELECT COUNT(*) AS n FROM experiences WHERE is_highlight = 1 AND id <> ?', [$exceptId]);
    return (int) ($row['n'] ?? 0);
}

/** The left column of an entry: the period, with the employer under it. */
function experience_when_html(array $e): string
{
    $employer = trim((string) ($e['employer'] ?? ''));
    return esc($e['period'])
         . ($employer !== '' ? '<span class="employer">' . esc($employer) . '</span>' : '');
}

/** One entry in the same markup the Resume page has always used. */
function experience_item_html(array $e): string
{
    $sub = $e['subtitle'] !== ''
        ? ' <span class="highlight-addon">' . esc($e['subtitle']) . '</span>'
        : '';
    $desc = trim($e['description']) !== ''
        ? "\n  <li class=\"description\">" . nl2br(esc($e['description']), false) . '</li>'
        : '';
    return "<ul class=\"item\">\n"
         . '  <li class="time-period">' . experience_when_html($e) . "</li>\n"
         . '  <li class="highlight">' . esc($e['title']) . $sub . '</li>'
         . $desc . "\n</ul>\n";
}

/**
 * The Career highlights block: highlighted entries first, then the rest
 * behind a "Show all projects" toggle (a native <details>, so it works
 * without JavaScript and with the keyboard).
 */
function render_experiences(array $all): string
{
    if (!$all) return '';
    $top = [];
    $rest = [];
    foreach ($all as $e) {
        if ($e['is_highlight'] && count($top) < EXPERIENCE_MAX_HIGHLIGHTS) $top[] = $e;
        else $rest[] = $e;
    }
    // Nothing ticked yet: just show everything.
    if (!$top) {
        return "<ul class=\"resume\">\n" . implode('', array_map('experience_item_html', $all)) . "</ul>\n";
    }
    $html = "<ul class=\"resume\">\n" . implode('', array_map('experience_item_html', $top)) . "</ul>\n";
    if ($rest) {
        $html .= "<details class=\"all-projects\">\n"
               . '<summary><span class="all-projects-more">Show all projects (' . count($all) . ')</span>'
               . "<span class=\"all-projects-less\">Show fewer projects</span></summary>\n"
               . "<ul class=\"resume\">\n" . implode('', array_map('experience_item_html', $rest)) . "</ul>\n"
               . "</details>\n";
    }
    return $html;
}

/**
 * Replace each placeholder paragraph in page HTML with its live list:
 * data-experiences="career-highlights" (Resume), "work" (the Work cards)
 * or "other" (every project that is not on Work).
 */
function live_experiences(string $html): string
{
    if (stripos($html, 'data-experiences') === false) return $html;
    $pattern = '/<(p|div)\b[^>]*\bdata-experiences="([^"]*)"[^>]*>.*?<\/\1>/is';
    return preg_replace_callback($pattern, function ($m) {
        try {
            switch (strtolower($m[2])) {
                case 'work':
                    return render_work(experiences_on_work());
                case 'other':
                    $other = array_values(array_filter(experiences_all(), fn ($e) => empty($e['on_work'])));
                    return $other ? "<ul class=\"resume\">\n" . implode('', array_map('experience_item_html', $other)) . "</ul>\n" : '';
                default:
                    return render_experiences(experiences_all());
            }
        } catch (Throwable $e) {
            return $m[0]; // keep the placeholder text rather than break the page
        }
    }, $html) ?? $html;
}

/**
 * Parse the hand-written Career highlights list out of a page's HTML.
 * Returns [entries, html-with-placeholder] or null when not found.
 * Only the list itself is replaced; the rest of the page text is left
 * byte for byte as it was.
 */
function parse_career_highlights(string $html): ?array
{
    if (!preg_match('/<h2\b[^>]*>\s*Career highlights\s*<\/h2>\s*/i', $html, $m, PREG_OFFSET_CAPTURE)) {
        return null;
    }
    $listStart = $m[0][1] + strlen($m[0][0]);
    if (!preg_match('/\G<ul\b/i', $html, $mm, 0, $listStart)) return null;

    // Find the </ul> that closes this list, counting nested lists.
    $depth = 0;
    $pos = $listStart;
    $listEnd = null;
    while (preg_match('/<(\/?)ul\b[^>]*>/i', $html, $t, PREG_OFFSET_CAPTURE, $pos)) {
        $depth += $t[1][0] === '/' ? -1 : 1;
        $pos = $t[0][1] + strlen($t[0][0]);
        if ($depth === 0) { $listEnd = $pos; break; }
    }
    if ($listEnd === null) return null;
    $listHtml = substr($html, $listStart, $listEnd - $listStart);

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="__root">' . $listHtml . '</div>',
                   LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $xp = new DOMXPath($doc);
    $list = $xp->query('//div[@id="__root"]/ul')->item(0);
    if (!$list) return null;

    $cls = fn (string $c) => './/li[contains(concat(" ", normalize-space(@class), " "), " ' . $c . ' ")]';
    $text = fn (?DOMNode $n) => $n ? trim(preg_replace('/\s+/u', ' ', $n->textContent)) : '';
    $entries = [];
    foreach ($xp->query('./ul', $list) as $item) {
        $period = $xp->query($cls('time-period'), $item)->item(0);
        $hl     = $xp->query($cls('highlight'), $item)->item(0);
        $desc   = $xp->query($cls('description'), $item)->item(0);
        $title = '';
        $subtitle = '';
        if ($hl) {
            $addon = $xp->query('.//span[contains(@class, "highlight-addon")]', $hl)->item(0);
            $subtitle = $text($addon);
            $titleNode = $hl->cloneNode(true);
            foreach ($xp->query('.//span[contains(@class, "highlight-addon")]', $titleNode) as $s) {
                $s->parentNode->removeChild($s);
            }
            $title = $text($titleNode);
        }
        if ($title === '' && $text($period) === '') continue;
        $entries[] = [
            'period'      => $text($period),
            'title'       => $title,
            'subtitle'    => $subtitle,
            'description' => $text($desc),
        ];
    }
    if (!$entries) return null;

    $placeholder = '<p data-experiences="career-highlights">Career highlights are managed under Experience in the CMS.</p>';
    return [$entries, substr($html, 0, $listStart) . $placeholder . substr($html, $listEnd)];
}

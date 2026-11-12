<?php
/**
 * Talks: every talk is its own record, managed under /admin/talks.php.
 *
 * Two pages show them, through a placeholder in their page text:
 *   Talks page:   <p data-talks="all">…</p>                   every talk, in full
 *   Resume page:  <p data-talks="speaking-engagements">…</p>  a short listing: the
 *                 latest SPEAKING_SHOWN, the rest under "Show all speaking engagements"
 * live_talks() swaps the placeholders on every request, so both pages always
 * show the same talks. Talks are ordered by date, newest first.
 *
 * Until the records exist (before import_talks_from_page() has run), the
 * resume listing falls back to reading the talks out of the Talks page text.
 */

const SPEAKING_SHOWN = 3;

/** Create the table on first use, so no manual SQL step is needed. */
function ensure_talks_table(): void
{
    static $done = false;
    if ($done) return;
    db()->exec(
        'CREATE TABLE IF NOT EXISTS talks (
           id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
           title        VARCHAR(255) NOT NULL,
           event        VARCHAR(255) NOT NULL DEFAULT \'\',
           venue        VARCHAR(255) NOT NULL DEFAULT \'\',
           talk_date    DATE         NOT NULL,
           takeaway     VARCHAR(500) NOT NULL DEFAULT \'\',
           description  TEXT         NOT NULL,
           image        VARCHAR(255) NOT NULL DEFAULT \'\',
           image_width  INT UNSIGNED NOT NULL DEFAULT 0,
           image_height INT UNSIGNED NOT NULL DEFAULT 0,
           image_alt    VARCHAR(255) NOT NULL DEFAULT \'\',
           links        TEXT         NOT NULL,
           created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
           updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
           PRIMARY KEY (id),
           KEY idx_talks_date (talk_date)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $done = true;
}

/** All talks, newest first. */
function talks_all(): array
{
    ensure_talks_table();
    return q('SELECT * FROM talks ORDER BY talk_date DESC, id DESC');
}

/** "November 6, 2026" */
function talk_date_label(string $date): string
{
    $ts = strtotime($date);
    return $ts ? date('F j, Y', $ts) : '';
}

/** Links are stored one per line as "Label | https://…". */
function talk_links(string $links): array
{
    $out = [];
    foreach (preg_split('/\R/', $links) as $line) {
        $parts = array_map('trim', explode('|', $line, 2));
        if (count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '') $out[] = ['label' => $parts[0], 'url' => $parts[1]];
    }
    return $out;
}

/** One talk on the Talks page, in the markup the page has always used. */
function talk_block_html(array $t): string
{
    $img = '';
    if ($t['image'] !== '') {
        $img = '<img src="' . esc($t['image']) . '"'
             . ($t['image_width'] ? ' width="' . (int) $t['image_width'] . '"' : '')
             . ($t['image_height'] ? ' height="' . (int) $t['image_height'] . '"' : '')
             . ' alt="' . esc($t['image_alt']) . '">';
    }
    $where = implode(' &ndash; ', array_filter([esc($t['venue']), esc(talk_date_label($t['talk_date']))], fn ($s) => $s !== ''));
    $html  = "<div class=\"talk\">\n"
           . '<h2>' . esc($t['title']) . "</h2>\n"
           . '<span class="subtitle">' . $img . esc($t['event']) . '</span> <span class="subtitle">' . $where . "</span>\n";
    if (trim($t['takeaway']) !== '') $html .= '<p>' . esc(trim($t['takeaway'])) . "</p>\n";
    foreach (preg_split('/\R\s*\R/', trim($t['description'])) as $para) {
        if (trim($para) !== '') $html .= '<p>' . nl2br(esc(trim($para)), false) . "</p>\n";
    }
    $links = talk_links($t['links']);
    if ($links) {
        $html .= "<ul class=\"bullit\">\n";
        foreach ($links as $l) $html .= '<li><a href="' . esc($l['url']) . '">' . esc($l['label']) . "</a></li>\n";
        $html .= "</ul>\n";
    }
    return $html . "</div>\n";
}

// --- Resume listing -------------------------------------------------------------

/** Short form of each talk: title, event, venue, year. From the records, or the page text before import. */
function talks_for_listing(): array
{
    $rows = talks_all();
    if ($rows) {
        return array_map(fn ($t) => [
            'title' => $t['title'],
            'event' => $t['event'],
            'venue' => $t['venue'],
            'year'  => substr($t['talk_date'], 0, 4),
        ], $rows);
    }
    $page = q1("SELECT body_html FROM pages WHERE slug = 'talks'");
    $parsed = $page ? parse_talk_blocks($page['body_html']) : [];
    return array_map(fn ($t) => [
        'title' => $t['title'],
        'event' => $t['event'],
        'venue' => $t['venue'],
        'year'  => substr($t['talk_date'], 0, 4),
    ], $parsed);
}

/** One talk on the Resume page, in the same markup as the other resume entries. */
function talk_item_html(array $t): string
{
    $where = implode(' · ', array_filter([$t['event'], $t['venue']], fn ($s) => $s !== ''));
    return "<ul class=\"item\">\n"
         . '  <li class="time-period">' . esc($t['year']) . "</li>\n"
         . '  <li class="highlight">' . esc($t['title']) . "</li>\n"
         . ($where !== '' ? '  <li class="location">' . esc($where) . "</li>\n" : '')
         . "</ul>\n";
}

function render_speaking(array $talks): string
{
    if (!$talks) return '';
    $top  = array_slice($talks, 0, SPEAKING_SHOWN);
    $rest = array_slice($talks, SPEAKING_SHOWN);
    $html = "<ul class=\"resume\">\n" . implode('', array_map('talk_item_html', $top)) . "</ul>\n";
    if ($rest) {
        $html .= "<details class=\"all-projects\">\n"
               . '<summary><span class="all-projects-more">Show all speaking engagements (' . count($talks) . ')</span>'
               . "<span class=\"all-projects-less\">Show fewer speaking engagements</span></summary>\n"
               . "<ul class=\"resume\">\n" . implode('', array_map('talk_item_html', $rest)) . "</ul>\n"
               . "</details>\n";
    }
    return $html;
}

/** Replace the data-talks placeholders in page HTML with the live talks. */
function live_talks(string $html): string
{
    if (stripos($html, 'data-talks') === false) return $html;
    $pattern = '/<(p|div)\b[^>]*\bdata-talks="([^"]*)"[^>]*>.*?<\/\1>/is';
    return preg_replace_callback($pattern, function ($m) {
        try {
            $block = $m[2] === 'all'
                ? implode('', array_map('talk_block_html', talks_all()))
                : render_speaking(talks_for_listing());
        } catch (Throwable $e) {
            return $m[0]; // keep the placeholder text rather than break the page
        }
        return $block !== '' ? $block : $m[0];
    }, $html) ?? $html;
}

// --- Import from the old Talks page text ----------------------------------------

/**
 * Read the hand-written talk blocks (<div class="talk">) out of page HTML.
 * Each becomes: title, event, venue, talk_date, takeaway (first paragraph),
 * description (other paragraphs), image (+ size and alt) and links.
 */
function parse_talk_blocks(string $html): array
{
    if (stripos($html, 'class="talk"') === false) return [];
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="__root">' . $html . '</div>',
                   LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $xp = new DOMXPath($doc);
    $clean = fn ($s) => trim(preg_replace('/\s+/u', ' ', $s));

    $talks = [];
    foreach ($xp->query('//div[contains(concat(" ", normalize-space(@class), " "), " talk ")]') as $div) {
        $h2 = $xp->query('./h2', $div)->item(0);
        if (!$h2) continue;
        $subs = $xp->query('./span[contains(concat(" ", normalize-space(@class), " "), " subtitle ")]', $div);
        $event = $subs->item(0) ? $clean($subs->item(0)->textContent) : '';
        $where = $subs->item(1) ? $clean($subs->item(1)->textContent) : '';
        $parts = preg_split('/\s+[–-]\s+/u', $where, 2);
        $venue = trim($parts[0] ?? '');
        $ts    = isset($parts[1]) ? strtotime($parts[1]) : false;
        if (!$ts && preg_match('/(\d{4})\s*$/', $where, $y)) $ts = strtotime($y[1] . '-01-01');

        $img = $xp->query('.//img', $div)->item(0);
        $paras = [];
        foreach ($xp->query('./p', $div) as $p) {
            $t = $clean($p->textContent);
            if ($t !== '') $paras[] = $t;
        }
        $links = [];
        foreach ($xp->query('./ul//a[@href]', $div) as $a) {
            $links[] = $clean($a->textContent) . ' | ' . $a->getAttribute('href');
        }
        $talks[] = [
            'title'        => $clean($h2->textContent),
            'event'        => $event,
            'venue'        => $venue,
            'talk_date'    => $ts ? date('Y-m-d', $ts) : '1970-01-01',
            'takeaway'     => $paras[0] ?? '',
            'description'  => implode("\n\n", array_slice($paras, 1)),
            'image'        => $img ? $img->getAttribute('src') : '',
            'image_width'  => $img ? (int) $img->getAttribute('width') : 0,
            'image_height' => $img ? (int) $img->getAttribute('height') : 0,
            'image_alt'    => $img ? $img->getAttribute('alt') : '',
            'links'        => implode("\n", $links),
        ];
    }
    return $talks;
}

/**
 * One-time move of the hand-written talks into records. Replaces the talk
 * blocks on the Talks page with the data-talks="all" placeholder (at the
 * spot of the first block); the page text around them is kept.
 * Returns the number of talks imported, or 0 when there was nothing to do.
 */
function import_talks_from_page(): int
{
    ensure_talks_table();
    $page = q1("SELECT id, body_html FROM pages WHERE slug = 'talks'");
    if (!$page || stripos($page['body_html'], 'data-talks="all"') !== false) return 0;
    $talks = parse_talk_blocks($page['body_html']);
    if (!$talks) return 0;

    // Remove every talk block (they hold no nested divs) and put the placeholder where the first one was.
    $body = $page['body_html'];
    $first = stripos($body, '<div class="talk">');
    $body = preg_replace('#<div class="talk">.*?</div>\s*#is', '', $body);
    $placeholder = '<p data-talks="all">Talks are managed under Talks in the CMS.</p>' . "\n";
    $body = substr($body, 0, $first) . $placeholder . substr($body, $first);

    $pdo = db();
    $pdo->beginTransaction();
    try {
        exec_sql('DELETE FROM talks');
        foreach ($talks as $t) {
            exec_sql('INSERT INTO talks (title, event, venue, talk_date, takeaway, description, image, image_width, image_height, image_alt, links)
                      VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                [$t['title'], $t['event'], $t['venue'], $t['talk_date'], $t['takeaway'], $t['description'],
                 $t['image'], $t['image_width'], $t['image_height'], $t['image_alt'], $t['links']]);
        }
        exec_sql('UPDATE pages SET body_html = ? WHERE id = ?', [$body, $page['id']]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    return count($talks);
}

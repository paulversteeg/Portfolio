<?php
/**
 * Publications: every publication is its own record, managed under
 * /admin/publications.php, and shown on the Publications page through a
 * placeholder in its page text:  <p data-publications="all">…</p>
 * Ordered by date, newest first. Works like lib/talks.php.
 */

/** Create the table on first use, so no manual SQL step is needed. */
function ensure_publications_table(): void
{
    static $done = false;
    if ($done) return;
    db()->exec(
        'CREATE TABLE IF NOT EXISTS publications (
           id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
           title       VARCHAR(255) NOT NULL,
           kind        VARCHAR(255) NOT NULL DEFAULT \'\',
           pub_date    DATE         NOT NULL,
           summary     TEXT         NOT NULL,
           links       TEXT         NOT NULL,
           publisher   VARCHAR(255) NOT NULL DEFAULT \'\',
           created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
           updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
           PRIMARY KEY (id),
           KEY idx_publications_date (pub_date)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $done = true;
}

/** All publications, newest first. */
function publications_all(): array
{
    ensure_publications_table();
    return q('SELECT * FROM publications ORDER BY pub_date DESC, id DESC');
}

/**
 * Links are stored one per line as "Label | https://… | tag", where the tag is
 * optional: a language (Dutch, English) or "pdf".
 */
function publication_links(string $links): array
{
    $out = [];
    foreach (preg_split('/\R/', $links) as $line) {
        $p = array_map('trim', explode('|', $line, 3));
        if (count($p) >= 2 && $p[0] !== '' && $p[1] !== '') $out[] = ['label' => $p[0], 'url' => $p[1], 'tag' => $p[2] ?? ''];
    }
    return $out;
}

/** One publication on the Publications page, in the markup the page has always used. */
function publication_block_html(array $p): string
{
    $sub = implode(' &ndash; ', array_filter([esc($p['kind']), esc(talk_date_label($p['pub_date']))], fn ($s) => $s !== ''));
    $html = "<div class=\"publication\">\n"
          . '<h2>' . esc($p['title']) . "</h2>\n"
          . '<span class="subtitle">' . $sub . "</span>\n";
    foreach (preg_split('/\R\s*\R/', trim($p['summary'])) as $para) {
        if (trim($para) !== '') $html .= '<p>' . nl2br(esc(trim($para)), false) . "</p>\n";
    }
    $links = publication_links($p['links']);
    if ($links || $p['publisher'] !== '') {
        $html .= "<ul class=\"bullit\">\n";
        foreach ($links as $l) {
            $tag = '';
            if ($l['tag'] !== '') {
                $tag = strtolower($l['tag']) === 'pdf'
                    ? '<span class="pdf">pdf</span>'
                    : '<span class="language">' . esc($l['tag']) . '</span>';
            }
            $html .= '<li><a href="' . esc($l['url']) . '">' . esc($l['label']) . '</a>' . $tag . "</li>\n";
        }
        if ($p['publisher'] !== '') $html .= '<li class="Publisher">' . esc($p['publisher']) . "</li>\n";
        $html .= "</ul>\n";
    }
    return $html . "</div>\n";
}

/** Replace the data-publications placeholder in page HTML with the live list. */
function live_publications(string $html): string
{
    if (stripos($html, 'data-publications') === false) return $html;
    $pattern = '/<(p|div)\b[^>]*\bdata-publications="[^"]*"[^>]*>.*?<\/\1>/is';
    return preg_replace_callback($pattern, function ($m) {
        try {
            $block = implode('', array_map('publication_block_html', publications_all()));
        } catch (Throwable $e) {
            return $m[0];
        }
        return $block !== '' ? $block : $m[0];
    }, $html) ?? $html;
}

// --- Import from the old Publications page text ---------------------------------

/** Read the hand-written <div class="publication"> blocks out of page HTML. */
function parse_publication_blocks(string $html): array
{
    if (stripos($html, 'class="publication"') === false) return [];
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="__root">' . $html . '</div>',
                   LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $xp = new DOMXPath($doc);
    $clean = fn ($s) => trim(preg_replace('/\s+/u', ' ', $s));

    $out = [];
    foreach ($xp->query('//div[contains(concat(" ", normalize-space(@class), " "), " publication ")]') as $div) {
        $h2 = $xp->query('./h2', $div)->item(0);
        if (!$h2) continue;
        $sub = $xp->query('./span[contains(concat(" ", normalize-space(@class), " "), " subtitle ")]', $div)->item(0);
        $subText = $sub ? $clean($sub->textContent) : '';
        $parts = preg_split('/\s+[–-]\s+(?=[A-Z][a-z]+ \d)/u', $subText, 2);   // "Article – May 7, 2014"
        $kind = trim($parts[0] ?? '');
        $ts = isset($parts[1]) ? strtotime($parts[1]) : false;
        if (!$ts && preg_match('/(\d{4})\s*$/', $subText, $y)) $ts = strtotime($y[1] . '-01-01');

        $paras = [];
        foreach ($xp->query('./p', $div) as $p) {
            $t = $clean($p->textContent);
            if ($t !== '') $paras[] = $t;
        }
        $links = [];
        $publisher = '';
        foreach ($xp->query('./ul/li', $div) as $li) {
            $a = $xp->query('.//a[@href]', $li)->item(0);
            if (!$a) { $publisher = $clean($li->textContent); continue; }
            $tag = '';
            if ($xp->query('.//span[contains(@class,"pdf")]', $li)->item(0)) $tag = 'pdf';
            elseif ($lang = $xp->query('.//span[contains(@class,"language")]', $li)->item(0)) $tag = $clean($lang->textContent);
            $links[] = $clean($a->textContent) . ' | ' . $a->getAttribute('href') . ($tag !== '' ? ' | ' . $tag : '');
        }
        $out[] = [
            'title'     => $clean($h2->textContent),
            'kind'      => $kind,
            'pub_date'  => $ts ? date('Y-m-d', $ts) : '1970-01-01',
            'summary'   => implode("\n\n", $paras),
            'links'     => implode("\n", $links),
            'publisher' => $publisher,
        ];
    }
    return $out;
}

/**
 * One-time move of the hand-written publications into records. Replaces the
 * blocks on the Publications page with the data-publications="all"
 * placeholder. Returns the number imported, or 0 when there was nothing to do.
 */
function import_publications_from_page(): int
{
    ensure_publications_table();
    $page = q1("SELECT id, body_html FROM pages WHERE slug = 'publications'");
    if (!$page || stripos($page['body_html'], 'data-publications="all"') !== false) return 0;
    $items = parse_publication_blocks($page['body_html']);
    if (!$items) return 0;

    $body  = $page['body_html'];
    $first = stripos($body, '<div class="publication">');
    $body  = preg_replace('#<div class="publication">.*?</div>\s*#is', '', $body);
    $body  = substr($body, 0, $first) . '<p data-publications="all">Publications are managed under Publications in the CMS.</p>' . "\n" . substr($body, $first);

    $pdo = db();
    $pdo->beginTransaction();
    try {
        exec_sql('DELETE FROM publications');
        foreach ($items as $p) {
            exec_sql('INSERT INTO publications (title, kind, pub_date, summary, links, publisher) VALUES (?,?,?,?,?,?)',
                [$p['title'], $p['kind'], $p['pub_date'], $p['summary'], $p['links'], $p['publisher']]);
        }
        exec_sql('UPDATE pages SET body_html = ? WHERE id = ?', [$body, $page['id']]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    return count($items);
}

<?php
/**
 * Speaking engagements on the Resume page, read from the Talks page.
 *
 * The Talks page is the single source: every talk there is a
 * <div class="talk"> with an <h2> title and two subtitles (event, and
 * "venue – date"). The Resume page holds a placeholder:
 *   <p data-talks="speaking-engagements">…</p>
 * live_talks() swaps it on every request for a short listing: the latest
 * SPEAKING_SHOWN talks, and the rest under "Show all talks", in the same
 * markup and toggle as Career highlights. Add or change a talk on the
 * Talks page and the resume follows.
 */

const SPEAKING_SHOWN = 3;

/** Talks from the Talks page, in page order (newest first): title, event, venue, year. */
function talks_from_page(): array
{
    $page = q1("SELECT body_html FROM pages WHERE slug = 'talks'");
    if (!$page || trim($page['body_html']) === '') return [];

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="__root">' . $page['body_html'] . '</div>',
                   LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $xp = new DOMXPath($doc);

    $talks = [];
    foreach ($xp->query('//div[contains(concat(" ", normalize-space(@class), " "), " talk ")]') as $div) {
        $h2 = $xp->query('.//h2', $div)->item(0);
        if (!$h2) continue;
        $subs = [];
        foreach ($xp->query('.//span[contains(concat(" ", normalize-space(@class), " "), " subtitle ")]', $div) as $s) {
            $subs[] = trim(preg_replace('/\s+/u', ' ', $s->textContent));
        }
        $event = $subs[0] ?? '';
        $where = $subs[1] ?? '';
        // "Act Eventstudios Arnhem – November 6, 2026": venue before the dash, year at the end.
        $parts = preg_split('/\s+[–-]\s+/u', $where, 2);
        $venue = trim($parts[0] ?? '');
        $year  = preg_match('/(\d{4})\s*$/', $where, $m) ? $m[1] : '';
        $talks[] = [
            'title' => trim(preg_replace('/\s+/u', ' ', $h2->textContent)),
            'event' => $event,
            'venue' => $venue,
            'year'  => $year,
        ];
    }
    return $talks;
}

/** One talk, in the same markup as the other resume entries. */
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
               . '<summary><span class="all-projects-more">Show all talks (' . count($talks) . ')</span>'
               . "<span class=\"all-projects-less\">Show fewer talks</span></summary>\n"
               . "<ul class=\"resume\">\n" . implode('', array_map('talk_item_html', $rest)) . "</ul>\n"
               . "</details>\n";
    }
    return $html;
}

/** Replace the placeholder paragraph in page HTML with the live listing. */
function live_talks(string $html): string
{
    if (stripos($html, 'data-talks') === false) return $html;
    $pattern = '/<(p|div)\b[^>]*\bdata-talks="[^"]*"[^>]*>.*?<\/\1>/is';
    if (!preg_match($pattern, $html)) return $html;
    try {
        $block = render_speaking(talks_from_page());
    } catch (Throwable $e) {
        return $html; // keep the placeholder text rather than break the page
    }
    if ($block === '') return $html;
    return preg_replace_callback($pattern, fn () => $block, $html, 1) ?? $html;
}

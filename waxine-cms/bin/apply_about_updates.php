<?php
/**
 * About page (Nov 2026): new order of the page text, About last in the menu,
 * and the subtitle 'me as a design strategist'. Runs from deploy.sh and can be run by hand: php bin/apply_about_updates.php.
 * Safe to run again: it does nothing once the page has its new layout.
 *
 * New order: intro · Contact details · "My work" (the rest of the story) ·
 * the "full resume" line as a call-out block · Other channels.
 *
 * APPLY_ON: 'staging' while being reviewed, 'all' once approved for live.
 */
require_once dirname(__DIR__) . '/lib/db.php';

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

const APPLY_ON = 'all';

$env = (config()['site']['env'] ?? 'live') === 'staging' ? 'staging' : 'live';
if (APPLY_ON !== 'all' && APPLY_ON !== $env) { echo "apply_about_updates: not on $env yet\n"; exit(0); }

// --- Menu: About after every other page ------------------------------------------
$about = q1("SELECT id, nav_order, body_html FROM pages WHERE slug = 'about'");
if (!$about) { echo "apply_about_updates: no About page\n"; exit(0); }
$max = q1("SELECT MAX(nav_order) AS m FROM pages WHERE in_nav = 1 AND slug <> 'about'");
$last = (int) ($max['m'] ?? 0) + 10;
if ((int) $about['nav_order'] < $last - 9) {
    exec_sql('UPDATE pages SET nav_order = ? WHERE id = ?', [$last, $about['id']]);
    echo "about: moved to the end of the menu (order $last)\n";
} else {
    echo "about: already last in the menu\n";
}

// --- Subtitle in the menu and the page heading -----------------------------------
$subtitle = 'me as a design strategist';
$cur = q1("SELECT subtitle FROM pages WHERE id = ?", [$about['id']]);
if (($cur['subtitle'] ?? '') !== $subtitle) {
    exec_sql('UPDATE pages SET subtitle = ? WHERE id = ?', [$subtitle, $about['id']]);
    echo "about: subtitle set to '$subtitle'\n";
}

// --- Page text --------------------------------------------------------------------
$crlf = strpos($about['body_html'], "\r\n") !== false;
$body = str_replace("\r\n", "\n", $about['body_html']);
// Heading of the last section. RENAME_ON: 'staging' while being reviewed, 'all' once approved.
const RENAME_ON = 'all';
$oldHeadings = ['<h2>Other channels:</h2>', '<h2>Find me elsewhere:</h2>'];   // the second was a first try on staging
$hasOld = array_filter($oldHeadings, fn ($h) => strpos($body, $h) !== false);
if ((RENAME_ON === 'all' || RENAME_ON === $env) && $hasOld && strpos($body, 'class="callout"') !== false) {
    $body = str_replace($oldHeadings, '<h2>Where else to find me:</h2>', $body);
    exec_sql('UPDATE pages SET body_html = ? WHERE id = ?', [$crlf ? str_replace("\n", "\r\n", $body) : $body, $about['id']]);
    echo "about: 'Other channels' renamed\n";
}
// Instagram after Facebook and YouTube after Vimeo (same review flag as the heading).
if ((RENAME_ON === 'all' || RENAME_ON === $env) && strpos($body, 'instagram.com/paulversteeg') === false) {
    $links = [
        '<li><a href="https://www.facebook.com/paulversteeg">Facebook</a></li>'
            => "\n<li><a href=\"https://www.instagram.com/paulversteeg/\">Instagram</a></li>",
        '<li><a href="https://vimeo.com/paulversteeg">Vimeo</a></li>'
            => "\n<li><a href=\"https://www.youtube.com/@paulversteeg\">YouTube</a></li>",
    ];
    $added = 0;
    foreach ($links as $after => $item) {
        if (strpos($body, $after) !== false) { $body = str_replace($after, $after . $item, $body); $added++; }
    }
    if ($added) {
        exec_sql('UPDATE pages SET body_html = ? WHERE id = ?', [$crlf ? str_replace("\n", "\r\n", $body) : $body, $about['id']]);
        echo "about: $added channel link(s) added\n";
    }
}
// Timeless wording (Nov 2026): no "over a decade", "recently", "not so long ago" or
// other time markers that age, and the "lang" typo fixed. TEXT_ON: 'staging' while
// being reviewed, 'all' once approved.
const TEXT_ON = 'staging';
if (TEXT_ON === 'all' || TEXT_ON === $env) {
    $timeless = [
        '/have over a decade of experience as a designer and consultant/' => 'have a wealth of experience as a designer and consultant',
        '/Some time later I realised a complete new social intranet/'      => 'I realised a completely new social intranet',
        '/Not so lang ago I worked for multiple financial clients/'        => 'I worked for multiple financial clients',
        '/ and after that I was responsible for designing/'                => ' and I was responsible for designing',
        '/Recently I(?:\x{2019}|\'|&rsquo;|&#8217;)ve worked for the most important telecom company/u' => 'I worked for the most important telecom company',
    ];
    $changed = 0;
    foreach ($timeless as $re => $to) {
        $body = preg_replace($re, $to, $body, -1, $n);
        $changed += $n;
    }
    if ($changed) {
        exec_sql('UPDATE pages SET body_html = ? WHERE id = ?', [$crlf ? str_replace("\n", "\r\n", $body) : $body, $about['id']]);
        echo "about: $changed time marker(s) removed\n";
    }
}

if (strpos($body, 'class="callout"') !== false) { echo "about: page already has its new layout\n"; exit(0); }

$posContact  = strpos($body, '<h2>Contact details:</h2>');
$posChannels = strpos($body, '<h2>Other channels:</h2>');
$posResume   = strpos($body, '<p>Please check my <a href="/resume">full resume</a> here.</p>');
$introEnd    = strpos($body, '</p>');
if ($posContact === false || $posChannels === false || $posResume === false || $introEnd === false
    || !($introEnd < $posResume && $posResume < $posContact && $posContact < $posChannels)) {
    echo "about: page text is not as expected, left unchanged\n";
    exit(0);
}
$introEnd += 4;

$tidy = fn ($html) => trim(preg_replace('#<p>\s*</p>#', '', $html));
$intro    = substr($body, 0, $introEnd);
$work     = $tidy(substr($body, $introEnd, $posResume - $introEnd));
$contact  = $tidy(substr($body, $posContact, $posChannels - $posContact));
$channels = $tidy(substr($body, $posChannels));
$callout  = '<p class="callout">Please check my <a href="/resume">full resume</a> here.</p>';

$new = $intro . "\n" . $contact . "\n<h2>My work:</h2>\n" . $work . "\n" . $callout . "\n" . $channels;
exec_sql('UPDATE pages SET body_html = ? WHERE id = ?', [$crlf ? str_replace("\n", "\r\n", $new) : $new, $about['id']]);
echo "about: page text rearranged\n";

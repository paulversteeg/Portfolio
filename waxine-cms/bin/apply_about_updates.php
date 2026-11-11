<?php
/**
 * About page (Nov 2026): new order of the page text, and About last in the
 * menu. Runs from deploy.sh and can be run by hand: php bin/apply_about_updates.php.
 * Safe to run again: it does nothing once the page has its new layout.
 *
 * New order: intro · Contact details · "My work" (the rest of the story) ·
 * the "full resume" line as a call-out block · Other channels.
 *
 * APPLY_ON: 'staging' while being reviewed, 'all' once approved for live.
 */
require_once dirname(__DIR__) . '/lib/db.php';

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

const APPLY_ON = 'staging';

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

// --- Page text --------------------------------------------------------------------
$crlf = strpos($about['body_html'], "\r\n") !== false;
$body = str_replace("\r\n", "\n", $about['body_html']);
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

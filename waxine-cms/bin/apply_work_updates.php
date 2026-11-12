<?php
/**
 * Work page (Nov 2026): Work becomes the home page and the first menu item,
 * Thoughts moves to /thoughts, Resume joins the menu.
 *
 * Menu, from what I do to who I am:
 *   Work · Talks · Publications · Thoughts · Resume · About
 *
 * Runs from deploy.sh (and after the nightly staging refresh) and can be
 * run by hand: php bin/apply_work_updates.php. Safe to run again: every
 * step checks first and does nothing once done.
 *
 * APPLY_ON: 'staging' while being reviewed, 'all' once approved for live.
 */
require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/helpers.php';

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

const APPLY_ON = 'staging';

$env = (config()['site']['env'] ?? 'live') === 'staging' ? 'staging' : 'live';
if (APPLY_ON !== 'all' && APPLY_ON !== $env) { echo "apply_work_updates: not on $env yet\n"; exit(0); }

ensure_experiences_table();

// --- Pages for Work and Thoughts ----------------------------------------------------
$newPages = [
    'work' => [
        'title'    => 'Work',
        'subtitle' => "I'm proud of",
        'body'     => '<p data-experiences="work">The Work cards are managed under Experience in the CMS: tick "Show on the Work page".</p>',
    ],
    'thoughts' => [
        'title'    => 'Thoughts',
        'subtitle' => (config()['site']['tagline'] ?? '') ?: 'that came to mind',
        'body'     => '<p>This page shows the list of Thoughts (your posts). Only its title, subtitle and place in the menu are used.</p>',
    ],
];
foreach ($newPages as $slug => $p) {
    if (!q1('SELECT id FROM pages WHERE slug = ?', [$slug])) {
        exec_sql('INSERT INTO pages (title, slug, subtitle, body_html, nav_order, in_nav) VALUES (?,?,?,?,0,1)',
            [$p['title'], $slug, $p['subtitle'], $p['body']]);
        echo "$slug: page created\n";
    }
}

// --- Menu order ---------------------------------------------------------------------
$order = ['work' => 10, 'talks' => 20, 'publications' => 30, 'thoughts' => 40, 'resume' => 50, 'about' => 60];
foreach ($order as $slug => $n) {
    $pg = q1('SELECT id, nav_order, in_nav FROM pages WHERE slug = ?', [$slug]);
    if (!$pg) { echo "$slug: no such page, skipped\n"; continue; }
    if ((int) $pg['nav_order'] !== $n || (int) $pg['in_nav'] !== 1) {
        exec_sql('UPDATE pages SET nav_order = ?, in_nav = 1 WHERE id = ?', [$n, $pg['id']]);
        echo "$slug: menu position $n\n";
    }
}

// --- First Work cards: the current Career highlights --------------------------------
// Only while nothing is on Work yet; after that the choice is made in the CMS.
if (!q1('SELECT id FROM experiences WHERE on_work = 1')) {
    $picked = q('SELECT id, title FROM experiences WHERE is_highlight = 1 ORDER BY sort_order, id');
    foreach ($picked as $e) {
        exec_sql('UPDATE experiences SET on_work = 1, slug = COALESCE(slug, ?) WHERE id = ?',
            [experience_unique_slug($e['title'], (int) $e['id']), $e['id']]);
    }
    echo 'work: ' . count($picked) . " Career highlights put on Work\n";
}

// --- Employer in its own field --------------------------------------------------------
// "fresk.digital · 2022 – present" becomes period "2022 – present" with
// employer "fresk.digital", shown under the period on the Resume page.
foreach (q("SELECT id, period FROM experiences WHERE employer = '' AND period LIKE '% · %'") as $e) {
    [$employer, $period] = array_map('trim', explode(' · ', $e['period'], 2));
    exec_sql('UPDATE experiences SET employer = ?, period = ? WHERE id = ?', [$employer, $period, $e['id']]);
    echo "employer: '$employer' split from the period of record {$e['id']}\n";
}

echo "apply_work_updates: done on $env\n";

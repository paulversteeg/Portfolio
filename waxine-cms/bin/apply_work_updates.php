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

// --- Employers for the projects before fresk.digital (as told by Paul, Nov 2026) ---
// Matched on the start of the project name and, where a name occurs twice, the period.
$employers = [
    ['NOC*NSF',                  'February 2021', 'INFO'],
    ['Sevvy',                    'August 2020',   'INFO'],
    ['OV-fiets',                 null,            'INFO'],
    ['Lloyds Bank',              null,            'INFO'],
    ['Lely',                     null,            'Mirabeau'],
    ['Etihad',                   null,            'Mirabeau'],
    ['Philips',                  null,            'Mirabeau'],
    ['China Southern',           null,            'Mirabeau'],
    ['FrieslandCampina',         null,            'Mirabeau'],
    ['PARKnSHOP',                null,            'Mirabeau'],
    ['Leaseplan',                null,            'Mirabeau'],
    ['Maxeda',                   null,            'Mirabeau'],
];
foreach ($employers as [$name, $periodStart, $employer]) {
    $sql = "UPDATE experiences SET employer = ? WHERE employer = '' AND title LIKE ?";
    $args = [$employer, $name . '%'];
    if ($periodStart !== null) { $sql .= ' AND period LIKE ?'; $args[] = $periodStart . '%'; }
    if (exec_sql($sql, $args)) echo "employer: $name -> $employer\n";
}

// --- NOC*NSF twice: concept and design system at INFO, inspection portal at fresk ---
$nocOld = 'Design of a platform that informs sport unions and municipalities about the possibilities to realise a high quality and sustainable sport infrastructure, including the product roadmap.';
$nocNew = 'Concept and design system for a platform that informs sport unions and municipalities about the possibilities to realise high-quality, sustainable sport infrastructure, including the product roadmap.';
if (exec_sql('UPDATE experiences SET description = ? WHERE title LIKE ? AND description = ?', [$nocNew, 'NOC*NSF%', $nocOld])) {
    echo "noc*nsf: INFO description now about concept and design system\n";
}
$nocInfo = q1("SELECT sort_order FROM experiences WHERE title LIKE 'NOC*NSF%' AND employer = 'INFO'");
if ($nocInfo && !q1("SELECT id FROM experiences WHERE title LIKE 'NOC*NSF%' AND employer = 'fresk.digital'")) {
    exec_sql('INSERT INTO experiences (period, employer, title, subtitle, description, sort_order, is_highlight, on_work)
              VALUES (?,?,?,?,?,?,0,0)', [
        'July 2023 – present',
        'fresk.digital',
        'NOC*NSF',
        'Inspection portal and knowledge base',
        'We started by validating the current implementation of the Sport Infrastructure Platform and what users wanted sharpened, then set up a roadmap to tackle it in the right order and priority. Together with Virginia Rispoli I created the concept for the inspection portal: one flow that follows a sports field from plan to approval. The municipality knows what is coming, inspection bodies create and inspect the field in the same system and check it against the right standards, and the result appears in the facility passport. I also shaped the concept for the knowledge base.',
        (int) $nocInfo['sort_order'] - 1,
    ]);
    echo "noc*nsf: fresk.digital entry for the inspection portal added\n";
}

// --- Earlier projects with a strategic side (as told by Paul, Nov 2026) -------------
// Added once, by name. 'after' places the entry directly after an existing one in
// the date order; without it the entry goes to the end of the list.
$earlier = [
    ['KPN', 'Online experience of the future', 'Mirabeau', 'September 2013 – March 2014', 'Leaseplan',
     'KPN wanted half of all sales and service online by 2016. We designed its online experience of the future: a prototype for every device, tested with consumers and small businesses. The big question: how does KPN stand out without competing with Apple and Google? As a device-independent hub, with one personal account to manage your packages and enjoy interactive TV your way, at home or on the go.'],
    ['Bibliotheek.nl', 'National library platform', 'Mirabeau', '2012 – 2016', null,
     'The website with the most registered members in the Netherlands. The new way of organising, searching and visiting the collection put people first: how someone experiences the collection and searches in it. That meant bringing stakeholders on several levels along in a different way of thinking. A more interactive, visually stronger library raised the value of the product. That in turn shaped the subscription structure: what each subscription offers, and how you sign up for it.'],
    ['Randstad', 'Recruitment platform', 'Connect Holland', '2010 – 2012', null,
     'An extensive recruitment website, built on a sharp understanding of who it was really for. Placing seasonal workers on critical work calls for flexibility and a buffer. When someone can\'t make it, the next person is asked quickly, so the work goes on and, for example, a harvest still gets in on the day it has to.'],
    ['Oogziekenhuis Rotterdam', 'Social intranet', 'Connect Holland', '2010 – 2012', null,
     'A completely new social intranet for the Rotterdam Eye Hospital: a professional Facebook of sorts, where groups post updates and departments easily share knowledge and organisational news with each other, instead of each working in its own silo.'],
    ['Consumentenbond', 'Product comparison tool', 'Puurpxl', '2007 – 2009', null,
     'Lead designer for the website of the Dutch consumer association, including a comparison tool for the products it tests. The key was understanding why members compare, what they want to see side by side and what they want to take away from it. At the same time, the quality of the comparison had to show the value of a paid membership. The interface had to balance independence with well-founded advice: the Best Buy and Best Tested labels stand out, without writing off the other products.'],
];
foreach ($earlier as [$title, $subtitle, $employer, $period, $after, $description]) {
    if (q1('SELECT id FROM experiences WHERE title = ?', [$title])) continue;
    $prev = $after !== null ? q1('SELECT sort_order FROM experiences WHERE title LIKE ? ORDER BY sort_order LIMIT 1', [$after . '%']) : null;
    if ($prev) {
        // Make room directly after the given entry.
        exec_sql('UPDATE experiences SET sort_order = sort_order + 1 WHERE sort_order > ?', [$prev['sort_order']]);
        $order = (int) $prev['sort_order'] + 1;
    } else {
        $order = (int) (q1('SELECT COALESCE(MAX(sort_order), 0) AS m FROM experiences')['m']) + 10;
    }
    exec_sql('INSERT INTO experiences (period, employer, title, subtitle, description, sort_order, is_highlight, on_work)
              VALUES (?,?,?,?,?,?,0,0)', [$period, $employer, $title, $subtitle, $description, $order]);
    echo "earlier: $title ($employer) added\n";
}

// KPN: shorter, lighter text (replaces the first version where it is still in place).
if (exec_sql("UPDATE experiences SET description = ? WHERE title = 'KPN' AND description LIKE 'KPN aimed to handle half%'", ['KPN wanted half of all sales and service online by 2016. We designed its online experience of the future: a prototype for every device, tested with consumers and small businesses. The big question: how does KPN stand out without competing with Apple and Google? As a device-independent hub, with one personal account to manage your packages and enjoy interactive TV your way, at home or on the go.'])) {
    echo "kpn: description shortened\n";
}

echo "apply_work_updates: done on $env\n";

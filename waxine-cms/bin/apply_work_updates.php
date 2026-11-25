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
 * WORK_DATA_ON: 'staging' while being reviewed, 'all' once approved for live.
 * WORK_ON: the same for the Work page and the new menu, which stay on staging
 * while the Work content is refined; the resume data below already goes live.
 */
require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/helpers.php';

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

const WORK_DATA_ON = 'all';
const WORK_ON  = 'all';

$env = (config()['site']['env'] ?? 'live') === 'staging' ? 'staging' : 'live';
if (WORK_DATA_ON !== 'all' && WORK_DATA_ON !== $env) { echo "apply_work_updates: not on $env yet\n"; return; }

ensure_experiences_table();

// --- Shorter menu subtitles (option A, Nov 2026; live, also without Work) ----------
// Only replaces the previous wording, so a subtitle changed in the CMS stays.
$subtitles = [
    'talks'        => ['and other stages I was on', 'I gave'],
    'publications' => ['I created',                 'I wrote'],
    'thoughts'     => ['that came to mind',         'on my mind'],
    'about'        => ['me as a design strategist', 'who I am'],
];
foreach ($subtitles as $slug => [$from, $to]) {
    if (exec_sql('UPDATE pages SET subtitle = ? WHERE slug = ? AND subtitle = ?', [$to, $slug, $from])) {
        echo "$slug: subtitle now '$to'\n";
    }
}

if (WORK_ON === 'all' || WORK_ON === $env) {
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

// --- The projects on Work (chosen by Paul, Nov 2026) --------------------------------
// Hertek (recent), NOC*NSF at fresk.digital, Etihad (international) and China
// Southern (strategic). Put on Work while nothing was chosen yet, or while
// the selection is still the first automatic one (the Career highlights);
// once the choice is made in the CMS on live, that choice stays. The order
// is the Experience order, newest first.
// Since Nov 2026 also the five career highlights, whose full story now lives
// on their case page (see "Full stories on Work" below).
$workPicks = [
    ['Senses',           null],
    ['Hertek%',          null],
    ['D-Connect',        null],
    ['Rocsys',           null],
    ['Ravlling',         null],
    ['NOC*NSF%',         'fresk.digital'],
    ['Etihad%',          null],
    ['China Southern%',  null],
];
$firstPicks = ['Hertek%' => null, 'NOC*NSF%' => 'fresk.digital', 'Etihad%' => null, 'China Southern%' => null];
$pickIds = function (array $picks): array {
    $ids = [];
    foreach ($picks as [$like, $employer]) {
        $sql = 'SELECT id FROM experiences WHERE title LIKE ?' . ($employer !== null ? ' AND employer = ?' : '') . ' ORDER BY sort_order LIMIT 1';
        $row = q1($sql, $employer !== null ? [$like, $employer] : [$like]);
        if ($row) $ids[] = (int) $row['id'];
    }
    sort($ids);
    return $ids;
};
$onWork     = array_map('intval', array_column(q('SELECT id FROM experiences WHERE on_work = 1 ORDER BY id'), 'id'));
$highlights = array_map('intval', array_column(q('SELECT id FROM experiences WHERE is_highlight = 1 ORDER BY id'), 'id'));
$firstSet   = $pickIds(array_map(null, array_keys($firstPicks), array_values($firstPicks)));
if (!$onWork || $onWork == $highlights || $onWork == $firstSet) {
    $ids = [];
    foreach ($workPicks as [$like, $employer]) {
        $sql = 'SELECT id, title FROM experiences WHERE title LIKE ?' . ($employer !== null ? ' AND employer = ?' : '') . ' ORDER BY sort_order LIMIT 1';
        $row = q1($sql, $employer !== null ? [$like, $employer] : [$like]);
        if (!$row) { echo "work: no project like '$like', skipped\n"; continue; }
        $ids[] = (int) $row['id'];
        exec_sql('UPDATE experiences SET on_work = 1, slug = COALESCE(slug, ?) WHERE id = ?',
            [experience_unique_slug($row['title'], (int) $row['id']), $row['id']]);
    }
    if ($ids) {
        exec_sql('UPDATE experiences SET on_work = 0 WHERE on_work = 1 AND id NOT IN (' . implode(',', $ids) . ')');
        echo 'work: ' . count($ids) . " chosen projects on Work\n";
    }
}

// --- The question each project answered (Work page, Nov 2026) -----------------------
// A first wording, drawn from the project texts; only filled where it is
// still empty, so Paul's own wording in the CMS always stays.
$questions = [
    ['NOC*NSF%',          'fresk.digital', 'How does a sports field get from plan to approval in one flow?'],
    ['NOC*NSF%',          'INFO',          'How do sport unions and municipalities find out what good, sustainable sport facilities take?'],
    ['KPN',               null,            'How does KPN stand out without competing with Apple and Google?'],
    ['Bibliotheek.nl',    null,            'How do you organise a national library around people instead of its collection?'],
    ['Randstad',          null,            'How does the work go on when someone can\'t make it?'],
    ['Oogziekenhuis%',    null,            'How do departments share what they know instead of working in silos?'],
    ['Consumentenbond',   null,            'How do you give well-founded advice without writing off the rest?'],
    ['Etihad%',           null,            'How does one design language hold up across dozens of stakeholders, three studios and four time zones?'],
    ['China Southern%',   null,            'Do European design methods work for an airline app in China?'],
];
foreach ($questions as [$like, $employer, $q]) {
    $sql = "UPDATE experiences SET work_question = ? WHERE (work_question IS NULL OR work_question = '') AND title LIKE ?";
    $args = [$q, $like];
    if ($employer !== null) { $sql .= ' AND employer = ?'; $args[] = $employer; }
    if (exec_sql($sql, $args)) echo "work question: $like" . ($employer ? " ($employer)" : '') . "\n";
}

// --- Full stories on Work, short highlights on Resume (Paul, Nov 2026) ---------------
// The five career highlights had grown into full case texts. The full story
// moves to the case page (work_body); the Resume keeps three sentences: what
// it is, my role, the strongest result. Runs only while the description still
// holds the long text (its marker), so a later edit in the CMS stays.
// The short texts avoid the anchors of apply_resume_updates.php, so its
// additions never land in them again.
$stories = [
    'Senses' => [
        'marker'   => 'Prijsvrij’s idea',
        'question' => 'Can tailor-made travel in the higher segment be built on a standard package?',
        'short'    => 'A B2C e-commerce platform for tailor-made travel in the higher segment, built on a standard package with modules and connected to Amadeus. I guided the overall style, coached the designers and safeguarded the concept and scope during implementation. Senses grew to over €5 million in revenue in its first year and won the travel industry’s Start-Up of the Year Award in 2026.',
        'body'     => "<p>A launched B2C e-commerce platform to configure and book tailor-made travel in the higher segment: premium hotels, flights and options beyond the standard package, connected to Amadeus.</p>\n<p>Prijsvrij’s idea was that the higher segment wants tailor-made travel that can still be built on a standard package, extended with modules. With them we explored where technology and the existing systems set the limits, and how to meet specific wishes within them.</p>\n<p>I guided the overall style and coached the designers strategically, realised part of the UI, and safeguarded the concept and scope during implementation, including some project management.</p>\n<p>In its first year, Senses grew to over €5 million in revenue and won the travel industry’s Start-Up of the Year Award in 2026.</p>",
    ],
    'Hertek Safety' => [
        'marker'   => 'Hertek’s tools had grown over the years',
        'question' => 'How do you take the administration off a technician’s hands?',
        'short'    => 'A portal for Hertek Safety, the Dutch fire-safety company, that brings a set of separate applications together in one. I laid the foundation of the concept and the product roadmap, and still guide its direction. Technicians now walk a building once instead of three or four times, without entering measurements twice.',
        'body'     => "<p>A working product for Hertek Safety, the Dutch fire-safety company. Hertek’s tools had grown over the years into a set of separate applications with overlapping functions: a maintenance technician worked across several of them, and the information never connected, which held back growth. Bundling them into one portal cut the cost of maintaining each application separately and became a springboard for combining data.</p>\n<p>I laid the foundation of the concept, took part in the research and the concept and design of the portal, and set up the product roadmap. Now the portal grows with new features made possible by its sharpened direction, which I partly design and strategically guide.</p>\n<p>Shadowing a single inspection round showed what the workaround cost: technicians walked the building three or four times instead of once, and wrote down every measurement by hand to enter it again later. The portal guides them through what to measure and records it as they go, so the administration is taken off their hands, a job takes fewer rounds and a technician can help more customers a day.</p>",
    ],
    'Ravlling' => [
        'marker'   => 'Each feature started from a hypothesis',
        'question' => 'How do you inspire people to travel responsibly, and see it in the numbers?',
        'short'    => 'A travel inspiration platform that encourages people to travel responsibly. I helped shape the concept and branding, designed the full design system and set up a feature roadmap built on hypotheses and a KPI model. Ravlling went live at the end of 2022, was featured on PhocusWire’s Startup Stage and was nominated for the Columbus Travel Awards 2024.',
        'body'     => "<p>A travel inspiration platform that encourages people to travel responsibly. I contributed to the concept and branding, set up and designed the full design system, and shaped the feature roadmap: which functionality comes at which point in the development path, why it matters and how it shows in the numbers. Each feature started from a hypothesis and an expected effect in a KPI model, so after release we could measure whether it delivered: some assumptions didn’t hold, and some of the ways people used it surprised us.</p>\n<p>Along the way I built hands-on knowledge of affiliate marketing and of setting up and using graph databases.</p>\n<p>Ravlling went live at the end of 2022, starting with the Nordic countries, was featured on PhocusWire’s Startup Stage and was nominated for the Columbus Travel Awards 2024 as sustainable start-up of the year.</p>",
    ],
    'D-Connect' => [
        'marker'   => 'The number of trips booked through D-Connect',
        'question' => 'Why were travel advisors bypassing their own tool?',
        'short'    => 'A platform for the travel advisors of D-reizen, built around their conversation with the traveller, after advisors had been bypassing the existing tool. I did the research with a colleague, made the service blueprints and designed the platform. It went live in all D-reizen stores, advisors stopped using other tools, and the filters they use most moved up in the interface.',
        'body'     => "<p>Travel advisors were bypassing the existing tool for alternative tools. The new platform offers layered information, using the conversation between advisor and traveller at the travel agency as its guiding principle.</p>\n<p>I did the research together with a colleague, created the service blueprints and designed the platform.</p>\n<p>The number of trips booked through D-Connect was the leading measure. We worked from hypotheses and tracked which functions advisors used most, so a popular filter moved higher up in the interface.</p>\n<p>D-Connect went live in all D-reizen stores, and advisors stopped falling back on other tools: it is faster and more intuitive, with search filters that follow their conversations with travellers and layers of information that match an advisor’s expertise.</p>",
    ],
    'Rocsys' => [
        'marker'   => 'make that reliability tangible',
        'question' => 'How do you make the reliability of a charging robot tangible?',
        'short'    => 'An interface system for the charging robots of Rocsys that makes their reliability tangible: how they meet the contract, and where deviations came from. I designed it and discussed the market launch and key choices at C-level. It replaced a spreadsheet-like first version and became part of the Rocsys Portal, launched in April 2025.',
        'body'     => "<p>An interface system that gives insight into the performance of a robotic arm that charges electric cars and trucks, and the bigger picture around it: how to bring it to market and which key choices to make, discussed at C-level.</p>\n<p>For customers only one thing counts: the robot does its job, reliably. The strategic question was how to make that reliability tangible: showing how Rocsys meets the contract, and tracing deviations it didn’t cause, for instance when an operator worked outside the terms of the contract.</p>\n<p>The fleet tool replaced a spreadsheet-like first version that didn’t present the information clearly, and became part of the Rocsys Portal, launched in April 2025 within the first integrated platform for hands-free charging of autonomous vehicles, with partners such as APM Terminals.</p>",
    ],
];
foreach ($stories as $title => $s) {
    $row = q1('SELECT id, description, work_body, work_question FROM experiences WHERE title = ? ORDER BY id LIMIT 1', [$title]);
    if (!$row) { echo "story: $title not found, skipped\n"; continue; }
    if (trim((string) $row['work_question']) === '') {
        exec_sql('UPDATE experiences SET work_question = ? WHERE id = ?', [$s['question'], $row['id']]);
        echo "work question: $title\n";
    }
    if (strpos(str_replace("\r\n", "\n", (string) $row['description']), $s['marker']) === false) continue;
    if (trim((string) $row['work_body']) === '') {
        exec_sql('UPDATE experiences SET work_body = ? WHERE id = ?', [$s['body'], $row['id']]);
        echo "story: $title, full text on its case page\n";
    } else {
        echo "story: $title already has a case text, kept\n";
    }
    exec_sql('UPDATE experiences SET description = ? WHERE id = ?', [$s['short'], $row['id']]);
    echo "story: $title, short text on the Resume\n";
}
// A one-line opening looks bare beside the drop cap: join it with the next paragraph.
foreach ([
    ['Hertek Safety', "Dutch fire-safety company.</p>\n<p>Hertek’s tools", 'Dutch fire-safety company. Hertek’s tools'],
    ['Ravlling',      "travel responsibly.</p>\n<p>I contributed",          'travel responsibly. I contributed'],
] as [$title, $from, $to]) {
    if (exec_sql('UPDATE experiences SET work_body = REPLACE(work_body, ?, ?) WHERE title = ? AND work_body LIKE ?', [$from, $to, $title, '%' . $from . '%'])) {
        echo "story: $title, opening joined\n";
    }
}
// Hertek: how AI speeds up the portal's growth (as told by Paul, Nov 2026).
$hertekAi = '<p>AI now speeds up how the portal grows. A request for a new feature starts with an AI-assisted intake and comes back as a scenario. The design system is fully available to AI, so prototypes are made quickly, and it becomes clear much sooner which solutions work and which don&rsquo;t.</p>';
$row = q1("SELECT id, work_body FROM experiences WHERE title = 'Hertek Safety' ORDER BY id LIMIT 1");
if ($row && strpos((string) $row['work_body'], 'AI now speeds up how the portal grows') === false) {
    $b = preg_replace('/(which I partly design and strategically guide\.<\/p>)/', '$1' . "\n" . $hertekAi, (string) $row['work_body'], 1, $n);
    if ($n) {
        exec_sql('UPDATE experiences SET work_body = ? WHERE id = ?', [$b, $row['id']]);
        echo "story: Hertek, AI in how the portal grows\n";
    }
}
// FASt: fresk's product and design system for frontline work (Paul's draft, Nov 2026).
// Added once, at the top of Work and of the projects; later edits in the CMS stay.
if (!q1("SELECT id FROM experiences WHERE title = 'FASt'")) {
    $top = (int) (q1('SELECT COALESCE(MIN(sort_order), 0) AS m FROM experiences')['m']) - 1;
    exec_sql('INSERT INTO experiences (period, employer, title, subtitle, slug, description, work_question, work_body, sort_order, is_highlight, on_work)
              VALUES (?,?,?,?,?,?,?,?,?,0,1)', [
        '2026 – present',
        'fresk.digital',
        'FASt',
        'Product and design system for frontline work',
        'fast',
        'A product and design system for frontline professionals, grown from what we kept designing for Hertek Safety, Rocsys and NOC*NSF: a technician at an installation who needs to know what to do, do it safely and record it. I shaped the concept, the module structure and the design system, where one Figma file serves every client through modes for brand, content, settings and size. Onboarding a new client means filling in modes rather than redesigning screens, and a prospect sees its own product in the first conversation.',
        'What does a frontline professional at an installation need?',
        "<p>In projects for Hertek Safety, Rocsys and NOC*NSF we kept designing for the same moment: a technician standing at an installation who needs to know what to do, do it safely and record it. FASt brings that moment together in one product for frontline professionals. It is not SaaS and not off-the-shelf: it is a semi-finished product, with a custom element for every client. The Cockpit is always the starting point. Clients add the modules they need: Guided Workflow, Knowledge Centre and Object Overview.</p>\n"
      . "<p>I shaped the concept, the module structure and the FASt design system. One Figma file drives every client through four sets of variables: brand, content, settings and size. A client’s colours, typeface and corner radius live in its brand mode, its terminology in content, and its modules in settings. The token system itself never changes per client, and every colour combination is checked against WCAG AA. With AI I turn the Figma tokens, text styles and components one to one into a clickable front-end prototype.</p>\n"
      . "<p>So onboarding a new client is a matter of filling in modes rather than redesigning screens, and we can show a prospect its own product, in its own brand and language, in the first conversation.</p>\n"
      . "<p>Design decisions start in the field: gloves, poor signal, a colleague taking over halfway through a job. That is why work is split into chapters with visible progress, steps you tick off, values that can come in through IoT, and team knowledge that shows up at the exact step where it is needed. Red is reserved for deviations, so no brand gets it as an action colour.</p>",
        $top,
    ]);
    echo "fast: added at the top of Work\n";
}
if (exec_sql("UPDATE experiences SET work_question = ? WHERE title = 'FASt' AND work_question = ?", ['What does a frontline professional at an installation need?', 'What does a technician at an installation need, whatever the client?'])) {
    echo "fast: question about the frontline professional\n";
}
} else {
    echo "work page and menu: not on $env yet\n";
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

// --- Project periods (as told by Paul, Nov 2026) -------------------------------------
foreach ([['Senses', 'May 2023 – October 2025'], ['D-Connect', 'January 2023 – January 2025'], ['Ravlling', 'February 2022 – September 2023'], ['Rocsys', 'October 2023 – November 2025']] as [$title, $period]) {
    if (exec_sql("UPDATE experiences SET period = ? WHERE title = ? AND period <> ?", [$period, $title, $period])) {
        echo "period: $title now $period\n";
    }
}

echo "apply_work_updates: done on $env\n";

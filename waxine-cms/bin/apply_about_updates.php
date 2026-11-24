<?php
/**
 * About page (Nov 2026): new order of the page text, About last in the menu,
 * and the subtitle 'me as a design strategist'. Runs from deploy.sh and can be run by hand: php bin/apply_about_updates.php.
 * Safe to run again: it does nothing once the page has its new layout.
 *
 * New order: intro · Contact details · "My work" (the rest of the story) ·
 * the "full resume" line as a call-out block · Other channels.
 *
 * ABOUT_ON: 'staging' while being reviewed, 'all' once approved for live.
 */
require_once dirname(__DIR__) . '/lib/db.php';

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

const ABOUT_ON = 'all';

$env = (config()['site']['env'] ?? 'live') === 'staging' ? 'staging' : 'live';
if (ABOUT_ON !== 'all' && ABOUT_ON !== $env) { echo "apply_about_updates: not on $env yet\n"; return; }

// --- Menu: About after every other page ------------------------------------------
$about = q1("SELECT id, nav_order, body_html FROM pages WHERE slug = 'about'");
if (!$about) { echo "apply_about_updates: no About page\n"; return; }
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
// Only when there is no subtitle yet: a later choice (see apply_work_updates) stays.
$cur = q1("SELECT subtitle FROM pages WHERE id = ?", [$about['id']]);
if (($cur['subtitle'] ?? '') === '') {
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
const TEXT_ON = 'all';
if (TEXT_ON === 'all' || TEXT_ON === $env) {
    $timeless = [
        '/have (?:over a decade|a wealth) of experience as a designer and consultant/' => 'have years of experience as a designer and consultant',
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

// Mission (Nov 2026): from the 2015 Dynamic Design wording to the design
// strategist of today, keeping its roots. MISSION_ON: 'staging' while reviewed.
const MISSION_ON = 'all';
if (MISSION_ON === 'all' || MISSION_ON === $env) {
    $mission = '<p>My mission is to turn complex digital challenges into clear choices. I start from the people who use a service and the context they use it in, whether that is a traveller at the counter or a technician on a ladder, and connect that to what the business needs. That way of thinking grew from my early work on dynamic design: interfaces that adapt to someone&rsquo;s personal context. I like to get real as quickly as possible: research, a first concept and testing with real users, so that decisions rest on what people actually do, and strategy stays connected to what can be built.</p>';
    $new = preg_replace('/<p[^>]*>\s*My mission is to challenge user centered design.*?<\/p>/s', $mission, $body, 1, $n);
    if ($n) {
        $body = $new;
        exec_sql('UPDATE pages SET body_html = ? WHERE id = ?', [$crlf ? str_replace("\n", "\r\n", $body) : $body, $about['id']]);
        echo "about: mission rewritten\n";
    }
}

// Clients (Nov 2026): the long career paragraph becomes one line on international
// work and the clients by sector. CLIENTS_ON: 'staging' while reviewed.
const CLIENTS_ON = 'all';
if (CLIENTS_ON === 'all' || CLIENTS_ON === $env) {
    $clients = <<<'HTML'
<p>I am at home in international, cross-cultural settings, with hands-on experience on projects in Europe, the Middle East and Asia: from a design language for <span class="clientreference">Etihad Airways</span>, created with studios in Amsterdam, Abu Dhabi and Kolkata across four time zones, to an app for <a class="clientreference" href="https://www.csair.com">China Southern Airlines</a> and an online store for <a class="clientreference" href="https://www.parknshop.com">PARKnSHOP</a> in Hong Kong.</p>
<ul class="clients">
<li><strong>Travel and hospitality:</strong> <span class="clientreference">Etihad</span>, <a class="clientreference" href="https://www.csair.com">China Southern Airlines</a>, <a class="clientreference" href="https://www.jumeirah.com/">Jumeirah</a>, <a class="clientreference" href="https://www.eurail.com">Eurail</a>, <span class="clientreference">D-reizen</span>, <span class="clientreference">Prijsvrij</span></li>
<li><strong>Retail and e-commerce:</strong> A.S. Watson (<a class="clientreference" href="https://www.parknshop.com">PARKnSHOP</a>), Maxeda (<a class="clientreference" href="https://www.praxis.nl">Praxis</a>)</li>
<li><strong>Industry and agriculture:</strong> <a class="clientreference" href="https://www.frieslandcampina.com">FrieslandCampina</a>, <a class="clientreference" href="https://www.lely.com/">Lely</a>, <span class="clientreference">Philips</span>, <span class="clientreference">Hertek Safety</span>, <span class="clientreference">Rocsys</span></li>
<li><strong>Finance:</strong> <a class="clientreference" href="https://www.ing.nl">ING</a>, <a class="clientreference" href="https://www.aegon.nl">Aegon</a>, <a class="clientreference" href="https://www.lloydsbank.nl/">Lloyds Bank</a></li>
<li><strong>Telecom, media and public services:</strong> <a class="clientreference" href="https://www.kpn.com">KPN</a>, <a class="clientreference" href="https://www.nu.nl">nu.nl</a>, <a class="clientreference" href="https://www.bndestem.nl">BN DeStem</a>, <a class="clientreference" href="https://www.bibliotheek.nl">National Library of the Netherlands</a>, <a class="clientreference" href="https://www.consumentenbond.nl">Consumentenbond</a></li>
<li><strong>Healthcare, work, sport and education:</strong> <a class="clientreference" href="https://www.oogziekenhuis.nl">Rotterdam Eye Hospital</a>, <a class="clientreference" href="https://www.randstad.nl">Randstad</a>, <span class="clientreference">NOC*NSF</span>, <a class="clientreference" href="https://www.hogeschoolrotterdam.nl">Hogeschool Rotterdam</a>, <a class="clientreference" href="https://www.eaie.org">EAIE</a></li>
</ul>
HTML;
    $new = preg_replace('/<p[^>]*>\s*My career started when I became the lead designer.*?<\/p>/s', $clients, $body, 1, $n);
    if ($n) {
        $body = $new;
        exec_sql('UPDATE pages SET body_html = ? WHERE id = ?', [$crlf ? str_replace("\n", "\r\n", $body) : $body, $about['id']]);
        echo "about: clients by sector\n";
    }
}

// Leadership (Nov 2026): the size of the department led at Mirabeau. LEAD_ON: 'staging' while reviewed.
const LEAD_ON = 'all';
if (LEAD_ON === 'all' || LEAD_ON === $env) {
    $new = preg_replace(
        '/I(?:\x{2019}|\'|&rsquo;|&#8217;)ve worked as UX Director leading the Concept &(?:amp;)? Design team at Mirabeau and organise regular meetups/u',
        'As UX Director at Mirabeau, I led the Concept &amp; Design department of around 50 people: interaction designers, visual designers, front-end developers and creative consultants. I also organise regular meetups',
        $body, 1, $n);
    if ($n) {
        $body = $new;
        exec_sql('UPDATE pages SET body_html = ? WHERE id = ?', [$crlf ? str_replace("\n", "\r\n", $body) : $body, $about['id']]);
        echo "about: leadership at Mirabeau\n";
    }
}

// Work (Nov 2026): the hands-on and strategy paragraphs become one, strategy first,
// without project lists (those are on the Resume) and without "in the last four years".
$work = '<p>My work combines strategy with hands-on design. Since co-founding fresk.digital in 2022, I mostly set up the concept and the roadmap, guide the design direction and discuss the key choices with leadership, for clients ranging from tailor-made travel to fire safety and robotics. I still enjoy designing myself, from a visual style and a complete design system to tools that support professionals in their daily work.</p>';
$new = preg_replace('/<p[^>]*>\s*Part of my work is hands-on visual, UX and product design\..*?<\/p>\s*<p[^>]*>\s*In the last four years my work moved more towards strategy\..*?<\/p>/s', $work, $body, 1, $n);
if ($n) {
    $body = $new;
    exec_sql('UPDATE pages SET body_html = ? WHERE id = ?', [$crlf ? str_replace("\n", "\r\n", $body) : $body, $about['id']]);
    echo "about: work paragraphs combined\n";
}

// Opening (Nov 2026): the blog origin in one sentence, without names or date,
// and a line about life outside work, fitting the subtitle "who I am".
$open = ' I write about my work and the things I encounter in <a href="/thoughts">Thoughts</a>.</p>' . "\n"
      . '<p>Outside of work, I teach drums at Jubal Drum &amp; Bugle Corps in Dordrecht, and as a Dordoloog I dig into the origins of things on the Island of Dordrecht and guide tours.</p>';
$new = preg_replace('/<br\s*\/?>\s*As a means to focus my thoughts I started to write.*?User Experience\.<\/p>/s', $open, $body, 1, $n);
if ($n) {
    $body = $new;
    exec_sql('UPDATE pages SET body_html = ? WHERE id = ?', [$crlf ? str_replace("\n", "\r\n", $body) : $body, $about['id']]);
    echo "about: opening shortened, life outside work added\n";
}

// Opening, second pass (Nov 2026): more about the work up front; "clear choices" moves
// from the mission to the opening, and life outside work moves to the end of the page.
$outside = '<p>Outside of work, I teach drums at Jubal Drum &amp; Bugle Corps in Dordrecht, and as a Dordoloog I dig into the origins of things on the Island of Dordrecht and guide tours.</p>';
if (strpos($body, 'I turn complex digital challenges into clear choices') === false) {
    $intro = 'My name is Paul. I&rsquo;m a design strategist, <i>Head of Design Strategy</i> and one of the founders of fresk.digital. I turn complex digital challenges into clear choices: I develop digital strategies and roadmaps for products and platforms, from e-commerce and omnichannel journeys to tools for frontline professionals, and I work with leadership teams from first concept to launch. I write about my work and the things I encounter in <a href="/thoughts">Thoughts</a>.</p>';
    $b = preg_replace('/My name is Paul and I(?:\x{2019}|\'|&rsquo;)m the <i>Head of Design Strategy<\/i> and one of the founders of fresk\.digital\. I write about my work and the things I encounter in <a href="\/thoughts">Thoughts<\/a>\.<\/p>/u', $intro, $body, 1, $n1);
    $b = str_replace("\n" . $outside, '', $b, $n2);
    if (!$n2) $b = str_replace($outside, '', $b, $n2);
    $b = preg_replace('/<p>My mission is to turn complex digital challenges into clear choices\. I start from/', '<p>I start from', $b, 1, $n3);
    $b = preg_replace('/(<p[^>]*>\s*Please check my)/', $outside . "\n" . '$1', $b, 1, $n4);
    if ($n1 && $n4) {
        $body = $b;
        exec_sql('UPDATE pages SET body_html = ? WHERE id = ?', [$crlf ? str_replace("\n", "\r\n", $body) : $body, $about['id']]);
        echo "about: opening about the work, life outside work at the end\n";
    } else {
        echo "about: opening second pass skipped (intro $n1, outside $n2, mission $n3, resume link $n4)\n";
    }
}

// Background and work in one paragraph (Nov 2026): education, how I work now,
// and the broad view that leads into the clients by sector.
if (strpos($body, 'I hold a Bachelor of Science degree in Interaction Design and have years of experience as a designer and consultant. My work combines') === false) {
    $combined = '<p>I hold a Bachelor of Science degree in Interaction Design and have years of experience as a designer and consultant. My work combines strategy with hands-on design. Since co-founding fresk.digital in 2022, I mostly set up the concept and the roadmap, guide the design direction and discuss the key choices with leadership, for clients ranging from tailor-made travel to fire safety and robotics. I still enjoy designing myself, from a visual style and a complete design system to tools that support professionals in their daily work. Working for clients in many sectors gives me a broad view of the craft.</p>';
    $b = preg_replace('/<p[^>]*>\s*I hold a Bachelor of Science degree in Interaction Design.*?<\/p>/s', $combined, $body, 1, $n1);
    $b = preg_replace('/\n?<p[^>]*>\s*My work combines strategy with hands-on design\..*?<\/p>/s', '', $b, 1, $n2);
    if ($n1 && $n2) {
        $body = $b;
        exec_sql('UPDATE pages SET body_html = ? WHERE id = ?', [$crlf ? str_replace("\n", "\r\n", $body) : $body, $about['id']]);
        echo "about: background and work in one paragraph\n";
    } else {
        echo "about: background and work skipped (education $n1, work $n2)\n";
    }
}

// How I work, without the company history (Nov 2026).
$body2 = str_replace('Since co-founding fresk.digital in 2022, I mostly set up the concept', 'I mostly set up the concept', $body, $n);
if ($n) {
    $body = $body2;
    exec_sql('UPDATE pages SET body_html = ? WHERE id = ?', [$crlf ? str_replace("\n", "\r\n", $body) : $body, $about['id']]);
    echo "about: 'since co-founding fresk.digital' removed\n";
}

if (strpos($body, 'class="callout"') !== false) { echo "about: page already has its new layout\n"; return; }

$posContact  = strpos($body, '<h2>Contact details:</h2>');
$posChannels = strpos($body, '<h2>Other channels:</h2>');
$posResume   = strpos($body, '<p>Please check my <a href="/resume">full resume</a> here.</p>');
$introEnd    = strpos($body, '</p>');
if ($posContact === false || $posChannels === false || $posResume === false || $introEnd === false
    || !($introEnd < $posResume && $posResume < $posContact && $posContact < $posChannels)) {
    echo "about: page text is not as expected, left unchanged\n";
    return;
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

<?php
/**
 * Apply the November 2026 resume updates (profile, contact details, skills,
 * languages and the Etihad Airways project).
 *
 * Runs from deploy.sh, like apply_talk_updates.php, and can be run by hand:
 * php bin/apply_resume_updates.php. Safe to run again: each change is a
 * replacement of an exact piece of text, skipped when the new text is
 * already there or the old text is gone (then it says so).
 *
 * Changes marked 'env' => 'staging' are a preview: only applied on staging
 * (which gets a fresh copy of live every night). Remove the marker once
 * approved, and the next live deploy applies them.
 */
require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/experiences.php';

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

$env = (config()['site']['env'] ?? 'live') === 'staging' ? 'staging' : 'live';
echo "Environment: $env\n";
$applies = fn ($c) => ($c['env'] ?? 'all') === 'all' || $c['env'] === $env;

// The fresk work-experience line on how Paul led the team (follows "and ").
const FRESK_LEAD_LINE = 'led the design team until January 2026. Building on what we learned at Mirabeau, we defined the human-centred designer we needed: a mix of service designer, creative consultant, interaction and visual designer, with its competencies, a growth matrix, a design vision for the discipline, regular critique sessions and our own review system. Coaching the team this way let it run itself within our direction, so a colleague could take over the lead.';

// --- Resume page text: [old => new] replacements --------------------------------
$pageChanges = [
    [
        'what' => 'profile: international, cross-cultural experience',
        'old'  => 'independent of who is across the table.</p>',
        'new'  => 'independent of who is across the table. I am at home in international, cross-cultural settings, with hands-on experience on projects in Europe, the Middle East and Asia. Pro-active and outspoken, I take the lead and get stakeholders enthusiastic.</p>',
    ],
    [
        'what' => 'contact details: LinkedIn and location',
        'old'  => '<a href="tel:+31650525000">+316 5052 5000</a></address>',
        'new'  => '<a href="tel:+31650525000">+316 5052 5000</a><br><a href="https://www.linkedin.com/in/paulversteeg/">linkedin.com/in/paulversteeg</a><br>Dordrecht, The Netherlands</address>',
    ],
    [
        'what' => 'new sections: Skills and Languages (after Education)',
        'old'  => '<h2>Speaking engagements</h2>',
        'new'  => '<h2>Skills</h2>
<ul class="resume">
<ul class="item">
<li class="highlight">Design strategy</li>
<li class="location">Digital strategies, product roadmaps and key choices with leadership teams</li>
</ul>
<ul class="item">
<li class="highlight">Service design</li>
<li class="location">Research, customer journeys and service blueprints</li>
</ul>
<ul class="item">
<li class="highlight">UX design</li>
<li class="location">Concepts, interaction design and design systems</li>
</ul>
<ul class="item">
<li class="highlight">Workshop facilitation</li>
<li class="location">Interactive workshops with clients, stakeholders and users</li>
</ul>
</ul>
<h2>Languages</h2>
<ul class="resume">
<ul class="item">
<li class="highlight">Dutch</li>
<li class="location">Native</li>
</ul>
<ul class="item">
<li class="highlight">English</li>
<li class="location">Fluent</li>
</ul>
</ul>
<h2>Speaking engagements</h2>',
        'marker' => '<h2>Skills</h2>',   // already there? then skip
    ],
    [
        'what'    => 'speaking engagements: listing from the Talks page',
        // The hand-written list between the heading and Side activities.
        'pattern' => '#(<h2>Speaking engagements</h2>\s*)<ul class="resume">.*?</ul>\s*</ul>#s',
        'new'     => '$1<p data-talks="speaking-engagements">Speaking engagements are filled in from the Talks page.</p>',
        'marker'  => 'data-talks="speaking-engagements"',
    ],
    [
        'what'    => 'round photo beside the profile, contact details below it',
        'pattern' => '#(<h2>Profile</h2>\s*)(<p>.*?</p>)(.*?<div class="address">)(<img class="image-profile"[^>]*>)#s',
        'new'     => '$1<div class="profile">$4$2</div>$3',
        'marker'  => '<div class="profile">',
    ],
    [
        'what'    => 'profile: opening sentence as its own lead paragraph',
        'pattern' => '#<p><b>(Design strategist with .*?clear choices\.)</b>\s*#s',
        'new'     => "<p class=\"lead\">\$1</p>\n<p>",
        'marker'  => '<p class="lead">',
    ],
    [
        'what'    => 'side activities: Dordoloog',
        'pattern' => '#(<h2>Side activities</h2>\s*<ul class="resume">\s*)#',
        'new'     => "\$1<ul class=\"item\">\n<li class=\"time-period\">2024 &ndash; present</li>\n<li class=\"highlight\">Dordoloog</li>\n<li class=\"location\">Local historian and tour guide on the Island of Dordrecht</li>\n</ul>\n",
        'marker'  => '>Dordoloog<',
    ],
    [
        'what'    => 'side activities: Dordoloog wording (staging had the first version)',
        'old'     => 'Local historian and guide, researching where things come from on the Island of Dordrecht and sharing it on guided tours',
        'new'     => 'Local historian and tour guide on the Island of Dordrecht',
    ],
    [
        'what'    => 'new section: Tools I like to work with (after Skills)',
        'pattern' => '#(<h2>Languages</h2>)#',
        'new'     => '<h2>Tools I like to work with</h2>
<ul class="resume">
<ul class="item">
<li class="highlight">Design &amp; presenting</li>
<li class="location">Figma, Keynote</li>
</ul>
<ul class="item">
<li class="highlight">AI</li>
<li class="location">Claude, Gemini, Midjourney, ElevenLabs</li>
</ul>
<ul class="item">
<li class="highlight">Audio &amp; video</li>
<li class="location">Logic Pro, Final Cut Pro</li>
</ul>
<ul class="item">
<li class="highlight">Organising work</li>
<li class="location">ClickUp</li>
</ul>
</ul>
$1',
        'marker'  => '<h2>Tools I like to work with</h2>',
    ],
    [
        'what'    => 'tools: Organising work (staging had three groups)',
        'old'     => '<li class="location">Logic Pro, Final Cut Pro</li>
</ul>
</ul>',
        'new'     => '<li class="location">Logic Pro, Final Cut Pro</li>
</ul>
<ul class="item">
<li class="highlight">Organising work</li>
<li class="location">ClickUp</li>
</ul>
</ul>',
    ],
    [
        'what'    => 'side activities: drum teacher still ongoing',
        'old'     => '<li class="time-period">2018</li>
<li class="highlight">Drum teacher</li>',
        'new'     => '<li class="time-period">2018 &ndash; present</li>
<li class="highlight">Drum teacher</li>',
    ],
    [
        // The first version had only a line under UX Director; take it out before the full set.
        'what'    => 'work experience: remove the first UX Director line',
        'pattern' => '/\\n?<li class="description">Led the Concept &amp; Design department of around 50 people: some 20 interaction designers[^<]*<\\/li>/u',
        'new'     => '',
        'marker'  => '#never-present#',
    ],
    [
        // Paul led the fresk team until January 2026; and how he led it (Nov 2026).
        // Must run before the line below, which checks for the new text.
        'what'    => 'work experience line: fresk, how I led the team',
        'old'     => 'and led the design team of four until 2025.',
        'new'     => 'and ' . FRESK_LEAD_LINE,
    ],
    [
        'what'    => 'work experience line: Founder, Head of Design Strategy',
        'pattern' => '/(<li class="highlight">Founder,\ Head\ of\ Design\ Strategy<\\/li>\\s*<li class="location">fresk\.digital,\ The\ Netherlands<\\/li>)/u',
        'new'     => '$1' . "\n" . '<li class="description">Co-founded the digital agency. I set the concept, roadmap and design direction for clients, and ' . FRESK_LEAD_LINE . '</li>',
        'marker'  => FRESK_LEAD_LINE,
    ],
    [
        'what'    => 'work experience line: fresk is a digital agency, not a studio',
        'old'     => 'Co-founded the studio.',
        'new'     => 'Co-founded the digital agency.',
    ],
    [
        'what'    => 'work experience line: fresk, restore the lost "and"',
        'old'     => 'for clients, led the design team until January 2026.',
        'new'     => 'for clients, and led the design team until January 2026.',
    ],
    [
        'what'    => 'work experience line: Design Lead',
        'pattern' => '/(<li class="highlight">Design\ Lead<\\/li>\\s*<li class="location">INFO,\ Amsterdam,\ The\ Netherlands<\\/li>)/u',
        'new'     => '$1' . "\n" . '<li class="description">Responsible for the design direction and quality of projects such as NOC*NSF and Lloyds Bank.</li>',
        'marker'  => 'Responsible for the design direction and quality of projects such as NOC*NSF and Lloyds Bank.',
    ],
    [
        'what'    => 'work experience line: UX Director',
        'pattern' => '/(<li class="highlight">UX\ Director<\\/li>\\s*<li class="location">Mirabeau\ (?:–|&ndash;)\ a\ Cognizant\ Digital\ Business,\ Amsterdam,\ The\ Netherlands<\\/li>)/u',
        'new'     => '$1' . "\n" . '<li class="description">Led the Concept &amp; Design department of around 50 people: interaction designers, visual designers, front-end developers and creative consultants.</li>',
        'marker'  => 'Led the Concept &amp; Design department of around 50 people: interaction designers, visual designers, front-end developers and creative consultants.',
    ],
    [
        'what'    => 'work experience line: Principal Interaction Designer | Design Lead',
        'pattern' => '/(<li class="highlight">Principal\ Interaction\ Designer\ \|\ Design\ Lead<\\/li>\\s*<li class="location">Mirabeau,\ Amsterdam,\ The\ Netherlands<\\/li>)/u',
        'new'     => '$1' . "\n" . '<li class="description">Led the group of some 20 interaction designers, alongside hands-on work for clients such as Etihad and FrieslandCampina.</li>',
        'marker'  => 'Led the group of some 20 interaction designers, alongside hands-on work for clients such as Etihad and FrieslandCampina.',
    ],
    [
        'what'    => 'work experience line: Principal Interaction Designer',
        'pattern' => '/(<li class="highlight">Principal\ Interaction\ Designer<\\/li>\\s*<li class="location">Mirabeau,\ Amsterdam,\ The\ Netherlands<\\/li>)/u',
        'new'     => '$1' . "\n" . '<li class="description">Strategy, concept and interaction design for international clients such as China Southern Airlines and PARKnSHOP.</li>',
        'marker'  => 'Strategy, concept and interaction design for international clients such as China Southern Airlines and PARKnSHOP.',
    ],
    [
        'what'    => 'work experience line: Senior Interaction Designer',
        'pattern' => '/(<li class="highlight">Senior\ Interaction\ Designer<\\/li>\\s*<li class="location">Mirabeau,\ Rotterdam,\ The\ Netherlands<\\/li>)/u',
        'new'     => '$1' . "\n" . '<li class="description">Interaction design for e-commerce and large platforms such as Maxeda, KPN and Bibliotheek.nl.</li>',
        'marker'  => 'Interaction design for e-commerce and large platforms such as Maxeda, KPN and Bibliotheek.nl.',
    ],
    [
        'what'    => 'work experience line: Senior Concept &amp; Interaction Designer',
        'pattern' => '/(<li class="highlight">Senior\ Concept\ \&amp;\ Interaction\ Designer<\\/li>\\s*<li class="location">Connect\ Holland,\ Rotterdam,\ The\ Netherlands<\\/li>)/u',
        'new'     => '$1' . "\n" . '<li class="description">Concepts and interaction design for Randstad and the Rotterdam Eye Hospital.</li>',
        'marker'  => 'Concepts and interaction design for Randstad and the Rotterdam Eye Hospital.',
    ],
    [
        'what'    => 'work experience line: Interaction Designer',
        'pattern' => '/(<li class="highlight">Interaction\ Designer<\\/li>\\s*<li class="location">Puurpxl,\ Utrecht,\ The\ Netherlands<\\/li>)/u',
        'new'     => '$1' . "\n" . '<li class="description">Design for the websites of the Consumentenbond and Televizier.</li>',
        'marker'  => 'Design for the websites of the Consumentenbond and Televizier.',
    ],
];

// --- Experience records: [title, old description start => new description] --------
$experienceChanges = [
    [
        // Undo a doubled result (Nov 2026), from before the done-markers.
        'title'   => 'Senses',
        'cleanup' => true,
        'old'     => 'including some project management. In its first year, Senses grew to over €5 million in revenue and won the travel industry’s Start-Up of the Year Award in 2026. Prijsvrij’s idea',
        'new'     => 'including some project management. Prijsvrij’s idea',
    ],
    [
        // Undo a doubled result (Nov 2026), from before the done-markers.
        'title'   => 'Hertek Safety',
        'cleanup' => true,
        'old'     => 'strategically guide. Shadowing a single inspection round showed what the workaround cost: technicians walked the building three or four times instead of once, and wrote down every measurement by hand to enter it again later. The portal guides them through what to measure and records it as they go, so the administration is taken off their hands, a job takes fewer rounds and a technician can help more customers a day. Hertek’s tools',
        'new'     => 'strategically guide. Hertek’s tools',
    ],
    [
        // Undo a doubled result (Nov 2026), from before the done-markers.
        'title'   => 'Rocsys',
        'cleanup' => true,
        'old'     => 'discussed at C-level. The fleet tool replaced a spreadsheet-like first version that didn’t present the information clearly, and became part of the Rocsys Portal, launched in April 2025 within the first integrated platform for hands-free charging of autonomous vehicles, with partners such as APM Terminals. For customers',
        'new'     => 'discussed at C-level. For customers',
    ],
    [
        // Key choice with leadership (as told by Paul, Nov 2026).
        'title'  => 'FrieslandCampina',
        'marker' => 'print costs were rising',
        'old'    => 'extending functionality the target group already knew.',
        'new'    => 'extending functionality the target group already knew. The cooperative was moving its member dairy farmers from paper administration and mailings to digital: print costs were rising, fewer farmers read them, and increasingly complex regulation no longer fitted the old form. The key choice we made with FrieslandCampina was which channel to use for which message, how often and with which purpose, so communication became clearer and more personal while costs went down.',
    ],
    [
        // Key insight (as told by Paul, Nov 2026).
        'title'  => 'KPN',
        'marker' => 'springboard for additional services',
        'old'    => 'As a device-independent hub,',
        'new'    => 'The insight: KPN’s connection is the key to every digital service, Netflix and HBO included, which makes KPN the springboard for additional services rather than one more provider. So it became a device-independent hub,',
    ],
    [
        // Key choice with leadership (as told by Paul, Nov 2026).
        'title'  => 'Senses',
        'marker' => 'standard package, extended with modules',
        'old'    => 'In its first year, Senses',
        'new'    => 'Prijsvrij’s idea was that the higher segment wants tailor-made travel that can still be built on a standard package, extended with modules. With them we explored where technology and the existing systems set the limits, and how to meet specific wishes within them. In its first year, Senses',
    ],
    [
        // The first wording ("jungle") was too blunt towards Hertek; take it out on staging.
        'title'   => 'Hertek Safety',
        'cleanup' => true,
        'old'     => 'Hertek ran on a jungle of applications with overlapping functions: a maintenance technician worked across several of them, and the information never connected, which held back growth. Bundling them into one portal cut the cost of maintaining each application separately and became a springboard for combining data. ',
        'new'     => '',
    ],
    [
        // Key choice with leadership (as told by Paul, Nov 2026).
        'title'  => 'Hertek Safety',
        'marker' => 'grown over the years into a set of separate applications',
        'old'    => 'Shadowing a single inspection round',
        'new'    => 'Hertek’s tools had grown over the years into a set of separate applications with overlapping functions: a maintenance technician worked across several of them, and the information never connected, which held back growth. Bundling them into one portal cut the cost of maintaining each application separately and became a springboard for combining data. Shadowing a single inspection round',
    ],
    [
        // Key choice with leadership (as told by Paul, Nov 2026).
        'title'  => 'Rocsys',
        'marker' => 'make that reliability tangible',
        'old'    => 'The fleet tool replaced',
        'new'    => 'For customers only one thing counts: the robot does its job, reliably. The strategic question was how to make that reliability tangible: showing how Rocsys meets the contract, and tracing deviations it didn’t cause, for instance when an operator worked outside the terms of the contract. The fleet tool replaced',
    ],
    [
        // Public result (Ravlling LinkedIn, PhocusWire, Columbus Travel Awards; Nov 2026).
        'title' => 'D-Connect',
        'old'   => 'bypassing the existing tool for a German application.',
        'new'   => 'bypassing the existing tool for alternative tools.',
    ],
    [
        'title'  => 'Rocsys',
        'marker' => 'Rocsys Portal',
        'old'    => 'which key choices to make, discussed at C-level.',
        'new'   => 'which key choices to make, discussed at C-level. The fleet tool replaced a spreadsheet-like first version that didn’t present the information clearly, and became part of the Rocsys Portal, launched in April 2025 within the first integrated platform for hands-free charging of autonomous vehicles, with partners such as APM Terminals.',
    ],
    [
        'title'  => 'Ravlling',
        'marker' => 'Ravlling went live',
        'old'    => 'setting up and using graph databases.',
        'new'   => 'setting up and using graph databases. Ravlling went live at the end of 2022, starting with the Nordic countries, was featured on PhocusWire’s Startup Stage and was nominated for the Columbus Travel Awards 2024 as sustainable start-up of the year.',
    ],
    [
        'title' => 'D-Connect',
        'old'   => 'advisors stopped falling back on the German tool:',
        'new'   => 'advisors stopped falling back on them:',
    ],
    [
        // The result (as told by Paul, Nov 2026): live in all stores, the workaround gone.
        'title'  => 'D-Connect',
        'marker' => 'D-Connect went live in all D-reizen stores',
        'old'    => 'created the service blueprints and designed the platform.',
        'new'   => 'created the service blueprints and designed the platform. D-Connect went live in all D-reizen stores, and advisors stopped falling back on alternative tools: it is faster and more intuitive, with search filters that follow their conversations with travellers and layers of information that match an advisor’s expertise.',
    ],
    [
        // Undo a double result sentence (Nov 2026): keep one copy.
        'title'   => 'D-Connect',
        'cleanup' => true,
        'old'     => 'advisors stopped falling back on them: it is faster and more intuitive, with search filters that follow their conversations with travellers and layers of information that match an advisor’s expertise. D-Connect went live in all D-reizen stores, and advisors stopped falling back on alternative tools:',
        'new'     => 'advisors stopped falling back on alternative tools:',
    ],
    [
        // The opening already says "alternative tools"; avoid saying it twice.
        'title'   => 'D-Connect',
        'cleanup' => true,
        'old'     => 'advisors stopped falling back on alternative tools:',
        'new'     => 'advisors stopped falling back on them:',
    ],
    [
        // The result in public figures (Quote, June 2026; Reisgala, February 2026).
        'title'  => 'Senses',
        'marker' => 'Senses grew to over',
        'old'    => 'including some project management.',
        'new'   => 'including some project management. In its first year, Senses grew to over €5 million in revenue and won the travel industry’s Start-Up of the Year Award in 2026.',
    ],
    [
        // The measured result (Nov 2026): rounds through the building and the administration.
        'title'  => 'Hertek Safety',
        'marker' => 'Shadowing a single inspection round',
        'old'    => 'which I partly design and strategically guide.',
        'new'   => 'which I partly design and strategically guide. Shadowing a single inspection round showed what the workaround cost: technicians walked the building three or four times instead of once, and wrote down every measurement by hand to enter it again later. The portal guides them through what to measure and records it as they go, so the administration is taken off their hands, a job takes fewer rounds and a technician can help more customers a day.',
    ],
    [
        'title' => 'Consumentenbond',
        'old'   => 'Lead designer for the website of the Dutch consumer association',
        'new'   => 'Design for the website of the Dutch consumer association',
    ],
    [
        'title'  => 'Etihad Airways',
        'marker' => 'dozens of internal stakeholders',
        'old'    => 'It should hold up to interpretation both internally, and externally by agencies and vendors.',
        'new'   => 'It should hold up to interpretation both internally, and externally by agencies and vendors. The work involved dozens of internal stakeholders and external vendors, across 4 time zones and 3 design studios in Amsterdam, Abu Dhabi and Kolkata.',
    ],
    [
        // Working with data (as told by Paul, Nov 2026).
        'title'  => 'FrieslandCampina',
        'marker' => 'extensive KPI model',
        'old'    => 'while costs went down.',
        'new'    => 'while costs went down. We set up an extensive KPI model, linked to analytics, to measure whether farmers were satisfied with the platform and used it, and whether that matched the strategic goals.',
    ],
    [
        // Working with data (as told by Paul, Nov 2026).
        'title'  => 'Ravlling',
        'marker' => 'started from a hypothesis',
        'old'    => 'how it shows in the numbers.',
        'new'    => 'how it shows in the numbers. Each feature started from a hypothesis and an expected effect in a KPI model, so after release we could measure whether it delivered: some assumptions didn’t hold, and some of the ways people used it surprised us.',
    ],
    [
        // Working with data (as told by Paul, Nov 2026).
        'title'  => 'D-Connect',
        'marker' => 'leading measure',
        'old'    => 'that match an advisor’s expertise.',
        'new'    => 'that match an advisor’s expertise. The number of trips booked through D-Connect was the leading measure. We worked from hypotheses and tracked which functions advisors used most, so a popular filter moved higher up in the interface.',
    ],
];

// --- Apply ----------------------------------------------------------------------
$page = q1("SELECT id, body_html FROM pages WHERE slug = 'resume'");
if (!$page) { fwrite(STDERR, "Resume page not found; page changes skipped.\n"); }
else {
    // The CMS saves line breaks as CRLF; compare on LF and keep the page's style.
    $crlf = strpos($page['body_html'], "\r\n") !== false;
    $body = str_replace("\r\n", "\n", $page['body_html']);
    $changed = 0;
    foreach (array_filter($pageChanges, $applies) as $c) {
        $done = strpos($body, $c['marker'] ?? $c['new']) !== false;
        if ($done)                                  { echo "already done: {$c['what']}\n"; continue; }
        if (isset($c['pattern'])) {
            if (!preg_match($c['pattern'], $body)) { echo "old text not found, skipped: {$c['what']}\n"; continue; }
            $body = preg_replace($c['pattern'], $c['new'], $body, 1);
            echo "updated: {$c['what']}\n";
            $changed++;
            continue;
        }
        if (strpos($body, $c['old']) === false)     { echo "old text not found, skipped: {$c['what']}\n"; continue; }
        $body = preg_replace('/' . preg_quote($c['old'], '/') . '/', addcslashes($c['new'], '\\$'), $body, 1);
        echo "updated: {$c['what']}\n";
        $changed++;
    }
    if ($changed) {
        exec_sql('UPDATE pages SET body_html = ? WHERE id = ?', [$crlf ? str_replace("\n", "\r\n", $body) : $body, $page['id']]);
        echo "Resume page saved ($changed changes).\n";
    }
}

ensure_experiences_table();
foreach (array_filter($experienceChanges, $applies) as $c) {
    $row = q1('SELECT id, description FROM experiences WHERE title = ? ORDER BY id LIMIT 1', [$c['title']]);
    if (!$row)                                         { echo "experience not found, skipped: {$c['title']}\n"; continue; }
    $desc = str_replace("\r\n", "\n", $row['description']);
    $done = $c['marker'] ?? (empty($c['cleanup']) ? $c['new'] : null);
    if ($done !== null && strpos($desc, $done) !== false) { echo "already done: {$c['title']}\n"; continue; }
    if (strpos($desc, $c['old']) === false)            { echo "old text not found, skipped: {$c['title']}\n"; continue; }
    exec_sql('UPDATE experiences SET description = ? WHERE id = ?', [str_replace($c['old'], $c['new'], $desc), $row['id']]);
    echo "updated experience: {$c['title']}\n";
}

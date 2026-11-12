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
        'env'     => 'staging',
        'pattern' => '#<p><b>(Design strategist with .*?clear choices\.)</b>\s*#s',
        'new'     => "<p class=\"lead\">\$1</p>\n<p>",
        'marker'  => '<p class="lead">',
    ],
    [
        'what'    => 'side activities: Dordoloog',
        'env'     => 'staging',
        'pattern' => '#(<h2>Side activities</h2>\s*<ul class="resume">\s*)#',
        'new'     => "\$1<ul class=\"item\">\n<li class=\"time-period\">2024 &ndash; present</li>\n<li class=\"highlight\">Dordoloog</li>\n<li class=\"location\">Local historian and tour guide on the Island of Dordrecht</li>\n</ul>\n",
        'marker'  => '>Dordoloog<',
    ],
    [
        'what'    => 'side activities: Dordoloog wording (staging had the first version)',
        'env'     => 'staging',
        'old'     => 'Local historian and guide, researching where things come from on the Island of Dordrecht and sharing it on guided tours',
        'new'     => 'Local historian and tour guide on the Island of Dordrecht',
    ],
    [
        'what'    => 'new section: Tools I like to work with (after Skills)',
        'env'     => 'staging',
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
        'env'     => 'staging',
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
        'env'     => 'staging',
        'old'     => '<li class="time-period">2018</li>
<li class="highlight">Drum teacher</li>',
        'new'     => '<li class="time-period">2018 &ndash; present</li>
<li class="highlight">Drum teacher</li>',
    ],
];

// --- Experience records: [title, old description start => new description] --------
$experienceChanges = [
    [
        'title' => 'Etihad Airways',
        'old'   => 'It should hold up to interpretation both internally, and externally by agencies and vendors.',
        'new'   => 'It should hold up to interpretation both internally, and externally by agencies and vendors. The work involved dozens of internal stakeholders and external vendors, across 4 time zones and 3 design studios in Amsterdam, Abu Dhabi and Kolkata.',
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
    if (strpos($desc, $c['new']) !== false)            { echo "already done: {$c['title']}\n"; continue; }
    if (strpos($desc, $c['old']) === false)            { echo "old text not found, skipped: {$c['title']}\n"; continue; }
    exec_sql('UPDATE experiences SET description = ? WHERE id = ?', [str_replace($c['old'], $c['new'], $desc), $row['id']]);
    echo "updated experience: {$c['title']}\n";
}

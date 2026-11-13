<?php
/**
 * Move the hand-written talks on the Talks page into talk records
 * (see lib/talks.php). Runs from deploy.sh and can be run by hand:
 * php bin/import_talks.php. Does nothing once the Talks page has its
 * data-talks="all" placeholder.
 *
 * IMPORT_ON says where it runs: 'staging' while the change is being
 * reviewed (staging gets a fresh copy of live every night, so it imports
 * again on its next deploy), 'all' once approved for live.
 */
require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/helpers.php';

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

const IMPORT_ON = 'all';

$env = (config()['site']['env'] ?? 'live') === 'staging' ? 'staging' : 'live';
if (IMPORT_ON !== 'all' && IMPORT_ON !== $env) { echo "import_talks: not on $env yet\n"; exit(0); }
ensure_talks_table();

$n = import_talks_from_page();
echo $n ? "import_talks: $n talks imported\n" : "import_talks: nothing to import\n";

/*
 * Text updates for talk records that cannot be made in the admin right now
 * (no login). Each finds a talk by its current or new title and sets the
 * given fields; it does nothing when they already match. Updates marked
 * 'env' => 'staging' are a preview until approved.
 */
$talkUpdates = [
    [
        'match' => ['Being different'],
        'set'   => [
            'takeaway'    => 'Branding is about accentuating differences.',
            'description' => "The focus of design is shifting from craftsmanship to copying best practices, and that is a problem. Physical products show how much room there is to be different, while many websites follow the same trends and start to look alike. Dieter Rams made Braun, but there is only one Braun: we have to be somebody else, and find our own place between minimal and expressive, conservative and innovative. Craft is hard and takes both breadth and depth, down to an 8-point grid. Craft gives you good design, creativity on top makes it standout design, and perseverance gets you there. So work together, argue your choices, test, think outside the box and stay in shape.",
        ],
    ],
    [
        'match' => ['Digital transformation of Etihad'],
        'set'   => [
            'takeaway'    => 'A design language starts with alignment.',
            'description' => "Etihad Airways set out to become the number one digital airline. The creative challenge: a visually striking design language that translates the \"Choose Well\" proposition to UX and holds up in the hands of internal teams, agencies and vendors. An audit showed that 90% of the touchpoints were inconsistent. Starting from strategy, personas and an experience map linking business goals, user needs and channels, we defined design principles for how the experience looks, communicates, behaves and is built, and a design system that was first used for baggage drop and kiosks at the new Midfield terminal.",
        ],
    ],
    [
        // The Sitecore webinar; "Dynamic Design" is also the title of the
        // UX Cocktail Hour talk, so this one is matched on its date too.
        'match' => ['Dynamic Design'],
        'date'  => '2015-01-20',
        'env'   => 'staging',
        'set'   => [
            'takeaway'    => 'Design systems, not pages.',
            'description' => "Print is far more dynamic than the web: think of how magazine and newspaper covers change from issue to issue. Many websites, by contrast, are static and only adapt to the screen size of the device. Customers expect online experiences that respond to real user variables, such as location, time, and how often and in which way they use a service. This webinar for Sitecore Wintercamp introduced dynamic design: the design of a context-dependent interface, where user needs lead instead of devices. Context ranges from what is always available, like location, time, device and movement, to what builds up over time: browsing behaviour, earlier purchases and a customer profile. Dynamic design takes four steps: set smart goals, add context to the customer journey, design systems instead of pages, and test, learn and improve. Two cases showed how it works: personas and customer journeys for the Van Gogh Museum, from a schoolboy who finds museums boring to the art lover, and a community site whose content blocks grow with reactions and views, while its members move up from unknown visitor to registered user and expert. And a design is never finished: it is impermanent, incomplete and imperfect.",
            'links'       => "White paper: From progressive enhancement to dynamic design | https://www.mirabeau.nl/en/news/article/2016/ecommerce-optimisation-for-the-ever-changing-web#verdiepend\nArticle (Dutch): Content first, from responsive design to a dynamic website | https://www.frankwatching.com/archive/2013/08/16/content-first-van-responsive-design-naar-dynamische-website/",
        ],
    ],
    [
        'match' => ['Design Thinking by Doing'],
        'env'   => 'staging',
        'set'   => [
            'image'        => '/assets/talks/20170713_ixdc_beijing.jpg',
            'image_width'  => '1086',
            'image_height' => '724',
            'image_alt'    => 'Paul Versteeg in the workshop room at IXDC 2017 in Beijing, next to a whiteboard',
        ],
    ],
    [
        'match' => ['Craftmanship'],
        'set'   => ['title' => 'Craftsmanship'],
    ],
    [
        // Intro from the original invitation e-mail worked into the story.
        'match' => ['Lost in Translation', 'Lost in translation'],
        'env'   => 'staging',
        'set'   => ['description' => 'Together with China Southern Airlines, Mirabeau worked on a new app around service, sales and loyalty: a first project for an Asian client. Our user-centred approach was the starting point, but it was not obvious that our design methods would work in China. The goal was simply to build a really good app, yet on the way there the cultural difference turned out to be a real challenge. This talk on digital design and Chinese culture shares what we ran into and what we learned. Culture shows up everywhere, from dragons and zodiac signs to colours and traditions. Everything is mobile: responsive matters less, apps like WeChat are everything, QR codes are hot and promotions are big. The local design and development scene is mature, so the bar is high. The hotel became our office, and we brought research, personas and scrum into the process. What worked best: show instead of explain, with demos and local examples; design with real content written by a native speaker; test more than once to convince stakeholders; and make sure you talk to the right person. And have fun while you learn.'],
    ],
    [
        'match' => ['Lost in translation'],
        'set'   => ['title' => 'Lost in Translation'],
    ],
    [
        // From the organisers' e-mails and briefing (Dutch Digital Agencies, Oct 2017).
        'match' => ['Craftsmanship', 'Craftmanship'],
        'env'   => 'staging',
        'set'   => [
            'venue'       => 'MU, Eindhoven',
            'description' => "A breakfast session by Dutch Digital Agencies to meet young talent, in the middle of the Materialising the Internet exhibition at MU during Dutch Design Week. Over an Asian breakfast, more than fifty students from all over the country, mostly third- and fourth-year CMD students looking for an internship or a first job, sat at ten tables, and every fifteen minutes the agencies moved on to the next one. No sales pitch, but one theme per agency: mine was craftsmanship, and why in this field of work you have to specialise. Right after, I joined the Adobe Creative Jam at SintLucas as coach and jury member, where twelve student teams designed a web or mobile service that helps people adapt to climate change.",
        ],
    ],
];

foreach ($talkUpdates as $u) {
    if (($u['env'] ?? 'all') !== 'all' && $u['env'] !== $env) continue;
    $row = null;
    foreach ($u['match'] as $title) {
        $row = isset($u['date'])
            ? q1('SELECT * FROM talks WHERE title = ? AND talk_date = ? ORDER BY id LIMIT 1', [$title, $u['date']])
            : q1('SELECT * FROM talks WHERE title = ? ORDER BY id LIMIT 1', [$title]);
        if ($row) break;
    }
    if (!$row) { echo "talk not found, skipped: {$u['match'][0]}\n"; continue; }
    $diff = array_filter($u['set'], fn ($v, $k) => (string) $row[$k] !== $v, ARRAY_FILTER_USE_BOTH);
    if (!$diff) { echo "talk already up to date: {$row['title']}\n"; continue; }
    $sets = implode(', ', array_map(fn ($k) => "$k = ?", array_keys($diff)));
    exec_sql("UPDATE talks SET $sets WHERE id = ?", array_merge(array_values($diff), [$row['id']]));
    echo "talk updated: {$row['title']}\n";
}

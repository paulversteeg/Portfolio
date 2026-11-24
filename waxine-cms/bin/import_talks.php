<?php
/**
 * Move the hand-written talks on the Talks page into talk records
 * (see lib/talks.php). Runs from deploy.sh and can be run by hand:
 * php bin/import_talks.php. Does nothing once the Talks page has its
 * data-talks="all" placeholder.
 *
 * IMPORT_TALKS_ON says where it runs: 'staging' while the change is being
 * reviewed (staging gets a fresh copy of live every night, so it imports
 * again on its next deploy), 'all' once approved for live.
 */
require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/helpers.php';

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

const IMPORT_TALKS_ON = 'all';

$env = (config()['site']['env'] ?? 'live') === 'staging' ? 'staging' : 'live';
if (IMPORT_TALKS_ON !== 'all' && IMPORT_TALKS_ON !== $env) { echo "import_talks: not on $env yet\n"; return; }
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
            'description' => "The focus of design is shifting from craftsmanship to copying best practices, and that is a problem. Physical products show how much room there is to be different, while many websites start to look alike. Dieter Rams made Braun, but there is only one Braun: find your own place between minimal and expressive, conservative and innovative. Craft is hard and takes both breadth and depth, down to an 8-point grid. Craft gives you good design, creativity makes it stand out, and perseverance gets you there. So work together, argue your choices, test, think outside the box and stay in shape.",
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
        'set'   => [
            'takeaway'    => 'Design systems, not pages.',
            'description' => "Print is far more dynamic than the web: magazine covers change with every issue, while many websites only adapt to the screen size. Customers expect experiences that respond to real user variables, like location, time and how often they use a service. This webinar for Sitecore Wintercamp introduced dynamic design: the design of a context-dependent interface, where user needs lead instead of devices. It takes four steps: set smart goals, add context to the customer journey, design systems instead of pages, and test, learn and improve. Cases for the Van Gogh Museum and a community site showed how it works. And a design is never finished: it is impermanent, incomplete and imperfect.",
            'links'       => "White paper: From progressive enhancement to dynamic design | https://www.mirabeau.nl/en/news/article/2016/ecommerce-optimisation-for-the-ever-changing-web#verdiepend\nArticle (Dutch): Content first, from responsive design to a dynamic website | https://www.frankwatching.com/archive/2013/08/16/content-first-van-responsive-design-naar-dynamische-website/",
        ],
    ],
    [
        'match' => ['Design Thinking by Doing'],
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
        'set'   => ['description' => "Together with China Southern Airlines, Mirabeau worked on a new app around service, sales and loyalty: a first project for an Asian client. Our user-centred approach was the starting point, but it was not obvious that it would work in China, and on the way to a really good app the cultural difference turned out to be a real challenge. Culture shows up everywhere, from dragons and zodiac signs to colours. Everything is mobile, WeChat is everything, and the local design scene sets a high bar. What worked best: show instead of explain, design with real content by a native speaker, test more than once to convince stakeholders, and talk to the right person. And have fun while you learn."],
    ],
    [
        'match' => ['Lost in translation'],
        'set'   => ['title' => 'Lost in Translation'],
    ],
    [
        // From the organisers' e-mails and briefing (Dutch Digital Agencies, Oct 2017).
        'match' => ['Craftsmanship', 'Craftmanship'],
        'set'   => [
            'venue'       => 'MU, Eindhoven',
            'description' => "A breakfast by Dutch Digital Agencies to meet young talent, at MU during Dutch Design Week. More than fifty students, mostly looking for an internship or a first job, sat at ten tables, and every fifteen minutes the agencies moved on. My theme was craftsmanship, and why in this field you have to specialise. Right after, at the Adobe Creative Jam at SintLucas, I gave a short talk on stage for twelve student teams and joined them as coach and jury member. The talk had three parts. Change: accept that a design is never finished. Challenge: think outside the box that brand, trends and rationale build around you. Hustle: build breadth and depth, work together, test and aim for the wow.",
        ],
    ],
    [
        // Taken offline until Paul remembers what this session was about (Nov 2026).
        'match' => ['Smart Home Smart'],
        'set'   => ['published' => '0'],
    ],
    [
        // Taken offline while Paul fills in the story; it is still too short (Nov 2026).
        'match' => ['The Power of 10', 'Power of 10', 'The Power of Ten', 'Power of Ten'],
        'set'   => ['published' => '0'],
    ],
    [
        'match' => ['Designing for Chaos: UX for dirty hands, ladders, and blaring alarms'],
        'set'   => [
            'image'        => '/assets/talks/20261106_enterprise_ux_conference.jpg',
            'image_width'  => '1086',
            'image_height' => '724',
            'image_alt'    => 'Paul Versteeg on stage at Enterprise UX Conference 2026 in front of a full room, presenting the slide ‘Shadowing: let the work flow’',
        ],
    ],
    [
        // Shorter text (Nov 2026): about 100 to 120 words per talk.
        'match' => ["The role of digital design in empowering professionals in mobility operations"],
        'set'   => ['description' => "Like a concert, a digital experience depends on what happens behind the scenes. When backstage processes fall short, engagement drops, errors creep in and strategy fails. Building on fresk.digital’s shared mobility research, this talk follows a rider from finding a shared bike or moped to ending the ride, and then shadows the people who keep the fleet available. A service blueprint brings both views together, from behaviour and touchpoints to backstage processes and data, and shows what the interface and architecture need to support. A second case puts it into practice: for Rocsys, maker of charging robots for unmanned yard trucks, it turned a spreadsheet into a robust fleet tool for program managers and service engineers."],
    ],
    [
        // Shorter text (Nov 2026): about 100 to 120 words per talk.
        'match' => ["A wall becomes a bridge"],
        'set'   => ['description' => "A drummer can play by copying what is around: the style, the trends, what the record company wants. Meaningful work asks you to zoom out first. Named after an album by drummer Kendrick Scott, this talk shows how user research can turn a barrier into a bridge. A research framework looks at a service from five perspectives, from customer journey and touchpoints to backstage, support processes and data, before, during and after use. The case is fresk.digital’s shared mobility research: we rode eleven bike services in the Netherlands and Belgium, from OV-fiets and Lime to Blue-bike. For Blue-bike it led to concrete suggestions, such as signing up in the app, seeing the battery level before choosing a bike, and only showing hubs with bikes available."],
    ],
    [
        // Shorter text (Nov 2026): about 100 to 120 words per talk.
        'match' => ["The Pitches Showcase"],
        'set'   => ['description' => "To win a project, you have to convince the client that you are the right team for the job, with the right quality and an approach that matches their way of thinking. This masterclass took students backstage at a design agency, through real pitches that were won or lost. It starts before the pitch: does the client match your weight class, and what is really being asked? Pitches are often lost on the same pitfalls: missing the real question, not understanding the business, and showing what we like instead of what the client needs. A good pitch is a fight in six rounds: set the stage, make a promise that pinpoints the why, show the plan, be clear on the price, give proof, and make them fall in love with a first design exploration."],
    ],
    [
        // Shorter text (Nov 2026): about 100 to 120 words per talk.
        'match' => ["New Skills"],
        'set'   => ['description' => "Pixel-perfect design is an illusion, and good tools alone do not make a good designer. This barcamp session presented the skills an effective designer needs as five superpowers. Ultra-mega-vision: understand the context, from the user and their goals to everything you can measure, like time, location or weather. Atomic brain-power: think from the business, from vision and strategy to KPIs. Amorphic flexibility: accept that a design is impermanent, incomplete and imperfect. Work like a scientist: research, run workshops and test. And break the boundaries: dare to be different, because branding is about accentuating differences."],
    ],
    [
        // Shorter text (Nov 2026): about 100 to 120 words per talk.
        'match' => ["Design Thinking by Doing"],
        'set'   => ['description' => "A three-hour workshop at IXDC, the biggest UX conference in Asia, given together with Lexi Lin of Mirabeau. Large companies tend to jump from a problem straight to a solution. Design thinking first takes time to understand the problem, starting with empathy for someone’s context. In the Wallet Project, participants first drew their own “ideal” wallet. Then they paired up and designed one for their partner, through a full design cycle: interview and empathize, define a point of view, sketch radical ideas, build a prototype by hand and test it. The pitches at the end showed how much observing, prototyping and adjusting to someone’s context changes the result."],
    ],
    [
        // Shorter text (Nov 2026): about 100 to 120 words per talk.
        'match' => ["Unleash the Artificial Smartness"],
        'set'   => ['description' => "The trend is to take a digital service and add AI, until everything is connected and “intelligent” and people end up overwhelmed. When a Tesla drives into a Tesla sign, that is the designer’s mistake, not the driver’s. Intelligence aims at a machine that thinks for itself; smartness is focused and specific, and tells people more about the world so they can act better themselves. Starting from Don Norman’s questions of how we do, feel and know, this talk shows how layers of context add up to artificial smartness: not a self-driving car, but a car that works with its driver like a horse with its jockey."],
    ],
    [
        // Shorter text (Nov 2026): about 100 to 120 words per talk.
        'match' => ["Defining Interaction"],
        'set'   => ['description' => "Responsive design, motion and one-page sites are hard to capture on paper, and teams increasingly work agile, side by side with the client. Prototypes tell a good story but take time and go out of date; detailed documents record every agreement but are hardly read. This talk shared a different approach: stay high-level and define the rest by trying. Sketch the concept on big A3 sheets and present the key parts: business goals, user scenarios, flows, a content index and wireframes. Then add detail in iterations, with visual design and front-end development taking the lead. Like a good cocktail: the interaction designer brings the ingredients and leaves the taste to those who master it."],
    ],
    [
        // Shorter text (Nov 2026). The UX Cocktail Hour talk; the webinar has the same title.
        'match' => ['Dynamic Design'],
        'date'  => '2014-11-25',
        'set'   => ['description' => "Dynamic design is the design of a context-dependent interface: user needs lead, not devices. Almost anything we can measure can be context, from time, location and weather to earlier use or the light around the screen. That gives designers a lot of power, but data only adds value when it makes the experience personal and relevant. And since nothing we build lasts much longer than two years, a design is never finished: leave room to grow, like a campus that lets people walk their own paths before laying the pavement. In the design thinking workshop that followed, groups picked the most important user goal, mapped the journey and looked for moments where context could enrich the experience."],
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

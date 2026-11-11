<?php
/**
 * Apply the November 2026 talk updates to the Talks page.
 *
 * Runs once from deploy.sh after a live deploy (and can be run by hand:
 * php bin/apply_talk_updates.php). Safe to run again: a talk that already
 * has its new text is left alone, and a talk that cannot be found is
 * skipped with a message. Nothing else on the page is touched.
 *
 * Each update finds one talk block (<div class="talk"> … </div>) by its
 * current <h2> title and replaces the whole block.
 *
 * An update marked 'env' => 'staging' is a preview: it is only applied on
 * staging (and disappears there with the nightly copy of live). Remove the
 * marker once it is approved, and the next live deploy applies it.
 */
require_once dirname(__DIR__) . '/lib/db.php';

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

$updates = [];

$updates[] = ['match' => ['Unleash the Artificial Intelligence', 'Unleash the Artificial Smartness'], 'html' => <<<'HTML'
<div class="talk">
<h2>Unleash the Artificial Smartness</h2>
<span class="subtitle"><img src="/images/20170119_dutch_design_week_bca0f4.jpg" width="1224" height="1224" alt="">Dutch Design Week goes Digital: Where Digital Meets the Real World</span> <span class="subtitle">Klokgebouw Eindhoven &ndash; October 28, 2016</span>
<p>Design artificial smartness, not artificial intelligence.</p>
<p>The trend is to take a digital service and add AI. Everything becomes connected and &ldquo;intelligent&rdquo;, and people end up overwhelmed. When a Tesla drives into a Tesla sign, that is not the driver&rsquo;s mistake but the designer&rsquo;s: the car never learned the sign was there. Intelligence aims at a machine that thinks for itself. Smartness is focused, measurable and specific: it tells people more about the world so they can act better themselves. Starting from Don Norman&rsquo;s questions of how we do, feel and know, this talk shows how layers of context (where you are, what you did, what you chose and who you are) add up to artificial smartness. Not a self-driving car, but a car that gives smart suggestions and works with its driver like a horse with its jockey.</p>
<ul class="bullit">
<li><a href="https://www.emerce.nl/interviews/mirabeau-ai-betere-interaction-design">Interview</a></li>
<li><a href="https://vimeo.com/192338636">Video</a></li>
</ul>
</div>
HTML];

// Matched on the old title only: the Sitecore webinar is also called "Dynamic Design".
$updates[] = ['match' => ['Dynamic Design &amp; Design Thinking'], 'html' => <<<'HTML'
<div class="talk">
<h2>Dynamic Design</h2>
<span class="subtitle"><img src="/images/20141125_ux_cocktail_hour_4207d1.jpg" width="1800" height="1200" alt="Paul Versteeg on stage in front of the slide &lsquo;Relevante context?&rsquo;">UX Cocktail Hour Rotterdam #15</span> <span class="subtitle">Mirabeau Rotterdam &ndash; November 25, 2014</span>
<p>Design for user goals, not business features.</p>
<p>Dynamic design is the design of a context-dependent interface. Devices used to be the starting point; now user needs lead. Almost anything we can measure can be context: time, location, weather, speed, earlier use, whether someone is logged in, even the light around the screen. That gives designers a lot of power, but data only adds value when it makes the experience personal and relevant. And since nothing we build lasts much longer than two years, a design is never finished: leave room to grow and value adaptability over beauty, like a campus that lets people walk their own paths before laying the pavement. The talk showed our dynamic design workflow, from strategy and scope to structure, surface and ongoing optimization. In the design thinking workshop that followed, groups picked the single most important user goal, defined who has that goal, mapped their journey and looked for the moments where context could enrich the experience.</p>
</div>
HTML];

$updates[] = ['match' => ['Defining Interactions', 'Defining Interaction'], 'html' => <<<'HTML'
<div class="talk">
<h2>Defining Interaction</h2>
<span class="subtitle"><img src="/images/20120904_ux_cocktail_hour_ff33f1.jpg" width="1800" height="1012" alt="Paul Versteeg presenting to a room full of people at Mirabeau Amsterdam">UX Cocktail Hour Amsterdam; Hot Summer Night at Mirabeau</span> <span class="subtitle">Mirabeau Amsterdam &ndash; September 4, 2012</span>
<p>An interaction design is a means, not the goal.</p>
<p>New trends like responsive design, motion, parallax scrolling and one-page sites are hard to capture on paper, and teams increasingly work agile, side by side with the client. Prototypes tell a good story but take a lot of time and quickly go out of date; detailed documents record every agreement but are hardly read. This talk shared the first findings of a different approach: stay high-level and define the rest by trying. Start from the requirements, sketch the concept on big A3 sheets and present the key parts to the client: business goals, user scenarios, flows, a content index and wireframes. Then add detail in iterations, together with visual design and front-end development, who take the lead from there. Like a good cocktail: the interaction designer brings the right ingredients (function, content and its hierarchy) and leaves the tools and the taste to the people who master them.</p>
</div>
HTML];

$updates[] = ['match' => ['Lost in translation'], 'html' => <<<'HTML'
<div class="talk">
<h2>Lost in translation</h2>
<span class="subtitle"><img src="/images/20170228_lost_in_translation_e8b2d0.jpg" width="1800" height="1200" alt="Paul Versteeg presenting the slide &lsquo;Learn&rsquo; to a seated audience, seen through a window">Dutch Digital Agencies &ndash; UX Meet-up: The English Breakfast Edition</span> <span class="subtitle">Amsterdam &ndash; February 28, 2017</span>
<p>Show, don&rsquo;t just tell.</p>
<p>Together with China Southern Airlines, Mirabeau worked on a new app around services, sales and loyalty: a first project for an Asian client. A user-centred approach was the starting point, but it was not obvious that it would work in China. This talk shares what we ran into and what we learned. Culture shows up everywhere, from dragons and zodiac signs to colours and traditions. Everything is mobile: responsive matters less, apps like WeChat are everything, QR codes are hot and promotions are big. The local design and development scene is mature, so the bar is high. The hotel became our office, and we brought research, personas and scrum into the process. What worked best: show instead of explain, with demos and local examples; design with real content written by a native speaker; test more than once to convince stakeholders; and make sure you talk to the right person. And have fun while you learn.</p>
</div>
HTML];

$updates[] = ['match' => ['Design thinking by doing', 'Design Thinking by Doing'], 'html' => <<<'HTML'
<div class="talk">
<h2>Design Thinking by Doing</h2>
<span class="subtitle">IXDC 2017 workshop: The Wallet Project</span> <span class="subtitle">Beijing &ndash; July 13, 2017</span>
<p>Understand the problem before you design the solution.</p>
<p>A three-hour workshop at IXDC, the biggest UX conference in Asia, given together with Lexi Lin of Mirabeau. Large companies tend to jump from a problem straight to a solution. Design thinking first takes the time to understand the problem, and that starts with empathy: seeing how someone&rsquo;s behaviour is shaped by their physical, inner, symbolic, relational, situational and cultural context. In the Wallet Project, participants first drew the &ldquo;ideal&rdquo; wallet on their own. Then they paired up and designed a wallet for their partner instead, going through a full design cycle: interview and dig deeper (empathize), capture insights and take a point of view (define), sketch radical ideas and get feedback (ideate), build it with their hands (prototype) and share it for feedback (test). Each team ended by pitching their prototype, which showed how much observing, prototyping and adjusting to someone&rsquo;s context changes the result.</p>
</div>
HTML];

$updates[] = ['match' => ['The role of digital design in empowering professionals in mobility operations'], 'html' => <<<'HTML'
<div class="talk">
<h2>The role of digital design in empowering professionals in mobility operations</h2>
<span class="subtitle"><img src="/images/20241003_emerce_eday_86dfb1.jpg" width="4032" height="2268" alt="">Emerce EDAY Mobility Connect</span> <span class="subtitle">De Kromhouthal Amsterdam &ndash; October 3, 2024</span>
<p>A great experience starts backstage.</p>
<p>Like a concert, a digital experience depends on what happens behind the scenes. When backstage processes are inefficient, engagement drops, solutions become error-prone, teams lose alignment, interfaces get complex and strategy fails. Building on fresk.digital&rsquo;s shared mobility research, this talk follows a shared bike or moped rider from finding a vehicle to ending the ride, and then shadows the people who keep the fleet available. A service blueprint brings both views together, layer by layer: rider and employee behaviour, touchpoints, backstage and support processes, and data. It feeds the interface design, connects technical processes to interfaces, shows what the architecture has to support, and when and how to measure. A second case shows the same approach in practice: for Rocsys, maker of charging robots for unmanned yard trucks, understanding the context, workflow and business goals turned a spreadsheet into a robust, secure and scalable fleet tool for program managers, maintenance and service engineers.</p>
</div>
HTML];

$updates[] = ['match' => ['A wall becomes a bridge'], 'html' => <<<'HTML'
<div class="talk">
<h2>A wall becomes a bridge</h2>
<span class="subtitle"><img src="/images/20221216_dtns_76612a.jpg" width="6000" height="3376" alt="">Design the Next Step 2022: Breaking Barriers</span> <span class="subtitle">Rijksmuseum Amsterdam &ndash; November 17, 2022</span>
<p>Take a step back before moving forward.</p>
<p>A drummer can play by copying what is around: the jazz style, the trends, what the record company wants. Truly meaningful work asks you to zoom out first. Named after an album by drummer Kendrick Scott, this talk shows how user research can turn a barrier into a bridge. A research framework brings human, data and business together and looks at a service from five perspectives (customer journey, digital touchpoints, backstage processes, support processes and data dependencies), before, during and after use. That shows the design areas where value is made. The case is fresk.digital&rsquo;s shared mobility research, for which we rode eleven bike and e-bike services in the Netherlands and Belgium, from OV-fiets and Lime to Blue-bike. Mapped onto the framework, it led to concrete suggestions for Blue-bike: sign up right in the app, pay with PayPal or credit card, see the battery level before choosing a bike, and only show hubs with bikes available.</p>
<ul class="bullit">
<li><a href="https://medium.com/design-ing/breaking-barriers-88358b7219ec">Event report</a></li>
</ul>
</div>
HTML];

$updates[] = ['match' => ['The Pitches Showcase'], 'html' => <<<'HTML'
<div class="talk">
<h2>The Pitches Showcase</h2>
<span class="subtitle">Masterclass University of applied sciences Rotterdam</span> <span class="subtitle">Rotterdam &ndash; December 3, 2020</span>
<p>The art of losing pitches.</p>
<p>Selling UX is a craft of its own. This masterclass takes students backstage at a design agency and walks through real pitches, the lost ones included. It starts before the pitch: does the client match your weight class, and what is really being asked? Pitches are often lost on the same pitfalls: not answering the true question, not understanding the business, showing what we like instead of what the client needs, forgetting how mature the client is, no clear creative direction, and decks without focus, structure or story. A good pitch is a fight in six rounds. Set the stage with the team and its experience. Make a promise that repeats the ask and pinpoints the why. Show the plan, from discovery and personas to roadmap and design principles. Be clear on the price. Give proof with earlier work. And make them fall in love with a first exploration of the design.</p>
</div>
HTML];

$updates[] = ['match' => ['New Skills'], 'insert_before' => 'Being different', 'html' => <<<'HTML'
<div class="talk">
<h2>New Skills</h2>
<span class="subtitle"><img src="/assets/talks/20180609_uxcamp_amsterdam.jpg" width="1086" height="724" alt="Paul Versteeg presenting his session to a room full of people at UXcamp Amsterdam">UXcamp Amsterdam 2018</span> <span class="subtitle">IJsfontein Amsterdam &ndash; June 9, 2018</span>
<p>Dare to be different.</p>
<p>Pixel perfect design is an illusion, and good tools alone do not make a good designer. This barcamp session was about the secret skills an effective designer needs, told as five superpowers. Ultra-mega-vision: understand the context, from who the user is and what they want to do, to the touchpoints before, during and after, and everything that can be measured, like time, location, speed or weather. Atomic brain-power: think from a business perspective, from vision and strategic pillars to KPIs, and an experience map that links user needs, business goals and channels. Amorphic flexibility: accept that a design is impermanent, incomplete and imperfect, and leave room to grow. Work like a scientist: do research, run workshops with clients and test what you create. And break the boundaries: dare to be different, because branding is about accentuating differences.</p>
<ul class="bullit">
<li><a href="https://uxcampams.com/welcome">Event page</a></li>
</ul>
</div>
HTML];

$env = (config()['site']['env'] ?? 'live') === 'staging' ? 'staging' : 'live';
$updates = array_filter($updates, fn ($u) => ($u['env'] ?? 'all') === 'all' || $u['env'] === $env);
echo "Environment: $env\n";

$page = q1("SELECT id, body_html FROM pages WHERE slug = 'talks'");
if (!$page) { fwrite(STDERR, "Talks page not found; nothing done.\n"); exit(0); }
$body = $page['body_html'];
$changed = 0;

foreach ($updates as $u) {
    $new = trim($u['html']);
    if (strpos($body, $new) !== false) { preg_match('#<h2>(.*?)</h2>#', $new, $m); echo "already up to date: {$m[1]}\n"; continue; }
    $found = false;
    foreach ($u['match'] as $title) {
        $h = strpos($body, '<h2>' . $title . '</h2>');
        if ($h === false) continue;
        $start = strrpos(substr($body, 0, $h), '<div class="talk">');
        $end   = strpos($body, '</div>', $h);
        if ($start === false || $end === false) continue;
        $body  = substr($body, 0, $start) . $new . substr($body, $end + 6);
        echo "updated: $title\n";
        $changed++; $found = true;
        break;
    }
    if (!$found && !empty($u['insert_before'])) {
        // A new talk: put it right before the given talk (talks run newest first).
        $h = strpos($body, '<h2>' . $u['insert_before'] . '</h2>');
        $start = $h === false ? false : strrpos(substr($body, 0, $h), '<div class="talk">');
        if ($start !== false) {
            $body = substr($body, 0, $start) . $new . "\n" . substr($body, $start);
            echo "added before {$u['insert_before']}: " . $u['match'][0] . "\n";
            $changed++; $found = true;
        }
    }
    if (!$found) echo "not found, skipped: " . implode(' / ', $u['match']) . "\n";
}

if ($changed) {
    exec_sql('UPDATE pages SET body_html = ? WHERE id = ?', [$body, $page['id']]);
    echo "Talks page saved ($changed talks updated).\n";
} else {
    echo "Talks page already up to date.\n";
}

<?php
/**
 * The Webartrose page (/webartrose, Nov 2026): what the signs of use on the
 * site are, and why. Linked from the Webartrose panel, not in the menu.
 * Runs from deploy.sh and after the nightly staging refresh; can be run by
 * hand: php bin/apply_webartrose_page.php.
 *
 * It creates the page when it does not exist yet. When the page still has
 * the text of an earlier version of this script, unchanged, it gets the
 * current text; once it was edited in the admin, it is left alone.
 *
 * Each sign of use has a pair of examples, new and worn: the worn one has
 * fixed wear values (--wt touched, --wp pressed, --ws seen, --wr which way
 * it leans, and so on), styled by "Webartrose" in screen.css; the scuffs,
 * staples and routes are drawn by assets/wear.js from data attributes.
 *
 * APPLY_ON: 'staging' while being reviewed, 'all' once approved for live.
 */
require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/helpers.php';

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

const APPLY_ON = 'all';

/** md5 of the page text as earlier versions of this script wrote it. */
const EARLIER_BODIES = [
    '49f8b1298d4baa917e836576758722a8',   // v1, 14 Nov 2026: the first eight signs
];

$env = (config()['site']['env'] ?? 'live') === 'staging' ? 'staging' : 'live';
if (APPLY_ON !== 'all' && APPLY_ON !== $env) { echo "apply_webartrose_page: not on $env yet\n"; exit(0); }

/** A pair of examples: the same thing new, and worn ($wornInner when the worn one differs). */
function pair(string $inner, string $worn, ?string $wornInner = null, string $wornAttrs = ''): string
{
    return '<div class="wear-pair">'
         . '<div class="wear-demo"><p class="wear-caption">New</p>' . $inner . '</div>'
         . '<div class="wear-demo" style="' . $worn . '"' . $wornAttrs . '><p class="wear-caption">Worn</p>' . ($wornInner ?? $inner) . '</div>'
         . '</div>';
}

$card = fn (string $extra = '') => '<a class="link-figure" href="/thoughts"><figure class="article-figure" style="background-image:'
      . esc(title_gradient('Craftsmanship')) . '"><time class="article-date">2018</time>'
      . '<strong class="article-title">Craftsmanship</strong>' . $extra . '</figure></a>';
$menu = '<ul><li class="nav-item"><a href="/talks"><strong>Talks</strong><span class="subtitle">I gave</span></a></li></ul>';
$flickerMenu = '<ul><li class="nav-item wear-flicker"><a href="/talks"><strong>Talks</strong><span class="subtitle">I gave</span></a></li></ul>';
$button = '<div class="publication"><ul class="bullit"><li><a href="/publications">Read it</a></li></ul></div>';
$tried = '<p><strong>A title that looks like a link</strong></p>' . $button;
$here = '<ul class="wear-menu"><li class="nav-item"><a href="/"><strong>Work</strong></a></li>'
      . '<li class="nav-item is-active"><a href="/talks"><strong>Talks</strong></a></li>'
      . '<li class="nav-item"><a href="/thoughts"><strong>Thoughts</strong></a></li></ul>';
$routes = fn (string $pairs) => '<div class="wear-menubox"><canvas class="wear-routes" data-routes="' . esc($pairs) . '" aria-hidden="true"></canvas>'
        . '<ul class="wear-menu"><li class="nav-item"><a href="/"><strong>Work</strong></a></li>'
        . '<li class="nav-item"><a href="/talks"><strong>Talks</strong></a></li>'
        . '<li class="nav-item"><a href="/thoughts"><strong>Thoughts</strong></a></li></ul></div>';
$reach = '<div class="wear-edgebox"><span class="wear-edge"></span><ul><li>The first</li><li>The second</li><li>The third</li>'
       . '<li>The fourth</li><li>The fifth</li><li>The sixth</li></ul></div>';
$slider = fn (string $spots) => '<div class="wear-slider"><span class="wear-track" style="' . $spots . '"></span><span class="wear-thumb"></span></div>';
$spot = fn (float $at, float $w) => 'radial-gradient(ellipse ' . $w . 'px 5px at calc(8px + (100% - 16px) * ' . $at . ') 50%, rgba(244,238,228,.95) 55%, rgba(150,130,100,.35) 75%, transparent)';
$screen = '<div class="wear-screen"><span class="wear-button"></span></div>';
$head = fn (string $extra) => '<div class="wear-head">' . $extra . 'Craftsmanship</div>';
$staples = fn (int $n) => '<canvas class="wear-staples" data-n="' . $n . '" aria-hidden="true"></canvas>';

$body = <<<HTML
<p>Everything around us wears. The edges of a table grow round, paint flakes where the sun hits it, plastic turns yellow, wood curls where it got wet, a door handle shines where hands go. Use leaves traces, and those traces give things character: they show that something has been around for a while, has been through a lot, and is used. For an interface, being used may be the best thing that can happen to it.</p>
<p>Webartrose is a concept from my graduation in Interaction Design. The name comes from <em>artrose</em>, the Dutch word for osteoarthritis: joints that wear with use. On this site it is a layer over everything you see. The use of all visitors together leaves its marks on the thing itself: analytics, not in a dashboard, but on the element you touch.</p>
<p>Some of the signs below came from photos of things worn by time: a can bleached above the cup holder it stood in for two years, a lamp post furry with forty years of staples, a door at IKEA that does not open, scratched around its handle anyway. Ordinary actions, repeated long enough, leave traces you can read.</p>

<h2>What wears</h2>

<h3>Touched</h3>
<p>Where fingers go, a link gets a greasy shine and its underline rubs thin. Going to it with the keyboard counts too.</p>
PAIR_LINK

<h3>Pressed</h3>
<p>A button that is pressed a lot sinks in a little. Its outline rubs away, the middle darkens, and in the end it gets a waist, like a post that cows have licked smooth.</p>
PAIR_BUTTON

<h3>Worked loose</h3>
<p>Menu items that are used every day work loose and hang slightly crooked.</p>
PAIR_MENU

<h3>Flickering</h3>
<p>A much-pressed item starts to flicker now and then, like old neon. Something nobody touched for months flickers once as it comes back on, as after a power cut.</p>
PAIR_FLICKER

<h3>Handled</h3>
<p>Cards get rounded corners and fingerprints where they are picked up. Handled long enough, the card wears through and the grid shows where the thumb goes.</p>
PAIR_CARD

<h3>In the sun</h3>
<p>What is looked at a lot fades: colours bleach and photos turn yellow. Older stories have been in the sun longer.</p>
PAIR_PHOTO

<h3>Tan line</h3>
<p>The screen itself yellows in the sun, except behind the round button at the bottom right, which always covers it: a ring stays fresh around it.</p>
PAIR_SUN

<h3>Read</h3>
<p>Text that is read a lot fades a little, and gets a thumb mark at the edge where the page is held.</p>
PAIR_TEXT

<h3>Graphite</h3>
<p>Text that people select, to copy or to read along, gets a grey smudge, like a clipboard after years of crosswords.</p>
PAIR_GRAPHITE

<h3>Walked</h3>
<p>Where the pointer walks, paths appear and the grid rubs away, like a carpet.</p>
PAIR_FLOOR

<h3>Tried</h3>
<p>Where people click on something that does nothing, or just next to a button, scuffs appear, like the paint around the handle of a fake door. They show what people expect to work, and where a button is not where the hand goes.</p>
PAIR_TRIED

<h3>How far</h3>
<p>The left edge of the page wears as far as people get down it, and fades where they stop. Not many kids make it past the fifth bar of the monkey bars.</p>
PAIR_REACH

<h3>Worn through</h3>
<p>Every page in a section passes the same menu item, so the item of where you are wears through to the grid paper underneath, like the spot on a library globe where everyone points to say: we are here.</p>
PAIR_HERE

<h3>Routes</h3>
<p>The ways people go between sections wear into the menu, like tyre marks on the roof of a parking garage.</p>
PAIR_ROUTES

<h3>Staples</h3>
<p>Every ten visits leave a staple at the foot of the page. A page visited for years starts to look like a lamp post after forty years of posters.</p>
PAIR_STAPLES

<h3>Dog-ears</h3>
<p>A page that people send each other gets a folded corner, like a banknote that has been around: the more it arrives through shared links, the bigger the fold.</p>
PAIR_DOGEAR

<h3>The maker</h3>
<p>My own edits wear a spot beside the title, where I stand, like the floor next to a barber's chair. Pages I keep rewriting show it most.</p>
PAIR_MAKER

<h3>The slider</h3>
<p>Even the time slider wears, where people leave it, like the paint on the weights that are used most.</p>
PAIR_SLIDER

<h2>Through time</h2>
<p>With the round button at the bottom right you can switch the layer off, or move through time: from as new, through now, to thirty years from now, if the site keeps being used the way it is.</p>

<h2>Always usable</h2>
<p>Wear only changes how things look, never where they are. Text stays readable and everything keeps working, also thirty years from now.</p>

<h2>What is counted</h2>
<p>Only anonymous counts: how often each element was touched, pressed and seen, how often the pointer passed each square of the grid, where clicks landed on things that do nothing, how far down a page people got, how often a page was opened and how often from a link elsewhere, the ways between the sections of the menu, and where the slider was left. No cookies, no IP addresses, nothing that tells visitors apart. Browsers that ask not to be tracked are not counted.</p>
<p>Wear can still give things away: on a worn keypad you can read the code. So a scuff only appears once a spot was clicked by several people, never for one visitor alone.</p>
HTML;

$body = strtr($body, [
    'PAIR_LINK'     => pair('<p>Read more <a href="/thoughts">Thoughts</a> on my mind.</p>', '--wt:.9;--wp:.8;--ws:0;--wr:.5'),
    'PAIR_BUTTON'   => pair($button, '--wt:.6;--wp:1;--ws:0;--wr:-.8'),
    'PAIR_MENU'     => pair($menu, '--wt:.7;--wp:.9;--ws:0;--wr:-.9'),
    'PAIR_FLICKER'  => pair($menu, '--wt:.7;--wp:.9;--ws:0;--wr:.6', $flickerMenu),
    'PAIR_CARD'     => pair($card(), '--wt:.95;--wp:.5;--ws:.6;--wr:.8'),
    'PAIR_PHOTO'    => pair('<img src="/assets/talks/20170713_ixdc_beijing.jpg" alt="Paul giving a talk at IXDC in Beijing">', '--wt:.5;--wp:0;--ws:.9;--wr:0;--wa:.6'),
    'PAIR_SUN'      => pair($screen, '--wsun:1'),
    'PAIR_TEXT'     => pair('<p>A line of text, read many times.</p>', '--wt:0;--wp:0;--ws:1;--wr:-.2;--wa:.5'),
    'PAIR_GRAPHITE' => pair('<p>A line of text people copy.</p>', '--wg:1;--wr:.3'),
    'PAIR_FLOOR'    => pair('<div class="wear-floor" aria-hidden="true"></div>', '--wt:1;--wp:0;--ws:0;--wr:0'),
    'PAIR_TRIED'    => pair($tried, '', null, ' data-scuffs="' . esc('[[30,42,0.9],[52,40,0.6],[44,74,0.8],[56,80,0.5]]') . '"'),
    'PAIR_REACH'    => pair($reach, '--wd:1'),
    'PAIR_HERE'     => pair($here, '--wh:1;--wr:.3'),
    'PAIR_ROUTES'   => pair($routes('[]'), '', $routes('[[0,1,900],[1,0,700],[1,2,350],[2,1,280],[0,2,120]]')),
    'PAIR_STAPLES'  => pair($staples(6), '', $staples(3200)),
    'PAIR_DOGEAR'   => pair($card('<span class="wear-dogear"></span>'), '--we:.9;--wr:-.3'),
    'PAIR_MAKER'    => pair($head('<span class="wear-maker"></span>'), '--wm:1'),
    'PAIR_SLIDER'   => pair($slider(''), '', $slider('background-image:' . $spot(.25, 16) . ',' . $spot(0, 10) . ',' . $spot(1, 12) . ',' . $spot(.5, 8))),
]);

$page = q1("SELECT id, body_html FROM pages WHERE slug = 'webartrose'");
if (!$page) {
    exec_sql('INSERT INTO pages (title, slug, subtitle, body_html, nav_order, in_nav) VALUES (?,?,?,?,0,0)',
             ['Webartrose', 'webartrose', 'signs of use', $body]);
    echo "webartrose: page created\n";
} elseif (md5((string) $page['body_html']) === md5($body)) {
    echo "webartrose: page already up to date\n";
} elseif (in_array(md5((string) $page['body_html']), EARLIER_BODIES, true)) {
    exec_sql('UPDATE pages SET body_html = ? WHERE id = ?', [$body, $page['id']]);
    echo "webartrose: page text updated\n";
} else {
    echo "webartrose: page was edited in the admin; left alone\n";
}

<?php
/**
 * The Webartrose page (/webartrose, Nov 2026): what the signs of use on the
 * site are, and why. Linked from the Webartrose panel, not in the menu.
 * Runs from deploy.sh and after the nightly staging refresh; can be run by
 * hand: php bin/apply_webartrose_page.php.
 *
 * It only creates the page when it does not exist yet, so edits made in
 * the admin stay. Each sign of use has a pair of examples, new and worn:
 * the worn one has fixed wear values (--wt touched, --wp pressed, --ws seen,
 * --wr which way it leans), styled by "Webartrose" in screen.css.
 *
 * APPLY_ON: 'staging' while being reviewed, 'all' once approved for live.
 */
require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/helpers.php';

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

const APPLY_ON = 'all';

$env = (config()['site']['env'] ?? 'live') === 'staging' ? 'staging' : 'live';
if (APPLY_ON !== 'all' && APPLY_ON !== $env) { echo "apply_webartrose_page: not on $env yet\n"; exit(0); }

if (q1("SELECT id FROM pages WHERE slug = 'webartrose'")) { echo "webartrose: page already there\n"; exit(0); }

/** A pair of examples: the same thing new, and worn ($wornInner when the worn one differs). */
function pair(string $inner, string $worn, ?string $wornInner = null): string
{
    return '<div class="wear-pair">'
         . '<div class="wear-demo"><p class="wear-caption">New</p>' . $inner . '</div>'
         . '<div class="wear-demo" style="' . $worn . '"><p class="wear-caption">Worn</p>' . ($wornInner ?? $inner) . '</div>'
         . '</div>';
}

$card = '<a class="link-figure" href="/thoughts"><figure class="article-figure" style="background-image:'
      . esc(title_gradient('Craftsmanship')) . '"><time class="article-date">2018</time>'
      . '<strong class="article-title">Craftsmanship</strong></figure></a>';
$menu = '<ul><li class="nav-item"><a href="/talks"><strong>Talks</strong><span class="subtitle">I gave</span></a></li></ul>';
$flickerMenu = '<ul><li class="nav-item wear-flicker"><a href="/talks"><strong>Talks</strong><span class="subtitle">I gave</span></a></li></ul>';

$body = <<<HTML
<p>Everything around us wears. The edges of a table grow round, paint flakes where the sun hits it, plastic turns yellow, wood curls where it got wet, a door handle shines where hands go. Use leaves traces, and those traces give things character: they show that something has been around for a while, has been through a lot, and is used. For an interface, being used may be the best thing that can happen to it.</p>
<p>Webartrose is a concept from my graduation in Interaction Design. The name comes from <em>artrose</em>, the Dutch word for osteoarthritis: joints that wear with use. On this site it is a layer over everything you see. The use of all visitors together leaves its marks on the thing itself: analytics, not in a dashboard, but on the element you touch.</p>

<h2>What wears</h2>

<h3>Touched</h3>
<p>Where fingers go, a link gets a greasy shine and its underline rubs thin.</p>
PAIR_LINK

<h3>Pressed</h3>
<p>A button that is pressed a lot sinks in a little. Its outline rubs away and the middle darkens.</p>
PAIR_BUTTON

<h3>Worked loose</h3>
<p>Menu items that are used every day work loose and hang slightly crooked.</p>
PAIR_MENU

<h3>Flickering</h3>
<p>A much-pressed item starts to flicker now and then, like old neon. Something nobody touched for months flickers once as it comes back on, as after a power cut.</p>
PAIR_FLICKER

<h3>Handled</h3>
<p>Cards get rounded corners and fingerprints where they are picked up.</p>
PAIR_CARD

<h3>In the sun</h3>
<p>What is looked at a lot fades: colours bleach and photos turn yellow. Older stories have been in the sun longer.</p>
PAIR_PHOTO

<h3>Read</h3>
<p>Text that is read a lot fades a little, and gets a thumb mark at the edge where the page is held.</p>
PAIR_TEXT

<h3>Walked</h3>
<p>Where the pointer walks, paths appear and the grid rubs away, like a carpet.</p>
PAIR_FLOOR

<h2>Through time</h2>
<p>With the round button at the bottom right you can switch the layer off, or move through time: from as new, through now, to thirty years from now, if the site keeps being used the way it is.</p>

<h2>Always usable</h2>
<p>Wear only changes how things look, never where they are. Text stays readable and everything keeps working, also thirty years from now.</p>

<h2>What is counted</h2>
<p>Only anonymous counts: how often each element was touched, pressed and seen, and how often the pointer passed each square of the grid. No cookies, no IP addresses, nothing that tells visitors apart. Browsers that ask not to be tracked are not counted.</p>
HTML;

$body = strtr($body, [
    'PAIR_LINK'    => pair('<p>Read more <a href="/thoughts">Thoughts</a> on my mind.</p>', '--wt:.9;--wp:.8;--ws:0;--wr:.5'),
    'PAIR_BUTTON'  => pair('<div class="publication"><ul class="bullit"><li><a href="/publications">Read it</a></li></ul></div>', '--wt:.6;--wp:.9;--ws:0;--wr:-.8'),
    'PAIR_MENU'    => pair($menu, '--wt:.7;--wp:.9;--ws:0;--wr:-.9'),
    'PAIR_FLICKER' => pair($menu, '--wt:.7;--wp:.9;--ws:0;--wr:.6', $flickerMenu),
    'PAIR_CARD'    => pair($card, '--wt:.9;--wp:.5;--ws:.6;--wr:.8'),
    'PAIR_PHOTO'   => pair('<img src="/assets/talks/20170713_ixdc_beijing.jpg" alt="Paul giving a talk at IXDC in Beijing">', '--wt:.5;--wp:0;--ws:.9;--wr:0;--wa:.6'),
    'PAIR_TEXT'    => pair('<p>A line of text, read many times.</p>', '--wt:0;--wp:0;--ws:1;--wr:-.2;--wa:.5'),
    'PAIR_FLOOR'   => pair('<div class="wear-floor" aria-hidden="true"></div>', '--wt:1;--wp:0;--ws:0;--wr:0'),
]);

exec_sql('INSERT INTO pages (title, slug, subtitle, body_html, nav_order, in_nav) VALUES (?,?,?,?,0,0)',
         ['Webartrose', 'webartrose', 'signs of use', $body]);
echo "webartrose: page created\n";

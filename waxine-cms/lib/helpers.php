<?php
/**
 * Shared helpers: escaping, slugs, the title-gradient (ported 1:1 from the
 * old functions.php so migrated posts keep the exact same header colours),
 * and renderers for the legacy article helper calls used during migration.
 */

/** HTML-escape for output. */
function esc(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Make a URL-safe slug from arbitrary text. New posts use this. */
function slugify(string $text): string
{
    $text = trim($text);
    if (function_exists('iconv')) {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        if ($t !== false) $text = $t;
    }
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '_', $text);
    $text = trim($text, '_');
    return $text === '' ? 'post' : $text;
}

/* ---------------------------------------------------------------------
 *  Title gradient  (ported from old functions.php)
 *  The old site derived two hex colours from the article's underscore
 *  slug via ascii2hex(), then took the first 6 and last 6 hex chars.
 * ------------------------------------------------------------------- */

function ascii2hex(string $ascii): string
{
    $ascii = str_replace('_', '', $ascii);
    $hex = '';
    $len = strlen($ascii);
    for ($i = 0; $i < $len; $i++) {
        $byte = strtoupper(dechex(ord($ascii[$i])));   // PHP8-safe [] indexing
        $byte = str_pad($byte, 2, '0', STR_PAD_LEFT);
        $hex .= $byte;
    }
    return $hex;
}

function grad_first6(string $slug): string
{
    $hex = ascii2hex($slug);
    return str_pad(substr($hex, 0, 6), 6, '0');
}

function grad_last6(string $slug): string
{
    $hex = ascii2hex($slug);
    return str_pad(substr($hex, -6), 6, '0');
}

/* ---------------------------------------------------------------------
 *  Title colours
 *  Every story gets a gradient made from the letters of its title:
 *   - start colour: the first letter of the title (each letter a-z has
 *     its own colour on a colour wheel); the drop capital uses it too
 *   - end colour:   all letters of the title together (their positions
 *     in the alphabet added up, wrapped around the same wheel)
 *   - direction:    the number of words in the title, in five steps from
 *                   left-to-right to top-to-bottom, so the start colour always
 *                   sits behind the title (top left), like the drop capital
 *  When the letters add up to the same colour as the first letter, the end
 *  colour moves five steps along the wheel, so every card keeps a gradient.
 *  Only the letters a-z count: capitals, accents, digits and punctuation
 *  are ignored. Each colour is darkened just enough to keep a contrast of at
 *  least 4.5:1 (WCAG AA) both behind the light title text (#F9F8F7) and as
 *  text on the page background (#F4F1EF), so the same colour also works for
 *  the drop capital and the links in a story.
 * ------------------------------------------------------------------- */

/** The letters of a title that count: lowercase a-z only. */
function title_letters(string $title): string
{
    $letters = preg_replace('/[^a-z]/', '', strtolower($title));
    return $letters === '' ? 'a' : $letters;
}

/** Hue on the colour wheel for alphabet position 0 (a) to 25 (z). */
function letter_hue(int $position): float
{
    return ($position % 26) / 26 * 360 + 20;
}

/** OKLCH colour to sRGB [r, g, b] in 0-255. */
function oklch_to_rgb(float $L, float $C, float $hue): array
{
    $h = deg2rad($hue);
    $a = $C * cos($h);
    $b = $C * sin($h);
    $l = ($L + 0.3963377774 * $a + 0.2158037573 * $b) ** 3;
    $m = ($L - 0.1055613458 * $a - 0.0638541728 * $b) ** 3;
    $s = ($L - 0.0894841775 * $a - 1.2914855480 * $b) ** 3;
    $lin = [
         4.0767416621 * $l - 3.3077115913 * $m + 0.2309699292 * $s,
        -1.2684380046 * $l + 2.6097574011 * $m - 0.3413193965 * $s,
        -0.0041960863 * $l - 0.7034186147 * $m + 1.7076147010 * $s,
    ];
    return array_map(function ($x) {
        $x = max(0.0, min(1.0, $x));
        return 255 * ($x <= 0.0031308 ? 12.92 * $x : 1.055 * $x ** (1 / 2.4) - 0.055);
    }, $lin);
}

/** WCAG contrast ratio between an [r, g, b] colour and the title text colour. */
function contrast_with_title_text(array $rgb): float
{
    $lum = function (array $c): float {
        $f = function ($v) { $v /= 255; return $v <= 0.04045 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4; };
        return 0.2126 * $f($c[0]) + 0.7152 * $f($c[1]) + 0.0722 * $f($c[2]);
    };
    return ($lum([249, 248, 247]) + 0.05) / ($lum($rgb) + 0.05);
}

/** WCAG contrast ratio between an [r, g, b] colour and the page background. */
function contrast_with_page(array $rgb): float
{
    $lum = function (array $c): float {
        $f = function ($v) { $v /= 255; return $v <= 0.04045 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4; };
        return 0.2126 * $f($c[0]) + 0.7152 * $f($c[1]) + 0.0722 * $f($c[2]);
    };
    return ($lum([0xF4, 0xF1, 0xEF]) + 0.05) / ($lum($rgb) + 0.05);
}

/** Readable colour (hex, no #) for a hue: as light as contrast allows. */
function readable_hue_colour(float $hue): string
{
    for ($L = 0.56; $L > 0.2; $L -= 0.01) {
        $rgb = oklch_to_rgb($L, 0.14, $hue);
        // The page background is the darker of the two, so passing against
        // it also passes against the light title text.
        if (contrast_with_page($rgb) >= 4.6) break;
    }
    return implode('', array_map(fn($v) => sprintf('%02x', (int) round(max(0, min(255, $v)))), $rgb));
}

/** ['start' => hex, 'end' => hex, 'angle' => degrees] for a title. */
function title_colours(string $title): array
{
    $letters = title_letters($title);
    $sum = 0;
    for ($i = 0, $n = strlen($letters); $i < $n; $i++) $sum += ord($letters[$i]) - 97;
    $words = count(preg_split('/\s+/', trim($title), -1, PREG_SPLIT_NO_EMPTY)) ?: 1;
    $first = ord($letters[0]) - 97;
    $end   = $sum % 26;
    if ($end === $first) $end = ($end + 5) % 26;
    return [
        'start' => readable_hue_colour(letter_hue($first)),
        'end'   => readable_hue_colour(letter_hue($end)),
        'angle' => 90 + (($words - 1) % 5) * 22.5,
    ];
}

/** CSS background for a story's header and overview card. */
function title_gradient(string $title): string
{
    $c = title_colours($title);
    return 'linear-gradient(' . $c['angle'] . 'deg,#' . $c['start'] . ',#' . $c['end'] . ')';
}

/** Start colour (hex, no #), used for the drop capital. */
function title_start_colour(string $title): string
{
    return title_colours($title)['start'];
}

/** Build an excerpt (plain text) from HTML body. */
function make_excerpt(string $html, int $len = 180): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)));
    if (mb_strlen($text) <= $len) return $text;
    return rtrim(mb_substr($text, 0, $len - 1)) . '…';
}

/* ---------------------------------------------------------------------
 *  Legacy article-helper renderers.
 *  The old per-story files called image()/dropcap()/embedVideo(); the
 *  migration script uses these to turn those calls into static HTML.
 * ------------------------------------------------------------------- */

function legacy_image(string $file, string $filetype, string $description, string $layout): string
{
    return "<img class='image-{$layout}' src='/images/" . esc($file) . '.' . esc($filetype)
         . "' alt='" . esc(ucfirst($description)) . "'>";
}

function legacy_dropcap(string $char, string $color): string
{
    $c = substr(ascii2hex($color), 0, 6);
    return "<span class='dropcap' style='color:#{$c}'>" . esc($char) . '</span>';
}

function legacy_embed_video(string $videoID): string
{
    if (is_numeric($videoID)) {
        $src = 'https://player.vimeo.com/video/' . $videoID;
    } else {
        $src = 'https://www.youtube.com/embed/' . $videoID . '?rel=0';
    }
    return '<p class="video-container"><iframe class="video" src="' . esc($src)
         . '" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe></p>';
}

/* ---------------------------------------------------------------------
 *  Automatic drop capital (front end)
 * ------------------------------------------------------------------- */
/**
 * Automatic drop capital.
 *
 * The big first letter of a story is added by the front end, so nothing
 * special is needed in the CMS: just start the story with a paragraph.
 *
 *  - The first paragraph that contains text gets the class "has-dropcap";
 *    the CSS styles its first letter (p.has-dropcap::first-letter), so the
 *    capital is always as wide as the letter itself.
 *  - The colour comes from the story's slug (the first colour of the title
 *    gradient), set as --dropcap-color on .content in post.php.
 *  - Old hand-made <span class="dropcap">X</span> markup is unwrapped back
 *    to the plain letter, so old and new stories behave the same.
 */
function auto_dropcap(string $html): string
{
    if (stripos($html, '<p') === false) return $html;

    $doc = new DOMDocument();
    $prev = libxml_use_internal_errors(true);
    $doc->loadHTML(
        '<?xml encoding="UTF-8"?><div id="auto-dropcap-root">' . $html . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    $root = $doc->getElementById('auto-dropcap-root');
    if (!$root) return $html;

    // Unwrap legacy drop-cap spans.
    foreach (iterator_to_array($root->getElementsByTagName('span')) as $span) {
        if (!preg_match('/(^|\s)dropcap(\s|$)/', $span->getAttribute('class'))) continue;
        while ($span->firstChild) $span->parentNode->insertBefore($span->firstChild, $span);
        $span->parentNode->removeChild($span);
    }

    // Mark the first paragraph with text.
    foreach ($root->childNodes as $node) {
        if ($node->nodeName !== 'p' || trim($node->textContent) === '') continue;
        $class = trim($node->getAttribute('class') . ' has-dropcap');
        $node->setAttribute('class', $class);
        break;
    }

    $root->normalize(); // merge the unwrapped letter back into its word
    $out = '';
    foreach ($root->childNodes as $child) $out .= $doc->saveHTML($child);
    return $out;
}

/**
 * Live "years since" numbers in authored content.
 * Write <span data-years-since="2007">19</span> in a page or story; the
 * number inside is replaced on every request with the years elapsed since
 * that year, so it never goes stale. The number typed in the CMS is only
 * a fallback.
 */
function live_years(string $html): string
{
    $now = (int) date('Y');
    return preg_replace_callback(
        '/(<span\b[^>]*\bdata-years-since="(\d{4})"[^>]*>)[^<]*(<\/span>)/i',
        fn ($m) => $m[1] . max(0, $now - (int) $m[2]) . $m[3],
        $html
    ) ?? $html;
}

require_once __DIR__ . '/experiences.php';

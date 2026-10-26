<?php
/**
 * Migrate the old flat-file "thoughts" into the CMS database.
 *
 *   php migrate/import.php --source=/path/to/thoughts [options]
 *
 * Options:
 *   --source=DIR    Folder containing the old YYYYMMDD_title.php files   (required)
 *   --images=DIR    Old images/ folder to copy into public/images        (optional)
 *   --category=SLUG Attach every imported post to this category          (default: thoughts)
 *   --commit        Actually write to the database + copy images.
 *                   Without this flag the script runs a DRY RUN: it parses
 *                   everything, writes HTML previews to migrate/review/,
 *                   and prints a summary, but changes nothing.
 *
 * The importer is idempotent: re-running with --commit updates existing
 * posts (matched on legacy_filename) instead of duplicating them.
 *
 * Old story file format (see the original _static/functions.php):
 *   filename  YYYYMMDD_title_with_underscores.php
 *   date      = first 8 chars               -> published_at
 *   slug      = substr(name, 9, -4)         -> stays the URL slug
 *   title     = underscores->spaces, ucfirst(strtolower())
 *   body      = HTML, possibly with image()/dropcap()/embedVideo() calls
 */

require_once dirname(__DIR__) . '/lib/helpers.php';

if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only.\n"); }

// ---- parse args ------------------------------------------------------
$opt = getopt('', ['source:', 'images::', 'category::', 'commit']);
$source   = $opt['source']   ?? '';
$imagesIn = $opt['images']   ?? '';
$catSlug  = $opt['category'] ?? 'thoughts';
$commit   = array_key_exists('commit', $opt);

if ($source === '' || !is_dir($source)) {
    fwrite(STDERR, "Missing or invalid --source=DIR (the old thoughts/ folder).\n");
    exit(1);
}

echo $commit ? "== COMMIT MODE (writing to database) ==\n"
             : "== DRY RUN (no changes; previews in migrate/review/) ==\n";

// ---- helpers ---------------------------------------------------------

/** Same test the old site used to decide a file is an article. */
function is_article_file(string $f): bool {
    return $f !== '.' && $f !== '..'
        && $f[0] !== '.' && $f[0] !== '_'
        && substr($f, -4) === '.php';
}

/**
 * Turn a slug into a properly title-cased heading.
 *  - known acronyms are forced uppercase (MTR, UI, URL, ...)
 *  - small connector words are lowercased, except first/last word
 *  - a standalone "i" becomes "I"
 *  - hyphenated words capitalise both halves (matrix-printer -> Matrix-Printer)
 */
function nice_title(string $slug): string {
    static $acr = ['UI','UX','URL','QR','CCTV','ATL','MTR','API','CSS','HTML',
                   'SEO','AI','IOT','NFC','GPS','PDF','FAQ','HTTP','HTTPS','CTA','KPI'];
    static $small = ['a','an','and','as','at','but','by','for','from','in','into',
                     'of','on','or','the','to','via','with','vs'];
    $words = array_values(array_filter(explode('_', $slug), fn($w) => $w !== ''));
    $n = count($words);
    $out = [];
    foreach ($words as $i => $w) {
        $core = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $w));
        if ($core !== '' && in_array(strtoupper($core), $acr, true)) {
            $out[] = preg_replace_callback('/[A-Za-z]/', fn($m) => strtoupper($m[0]), $w);
        } elseif ($core === 'i') {
            $out[] = preg_replace('/[A-Za-z]/', 'I', $w, 1);
        } elseif (in_array($core, $small, true) && $i > 0 && $i < $n - 1) {
            $out[] = mb_strtolower($w, 'UTF-8');
        } else {
            $lw = mb_strtolower($w, 'UTF-8');
            $out[] = preg_replace_callback('/(^|-)([a-z])/', fn($m) => $m[1] . strtoupper($m[2]), $lw);
        }
    }
    return implode(' ', $out);
}

/**
 * Turn one old story file's raw PHP/HTML into clean stored HTML.
 * Strategy: strip the wrapper includes and setup, render the known
 * helper calls to static HTML, and drop leftover pure-PHP include/comment
 * blocks. Anything more exotic is reported so it can be checked by hand.
 */
function extract_body(string $raw, string $slug, string $fileBase, array &$warnings): string {
    $s = $raw;

    // 1) Remove include/require of the framework wrappers + script tags.
    $s = preg_replace(
        '/<\?(php)?\s*(include|include_once|require|require_once)\b[^?]*\?>/i',
        '', $s
    );
    // Remove leading setup blocks like <? $file = basename(__FILE__,'.php'); ?>
    $s = preg_replace('/<\?(php)?\s*\$file\s*=.*?\?>/is', '', $s);
    $s = preg_replace('/<\?(php)?[^?]*__FILE__[^?]*\?>/is', '', $s);
    $s = preg_replace('#<script\b[^>]*keyboardcontrol[^>]*></script>#i', '', $s);
    $s = preg_replace('#<script\b[^>]*noise[^>]*></script>#i', '', $s);

    // 1b) Older inline image style (predates the image() helper):
    //     <img src="<? echo $imagepath.$file."_image1.jpg"; ?>">
    //     -> /images/<filename>_image1.jpg  (all post images live in /images)
    $s = preg_replace_callback(
        '/<\?(?:php)?\s*echo\s+\$imagepath\s*\.\s*\$file\s*\.\s*([\'"])(.*?)\1\s*;?\s*\?>/is',
        fn($m) => '/images/' . $fileBase . $m[2],
        $s
    );

    // 2) Render helper calls -> HTML. Handles <? / <?php, an optional
    //    `echo`, and a trailing `;`. The real story files write these as
    //    e.g.  <?php echo image("f","jpg","desc","full"); ?>
    // image('file','type','desc','layout')
    $s = preg_replace_callback(
        '/<\?(?:php)?\s*(?:echo\s+)?image\s*\(\s*([\'"])(.*?)\1\s*,\s*([\'"])(.*?)\3\s*,\s*([\'"])(.*?)\5\s*,\s*([\'"])(.*?)\7\s*\)\s*;?\s*\?>/is',
        fn($m) => legacy_image($m[2], $m[4], $m[6], $m[8]),
        $s
    );
    // dropcap('X', 'color')  OR  dropcap('X', $fileNameWithoutDate)
    //   In the originals the colour seed is the article slug variable, so
    //   when the 2nd arg is $fileNameWithoutDate we substitute $slug.
    $s = preg_replace_callback(
        '/<\?(?:php)?\s*(?:echo\s+)?dropcap\s*\(\s*([\'"])(.*?)\1\s*,\s*(?:([\'"])(.*?)\3|\$[A-Za-z_]\w*)\s*\)\s*;?\s*\?>/is',
        fn($m) => legacy_dropcap($m[2], isset($m[4]) && $m[4] !== '' ? $m[4] : $slug),
        $s
    );
    // embedVideo('id')  or embedVideo(12345)
    $s = preg_replace_callback(
        '/<\?(?:php)?\s*(?:echo\s+)?embedVideo\s*\(\s*([\'"]?)(.*?)\1\s*\)\s*;?\s*\?>/is',
        fn($m) => legacy_embed_video($m[2]),
        $s
    );

    // 3) Remove pure-comment / whitespace PHP blocks that are now noise.
    $s = preg_replace('/<\?(php)?\s*(\/\/[^\n]*|\/\*.*?\*\/|\s*)\s*\?>/s', '', $s);

    // 4) Flag any PHP still left (means custom logic we did not translate).
    if (preg_match('/<\?/', $s)) {
        $warnings[] = 'Leftover PHP code — review this post by hand.';
    }

    // 5) Tidy stray closing tags from the old wrappers and trim.
    $s = preg_replace('#</?(html|head|body|main|div id="wrapper")[^>]*>#i', '', $s);
    return trim($s);
}

// ---- scan ------------------------------------------------------------
$files = array_values(array_filter(scandir($source), 'is_article_file'));
sort($files);

if (!$files) { fwrite(STDERR, "No article files found in $source.\n"); exit(1); }

$reviewDir = __DIR__ . '/review';
if (!$commit && !is_dir($reviewDir)) mkdir($reviewDir, 0775, true);

$rows = [];
foreach ($files as $f) {
    $slug  = substr($f, 9, -4);                 // fileNameWithoutDate
    $dateS = substr($f, 0, 8);                  // YYYYMMDD
    $ts    = strtotime($dateS);
    $title = nice_title($slug);
    $raw   = file_get_contents($source . '/' . $f);
    $warn  = [];
    $body  = extract_body($raw, $slug, substr($f, 0, -4), $warn);
    if (!$ts)          $warn[] = "Unparseable date prefix '$dateS'.";
    if ($slug === '')  $warn[] = 'Empty slug from filename.';
    if (trim($body) === '') $warn[] = 'Empty body after extraction.';

    $rows[] = [
        'file'  => $f,
        'slug'  => $slug,
        'title' => $title,
        'date'  => $ts ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s'),
        'body'  => $body,
        'warn'  => $warn,
    ];
}

// ---- report ----------------------------------------------------------
$warned = 0;
foreach ($rows as $r) {
    $flag = $r['warn'] ? '  [!] ' . implode(' ', $r['warn']) : '';
    if ($r['warn']) $warned++;
    printf("%-40s %s  %s%s\n", $r['file'], substr($r['date'],0,10), $r['title'], $flag);

    if (!$commit) {
        $preview = "<!doctype html><meta charset='utf-8'>"
                 . "<h1>{$r['title']}</h1><p><em>{$r['date']} — slug: {$r['slug']}</em></p><hr>"
                 . $r['body'];
        file_put_contents($reviewDir . '/' . $r['slug'] . '.html', $preview);
    }
}
echo "\n" . count($rows) . " posts parsed, {$warned} with warnings.\n";

// ---- commit ----------------------------------------------------------
if (!$commit) {
    echo "Dry run complete. Open migrate/review/*.html to check the output,\n";
    echo "then re-run with --commit to write to the database.\n";
    exit(0);
}

require_once dirname(__DIR__) . '/lib/db.php';
$cfg = config();

// Category
$cat = q1('SELECT id FROM categories WHERE slug = ?', [$catSlug]);
if (!$cat) {
    exec_sql('INSERT INTO categories (name, slug) VALUES (?,?)', [ucfirst($catSlug), $catSlug]);
    $catId = (int) db()->lastInsertId();
} else { $catId = (int)$cat['id']; }

// Optional image copy
if ($imagesIn !== '' && is_dir($imagesIn)) {
    $dest = $cfg['site']['uploads_dir'];
    if (!is_dir($dest)) mkdir($dest, 0775, true);
    $n = 0;
    foreach (scandir($imagesIn) as $img) {
        if ($img[0] === '.') continue;
        $src = $imagesIn . '/' . $img;
        if (is_file($src)) { copy($src, rtrim($dest,'/') . '/' . $img); $n++; }
    }
    echo "Copied {$n} images into {$dest}.\n";
}

$ins = 0; $upd = 0;
foreach ($rows as $r) {
    $excerpt = make_excerpt($r['body']);
    $existing = q1('SELECT id FROM posts WHERE legacy_filename = ?', [$r['file']]);
    if ($existing) {
        exec_sql('UPDATE posts SET title=?, slug=?, body_html=?, excerpt=?, status="published", published_at=? WHERE id=?',
            [$r['title'], $r['slug'], $r['body'], $excerpt, $r['date'], $existing['id']]);
        $pid = (int)$existing['id']; $upd++;
    } else {
        exec_sql('INSERT INTO posts (title, slug, legacy_filename, body_html, excerpt, status, published_at) VALUES (?,?,?,?,?,"published",?)',
            [$r['title'], $r['slug'], $r['file'], $r['body'], $excerpt, $r['date']]);
        $pid = (int) db()->lastInsertId(); $ins++;
    }
    exec_sql('INSERT IGNORE INTO post_categories (post_id, category_id) VALUES (?,?)', [$pid, $catId]);
}

echo "Done. Inserted {$ins}, updated {$upd}.\n";

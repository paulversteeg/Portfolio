<?php
/**
 * Webartrose: signs of use on the site (Nov 2026).
 *
 * Everything you can see or touch on the site wears with use, like things
 * in the real world: links get greasy, buttons get pressed in, menu items
 * hang crooked, photos fade in the sun, paths appear where the pointer
 * walks. The use of all visitors adds up, so the site ages for real.
 *
 * Only anonymous counts are stored: no cookies, no IP addresses, no
 * visitor ids. Per element (a short key such as "a /talks" or
 * "img /images/x.jpg"): how often it was touched, pressed and seen. Per
 * page: how often the pointer passed each square of the grid.
 *
 * assets/wear.js records and shows it; public/wear.php is the endpoint.
 */

const WEAR_KINDS       = ['a', 'img', 'card', 't', 'b'];   // link, image, card, text block, button
const WEAR_MAX_EVENTS  = 200;   // per request
const WEAR_MAX_STEP    = 3;     // how much one page view can add to one counter
const WEAR_MAX_CELLS   = 600;   // grid squares in one trail report
const WEAR_MAX_CELL_N  = 5;     // passes one page view can add to one square
const WEAR_MAX_READ    = 400;   // keys in one read request

/** Create the tables on first use, so no manual SQL step is needed. */
function ensure_wear_tables(): void
{
    static $done = false;
    if ($done) return;
    exec_sql(
        'CREATE TABLE IF NOT EXISTS wear (
           k        VARCHAR(191) NOT NULL,
           touch    INT UNSIGNED NOT NULL DEFAULT 0,
           press    INT UNSIGNED NOT NULL DEFAULT 0,
           seen     INT UNSIGNED NOT NULL DEFAULT 0,
           first_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
           last_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
           PRIMARY KEY (k)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    exec_sql(
        'CREATE TABLE IF NOT EXISTS wear_trail (
           page VARCHAR(191) NOT NULL,
           cx   SMALLINT     NOT NULL,
           cy   SMALLINT     NOT NULL,
           n    INT UNSIGNED NOT NULL DEFAULT 0,
           PRIMARY KEY (page, cx, cy)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $done = true;
}

/** A page path we accept: "/", "/talks", "/thoughts/some_post". */
function wear_page(string $p): ?string
{
    $p = '/' . trim($p, '/');
    return (strlen($p) <= 160 && preg_match('~^/[A-Za-z0-9/_.-]*$~', $p)) ? $p : null;
}

/** An element key we accept: "<kind> <value>", short and plain. */
function wear_key(string $k): ?string
{
    if (strlen($k) > 190 || !preg_match('~^([a-z]+) [\x21-\x7E]{1,180}$~', $k, $m)) return null;
    return in_array($m[1], WEAR_KINDS, true) ? $k : null;
}

/** Crawlers and tools do not wear the site. */
function wear_is_bot(string $ua): bool
{
    return $ua === '' || (bool) preg_match('~bot|crawl|spider|slurp|preview|headless|lighthouse|curl|wget|python|php~i', $ua);
}

/**
 * Add one page view's worth of use.
 * $events: [[key, touch, press, seen], ...]; $trail: [[cx, cy, n], ...].
 */
function wear_record(string $page, array $events, array $trail): int
{
    ensure_wear_tables();
    $added = 0;
    foreach (array_slice($events, 0, WEAR_MAX_EVENTS) as $e) {
        if (!is_array($e) || count($e) < 4 || !is_string($e[0])) continue;
        $k = wear_key($e[0]);
        if ($k === null) continue;
        [$t, $p, $s] = array_map(fn($v) => max(0, min(WEAR_MAX_STEP, (int) $v)), [$e[1], $e[2], $e[3]]);
        if ($t + $p + $s === 0) continue;
        exec_sql(
            'INSERT INTO wear (k, touch, press, seen) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE touch = touch + VALUES(touch), press = press + VALUES(press),
                                     seen = seen + VALUES(seen), last_at = CURRENT_TIMESTAMP',
            [$k, $t, $p, $s]
        );
        $added++;
    }
    foreach (array_slice($trail, 0, WEAR_MAX_CELLS) as $c) {
        if (!is_array($c) || count($c) < 3) continue;
        [$x, $y, $n] = [(int) $c[0], (int) $c[1], max(0, min(WEAR_MAX_CELL_N, (int) $c[2]))];
        if ($n === 0 || $x < -80 || $x > 80 || $y < 0 || $y > 4000) continue;
        exec_sql(
            'INSERT INTO wear_trail (page, cx, cy, n) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE n = n + VALUES(n)',
            [$page, $x, $y, $n]
        );
        $added++;
    }
    return $added;
}

/** The wear of the given elements, the trail of one page, and since when the site keeps count. */
function wear_read(string $page, array $keys): array
{
    ensure_wear_tables();
    $keys = array_values(array_unique(array_filter(array_map(
        fn($k) => is_string($k) ? wear_key($k) : null, array_slice($keys, 0, WEAR_MAX_READ)
    ))));
    $elements = [];
    foreach (array_chunk($keys, 100) as $chunk) {
        $rows = q('SELECT k, touch, press, seen, last_at FROM wear WHERE k IN (' . implode(',', array_fill(0, count($chunk), '?')) . ')', $chunk);
        foreach ($rows as $r) {
            // Days since anyone last used it: something left alone for months warms up when it comes back.
            $idle = max(0, (int) floor((time() - (strtotime((string) $r['last_at']) ?: time())) / 86400));
            $elements[$r['k']] = [(int) $r['touch'], (int) $r['press'], (int) $r['seen'], $idle];
        }
    }
    $trail = array_map(
        fn($r) => [(int) $r['cx'], (int) $r['cy'], (int) $r['n']],
        q('SELECT cx, cy, n FROM wear_trail WHERE page = ?', [$page])
    );
    $since = q1('SELECT MIN(first_at) AS s FROM wear');
    return [
        'elements' => (object) $elements,
        'trail'    => $trail,
        'since'    => $since['s'] ?? null,
    ];
}

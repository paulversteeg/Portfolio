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

const IMPORT_ON = 'staging';

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
        'env'   => 'staging',
        'match' => ['Digital transformation of Etihad'],
        'set'   => [
            'takeaway'    => 'A design language starts with alignment.',
            'description' => "Etihad Airways set out to become the number one digital airline. The creative challenge: a visually striking design language that translates the \"Choose Well\" proposition to UX and holds up in the hands of internal teams, agencies and vendors. An audit showed that 90% of the touchpoints were inconsistent. Starting from strategy, personas and an experience map linking business goals, user needs and channels, we defined design principles for how the experience looks, communicates, behaves and is built, and a design system that was first used for baggage drop and kiosks at the new Midfield terminal.",
        ],
    ],
];

foreach ($talkUpdates as $u) {
    if (($u['env'] ?? 'all') !== 'all' && $u['env'] !== $env) continue;
    $row = null;
    foreach ($u['match'] as $title) {
        $row = q1('SELECT * FROM talks WHERE title = ? ORDER BY id LIMIT 1', [$title]);
        if ($row) break;
    }
    if (!$row) { echo "talk not found, skipped: {$u['match'][0]}\n"; continue; }
    $diff = array_filter($u['set'], fn ($v, $k) => (string) $row[$k] !== $v, ARRAY_FILTER_USE_BOTH);
    if (!$diff) { echo "talk already up to date: {$row['title']}\n"; continue; }
    $sets = implode(', ', array_map(fn ($k) => "$k = ?", array_keys($diff)));
    exec_sql("UPDATE talks SET $sets WHERE id = ?", array_merge(array_values($diff), [$row['id']]));
    echo "talk updated: {$row['title']}\n";
}

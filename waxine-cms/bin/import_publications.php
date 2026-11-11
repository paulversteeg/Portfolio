<?php
/**
 * Move the hand-written publications on the Publications page into records
 * (see lib/publications.php), and apply text updates to publication records.
 * Runs from deploy.sh and can be run by hand: php bin/import_publications.php.
 * Does nothing once done.
 *
 * IMPORT_ON says where the import runs: 'staging' while being reviewed,
 * 'all' once approved for live. Updates marked 'env' => 'staging' are a
 * preview too.
 */
require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/helpers.php';

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

const IMPORT_ON = 'all';

$env = (config()['site']['env'] ?? 'live') === 'staging' ? 'staging' : 'live';
if (IMPORT_ON !== 'all' && IMPORT_ON !== $env) { echo "import_publications: not on $env yet\n"; exit(0); }
ensure_publications_table();

$n = import_publications_from_page();
echo $n ? "import_publications: $n publications imported\n" : "import_publications: nothing to import\n";

$publicationUpdates = [
    [
        'match' => ['MaaS Conference 2020: challenges in Mobility becoming a Service for everyone'],
        'set'   => [
            // The article on info.nl is gone; the PDF is rebuilt from the Internet Archive copy.
            'links'     => 'Download article | /assets/downloads/maas-conference-2020-paul-versteeg.pdf | pdf',
            'publisher' => 'INFO',
        ],
    ],
    [
        'match' => ['Unleash the artificial smartness', 'Unleash the Artificial Smartness'],
        'set'   => [
            'title'   => 'Unleash the Artificial Smartness',
            'summary' => "Article in the second edition of Mirabeau's Dynamic Design magazine. The current trend is to take a digital service and just add AI, while the people using it are left overwhelmed. From HAL 9000 and KITT to David in Prometheus, the article argues that the challenge is not autonomy but relevance: we should design artificial smartness instead of intelligence. When a self-driving Tesla crashed, the car did exactly what it was programmed to do; the error was the designer's, not the driver's. Machines should be companions that extend our abilities, like a horse and its jockey, or the centaurs of humans working with algorithms. Smart technology helps us understand the world so we can act better ourselves, and makes us more human again.",
        ],
    ],
];

foreach ($publicationUpdates as $u) {
    if (($u['env'] ?? 'all') !== 'all' && $u['env'] !== $env) continue;
    $item = null;
    foreach ($u['match'] as $title) {
        $item = q1('SELECT * FROM publications WHERE title = ? ORDER BY id LIMIT 1', [$title]);
        if ($item) break;
    }
    if (!$item) { echo "publication not found, skipped: {$u['match'][0]}\n"; continue; }
    $diff = array_filter($u['set'], fn ($v, $k) => (string) $item[$k] !== $v, ARRAY_FILTER_USE_BOTH);
    if (!$diff) { echo "publication already up to date: {$item['title']}\n"; continue; }
    $sets = implode(', ', array_map(fn ($k) => "$k = ?", array_keys($diff)));
    exec_sql("UPDATE publications SET $sets WHERE id = ?", array_merge(array_values($diff), [$item['id']]));
    echo "publication updated: {$item['title']}\n";
}

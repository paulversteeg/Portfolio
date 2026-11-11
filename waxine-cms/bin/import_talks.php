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

$n = import_talks_from_page();
echo $n ? "import_talks: $n talks imported\n" : "import_talks: nothing to import\n";

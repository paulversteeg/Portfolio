<?php
/**
 * Configuration for staging.waxine.nl.
 *
 * deploy.sh copies this file to staging-cms/config.php. It holds no
 * secrets: it reads the live configuration and only switches to the
 * staging database and marks the site as staging. Uploads keep going to
 * the live images folder, which staging shares.
 */
$cfg = require __DIR__ . '/../waxine-cms/config.php';

$cfg['db']['name']       = 'waxine_staging';
$cfg['site']['env']      = 'staging';
$cfg['site']['base_url'] = 'https://staging.waxine.nl';

return $cfg;

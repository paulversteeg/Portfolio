<?php
/**
 * Configuration for staging.waxine.nl.
 *
 * deploy.sh copies this file to staging-cms/config.php. It holds no
 * secrets: it reads the live configuration, switches to the staging
 * database and its own user (from config.local.php), and marks the site
 * as staging. Uploads keep going to
 * the live images folder, which staging shares.
 */
$cfg = require __DIR__ . '/../waxine-cms/config.php';

$cfg['db']['name']       = 'ywefbduf_waxine_staging';
$cfg['site']['env']      = 'staging';
$cfg['site']['base_url'] = 'https://staging.waxine.nl';

// Staging has its own database user, with access to the staging database
// only. Its user name and password live in config.local.php next to this
// file on the server (not in the repository, never overwritten by a deploy):
//   <?php return ['db' => ['user' => '...', 'pass' => '...']];
$local = __DIR__ . '/config.local.php';
if (is_file($local)) {
    $override = require $local;
    $cfg['db'] = array_merge($cfg['db'], $override['db'] ?? []);
}

return $cfg;

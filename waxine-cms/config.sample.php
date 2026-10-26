<?php
/**
 * Waxine CMS - configuration
 *
 * Copy this file to  config.php  and fill in your real values.
 * config.php is git-ignored and must NEVER be committed or web-served.
 */

return [
    // --- Database (MySQL) -------------------------------------------
    'db' => [
        'host'    => 'localhost',
        'name'    => 'waxine',
        'user'    => 'waxine_user',
        'pass'    => 'change-me',
        'charset' => 'utf8mb4',
    ],

    // --- Site --------------------------------------------------------
    'site' => [
        'title'     => 'Waxine',
        'tagline'   => 'that came to mind',
        // Base URL of the public site, no trailing slash.
        'base_url'  => 'https://www.waxine.nl',
        // Where uploaded images are stored (filesystem path) and served (url path).
        'uploads_dir' => __DIR__ . '/public/images',
        'uploads_url' => '/images',
        'timezone'  => 'Europe/Amsterdam',
    ],

    // --- Security ----------------------------------------------------
    // Used to sign the session cookie. Generate a fresh random string:
    //   php -r "echo bin2hex(random_bytes(32));"
    'app_secret' => 'replace-with-a-long-random-string',
];

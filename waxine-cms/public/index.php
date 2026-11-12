<?php
/**
 * Home page: the Work page (a CMS page with slug "work"), shown at /.
 * Until that page exists, the home page keeps showing the Thoughts listing.
 */
require_once __DIR__ . '/inc/bootstrap.php';

if (!q1("SELECT id FROM pages WHERE slug = 'work'")) {
    require __DIR__ . '/thoughts.php';
    exit;
}
$_GET['slug'] = 'work';
require __DIR__ . '/page.php';

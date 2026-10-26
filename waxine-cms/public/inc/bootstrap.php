<?php
/**
 * Front-end bootstrap: loads config, db and helpers for every public page.
 */
require_once dirname(__DIR__, 2) . '/lib/db.php';
require_once dirname(__DIR__, 2) . '/lib/helpers.php';

/** Fetch nav pages once. */
function nav_pages(): array
{
    static $pages = null;
    if ($pages === null) {
        $pages = q('SELECT title, slug, subtitle FROM pages WHERE in_nav = 1 ORDER BY nav_order, title');
    }
    return $pages;
}

/** URL builders. */
function post_url(array $p): string  { return '/thoughts/' . rawurlencode($p['slug']); }
function page_url(array $pg): string { return '/' . rawurlencode($pg['slug']); }

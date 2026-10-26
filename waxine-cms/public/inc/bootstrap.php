<?php
/**
 * Front-end bootstrap: loads config, db and helpers for every public page.
 */
require_once dirname(__DIR__, 2) . '/lib/db.php';
require_once dirname(__DIR__, 2) . '/lib/helpers.php';
require_once dirname(__DIR__, 2) . '/lib/auth.php';

// Staging is private: only a logged-in CMS user can see it, and search
// engines are told not to index it.
if (is_staging()) {
    header('X-Robots-Tag: noindex, nofollow');
    if (!current_user()) {
        header('Location: /admin/login.php?next=' . rawurlencode($_SERVER['REQUEST_URI'] ?? '/'));
        exit;
    }
}

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

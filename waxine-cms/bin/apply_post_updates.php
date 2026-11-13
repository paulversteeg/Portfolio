<?php
/**
 * Status changes for thoughts (posts) that cannot be made in the admin
 * right now. Runs from deploy.sh and can be run by hand:
 * php bin/apply_post_updates.php. Safe to run again: a post that already
 * has the given status is left alone.
 *
 * Each entry finds a post by slug or title and sets its status. 'draft'
 * takes it offline without deleting it; it stays in the admin. To put a
 * post back online, remove its entry here first, otherwise the next
 * deploy takes it offline again.
 */
require_once dirname(__DIR__) . '/lib/db.php';

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

$postUpdates = [
    [
        // The text got lost in the move to the CMS: offline until it is restored (Nov 2026).
        'slugs'  => ['understanding_the_url'],
        'titles' => ['Understanding the URL'],
        'status' => 'draft',
    ],
];

foreach ($postUpdates as $u) {
    $where = [];
    $args  = [];
    if ($u['slugs'])  { $where[] = 'slug IN (' . implode(',', array_fill(0, count($u['slugs']), '?')) . ')';  $args = array_merge($args, $u['slugs']); }
    if ($u['titles']) { $where[] = 'title IN (' . implode(',', array_fill(0, count($u['titles']), '?')) . ')'; $args = array_merge($args, $u['titles']); }
    $rows = q('SELECT id, title, status FROM posts WHERE ' . implode(' OR ', $where), $args);
    if (!$rows) { echo "post not found, skipped: {$u['titles'][0]}\n"; continue; }
    foreach ($rows as $row) {
        if ($row['status'] === $u['status']) { echo "post already {$u['status']}: {$row['title']}\n"; continue; }
        exec_sql('UPDATE posts SET status = ? WHERE id = ?', [$u['status'], $row['id']]);
        echo "post set to {$u['status']}: {$row['title']}\n";
    }
}

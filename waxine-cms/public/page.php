<?php
/**
 * Static page view (about / talks / publications / ...).
 * Accepts ?slug=about  (via .htaccess rewrite of /about).
 */
require __DIR__ . '/inc/bootstrap.php';

$slug = $_GET['slug'] ?? '';
$page = q1('SELECT * FROM pages WHERE slug = ?', [$slug]);

if (!$page) {
    http_response_code(404);
    $pageTitle = 'Not found';
    include __DIR__ . '/inc/head.php';
    echo '<div class="header"><h1>Not found</h1></div>';
    include __DIR__ . '/inc/foot.php';
    exit;
}

$pageTitle = $page['title'];
include __DIR__ . '/inc/head.php';
?>
<div class="header">
  <h1><?= esc($page['title']) ?><?php if ($page['subtitle'] !== ''): ?><span class="header_subtitle"> <?= esc($page['subtitle']) ?></span><?php endif; ?></h1>
</div>
<div class="content page-<?= esc(preg_replace('/[^a-z0-9-]+/', '-', strtolower($page['slug']))) ?>">
  <?= live_experiences(live_years($page['body_html'])) // trusted: authored HTML; live year counts and the Experience list ?>
</div>
<?php include __DIR__ . '/inc/foot.php'; ?>

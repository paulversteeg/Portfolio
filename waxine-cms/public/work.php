<?php
/**
 * One case on the Work page: /work/<slug> (via .htaccess).
 * The content is an Experience record that has "Show on Work" ticked.
 * Same cover header and title colour as a Thought, so the site reads as one.
 */
require_once __DIR__ . '/inc/bootstrap.php';

$item = experience_by_slug($_GET['slug'] ?? '');

if (!$item) {
    http_response_code(404);
    $pageTitle = 'Not found';
    include __DIR__ . '/inc/head.php';
    echo '<div class="header"><h1>Not found</h1></div>';
    echo '<div class="content"><p>That project isn\'t on the Work page. <a href="/">See all work</a>.</p></div>';
    include __DIR__ . '/inc/foot.php';
    exit;
}

// Previous / next case in the Work order.
$all = experiences_on_work();
$ids = array_column($all, 'id');
$at  = array_search($item['id'], $ids);
$next = $at !== false && isset($all[$at + 1]) ? $all[$at + 1] : null;
$prev = $at !== false && $at > 0 ? $all[$at - 1] : null;

$body    = trim((string) ($item['work_body'] ?? ''));
$summary = experience_summary($item);

$pageTitle = $item['title'];
include __DIR__ . '/inc/head.php';
?>
<div class="header cover angle" style="background-image:<?= esc(title_gradient($item['title'])) ?>">
  <div class="up-one-level"><a href="/" class="first" title="All work">Other work</a></div>
  <h1 class="article-detail-title"><?= esc($item['title']) ?></h1>
  <span class="article-detail-date"><?= esc(trim($item['subtitle'] . ($item['period'] !== '' ? ' · ' . $item['period'] : ''), ' ·')) ?></span>
</div>

<div class="story-colour" style="--title-colour:#<?= esc(title_start_colour($item['title'])) ?>">
<div class="content page-work-case">
  <?php if ($body !== ''): ?>
    <?php if (trim((string) ($item['work_summary'] ?? '')) !== ''): ?><p class="case-summary"><?= nl2br(esc($item['work_summary']), false) ?></p><?php endif; ?>
    <?= live_years(auto_dropcap($body)) // trusted: authored HTML from the CMS ?>
  <?php else: ?>
    <?= auto_dropcap('<p>' . nl2br(esc($summary), false) . '</p>') ?>
  <?php endif; ?>
</div>

<div class="article-nav">
  <?php if ($next): ?>
    <div class="next-article">
      <span class="next-article-label">Next project</span>
      <a rel="next" class="next-article-link" href="<?= esc(experience_url($next)) ?>"><?= esc($next['title']) ?></a>
    </div>
  <?php endif; ?>
  <?php if ($prev): ?>
    <div class="previous-article">
      <span class="previous-article-label">Previous project</span>
      <a rel="prev" class="previous-article-link" href="<?= esc(experience_url($prev)) ?>"><?= esc($prev['title']) ?></a>
    </div>
  <?php endif; ?>
</div>
</div><!-- /.story-colour -->
<script src="/assets/keyboard.js?v=<?= @filemtime(__DIR__ . '/assets/keyboard.js') ?>"></script>
<?php include __DIR__ . '/inc/foot.php'; ?>

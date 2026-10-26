<?php
/**
 * Single "thought" view, with the gradient cover header and prev/next
 * navigation, matching the old article.php + articlefooter.php behaviour.
 *
 * Accepts either  ?slug=my_post   (new URLs, via .htaccess rewrite)
 * or              ?legacy=YYYYMMDD_my_post.php   (old inbound links).
 */
require __DIR__ . '/inc/bootstrap.php';

$slug   = $_GET['slug']   ?? '';
$legacy = $_GET['legacy'] ?? '';

if ($legacy !== '') {
    $post = q1('SELECT * FROM posts WHERE legacy_filename = ? AND status = "published"', [$legacy]);
} else {
    $post = q1('SELECT * FROM posts WHERE slug = ? AND status = "published"', [$slug]);
}

if (!$post) {
    http_response_code(404);
    $pageTitle = 'Not found';
    include __DIR__ . '/inc/head.php';
    echo '<div class="header"><h1>Not found</h1></div>';
    echo '<div class="content"><p>That thought doesn\'t exist. <a href="/">Back to all thoughts</a>.</p></div>';
    include __DIR__ . '/inc/foot.php';
    exit;
}

// Prev / next by publish date.
$next = q1('SELECT title, slug FROM posts WHERE status="published" AND published_at > ? ORDER BY published_at ASC  LIMIT 1', [$post['published_at']]);
$prev = q1('SELECT title, slug FROM posts WHERE status="published" AND published_at < ? ORDER BY published_at DESC LIMIT 1', [$post['published_at']]);

$pageTitle = $post['title'];
include __DIR__ . '/inc/head.php';
?>
<div class="header cover angle" style="background-image:<?= esc(title_gradient($post['title'])) ?>">
  <div class="up-one-level"><a href="/" class="first" title="All articles">Other thoughts</a></div>
  <h1 class="article-detail-title"><?= esc($post['title']) ?></h1>
  <time class="article-detail-date"><?= esc(date("F j, 'y", strtotime($post['published_at']))) ?></time>
</div>

<div class="story-colour" style="--title-colour:#<?= esc(title_start_colour($post['title'])) ?>">
<div class="content">
  <?php if (!empty($post['cover_image'])): ?>
    <p><img class="image-header" src="<?= esc($post['cover_image']) ?>" alt="<?= esc($post['title']) ?>"></p>
  <?php endif; ?>
  <?= live_years(auto_dropcap($post['body_html'])) // trusted: authored/migrated HTML; adds the drop capital ?>
</div>

<div class="article-nav">
  <?php if ($next): ?>
    <div class="next-article">
      <span class="next-article-label">Next article</span>
      <a rel="next" class="next-article-link" href="<?= esc(post_url($next)) ?>"><?= esc($next['title']) ?></a>
    </div>
  <?php endif; ?>
  <?php if ($prev): ?>
    <div class="previous-article">
      <span class="previous-article-label">Previous article</span>
      <a rel="prev" class="previous-article-link" href="<?= esc(post_url($prev)) ?>"><?= esc($prev['title']) ?></a>
    </div>
  <?php endif; ?>
</div>
</div><!-- /.story-colour -->
<script src="/assets/keyboard.js?v=<?= @filemtime(__DIR__ . '/assets/keyboard.js') ?>"></script>
<?php include __DIR__ . '/inc/foot.php'; ?>

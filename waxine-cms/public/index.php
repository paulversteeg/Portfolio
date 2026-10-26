<?php
/**
 * Home - the "Thoughts" listing. Mirrors the old index.php layout,
 * but reads published posts from the database instead of scanning disk.
 */
require __DIR__ . '/inc/bootstrap.php';

$posts = q(
    'SELECT title, slug, excerpt, published_at
       FROM posts
      WHERE status = "published"
      ORDER BY published_at DESC, id DESC'
);

$pageTitle = 'Thoughts';
include __DIR__ . '/inc/head.php';
?>
<div class="header">
  <h1>Thoughts<span class="header_subtitle"> that came to mind</span></h1>
</div>

<ol class="article-listing">
  <?php foreach ($posts as $p): ?>
  <li class="listing-result">
    <a href="<?= esc(post_url($p)) ?>" class="link-figure" title="<?= esc($p['title']) ?>">
      <figure class="article-figure" style="background-image:<?= esc(title_gradient($p['title'])) ?>">
        <time class="article-date"><?= esc(date('F jS, Y', strtotime($p['published_at']))) ?></time>
        <strong class="article-title"><?= esc($p['title']) ?></strong>
      </figure>
    </a>
  </li>
  <?php endforeach; ?>
</ol>

<?php if (!$posts): ?>
  <p style="padding:2rem 0;">No thoughts published yet.</p>
<?php endif; ?>
<?php include __DIR__ . '/inc/foot.php'; ?>

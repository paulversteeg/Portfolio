<?php
require __DIR__ . '/_boot.php';
$me = require_login();

$posts = q('SELECT id, title, slug, status, published_at, updated_at FROM posts ORDER BY published_at DESC, id DESC');

$adminTitle = 'Posts';
include __DIR__ . '/inc/admin_head.php';
?>
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center">
    <h2 style="margin:0">Posts <span class="muted">(<?= count($posts) ?>)</span></h2>
    <a class="btn" href="/admin/edit.php">+ New post</a>
  </div>
</div>

<div class="card">
  <table>
    <thead><tr><th>Title</th><th>Date</th><th>Status</th><th>Slug</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($posts as $p): ?>
      <tr>
        <td><a href="/admin/edit.php?id=<?= (int)$p['id'] ?>"><?= esc($p['title']) ?></a></td>
        <td class="muted"><?= esc(date('Y-m-d', strtotime($p['published_at']))) ?></td>
        <td><span class="pill <?= $p['status']==='published'?'published':'' ?>"><?= esc($p['status']) ?></span></td>
        <td class="muted"><?= esc($p['slug']) ?></td>
        <td><a href="/thoughts/<?= rawurlencode($p['slug']) ?>" target="_blank">view ↗</a></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$posts): ?><tr><td colspan="5" class="muted">No posts yet. Import them, or create a new one.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/inc/admin_foot.php'; ?>

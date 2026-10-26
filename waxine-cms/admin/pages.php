<?php
require __DIR__ . '/_boot.php';
$me = require_login();

$pages = q('SELECT id, title, slug, subtitle, nav_order, in_nav, updated_at FROM pages ORDER BY nav_order, title');

$adminTitle = 'Pages';
include __DIR__ . '/inc/admin_head.php';
?>
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center">
    <h2 style="margin:0">Pages <span class="muted">(<?= count($pages) ?>)</span></h2>
    <a class="btn" href="/admin/edit_page.php">+ New page</a>
  </div>
</div>

<div class="card">
  <table>
    <thead><tr><th>Title</th><th>URL</th><th>In nav</th><th>Order</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($pages as $p): ?>
      <tr>
        <td><a href="/admin/edit_page.php?id=<?= (int)$p['id'] ?>"><?= esc($p['title']) ?></a></td>
        <td class="muted">/<?= esc($p['slug']) ?></td>
        <td><?= $p['in_nav'] ? '<span class="pill published">yes</span>' : '<span class="pill">no</span>' ?></td>
        <td class="muted"><?= (int)$p['nav_order'] ?></td>
        <td><a href="/<?= rawurlencode($p['slug']) ?>" target="_blank">view ↗</a></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$pages): ?><tr><td colspan="5" class="muted">No pages yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/inc/admin_foot.php'; ?>

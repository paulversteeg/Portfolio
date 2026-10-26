<?php
require __DIR__ . '/_boot.php';
$me = require_login();

$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$page = $id ? q1('SELECT * FROM pages WHERE id = ?', [$id]) : null;
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $title    = trim($_POST['title'] ?? '');
    $slug     = trim($_POST['slug'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $body     = $_POST['body_html'] ?? '';
    $nav_order= (int)($_POST['nav_order'] ?? 0);
    $in_nav   = isset($_POST['in_nav']) ? 1 : 0;

    if ($slug === '' && $title !== '') $slug = slugify($title);

    if ($title === '') {
        $flash = 'Title is required.';
    } else {
        // Ensure slug is unique among pages.
        $base = $slug; $n = 1;
        while (true) {
            $clash = q1('SELECT id FROM pages WHERE slug = ? AND id <> ?', [$slug, $id]);
            if (!$clash) break;
            $n++; $slug = $base . '_' . $n;
        }
        if ($id) {
            exec_sql('UPDATE pages SET title=?, slug=?, subtitle=?, body_html=?, nav_order=?, in_nav=? WHERE id=?',
                [$title, $slug, $subtitle, $body, $nav_order, $in_nav, $id]);
        } else {
            exec_sql('INSERT INTO pages (title, slug, subtitle, body_html, nav_order, in_nav) VALUES (?,?,?,?,?,?)',
                [$title, $slug, $subtitle, $body, $nav_order, $in_nav]);
            $id = (int) db()->lastInsertId();
        }
        header('Location: /admin/edit_page.php?id=' . $id . '&saved=1');
        exit;
    }
    // Re-populate on validation error.
    $page = compact('title','slug','subtitle','nav_order','in_nav') + ['id'=>$id, 'body_html'=>$body];
}

if (isset($_GET['saved'])) $flash = 'Saved.';

$v = fn($k, $d='') => esc((string)($page[$k] ?? $d));
$inNav = !isset($page['in_nav']) || $page['in_nav']; // default checked for new pages

$adminTitle = $id ? 'Edit page' : 'New page';
include __DIR__ . '/inc/admin_head.php';
?>
<?php if ($flash): ?><p class="flash"><?= esc($flash) ?></p><?php endif; ?>
<form method="post" class="card">
  <?= csrf_field() ?>
  <label>Title</label>
  <input type="text" name="title" value="<?= $v('title') ?>" required>

  <div class="row">
    <div>
      <label>Slug <span class="muted">(URL: /&lt;slug&gt;)</span></label>
      <input type="text" name="slug" value="<?= $v('slug') ?>" placeholder="auto from title if blank">
    </div>
    <div>
      <label>Subtitle <span class="muted">(shown next to the heading)</span></label>
      <input type="text" name="subtitle" value="<?= $v('subtitle') ?>">
    </div>
  </div>

  <div class="row">
    <div>
      <label>Navigation order</label>
      <input type="number" name="nav_order" value="<?= $v('nav_order','0') ?>">
    </div>
    <div>
      <label>Show in navigation</label>
      <label style="font-weight:normal"><input type="checkbox" name="in_nav" value="1" <?= $inNav ? 'checked' : '' ?>> Include this page in the top menu</label>
    </div>
  </div>

  <label>Body (HTML)</label>
  <textarea name="body_html"><?= esc($page['body_html'] ?? '') ?></textarea>

  <div class="form-actions">
    <?php if ($id): ?>
      <a class="btn danger" href="/admin/delete_page.php?id=<?= (int)$id ?>&_csrf=<?= urlencode(csrf_token()) ?>"
         onclick="return confirm('Delete this page permanently?')">Delete</a>
    <?php endif; ?>
    <div class="form-actions-main">
      <?php if ($id): ?>
        <a class="btn secondary" href="/<?= rawurlencode($page['slug'] ?? '') ?>" target="_blank">Preview ↗</a>
      <?php endif; ?>
      <button class="btn" type="submit">Save</button>
    </div>
  </div>
</form>

<div class="card">
  <p class="muted">Format with the toolbar and insert images directly (drag in or use the image button). For the structured pages (Talks, Publications, Resume) that use custom layout classes, click <strong>&lt;/&gt;</strong> in the toolbar to edit the raw HTML so the special styling is preserved.</p>
</div>
<?php include __DIR__ . '/inc/richtext.php'; ?>
<?php include __DIR__ . '/inc/admin_foot.php'; ?>

<?php
require __DIR__ . '/_boot.php';
$me = require_login();

$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$post = $id ? q1('SELECT * FROM posts WHERE id = ?', [$id]) : null;
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $title  = trim($_POST['title'] ?? '');
    $slug   = trim($_POST['slug'] ?? '');
    $body   = $_POST['body_html'] ?? '';
    $status = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
    $pubRaw = $_POST['published_at'] ?? '';
    $cover  = trim($_POST['cover_image'] ?? '');

    if ($slug === '' && $title !== '') $slug = slugify($title);
    $published_at = $pubRaw !== '' ? date('Y-m-d H:i:s', strtotime($pubRaw)) : date('Y-m-d H:i:s');
    $excerpt = make_excerpt($body);

    if ($title === '') {
        $flash = 'Title is required.';
    } else {
        // Ensure slug is unique (append -2, -3, ...).
        $base = $slug; $n = 1;
        while (true) {
            $clash = q1('SELECT id FROM posts WHERE slug = ? AND id <> ?', [$slug, $id]);
            if (!$clash) break;
            $n++; $slug = $base . '_' . $n;
        }

        if ($id) {
            exec_sql('UPDATE posts SET title=?, slug=?, body_html=?, excerpt=?, cover_image=?, status=?, published_at=? WHERE id=?',
                [$title, $slug, $body, $excerpt, ($cover ?: null), $status, $published_at, $id]);
        } else {
            exec_sql('INSERT INTO posts (title, slug, body_html, excerpt, cover_image, status, author_id, published_at) VALUES (?,?,?,?,?,?,?,?)',
                [$title, $slug, $body, $excerpt, ($cover ?: null), $status, $me['id'], $published_at]);
            $id = (int) db()->lastInsertId();
        }
        header('Location: /admin/edit.php?id=' . $id . '&saved=1');
        exit;
    }
    // Re-populate form on validation error.
    $post = compact('title','slug','body','status','published_at','cover') + ['id'=>$id];
    $post['body_html'] = $body; $post['cover_image'] = $cover;
}

if (isset($_GET['saved'])) $flash = 'Saved.';

$v = fn($k, $d='') => esc((string)($post[$k] ?? $d));
$pubValue = $post['published_at'] ?? date('Y-m-d H:i:s');
$pubValue = date('Y-m-d\TH:i', strtotime($pubValue));

$adminTitle = $id ? 'Edit post' : 'New post';
include __DIR__ . '/inc/admin_head.php';
?>
<?php if ($flash): ?><p class="flash"><?= esc($flash) ?></p><?php endif; ?>
<form method="post" class="card">
  <?= csrf_field() ?>
  <label>Title</label>
  <input type="text" name="title" value="<?= $v('title') ?>" required>

  <div class="row">
    <div>
      <label>Slug <span class="muted">(URL: /thoughts/&lt;slug&gt;)</span></label>
      <input type="text" name="slug" value="<?= $v('slug') ?>" placeholder="auto from title if blank">
    </div>
    <div>
      <label>Published date/time</label>
      <input type="datetime-local" name="published_at" value="<?= esc($pubValue) ?>">
    </div>
  </div>

  <div class="row">
    <div>
      <label>Status</label>
      <select name="status">
        <option value="draft"     <?= ($post['status'] ?? '')==='draft'?'selected':'' ?>>Draft</option>
        <option value="published" <?= ($post['status'] ?? 'published')==='published'?'selected':'' ?>>Published</option>
      </select>
    </div>
    <div>
      <label>Cover image URL <span class="muted">(optional)</span></label>
      <input type="text" name="cover_image" value="<?= $v('cover_image') ?>" placeholder="/images/....jpg">
    </div>
  </div>

  <label>Body (HTML)</label>
  <textarea name="body_html"><?= esc($post['body_html'] ?? '') ?></textarea>

  <div class="form-actions">
    <?php if ($id): ?>
      <a class="btn danger" href="/admin/delete.php?id=<?= (int)$id ?>&_csrf=<?= urlencode(csrf_token()) ?>"
         onclick="return confirm('Delete this post permanently?')">Delete</a>
    <?php endif; ?>
    <div class="form-actions-main">
      <?php if ($id): ?>
        <a class="btn secondary" href="/thoughts/<?= rawurlencode($post['slug'] ?? '') ?>" target="_blank">Preview ↗</a>
      <?php endif; ?>
      <button class="btn" type="submit">Save</button>
    </div>
  </div>
</form>

<div class="card">
  <p class="muted">Use the toolbar to format text and insert images (drag an image in or use the image button — it uploads automatically). Click <strong>&lt;/&gt;</strong> in the toolbar for raw HTML.</p>
</div>
<?php include __DIR__ . '/inc/richtext.php'; ?>
<?php include __DIR__ . '/inc/admin_foot.php'; ?>

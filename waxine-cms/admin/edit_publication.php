<?php
require __DIR__ . '/_boot.php';
$me = require_login();
ensure_publications_table();

$id   = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$item = $id ? q1('SELECT * FROM publications WHERE id = ?', [$id]) : null;
if ($id && !$item) { header('Location: /admin/publications.php'); exit; }
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $f = [];
    foreach (['title', 'kind', 'pub_date', 'summary', 'links', 'publisher'] as $k) {
        $f[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    $errors = [];
    if ($f['title'] === '') $errors[] = 'Title is required.';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['pub_date']) || !strtotime($f['pub_date'])) $errors[] = 'Date is required.';
    foreach (publication_links($f['links']) as $l) {
        if (!preg_match('#^(https?://|/|mailto:)#i', $l['url'])) $errors[] = 'Links need a full address, starting with https:// (or / for a file on this site).';
    }

    if ($errors) {
        $flash = implode(' ', array_unique($errors));
        $item = $f + ['id' => $id];
    } else {
        $args = [$f['title'], $f['kind'], $f['pub_date'], $f['summary'], $f['links'], $f['publisher']];
        if ($id) {
            exec_sql('UPDATE publications SET title=?, kind=?, pub_date=?, summary=?, links=?, publisher=? WHERE id=?', array_merge($args, [$id]));
        } else {
            exec_sql('INSERT INTO publications (title, kind, pub_date, summary, links, publisher) VALUES (?,?,?,?,?,?)', $args);
            $id = (int) db()->lastInsertId();
        }
        header('Location: /admin/edit_publication.php?id=' . $id . '&saved=1');
        exit;
    }
}

if (isset($_GET['saved'])) $flash = 'Saved.';
$v = fn ($k, $d = '') => esc((string) ($item[$k] ?? $d));

$adminTitle = $id ? 'Edit publication' : 'New publication';
include __DIR__ . '/inc/admin_head.php';
?>
<?php if ($flash): ?><p class="flash<?= $flash === 'Saved.' ? '' : ' error' ?>"><?= esc($flash) ?></p><?php endif; ?>
<p><a href="/admin/publications.php">← All publications</a></p>
<form method="post" class="card">
  <?= csrf_field() ?>
  <label for="title">Title</label>
  <input type="text" id="title" name="title" value="<?= $v('title') ?>" required>

  <div class="row">
    <div>
      <label for="kind">Type <span class="muted">(shown before the date)</span></label>
      <input type="text" id="kind" name="kind" value="<?= $v('kind') ?>" placeholder="e.g. Article in Dynamic Design Magazine">
    </div>
    <div>
      <label for="pub_date">Date <span class="muted">(newest first)</span></label>
      <input type="date" id="pub_date" name="pub_date" value="<?= $v('pub_date') ?>" required>
    </div>
  </div>

  <label for="summary">Summary <span class="muted">(an empty line starts a new paragraph)</span></label>
  <textarea id="summary" name="summary" style="min-height:200px;font-family:inherit;font-size:inherit"><?= $v('summary') ?></textarea>

  <label for="links">Links <span class="muted">(one per line: Label | https://… | Dutch, English or pdf — the last part is optional)</span></label>
  <textarea id="links" name="links" style="min-height:90px;font-family:inherit;font-size:inherit" placeholder="Emerce | https://… | Dutch"><?= $v('links') ?></textarea>

  <label for="publisher">Publisher without a link <span class="muted">(optional, e.g. Mirabeau)</span></label>
  <input type="text" id="publisher" name="publisher" value="<?= $v('publisher') ?>">

  <div class="form-actions">
    <?php if ($id): ?>
      <a class="btn danger" href="/admin/delete_publication.php?id=<?= (int) $id ?>&_csrf=<?= urlencode(csrf_token()) ?>"
         onclick="return confirm('Delete this publication permanently?')">Delete</a>
    <?php endif; ?>
    <div class="form-actions-main">
      <?php if ($id): ?>
        <a class="btn secondary" href="/publications" target="_blank">Preview ↗</a>
      <?php endif; ?>
      <button class="btn" type="submit">Save</button>
    </div>
  </div>
</form>
<?php include __DIR__ . '/inc/admin_foot.php'; ?>

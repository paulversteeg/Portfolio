<?php
require __DIR__ . '/_boot.php';
$me = require_login();
ensure_publications_table();

$flash = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'import') {
    csrf_check();
    try {
        $n = import_publications_from_page();
        header('Location: /admin/publications.php?imported=' . $n);
        exit;
    } catch (Throwable $t) {
        $error = 'Import failed, nothing was changed: ' . $t->getMessage();
    }
}

if (isset($_GET['saved']))    $flash = 'Saved.';
if (isset($_GET['deleted']))  $flash = 'Deleted.';
if (isset($_GET['imported'])) $flash = (int) $_GET['imported'] . ' publications imported from the Publications page.';

$items = publications_all();

$page = q1("SELECT body_html FROM pages WHERE slug = 'publications'");
$canImport = $page && stripos($page['body_html'], 'data-publications="all"') === false
    ? count(parse_publication_blocks($page['body_html'])) : 0;

$adminTitle = 'Publications';
include __DIR__ . '/inc/admin_head.php';
?>
<?php if ($flash): ?><p class="flash"><?= esc($flash) ?></p><?php endif; ?>
<?php if ($error): ?><p class="flash error"><?= esc($error) ?></p><?php endif; ?>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
    <h2 style="margin:0">Publications <span class="muted">(<?= count($items) ?>)</span></h2>
    <a class="btn" href="/admin/edit_publication.php">+ New publication</a>
  </div>
  <p class="muted" style="margin:.6rem 0 0">Shown on the Publications page, newest first.</p>
</div>

<?php if ($canImport): ?>
<form method="post" class="card">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="import">
  <h3 style="margin-top:0">Import from the Publications page</h3>
  <p>The Publications page still has <?= (int) $canImport ?> hand-written publications. Import them as separate records<?= $items ? ' (this replaces the ' . count($items) . ' records below)' : '' ?>. The publications on the page are then replaced by the live list from here.</p>
  <button class="btn" type="submit">Import <?= (int) $canImport ?> publications</button>
</form>
<?php endif; ?>

<?php if ($items): ?>
<div class="card">
  <table>
    <thead><tr><th>Date</th><th>Publication</th><th>Type</th></tr></thead>
    <tbody>
    <?php foreach ($items as $p): ?>
      <tr>
        <td class="muted" style="white-space:nowrap"><?= esc(talk_date_label($p['pub_date'])) ?></td>
        <td><a href="/admin/edit_publication.php?id=<?= (int) $p['id'] ?>"><?= esc($p['title']) ?></a></td>
        <td class="muted"><?= esc($p['kind']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <div class="form-actions">
    <div class="form-actions-main">
      <a class="btn secondary" href="/publications" target="_blank">Preview ↗</a>
    </div>
  </div>
</div>
<?php elseif (!$canImport): ?>
<div class="card"><p class="muted" style="margin:0">No publications yet. Add your first one with "+ New publication".</p></div>
<?php endif; ?>
<?php include __DIR__ . '/inc/admin_foot.php'; ?>

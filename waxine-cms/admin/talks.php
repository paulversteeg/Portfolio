<?php
require __DIR__ . '/_boot.php';
$me = require_login();
ensure_talks_table();

$flash = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'import') {
    csrf_check();
    try {
        $n = import_talks_from_page();
        header('Location: /admin/talks.php?imported=' . $n);
        exit;
    } catch (Throwable $t) {
        $error = 'Import failed, nothing was changed: ' . $t->getMessage();
    }
}

if (isset($_GET['saved']))    $flash = 'Saved.';
if (isset($_GET['deleted']))  $flash = 'Deleted.';
if (isset($_GET['imported'])) $flash = (int) $_GET['imported'] . ' talks imported from the Talks page.';

$items = talks_all(true);

// Offer the import while the Talks page still has hand-written talks.
$page = q1("SELECT body_html FROM pages WHERE slug = 'talks'");
$canImport = $page && stripos($page['body_html'], 'data-talks="all"') === false
    ? count(parse_talk_blocks($page['body_html'])) : 0;

$adminTitle = 'Talks';
include __DIR__ . '/inc/admin_head.php';
?>
<?php if ($flash): ?><p class="flash"><?= esc($flash) ?></p><?php endif; ?>
<?php if ($error): ?><p class="flash error"><?= esc($error) ?></p><?php endif; ?>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
    <h2 style="margin:0">Talks <span class="muted">(<?= count($items) ?>)</span></h2>
    <a class="btn" href="/admin/edit_talk.php">+ New talk</a>
  </div>
  <p class="muted" style="margin:.6rem 0 0">Shown on the Talks page, newest first, and as Speaking engagements on the Resume page (the latest <?= SPEAKING_SHOWN ?>, the rest under "Show all speaking engagements").</p>
</div>

<?php if ($canImport): ?>
<form method="post" class="card">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="import">
  <h3 style="margin-top:0">Import from the Talks page</h3>
  <p>The Talks page still has <?= (int) $canImport ?> hand-written talks. Import them as separate records<?= $items ? ' (this replaces the ' . count($items) . ' records below)' : '' ?>. The talks on the page are then replaced by the live list from here.</p>
  <button class="btn" type="submit">Import <?= (int) $canImport ?> talks</button>
</form>
<?php endif; ?>

<?php if ($items): ?>
<div class="card">
  <table>
    <thead><tr><th>Date</th><th>Talk</th><th>Event</th></tr></thead>
    <tbody>
    <?php foreach ($items as $t): ?>
      <tr>
        <td class="muted" style="white-space:nowrap"><?= esc(talk_date_label($t['talk_date'])) ?></td>
        <td><a href="/admin/edit_talk.php?id=<?= (int) $t['id'] ?>"><?= esc($t['title']) ?></a><?php if ($t['image'] !== ''): ?> <span class="muted" title="Has a photo">· photo</span><?php endif; ?><?php if (empty($t['published'])): ?> <span class="muted">· offline</span><?php endif; ?></td>
        <td class="muted"><?= esc($t['event']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <div class="form-actions">
    <div class="form-actions-main">
      <a class="btn secondary" href="/talks" target="_blank">Preview ↗</a>
    </div>
  </div>
</div>
<?php elseif (!$canImport): ?>
<div class="card"><p class="muted" style="margin:0">No talks yet. Add your first one with "+ New talk".</p></div>
<?php endif; ?>
<?php include __DIR__ . '/inc/admin_foot.php'; ?>

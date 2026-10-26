<?php
require __DIR__ . '/_boot.php';
$me = require_login();
ensure_experiences_table();

$max   = EXPERIENCE_MAX_HIGHLIGHTS;
$flash = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'import') {
        // One-time move of the hand-written list on the Resume page into records.
        $page = q1('SELECT id, body_html FROM pages WHERE slug = ?', ['resume']);
        $parsed = $page ? parse_career_highlights($page['body_html']) : null;
        if (experiences_highlight_count() > 0 || q1('SELECT id FROM experiences LIMIT 1')) {
            $error = 'There are already experience records, so nothing was imported.';
        } elseif (!$parsed) {
            $error = 'No Career highlights list was found on the Resume page.';
        } else {
            [$entries, $newBody] = $parsed;
            $pdo = db();
            $pdo->beginTransaction();
            try {
                foreach ($entries as $i => $e) {
                    exec_sql('INSERT INTO experiences (period, title, subtitle, description, sort_order, is_highlight)
                              VALUES (?,?,?,?,?,?)',
                        [$e['period'], $e['title'], $e['subtitle'], $e['description'],
                         ($i + 1) * 10, $i < $max ? 1 : 0]);
                }
                exec_sql('UPDATE pages SET body_html = ? WHERE id = ?', [$newBody, $page['id']]);
                $pdo->commit();
                header('Location: /admin/experiences.php?imported=' . count($entries));
                exit;
            } catch (Throwable $t) {
                $pdo->rollBack();
                $error = 'Import failed, nothing was changed: ' . $t->getMessage();
            }
        }
    }

    if ($action === 'save_list') {
        $picked = array_map('intval', (array) ($_POST['highlight'] ?? []));
        $orders = (array) ($_POST['order'] ?? []);
        if (count($picked) > $max) {
            $error = "You can highlight at most $max. Nothing was saved.";
        } else {
            foreach ($orders as $id => $ord) {
                exec_sql('UPDATE experiences SET sort_order = ?, is_highlight = ? WHERE id = ?',
                    [(int) $ord, in_array((int) $id, $picked, true) ? 1 : 0, (int) $id]);
            }
            header('Location: /admin/experiences.php?saved=1');
            exit;
        }
    }
}

if (isset($_GET['saved']))    $flash = 'Saved.';
if (isset($_GET['imported'])) $flash = (int) $_GET['imported'] . ' entries imported from the Resume page. The first ' . $max . ' are highlighted; change that below.';
if (isset($_GET['deleted']))  $flash = 'Deleted.';

$items = experiences_all();
$highlighted = count(array_filter($items, fn ($e) => (int) $e['is_highlight'] === 1));

// Offer the import only while there are no records yet.
$canImport = false;
if (!$items) {
    $page = q1('SELECT body_html FROM pages WHERE slug = ?', ['resume']);
    $parsed = $page ? parse_career_highlights($page['body_html']) : null;
    $canImport = $parsed ? count($parsed[0]) : false;
}

$adminTitle = 'Experience';
include __DIR__ . '/inc/admin_head.php';
?>
<?php if ($flash): ?><p class="flash"><?= esc($flash) ?></p><?php endif; ?>
<?php if ($error): ?><p class="flash error"><?= esc($error) ?></p><?php endif; ?>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
    <h2 style="margin:0">Experience <span class="muted">(<?= count($items) ?>)</span></h2>
    <a class="btn" href="/admin/edit_experience.php">+ New experience</a>
  </div>
  <p class="muted" style="margin:.6rem 0 0">Tick up to <?= $max ?> to show as Career highlights on the Resume page. The rest appear under "Show all projects". Lower order numbers come first.</p>
</div>

<?php if ($canImport): ?>
<form method="post" class="card">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="import">
  <h3 style="margin-top:0">Import from the Resume page</h3>
  <p>Your Resume page has <?= (int) $canImport ?> entries under Career highlights. Import them as separate records, with the same text and order. The list on the page is then replaced by the live list from here.</p>
  <button class="btn" type="submit">Import <?= (int) $canImport ?> entries</button>
</form>
<?php endif; ?>

<?php if ($items): ?>
<form method="post" class="card" id="experience-list">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save_list">
  <p style="margin-top:0"><strong id="hl-count"><?= $highlighted ?></strong> of <?= $max ?> highlighted</p>
  <table>
    <thead><tr><th>Highlight</th><th>Order</th><th>Project</th><th>Period</th></tr></thead>
    <tbody>
    <?php foreach ($items as $e): $id = (int) $e['id']; ?>
      <tr>
        <td><input type="checkbox" class="hl" name="highlight[]" value="<?= $id ?>" aria-label="Highlight <?= esc($e['title']) ?>" <?= $e['is_highlight'] ? 'checked' : '' ?>></td>
        <td><input type="number" name="order[<?= $id ?>]" value="<?= (int) $e['sort_order'] ?>" style="width:5.5em" aria-label="Order of <?= esc($e['title']) ?>"></td>
        <td><a href="/admin/edit_experience.php?id=<?= $id ?>"><?= esc($e['title']) ?></a><?php if ($e['subtitle'] !== ''): ?><br><span class="muted"><?= esc($e['subtitle']) ?></span><?php endif; ?></td>
        <td class="muted"><?= esc($e['period']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <div class="form-actions">
    <div class="form-actions-main">
      <a class="btn secondary" href="/resume" target="_blank">Preview ↗</a>
      <button class="btn" type="submit">Save</button>
    </div>
  </div>
</form>
<script>
(function () {
  var max = <?= $max ?>;
  var boxes = Array.prototype.slice.call(document.querySelectorAll('#experience-list .hl'));
  var counter = document.getElementById('hl-count');
  function update() {
    var n = boxes.filter(function (b) { return b.checked; }).length;
    counter.textContent = n;
    boxes.forEach(function (b) { b.disabled = !b.checked && n >= max; });
  }
  boxes.forEach(function (b) { b.addEventListener('change', update); });
  update();
})();
</script>
<?php elseif (!$canImport): ?>
<div class="card"><p class="muted" style="margin:0">No experience yet. Add your first one with "+ New experience".</p></div>
<?php endif; ?>
<?php include __DIR__ . '/inc/admin_foot.php'; ?>

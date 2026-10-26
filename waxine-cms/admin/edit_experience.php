<?php
require __DIR__ . '/_boot.php';
$me = require_login();
ensure_experiences_table();

$max  = EXPERIENCE_MAX_HIGHLIGHTS;
$id   = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$item = $id ? q1('SELECT * FROM experiences WHERE id = ?', [$id]) : null;
if ($id && !$item) { header('Location: /admin/experiences.php'); exit; }
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $period      = trim($_POST['period'] ?? '');
    $title       = trim($_POST['title'] ?? '');
    $subtitle    = trim($_POST['subtitle'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $sort_order  = (int) ($_POST['sort_order'] ?? 0);
    $highlight   = isset($_POST['is_highlight']) ? 1 : 0;

    if ($title === '') {
        $flash = 'Project name is required.';
    } elseif ($highlight && experiences_highlight_count($id) >= $max) {
        $flash = "You already have $max highlights. Untick one in the Experience list first.";
    } else {
        if ($id) {
            exec_sql('UPDATE experiences SET period=?, title=?, subtitle=?, description=?, sort_order=?, is_highlight=? WHERE id=?',
                [$period, $title, $subtitle, $description, $sort_order, $highlight, $id]);
        } else {
            exec_sql('INSERT INTO experiences (period, title, subtitle, description, sort_order, is_highlight) VALUES (?,?,?,?,?,?)',
                [$period, $title, $subtitle, $description, $sort_order, $highlight]);
            $id = (int) db()->lastInsertId();
        }
        header('Location: /admin/edit_experience.php?id=' . $id . '&saved=1');
        exit;
    }
    $item = compact('period', 'title', 'subtitle', 'description', 'sort_order') + ['id' => $id, 'is_highlight' => $highlight];
}

if (isset($_GET['saved'])) $flash = 'Saved.';

// New entries go to the top of the list by default.
if (!$item) {
    $first = q1('SELECT MIN(sort_order) AS m FROM experiences');
    $item = ['sort_order' => (int) ($first['m'] ?? 10) - 10, 'is_highlight' => 0];
}

$v = fn ($k, $d = '') => esc((string) ($item[$k] ?? $d));
$isHl   = !empty($item['is_highlight']);
$full   = !$isHl && experiences_highlight_count($id) >= $max;

$adminTitle = $id ? 'Edit experience' : 'New experience';
include __DIR__ . '/inc/admin_head.php';
?>
<?php if ($flash): ?><p class="flash<?= $flash === 'Saved.' ? '' : ' error' ?>"><?= esc($flash) ?></p><?php endif; ?>
<p><a href="/admin/experiences.php">← All experience</a></p>
<form method="post" class="card">
  <?= csrf_field() ?>
  <div class="row">
    <div>
      <label for="title">Project or role</label>
      <input type="text" id="title" name="title" value="<?= $v('title') ?>" required placeholder="e.g. Hertek Safety">
    </div>
    <div>
      <label for="subtitle">Subtitle <span class="muted">(shown after the name)</span></label>
      <input type="text" id="subtitle" name="subtitle" value="<?= $v('subtitle') ?>" placeholder="e.g. Portal">
    </div>
  </div>

  <div class="row">
    <div>
      <label for="period">Period</label>
      <input type="text" id="period" name="period" value="<?= $v('period') ?>" placeholder="e.g. fresk.digital · 2022 – present">
    </div>
    <div>
      <label for="sort_order">Order <span class="muted">(lower comes first)</span></label>
      <input type="number" id="sort_order" name="sort_order" value="<?= $v('sort_order', '0') ?>">
    </div>
  </div>

  <label for="description">Description</label>
  <textarea id="description" name="description" style="min-height:180px;font-family:inherit;font-size:inherit"><?= $v('description') ?></textarea>

  <label style="font-weight:normal;margin-top:1rem">
    <input type="checkbox" name="is_highlight" value="1" <?= $isHl ? 'checked' : '' ?> <?= $full ? 'disabled' : '' ?>>
    Show as a Career highlight on the Resume page
  </label>
  <?php if ($full): ?><p class="muted" style="margin:.2rem 0 0">You already have <?= $max ?> highlights. Untick one in the Experience list to highlight this one.</p><?php endif; ?>

  <div class="form-actions">
    <?php if ($id): ?>
      <a class="btn danger" href="/admin/delete_experience.php?id=<?= (int) $id ?>&_csrf=<?= urlencode(csrf_token()) ?>"
         onclick="return confirm('Delete this experience permanently?')">Delete</a>
    <?php endif; ?>
    <div class="form-actions-main">
      <?php if ($id): ?>
        <a class="btn secondary" href="/resume" target="_blank">Preview ↗</a>
      <?php endif; ?>
      <button class="btn" type="submit">Save</button>
    </div>
  </div>
</form>
<?php include __DIR__ . '/inc/admin_foot.php'; ?>

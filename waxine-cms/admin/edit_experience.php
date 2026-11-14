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
    $employer    = trim($_POST['employer'] ?? '');
    $title       = trim($_POST['title'] ?? '');
    $subtitle    = trim($_POST['subtitle'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $sort_order  = (int) ($_POST['sort_order'] ?? 0);
    $highlight   = isset($_POST['is_highlight']) ? 1 : 0;
    $on_work     = isset($_POST['on_work']) ? 1 : 0;
    $work_summary = trim($_POST['work_summary'] ?? '');
    $work_question = trim($_POST['work_question'] ?? '');
    $work_body   = trim($_POST['body_html'] ?? '');
    $slugIn      = trim($_POST['slug'] ?? '');
    $slug        = $slugIn !== '' ? str_replace('_', '-', slugify($slugIn)) : '';

    if ($title === '') {
        $flash = 'Project name is required.';
    } elseif ($highlight && experiences_highlight_count($id) >= $max) {
        $flash = "You already have $max highlights. Untick one in the Experience list first.";
    } elseif ($slug !== '' && q1('SELECT id FROM experiences WHERE slug = ? AND id <> ?', [$slug, $id])) {
        $flash = 'Another project already uses the web address /work/' . $slug . '.';
    } else {
        // A case on Work needs a web address: make one from the name when empty.
        if ($slug === '' && $on_work) $slug = experience_unique_slug($title, $id);
        $slugDb = $slug === '' ? null : $slug;
        $summaryDb = $work_summary === '' ? null : $work_summary;
        $questionDb = $work_question === '' ? null : mb_substr($work_question, 0, 255);
        $bodyDb = trim(strip_tags($work_body, '<img>')) === '' ? null : $work_body;
        if ($id) {
            exec_sql('UPDATE experiences SET period=?, employer=?, title=?, subtitle=?, slug=?, description=?, work_summary=?, work_question=?, work_body=?, sort_order=?, is_highlight=?, on_work=? WHERE id=?',
                [$period, $employer, $title, $subtitle, $slugDb, $description, $summaryDb, $questionDb, $bodyDb, $sort_order, $highlight, $on_work, $id]);
        } else {
            exec_sql('INSERT INTO experiences (period, employer, title, subtitle, slug, description, work_summary, work_question, work_body, sort_order, is_highlight, on_work) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                [$period, $employer, $title, $subtitle, $slugDb, $description, $summaryDb, $questionDb, $bodyDb, $sort_order, $highlight, $on_work]);
            $id = (int) db()->lastInsertId();
        }
        header('Location: /admin/edit_experience.php?id=' . $id . '&saved=1');
        exit;
    }
    $item = compact('period', 'employer', 'title', 'subtitle', 'slug', 'description', 'work_summary', 'work_question', 'sort_order', 'on_work')
          + ['id' => $id, 'is_highlight' => $highlight, 'work_body' => $work_body];
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
      <input type="text" id="period" name="period" value="<?= $v('period') ?>" placeholder="e.g. 2022 – present">
    </div>
    <div>
      <label for="employer">Employer <span class="muted">(shown under the period)</span></label>
      <input type="text" id="employer" name="employer" value="<?= $v('employer') ?>" placeholder="e.g. fresk.digital">
    </div>
  </div>

  <div class="row">
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

  <h3 style="margin:2rem 0 .4rem">Work page</h3>
  <label style="font-weight:normal">
    <input type="checkbox" name="on_work" value="1" <?= !empty($item['on_work']) ? 'checked' : '' ?>>
    Show on the Work page, with its own case page
  </label>

  <label for="slug">Web address <span class="muted">(/work/…, made from the name when empty)</span></label>
  <input type="text" id="slug" name="slug" value="<?= $v('slug') ?>" placeholder="e.g. hertek-safety">

  <label for="work_question">The question <span class="muted">(shown on the Work page beside the project, e.g. "How does KPN stand out without competing with Apple and Google?"; when empty, the first sentence of the Summary or Description)</span></label>
  <input type="text" id="work_question" name="work_question" maxlength="255" value="<?= $v('work_question') ?>">

  <label for="work_summary">Summary <span class="muted">(the opening of the case page; when empty, the Description is used)</span></label>
  <textarea id="work_summary" name="work_summary" style="min-height:110px;font-family:inherit;font-size:inherit"><?= $v('work_summary') ?></textarea>

  <label for="body_html">Case text <span class="muted">(the full story on the case page: question, approach, your role, result)</span></label>
  <textarea id="body_html" name="body_html"><?= $v('work_body') ?></textarea>

  <div class="form-actions">
    <?php if ($id): ?>
      <a class="btn danger" href="/admin/delete_experience.php?id=<?= (int) $id ?>&_csrf=<?= urlencode(csrf_token()) ?>"
         onclick="return confirm('Delete this experience permanently?')">Delete</a>
    <?php endif; ?>
    <div class="form-actions-main">
      <?php if ($id): ?>
        <a class="btn secondary" href="/resume" target="_blank">Preview ↗</a>
        <?php if (!empty($item['on_work']) && !empty($item['slug'])): ?>
          <a class="btn secondary" href="/work/<?= rawurlencode($item['slug']) ?>" target="_blank">Case page ↗</a>
        <?php endif; ?>
      <?php endif; ?>
      <button class="btn" type="submit">Save</button>
    </div>
  </div>
</form>
<?php include __DIR__ . '/inc/richtext.php'; ?>
<?php include __DIR__ . '/inc/admin_foot.php'; ?>

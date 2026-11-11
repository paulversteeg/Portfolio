<?php
require __DIR__ . '/_boot.php';
$me = require_login();
ensure_talks_table();

$id   = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$item = $id ? q1('SELECT * FROM talks WHERE id = ?', [$id]) : null;
if ($id && !$item) { header('Location: /admin/talks.php'); exit; }
$flash = '';

const TALK_IMAGE_TYPES = ['jpg' => 1, 'jpeg' => 1, 'png' => 1, 'gif' => 1, 'webp' => 1];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $f = [];
    foreach (['title', 'event', 'venue', 'talk_date', 'takeaway', 'description', 'image_alt', 'links'] as $k) {
        $f[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    $f['image']        = $item['image'] ?? '';
    $f['image_width']  = (int) ($item['image_width'] ?? 0);
    $f['image_height'] = (int) ($item['image_height'] ?? 0);

    $errors = [];
    if ($f['title'] === '') $errors[] = 'Title is required.';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['talk_date']) || !strtotime($f['talk_date'])) $errors[] = 'Date is required.';
    foreach (talk_links($f['links']) as $l) {
        if (!preg_match('#^(https?://|/|mailto:)#i', $l['url'])) $errors[] = 'Links need a full address, starting with https://.';
    }

    if (!empty($_POST['remove_image'])) {
        $f['image'] = ''; $f['image_width'] = 0; $f['image_height'] = 0;
    }
    if (!$errors && !empty($_FILES['photo']['tmp_name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $cfg  = config();
        $dir  = $cfg['site']['uploads_dir'];
        $url  = rtrim($cfg['site']['uploads_url'], '/');
        $ext  = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $size = @getimagesize($_FILES['photo']['tmp_name']);
        if (!isset(TALK_IMAGE_TYPES[$ext]) || !$size) {
            $errors[] = 'The photo must be a JPG, PNG, GIF or WebP image.';
        } else {
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            $name = slugify(pathinfo($_FILES['photo']['name'], PATHINFO_FILENAME)) . '_' . substr(bin2hex(random_bytes(4)), 0, 6) . '.' . $ext;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], rtrim($dir, '/') . '/' . $name)) {
                $f['image'] = $url . '/' . $name;
                $f['image_width'] = (int) $size[0];
                $f['image_height'] = (int) $size[1];
            } else {
                $errors[] = 'Could not save the photo (check folder permissions).';
            }
        }
    }

    if ($errors) {
        $flash = implode(' ', array_unique($errors));
        $item = $f + ['id' => $id];
    } else {
        $args = [$f['title'], $f['event'], $f['venue'], $f['talk_date'], $f['takeaway'], $f['description'],
                 $f['image'], $f['image_width'], $f['image_height'], $f['image_alt'], $f['links']];
        if ($id) {
            exec_sql('UPDATE talks SET title=?, event=?, venue=?, talk_date=?, takeaway=?, description=?, image=?, image_width=?, image_height=?, image_alt=?, links=? WHERE id=?',
                array_merge($args, [$id]));
        } else {
            exec_sql('INSERT INTO talks (title, event, venue, talk_date, takeaway, description, image, image_width, image_height, image_alt, links) VALUES (?,?,?,?,?,?,?,?,?,?,?)', $args);
            $id = (int) db()->lastInsertId();
        }
        header('Location: /admin/edit_talk.php?id=' . $id . '&saved=1');
        exit;
    }
}

if (isset($_GET['saved'])) $flash = 'Saved.';
$v = fn ($k, $d = '') => esc((string) ($item[$k] ?? $d));

$adminTitle = $id ? 'Edit talk' : 'New talk';
include __DIR__ . '/inc/admin_head.php';
?>
<?php if ($flash): ?><p class="flash<?= $flash === 'Saved.' ? '' : ' error' ?>"><?= esc($flash) ?></p><?php endif; ?>
<p><a href="/admin/talks.php">← All talks</a></p>
<form method="post" class="card" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <label for="title">Title</label>
  <input type="text" id="title" name="title" value="<?= $v('title') ?>" required placeholder="e.g. Designing for Chaos">

  <div class="row">
    <div>
      <label for="event">Event</label>
      <input type="text" id="event" name="event" value="<?= $v('event') ?>" placeholder="e.g. Enterprise UX Conference 2026">
    </div>
    <div>
      <label for="venue">Venue and city</label>
      <input type="text" id="venue" name="venue" value="<?= $v('venue') ?>" placeholder="e.g. Act Eventstudios Arnhem">
    </div>
  </div>

  <div class="row">
    <div>
      <label for="talk_date">Date <span class="muted">(talks are ordered by this, newest first)</span></label>
      <input type="date" id="talk_date" name="talk_date" value="<?= $v('talk_date') ?>" required>
    </div>
    <div></div>
  </div>

  <label for="takeaway">Key takeaway <span class="muted">(one line, shown large)</span></label>
  <input type="text" id="takeaway" name="takeaway" value="<?= $v('takeaway') ?>" placeholder="e.g. A great experience starts backstage.">

  <label for="description">Description <span class="muted">(an empty line starts a new paragraph)</span></label>
  <textarea id="description" name="description" style="min-height:200px;font-family:inherit;font-size:inherit"><?= $v('description') ?></textarea>

  <label for="links">Links <span class="muted">(one per line: Label | https://…)</span></label>
  <textarea id="links" name="links" style="min-height:80px;font-family:inherit;font-size:inherit" placeholder="Event report | https://…"><?= $v('links') ?></textarea>

  <fieldset style="border:1px solid #ddd;border-radius:6px;padding:1rem;margin-top:1rem">
    <legend>Photo</legend>
    <?php if (($item['image'] ?? '') !== ''): ?>
      <p style="margin-top:0"><img src="<?= $v('image') ?>" alt="" style="max-width:240px;height:auto;border-radius:4px;display:block"></p>
      <label style="font-weight:normal"><input type="checkbox" name="remove_image" value="1"> Remove this photo</label>
    <?php endif; ?>
    <label for="photo"><?= ($item['image'] ?? '') !== '' ? 'Replace with' : 'Upload' ?> <span class="muted">(JPG, PNG, GIF or WebP)</span></label>
    <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/gif,image/webp">
    <label for="image_alt">Description of the photo <span class="muted">(for screen readers)</span></label>
    <input type="text" id="image_alt" name="image_alt" value="<?= $v('image_alt') ?>" placeholder="e.g. Paul presenting to a full room">
  </fieldset>

  <div class="form-actions">
    <?php if ($id): ?>
      <a class="btn danger" href="/admin/delete_talk.php?id=<?= (int) $id ?>&_csrf=<?= urlencode(csrf_token()) ?>"
         onclick="return confirm('Delete this talk permanently?')">Delete</a>
    <?php endif; ?>
    <div class="form-actions-main">
      <?php if ($id): ?>
        <a class="btn secondary" href="/talks" target="_blank">Preview ↗</a>
      <?php endif; ?>
      <button class="btn" type="submit">Save</button>
    </div>
  </div>
</form>
<?php include __DIR__ . '/inc/admin_foot.php'; ?>

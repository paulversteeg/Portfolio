<?php
require __DIR__ . '/_boot.php';
$me = require_login();

$cfg = config();
$dir = $cfg['site']['uploads_dir'];
$url = rtrim($cfg['site']['uploads_url'], '/');
$msg = ''; $ok = false; $publicUrl = '';

const ALLOWED = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif','webp'=>'image/webp','svg'=>'image/svg+xml'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (empty($_FILES['image']['tmp_name']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $msg = 'Upload failed (check file size limits in php.ini).';
    } else {
        $orig = $_FILES['image']['name'];
        $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if (!isset(ALLOWED[$ext])) {
            $msg = 'Only images are allowed: ' . implode(', ', array_keys(ALLOWED));
        } else {
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            $safe = slugify(pathinfo($orig, PATHINFO_FILENAME)) . '_' . substr(bin2hex(random_bytes(4)),0,6) . '.' . $ext;
            $dest = rtrim($dir,'/') . '/' . $safe;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                $ok = true; $publicUrl = $url . '/' . $safe;
                $msg = 'Uploaded.';
            } else {
                $msg = 'Could not save the file (check folder permissions on ' . esc($dir) . ').';
            }
        }
    }
}

$adminTitle = 'Upload image';
include __DIR__ . '/inc/admin_head.php';
?>
<div class="card" style="max-width:560px">
  <h2 style="margin-top:0">Upload image</h2>
  <?php if ($msg): ?><p class="flash" <?= $ok?'':'style="background:#f6d9d9;border-color:#e0b4b4"' ?>><?= esc($msg) ?></p><?php endif; ?>
  <?php if ($ok): ?>
    <p>URL to paste into a post body:</p>
    <input type="text" readonly value="<?= esc($publicUrl) ?>" onclick="this.select()">
    <p><img src="<?= esc($publicUrl) ?>" alt="" style="max-width:100%;border-radius:8px;margin-top:.6rem"></p>
  <?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <label>Choose an image</label>
    <input type="file" name="image" accept="image/*" required>
    <p style="margin-top:1rem"><button class="btn" type="submit">Upload</button>
       <a class="btn secondary" href="/admin/">Back</a></p>
  </form>
</div>
<?php include __DIR__ . '/inc/admin_foot.php'; ?>

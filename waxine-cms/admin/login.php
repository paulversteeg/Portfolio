<?php
require __DIR__ . '/_boot.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (attempt_login($_POST['username'] ?? '', $_POST['password'] ?? '')) {
        header('Location: /admin/');
        exit;
    }
    $error = 'Wrong username or password.';
}

if (current_user()) { header('Location: /admin/'); exit; }
$adminTitle = 'Log in';
include __DIR__ . '/inc/admin_head.php';
?>
<div class="card" style="max-width:380px;margin:3rem auto">
  <h2 style="margin-top:0">Log in</h2>
  <?php if ($error): ?><p class="flash" style="background:#f6d9d9;border-color:#e0b4b4"><?= esc($error) ?></p><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>Username</label>
    <input type="text" name="username" autofocus required>
    <label>Password</label>
    <input type="password" name="password" required>
    <p style="margin-top:1.2rem"><button class="btn" type="submit">Log in</button></p>
  </form>
</div>
<?php include __DIR__ . '/inc/admin_foot.php'; ?>

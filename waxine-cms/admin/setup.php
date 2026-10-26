<?php
/**
 * ONE-TIME admin setup (no CLI needed).
 * Creates the first admin user through the browser, then refuses to run
 * again once a user exists. DELETE this file after you've logged in.
 */
require __DIR__ . '/_boot.php';

$existing = (int) (q1('SELECT COUNT(*) AS n FROM users')['n'] ?? 0);
$done = false;
$error = '';

if ($existing === 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $u    = trim($_POST['username'] ?? '');
    $name = trim($_POST['display_name'] ?? '');
    $p    = $_POST['password'] ?? '';
    $p2   = $_POST['password2'] ?? '';
    if ($u === '' || $p === '') {
        $error = 'Username and password are required.';
    } elseif ($p !== $p2) {
        $error = 'The two passwords do not match.';
    } elseif (strlen($p) < 8) {
        $error = 'Use a password of at least 8 characters.';
    } else {
        $hash = password_hash($p, PASSWORD_DEFAULT);
        exec_sql('INSERT INTO users (username, password_hash, display_name) VALUES (?,?,?)',
                 [$u, $hash, ($name !== '' ? $name : $u)]);
        $done = true;
    }
}
?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Waxine admin setup</title>
<style>
  body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#f4f1ef;color:#25282a;margin:0}
  .box{max-width:420px;margin:4rem auto;background:#fff;border:1px solid #e2ddd7;border-radius:12px;padding:1.6rem}
  h1{margin:.2rem 0 1rem;font-size:1.3rem}
  label{display:block;font-weight:600;margin:.9rem 0 .3rem}
  input{width:100%;padding:.6rem .7rem;border:1px solid #e2ddd7;border-radius:8px;font:inherit;box-sizing:border-box}
  .btn{margin-top:1.2rem;background:#3a6ea5;color:#fff;border:0;border-radius:8px;padding:.7rem 1.1rem;font:inherit;cursor:pointer}
  .msg{padding:.7rem 1rem;border-radius:8px;margin-bottom:1rem}
  .err{background:#f6d9d9;border:1px solid #e0b4b4}
  .ok{background:#d9ecd9;border:1px solid #b6d6b6}
  code{background:#eee;padding:.1rem .3rem;border-radius:4px}
</style></head><body>
<div class="box">
<h1>Waxine — admin setup</h1>
<?php if ($existing > 0 && !$done): ?>
  <div class="msg ok">An admin account already exists, so setup is closed.</div>
  <p><strong>Please delete this file</strong> (<code>admin/setup.php</code>) now, then log in at
     <a href="/admin/">/admin/</a>.</p>
<?php elseif ($done): ?>
  <div class="msg ok">Admin account created.</div>
  <p><strong>Important:</strong> delete this file (<code>admin/setup.php</code>) now — it's a security risk if left in place.</p>
  <p>Then log in at <a href="/admin/">/admin/</a>.</p>
<?php else: ?>
  <?php if ($error): ?><div class="msg err"><?= esc($error) ?></div><?php endif; ?>
  <p>Create your admin login. This page stops working once an account exists.</p>
  <form method="post">
    <?= csrf_field() ?>
    <label>Username</label>
    <input type="text" name="username" autofocus required>
    <label>Display name</label>
    <input type="text" name="display_name" placeholder="Paul Versteeg">
    <label>Password (min 8 characters)</label>
    <input type="password" name="password" required>
    <label>Confirm password</label>
    <input type="password" name="password2" required>
    <button class="btn" type="submit">Create admin account</button>
  </form>
<?php endif; ?>
</div>
</body></html>

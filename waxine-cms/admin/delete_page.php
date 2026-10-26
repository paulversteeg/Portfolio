<?php
require __DIR__ . '/_boot.php';
$me = require_login();

auth_boot();
if (!hash_equals($_SESSION['csrf'] ?? '', $_GET['_csrf'] ?? '')) {
    http_response_code(400);
    exit('Bad token.');
}

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    exec_sql('DELETE FROM pages WHERE id = ?', [$id]);
}
header('Location: /admin/pages.php');

<?php
/**
 * JSON image-upload endpoint for the rich-text editor (TinyMCE).
 * Requires an authenticated admin session. Returns {"location":"/images/.."}.
 */
require __DIR__ . '/_boot.php';
$me = require_login();          // 302 to login if not authenticated

header('Content-Type: application/json');

$cfg = config();
$dir = $cfg['site']['uploads_dir'];
$url = rtrim($cfg['site']['uploads_url'], '/');

const ALLOWED = ['jpg'=>1,'jpeg'=>1,'png'=>1,'gif'=>1,'webp'=>1,'svg'=>1];

function fail($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['error' => ['message' => $msg]]);
    exit;
}

if (empty($_FILES['file']['tmp_name']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    fail('No file received or upload error.');
}
$orig = $_FILES['file']['name'];
$ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
if (!isset(ALLOWED[$ext])) {
    fail('Unsupported file type.');
}
if (!is_dir($dir)) @mkdir($dir, 0775, true);
$safe = slugify(pathinfo($orig, PATHINFO_FILENAME)) . '_' . substr(bin2hex(random_bytes(4)), 0, 6) . '.' . $ext;
$dest = rtrim($dir, '/') . '/' . $safe;

if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
    fail('Could not save the file (check folder permissions).', 500);
}
echo json_encode(['location' => $url . '/' . $safe]);

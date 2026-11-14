<?php
/**
 * Webartrose endpoint (see lib/wear.php). JSON in, JSON out.
 *
 *   POST {"op":"read","page":"/talks","keys":["a /talks", ...]}
 *        -> {"elements":{key:[touch,press,seen,idleDays]}, "trail":[[cx,cy,n]], "since":"..."}
 *   POST {"op":"hit","page":"/talks","events":[[key,t,p,s]], "trail":[[cx,cy,n]]}
 *        -> {"ok":true}   (sent with navigator.sendBeacon when a page is left)
 */
require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/wear.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

function wear_out(int $code, array $body): void
{
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') wear_out(405, ['error' => 'POST only']);

// Only from this site's own pages.
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$host   = $_SERVER['HTTP_HOST'] ?? '';
if ($origin !== '' && parse_url($origin, PHP_URL_HOST) !== preg_replace('~:\d+$~', '', $host)) {
    wear_out(403, ['error' => 'other origin']);
}

$raw = file_get_contents('php://input', false, null, 0, 65536);
$in  = json_decode($raw ?: '', true);
if (!is_array($in)) wear_out(400, ['error' => 'bad json']);

$page = wear_page((string) ($in['page'] ?? ''));
if ($page === null) wear_out(400, ['error' => 'bad page']);

try {
    if (($in['op'] ?? '') === 'read') {
        wear_out(200, wear_read($page, is_array($in['keys'] ?? null) ? $in['keys'] : []));
    }
    if (($in['op'] ?? '') === 'hit') {
        if (wear_is_bot($_SERVER['HTTP_USER_AGENT'] ?? '')) wear_out(200, ['ok' => true, 'counted' => 0]);
        $n = wear_record(
            $page,
            is_array($in['events'] ?? null) ? $in['events'] : [],
            is_array($in['trail'] ?? null) ? $in['trail'] : []
        );
        wear_out(200, ['ok' => true, 'counted' => $n]);
    }
} catch (Throwable $e) {
    error_log('wear.php: ' . $e->getMessage());
    wear_out(500, ['error' => 'not available']);   // the site works the same without it
}
wear_out(400, ['error' => 'unknown op']);

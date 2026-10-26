<?php
/**
 * PDO connection + tiny query helpers.
 * Usage:  $db = db();  $rows = q('SELECT * FROM posts WHERE id = ?', [$id]);
 */

function config(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $path = dirname(__DIR__) . '/config.php';
        if (!is_file($path)) {
            http_response_code(500);
            exit('Missing config.php — copy config.sample.php to config.php and fill it in.');
        }
        $cfg = require $path;
        date_default_timezone_set($cfg['site']['timezone'] ?? 'UTC');
    }
    return $cfg;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = config()['db'];
        $dsn = "mysql:host={$c['host']};dbname={$c['name']};charset={$c['charset']}";
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

/** True on the staging copy of the site (config: site.env = 'staging'). */
function is_staging(): bool
{
    return (config()['site']['env'] ?? 'live') === 'staging';
}

/** The fixed bar at the top of every staging page, public and admin. */
function staging_banner(): string
{
    if (!is_staging()) return '';
    return '<div role="note" style="position:sticky;top:0;z-index:1000;margin:0;padding:.55rem 1rem;'
         . 'background:#8a3b12;color:#fff;font:600 15px/1.4 -apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;text-align:center">'
         . 'Staging: changes to content here are overwritten every night by a copy of the live site. '
         . 'Edit content on <a href="https://waxine.nl/admin/" style="color:#fff;text-decoration:underline">waxine.nl</a>.'
         . '</div>';
}

/** Run a query, return all rows. */
function q(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/** Run a query, return the first row or null. */
function q1(string $sql, array $params = []): ?array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();
    return $row === false ? null : $row;
}

/** Run an INSERT/UPDATE/DELETE, return affected row count. */
function exec_sql(string $sql, array $params = []): int
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->rowCount();
}

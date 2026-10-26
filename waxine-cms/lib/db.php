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

<?php
/**
 * Copy the live database into the staging database.
 *
 * Runs every night as a Plesk scheduled task on staging-cms/bin/, and can
 * be run by hand ("Run Now") to test with today's content. Live is only
 * read; everything in the staging database is replaced.
 *
 * Safety: it only runs from a staging install, and refuses when the two
 * configurations point at the same database.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$staging = require __DIR__ . '/../config.php';
if (($staging['site']['env'] ?? '') !== 'staging') {
    fwrite(STDERR, "Not a staging install; nothing done.\n");
    exit(1);
}
$live = require dirname(__DIR__, 2) . '/waxine-cms/config.php';
if (($live['site']['env'] ?? 'live') === 'staging'
    || ($live['db']['host'] === $staging['db']['host'] && $live['db']['name'] === $staging['db']['name'])) {
    fwrite(STDERR, "Live and staging point at the same database; nothing done.\n");
    exit(1);
}

$connect = function (array $c): PDO {
    return new PDO(
        "mysql:host={$c['host']};dbname={$c['name']};charset={$c['charset']}",
        $c['user'], $c['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
};
$from = $connect($live['db']);
$to   = $connect($staging['db']);
$q    = fn (PDO $db, string $name) => '`' . str_replace('`', '``', $name) . '`';

$tables = $from->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
$tables = array_column($tables, 0);

$to->exec('SET FOREIGN_KEY_CHECKS = 0');

// Start from an empty staging database, so tables removed on live go too.
foreach (array_column($to->query("SHOW FULL TABLES")->fetchAll(PDO::FETCH_NUM), 1, 0) as $name => $type) {
    $to->exec(($type === 'VIEW' ? 'DROP VIEW ' : 'DROP TABLE ') . $q($to, $name));
}

$total = 0;
foreach ($tables as $table) {
    $create = $from->query('SHOW CREATE TABLE ' . $q($from, $table))->fetch(PDO::FETCH_NUM)[1];
    $to->exec($create);

    $rows = $from->query('SELECT * FROM ' . $q($from, $table));
    $batch = [];
    $count = 0;
    $flush = function () use (&$batch, $to, $table, $q) {
        if (!$batch) return;
        $cols = array_keys($batch[0]);
        $place = '(' . implode(',', array_fill(0, count($cols), '?')) . ')';
        $sql = 'INSERT INTO ' . $q($to, $table) . ' (' . implode(',', array_map(fn ($c) => $q($to, $c), $cols)) . ') VALUES '
             . implode(',', array_fill(0, count($batch), $place));
        $values = [];
        foreach ($batch as $r) foreach ($r as $v) $values[] = $v;
        $to->prepare($sql)->execute($values);
        $batch = [];
    };
    while ($row = $rows->fetch()) {
        $batch[] = $row;
        $count++;
        if (count($batch) >= 200) $flush();
    }
    $flush();
    $total += $count;
    echo str_pad($table, 24) . " $count rows\n";
}

$to->exec('SET FOREIGN_KEY_CHECKS = 1');
echo 'Staging refreshed from live: ' . count($tables) . " tables, $total rows, " . date('Y-m-d H:i') . "\n";

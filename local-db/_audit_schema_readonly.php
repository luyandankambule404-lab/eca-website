<?php
/**
 * READ-ONLY schema inventory for eca_local / eca_portal_local.
 * SELECT / SHOW / information_schema only — no writes.
 */
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__);
require_once $root . '/v1/includes/env.php';
eca_load_env_file($root . '/.env');

function db(string $host, string $user, string $pass, string $name): PDO
{
    return new PDO(
        'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4',
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8]
    );
}

function inventory(PDO $pdo, string $dbName): array
{
    $tables = $pdo->query(
        "SELECT TABLE_NAME, ENGINE, TABLE_ROWS, TABLE_COLLATION, CREATE_TIME, UPDATE_TIME
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = " . $pdo->quote($dbName) . "
           AND TABLE_TYPE = 'BASE TABLE'
         ORDER BY TABLE_NAME"
    )->fetchAll(PDO::FETCH_ASSOC);

    $out = ['database' => $dbName, 'tables' => []];

    foreach ($tables as $t) {
        $name = $t['TABLE_NAME'];
        $cols = $pdo->query(
            "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, COLUMN_DEFAULT, EXTRA, COLUMN_COMMENT
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = " . $pdo->quote($dbName) . "
               AND TABLE_NAME = " . $pdo->quote($name) . "
             ORDER BY ORDINAL_POSITION"
        )->fetchAll(PDO::FETCH_ASSOC);

        $indexes = $pdo->query(
            "SELECT INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME, INDEX_TYPE
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = " . $pdo->quote($dbName) . "
               AND TABLE_NAME = " . $pdo->quote($name) . "
             ORDER BY INDEX_NAME, SEQ_IN_INDEX"
        )->fetchAll(PDO::FETCH_ASSOC);

        $fks = $pdo->query(
            "SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = " . $pdo->quote($dbName) . "
               AND TABLE_NAME = " . $pdo->quote($name) . "
               AND REFERENCED_TABLE_NAME IS NOT NULL
             ORDER BY CONSTRAINT_NAME, ORDINAL_POSITION"
        )->fetchAll(PDO::FETCH_ASSOC);

        // Exact count (read-only)
        $exact = (int) $pdo->query('SELECT COUNT(*) FROM `' . str_replace('`', '``', $name) . '`')->fetchColumn();

        $idxGrouped = [];
        foreach ($indexes as $ix) {
            $idxGrouped[$ix['INDEX_NAME']]['unique'] = ((int) $ix['NON_UNIQUE'] === 0);
            $idxGrouped[$ix['INDEX_NAME']]['type'] = $ix['INDEX_TYPE'];
            $idxGrouped[$ix['INDEX_NAME']]['columns'][] = $ix['COLUMN_NAME'];
        }
        $idxList = [];
        foreach ($idxGrouped as $iname => $meta) {
            $idxList[] = [
                'name' => $iname,
                'unique' => $meta['unique'],
                'type' => $meta['type'],
                'columns' => $meta['columns'],
            ];
        }

        $out['tables'][$name] = [
            'engine' => $t['ENGINE'],
            'approx_rows_is' => (int) $t['TABLE_ROWS'],
            'exact_rows' => $exact,
            'collation' => $t['TABLE_COLLATION'],
            'columns' => $cols,
            'indexes' => $idxList,
            'foreign_keys' => $fks,
        ];
    }

    return $out;
}

$host = eca_env('ECA_DB_HOST', '127.0.0.1');
$user = eca_env('ECA_DB_USER', 'root');
$pass = eca_env('ECA_DB_PASS', '');
$hubName = eca_env('ECA_DB_NAME', 'eca_local');
$portalHost = eca_env('ECA_PORTAL_DB_HOST', $host);
$portalUser = eca_env('ECA_PORTAL_DB_USER', $user);
$portalPass = eca_env('ECA_PORTAL_DB_PASS', $pass);
$portalName = eca_env('ECA_PORTAL_DB_NAME', 'eca_portal_local');

echo "Connecting (user={$user}, pass_set=" . ($pass !== '' ? 'yes' : 'no') . ")...\n";

try {
    $hub = db($host, $user, $pass, $hubName);
    $portal = db($portalHost, $portalUser, $portalPass, $portalName);
} catch (Throwable $e) {
    fwrite(STDERR, "CONNECT_FAIL: " . $e->getMessage() . "\n");
    exit(2);
}

$result = [
    'generated_at' => date('c'),
    'connection' => [
        'hub' => ['host' => $host, 'db' => $hubName, 'user' => $user],
        'portal' => ['host' => $portalHost, 'db' => $portalName, 'user' => $portalUser],
    ],
    'hub' => inventory($hub, $hubName),
    'portal' => inventory($portal, $portalName),
];

$outPath = __DIR__ . '/_audit_schema_inventory.json';
file_put_contents($outPath, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "WROTE {$outPath}\n";
echo "HUB_TABLES=" . count($result['hub']['tables']) . "\n";
echo "PORTAL_TABLES=" . count($result['portal']['tables']) . "\n";
foreach (['hub', 'portal'] as $k) {
    echo strtoupper($k) . ":\n";
    foreach ($result[$k]['tables'] as $tname => $meta) {
        echo "  {$tname}\t{$meta['exact_rows']}\n";
    }
}

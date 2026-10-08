<?php
/**
 * LOCAL backup restore test into differently named databases only.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/includes/env.php';

$host = strtolower(trim(eca_env('ECA_DB_HOST', '')));
$name = strtolower(trim(eca_env('ECA_DB_NAME', '')));
$portalName = strtolower(trim(eca_env('ECA_PORTAL_DB_NAME', '')));
if ($name !== 'eca_local' || $portalName !== 'eca_portal_local' || !in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
    fwrite(STDERR, "STOP: environment is not local.\n");
    exit(2);
}

$mysql = 'C:\\xampp\\mysql\\bin\\mysql.exe';
if (!is_file($mysql)) {
    fwrite(STDERR, "mysql.exe not found.\n");
    exit(1);
}

$pairs = [
    [
        'src' => 'D:\\Website\\_backups\\eca_local_20260922-125327.sql',
        'from' => 'eca_local',
        'to' => 'eca_local_phasef_restore',
    ],
    [
        'src' => 'D:\\Website\\_backups\\eca_portal_local_20260922-125327.sql',
        'from' => 'eca_portal_local',
        'to' => 'eca_portal_local_phasef_restore',
    ],
];

function eca_phasef_mysql(string $mysql, string $sql): string
{
    $cmd = '"' . $mysql . '" -h 127.0.0.1 -u root --batch --raw -e ' . escapeshellarg($sql);
    $out = [];
    $code = 0;
    exec($cmd, $out, $code);
    if ($code !== 0) {
        throw new RuntimeException('mysql failed: ' . implode("\n", $out));
    }
    return implode("\n", $out);
}

$ok = true;
foreach ($pairs as $pair) {
    if (!is_file($pair['src'])) {
        fwrite(STDERR, "Missing backup {$pair['src']}\n");
        $ok = false;
        continue;
    }
    $sql = file_get_contents($pair['src']);
    if ($sql === false || strlen($sql) < 100) {
        fwrite(STDERR, "Backup unreadable {$pair['src']}\n");
        $ok = false;
        continue;
    }
    $rewritten = str_replace(
        ['`' . $pair['from'] . '`', 'Database: ' . $pair['from']],
        ['`' . $pair['to'] . '`', 'Database: ' . $pair['to']],
        $sql
    );
    if (str_contains($rewritten, '`' . $pair['from'] . '`')) {
        fwrite(STDERR, "Rewrite left original database name in {$pair['from']}\n");
        $ok = false;
        continue;
    }
    $tmp = $pair['src'] . '.phasef-restore.sql';
    file_put_contents($tmp, $rewritten);
    try {
        eca_phasef_mysql($mysql, 'DROP DATABASE IF EXISTS `' . $pair['to'] . '`');
        $import = 'cmd /c ""' . $mysql . '" -h 127.0.0.1 -u root < "' . $tmp . '""';
        $out = [];
        $code = 0;
        exec($import, $out, $code);
        if ($code !== 0) {
            throw new RuntimeException('import failed ' . $pair['to'] . ': ' . implode("\n", $out));
        }
        $tables = eca_phasef_mysql($mysql, 'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = \'' . $pair['to'] . '\'');
        $count = (int) trim(preg_replace('/[^0-9]/', '', $tables));
        echo "RESTORE_OK {$pair['to']} tables={$count} bytes=" . filesize($pair['src']) . "\n";
        if ($count < 5) {
            $ok = false;
            echo "RESTORE_FAIL {$pair['to']} too few tables\n";
        }
        eca_phasef_mysql($mysql, 'DROP DATABASE IF EXISTS `' . $pair['to'] . '`');
        echo "RESTORE_DROPPED {$pair['to']}\n";
    } catch (Throwable $e) {
        $ok = false;
        echo "RESTORE_FAIL {$pair['to']} " . $e->getMessage() . "\n";
        try {
            eca_phasef_mysql($mysql, 'DROP DATABASE IF EXISTS `' . $pair['to'] . '`');
        } catch (Throwable $ignore) {
        }
    }
    @unlink($tmp);
}

$stillLocal = eca_phasef_mysql($mysql, 'SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME IN (\'eca_local\',\'eca_portal_local\')');
echo "LOCAL_DBS_PRESENT\n{$stillLocal}\n";
exit($ok ? 0 : 1);

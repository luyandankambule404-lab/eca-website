<?php
/**
 * Local-only check. Does not open a production connection.
 */
require_once __DIR__ . '/../includes/portal-db.php';

$failed = 0;
$check = static function (bool $ok, string $label) use (&$failed): void {
    echo ($ok ? 'OK  ' : 'FAIL ') . $label . PHP_EOL;
    if (!$ok) {
        $failed++;
    }
};

$check(eca_app_env() === 'local' || eca_app_env() === 'live_readonly', 'APP_ENV is local or live_readonly');
$check(eca_sql_is_read_only('SELECT 1'), 'SELECT is allowed');
$check(eca_sql_is_read_only('SHOW TABLES'), 'SHOW is allowed');
$check(eca_sql_is_read_only('set names utf8mb4'), 'SET NAMES is allowed');
$check(!eca_sql_is_read_only('INSERT INTO t (id) VALUES (1)'), 'INSERT is blocked');
$check(!eca_sql_is_read_only('UPDATE t SET a = 1'), 'UPDATE is blocked');
$check(!eca_sql_is_read_only('DELETE FROM t'), 'DELETE is blocked');
$check(!eca_sql_is_read_only('CREATE TABLE t (id INT)'), 'CREATE is blocked');
$check(!eca_sql_is_read_only('DROP TABLE t'), 'DROP is blocked');
$check(!eca_sql_is_read_only('ALTER TABLE t ADD c INT'), 'ALTER is blocked');
$check(!eca_sql_is_read_only('TRUNCATE TABLE t'), 'TRUNCATE is blocked');
$check(!eca_sql_is_read_only("SELECT * FROM t INTO OUTFILE '/tmp/x'"), 'SELECT INTO OUTFILE is blocked');

$local = eca_local_portal_pdo(false);
$check($local instanceof PDO, 'Local portal connection opens');
if ($local instanceof PDO) {
    $check((int) $local->query('SELECT 1')->fetchColumn() === 1, 'Local SELECT 1 works');
    $check(!$local instanceof EcaReadOnlyPdo, 'Local portal connection is not the read-only wrapper');
    try {
        $local->exec('CREATE TEMPORARY TABLE eca_mode_probe (id INT)');
        $local->exec('INSERT INTO eca_mode_probe (id) VALUES (1)');
        $count = (int) $local->query('SELECT COUNT(*) FROM eca_mode_probe')->fetchColumn();
        $local->exec('DROP TEMPORARY TABLE eca_mode_probe');
        $check($count === 1, 'Local write still works on a temporary table');
    } catch (Throwable $e) {
        $check(false, 'Local write still works on a temporary table');
    }
}

if (eca_is_live_readonly()) {
    echo "APP_ENV is live_readonly. This script does not send write SQL to production." . PHP_EOL;
    $check(eca_live_readonly_auth_request() === false, 'CLI is not treated as an auth request');
} else {
    $check(!eca_is_live_readonly(), 'Write guard stays off while APP_ENV=local');
}

exit($failed > 0 ? 1 : 0);

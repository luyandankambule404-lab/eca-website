<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/portal-db.php';

eca_admin_require('security.view');

function eca_db_mode_safe_failure(Throwable $e): string
{
    $code = (string) $e->getCode();
    return match ($code) {
        '2002', '2003' => 'The production host could not be reached.',
        '1045' => 'The read-only account was rejected.',
        '1044', '1049' => 'The production database name was not found for this account.',
        default => 'The production read-only database is not available.',
    };
}

function eca_db_mode_privileges(PDO $pdo): array
{
    $words = [];
    $rows = $pdo->query('SHOW GRANTS')->fetchAll(PDO::FETCH_NUM);
    foreach ($rows as $row) {
        $grant = (string) ($row[0] ?? '');
        if (preg_match('/GRANT\s+(.+?)\s+ON\b/i', $grant, $match)) {
            foreach (preg_split('/\s*,\s*/', strtoupper($match[1])) ?: [] as $word) {
                $word = trim($word);
                if ($word !== '') {
                    $words[$word] = true;
                }
            }
        }
    }
    return array_keys($words);
}

$status = eca_db_mode_status();
$tables = [];
$connection = 'NOT ATTEMPTED';
$select = 'NOT AVAILABLE';
$writes = 'UNKNOWN';
$note = '';

if ($status['env'] !== 'live_readonly') {
    $note = 'The production database was not contacted.';
} elseif (!$status['ready']) {
    $note = $status['reason'] !== '' ? $status['reason'] : 'Production read-only credentials are incomplete.';
} else {
    try {
        $live = eca_portal_pdo(false);
        if (!$live instanceof EcaReadOnlyPdo) {
            $connection = 'FAILED';
            $note = 'The production connection did not open in read-only mode.';
        } else {
            $live->query('SELECT 1')->fetchColumn();
            $connection = 'SUCCESS';
            $select = 'AVAILABLE';
            try {
                $privileges = eca_db_mode_privileges($live);
                $danger = ['INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'CREATE', 'TRUNCATE', 'GRANT', 'REVOKE', 'ALL', 'ALL PRIVILEGES', 'CREATE TEMPORARY TABLES', 'INDEX'];
                $found = array_values(array_intersect($privileges, $danger));
                if ($privileges === []) {
                    $writes = 'UNKNOWN';
                } elseif ($found) {
                    $writes = 'PRESENT';
                    $note = 'This account can still change data. Remove every privilege except SELECT before using it.';
                } else {
                    $writes = 'DISABLED';
                }
            } catch (Throwable $e) {
                $writes = 'UNKNOWN';
                error_log('Live read-only grant check failed. SQLSTATE ' . $e->getCode());
            }
            $listed = $live->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM);
            foreach ($listed as $row) {
                $name = (string) ($row[0] ?? '');
                if ($name !== '' && preg_match('/^[A-Za-z0-9_]+$/', $name)) {
                    $tables[] = $name;
                }
            }
            sort($tables);
        }
    } catch (Throwable $e) {
        $connection = 'FAILED';
        $select = 'NOT AVAILABLE';
        $writes = 'UNKNOWN';
        $note = eca_db_mode_safe_failure($e);
        error_log('Live read-only check failed. SQLSTATE ' . $e->getCode());
    }
}

eca_admin_hub_start('Database mode', 'dashboard');
?>
<div class="panel" style="margin:18px">
    <div class="eca-panel-heading"><h3>Database mode</h3></div>
    <div class="eca-panel-body">
        <dl class="eca-db-check" style="display:grid;grid-template-columns:180px 1fr;gap:8px 16px;margin:0 0 16px">
            <dt>Environment</dt>
            <dd><?= $status['env'] === 'live_readonly' ? 'LIVE READ-ONLY' : 'LOCAL DEVELOPMENT' ?></dd>
            <dt>Database</dt>
            <dd><?= eca_admin_h($status['database']) ?></dd>
            <dt>Connection</dt>
            <dd><?= eca_admin_h($connection) ?></dd>
            <dt>SELECT</dt>
            <dd><?= eca_admin_h($select) ?></dd>
            <dt>Write privileges</dt>
            <dd><?= eca_admin_h($writes) ?></dd>
        </dl>
        <?php if ($note !== ''): ?><p><?= eca_admin_h($note) ?></p><?php endif; ?>
        <h4>Production tables</h4>
        <?php if ($tables): ?>
            <p><?= eca_admin_h(implode(', ', $tables)) ?></p>
        <?php elseif ($connection === 'SUCCESS'): ?>
            <p>No tables were listed.</p>
        <?php else: ?>
            <p>None. Production is listed here only after a read-only connection succeeds.</p>
        <?php endif; ?>
    </div>
</div>
<?php eca_admin_hub_end(); ?>

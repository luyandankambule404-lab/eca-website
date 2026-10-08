<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/pagination.php';
eca_admin_require('audit.view');
if (!eca_can('audit.view')) {
    eca_forbid();
}
header('Cache-Control: no-store');

$detailId = (int) ($_GET['id'] ?? 0);
$action = trim((string) ($_GET['action'] ?? ''));
$search = trim((string) ($_GET['search'] ?? ''));
$actor = trim((string) ($_GET['actor'] ?? ''));
$entity = trim((string) ($_GET['entity'] ?? ''));
$module = trim((string) ($_GET['module'] ?? ''));
$from = trim((string) ($_GET['from'] ?? ''));
$to = trim((string) ($_GET['to'] ?? ''));
$eventType = strtolower(trim((string) ($_GET['type'] ?? '')));
$sort = strtolower(trim((string) ($_GET['sort'] ?? 'id')));
$sortDir = strtolower(trim((string) ($_GET['dir'] ?? 'desc'))) === 'asc' ? 'ASC' : 'DESC';
$sortable = [
    'id' => 'id',
    'created_at' => 'created_at',
    'action' => 'action',
    'actor_email' => 'actor_email',
    'entity_type' => 'entity_type',
];
if (!isset($sortable[$sort])) {
    $sort = 'id';
}
$orderSql = $sortable[$sort] . ' ' . $sortDir . ', id DESC';
$page = eca_pager_page();
$limit = eca_pager_limit();
$conn = eca_admin_db();
$rows = [];
$total = 0;
$totalPages = 1;
$detail = null;
$canViewSecurity = eca_can('security.view');
$securityActions = eca_security_audit_actions();
$actions = eca_hub_audit_filter_actions($conn, $canViewSecurity);
$modules = [];

if ($conn) {
    try {
        $modRows = $conn->query(
            "SELECT DISTINCT entity_type FROM audit_logs
             WHERE entity_type IS NOT NULL AND entity_type <> ''
             ORDER BY entity_type"
        )->fetchAll(PDO::FETCH_COLUMN);
        foreach ($modRows as $mod) {
            $mod = trim((string) $mod);
            if ($mod !== '') {
                $modules[] = $mod;
            }
        }
    } catch (Throwable $e) {
        $modules = [];
    }
}

function eca_audit_row_visible(?array $row, bool $canViewSecurity, array $securityActions): bool
{
    if (!$row) {
        return false;
    }
    $rowAction = strtolower(trim((string) ($row['action'] ?? '')));
    if ($rowAction === '') {
        return true;
    }
    if ($canViewSecurity) {
        return true;
    }
    return !in_array($rowAction, $securityActions, true);
}

function eca_audit_safe_meta_display($metaRaw): string
{
    if ($metaRaw === null || $metaRaw === '') {
        return '';
    }
    $decoded = is_array($metaRaw) ? $metaRaw : json_decode((string) $metaRaw, true);
    if (!is_array($decoded)) {
        return '';
    }
    if (function_exists('eca_rbac_safe_meta')) {
        $decoded = eca_rbac_safe_meta($decoded);
    }
    $json = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    return is_string($json) ? $json : '';
}

if ($conn && $detailId > 0) {
    try {
        $stmt = $conn->prepare(
            'SELECT id, actor_type, actor_id, actor_email, action, entity_type, entity_id, ip, user_agent, meta, created_at
             FROM audit_logs WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$detailId]);
        $detail = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($detail && !eca_audit_row_visible($detail, $canViewSecurity, $securityActions)) {
            eca_forbid('Only a Super Admin can view security audit events.');
        }
        if (!$detail) {
            eca_not_found('Audit record not found.');
        }
    } catch (Throwable $e) {
        $detail = null;
        eca_not_found('Audit record not found.');
    }
}

if ($conn && $detailId < 1) {
    try {
        $where = ' WHERE 1=1';
        $params = [];
        if (!$canViewSecurity) {
            $placeholders = implode(',', array_fill(0, count($securityActions), '?'));
            $where .= ' AND (action IS NULL OR action NOT IN (' . $placeholders . '))';
            foreach ($securityActions as $securityAction) {
                $params[] = $securityAction;
            }
        } elseif ($eventType === 'security') {
            $placeholders = implode(',', array_fill(0, count($securityActions), '?'));
            $where .= ' AND action IN (' . $placeholders . ')';
            foreach ($securityActions as $securityAction) {
                $params[] = $securityAction;
            }
        } elseif ($eventType === 'operational') {
            $placeholders = implode(',', array_fill(0, count($securityActions), '?'));
            $where .= ' AND (action IS NULL OR action NOT IN (' . $placeholders . '))';
            foreach ($securityActions as $securityAction) {
                $params[] = $securityAction;
            }
        }
        if ($action !== '') {
            if (!$canViewSecurity && in_array($action, $securityActions, true)) {
                eca_forbid('Only a Super Admin can view security audit events.');
            }
            $where .= ' AND action = ?';
            $params[] = $action;
        }
        if ($actor !== '') {
            $where .= ' AND (actor_email LIKE ? OR actor_type LIKE ? OR actor_id LIKE ?)';
            $likeActor = '%' . $actor . '%';
            array_push($params, $likeActor, $likeActor, $likeActor);
        }
        if ($module !== '') {
            $where .= ' AND entity_type = ?';
            $params[] = $module;
        }
        if ($entity !== '') {
            $where .= ' AND (entity_type LIKE ? OR entity_id LIKE ?)';
            $likeEntity = '%' . $entity . '%';
            array_push($params, $likeEntity, $likeEntity);
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $where .= ' AND created_at >= ?';
            $params[] = $from . ' 00:00:00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $where .= ' AND created_at <= ?';
            $params[] = $to . ' 23:59:59';
        }
        if ($search !== '') {
            $where .= ' AND (actor_email LIKE ? OR action LIKE ? OR entity_type LIKE ? OR entity_id LIKE ? OR actor_id LIKE ?)';
            $like = '%' . $search . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }
        $paged = eca_paged_query(
            $conn,
            'SELECT COUNT(*) FROM audit_logs' . $where,
            'SELECT id, actor_type, actor_id, actor_email, action, entity_type, entity_id, created_at FROM audit_logs'
                . $where . ' ORDER BY ' . $orderSql,
            $params,
            $page,
            $limit
        );
        $rows = $paged['rows'];
        $total = $paged['total'];
        $page = $paged['page'];
        $totalPages = $paged['pages'];
        if (eca_admin_export_requested()) {
            eca_admin_require_csv_export();
            $exportRows = eca_admin_export_rows(
                $conn,
                'SELECT created_at, actor_type, actor_email, action, entity_type, entity_id FROM audit_logs'
                    . $where . ' ORDER BY ' . $orderSql,
                $params
            );
            $csv = [];
            foreach ($exportRows as $row) {
                $csv[] = [
                    $row['created_at'] ?? '',
                    $row['actor_type'] ?? '',
                    $row['actor_email'] ?? '',
                    $row['action'] ?? '',
                    $row['entity_type'] ?? '',
                    $row['entity_id'] ?? '',
                ];
            }
            eca_admin_send_csv('eca-audit-logs', ['When', 'Actor type', 'Actor', 'Action', 'Entity type', 'Entity id'], $csv, 'audit_logs');
        }
    } catch (Throwable $e) {
        $rows = [];
    }
}

$filterQuery = [
    'search' => $search,
    'actor' => $actor,
    'entity' => $entity,
    'module' => $module,
    'from' => $from,
    'to' => $to,
    'action' => $action,
    'type' => $eventType,
    'sort' => $sort,
    'dir' => strtolower($sortDir),
];

eca_admin_hub_start('Audit logs', 'audit');
?>
<div class="hub-hello">
    <h1 class="hub-hello-title"><?= $detail ? 'Audit detail' : 'Audit logs (' . (int) $total . ')' ?></h1>
    <p>Read-only history of local Hub actions. Passwords, tokens and secrets are not shown. Records cannot be edited or deleted here.</p>
</div>
<?php if ($detail): ?>
<div class="hub-card" style="margin-bottom:12px;">
    <p style="margin:0 0 12px;"><a class="hub-btn" href="/admin/audit.php?<?= eca_admin_h(http_build_query(array_filter($filterQuery, static fn ($v) => $v !== '' && $v !== null))) ?>">Back to list</a></p>
    <table class="hub-table">
        <tbody>
            <tr><th scope="row">When</th><td><?= eca_admin_h($detail['created_at'] ?? '') ?></td></tr>
            <tr><th scope="row">Actor</th><td><?= eca_admin_h(trim(($detail['actor_email'] ?? '') . ' (' . ($detail['actor_type'] ?? '') . ' #' . ($detail['actor_id'] ?? '') . ')')) ?></td></tr>
            <tr><th scope="row">Action</th><td><?= eca_admin_h($detail['action'] ?? '') ?></td></tr>
            <tr><th scope="row">Module / entity</th><td><?= eca_admin_h(trim(($detail['entity_type'] ?? '') . ' ' . ($detail['entity_id'] ?? ''))) ?></td></tr>
            <tr><th scope="row">IP</th><td><?= eca_admin_h($detail['ip'] ?? '') ?></td></tr>
            <tr><th scope="row">User agent</th><td style="word-break:break-word;"><?= eca_admin_h($detail['user_agent'] ?? '') ?></td></tr>
            <tr><th scope="row">Metadata</th><td><pre style="white-space:pre-wrap;margin:0;font-size:.85rem;"><?= eca_admin_h(eca_audit_safe_meta_display($detail['meta'] ?? '')) ?></pre></td></tr>
        </tbody>
    </table>
</div>
<?php else: ?>
<form class="hub-card" method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">
    <input type="search" name="search" data-hub-search value="<?= eca_admin_h($search) ?>" placeholder="Actor, action or entity" autocomplete="off" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
    <input type="text" name="actor" value="<?= eca_admin_h($actor) ?>" placeholder="User / actor" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
    <input type="text" name="entity" value="<?= eca_admin_h($entity) ?>" placeholder="Target / entity" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
    <input type="date" name="from" value="<?= eca_admin_h($from) ?>" aria-label="From date" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
    <input type="date" name="to" value="<?= eca_admin_h($to) ?>" aria-label="To date" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
    <select name="action" onchange="this.form.submit()" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
        <option value="">All actions</option>
        <?php foreach ($actions as $opt): ?>
            <option value="<?= eca_admin_h($opt) ?>"<?= $action === $opt ? ' selected' : '' ?>><?= eca_admin_h($opt) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="module" onchange="this.form.submit()" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
        <option value="">All modules</option>
        <?php foreach ($modules as $mod): ?>
            <option value="<?= eca_admin_h($mod) ?>"<?= $module === $mod ? ' selected' : '' ?>><?= eca_admin_h($mod) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($canViewSecurity): ?>
    <select name="type" onchange="this.form.submit()" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
        <option value="">All event types</option>
        <option value="security"<?= $eventType === 'security' ? ' selected' : '' ?>>Security</option>
        <option value="operational"<?= $eventType === 'operational' ? ' selected' : '' ?>>Operational</option>
    </select>
    <?php endif; ?>
    <select name="sort" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
        <?php foreach (['id' => 'ID', 'created_at' => 'When', 'action' => 'Action', 'actor_email' => 'Actor', 'entity_type' => 'Module'] as $sk => $sl): ?>
            <option value="<?= $sk ?>"<?= $sort === $sk ? ' selected' : '' ?>><?= $sl ?></option>
        <?php endforeach; ?>
    </select>
    <select name="dir" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
        <option value="desc"<?= $sortDir === 'DESC' ? ' selected' : '' ?>>Desc</option>
        <option value="asc"<?= $sortDir === 'ASC' ? ' selected' : '' ?>>Asc</option>
    </select>
    <button class="hub-btn" type="submit">Filter</button>
    <?= eca_admin_csv_button() ?>
</form>
<div class="hub-card eca-table-panel">
    <h5>Audit log</h5>
    <table class="hub-table" data-dash-server-page="1">
        <thead><tr><th>When</th><th>Actor</th><th>Action</th><th>Module / entity</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= eca_admin_h($row['created_at'] ?? '') ?></td>
                <td><?= eca_admin_h($row['actor_email'] ?? $row['actor_type'] ?? '') ?></td>
                <td><?= eca_admin_h($row['action'] ?? '') ?></td>
                <td><?= eca_admin_h(trim(($row['entity_type'] ?? '') . ' ' . ($row['entity_id'] ?? ''))) ?></td>
                <td><a href="/admin/audit.php?id=<?= (int) ($row['id'] ?? 0) ?>">View</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr data-hub-empty-row><td colspan="5">No audit rows.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php
eca_render_request_pager($page, $totalPages, $total, $limit);
endif;
eca_admin_hub_end();
?>

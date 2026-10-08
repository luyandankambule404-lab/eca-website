<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../_hub.php';
require_once __DIR__ . '/../../includes/wellness.php';
require_once __DIR__ . '/../../includes/wellness-hub.php';
require_once __DIR__ . '/../../includes/audit.php';
require_once __DIR__ . '/../../includes/pagination.php';
eca_admin_require('wellness.manage');
header('Cache-Control: no-store');

$typeKey = trim((string) ($_GET['type'] ?? 'article'));
$types = eca_wellness_admin_content_types();
if (!isset($types[$typeKey]) || $typeKey === 'resource') {
    header('Location: /admin/wellness/resources.php');
    exit;
}
$type = $types[$typeKey];

$conn = eca_wellness_db();
if ($conn) {
    eca_wellness_hub_ensure_schema($conn);
}
$notice = '';
if ($conn && $_SERVER['REQUEST_METHOD'] === 'POST' && eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'status' && $id > 0) {
        $status = strtoupper((string) ($_POST['status'] ?? 'DRAFT'));
        if (!in_array($status, eca_content_statuses(), true)) {
            $status = 'DRAFT';
        }
        $pub = $status === 'PUBLISHED' ? date('Y-m-d H:i:s') : null;
        $conn->prepare('UPDATE wellness_resources SET status = ?, published_at = COALESCE(published_at, ?) WHERE id = ?')
            ->execute([$status, $pub, $id]);
        eca_audit('wellness.resource.' . ($status === 'PUBLISHED' ? 'published' : 'status'), 'wellness_resources', (string) $id);
        $notice = ucfirst($type['singular']) . ' status updated.';
    } elseif ($action === 'delete' && $id > 0) {
        $conn->prepare('DELETE FROM wellness_resources WHERE id = ?')->execute([$id]);
        eca_audit('wellness.resource.deleted', 'wellness_resources', (string) $id);
        $notice = ucfirst($type['singular']) . ' deleted.';
    }
}

$search = trim((string) ($_GET['search'] ?? ''));
$statusFilter = strtoupper(trim((string) ($_GET['status'] ?? '')));
$page = eca_pager_page();
$limit = eca_pager_limit();
$rows = [];
$total = 0;
$totalPages = 1;
if ($conn) {
    $where = ' WHERE r.kind = ?';
    $params = [$type['kind']];
    if ($search !== '') {
        $where .= ' AND (r.title LIKE ? OR r.description LIKE ? OR r.body_text LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like);
    }
    if ($statusFilter !== '' && in_array($statusFilter, eca_content_statuses(), true)) {
        $where .= ' AND r.status = ?';
        $params[] = $statusFilter;
    }
    $from = 'wellness_resources r LEFT JOIN wellness_categories c ON c.id = r.category_id';
    $paged = eca_paged_query(
        $conn,
        'SELECT COUNT(*) FROM ' . $from . $where,
        'SELECT r.*, c.name AS category_name FROM ' . $from . $where . ' ORDER BY r.id DESC',
        $params,
        $page,
        $limit
    );
    $rows = $paged['rows'];
    $total = $paged['total'];
    $page = $paged['page'];
    $totalPages = $paged['pages'];
}
$csrf = eca_admin_csrf();
$editBase = '/admin/wellness/resource-edit.php?type=' . rawurlencode($typeKey);
eca_admin_hub_start($type['label'], 'wellness');
eca_wellness_admin_subnav($type['nav']);
?>
<div class="hub-hello">
    <h1 class="hub-hello-title"><?= eca_admin_h($type['label']) ?></h1>
    <p><?= eca_admin_h($type['help']) ?></p>
    <p><a class="hub-btn" href="<?= eca_admin_h($editBase) ?>"><?= eca_admin_h($type['add']) ?></a></p>
</div>
<?php if ($notice): ?><p class="hub-card"><?= eca_admin_h($notice) ?></p><?php endif; ?>
<form class="hub-card hub-filter-row" method="get">
    <input type="hidden" name="type" value="<?= eca_admin_h($typeKey) ?>">
    <input type="search" name="search" value="<?= eca_admin_h($search) ?>" placeholder="Search <?= eca_admin_h($type['label']) ?>">
    <select name="status">
        <option value="">All statuses</option>
        <?php foreach (eca_content_statuses() as $st): ?>
            <option value="<?= $st ?>"<?= $statusFilter === $st ? ' selected' : '' ?>><?= $st ?></option>
        <?php endforeach; ?>
    </select>
    <button class="hub-btn" type="submit">Filter</button>
</form>
<div class="hub-card eca-table-panel">
<table class="hub-table" data-hub-search>
    <thead><tr><th>Title</th><th>Section</th><th>Status</th><th>Published</th><th>Actions</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?>
        <tr><td colspan="5">None stored yet. Use <?= eca_admin_h($type['add']) ?> when you have a real item to publish.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= eca_admin_h($row['title']) ?></td>
            <td><?= eca_admin_h(eca_wellness_section_labels()[(string) ($row['hub_section'] ?? '')] ?? ($row['hub_section'] ?? '—')) ?></td>
            <td><?= eca_admin_h($row['status']) ?></td>
            <td><?= eca_admin_h($row['published_at'] ?? '—') ?></td>
            <td class="hub-table-actions">
                <a class="hub-home-link" href="<?= eca_admin_h($editBase . '&id=' . (int) $row['id']) ?>">Edit</a>
                <form class="hub-table-action-form" method="post">
                    <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                    <input type="hidden" name="action" value="status">
                    <select name="status" onchange="this.form.submit()">
                        <?php foreach (eca_content_statuses() as $st): ?>
                            <option value="<?= $st ?>"<?= ($row['status'] ?? '') === $st ? ' selected' : '' ?>><?= $st ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <form class="hub-table-action-form" method="post" onsubmit="return confirm('Delete this <?= eca_admin_h($type['singular']) ?>?');">
                    <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="hub-link-danger">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php
echo eca_render_request_pager($page, $totalPages, $total);
eca_admin_hub_end();

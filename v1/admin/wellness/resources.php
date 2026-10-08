<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../_hub.php';
require_once __DIR__ . '/../../includes/wellness.php';
require_once __DIR__ . '/../../includes/wellness-hub.php';
require_once __DIR__ . '/../../includes/audit.php';
require_once __DIR__ . '/../../includes/pagination.php';
eca_admin_require('wellness.manage');
header('Cache-Control: no-store');
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
        eca_audit('wellness.resource.' . strtolower($status === 'PUBLISHED' ? 'published' : 'status'), 'wellness_resources', (string) $id);
        $notice = 'Resource status updated.';
    } elseif ($action === 'delete' && $id > 0) {
        $conn->prepare('DELETE FROM wellness_resources WHERE id = ?')->execute([$id]);
        eca_audit('wellness.resource.deleted', 'wellness_resources', (string) $id);
        $notice = 'Resource deleted.';
    }
}
$search = trim((string) ($_GET['search'] ?? ''));
$category = (int) ($_GET['category'] ?? 0);
$statusFilter = strtoupper(trim((string) ($_GET['status'] ?? '')));
$sectionFilter = trim((string) ($_GET['hub_section'] ?? ''));
if (!in_array($sectionFilter, eca_wellness_hub_section_keys(), true)) {
    $sectionFilter = '';
}
$kindFilter = trim((string) ($_GET['kind'] ?? ''));
if ($kindFilter !== '' && !isset(eca_wellness_hub_kinds()[$kindFilter])) {
    $kindFilter = '';
}
$page = eca_pager_page();
$limit = eca_pager_limit();
$rows = [];
$total = 0;
$totalPages = 1;
$categories = $conn ? eca_wellness_categories($conn, false) : [];
if ($conn) {
    $where = ' WHERE 1=1';
    $params = [];
    if ($search !== '') {
        $where .= ' AND (r.title LIKE ? OR r.description LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like);
    }
    if ($category > 0) {
        $where .= ' AND r.category_id = ?';
        $params[] = $category;
    }
    if ($statusFilter !== '' && in_array($statusFilter, eca_content_statuses(), true)) {
        $where .= ' AND r.status = ?';
        $params[] = $statusFilter;
    }
    if ($sectionFilter !== '') {
        $where .= ' AND r.hub_section = ?';
        $params[] = $sectionFilter;
    }
    if ($kindFilter !== '') {
        $where .= ' AND r.kind = ?';
        $params[] = $kindFilter;
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
eca_admin_hub_start('Wellness resources', 'wellness');
eca_wellness_admin_subnav('resources');
?>
<div class="hub-hello">
    <h1 class="hub-hello-title">Wellness resources</h1>
    <p>All Hub items in one list. Use Articles, Videos, Documents, Toolbox talks, Referrals or Groups when the type is known.</p>
    <p><a class="hub-btn" href="/admin/wellness/resource-edit.php?type=resource">Add resource</a></p>
</div>
<?php if ($notice): ?><p class="hub-card"><?= eca_admin_h($notice) ?></p><?php endif; ?>
<form class="hub-card hub-filter-row" method="get">
    <input type="search" name="search" value="<?= eca_admin_h($search) ?>" placeholder="Search title or description">
    <select name="category">
        <option value="0">All categories</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= (int) $cat['id'] ?>"<?= $category === (int) $cat['id'] ? ' selected' : '' ?>><?= eca_admin_h($cat['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="status">
        <option value="">All statuses</option>
        <?php foreach (eca_content_statuses() as $st): ?>
            <option value="<?= $st ?>"<?= $statusFilter === $st ? ' selected' : '' ?>><?= $st ?></option>
        <?php endforeach; ?>
    </select>
    <select name="hub_section">
        <option value="">All hub sections</option>
        <?php foreach (eca_wellness_section_labels() as $key => $label): ?>
            <?php if ($key === '') { continue; } ?>
            <option value="<?= eca_admin_h($key) ?>"<?= $sectionFilter === $key ? ' selected' : '' ?>><?= eca_admin_h($label) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="kind">
        <option value="">All types</option>
        <?php foreach (eca_wellness_hub_kinds() as $key => $label): ?>
            <option value="<?= eca_admin_h($key) ?>"<?= $kindFilter === $key ? ' selected' : '' ?>><?= eca_admin_h($label) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="hub-btn" type="submit">Filter</button>
</form>
<div class="hub-card eca-table-panel">
<table class="hub-table" data-hub-search>
    <thead><tr><th>Title</th><th>Section</th><th>Type</th><th>Category</th><th>Status</th><th>Published</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= eca_admin_h($row['title']) ?></td>
            <td><?= eca_admin_h(eca_wellness_section_labels()[(string) ($row['hub_section'] ?? '')] ?? ($row['hub_section'] ?? '—')) ?></td>
            <td><?= eca_admin_h(eca_wellness_hub_kinds()[(string) ($row['kind'] ?? '')] ?? ($row['kind'] ?? '—')) ?></td>
            <td><?= eca_admin_h($row['category_name'] ?? '—') ?></td>
            <td><?= eca_admin_h($row['status']) ?></td>
            <td><?= eca_admin_h($row['published_at'] ?? '—') ?></td>
            <td class="hub-table-actions">
                <a class="hub-home-link" href="/admin/wellness/resource-edit.php?id=<?= (int) $row['id'] ?>">Edit</a>
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
                <form class="hub-table-action-form" method="post" onsubmit="return confirm('Delete this resource?');">
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

<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../_hub.php';
require_once __DIR__ . '/../../includes/wellness.php';
require_once __DIR__ . '/../../includes/audit.php';
require_once __DIR__ . '/../../includes/pagination.php';
eca_admin_require('wellness.manage');
header('Cache-Control: no-store');
$conn = eca_wellness_db();
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
        $conn->prepare('UPDATE wellness_announcements SET status = ?, published_at = COALESCE(published_at, ?) WHERE id = ?')
            ->execute([$status, $pub, $id]);
        eca_audit('wellness.announcement.' . ($status === 'PUBLISHED' ? 'published' : 'updated'), 'wellness_announcements', (string) $id);
        $notice = 'Announcement updated.';
    } elseif ($action === 'delete' && $id > 0) {
        $conn->prepare('DELETE FROM wellness_announcements WHERE id = ?')->execute([$id]);
        eca_audit('wellness.announcement.deleted', 'wellness_announcements', (string) $id);
        $notice = 'Announcement deleted.';
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
    $where = ' WHERE 1=1';
    $params = [];
    if ($search !== '') {
        $where .= ' AND (title LIKE ? OR content LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like);
    }
    if ($statusFilter !== '' && in_array($statusFilter, eca_content_statuses(), true)) {
        $where .= ' AND status = ?';
        $params[] = $statusFilter;
    }
    $paged = eca_paged_query(
        $conn,
        'SELECT COUNT(*) FROM wellness_announcements' . $where,
        'SELECT * FROM wellness_announcements' . $where . ' ORDER BY id DESC',
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
eca_admin_hub_start('Wellness announcements', 'wellness');
eca_wellness_admin_subnav('announcements');
?>
<div class="hub-hello">
    <h1 class="hub-hello-title">Wellness announcements</h1>
    <p><a class="hub-btn" href="/admin/wellness/announcement-edit.php">Add announcement</a></p>
</div>
<?php if ($notice): ?><p class="hub-card"><?= eca_admin_h($notice) ?></p><?php endif; ?>
<form class="hub-card hub-filter-row" method="get">
    <input type="search" name="search" value="<?= eca_admin_h($search) ?>" placeholder="Search">
    <select name="status">
        <option value="">All statuses</option>
        <?php foreach (eca_content_statuses() as $st): ?>
            <option value="<?= $st ?>"<?= $statusFilter === $st ? ' selected' : '' ?>><?= $st ?></option>
        <?php endforeach; ?>
    </select>
    <button class="hub-btn" type="submit">Filter</button>
</form>
<div class="hub-card eca-table-panel">
<table class="hub-table">
    <thead><tr><th>Title</th><th>Status</th><th>Published</th><th>Expires</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= eca_admin_h($row['title']) ?></td>
            <td><?= eca_admin_h($row['status']) ?></td>
            <td><?= eca_admin_h($row['published_at'] ?? '—') ?></td>
            <td><?= eca_admin_h($row['expires_at'] ?? '—') ?></td>
            <td class="hub-table-actions">
                <a class="hub-home-link" href="/admin/wellness/announcement-edit.php?id=<?= (int) $row['id'] ?>">Edit</a>
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
                <form class="hub-table-action-form" method="post" onsubmit="return confirm('Delete?');">
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

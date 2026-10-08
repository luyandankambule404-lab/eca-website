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
    $id = (int) ($_POST['id'] ?? 0);
    $status = strtoupper((string) ($_POST['status'] ?? ''));
    if ($id > 0 && in_array($status, eca_content_statuses(), true)) {
        $conn->prepare('UPDATE wellness_events SET status = ? WHERE id = ?')->execute([$status, $id]);
        $action = $status === 'CLOSED' ? 'wellness.event.cancelled' : 'wellness.event.updated';
        eca_audit($action, 'wellness_events', (string) $id, ['status' => $status]);
        $notice = 'Event status saved.';
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
        $where .= ' AND (e.title LIKE ? OR e.venue LIKE ? OR e.location LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like);
    }
    if ($statusFilter !== '' && in_array($statusFilter, eca_content_statuses(), true)) {
        $where .= ' AND e.status = ?';
        $params[] = $statusFilter;
    }
    $from = 'wellness_events e';
    $paged = eca_paged_query(
        $conn,
        'SELECT COUNT(*) FROM ' . $from . $where,
        'SELECT e.*, (SELECT COUNT(*) FROM wellness_event_registrations r WHERE r.event_id = e.id) AS registered FROM ' . $from . $where . ' ORDER BY e.starts_at DESC, e.id DESC',
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
eca_admin_hub_start('Wellness events', 'wellness');
eca_wellness_admin_subnav('events');
?>
<div class="hub-hello">
    <h1 class="hub-hello-title">Wellness events</h1>
    <p><a class="hub-btn" href="/admin/wellness/event-edit.php">Add event</a></p>
</div>
<?php if ($notice): ?><p class="hub-card"><?= eca_admin_h($notice) ?></p><?php endif; ?>
<form class="hub-card hub-filter-row" method="get">
    <input type="search" name="search" value="<?= eca_admin_h($search) ?>" placeholder="Search events">
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
    <thead><tr><th>Title</th><th>When</th><th>Venue</th><th>Status</th><th>Registered</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= eca_admin_h($row['title']) ?></td>
            <td><?= eca_admin_h($row['starts_at'] ?? '—') ?></td>
            <td><?= eca_admin_h($row['venue'] ?? '—') ?></td>
            <td><?= eca_admin_h($row['status']) ?></td>
            <td><?= (int) ($row['registered'] ?? 0) ?><?php if (!empty($row['capacity'])): ?> / <?= (int) $row['capacity'] ?><?php endif; ?></td>
            <td class="hub-table-actions">
                <a class="hub-home-link" href="/admin/wellness/event-edit.php?id=<?= (int) $row['id'] ?>">Edit</a>
                <form class="hub-table-action-form" method="post">
                    <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                    <select name="status" onchange="this.form.submit()">
                        <?php foreach (eca_content_statuses() as $st): ?>
                            <option value="<?= $st ?>"<?= ($row['status'] ?? '') === $st ? ' selected' : '' ?>><?= $st ?></option>
                        <?php endforeach; ?>
                    </select>
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

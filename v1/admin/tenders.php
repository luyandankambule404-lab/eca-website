<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/content.php';
require_once __DIR__ . '/../includes/pagination.php';
eca_admin_require('content.manage');
header('Cache-Control: no-store');
$conn = eca_admin_db();
$notice = '';
if ($conn && $_SERVER['REQUEST_METHOD'] === 'POST' && eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
    $id = (int) ($_POST['id'] ?? 0);
    $status = strtoupper((string) ($_POST['status'] ?? ''));
    if ($id > 0 && in_array($status, eca_content_statuses(), true)) {
        $conn->prepare('UPDATE tenders SET status = ?, published_at = CASE WHEN ? = "PUBLISHED" THEN COALESCE(published_at, NOW()) ELSE published_at END WHERE id = ?')
            ->execute([$status, $status, $id]);
        require_once __DIR__ . '/../includes/admin-ops.php';
        eca_audit_content_status('tenders', (string) $id, $status, $status === 'PUBLISHED');
        $notice = 'Tender status saved.';
    }
}
$search = trim((string) ($_GET['search'] ?? ''));
$page = eca_pager_page();
$limit = eca_pager_limit();
$rows = [];
$total = 0;
$totalPages = 1;
if ($conn) {
    $where = ' WHERE 1=1';
    $params = [];
    if ($search !== '') {
        $where .= ' AND (title LIKE ? OR status LIKE ? OR summary LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like);
    }
    $paged = eca_paged_query(
        $conn,
        'SELECT COUNT(*) FROM tenders' . $where,
        'SELECT * FROM tenders' . $where . ' ORDER BY id DESC',
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
eca_admin_hub_start('Tenders', 'tenders');
?>
<div class="hub-hello"><h1 class="hub-hello-title">Tenders (<?= (int) $total ?>)</h1><p>Publish, close or archive. Empty until a real notice is added.</p></div>
<?php if ($notice): ?><p class="hub-card"><?= eca_admin_h($notice) ?></p><?php endif; ?>
<form class="hub-card" method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">
    <input type="search" name="search" data-hub-search value="<?= eca_admin_h($search) ?>" placeholder="Type a letter to filter title or status" autocomplete="off" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
    <button class="hub-btn" type="submit">Search</button>
</form>
<p class="hub-card"><a href="/admin/tender-edit.php">Add tender</a></p>
<div class="hub-card eca-table-panel">
<table class="hub-table" data-dash-server-page="1"><thead><tr><th>Title</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $row): ?>
<tr>
    <td><?= eca_admin_h($row['title'] ?? '') ?></td>
    <td><?= eca_admin_h($row['status'] ?? '') ?></td>
    <td class="hub-table-actions">
        <a href="/admin/tender-edit.php?id=<?= (int) $row['id'] ?>">Edit</a>
        <form method="post" class="hub-table-action-form">
            <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
            <select name="status" style="padding:8px;border-radius:8px;border:1px solid #d0d5dd;">
                <?php foreach (eca_content_statuses() as $opt): ?>
                    <option value="<?= $opt ?>"<?= ($row['status'] ?? '') === $opt ? ' selected' : '' ?>><?= $opt ?></option>
                <?php endforeach; ?>
            </select>
            <button class="hub-btn" type="submit">Update</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr data-hub-empty-row><td colspan="3">No tenders yet.</td></tr><?php endif; ?>
</tbody></table></div>
<?php
eca_render_request_pager($page, $totalPages, $total, $limit);
eca_admin_hub_end();

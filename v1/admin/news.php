<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/portal-db.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/pagination.php';
eca_admin_require('content.manage');
header('Cache-Control: no-store');

$conn = eca_portal_pdo(false);
$notice = '';
if ($conn && $_SERVER['REQUEST_METHOD'] === 'POST' && eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
    $id = (int) ($_POST['id'] ?? 0);
    $status = (string) ($_POST['status'] ?? 'Inactive');
    if ($id > 0 && in_array($status, ['Active', 'Inactive'], true)) {
        $conn->prepare('UPDATE news SET status = ? WHERE id = ?')->execute([$status, $id]);
        require_once __DIR__ . '/../includes/admin-ops.php';
        eca_audit_content_status('news', (string) $id, $status, $status === 'Active');
        $notice = 'News status saved.';
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
        $where .= ' AND (title LIKE ? OR status LIKE ? OR author LIKE ? OR `date` LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like);
    }
    $paged = eca_paged_query(
        $conn,
        'SELECT COUNT(*) FROM news' . $where,
        'SELECT id, title, status, date, author FROM news' . $where . ' ORDER BY date DESC, id DESC',
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
eca_admin_hub_start('News', 'news');
?>
<div class="hub-hello">
    <h1 class="hub-hello-title">News (<?= (int) $total ?>)</h1>
    <p>Publish or unpublish existing articles. Do not invent stories — add only real copy.</p>
</div>
<?php if ($notice): ?><p class="hub-card"><?= eca_admin_h($notice) ?></p><?php endif; ?>
<form class="hub-card" method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">
    <input type="search" name="search" data-hub-search value="<?= eca_admin_h($search) ?>" placeholder="Type a letter to filter title, date or status" autocomplete="off" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
    <button class="hub-btn" type="submit">Search</button>
</form>
<p class="hub-card"><a href="/admin/news-edit.php">Add article</a></p>
<div class="hub-card eca-table-panel">
    <h5>News</h5>
    <table class="hub-table" data-dash-server-page="1">
        <thead><tr><th>Title</th><th>Date</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= eca_admin_h($row['title'] ?? '') ?></td>
                <td><?= eca_admin_h($row['date'] ?? '') ?></td>
                <td><?= eca_admin_h($row['status'] ?? '') ?></td>
                <td class="hub-table-actions">
                    <a href="/admin/news-edit.php?id=<?= (int) $row['id'] ?>">Edit</a>
                    <form method="post" class="hub-table-action-form">
                        <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                        <button class="hub-btn" name="status" value="<?= ($row['status'] ?? '') === 'Active' ? 'Inactive' : 'Active' ?>" type="submit"><?= ($row['status'] ?? '') === 'Active' ? 'Unpublish' : 'Publish' ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr data-hub-empty-row><td colspan="4">No news articles match.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php
eca_render_request_pager($page, $totalPages, $total, $limit);
eca_admin_hub_end();

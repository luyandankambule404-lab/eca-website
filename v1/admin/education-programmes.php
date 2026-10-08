<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/_education.php';
require_once __DIR__ . '/../includes/education.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/pagination.php';
eca_admin_require('education.manage');
header('Cache-Control: no-store');
$conn = eca_education_db();
eca_education_admin_require_ready($conn, 'Programmes');
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
    $id = (int) ($_POST['id'] ?? 0);
    $status = strtoupper((string) ($_POST['status'] ?? ''));
    if ($id > 0 && isset(eca_education_publish_statuses()[$status])) {
        $conn->prepare('UPDATE education_programmes SET status = ? WHERE id = ?')->execute([$status, $id]);
        eca_audit('education.programme.status', 'education_programmes', (string) $id);
        $notice = 'Programme status saved.';
    }
}
$search = trim((string) ($_GET['search'] ?? ''));
$where = ' WHERE 1=1';
$params = [];
if ($search !== '') {
    $where .= ' AND (title LIKE ? OR summary LIKE ? OR status LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like);
}
$paged = eca_paged_query(
    $conn,
    'SELECT COUNT(*) FROM education_programmes' . $where,
    'SELECT * FROM education_programmes' . $where . ' ORDER BY sort_order ASC, id DESC',
    $params,
    eca_pager_page(),
    eca_pager_limit()
);
$csrf = eca_admin_csrf();
eca_admin_hub_start('Programmes', 'education');
?>
<div class="hub-hello"><h1 class="hub-hello-title">Contractor development programmes (<?= (int) $paged['total'] ?>)</h1><p>Add new programmes as ECA expands mentorship, readiness and targeted development work.</p></div>
<?php eca_education_admin_nav('programmes'); ?>
<?php if ($notice): ?><p class="hub-card"><?= eca_admin_h($notice) ?></p><?php endif; ?>
<p class="hub-card"><a href="/admin/education-programme-edit.php">Add programme</a></p>
<form class="hub-card" method="get"><input type="search" name="search" value="<?= eca_admin_h($search) ?>" placeholder="Search programmes" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;"> <button class="hub-btn" type="submit">Search</button></form>
<div class="hub-card eca-table-panel">
    <h5>Programmes</h5>
    <table class="hub-table">
        <thead><tr><th>Programme</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($paged['rows'] as $row): ?>
            <tr>
                <td><?= eca_admin_h($row['title'] ?? '') ?></td>
                <td><?= eca_admin_h($row['status'] ?? '') ?></td>
                <td class="hub-table-actions">
                    <a href="/admin/education-programme-edit.php?id=<?= (int) $row['id'] ?>">Edit</a>
                    <form method="post" class="hub-table-action-form">
                        <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                        <button class="hub-btn" name="status" value="<?= ($row['status'] ?? '') === 'PUBLISHED' ? 'DRAFT' : 'PUBLISHED' ?>" type="submit"><?= ($row['status'] ?? '') === 'PUBLISHED' ? 'Unpublish' : 'Publish' ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$paged['rows']): ?><tr><td colspan="3">No programmes match.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php
eca_render_request_pager($paged['page'], $paged['pages'], $paged['total'], $paged['limit']);
eca_admin_hub_end();

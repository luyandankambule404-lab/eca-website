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
eca_education_admin_require_ready($conn, 'Articles');
$kind = (string) ($_GET['kind'] ?? 'knowledge');
if (!in_array($kind, ['knowledge', 'policy'], true)) {
    $kind = 'knowledge';
}
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
    $id = (int) ($_POST['id'] ?? 0);
    $status = strtoupper((string) ($_POST['status'] ?? ''));
    if ($id > 0 && isset(eca_education_publish_statuses()[$status])) {
        $pub = $status === 'PUBLISHED' ? date('Y-m-d H:i:s') : null;
        $conn->prepare('UPDATE education_articles SET status = ?, published_at = COALESCE(?, published_at) WHERE id = ?')->execute([$status, $pub, $id]);
        eca_audit('education.article.status', 'education_articles', (string) $id);
        $notice = 'Status saved.';
    }
}
$search = trim((string) ($_GET['search'] ?? ''));
$where = ' WHERE kind = ?';
$params = [$kind];
if ($search !== '') {
    $where .= ' AND (title LIKE ? OR status LIKE ? OR summary LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like);
}
$paged = eca_paged_query(
    $conn,
    'SELECT COUNT(*) FROM education_articles' . $where,
    'SELECT id, title, status, is_featured, published_at FROM education_articles' . $where . ' ORDER BY id DESC',
    $params,
    eca_pager_page(),
    eca_pager_limit()
);
$csrf = eca_admin_csrf();
$label = $kind === 'policy' ? 'Policy updates' : 'Knowledge articles';
eca_admin_hub_start($label, 'education');
?>
<div class="hub-hello"><h1 class="hub-hello-title"><?= eca_admin_h($label) ?> (<?= (int) $paged['total'] ?>)</h1></div>
<?php eca_education_admin_nav($kind); ?>
<?php if ($notice): ?><p class="hub-card"><?= eca_admin_h($notice) ?></p><?php endif; ?>
<p class="hub-card"><a href="/admin/education-article-edit.php?kind=<?= eca_admin_h($kind) ?>">Add <?= $kind === 'policy' ? 'policy update' : 'article' ?></a></p>
<form class="hub-card" method="get" style="display:flex;gap:10px;flex-wrap:wrap;">
    <input type="hidden" name="kind" value="<?= eca_admin_h($kind) ?>">
    <input type="search" name="search" value="<?= eca_admin_h($search) ?>" placeholder="Search" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
    <button class="hub-btn" type="submit">Search</button>
</form>
<div class="hub-card eca-table-panel">
    <h5>Articles</h5>
    <table class="hub-table">
        <thead><tr><th>Title</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($paged['rows'] as $row): ?>
            <tr>
                <td><?= eca_admin_h($row['title'] ?? '') ?><?= !empty($row['is_featured']) ? ' · Featured' : '' ?></td>
                <td><?= eca_admin_h($row['status'] ?? '') ?></td>
                <td class="hub-table-actions">
                    <a href="/admin/education-article-edit.php?id=<?= (int) $row['id'] ?>">Edit</a>
                    <form method="post" class="hub-table-action-form">
                        <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                        <button class="hub-btn" name="status" value="<?= ($row['status'] ?? '') === 'PUBLISHED' ? 'DRAFT' : 'PUBLISHED' ?>" type="submit"><?= ($row['status'] ?? '') === 'PUBLISHED' ? 'Unpublish' : 'Publish' ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$paged['rows']): ?><tr><td colspan="3">No items match.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php
eca_render_request_pager($paged['page'], $paged['pages'], $paged['total'], $paged['limit']);
eca_admin_hub_end();

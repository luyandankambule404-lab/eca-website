<?php
require_once __DIR__ . '/../_shell.php';
require_once __DIR__ . '/../../includes/wellness.php';
require_once __DIR__ . '/../../includes/authz.php';
require_once __DIR__ . '/../../includes/pagination.php';

$member = eca_portal_member();
$GLOBALS['eca_member'] = $member;
eca_require_permission('wellness.view');
$conn = eca_wellness_db();

$search = trim((string) ($_GET['search'] ?? ''));
$category = (int) ($_GET['category'] ?? 0);
$page = eca_pager_page();
$limit = eca_pager_limit();
$rows = [];
$total = 0;
$totalPages = 1;
$categories = $conn ? eca_wellness_categories($conn) : [];

if ($conn) {
    $where = " WHERE r.status = 'PUBLISHED'";
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
    $from = 'wellness_resources r LEFT JOIN wellness_categories c ON c.id = r.category_id';
    $paged = eca_paged_query(
        $conn,
        'SELECT COUNT(*) FROM ' . $from . $where,
        'SELECT r.*, c.name AS category_name FROM ' . $from . $where . ' ORDER BY r.published_at DESC, r.id DESC',
        $params,
        $page,
        $limit
    );
    $rows = $paged['rows'];
    $total = $paged['total'];
    $page = $paged['page'];
    $totalPages = $paged['pages'];
}

eca_portal_start('Wellness resources', 'wellness');
eca_wellness_member_subnav('resources');
?>
<div class="hub-hello"><h1 class="hub-hello-title">Wellness resources</h1></div>
<form class="hub-card hub-filter-row" method="get">
    <input type="search" name="search" value="<?= eca_h($search) ?>" placeholder="Search resources">
    <select name="category">
        <option value="0">All categories</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= (int) $cat['id'] ?>"<?= $category === (int) $cat['id'] ? ' selected' : '' ?>><?= eca_h($cat['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="hub-btn" type="submit">Filter</button>
</form>
<div class="hub-card eca-table-panel">
<table class="hub-table">
    <thead><tr><th>Title</th><th>Category</th><th>Published</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
        <tr id="res-<?= (int) $row['id'] ?>">
            <td>
                <strong><?= eca_h($row['title']) ?></strong>
                <?php if (!empty($row['description'])): ?>
                    <div class="hub-stat-meta"><?= eca_h(mb_strimwidth((string) $row['description'], 0, 120, '…')) ?></div>
                <?php endif; ?>
            </td>
            <td><?= eca_h($row['category_name'] ?? '—') ?></td>
            <td><?= eca_h($row['published_at'] ?? $row['created_at'] ?? '') ?></td>
            <td>
                <?php if (!empty($row['file_path'])): ?>
                    <a href="/wellness-download.php?id=<?= (int) $row['id'] ?>">Download</a>
                <?php elseif (!empty($row['external_url'])): ?>
                    <a href="<?= eca_h($row['external_url']) ?>" target="_blank" rel="noopener">View</a>
                <?php else: ?>
                    —
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php echo eca_render_request_pager($page, $totalPages, $total); ?>
<?php eca_portal_end(); ?>

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
$page = eca_pager_page();
$limit = eca_pager_limit();
$rows = [];
$total = 0;
$totalPages = 1;

if ($conn) {
    $where = ' WHERE ' . eca_wellness_active_announcement_sql();
    $params = [];
    if ($search !== '') {
        $where .= ' AND (title LIKE ? OR content LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like);
    }
    $paged = eca_paged_query(
        $conn,
        'SELECT COUNT(*) FROM wellness_announcements' . $where,
        'SELECT * FROM wellness_announcements' . $where . ' ORDER BY published_at DESC, id DESC',
        $params,
        $page,
        $limit
    );
    $rows = $paged['rows'];
    $total = $paged['total'];
    $page = $paged['page'];
    $totalPages = $paged['pages'];
}

eca_portal_start('Wellness announcements', 'wellness');
eca_wellness_member_subnav('announcements');
?>
<div class="hub-hello"><h1 class="hub-hello-title">Wellness announcements</h1></div>
<form class="hub-card hub-filter-row" method="get">
    <input type="search" name="search" value="<?= eca_h($search) ?>" placeholder="Search announcements">
    <button class="hub-btn" type="submit">Search</button>
</form>
<?php foreach ($rows as $row): ?>
<div class="hub-card">
    <h2><?= eca_h($row['title']) ?></h2>
    <p class="hub-stat-meta"><?= eca_h($row['published_at'] ?? '') ?></p>
    <p><?= nl2br(eca_h($row['content'])) ?></p>
</div>
<?php endforeach; ?>
<?php if (!$rows): ?><p class="hub-card">No active announcements.</p><?php endif; ?>
<?php echo eca_render_request_pager($page, $totalPages, $total); ?>
<?php eca_portal_end(); ?>

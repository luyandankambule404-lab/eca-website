<?php
require_once __DIR__ . '/../_shell.php';
require_once __DIR__ . '/../../includes/wellness.php';
require_once __DIR__ . '/../../includes/authz.php';
require_once __DIR__ . '/../../includes/pagination.php';

$member = eca_portal_member();
$GLOBALS['eca_member'] = $member;
eca_require_permission('wellness.view');
$conn = eca_wellness_db();

$when = (string) ($_GET['when'] ?? 'upcoming');
if (!in_array($when, ['upcoming', 'past', 'all'], true)) {
    $when = 'upcoming';
}
$search = trim((string) ($_GET['search'] ?? ''));
$page = eca_pager_page();
$limit = eca_pager_limit();
$rows = [];
$total = 0;
$totalPages = 1;

if ($conn) {
    $where = " WHERE e.status = 'PUBLISHED'";
    $params = [];
    if ($when === 'upcoming') {
        $where .= ' AND (e.starts_at IS NULL OR e.starts_at >= NOW())';
    } elseif ($when === 'past') {
        $where .= ' AND e.starts_at IS NOT NULL AND e.starts_at < NOW()';
    }
    if ($search !== '') {
        $where .= ' AND (e.title LIKE ? OR e.venue LIKE ? OR e.location LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like);
    }
    $from = 'wellness_events e';
    $paged = eca_paged_query(
        $conn,
        'SELECT COUNT(*) FROM ' . $from . $where,
        'SELECT e.* FROM ' . $from . $where . ' ORDER BY e.starts_at DESC, e.id DESC',
        $params,
        $page,
        $limit
    );
    $rows = $paged['rows'];
    $total = $paged['total'];
    $page = $paged['page'];
    $totalPages = $paged['pages'];
}

eca_portal_start('Wellness events', 'wellness');
eca_wellness_member_subnav('events');
?>
<div class="hub-hello"><h1 class="hub-hello-title">Wellness events</h1></div>
<form class="hub-card hub-filter-row" method="get">
    <input type="search" name="search" value="<?= eca_h($search) ?>" placeholder="Search events">
    <select name="when">
        <option value="upcoming"<?= $when === 'upcoming' ? ' selected' : '' ?>>Upcoming</option>
        <option value="past"<?= $when === 'past' ? ' selected' : '' ?>>Past</option>
        <option value="all"<?= $when === 'all' ? ' selected' : '' ?>>All</option>
    </select>
    <button class="hub-btn" type="submit">Filter</button>
</form>
<div class="hub-card eca-table-panel">
<table class="hub-table">
    <thead><tr><th>Event</th><th>When</th><th>Venue</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= eca_h($row['title']) ?></td>
            <td><?= eca_h($row['starts_at'] ?? '—') ?></td>
            <td><?= eca_h($row['venue'] ?? '—') ?></td>
            <td><a href="/client/wellness/event.php?id=<?= (int) $row['id'] ?>">View</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php echo eca_render_request_pager($page, $totalPages, $total); ?>
<?php eca_portal_end(); ?>

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
eca_education_admin_require_ready($conn, 'Courses');
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
    $id = (int) ($_POST['id'] ?? 0);
    $status = strtoupper((string) ($_POST['status'] ?? ''));
    if ($id > 0 && isset(eca_education_course_statuses()[$status])) {
        $conn->prepare('UPDATE education_courses SET status = ? WHERE id = ?')->execute([$status, $id]);
        eca_audit('education.course.status', 'education_courses', (string) $id, ['status' => $status]);
        $notice = 'Course status saved.';
    }
}
$search = trim((string) ($_GET['search'] ?? ''));
$page = eca_pager_page();
$limit = eca_pager_limit();
$where = ' WHERE 1=1';
$params = [];
if ($search !== '') {
    $where .= ' AND (c.title LIKE ? OR c.status LIKE ? OR c.audience LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like);
}
$paged = eca_paged_query(
    $conn,
    'SELECT COUNT(*) FROM education_courses c' . $where,
    eca_education_course_sql() . $where . ' ORDER BY c.start_date DESC, c.id DESC',
    $params,
    $page,
    $limit
);
$statuses = eca_education_course_statuses();
$csrf = eca_admin_csrf();
eca_admin_hub_start('Education courses', 'education');
?>
<div class="hub-hello"><h1 class="hub-hello-title">Courses (<?= (int) $paged['total'] ?>)</h1><p>Add dates, venues, fees, CPD information and registration links. Mark training upcoming or completed.</p></div>
<?php eca_education_admin_nav('courses'); ?>
<?php if ($notice): ?><p class="hub-card"><?= eca_admin_h($notice) ?></p><?php endif; ?>
<p class="hub-card"><a href="/admin/education-course-edit.php">Add course</a></p>
<form class="hub-card" method="get" style="display:flex;gap:10px;flex-wrap:wrap;">
    <input type="search" name="search" value="<?= eca_admin_h($search) ?>" placeholder="Search title, audience or status" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
    <button class="hub-btn" type="submit">Search</button>
</form>
<div class="hub-card eca-table-panel">
    <h5>Courses</h5>
    <table class="hub-table">
        <thead><tr><th>Course</th><th>Date</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($paged['rows'] as $row): ?>
            <tr>
                <td><?= eca_admin_h($row['title'] ?? '') ?><?= !empty($row['is_featured']) ? ' · Featured' : '' ?></td>
                <td><?= eca_admin_h(eca_education_date($row['start_date'] ?? null)) ?></td>
                <td><?= eca_admin_h($statuses[$row['status'] ?? ''] ?? $row['status'] ?? '') ?></td>
                <td class="hub-table-actions">
                    <a href="/admin/education-course-edit.php?id=<?= (int) $row['id'] ?>">Edit</a>
                    <form method="post" class="hub-table-action-form">
                        <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                        <select name="status"><?php foreach ($statuses as $key => $label): ?>
                            <option value="<?= eca_admin_h($key) ?>"<?= ($row['status'] ?? '') === $key ? ' selected' : '' ?>><?= eca_admin_h($label) ?></option>
                        <?php endforeach; ?></select>
                        <button class="hub-btn" type="submit">Save</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$paged['rows']): ?><tr><td colspan="4">No courses match.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php
eca_render_request_pager($paged['page'], $paged['pages'], $paged['total'], $limit);
eca_admin_hub_end();

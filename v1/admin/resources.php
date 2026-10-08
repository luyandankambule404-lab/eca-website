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
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'create') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $category = trim((string) ($_POST['category'] ?? 'General'));
        $desc = trim((string) ($_POST['description'] ?? ''));
        $status = (string) ($_POST['status'] ?? 'Published');
        $path = '';
        if (!empty($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
            require_once dirname(__DIR__) . '/includes/documents.php';
            $meta = eca_uploaded_file_meta($_FILES['file'], eca_upload_mime_map(['pdf', 'jpg', 'jpeg', 'png']), 5 * 1024 * 1024);
            if ($meta) {
                $ext = $meta['ext'];
                $dir = dirname(__DIR__) . '/downloads';
                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }
                $name = preg_replace('/[^A-Za-z0-9._-]/', '_', basename((string) $_FILES['file']['name']));
                if (move_uploaded_file($_FILES['file']['tmp_name'], $dir . '/' . $name)) {
                    $path = $name;
                }
            }
        }
        if ($title !== '' && $path !== '') {
            $conn->prepare('INSERT INTO resources (title, description, file_path, file_type, category, status) VALUES (?,?,?,?,?,?)')
                ->execute([$title, $desc, $path, pathinfo($path, PATHINFO_EXTENSION), $category, $status]);
            eca_audit('resource.create', 'resources', (string) $conn->lastInsertId());
            $notice = 'Resource saved.';
        } else {
            $notice = 'Title and a public PDF/JPG/PNG are required.';
        }
    } elseif ($action === 'status') {
        $id = (int) ($_POST['id'] ?? 0);
        $status = (string) ($_POST['status'] ?? 'Archived');
        if ($id > 0) {
            $conn->prepare('UPDATE resources SET status = ? WHERE id = ?')->execute([$status, $id]);
            require_once __DIR__ . '/../includes/admin-ops.php';
            eca_audit_content_status('resources', (string) $id, $status, in_array($status, ['Published', 'published', 'Active'], true));
            $notice = 'Resource status saved.';
        }
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
        $where .= ' AND (title LIKE ? OR category LIKE ? OR status LIKE ? OR description LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like);
    }
    $paged = eca_paged_query(
        $conn,
        'SELECT COUNT(*) FROM resources' . $where,
        'SELECT * FROM resources' . $where . ' ORDER BY id DESC',
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
eca_admin_hub_start('Resources', 'resources');
?>
<div class="hub-hello"><h1 class="hub-hello-title">Resources (<?= (int) $total ?>)</h1><p>Public files are stored with the existing downloads folder and served by download.php.</p></div>
<?php if ($notice): ?><p class="hub-card"><?= eca_admin_h($notice) ?></p><?php endif; ?>
<form class="hub-card" method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
    <input type="hidden" name="action" value="create">
    <p><label>Title<br><input class="form-control" name="title"></label></p>
    <p><label>Category<br><input class="form-control" name="category" value="General"></label></p>
    <p><label>Description<br><textarea class="form-control" name="description" rows="2"></textarea></label></p>
    <p><label>Status
        <select class="form-select" name="status">
            <option>Published</option>
            <option>Archived</option>
        </select>
    </label></p>
    <p><label>File<br><input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required></label></p>
    <button class="hub-btn" type="submit">Upload</button>
</form>
<form class="hub-card" method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">
    <input type="search" name="search" data-hub-search value="<?= eca_admin_h($search) ?>" placeholder="Type a letter to filter title, category or status" autocomplete="off" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
    <button class="hub-btn" type="submit">Search</button>
</form>
<div class="hub-card eca-table-panel">
<table class="hub-table" data-dash-server-page="1"><thead><tr><th>Title</th><th>Category</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $row): ?>
<tr>
    <td><?= eca_admin_h($row['title'] ?? '') ?></td>
    <td><?= eca_admin_h($row['category'] ?? '') ?></td>
    <td><?= eca_admin_h($row['status'] ?? '') ?></td>
    <td class="hub-table-actions">
        <a href="/download.php?file=<?= urlencode((string) ($row['file_path'] ?? '')) ?>">Download</a>
        <form method="post" class="hub-table-action-form">
            <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
            <input type="hidden" name="action" value="status">
            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
            <input type="hidden" name="status" value="<?= ($row['status'] ?? '') === 'Published' ? 'Archived' : 'Published' ?>">
            <button class="hub-btn" type="submit"><?= ($row['status'] ?? '') === 'Published' ? 'Archive' : 'Publish' ?></button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="4">No extra resources in the database. The public page still lists the existing constitution and bylaw files.</td></tr><?php endif; ?>
</tbody></table></div>
<?php
eca_render_request_pager($page, $totalPages, $total, $limit);
eca_admin_hub_end();
?>

<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/portal-db.php';
require_once __DIR__ . '/../includes/audit.php';
eca_admin_require('content.manage');
header('Cache-Control: no-store');

$conn = eca_portal_pdo(false);
if (!$conn) {
    eca_server_error();
}
$id = (int) ($_GET['id'] ?? 0);
$row = ['title' => '', 'summary' => '', 'author' => 'ECA Communications', 'categories' => 'Industry News', 'image' => '', 'link' => '#', 'date' => date('Y-m-d'), 'status' => 'Inactive'];
if ($id > 0) {
    $stmt = $conn->prepare('SELECT * FROM news WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: $row;
}
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
    $title = trim((string) ($_POST['title'] ?? ''));
    $summary = trim((string) ($_POST['summary'] ?? ''));
    $author = trim((string) ($_POST['author'] ?? 'ECA Communications'));
    $categories = trim((string) ($_POST['categories'] ?? ''));
    $link = trim((string) ($_POST['link'] ?? '#'));
    $date = trim((string) ($_POST['date'] ?? date('Y-m-d')));
    $status = (string) ($_POST['status'] ?? 'Inactive');
    $image = (string) ($row['image'] ?? '');
    if (!empty($_FILES['image']['tmp_name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
        require_once dirname(__DIR__) . '/includes/documents.php';
        $meta = eca_uploaded_file_meta($_FILES['image'], eca_upload_mime_map(['jpg', 'jpeg', 'png', 'gif', 'webp']), 5 * 1024 * 1024);
        if ($meta) {
            $ext = $meta['ext'];
            $dir = dirname(__DIR__) . '/uploads/news';
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $name = 'news-' . bin2hex(random_bytes(6)) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . '/' . $name)) {
                $image = 'uploads/news/' . $name;
            }
        }
    }
    if ($title === '' || $summary === '') {
        $notice = 'Title and summary are required.';
    } elseif ($id > 0) {
        $conn->prepare('UPDATE news SET title=?, summary=?, author=?, categories=?, image=?, link=?, date=?, status=? WHERE id=?')
            ->execute([$title, $summary, $author, $categories, $image, $link, $date, $status, $id]);
        eca_audit('news.update', 'news', (string) $id);
        require_once __DIR__ . '/../includes/admin-ops.php';
        eca_audit_content_status('news', (string) $id, $status, $status === 'Active');
        $notice = 'Article saved.';
    } else {
        $conn->prepare('INSERT INTO news (title, summary, author, categories, image, video, link, date, status, count, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,CURDATE())')
            ->execute([$title, $summary, $author, $categories, $image ?: 'img/ecalogo.png', '', $link, $date, $status, 0]);
        $id = (int) $conn->lastInsertId();
        eca_audit('news.create', 'news', (string) $id);
        require_once __DIR__ . '/../includes/admin-ops.php';
        eca_audit_content_status('news', (string) $id, $status, $status === 'Active');
        header('Location: /admin/news-edit.php?id=' . $id);
        exit;
    }
    $stmt = $conn->prepare('SELECT * FROM news WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: $row;
}
$csrf = eca_admin_csrf();
eca_admin_hub_start('Edit news', 'news');
?>
<div class="hub-hello"><h1 class="hub-hello-title"><?= $id ? 'Edit article' : 'Add article' ?></h1></div>
<?php if ($notice): ?><p class="hub-card"><?= eca_admin_h($notice) ?></p><?php endif; ?>
<form class="hub-card" method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
    <p><label>Title<br><input name="title" value="<?= eca_admin_h($row['title'] ?? '') ?>" class="form-control"></label></p>
    <p><label>Summary<br><textarea name="summary" rows="5" class="form-control"><?= eca_admin_h($row['summary'] ?? '') ?></textarea></label></p>
    <p><label>Author<br><input name="author" value="<?= eca_admin_h($row['author'] ?? '') ?>" class="form-control"></label></p>
    <p><label>Categories<br><input name="categories" value="<?= eca_admin_h($row['categories'] ?? '') ?>" class="form-control" placeholder="e.g. Advocacy, Industry News"></label></p>
    <p class="hub-note">To publish on the public <strong>Advocacy Updates</strong> page, include <code>Advocacy</code> in Categories. Local demo articles must not be used for official advocacy claims.</p>
    <p><label>Date<br><input name="date" type="date" value="<?= eca_admin_h($row['date'] ?? '') ?>" class="form-control"></label></p>
    <p><label>Status
        <select name="status" class="form-control">
            <option value="Active"<?= ($row['status'] ?? '') === 'Active' ? ' selected' : '' ?>>Published</option>
            <option value="Inactive"<?= ($row['status'] ?? '') !== 'Active' ? ' selected' : '' ?>>Unpublished</option>
        </select>
    </label></p>
    <p><label>Link<br><input name="link" value="<?= eca_admin_h($row['link'] ?? '#') ?>" class="form-control"></label></p>
    <p><label>Featured image<br><input type="file" name="image" accept="image/*"></label></p>
    <button class="hub-btn" type="submit">Save</button>
    <a class="hub-home-link" href="/admin/news.php">Back</a>
</form>
<?php eca_admin_hub_end(); ?>

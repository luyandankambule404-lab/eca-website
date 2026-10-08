<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/_education.php';
require_once __DIR__ . '/../includes/education.php';
require_once __DIR__ . '/../includes/audit.php';
eca_admin_require('education.manage');
header('Cache-Control: no-store');
$conn = eca_education_db();
eca_education_admin_require_ready($conn, 'Resource');
$id = (int) ($_GET['id'] ?? 0);
$row = ['title' => '', 'description' => '', 'category_id' => '', 'resource_type' => 'guide', 'file_path' => '', 'external_url' => '', 'status' => 'DRAFT', 'is_featured' => 0];
if ($id > 0) {
    $found = eca_education_one($conn, 'SELECT * FROM education_resources WHERE id = ? LIMIT 1', [$id]);
    if ($found) {
        $row = $found;
    }
}
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
    $title = trim((string) ($_POST['title'] ?? ''));
    $status = strtoupper((string) ($_POST['status'] ?? 'DRAFT'));
    if (!isset(eca_education_publish_statuses()[$status])) {
        $status = 'DRAFT';
    }
    $filePath = trim((string) ($row['file_path'] ?? ''));
    if (!empty($_FILES['file']['tmp_name'])) {
        $stored = eca_education_store_upload($_FILES['file'], 'res');
        if ($stored) {
            $filePath = $stored;
        } else {
            $notice = 'Upload failed. Use PDF, Office, image or MP4 files up to 15 MB.';
        }
    }
    $values = [
        $title,
        trim((string) ($_POST['description'] ?? '')),
        (int) ($_POST['category_id'] ?? 0) ?: null,
        trim((string) ($_POST['resource_type'] ?? 'guide')),
        $filePath !== '' ? $filePath : null,
        $filePath !== '' ? pathinfo($filePath, PATHINFO_EXTENSION) : null,
        trim((string) ($_POST['external_url'] ?? '')),
        $status,
        !empty($_POST['is_featured']) ? 1 : 0,
    ];
    if ($title === '') {
        $notice = 'Title is required.';
    } elseif ($notice === '' && $id > 0) {
        $values[] = $id;
        $conn->prepare('UPDATE education_resources SET title=?, description=?, category_id=?, resource_type=?, file_path=?, file_type=?, external_url=?, status=?, is_featured=? WHERE id=?')->execute($values);
        eca_audit('education.resource.update', 'education_resources', (string) $id);
        $notice = 'Resource saved.';
        $row = eca_education_one($conn, 'SELECT * FROM education_resources WHERE id = ? LIMIT 1', [$id]) ?: $row;
    } elseif ($notice === '') {
        $conn->prepare('INSERT INTO education_resources (title, description, category_id, resource_type, file_path, file_type, external_url, status, is_featured) VALUES (?,?,?,?,?,?,?,?,?)')->execute($values);
        $id = (int) $conn->lastInsertId();
        eca_audit('education.resource.create', 'education_resources', (string) $id);
        header('Location: /admin/education-resource-edit.php?id=' . $id);
        exit;
    }
}
$style = eca_education_admin_field_style();
$csrf = eca_admin_csrf();
eca_admin_hub_start($id ? 'Edit resource' : 'Add resource', 'education');
?>
<div class="hub-hello"><h1 class="hub-hello-title"><?= $id ? 'Edit resource' : 'Add resource' ?></h1></div>
<?php eca_education_admin_nav('resources'); ?>
<?php if ($notice): ?><p class="hub-card"><?= eca_admin_h($notice) ?></p><?php endif; ?>
<form class="hub-card" method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
    <p><label>Title<br><input name="title" value="<?= eca_admin_h($row['title'] ?? '') ?>" class="form-control"></label></p>
    <p><label>Description<br><textarea name="description" rows="4" class="form-control"><?= eca_admin_h($row['description'] ?? '') ?></textarea></label></p>
    <p><label>Category<br>
        <select name="category_id" class="form-control">
            <option value="">None</option>
            <?php foreach (eca_education_categories($conn, 'resources', false) as $cat): ?>
                <option value="<?= (int) $cat['id'] ?>"<?= (int) ($row['category_id'] ?? 0) === (int) $cat['id'] ? ' selected' : '' ?>><?= eca_admin_h($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label></p>
    <p><label>Type
        <select name="resource_type" class="form-control">
            <?php foreach (eca_education_resource_types() as $key => $label): ?>
                <option value="<?= eca_admin_h($key) ?>"<?= ($row['resource_type'] ?? '') === $key ? ' selected' : '' ?>><?= eca_admin_h($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label></p>
    <p><label>File upload<br><input type="file" name="file"><?php if (!empty($row['file_path'])): ?><br><small>Current: <?= eca_admin_h($row['file_path']) ?></small><?php endif; ?></label></p>
    <p><label>Or link / video URL<br><input name="external_url" value="<?= eca_admin_h($row['external_url'] ?? '') ?>" class="form-control"></label></p>
    <p><label>Status
        <select name="status" class="form-control">
            <?php foreach (eca_education_publish_statuses() as $key => $label): ?>
                <option value="<?= eca_admin_h($key) ?>"<?= ($row['status'] ?? '') === $key ? ' selected' : '' ?>><?= eca_admin_h($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label></p>
    <p><label><input type="checkbox" name="is_featured" value="1"<?= !empty($row['is_featured']) ? ' checked' : '' ?>> Feature this resource</label></p>
    <button class="hub-btn" type="submit">Save resource</button>
    <a class="hub-home-link" href="/admin/education-resources.php">Back</a>
</form>
<?php eca_admin_hub_end(); ?>
